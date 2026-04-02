<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip in testing environment
        if (app()->environment('testing')) {
            return;
        }

        // Setze alle Geokoordinaten bei Ländern auf null
        if (Schema::hasColumn('countries', 'lat')) {
            DB::table('countries')->update([
                'lat' => null,
                'lng' => null,
            ]);
        }
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
