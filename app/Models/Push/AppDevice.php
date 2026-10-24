<?php

namespace App\Models\Push;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    /** Zeichen der App-ID: ohne 0/O, 1/I/L und U, damit sie sich vorlesen und abtippen laesst. */
    public const SHARE_ALPHABET = '23456789ABCDEFGHJKMNPQRSTVWXYZ';

    protected $fillable = [
        'customer_id',
        'app',
        'platform',
        'push_token',
        'push_environment',
        'locale',
        'app_version',
        'notifications_enabled',
        'notify_itinerary',
        'secret_hash',
        'share_code',
        'display_name',
        'last_seen_at',
        'token_invalid_at',
        'recovery_hash',
        'recovery_created_at',
    ];

    protected $hidden = ['secret_hash', 'push_token', 'recovery_hash'];

    protected function casts(): array
    {
        return [
            'notifications_enabled' => 'boolean',
            'notify_itinerary' => 'boolean',
            'last_seen_at' => 'datetime',
            'token_invalid_at' => 'datetime',
            'recovery_created_at' => 'datetime',
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

    public function backup(): HasOne
    {
        return $this->hasOne(AppDeviceBackup::class);
    }

    /**
     * App-ID zum Teilen; aeltere Geraete bekommen sie beim ersten Bedarf.
     */
    public function ensureShareCode(): string
    {
        if ($this->share_code) {
            return $this->share_code;
        }

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= self::SHARE_ALPHABET[random_int(0, strlen(self::SHARE_ALPHABET) - 1)];
            }
        } while (self::where('share_code', $code)->exists());

        $this->forceFill(['share_code' => $code])->save();

        return $code;
    }

    /** "k7m3-9qxa", "K7M39QXA" → "K7M39QXA"; null, wenn es keine App-ID sein kann. */
    public static function normalizeShareCode(?string $code): ?string
    {
        $code = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $code));

        return strlen($code) === 8 && strspn($code, self::SHARE_ALPHABET) === 8 ? $code : null;
    }

    /** "K7M39QXA" → "K7M3-9QXA" */
    public static function formatShareCode(?string $code): ?string
    {
        return $code ? substr($code, 0, 4).'-'.substr($code, 4) : null;
    }

    /** Name fuer andere: Anzeigename oder die App-ID. */
    public function publicName(): string
    {
        return filled($this->display_name) ? $this->display_name : (self::formatShareCode($this->share_code) ?? 'TripData');
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
     * Neuer Wiederherstellungscode (12 Zeichen aus SHARE_ALPHABET): der Klartext geht genau einmal an die App,
     * gespeichert wird nur der Hash. Ein vorheriger Code wird damit ungueltig.
     */
    public function newRecoveryCode(): string
    {
        $code = '';
        for ($i = 0; $i < 12; $i++) {
            $code .= self::SHARE_ALPHABET[random_int(0, strlen(self::SHARE_ALPHABET) - 1)];
        }

        $this->forceFill(['recovery_hash' => hash('sha256', $code), 'recovery_created_at' => now()])->save();

        return $code;
    }

    /** "abcd-efgh-jkmn", "ABCDEFGHJKMN" → "ABCDEFGHJKMN"; null, wenn es kein Wiederherstellungscode sein kann. */
    public static function normalizeRecoveryCode(?string $code): ?string
    {
        $code = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $code));

        return strlen($code) === 12 && strspn($code, self::SHARE_ALPHABET) === 12 ? $code : null;
    }

    /** "ABCDEFGHJKMN" → "ABCD-EFGH-JKMN" */
    public static function formatRecoveryCode(string $code): string
    {
        return implode('-', str_split($code, 4));
    }

    public function recoveryCodeMatches(?string $code): bool
    {
        $code = self::normalizeRecoveryCode($code);

        return $code !== null && filled($this->recovery_hash) && hash_equals($this->recovery_hash, hash('sha256', $code));
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
            'notify_itinerary' => $this->notify_itinerary ?? true,
            'has_push_token' => filled($this->push_token) && $this->token_invalid_at === null,
            'share_code' => self::formatShareCode($this->share_code),
            'display_name' => $this->display_name,
        ];
    }
}
