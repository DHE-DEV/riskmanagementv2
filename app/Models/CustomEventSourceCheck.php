<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ergebnis einer KI-Pruefung: stimmt der erfasste Stand eines Ereignisses
 * noch mit seiner Quelle ueberein?
 */
class CustomEventSourceCheck extends Model
{
    public const STATUS_UNCHANGED = 'unchanged';

    public const STATUS_CHANGED = 'changed';

    public const STATUS_UNCLEAR = 'unclear';

    public const STATUS_ERROR = 'error';

    public const UPDATED_AT = null;

    protected $fillable = ['custom_event_id', 'url', 'url_hash', 'status', 'summary', 'changes', 'suggestion', 'proposals', 'model', 'input_tokens', 'output_tokens', 'cost', 'checked_by'];

    protected $casts = [
        'changes' => 'array',
        'proposals' => 'array',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cost' => 'float',
    ];

    public static function hashFor(string $url): string
    {
        return hash('sha256', trim($url));
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(CustomEvent::class, 'custom_event_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
