<?php

namespace App\Models\Push;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein Reise-Link (tid), fuer den ein Geraet bei wichtigen Aenderungen einen Push will.
 * notified_change_at verhindert doppelte Pushs fuer dieselbe Aenderung.
 */
class AppDeviceTravelLink extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['app_device_id', 'tid', 'notified_change_at'];

    protected function casts(): array
    {
        return [
            'notified_change_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(AppDevice::class, 'app_device_id');
    }

    /**
     * Reise-ID wie in der App und auf travel-details.eu: 12 Zeichen, gross, ohne Striche.
     */
    public static function normalizeTid(?string $tid): ?string
    {
        $tid = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $tid));

        return strlen($tid) === 12 ? $tid : null;
    }
}
