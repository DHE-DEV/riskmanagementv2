<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reiseart einer geteilten Reise (Pauschalreise, Rundreise, Kreuzfahrt …): pflegt der Ersteller, die anderen sehen sie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_shared_trips', function (Blueprint $table) {
            $table->string('travel_type', 20)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('app_shared_trips', function (Blueprint $table) {
            $table->dropColumn('travel_type');
        });
    }
};
