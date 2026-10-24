<?php

namespace App\Models\Push;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sicherung der TripData-App eines Geraets (PUT /api/v1/devices/{id}/backup). Der Inhalt ist fuer die Plattform
 * undurchsichtiges JSON und liegt gzip-komprimiert in payload; counts liefert die App nur zur Anzeige.
 */
class AppDeviceBackup extends Model
{
    /** Hoechstens 8 MB unkomprimiert. */
    public const MAX_BYTES = 8 * 1024 * 1024;

    protected $fillable = ['app_device_id', 'schema', 'platform', 'app_version', 'payload', 'size', 'counts', 'backed_up_at'];

    protected $hidden = ['payload'];

    protected function casts(): array
    {
        return [
            'schema' => 'integer',
            'size' => 'integer',
            // object statt array: {} bleibt {} (und wird nicht zu [])
            'counts' => 'object',
            'backed_up_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(AppDevice::class, 'app_device_id');
    }

    /** Das JSON der App, wie gesichert. */
    public function rawData(): string
    {
        $payload = is_resource($this->payload) ? stream_get_contents($this->payload) : (string) $this->payload;

        return (string) gzdecode($payload);
    }

    /** Kopfdaten ohne Inhalt. */
    public function toApi(): array
    {
        return [
            'backed_up_at' => $this->backed_up_at?->toIso8601String(),
            'size' => $this->size,
            'schema' => $this->schema,
            'platform' => $this->platform,
            'app_version' => $this->app_version,
            'counts' => $this->counts ?? new \stdClass,
        ];
    }
}
