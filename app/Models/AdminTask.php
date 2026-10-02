<?php

namespace App\Models;

use App\Mail\AdminTaskAssignedMail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Aufgabe eines Mitarbeiters im Admin-Bereich.
 *
 * Drei Personen gehoeren zu einer Aufgabe: wer sie erfasst hat, wer fuer sie
 * verantwortlich ist und wer als Naechstes daran arbeitet. Ist kein naechster
 * Bearbeiter gesetzt, liegt sie beim Verantwortlichen.
 *
 * Jede Aenderung an den nachverfolgten Feldern schreibt einen Eintrag in den
 * Verlauf (AdminTaskActivity) – unabhaengig davon, wo gespeichert wird.
 */
class AdminTask extends Model
{
    use SoftDeletes;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DONE = 'done';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'status',
        'priority',
        'due_date',
        'created_by',
        'responsible_id',
        'responsible_team_id',
        'next_assignee_id',
        'next_assignee_team_id',
        'subject_type',
        'subject_id',
        'subject_token',
        'recurrence_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'due_notified_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_OPEN,
        'priority' => self::PRIORITY_NORMAL,
    ];

    /**
     * Prioritaeten, von niedrig nach dringend.
     *
     * @return array<string, string>
     */
    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => 'Niedrig',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'Hoch',
            self::PRIORITY_URGENT => 'Dringend',
        ];
    }

    /**
     * Farbpunkt je Prioritaet (Tailwind-Klasse).
     *
     * @return array<string, string>
     */
    public static function priorityDots(): array
    {
        return [
            self::PRIORITY_LOW => 'bg-zinc-400',
            self::PRIORITY_NORMAL => 'bg-sky-500',
            self::PRIORITY_HIGH => 'bg-amber-500',
            self::PRIORITY_URGENT => 'bg-red-500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'Offen',
            self::STATUS_IN_PROGRESS => 'In Arbeit',
            self::STATUS_DONE => 'Erledigt',
        ];
    }

    /**
     * Mitarbeiter, denen Aufgaben zugewiesen werden koennen: alle aktiven
     * Benutzer mit Admin-Zugang.
     */
    public static function assignableUsers()
    {
        return User::query()
            ->where('is_admin', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /**
     * Teams, denen Aufgaben zugewiesen werden koennen.
     */
    public static function assignableTeams()
    {
        return AdminTeam::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Auswahlwert fuer "Person oder Team": die ID eines Benutzers als Zahl,
     * ein Team als "team:ID".
     */
    public static function assigneeValue(?int $userId, ?int $teamId): string
    {
        return match (true) {
            (bool) $teamId => 'team:'.$teamId,
            (bool) $userId => (string) $userId,
            default => '',
        };
    }

    /**
     * Gegenstueck zu assigneeValue().
     *
     * @return array{0: ?int, 1: ?int} [Benutzer-ID, Team-ID]
     */
    public static function parseAssignee(?string $value): array
    {
        $value = trim((string) $value);

        if (str_starts_with($value, 'team:')) {
            return [null, (int) substr($value, 5) ?: null];
        }

        return [(int) $value ?: null, null];
    }

    protected static function booted(): void
    {
        static::saving(function (AdminTask $task) {
            if ($task->isDirty('status')) {
                $task->completed_at = $task->status === self::STATUS_DONE ? now() : null;
            }

            // Die Mail zur Faelligkeit geht erneut raus, wenn das Datum verschoben wird.
            if ($task->isDirty('due_date')) {
                $task->due_notified_at = null;
            }
        });

        static::created(function (AdminTask $task) {
            $task->activities()->create([
                'user_id' => auth('web')->id(),
                'type' => AdminTaskActivity::TYPE_CREATED,
            ]);

            // Die Aufgabe beginnt: Verantwortliche und naechster Bearbeiter (Person oder Team) erfahren davon.
            $task->notifyAssigned(array_merge($task->responsibleEmails(), $task->nextAssigneeEmails()), isNew: true);
        });

        static::updated(function (AdminTask $task) {
            // Wer die Aufgabe neu uebernimmt, bekommt sie per Mail.
            $task->unsetRelation('responsible')->unsetRelation('responsibleTeam')
                ->unsetRelation('nextAssignee')->unsetRelation('nextAssigneeTeam');

            $task->notifyAssigned(array_merge(
                $task->wasChanged(['responsible_id', 'responsible_team_id']) ? $task->responsibleEmails() : [],
                $task->wasChanged(['next_assignee_id', 'next_assignee_team_id']) ? $task->nextAssigneeEmails() : [],
            ), isNew: false);

            $changes = $task->describeChanges();

            if ($changes !== []) {
                $task->activities()->create([
                    'user_id' => auth('web')->id(),
                    'type' => AdminTaskActivity::TYPE_CHANGED,
                    'changes' => $changes,
                ]);
            }
        });
    }

    /**
     * Mail "Aufgabe fuer dich" an die genannten Adressen – nicht an die Person,
     * die gerade speichert, und nicht fuer erledigte Aufgaben. Ein Fehler beim
     * Versand darf das Speichern der Aufgabe nicht verhindern.
     *
     * @param  array<int, string>  $emails
     */
    protected function notifyAssigned(array $emails, bool $isNew): void
    {
        if ($this->isDone()) {
            return;
        }

        $actor = auth('web')->user();
        $emails = array_diff(array_unique(array_filter($emails)), [(string) $actor?->email]);

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new AdminTaskAssignedMail($this, $isNew, trim((string) $actor?->name) ?: null));
            } catch (\Throwable $e) {
                Log::error('Mail zur Aufgabe konnte nicht versendet werden', [
                    'task_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Die gerade gespeicherten Aenderungen in lesbarer Form – Namen statt IDs,
     * formatierte Daten. So bleibt der Verlauf auch dann verstaendlich, wenn
     * eine Rubrik spaeter umbenannt oder ein Benutzer geloescht wird.
     *
     * @return array<int, array{field: string, label: string, old: ?string, new: ?string}>
     */
    protected function describeChanges(): array
    {
        $tracked = [
            'title' => 'Titel',
            'description' => 'Beschreibung',
            'category_id' => 'Rubrik',
            'status' => 'Status',
            'priority' => 'Priorität',
            'due_date' => 'Fällig am',
            'responsible_id' => 'Verantwortlich',
            'next_assignee_id' => 'Nächster Bearbeiter',
            'subject_id' => 'Bezug',
        ];

        // Verantwortlich und naechster Bearbeiter: Person oder Team – ein Eintrag je Rolle.
        $teamFields = ['responsible_id' => 'responsible_team_id', 'next_assignee_id' => 'next_assignee_team_id'];

        $changes = [];

        foreach ($tracked as $field => $label) {
            $teamField = $teamFields[$field] ?? null;

            if (! $this->wasChanged($field) && ! ($teamField && $this->wasChanged($teamField))) {
                continue;
            }

            if ($teamField) {
                $old = $this->assigneeName($this->getOriginal($field), $this->getOriginal($teamField));
                $new = $this->assigneeName($this->getAttribute($field), $this->getAttribute($teamField));
            } else {
                $old = $this->displayValue($field, $this->getOriginal($field));
                $new = $this->displayValue($field, $this->getAttribute($field));
            }

            if ($old === $new) {
                continue;
            }

            $changes[] = ['field' => $field, 'label' => $label, 'old' => $old, 'new' => $new];
        }

        return $changes;
    }

    /**
     * Name einer Person bzw. eines Teams fuer den Verlauf.
     */
    protected function assigneeName(mixed $userId, mixed $teamId): ?string
    {
        return match (true) {
            (bool) $teamId => AdminTeam::withTrashed()->find($teamId)?->label(),
            (bool) $userId => User::find($userId)?->name,
            default => null,
        };
    }

    protected function displayValue(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field) {
            'category_id' => AdminTaskCategory::find($value)?->name,
            'status' => self::statusOptions()[$value] ?? (string) $value,
            'priority' => self::priorityOptions()[$value] ?? (string) $value,
            'due_date' => Carbon::parse($value)->format('d.m.Y'),
            'subject_id' => $this->subjectLabel(),
            default => (string) $value,
        };
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AdminTaskCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function nextAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'next_assignee_id');
    }

    /**
     * Die wiederkehrende Aufgabe, aus der diese Aufgabe angelegt wurde.
     */
    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(AdminTaskRecurrence::class, 'recurrence_id')->withTrashed();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Erinnerungen in zeitlicher Reihenfolge.
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(AdminTaskReminder::class, 'task_id')->orderBy('remind_at')->orderBy('id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AdminTaskActivity::class, 'task_id');
    }

    public function responsibleTeam(): BelongsTo
    {
        return $this->belongsTo(AdminTeam::class, 'responsible_team_id')->withTrashed();
    }

    public function nextAssigneeTeam(): BelongsTo
    {
        return $this->belongsTo(AdminTeam::class, 'next_assignee_team_id')->withTrashed();
    }

    /**
     * Die Person, bei der die Aufgabe gerade liegt – null, wenn sie bei einem
     * Team liegt (siehe handlerLabel() und handlerEmails()).
     */
    public function currentHandler(): ?User
    {
        if ($this->next_assignee_id || $this->next_assignee_team_id) {
            return $this->nextAssignee;
        }

        return $this->responsible;
    }

    /**
     * Liegt die Aufgabe bei einem Team, dann dieses.
     */
    public function currentHandlerTeam(): ?AdminTeam
    {
        if ($this->next_assignee_id || $this->next_assignee_team_id) {
            return $this->nextAssigneeTeam;
        }

        return $this->responsibleTeam;
    }

    /**
     * Bei wem die Aufgabe liegt – Name der Person oder des Teams.
     */
    public function handlerLabel(): ?string
    {
        return $this->currentHandlerTeam()?->label() ?? (trim((string) $this->currentHandler()?->name) ?: null);
    }

    public function responsibleLabel(): ?string
    {
        return $this->responsibleTeam?->label() ?? (trim((string) $this->responsible?->name) ?: null);
    }

    public function nextAssigneeLabel(): ?string
    {
        return $this->nextAssigneeTeam?->label() ?? (trim((string) $this->nextAssignee?->name) ?: null);
    }

    /**
     * Adressen fuer Mails an die Verantwortlichen: die Person oder – je nach
     * Einstellung des Teams – dessen zentrale Adresse bzw. jedes Mitglied.
     *
     * @return array<int, string>
     */
    public function responsibleEmails(): array
    {
        return $this->responsibleTeam?->notificationEmails()
            ?? array_values(array_filter([$this->responsible?->email]));
    }

    /**
     * @return array<int, string>
     */
    public function nextAssigneeEmails(): array
    {
        return $this->nextAssigneeTeam?->notificationEmails()
            ?? array_values(array_filter([$this->nextAssignee?->email]));
    }

    /**
     * Adressen der Person bzw. des Teams, bei dem die Aufgabe gerade liegt.
     *
     * @return array<int, string>
     */
    public function handlerEmails(): array
    {
        return $this->next_assignee_id || $this->next_assignee_team_id
            ? $this->nextAssigneeEmails()
            : $this->responsibleEmails();
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isOverdue(): bool
    {
        return ! $this->isDone() && $this->due_date !== null && $this->due_date->lt(today());
    }

    /**
     * Die naechste noch nicht verschickte Erinnerung.
     */
    public function nextReminder(): ?AdminTaskReminder
    {
        return $this->reminders->first(fn (AdminTaskReminder $reminder) => ! $reminder->isSent());
    }

    /**
     * Eine Erinnerung ist faellig und die Aufgabe noch nicht erledigt.
     */
    public function isReminderDue(): bool
    {
        return ! $this->isDone() && $this->reminders->contains(fn (AdminTaskReminder $reminder) => $reminder->isDue());
    }

    /**
     * Die Erinnerungen auf den uebergebenen Stand bringen: vorhandene
     * aendern, neue anlegen, fehlende loeschen. Eine verschobene Erinnerung
     * geht erneut raus. Aenderungen landen im Verlauf der Aufgabe.
     *
     * @param  array<int, array{id?: int|null, remind_at: string, user_id?: int|null, note?: string|null}>  $rows
     */
    public function syncReminders(array $rows, bool $log = true): void
    {
        $existing = $this->reminders()->get()->keyBy('id');
        $kept = [];
        $changes = [];

        foreach ($rows as $row) {
            $attributes = [
                'remind_at' => Carbon::parse($row['remind_at']),
                'user_id' => ! empty($row['user_id']) ? (int) $row['user_id'] : null,
                'note' => filled($row['note'] ?? null) ? trim((string) $row['note']) : null,
            ];

            // Nur Erinnerungen dieser Aufgabe lassen sich aendern.
            $reminder = ! empty($row['id']) ? $existing->get((int) $row['id']) : null;

            if (! $reminder) {
                $reminder = $this->reminders()->create($attributes + ['created_by' => auth('web')->id()]);
                $changes[] = ['field' => 'reminder', 'label' => 'Erinnerung', 'old' => null, 'new' => $reminder->label()];

                continue;
            }

            $kept[] = $reminder->id;
            $before = $reminder->label();
            $reminder->fill($attributes);

            if ($reminder->isDirty('remind_at')) {
                $reminder->sent_at = null;
            }

            if ($reminder->isDirty()) {
                $reminder->save();
                $changes[] = ['field' => 'reminder', 'label' => 'Erinnerung', 'old' => $before, 'new' => $reminder->label()];
            }
        }

        foreach ($existing->except($kept) as $removed) {
            $changes[] = ['field' => 'reminder', 'label' => 'Erinnerung', 'old' => $removed->label(), 'new' => null];
            $removed->delete();
        }

        if ($log && $changes !== []) {
            $this->activities()->create([
                'user_id' => auth('web')->id(),
                'type' => AdminTaskActivity::TYPE_CHANGED,
                'changes' => $changes,
            ]);
        }

        $this->unsetRelation('reminders');
    }

    /**
     * Kurze Bezeichnung des Datensatzes, an dem die Aufgabe haengt.
     */
    public function subjectLabel(): ?string
    {
        $subject = $this->subject;

        return match (true) {
            $subject instanceof CustomEvent => 'Ereignis: '.($subject->getTitle('de') ?: 'Ohne Titel'),
            default => null,
        };
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_DONE);
    }

    /**
     * Aufgaben, die gerade bei diesem Benutzer liegen – unmittelbar oder ueber
     * eines seiner Teams.
     */
    public function scopeHandledBy(Builder $query, int|array $userIds): Builder
    {
        $userIds = (array) $userIds;
        $teamIds = AdminTeam::query()->whereHas('users', fn ($users) => $users->whereIn('users.id', $userIds))->pluck('id')->all();

        return $query->where(function (Builder $q) use ($userIds, $teamIds) {
            $q->whereIn('next_assignee_id', $userIds)
                ->orWhereIn('next_assignee_team_id', $teamIds)
                // Ohne naechsten Bearbeiter liegt die Aufgabe beim Verantwortlichen.
                ->orWhere(fn (Builder $sub) => $sub
                    ->whereNull('next_assignee_id')
                    ->whereNull('next_assignee_team_id')
                    ->where(fn (Builder $responsible) => $responsible
                        ->whereIn('responsible_id', $userIds)
                        ->orWhereIn('responsible_team_id', $teamIds)));
        });
    }

    /**
     * Aufgaben, die dieser Benutzer verantwortet – selbst oder ueber eines seiner Teams.
     */
    public function scopeResponsibleFor(Builder $query, int|array $userIds): Builder
    {
        $userIds = (array) $userIds;
        $teamIds = AdminTeam::query()->whereHas('users', fn ($users) => $users->whereIn('users.id', $userIds))->pluck('id')->all();

        return $query->where(fn (Builder $q) => $q
            ->whereIn('responsible_id', $userIds)
            ->orWhereIn('responsible_team_id', $teamIds));
    }

    /**
     * Aufgaben eines Ereignisses – ueber alle seine Versionen hinweg, damit
     * sie beim Anlegen einer neuen Version nicht verloren gehen.
     */
    public function scopeForEvent(Builder $query, CustomEvent $event): Builder
    {
        $ids = $event->version_group_uuid
            ? CustomEvent::withTrashed()->where('version_group_uuid', $event->version_group_uuid)->pluck('id')
            : collect([$event->getKey()]);

        return $query
            ->where('subject_type', $event->getMorphClass())
            ->whereIn('subject_id', $ids);
    }
}
