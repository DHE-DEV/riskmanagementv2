<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Regionale Feiertage: ein Feiertag kann an einer Region des Landes haengen
 * (z. B. Fronleichnam in Bayern); ohne Region gilt er landesweit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('country_holidays', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('country_id')->constrained('regions')->cascadeOnDelete();
            $table->index(['region_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('country_holidays', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropIndex(['region_id', 'date']);
            $table->dropColumn('region_id');
        });
    }
};
