<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Landesweit": Das Ereignis gilt im gesamten Land, nicht nur am hinterlegten Standort.
     * Bei Radius-/Koordinaten-Abfragen greift es damit unabhaengig von der Entfernung,
     * sobald der Abfragepunkt in einem der Laender des Ereignisses liegt.
     */
    public function up(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->boolean('is_nationwide')->default(false)->after('longitude');
            $table->index('is_nationwide');
        });
    }

    public function down(): void
    {
        Schema::table('custom_events', function (Blueprint $table) {
            $table->dropIndex(['is_nationwide']);
            $table->dropColumn('is_nationwide');
        });
    }
};
