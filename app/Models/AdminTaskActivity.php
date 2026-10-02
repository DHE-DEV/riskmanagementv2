<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein Eintrag im Verlauf einer Aufgabe: angelegt, geaendert oder Notiz.
 * Eintraege werden nur angehaengt, nie veraendert.
 */
class AdminTaskActivity extends Model
{
    public const TYPE_CREATED = 'created';

    public const TYPE_CHANGED = 'changed';

    public const TYPE_NOTE = 'note';

    public const UPDATED_AT = null;

    protected $fillable = ['task_id', 'user_id', 'type', 'body', 'changes'];

    protected $casts = [
        'changes' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(AdminTask::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
