<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Erinnerung an eine Aufgabe. Eine Aufgabe kann beliebig viele haben – zu
 * verschiedenen Zeitpunkten und fuer verschiedene Personen.
 *
 * Ohne Person (user_id = null) geht die Erinnerung an die Person, bei der die
 * Aufgabe zum Zeitpunkt des Versands liegt.
 */
class AdminTaskReminder extends Model
{
    protected $fillable = ['task_id', 'remind_at', 'user_id', 'note', 'sent_at', 'created_by'];

    protected $casts = [
        'remind_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(AdminTask::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }

    public function isDue(): bool
    {
        return ! $this->isSent() && $this->remind_at !== null && $this->remind_at->lte(now());
    }

    /**
     * An wen die Erinnerung geht: die gewaehlte Person, solange sie aktiv ist –
     * sonst an die Person bzw. das Team, bei dem die Aufgabe liegt.
     *
     * @return array<int, string>
     */
    public function recipientEmails(): array
    {
        if ($this->user?->is_active && filled($this->user->email)) {
            return [$this->user->email];
        }

        return $this->task?->handlerEmails() ?? [];
    }

    /**
     * Kurzform fuer den Verlauf, z. B. "05.10.2026 09:00 an Dennis".
     */
    public function label(): string
    {
        $who = $this->user_id
            ? (trim((string) User::find($this->user_id)?->name) ?: 'unbekannt')
            : 'den aktuellen Bearbeiter';

        return $this->remind_at?->format('d.m.Y H:i').' an '.$who.(filled($this->note) ? ' („'.$this->note.'“)' : '');
    }
}
