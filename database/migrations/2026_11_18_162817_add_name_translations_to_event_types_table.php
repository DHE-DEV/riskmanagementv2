<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Übersetzte Namen der Event-Typen (Kategorien) je Sprache, z. B. {"en": "Security", "nl": "Veiligheid"}.
 * Die Spalte name bleibt der Name in der Ausgangssprache (Deutsch).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_types', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('event_types', function (Blueprint $table) {
            $table->dropColumn('name_translations');
        });
    }
};
