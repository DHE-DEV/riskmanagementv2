<?php

use App\Models\AdminTask;
use App\Models\Country;
use App\Models\User;
use App\Support\AdminV2\RegionInfoTask;
use App\Support\AdminV2\TaskSubjects;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Aufgaben "Regionsinfos per KI ergänzen – <Land>" fuer alle Laender mit
 * Regionen ausser Spanien und Italien: fehlende anlegen und alle noch
 * offenen Daniel Henninger zuweisen – ohne Zuweisungsmails, er startet die
 * Laeufe selbst (Kasten "KI-Lauf" an der Aufgabe).
 */
return new class extends Migration
{
    public function up(): void
    {
        RegionInfoTask::countries()->each(fn (Country $country) => RegionInfoTask::createFor($country));

        $user = User::query()->where('email', RegionInfoTask::RESPONSIBLE_EMAIL)->first();

        if (! $user) {
            Log::warning('Regionsinfo-Aufgaben: '.RegionInfoTask::RESPONSIBLE_EMAIL.' gibt es nicht – Aufgaben bleiben ohne Zuweisung.');

            return;
        }

        AdminTask::withoutAssignmentMails(fn () => AdminTask::query()
            ->where('subject_type', TaskSubjects::morphClass('country'))
            ->where('title', 'like', RegionInfoTask::TITLE.' – %')
            ->where('status', '!=', AdminTask::STATUS_DONE)
            ->whereNotIn('subject_id', Country::query()->whereIn('iso_code', RegionInfoTask::EXCLUDED)->select('id'))
            ->get()
            ->each(fn (AdminTask $task) => $task->update(['responsible_id' => $user->id, 'responsible_team_id' => null])));
    }

    public function down(): void
    {
        // Zuweisungen bleiben – sie koennen inzwischen geaendert sein.
    }
};
