<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Im Admin-Bereich gepflegte Einstellung (Schluessel/Wert).
 *
 * Gelesen wird fehlertolerant: fehlt die Tabelle noch (Migration nicht
 * gelaufen) oder laesst sich ein Wert nicht entschluesseln, gilt der
 * Vorgabewert – die Anwendung faellt dann auf die .env zurueck.
 */
class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'is_encrypted', 'updated_by'];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /** @var array<string, ?string> Werte, die in dieser Anfrage schon gelesen wurden */
    protected static array $resolved = [];

    public static function read(string $key, ?string $default = null): ?string
    {
        if (! array_key_exists($key, static::$resolved)) {
            static::$resolved[$key] = rescue(function () use ($key) {
                $setting = static::query()->where('key', $key)->first();

                if (! $setting || $setting->value === null || $setting->value === '') {
                    return null;
                }

                return $setting->is_encrypted ? Crypt::decryptString($setting->value) : $setting->value;
            }, null, false);
        }

        return static::$resolved[$key] ?? $default;
    }

    /**
     * Wert setzen; null oder leer entfernt die Einstellung.
     */
    public static function write(string $key, ?string $value, bool $encrypted = false): void
    {
        unset(static::$resolved[$key]);

        if ($value === null || $value === '') {
            static::query()->where('key', $key)->delete();

            return;
        }

        static::query()->updateOrCreate(['key' => $key], [
            'value' => $encrypted ? Crypt::encryptString($value) : $value,
            'is_encrypted' => $encrypted,
            'updated_by' => auth('web')->id(),
        ]);
    }

    /**
     * Den Zwischenspeicher der Anfrage leeren (fuer Tests und lange Laeufe).
     */
    public static function flushResolved(): void
    {
        static::$resolved = [];
    }
}
