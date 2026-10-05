<?php

namespace App\Services;

use App\Models\AdminTask;
use App\Models\AiCheck;
use App\Models\AiCheckRun;
use App\Models\User;
use App\Support\AdminV2\AiAreas;
use App\Support\AdminV2\AiRecordContexts;
use App\Support\AiSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Sammellauf: fuehrt eine KI-Pruefung, die Aufgaben anlegt, fuer alle
 * Datensaetze ihres Bereichs aus. Der Lauf arbeitet stueckweise – wenige
 * Datensaetze gleichzeitig – und merkt sich, wo er steht. Weitergefuehrt wird
 * er vom Zeitplan (ai:run-check-batches) und von der Seite System > KI,
 * solange sie geoeffnet ist.
 */
class AiCheckBatchService
{
    /** So viele Datensaetze gehen gleichzeitig an die KI. */
    public const PARALLEL = 4;

    /**
     * Hoechstens so viele Schritte je Aufruf. Jedes befuellte Formular belegt bis
     * zu 1 MB, das erst mit dem Ende des Prozesses frei wird – der naechste
     * Aufruf (Zeitplan, geoeffnete Seite) macht dort weiter, wo dieser aufhoert.
     */
    public const MAX_STEPS = 10;

    public function __construct(protected AiCheckTaskService $tasks) {}

    /**
     * Anzahl der Datensaetze, ueber die ein Sammellauf ginge.
     */
    public function recordCount(AiCheck $check): int
    {
        return AiRecordContexts::supports($check->area) ? AiRecordContexts::query($check->area)->count() : 0;
    }

    public function start(AiCheck $check, ?int $userId = null): AiCheckRun
    {
        if (! AiRecordContexts::supports($check->area)) {
            throw new RuntimeException('Für diesen Bereich gibt es noch keinen Sammellauf.');
        }

        if (! $check->createsTasks()) {
            throw new RuntimeException('Die Prüfung legt keine Aufgaben an – bitte zuerst die Bedingung hinterlegen.');
        }

        if ($check->runs()->where('status', AiCheckRun::STATUS_RUNNING)->exists()) {
            throw new RuntimeException('Für diese Prüfung läuft bereits ein Sammellauf.');
        }

        // Die Sammelaufgabe steht, bevor die ersten Unteraufgaben entstehen.
        $this->tasks->parentTask($check, $userId);

        return $check->runs()->create([
            'started_by' => $userId,
            'status' => AiCheckRun::STATUS_RUNNING,
            'total' => $this->recordCount($check),
        ]);
    }

    public function cancel(AiCheckRun $run): void
    {
        if ($run->isRunning()) {
            $run->update(['status' => AiCheckRun::STATUS_CANCELLED, 'finished_at' => now()]);
        }
    }

    /**
     * Den Lauf ein Stueck weiterfuehren – hoechstens $seconds lang. Laeuft er
     * gerade an anderer Stelle weiter, geschieht hier nichts.
     */
    public function advance(AiCheckRun $run, int $seconds = 20): AiCheckRun
    {
        $lock = Cache::lock('ai-check-run-'.$run->id, 300);

        if (! $lock->get()) {
            return $run->refresh();
        }

        try {
            $deadline = microtime(true) + $seconds;
            $steps = 0;

            do {
                $continue = $this->step($run->refresh());
            } while ($continue && ++$steps < self::MAX_STEPS && microtime(true) < $deadline);
        } finally {
            $lock->release();
        }

        return $run->refresh();
    }

    /**
     * Im Zeitplan ist niemand angemeldet: der Lauf arbeitet dann als die Person,
     * die ihn gestartet hat – ersatzweise als irgendein aktiver Admin. Ihr werden
     * auch die angelegten Unteraufgaben im Verlauf zugeschrieben.
     */
    protected function actAsAdmin(AiCheckRun $run): bool
    {
        $isAdmin = fn (?User $user) => $user && $user->is_admin && $user->is_active;

        if ($isAdmin(auth('web')->user())) {
            return true;
        }

        $user = $isAdmin($run->starter) ? $run->starter : User::query()->where('is_admin', true)->where('is_active', true)->orderBy('id')->first();

        if (! $user) {
            return false;
        }

        auth('web')->setUser($user);

        return true;
    }

    /**
     * Die naechsten Datensaetze pruefen. Liefert false, wenn der Lauf zu Ende ist.
     */
    protected function step(AiCheckRun $run): bool
    {
        if (! $run->isRunning()) {
            return false;
        }

        $check = $run->check;

        if (! $check || ! $check->createsTasks() || ! AiRecordContexts::supports($check->area)) {
            $run->update(['status' => AiCheckRun::STATUS_FAILED, 'error' => 'Die Prüfung legt keine Aufgaben mehr an.', 'finished_at' => now()]);

            return false;
        }

        // Die Angaben kommen aus den Formularen des Bereichs – sie verlangen einen angemeldeten Admin.
        if (! $this->actAsAdmin($run)) {
            $run->update(['status' => AiCheckRun::STATUS_FAILED, 'error' => 'Der Lauf braucht einen aktiven Admin – wer ihn gestartet hat, ist es nicht mehr.', 'finished_at' => now()]);

            return false;
        }

        $query = AiRecordContexts::query($check->area);
        $key = $query->getModel()->getQualifiedKeyName();
        $records = $query->where($key, '>', $run->last_record_id)->orderBy($key)->limit(self::PARALLEL)->get();

        if ($records->isEmpty()) {
            $run->update(['status' => AiCheckRun::STATUS_FINISHED, 'finished_at' => now()]);

            return false;
        }

        // Wie im Formular: die Angaben des Abschnitts als Daten, alles Uebrige als Platzhalter.
        $labels = AiAreas::placeholders($check->area, $check->section ?: AiAreas::GENERAL);
        $prompts = [];

        foreach ($records as $record) {
            $all = AiRecordContexts::context($check->area, $record);
            $context = array_intersect_key($all, $labels);
            $prompts[(string) $record->getKey()] = $this->tasks->buildPrompt($check, $context, $labels, array_diff_key($all, $context));
        }

        $counts = ['processed' => $records->count(), 'matched' => 0, 'created' => 0, 'failed' => 0];
        $tokens = 0;
        $cost = null;
        $error = null;

        try {
            $ai = app(ChatGptService::class);
            $answers = $ai->sendPrompts($prompts, ['model' => $check->model ?: AiSettings::model(), 'timeout' => 90]);

            if ($usage = $ai->lastUsage()) {
                $tokens = $usage['total_tokens'];
                $cost = AiSettings::cost($usage['model'], $usage['input_tokens'], $usage['output_tokens']);
            }

            // Im Sammellauf entstehen viele Aufgaben auf einmal – ohne je eine Mail an die Verantwortlichen.
            AdminTask::withoutAssignmentMails(function () use ($records, $answers, $check, $run, &$counts) {
                foreach ($records as $record) {
                    $outcome = $this->tasks->apply($check, $record, $this->tasks->parse($answers[(string) $record->getKey()] ?? ''), $run->started_by);

                    $counts['failed'] += $outcome['parsed'] ? 0 : 1;
                    $counts['matched'] += $outcome['met'] ? 1 : 0;
                    $counts['created'] += $outcome['created'] ? 1 : 0;
                }
            });
        } catch (Throwable $exception) {
            $counts['failed'] = $records->count();
            $error = Str::limit($exception->getMessage(), 480, '…');
        }

        $run->forceFill([
            'processed' => $run->processed + $counts['processed'],
            'matched' => $run->matched + $counts['matched'],
            'created' => $run->created + $counts['created'],
            'failed' => $run->failed + $counts['failed'],
            'last_record_id' => (int) $records->last()->getKey(),
            'total_tokens' => $run->total_tokens + $tokens,
            'cost' => $cost === null ? $run->cost : (float) $run->cost + $cost,
            'error' => $error ?? $run->error,
        ]);

        // Geht von Anfang an gar nichts (kein Schluessel, Modell nicht verfuegbar), hat Weitermachen keinen Sinn.
        if ($error !== null && $run->failed === $run->processed && $run->processed >= self::PARALLEL * 2) {
            $run->forceFill(['status' => AiCheckRun::STATUS_FAILED, 'finished_at' => now()]);
        }

        $run->save();

        return $run->isRunning();
    }
}
