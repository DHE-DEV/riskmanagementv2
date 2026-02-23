<?php

namespace App\Models;

use App\Models\Folder\BaseCustomerModel;
use App\Models\Folder\Folder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Label extends BaseCustomerModel
{
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
