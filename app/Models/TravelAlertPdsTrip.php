<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eine gespiegelte pds-Reise (nur die Felder, die der Travel Alert liest).
 *
 * @property int $pds_account_id
 * @property string $tid
 * @property array<string, mixed> $payload
 */
class TravelAlertPdsTrip extends Model
{
    protected $fillable = [
        'pds_account_id',
        'tid',
        'start_date',
        'end_date',
        'payload',
        'last_change_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'payload' => 'array',
            'last_change_at' => 'datetime',
        ];
    }
}
