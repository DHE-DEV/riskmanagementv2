<?php

namespace App\Models;

use App\Support\TravelInformationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Abrechnungsintervall eines Kunden fuer einen Zeitraum – fuer alle
 * abonnierten Dienste (module null) oder ein einzelnes Modul. Ein Eintrag fuer
 * ein Modul geht dem fuer alle Dienste vor (intervalFor()). Zeitraeume
 * desselben Moduls ueberschneiden sich nicht.
 */
class CustomerBillingInterval extends Model
{
    public const INTERVALS = Cooperation::BILLING_INTERVALS;

    protected $fillable = [
        'customer_id',
        'module',
        'interval',
        'discount_percent',
        'starts_on',
        'ends_on',
        'notes',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'starts_on' => 'date:Y-m-d',
        'ends_on' => 'date:Y-m-d',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeOn(Builder $query, $date = null): Builder
    {
        $day = ($date ? Carbon::parse($date) : today())->toDateString();

        return $query->whereDate('starts_on', '<=', $day)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $day));
    }

    /** Erster Zeitraum desselben Moduls, der sich mit [starts, ends] ueberschneidet. */
    public static function overlaps(int $customerId, ?string $module, string $startsOn, ?string $endsOn, array $ignoreIds = []): ?self
    {
        return self::query()
            ->where('customer_id', $customerId)
            ->when($module === null, fn ($q) => $q->whereNull('module'), fn ($q) => $q->where('module', $module))
            ->when($ignoreIds, fn ($q) => $q->whereKeyNot($ignoreIds))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $startsOn))
            ->when($endsOn, fn ($q) => $q->whereDate('starts_on', '<=', $endsOn))
            ->orderBy('starts_on')
            ->first();
    }

    /** Intervall, das am Tag $date fuer das Modul gilt (Modul vor „alle Dienste“). */
    public static function intervalFor(int $customerId, ?string $module = null, $date = null): ?self
    {
        $periods = self::query()->where('customer_id', $customerId)->on($date)
            ->where(fn ($q) => $q->whereNull('module')->when($module, fn ($q) => $q->orWhere('module', $module)))
            ->get();

        return $periods->firstWhere('module', $module) ?? $periods->firstWhere('module', null);
    }

    public function moduleLabel(): string
    {
        return TravelInformationService::moduleLabel($this->module);
    }

    public function intervalLabel(): string
    {
        return self::INTERVALS[$this->interval] ?? $this->interval;
    }

    /** z. B. "10 % Rabatt"; null ohne Rabatt. */
    public function discountLabel(): ?string
    {
        if ($this->discount_percent === null || (float) $this->discount_percent <= 0) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $this->discount_percent, 2, ',', '.'), '0'), ',').' % Rabatt';
    }

    public function rangeLabel(): string
    {
        return $this->starts_on->format('d.m.Y').' – '.($this->ends_on?->format('d.m.Y') ?? 'offen');
    }
}
