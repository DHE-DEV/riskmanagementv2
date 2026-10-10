<?php

use App\Support\AdminV2\SightTransfer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Sehenswuerdigkeiten fuer Spanien und Italien anlegen.
 *
 * Die Eintraege wurden redaktionell mit KI-Unterstuetzung erzeugt (Stand
 * Oktober 2026, Koordinaten mit OpenStreetMap abgeglichen) und liegen in
 * database/data/sights-es-it.json. Sie sind als "KI-Entwurf, ungeprueft"
 * gekennzeichnet und koennen im Admin geprueft und ueberarbeitet werden.
 *
 * Zuordnung ueber Land (ISO-Code), Region und Stadt (deutscher Name). Was
 * es im Land schon gibt (gleicher Name), wird nicht doppelt angelegt.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $path = database_path('data/sights-es-it.json');

        if (! is_file($path)) {
            return;
        }

        $result = SightTransfer::import(json_decode((string) file_get_contents($path), true) ?: []);

        Log::info('Sehenswürdigkeiten Spanien/Italien importiert', $result);
    }

    public function down(): void
    {
        // Angelegte Eintraege bleiben – sie koennen inzwischen geprueft oder ueberarbeitet sein.
    }
};
