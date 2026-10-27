<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Vertrag mit einem Kunden – z. B. ein Rahmenvertrag mit einer grossen Firma:
 * Laufzeit, Bedingungen und individuelle Preise je Auspraegung
 * (CustomerContractPrice). Was nicht eingetragen ist, kostet den
 * Standardpreis aus dem Produktkatalog.
 */
class CustomerContract extends Model
{
    protected $fillable = [
        'customer_id',
        'name',
        'starts_on',
        'ends_on',
        'minimum_term_months',
        'notice_period',
        'auto_renews',
        'annual_increase_percent',
        'increase_reminded_for',
        'conditions',
        'notes',
    ];

    protected $casts = [
        'starts_on' => 'date:Y-m-d',
        'ends_on' => 'date:Y-m-d',
        'auto_renews' => 'boolean',
        'annual_increase_percent' => 'decimal:2',
        'increase_reminded_for' => 'date:Y-m-d',
        'conditions' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(CustomerContractPrice::class);
    }

    public function scopeOn(Builder $query, $date = null): Builder
    {
        $day = ($date ? Carbon::parse($date) : today())->toDateString();

        return $query->whereDate('starts_on', '<=', $day)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $day));
    }

    /** Erster Vertrag des Kunden, der sich mit [starts, ends] ueberschneidet. */
    public static function overlaps(int $customerId, string $startsOn, ?string $endsOn, ?int $ignoreId = null): ?self
    {
        return self::query()
            ->where('customer_id', $customerId)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $startsOn))
            ->when($endsOn, fn ($q) => $q->whereDate('starts_on', '<=', $endsOn))
            ->orderBy('starts_on')
            ->first();
    }

    /** Individueller Preis fuer die Auspraegung, sonst null (= Standardpreis). */
    public function priceFor(int $productVersionId): ?CustomerContractPrice
    {
        return $this->prices->firstWhere('product_version_id', $productVersionId);
    }

    /**
     * Naechster Jahrestag des Vertragsbeginns ab $from (Standard: heute) –
     * Stichtag fuer die vereinbarte Preiserhoehung.
     */
    public function nextAnniversary($from = null): Carbon
    {
        $from = ($from ? Carbon::parse($from) : today())->startOfDay();
        $anniversary = $this->starts_on->copy()->addYearNoOverflow();

        while ($anniversary->lt($from)) {
            $anniversary->addYearNoOverflow();
        }

        return $anniversary;
    }

    /** z. B. "3 % pro Jahr"; null ohne Vereinbarung. */
    public function increaseLabel(): ?string
    {
        if ($this->annual_increase_percent === null || (float) $this->annual_increase_percent <= 0) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $this->annual_increase_percent, 2, ',', '.'), '0'), ',').' % pro Jahr';
    }

    public function rangeLabel(): string
    {
        return $this->starts_on->format('d.m.Y').' – '.($this->ends_on?->format('d.m.Y') ?? 'offen');
    }
}
