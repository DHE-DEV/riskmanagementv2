<?php

namespace App\Models\TravelDetail;

use Illuminate\Database\Eloquent\Model;

/**
 * Wasserstand des account-uebergreifenden Delta-Syncs (Keyset-Cursor).
 */
class PdsGlobalSyncState extends Model
{
    protected $table = 'pds_global_sync_states';

    public const KEY_TRAVEL_DETAILS = 'travel_details_changes';

    protected $fillable = [
        'key',
        'cursor_last_change_at',
        'cursor_id',
        'last_run_at',
    ];

    protected $casts = [
        'cursor_last_change_at' => 'datetime',
        'last_run_at' => 'datetime',
    ];

    public static function forKey(string $key): self
    {
        return static::firstOrCreate(['key' => $key]);
    }
}
