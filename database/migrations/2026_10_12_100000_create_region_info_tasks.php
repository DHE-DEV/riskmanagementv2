<?php

use App\Models\AdminTask;
use App\Models\Country;
use App\Support\AdminV2\RegionInfoTask;
use App\Support\AdminV2\TaskSubjects;
use Illuminate\Database\Migrations\Migration;

/**
 * Je Land mit Regionen eine Aufgabe "Regionsinfos per KI ergänzen – <Land>"
 * (Rubrik Stammdaten, Bezug: das Land) – ausser Spanien und Italien, deren
 * Infos per Migration kommen (RegionInfoTask::EXCLUDED). Gestartet wird Land fuer Land mit
 * regions:fill-missing --country=XX; der Lauf erledigt die Aufgabe am Ende.
 * Laender, die schon eine solche Aufgabe haben, bleiben aussen vor.
 */
return new class extends Migration
{
    public function up(): void
    {
        RegionInfoTask::countries()->each(fn (Country $country) => RegionInfoTask::createFor($country));
    }

    public function down(): void
    {
        AdminTask::query()
            ->where('subject_type', TaskSubjects::morphClass('country'))
            ->where('title', 'like', RegionInfoTask::TITLE.' – %')
            ->where('status', AdminTask::STATUS_OPEN)
            ->whereDoesntHave('activities', fn ($query) => $query->where('type', '!=', 'created'))
            ->get()
            ->each->forceDelete();
    }
};
