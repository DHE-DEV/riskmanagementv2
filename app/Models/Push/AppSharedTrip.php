<?php

namespace App\Models\Push;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eine Reise, die ein Geraet mit anderen teilt (einzelne App-IDs und/oder Gruppen). Nur der Ersteller
 * aendert sie; die anderen sehen den aktuellen Stand (revision) und koennen ihr lokal einen eigenen Namen geben.
 */
class AppSharedTrip extends Model
{
    use HasUlids;

    public const MAX_RECIPIENTS = 50;

    protected $fillable = ['owner_device_id', 'client_trip_id', 'name', 'start_date', 'end_date', 'country_codes', 'tid', 'revision'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'country_codes' => 'array',
            'revision' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(AppDevice::class, 'owner_device_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AppSharedTripRecipient::class);
    }

    public function list(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AppSharedList::class);
    }

    /**
     * Alle, die die Reise sehen: Ersteller, angenommene direkte Empfaenger und angenommene Mitglieder
     * der Gruppen – ohne die, die abgelehnt oder ausgeblendet haben.
     *
     * @return \Illuminate\Support\Collection<int, AppDevice>
     */
    public function participants(): \Illuminate\Support\Collection
    {
        $declined = $this->recipients()->where('status', AppSharedTripRecipient::STATUS_DECLINED)->pluck('app_device_id')->all();
        $ids = collect([$this->owner_device_id])
            ->merge($this->recipients()->where('status', AppSharedTripRecipient::STATUS_ACCEPTED)->pluck('app_device_id'))
            ->merge(AppGroupMember::whereIn('app_group_id', $this->groups()->pluck('app_groups.id'))->where('status', AppGroupMember::STATUS_ACCEPTED)->pluck('app_device_id'))
            ->unique()->reject(fn ($id) => in_array($id, $declined, true));

        return AppDevice::whereIn('id', $ids->all())->get()->sortBy(fn ($d) => [$d->id !== $this->owner_device_id, mb_strtolower($d->publicName())])->values();
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(AppGroup::class, 'app_shared_trip_groups')->withPivot('created_at');
    }

    /** "ABCDEFGHJKLM" → "ABCD-EFGH-JKLM", wie die App den Code zeigt. */
    public function travelLinkCode(): ?string
    {
        return $this->tid ? implode('-', str_split($this->tid, 4)) : null;
    }

    /** Inhalt der Reise fuer die Apps der anderen. */
    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'country_codes' => array_values((array) $this->country_codes),
            'travel_link_code' => $this->travelLinkCode(),
            'revision' => $this->revision,
            'owner_name' => $this->owner?->publicName(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
