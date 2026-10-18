<?php

namespace App\Models\Push;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Empfaenger einer geteilten Reise. Direkte Einladungen: invited → accepted/declined.
 * Ueber eine Gruppe braucht es keine Zeile – nur wer eine Reise der Gruppe ausblendet, bekommt
 * eine Zeile mit direct = false und declined.
 */
class AppSharedTripRecipient extends Model
{
    public const STATUS_INVITED = 'invited';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    protected $fillable = ['app_shared_trip_id', 'app_device_id', 'status', 'direct'];

    protected function casts(): array
    {
        return ['direct' => 'boolean'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(AppSharedTrip::class, 'app_shared_trip_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(AppDevice::class, 'app_device_id');
    }
}
