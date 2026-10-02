<?php

namespace App\Services;

use App\Models\AdminTask;
use App\Models\AdminTaskRecurrence;
use App\Models\AdminTeam;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Legt aus wiederkehrenden Aufgaben zum jeweiligen Termin gewoehnliche
 * Aufgaben an.
 */
class AdminTaskRecurrenceService
{
    /**
     * Alle faelligen Termine abarbeiten.
     *
     * @return array{created: int, skipped: int, failed: int}
     */
    public function runDue(?CarbonInterface $now = null): array
    {
        $now ??= now();
        $result = ['created' => 0, 'skipped' => 0, 'failed' => 0];

        $due = AdminTaskRecurrence::query()
            ->where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->orderBy('next_run_at')
            ->get();

        foreach ($due as $recurrence) {
            try {
                $result[$this->run($recurrence, $now)]++;
            } catch (\Throwable $e) {
                // Eine fehlerhafte Serie darf die uebrigen nicht aufhalten.
                Log::error('Wiederkehrende Aufgabe konnte nicht angelegt werden', [
                    'recurrence_id' => $recurrence->id,
                    'error' => $e->getMessage(),
                ]);

                $recurrence->forceFill(['last_error' => mb_substr($e->getMessage(), 0, 250)])->save();
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * Einen faelligen Termin verarbeiten.
     *
     * War der Zeitplaner laenger ausgefallen, wird nur EINE Aufgabe
     * nachgeholt – nicht fuer jeden verpassten Termin eine.
     *
     * @return string created | skipped | failed
     */
    protected function run(AdminTaskRecurrence $recurrence, CarbonInterface $now): string
    {
        return DB::transaction(function () use ($recurrence, $now) {
            // Gegen doppelte Laeufe: den Datensatz sperren und erneut pruefen.
            $recurrence = AdminTaskRecurrence::query()->lockForUpdate()->find($recurrence->id);

            if (! $recurrence || ! $recurrence->is_active || ! $recurrence->next_run_at || $recurrence->next_run_at->gt($now)) {
                return 'skipped';
            }

            $scheduledFor = $recurrence->next_run_at;
            $outcome = 'created';

            if ($recurrence->skip_if_open && $recurrence->tasks()->open()->exists()) {
                // Die vorige Aufgabe ist noch offen – dieser Termin entfaellt.
                $recurrence->last_skipped_at = $now;
                $outcome = 'skipped';
            } elseif (! $this->createTask($recurrence, $scheduledFor)) {
                $outcome = 'failed';
            }

            // Naechster Termin ab jetzt – verpasste Termine werden nicht einzeln nachgeholt.
            $recurrence->scheduleNext($now)->save();

            return $outcome;
        });
    }

    /**
     * Sofort eine Aufgabe aus der Vorlage anlegen – ausser der Reihe; der
     * Rhythmus bleibt unveraendert.
     */
    public function createNow(AdminTaskRecurrence $recurrence): ?AdminTask
    {
        $task = $this->createTask($recurrence, now());
        $recurrence->scheduleNext()->save();

        return $task;
    }

    /**
     * Die Aufgabe zum Termin anlegen. Fehlt die verantwortliche Person
     * (geloescht oder deaktiviert), uebernimmt der Erfasser der Serie; gibt es
     * auch ihn nicht mehr, wird die Serie angehalten.
     */
    protected function createTask(AdminTaskRecurrence $recurrence, CarbonInterface $scheduledFor): ?AdminTask
    {
        // Verantwortlich ist eine Person oder ein Team.
        $responsibleTeamId = $this->existingTeamId($recurrence->responsible_team_id);
        $responsibleId = $responsibleTeamId ? null : $this->activeUserId($recurrence->responsible_id);
        $usedFallback = false;

        if (! $responsibleTeamId && ! $responsibleId) {
            $responsibleId = $this->activeUserId($recurrence->created_by);
            $usedFallback = true;
        }

        if (! $responsibleTeamId && ! $responsibleId) {
            $recurrence->is_active = false;
            $recurrence->last_error = 'Angehalten: Die verantwortliche Person gibt es nicht mehr oder sie ist deaktiviert.';

            return null;
        }

        $dueDate = $recurrence->due_in_days !== null
            ? $scheduledFor->copy()->startOfDay()->addDays($recurrence->due_in_days)
            : null;

        $remindAt = null;

        if ($dueDate && $recurrence->remind_days_before !== null) {
            [$hour, $minute] = array_map('intval', explode(':', (string) $recurrence->remind_time) + [0, 0]);
            $remindAt = $dueDate->copy()->subDays($recurrence->remind_days_before)->setTime($hour, $minute);

            // Eine Erinnerung vor dem Anlegen der Aufgabe ergibt keinen Sinn.
            if ($remindAt->lt($scheduledFor)) {
                $remindAt = null;
            }
        }

        $nextTeamId = $this->existingTeamId($recurrence->next_assignee_team_id);

        $task = AdminTask::create([
            'title' => $recurrence->titleFor($scheduledFor),
            'description' => $recurrence->description,
            'category_id' => $recurrence->category_id,
            'priority' => $recurrence->priority ?: AdminTask::PRIORITY_NORMAL,
            'due_date' => $dueDate,
            'created_by' => $recurrence->created_by,
            'responsible_id' => $responsibleId,
            'responsible_team_id' => $responsibleTeamId,
            'next_assignee_id' => $nextTeamId ? null : $this->activeUserId($recurrence->next_assignee_id),
            'next_assignee_team_id' => $nextTeamId,
            'recurrence_id' => $recurrence->id,
        ]);

        if ($remindAt) {
            // Ohne Person: geht an die, bei der die Aufgabe dann liegt.
            $task->reminders()->create(['remind_at' => $remindAt]);
        }

        $recurrence->occurrences_count++;
        $recurrence->last_run_at = now();
        $recurrence->last_error = $usedFallback
            ? 'Die verantwortliche Person fehlt – die Aufgabe ging an den Erfasser der Serie.'
            : null;

        return $task;
    }

    protected function existingTeamId(?int $teamId): ?int
    {
        return $teamId ? AdminTeam::query()->whereKey($teamId)->value('id') : null;
    }

    protected function activeUserId(?int $userId): ?int
    {
        if (! $userId) {
            return null;
        }

        return User::query()->whereKey($userId)->where('is_admin', true)->where('is_active', true)->value('id');
    }
}
