<?php

use App\Support\AdminV2\RegionInfoTransfer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Regionsinfos und Teilgebiete fuer Spanien und Italien vervollstaendigen:
 * fehlende Texte, Sprachen und Fakten der Regionen und Teilgebiete, neu
 * recherchierte Teilgebiete (Inseln, Reisegebiete) samt Infos und die
 * Zuordnung der vorhandenen Staedte und Sehenswuerdigkeiten.
 *
 * Erzeugt lokal mit regions:fill-missing --web (KI mit Websuche, Stand
 * Oktober 2026) und regions:export-info; die Daten liegen in database/data/.
 * Es werden nur Luecken gefuellt – vorhandene Texte und Teilgebiete bleiben.
 * Ergaenztes ist als "KI-Entwurf, ungeprueft" gekennzeichnet, neue
 * Teilgebiete zusaetzlich als "von der KI angelegt".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $file = database_path('data/region-info-es-it.json');

        if (is_file($file)) {
            Log::info('Regionsinfos und Teilgebiete Spanien/Italien vervollständigt', RegionInfoTransfer::import(json_decode((string) file_get_contents($file), true) ?: []));
        }
    }

    public function down(): void
    {
        // Ergaenztes bleibt – es kann inzwischen geprueft oder ueberarbeitet sein.
    }
};
