<?php

namespace App\Models\Push;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Einladungslink zu einer geteilten Reise oder einer Gruppe (siehe Migration app_invites).
 */
class AppInvite extends Model
{
    public const DAYS = 30;

    public const PATH = 'einladung';

    protected $fillable = ['code', 'created_by_device_id', 'app_shared_trip_id', 'app_group_id', 'uses', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'uses' => 'integer'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AppDevice::class, 'created_by_device_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(AppSharedTrip::class, 'app_shared_trip_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AppGroup::class, 'app_group_id');
    }

    public function isValid(): bool
    {
        return $this->expires_at->isFuture() && ($this->trip !== null || $this->group !== null);
    }

    public static function newCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= AppDevice::SHARE_ALPHABET[random_int(0, strlen(AppDevice::SHARE_ALPHABET) - 1)];
            }
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /** Gueltigen Link fuer Reise bzw. Gruppe holen oder neu anlegen (`renew` = neuen Code, alter wird ungueltig). */
    public static function for(AppDevice $device, ?AppSharedTrip $trip, ?AppGroup $group, bool $renew = false): self
    {
        $query = self::where($trip ? 'app_shared_trip_id' : 'app_group_id', $trip?->id ?? $group?->id);
        if ($renew) {
            $query->delete();
        } elseif ($existing = $query->where('expires_at', '>', now()->addDays(3))->latest('id')->first()) {
            return $existing;
        }

        return self::create([
            'code' => self::newCode(),
            'created_by_device_id' => $device->id,
            'app_shared_trip_id' => $trip?->id,
            'app_group_id' => $group?->id,
            'expires_at' => now()->addDays(self::DAYS),
        ]);
    }

    public function formattedCode(): string
    {
        return AppDevice::formatShareCode($this->code);
    }

    public function url(): string
    {
        return rtrim((string) config('app.url'), '/').'/'.self::PATH.'/'.$this->formattedCode();
    }

    /** Was die Einladung zeigt – fuer die App und die Webseite. */
    public function preview(): array
    {
        $trip = $this->trip;
        $group = $this->group;

        return [
            'code' => $this->formattedCode(),
            'kind' => $trip ? 'trip' : 'group',
            'trip_id' => $trip?->id,
            'name' => $trip?->name ?? $group?->name,
            'from' => $this->creator?->publicName(),
            'start_date' => $trip?->start_date?->toDateString(),
            'end_date' => $trip?->end_date?->toDateString(),
            'country_codes' => $trip ? array_values((array) $trip->country_codes) : [],
            'sights_count' => $trip?->sharesSights() ? collect((array) $trip->sights)->reject(fn ($s) => $s['deleted'] ?? false)->count() : null,
            'has_list' => $trip ? $trip->list !== null : null,
            'members' => $group ? $group->members()->where('status', AppGroupMember::STATUS_ACCEPTED)->count() : null,
            'expires_at' => $this->expires_at->toIso8601String(),
            'url' => $this->url(),
        ];
    }

    public function toApi(): array
    {
        return ['code' => $this->formattedCode(), 'url' => $this->url(), 'expires_at' => $this->expires_at->toIso8601String()];
    }
}
