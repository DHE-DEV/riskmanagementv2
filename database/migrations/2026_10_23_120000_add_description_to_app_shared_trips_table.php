<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Beschreibung (Notiz) einer geteilten Reise: pflegt der Ersteller, die anderen Teilnehmer bekommen sie mit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_shared_trips', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('app_shared_trips', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
