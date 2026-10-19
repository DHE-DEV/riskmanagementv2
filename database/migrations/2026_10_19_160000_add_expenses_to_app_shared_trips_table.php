<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gemeinsame Reisekasse einer geteilten Reise: Ausgaben (wer hat was fuer wen bezahlt), je Eintrag zusammengefuehrt
 * wie die Packliste (id, updated_at, deleted). Alle Teilnehmer duerfen erfassen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_shared_trips', function (Blueprint $table) {
            $table->json('expenses')->nullable()->after('sight_votes');
        });
    }

    public function down(): void
    {
        Schema::table('app_shared_trips', function (Blueprint $table) {
            $table->dropColumn('expenses');
        });
    }
};
