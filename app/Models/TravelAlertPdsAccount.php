<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ein pds-Konto, dessen Reisen für den Travel Alert lokal gespiegelt werden.
 *
 * @property int $pds_account_id
 * @property \Illuminate\Support\Carbon $last_requested_at
 * @property \Illuminate\Support\Carbon|null $full_synced_at
 * @property \Illuminate\Support\Carbon|null $synced_from
 * @property int $trips_count
 */
class TravelAlertPdsAccount extends Model
{
    protected $primaryKey = 'pds_account_id';

    public $incrementing = false;

    protected $fillable = [
        'pds_account_id',
        'last_requested_at',
        'full_synced_at',
        'synced_from',
        'trips_count',
    ];

    protected function casts(): array
    {
        return [
            'last_requested_at' => 'datetime',
            'full_synced_at' => 'datetime',
            'synced_from' => 'date',
            'trips_count' => 'integer',
        ];
    }

    /**
     * Deckt der Spiegel einen Zeitraum ab, der an diesem Tag beginnt?
     */
    public function coversWindowFrom(string $startDate): bool
    {
        return $this->full_synced_at !== null
            && $this->synced_from !== null
            && $startDate >= $this->synced_from->toDateString();
    }
}
