<?php

namespace App\Models\Push;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Ein Versand: Text je Sprache, Ziel und Zaehler. Die Versand-Jobs zaehlen atomar hoch;
 * ist der letzte Job fertig, steht der Versand auf "done".
 */
class AppPushMessage extends Model
{
    public const TYPE_TRAVEL_IMPORTANT_CHANGE = 'travel_important_change';

    public const TYPE_CUSTOM = 'custom';

    /** Einladung zu einer Reise oder Gruppe bzw. neue Reise in einer Gruppe (Teilen in der App). */
    public const TYPE_SHARE = 'share';

    /** Ein neues Geraet moechte die Daten dieses Geraets uebernehmen (Bestaetigung in der App). */
    public const TYPE_TRANSFER_REQUEST = 'transfer_request';

    public const SOURCE_SCANNER = 'scanner';

    public const SOURCE_API = 'api';

    /** Aus Admin v2 (System > Push senden). */
    public const SOURCE_ADMIN = 'admin';

    /** Teilen in der App (Einladungen). */
    public const SOURCE_SHARING = 'sharing';

    /** Geraeteuebertragung in der App (Anfrage an das alte Geraet). */
    public const SOURCE_TRANSFER = 'transfer';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENDING = 'sending';

    public const STATUS_DONE = 'done';

    protected $fillable = [
        'app', 'type', 'source', 'customer_id', 'user_id', 'title', 'body', 'data', 'target',
        'status', 'targeted', 'sent', 'failed', 'invalid', 'pending_batches', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'body' => 'array',
            'data' => 'array',
            'target' => 'array',
            'finished_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Text in der Sprache des Geraets, sonst Deutsch, sonst der erste vorhandene.
     */
    public function text(string $field, string $locale): string
    {
        $texts = (array) $this->{$field};

        return (string) ($texts[$locale] ?? $texts['de'] ?? reset($texts) ?: '');
    }

    /**
     * Ergebnis eines Versand-Jobs verbuchen; der letzte Job schliesst den Versand ab.
     */
    public function recordBatch(int $sent, int $failed, int $invalid): void
    {
        DB::table($this->getTable())->where('id', $this->id)->update([
            'sent' => DB::raw('sent + '.$sent),
            'failed' => DB::raw('failed + '.$failed),
            'invalid' => DB::raw('invalid + '.$invalid),
            'pending_batches' => DB::raw('CASE WHEN pending_batches > 0 THEN pending_batches - 1 ELSE 0 END'),
            'updated_at' => now(),
        ]);

        DB::table($this->getTable())->where('id', $this->id)->where('pending_batches', 0)
            ->whereNull('finished_at')
            ->update(['status' => self::STATUS_DONE, 'finished_at' => now()]);
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'targeted' => $this->targeted,
            'sent' => $this->sent,
            'failed' => $this->failed,
            'invalid' => $this->invalid,
            'created_at' => $this->created_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
        ];
    }
}
