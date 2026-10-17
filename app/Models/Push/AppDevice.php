<?php

namespace App\Models\Push;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Ein Geraet mit einer App (TripData), angemeldet ueber POST /api/v1/devices.
 * Anonym: kein Name, keine E-Mail – nur Push-Token, Sprache und die Reise-Links,
 * fuer die es benachrichtigt werden will. Aenderungen nur mit dem Geraete-Secret.
 */
class AppDevice extends Model
{
    use HasUlids;

    public const PLATFORM_IOS = 'ios';

    public const PLATFORM_ANDROID = 'android';

    public const LOCALES = ['de', 'en', 'nl'];

    protected $fillable = [
        'customer_id',
        'app',
        'platform',
        'push_token',
        'push_environment',
        'locale',
        'app_version',
        'notifications_enabled',
        'secret_hash',
        'last_seen_at',
        'token_invalid_at',
    ];

    protected $hidden = ['secret_hash', 'push_token'];

    protected function casts(): array
    {
        return [
            'notifications_enabled' => 'boolean',
            'last_seen_at' => 'datetime',
            'token_invalid_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function travelLinks(): HasMany
    {
        return $this->hasMany(AppDeviceTravelLink::class);
    }

    /**
     * Neues Secret: der Klartext geht genau einmal an die App, gespeichert wird nur der Hash.
     */
    public static function newSecret(): array
    {
        $secret = Str::random(48);

        return [$secret, hash('sha256', $secret)];
    }

    public function secretMatches(?string $secret): bool
    {
        return is_string($secret) && $secret !== '' && hash_equals($this->secret_hash, hash('sha256', $secret));
    }

    /**
     * Geraete, die jetzt einen Push bekommen koennen.
     */
    public function scopeReachable(Builder $query): Builder
    {
        return $query->where('notifications_enabled', true)
            ->whereNotNull('push_token')
            ->whereNull('token_invalid_at')
            ->where(fn (Builder $q) => $q->whereNull('last_seen_at')
                ->orWhere('last_seen_at', '>=', now()->subDays(config('push.device_stale_days', 180))));
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'app' => $this->app,
            'platform' => $this->platform,
            'push_environment' => $this->push_environment,
            'locale' => $this->locale,
            'app_version' => $this->app_version,
            'notifications_enabled' => $this->notifications_enabled,
            'has_push_token' => filled($this->push_token) && $this->token_invalid_at === null,
        ];
    }
}
