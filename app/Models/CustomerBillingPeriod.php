<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Zeitraum, in dem ein Kunde selbststaendig oder ueber eine Kooperation
 * abgerechnet wird. Ohne Enddatum laeuft er offen weiter. Die Zeitraeume
 * eines Kunden ueberschneiden sich nicht (overlaps()).
 */
class CustomerBillingPeriod extends Model
{
    public const BILLING_MODES = [
        'self_payer' => 'Selbstzahler',
        'cooperation' => 'Über Kooperation',
    ];

    protected $fillable = [
        'customer_id',
        'cooperation_id',
        'billing_mode',
        'starts_on',
        'ends_on',
        'notes',
    ];

    protected $casts = [
        'starts_on' => 'date:Y-m-d',
        'ends_on' => 'date:Y-m-d',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cooperation(): BelongsTo
    {
        return $this->belongsTo(Cooperation::class);
    }

    /** Zeitraeume, die am Tag $date gelten (Standard: heute). */
    public function scopeOn(Builder $query, CarbonInterface|string|null $date = null): Builder
    {
        $day = ($date ? \Illuminate\Support\Carbon::parse($date) : today())->toDateString();

        return $query->whereDate('starts_on', '<=', $day)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $day));
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->on(today());
    }

    /**
     * Ueberschneidet sich [starts, ends] (ends null = offen) mit einem anderen
     * Zeitraum des Kunden? Liefert den ersten Konflikt.
     */
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

    /** z. B. "Selbststaendig · Selbstzahler" oder "solamento · ueber Kooperation abgerechnet · Festbetrag …". */
    public function label(): string
    {
        if (! $this->cooperation_id) {
            return 'Selbstständig · Selbstzahler';
        }

        $name = $this->cooperation?->name ?? 'Kooperation';
        $fixedPrice = $this->cooperation?->fixedPriceLabel();

        return ($this->billing_mode === 'cooperation'
            ? $name.' · über Kooperation abgerechnet'
            : $name.' · einzeln abgerechnet (Selbstzahler)')
            .($fixedPrice ? ' · Festbetrag '.$fixedPrice : '');
    }

    /** "01.01.2026 – offen" */
    public function rangeLabel(): string
    {
        return $this->starts_on->format('d.m.Y').' – '.($this->ends_on?->format('d.m.Y') ?? 'offen');
    }
}
