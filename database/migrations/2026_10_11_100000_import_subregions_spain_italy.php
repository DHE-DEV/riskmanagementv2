<?php

use App\Support\AdminV2\SightTransfer;
use App\Support\AdminV2\SubregionTransfer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Teilgebiete fuer Spanien und Italien anlegen: Inseln, Inselgruppen und
 * Reisegebiete (z. B. Teneriffa, Liparische Inseln, Costa Brava) mit
 * Beschreibung, Reiseinfos und Fakten, dazu die Zuordnung der vorhandenen
 * Staedte und Sehenswuerdigkeiten und weitere Sehenswuerdigkeiten fuer
 * Teilgebiete, die bisher kaum welche hatten.
 *
 * Die Daten wurden redaktionell mit KI-Unterstuetzung erzeugt (Stand Oktober
 * 2026; Mittelpunkte von Inseln aus OpenStreetMap, Einwohner und Flaeche von
 * Inseln aus Wikidata) und liegen in database/data/. Infos und neue
 * Sehenswuerdigkeiten sind als "KI-Entwurf, ungeprueft" gekennzeichnet.
 * Was es schon gibt (gleicher Name), wird nicht doppelt angelegt.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $subregions = database_path('data/subregions-es-it.json');
        $sights = database_path('data/sights-es-it-subregions.json');

        if (is_file($subregions)) {
            Log::info('Teilgebiete Spanien/Italien importiert', SubregionTransfer::import(json_decode((string) file_get_contents($subregions), true) ?: []));
        }

        if (is_file($sights)) {
            Log::info('Sehenswürdigkeiten der Teilgebiete Spanien/Italien importiert', SightTransfer::import(json_decode((string) file_get_contents($sights), true) ?: []));
        }
    }

    public function down(): void
    {
        // Angelegte Eintraege bleiben – sie koennen inzwischen geprueft oder ueberarbeitet sein.
    }
};
