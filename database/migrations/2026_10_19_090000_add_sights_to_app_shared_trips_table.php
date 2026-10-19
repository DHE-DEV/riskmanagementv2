<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gemeinsame Sehenswuerdigkeiten einer geteilten Reise: `sights` ist die Liste aller, die sie uebernommen haben
 * (je Eintrag key, by, updated_at, deleted – zusammengefuehrt wie die Packliste; null = nicht geteilt).
 * „Besucht“ bleibt persoenlich; `sight_visits` haelt je Geraet, welche davon es besucht hat (Anzeige „war hier“).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_shared_trips', function (Blueprint $table) {
            $table->json('sights')->nullable()->after('tid');
            $table->json('sight_visits')->nullable()->after('sights');
        });
    }

    public function down(): void
    {
        Schema::table('app_shared_trips', function (Blueprint $table) {
            $table->dropColumn(['sights', 'sight_visits']);
        });
    }
};
