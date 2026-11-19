<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Status "erledigt" eines Ereignisses oder einer Reise im Travel Alert (je Kunde).
 * Ohne Zeile gilt das Element als offen.
 *
 * @property int $customer_id
 * @property string $subject_type
 * @property string $subject_key
 * @property \Illuminate\Support\Carbon $done_at
 */
class TravelAlertStatus extends Model
{
    public const TYPE_EVENT = 'event';

    public const TYPE_TRIP = 'trip';

    protected $fillable = [
        'customer_id',
        'subject_type',
        'subject_key',
        'done_at',
    ];

    protected function casts(): array
    {
        return [
            'done_at' => 'datetime',
        ];
    }

    /**
     * Zeitpunkt "erledigt" je Schlüssel (ISO 8601); offene Elemente fehlen.
     *
     * @param  array<int, int|string>  $keys
     * @return array<string, string>
     */
    public static function doneAtFor(int $customerId, string $type, array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        return static::query()
            ->where('customer_id', $customerId)
            ->where('subject_type', $type)
            ->whereIn('subject_key', array_map('strval', $keys))
            ->get(['subject_key', 'done_at'])
            ->mapWithKeys(fn (self $status) => [$status->subject_key => $status->done_at->toIso8601String()])
            ->all();
    }

    /**
     * IDs aller erledigten Ereignisse des Kunden.
     *
     * @return array<int, int>
     */
    public static function doneEventIds(int $customerId): array
    {
        return static::query()
            ->where('customer_id', $customerId)
            ->where('subject_type', self::TYPE_EVENT)
            ->pluck('subject_key')
            ->map(fn ($key) => (int) $key)
            ->all();
    }
}
