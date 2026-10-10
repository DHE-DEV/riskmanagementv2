<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Regionsinfos (regions.info): Beschreibung, Reiseinfos und Fakten, die nur
 * fuer diese Region gelten – siehe App\Support\AdminV2\RegionInfo. Was fuer
 * das ganze Land gilt, steht weiter nur am Land.
 *
 * Die bisherige einsprachige Beschreibung (regions.description) wird zur
 * deutschen Kurzbeschreibung; die Spalte bleibt fuer aeltere Leser erhalten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->json('info')->nullable()->after('keywords');
        });

        DB::table('regions')->whereNotNull('description')->where('description', '!=', '')->orderBy('id')
            ->each(function ($region) {
                DB::table('regions')->where('id', $region->id)->update([
                    'info' => json_encode(['texts' => ['short_description' => ['de' => trim((string) $region->description)]]], JSON_UNESCAPED_UNICODE),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn('info');
        });
    }
};
