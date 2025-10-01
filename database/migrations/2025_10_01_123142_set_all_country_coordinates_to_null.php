<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Setze alle Geokoordinaten bei Ländern auf null
        DB::table('countries')->update([
            'lat' => null,
            'lng' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Die ursprünglichen Koordinaten können nicht wiederhergestellt werden
        // Diese Migration ist nicht reversibel
    }
};
