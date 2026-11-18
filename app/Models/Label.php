<?php

namespace App\Models;

use App\Models\Folder\BaseCustomerModel;
use App\Models\Folder\Folder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Label extends BaseCustomerModel
{
    /**
     * Farben für Labels – gut unterscheidbar, lesbar mit weißer Schrift.
     * Neue Labels bekommen reihum die nächste Farbe.
     */
    public const PALETTE = [
        '#2563EB', '#DC2626', '#059669', '#D97706', '#7C3AED',
        '#DB2777', '#0891B2', '#65A30D', '#EA580C', '#475569',
    ];

    protected $fillable = [
        'customer_id',
        'name',
        'description',
        'color',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(Folder::class, 'folder_label')
            ->withTimestamps();
    }

    public function customEvents(): BelongsToMany
    {
        return $this->belongsToMany(CustomEvent::class, 'custom_event_label')
            ->withTimestamps();
    }

    /**
     * Get labels for a PDS API trip by customer and trip ID.
     */
    public static function forPdsTrip(int $customerId, string $pdsTid): \Illuminate\Database\Eloquent\Collection
    {
        $labelIds = \Illuminate\Support\Facades\DB::table('pds_trip_label')
            ->where('customer_id', $customerId)
            ->where('pds_tid', $pdsTid)
            ->pluck('label_id');

        return static::whereIn('id', $labelIds)->get();
    }

    /**
     * Get labels for multiple PDS API trips at once (batch query).
     */
    public static function forPdsTrips(int $customerId, array $pdsTids): array
    {
        if (empty($pdsTids)) {
            return [];
        }

        $pivots = \Illuminate\Support\Facades\DB::table('pds_trip_label')
            ->where('customer_id', $customerId)
            ->whereIn('pds_tid', $pdsTids)
            ->get();

        if ($pivots->isEmpty()) {
            return [];
        }

        $labelIds = $pivots->pluck('label_id')->unique()->toArray();
        $labels = static::whereIn('id', $labelIds)->get()->keyBy('id');

        $result = [];
        foreach ($pivots as $pivot) {
            if ($labels->has($pivot->label_id)) {
                $label = $labels->get($pivot->label_id);
                $result[$pivot->pds_tid][] = [
                    'id' => $label->id,
                    'name' => $label->name,
                    'color' => $label->color,
                    'icon' => $label->icon,
                ];
            }
        }

        return $result;
    }

    /**
     * Get labels for multiple custom events at once (batch query), scoped by customer.
     */
    public static function forCustomEvents(int $customerId, array $eventIds): array
    {
        if (empty($eventIds)) {
            return [];
        }

        $pivots = \Illuminate\Support\Facades\DB::table('custom_event_label')
            ->join('labels', 'labels.id', '=', 'custom_event_label.label_id')
            ->where('labels.customer_id', $customerId)
            ->whereIn('custom_event_label.custom_event_id', $eventIds)
            ->select('custom_event_label.custom_event_id', 'custom_event_label.label_id')
            ->get();

        if ($pivots->isEmpty()) {
            return [];
        }

        $labelIds = $pivots->pluck('label_id')->unique()->toArray();
        $labels = static::whereIn('id', $labelIds)->get()->keyBy('id');

        $result = [];
        foreach ($pivots as $pivot) {
            if ($labels->has($pivot->label_id)) {
                $label = $labels->get($pivot->label_id);
                $result[$pivot->custom_event_id][] = [
                    'id' => $label->id,
                    'name' => $label->name,
                    'color' => $label->color,
                    'icon' => $label->icon,
                ];
            }
        }

        return $result;
    }

    /**
     * Get event IDs that have a specific label for a customer.
     */
    /**
     * IDs der Ereignisse, die mindestens eines der Labels des Kunden tragen.
     *
     * @param  array<int, string>  $labelIds
     * @return array<int, int>
     */
    public static function eventIdsForLabels(int $customerId, array $labelIds): array
    {
        if ($labelIds === []) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('custom_event_label')
            ->join('labels', 'labels.id', '=', 'custom_event_label.label_id')
            ->where('labels.customer_id', $customerId)
            ->whereIn('custom_event_label.label_id', $labelIds)
            ->pluck('custom_event_label.custom_event_id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Nächste Palettenfarbe für ein neues Label des Kunden.
     */
    public static function nextColor(int $customerId): string
    {
        $count = static::withoutGlobalScopes()->where('customer_id', $customerId)->count();

        return self::PALETTE[$count % count(self::PALETTE)];
    }

    /**
     * Label des Kunden mit diesem Namen holen oder neu anlegen (mit Palettenfarbe).
     */
    public static function findOrCreateForCustomer(int $customerId, string $name): self
    {
        $label = static::withoutGlobalScopes()->where('customer_id', $customerId)->where('name', $name)->first();

        return $label ?? static::create([
            'customer_id' => $customerId,
            'name' => $name,
            'color' => static::nextColor($customerId),
        ]);
    }

    /**
     * Teil des Cache-Schlüssels für Label-gefilterte Daten: gewählte Labels plus ein
     * Zähler, der bei jeder Label-Zuordnung steigt – so liefert der Cache nie einen alten Stand.
     *
     * @param  array<int, string>  $labelIds
     */
    public static function filterCacheKey(int $customerId, array $labelIds): string
    {
        if ($labelIds === []) {
            return 'nolabel';
        }

        sort($labelIds);

        return implode('-', $labelIds).'_v'.\Illuminate\Support\Facades\Cache::get("labels_version_{$customerId}", 0);
    }

    /**
     * Nach einer Änderung an Labels oder Zuordnungen: Label-gefilterte Caches verwerfen.
     */
    public static function bumpCacheVersion(int $customerId): void
    {
        $key = "labels_version_{$customerId}";
        \Illuminate\Support\Facades\Cache::forever($key, (int) \Illuminate\Support\Facades\Cache::get($key, 0) + 1);
    }

    public static function eventIdsForLabel(int $customerId, int $labelId): array
    {
        return \Illuminate\Support\Facades\DB::table('custom_event_label')
            ->join('labels', 'labels.id', '=', 'custom_event_label.label_id')
            ->where('labels.customer_id', $customerId)
            ->where('custom_event_label.label_id', $labelId)
            ->pluck('custom_event_label.custom_event_id')
            ->unique()
            ->values()
            ->toArray();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
