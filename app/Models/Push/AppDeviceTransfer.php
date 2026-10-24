<?php

namespace App\Models\Push;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Anfrage einer neuen Installation, die Daten eines anderen (alten) Geraets zu uebernehmen. Das alte Geraet bestaetigt
 * oder lehnt ab; nach der Bestaetigung uebernimmt die neue Installation beim naechsten Abruf Identitaet und Sicherung.
 */
class AppDeviceTransfer extends Model
{
    use HasUlids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    /** Gueltigkeit einer Anfrage bzw. nach der Bestaetigung, in Minuten. */
    public const TTL_MINUTES = 15;

    /** So lange liefert ein erneuter Abruf nach dem Abschluss noch einmal das Secret, in Minuten. */
    public const SECRET_KEEP_MINUTES = 10;

    protected $fillable = [
        'from_device_id', 'to_device_id', 'to_secret_hash', 'to_platform', 'to_label',
        'status', 'expires_at', 'approved_at', 'completed_at', 'secret_encrypted',
    ];

    protected $hidden = ['to_secret_hash', 'secret_encrypted'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'secret_encrypted' => 'encrypted',
        ];
    }

    public function fromDevice(): BelongsTo
    {
        return $this->belongsTo(AppDevice::class, 'from_device_id');
    }

    /** Offen: wartet auf Bestaetigung oder Abruf. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    /** Offene Anfrage abgelaufen → expired (beim Lesen). */
    public function expireIfDue(): static
    {
        if (in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED], true) && $this->expires_at->isPast()) {
            $this->forceFill(['status' => self::STATUS_EXPIRED])->save();
        }

        return $this;
    }

    /** Secret fuer den erneuten Abruf noch vorhanden? Sonst (nach SECRET_KEEP_MINUTES) loeschen. */
    public function keptSecret(): ?string
    {
        if ($this->secret_encrypted === null) {
            return null;
        }
        if (! $this->completed_at || $this->completed_at->lt(now()->subMinutes(self::SECRET_KEEP_MINUTES))) {
            $this->forceFill(['secret_encrypted' => null])->save();

            return null;
        }

        return $this->secret_encrypted;
    }

    public function secretMatches(?string $secret): bool
    {
        return is_string($secret) && $secret !== '' && hash_equals($this->to_secret_hash, hash('sha256', $secret));
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
