<?php

namespace App\Models\Push;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gruppe von Geraeten in der TripData-App (Familie, Reisegruppe). Der Ersteller verwaltet die
 * Mitglieder; alle angenommenen Mitglieder sehen sich gegenseitig und koennen Reisen mit der Gruppe teilen.
 */
class AppGroup extends Model
{
    use HasUlids;

    public const MAX_MEMBERS = 50;

    public const MAX_OWNED = 20;

    protected $fillable = ['owner_device_id', 'name'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(AppDevice::class, 'owner_device_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(AppGroupMember::class);
    }

    public function trips(): BelongsToMany
    {
        return $this->belongsToMany(AppSharedTrip::class, 'app_shared_trip_groups');
    }

    /** IDs der angenommenen Mitglieder. */
    public function acceptedDeviceIds(): array
    {
        return $this->members()->where('status', AppGroupMember::STATUS_ACCEPTED)->pluck('app_device_id')->all();
    }
}
