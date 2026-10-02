<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Team von Mitarbeitern im Admin-Bereich. Aufgaben koennen statt bei einer
 * Person bei einem Team liegen oder von ihm verantwortet werden.
 */
class AdminTeam extends Model
{
    use SoftDeletes;

    /** Jedes Mitglied bekommt die Mail einzeln. */
    public const NOTIFY_MEMBERS = 'members';

    /** Die Mail geht an die zentrale Adresse des Teams. */
    public const NOTIFY_TEAM_EMAIL = 'team_email';

    protected $fillable = ['name', 'description', 'email', 'notify_mode'];

    protected $attributes = [
        'notify_mode' => self::NOTIFY_MEMBERS,
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_team_user', 'team_id', 'user_id');
    }

    /**
     * Mitglieder, die im Admin-Bereich arbeiten koennen.
     */
    public function activeMembers(): Collection
    {
        return $this->users->filter(fn (User $user) => $user->is_admin && $user->is_active)->values();
    }

    /**
     * An welche Adressen Benachrichtigungen fuer dieses Team gehen: an die
     * zentrale Adresse oder an jedes Mitglied einzeln. Ist "zentrale Adresse"
     * gewaehlt, aber keine hinterlegt, gehen die Mails an die Mitglieder.
     *
     * @return array<int, string>
     */
    public function notificationEmails(): array
    {
        if ($this->notify_mode === self::NOTIFY_TEAM_EMAIL && filled($this->email)) {
            return [$this->email];
        }

        return $this->activeMembers()->pluck('email')->filter()->unique()->values()->all();
    }

    public function usesTeamEmail(): bool
    {
        return $this->notify_mode === self::NOTIFY_TEAM_EMAIL && filled($this->email);
    }

    /**
     * IDs der Teams, denen ein Benutzer angehoert.
     *
     * @return array<int, int>
     */
    public static function idsForUser(int $userId): array
    {
        return static::query()
            ->whereHas('users', fn ($query) => $query->whereKey($userId))
            ->pluck('id')
            ->all();
    }

    /**
     * Bezeichnung in Listen und im Verlauf.
     */
    public function label(): string
    {
        return 'Team '.$this->name.($this->trashed() ? ' (gelöscht)' : '');
    }
}
