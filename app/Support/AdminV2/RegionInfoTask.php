<?php

namespace App\Support\AdminV2;

use App\Jobs\FillMissingRegionInfoJob;
use App\Models\AdminTask;
use App\Models\AdminTaskActivity;
use App\Models\AdminTaskCategory;
use App\Models\Country;
use App\Models\RegionInfoRun;

/**
 * Aufgabe "Regionsinfos per KI ergänzen – <Land>": eine je Land, angelegt per
 * Migration. regions:fill-missing fuer genau dieses Land setzt sie beim Start
 * auf "In Arbeit" und nach Lauf und Mail auf "Erledigt" – jeweils mit dem
 * Ergebnis als Notiz im Verlauf.
 */
class RegionInfoTask
{
    public const TITLE = 'Regionsinfos per KI ergänzen';

    public static function title(Country $country): string
    {
        return self::TITLE.' – '.$country->getName('de');
    }

    /**
     * Anlegen, wenn es fuer das Land noch keine gibt (auch keine erledigte).
     */
    public static function createFor(Country $country): ?AdminTask
    {
        if (self::query($country)->withTrashed()->exists()) {
            return null;
        }

        $code = strtoupper((string) $country->iso_code);

        return AdminTask::withoutAssignmentMails(fn () => AdminTask::create([
            'title' => self::title($country),
            'description' => "Fehlende Regionsinfos aller Regionen und Teilgebiete von {$country->getName('de')} per KI ergänzen. Starten auf dem Server:\n\n"
                ."php artisan regions:fill-missing --country={$code} --dry-run\n"
                ."php artisan regions:fill-missing --country={$code} --notify=…\n\n"
                .'Die Aufgabe wird nach dem Lauf und der Mail automatisch erledigt. Ergänztes steht danach als „KI-Entwurf, ungeprüft“ an den Regionen und Teilgebieten.',
            'category_id' => AdminTaskCategory::query()->firstOrCreate(['name' => 'Stammdaten'], ['sort_order' => 3, 'is_active' => true])->id,
            'subject_type' => TaskSubjects::morphClass('country'),
            'subject_id' => $country->id,
        ]));
    }

    /**
     * Die noch offene Aufgabe des Landes.
     */
    public static function openFor(Country $country): ?AdminTask
    {
        return self::query($country)->where('status', '!=', AdminTask::STATUS_DONE)->oldest('id')->first();
    }

    public static function started(AdminTask $task, RegionInfoRun $run): void
    {
        $result = (array) $run->result;

        $discover = (int) ($result['discover'] ?? 0);

        self::note($task, AdminTask::STATUS_IN_PROGRESS, "Lauf #{$run->id} gestartet: ".(int) ($result['regions'] ?? 0).' Regionen und '.(int) ($result['subregions'] ?? 0).' Teilgebiete mit Lücken'
            .($discover ? ", Suche nach neuen Teilgebieten in {$discover} Regionen" : '').(($result['web'] ?? false) ? ', mit Websuche.' : '.'));
    }

    /**
     * Lauf beendet (fertig, angehalten oder abgebrochen): Ergebnis als Notiz,
     * erledigt nur, wenn er fertig durchgelaufen ist – sonst wieder offen.
     */
    public static function finished(?RegionInfoRun $run): void
    {
        $task = AdminTask::query()->find((int) ($run?->result['task_id'] ?? 0));

        if (! $task) {
            return;
        }

        $result = (array) $run->result;
        $changed = (int) ($result['changed'] ?? 0);
        $failed = count((array) $run->failed);
        $created = (int) ($result['created'] ?? 0);
        $summary = ($created ? "{$created} Teilgebiete neu angelegt, " : '')."{$changed} ergänzt, ".FillMissingRegionInfoJob::unchanged($run).' ohne neue Angaben, '.$failed.' fehlgeschlagen.';

        [$status, $text] = match ($run->status) {
            RegionInfoRun::STATUS_DONE => [AdminTask::STATUS_DONE, "Lauf #{$run->id} fertig: {$summary}".(($result['notify'] ?? []) ? ' Mail an '.implode(', ', $result['notify']).' verschickt.' : '')],
            RegionInfoRun::STATUS_CANCELLED => [AdminTask::STATUS_OPEN, "Lauf #{$run->id} angehalten: {$summary}"],
            default => [AdminTask::STATUS_OPEN, "Lauf #{$run->id} abgebrochen".($run->error ? " ({$run->error})" : '').": {$summary}"],
        };

        self::note($task, $status, $text);
    }

    /**
     * Nichts zu tun: gleich erledigt.
     */
    public static function nothingToDo(AdminTask $task): void
    {
        self::note($task, AdminTask::STATUS_DONE, 'Keine Lücken – nichts zu ergänzen.');
    }

    protected static function note(AdminTask $task, string $status, string $text): void
    {
        $task->activities()->create(['type' => AdminTaskActivity::TYPE_NOTE, 'body' => $text, 'user_id' => auth('web')->id()]);

        if ($task->status !== $status) {
            AdminTask::withoutAssignmentMails(fn () => $task->update(['status' => $status]));
        }
    }

    protected static function query(Country $country)
    {
        return AdminTask::query()
            ->where('subject_type', TaskSubjects::morphClass('country'))
            ->where('subject_id', $country->id)
            ->where('title', 'like', self::TITLE.'%');
    }
}
