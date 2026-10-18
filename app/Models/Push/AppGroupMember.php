<?php

namespace App\Models\Push;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mitgliedschaft eines Geraets in einer Gruppe: eingeladen oder angenommen (der Ersteller ist sofort angenommen).
 */
class AppGroupMember extends Model
{
    public const STATUS_INVITED = 'invited';

    public const STATUS_ACCEPTED = 'accepted';

    protected $fillable = ['app_group_id', 'app_device_id', 'status'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AppGroup::class, 'app_group_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(AppDevice::class, 'app_device_id');
    }
}
