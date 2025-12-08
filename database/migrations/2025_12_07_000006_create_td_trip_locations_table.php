<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('td_trip_locations', function (Blueprint $table) {
            $table->id();

            // Foreign key to trip
            $table->foreignId('trip_id')->constrained('td_trips')->cascadeOnDelete();

            // Location type and source
            $table->enum('location_type', ['departure', 'arrival', 'stay', 'transfer']);
            $table->enum('source_type', ['flight_segment', 'stay', 'transfer']);
            $table->unsignedBigInteger('source_id');

            // Coordinates (for regular queries)
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);

            // Location details
            $table->string('location_code', 10)->nullable();
            $table->string('location_name', 255)->nullable();
            $table->string('country_code', 2)->nullable();

            // Time at location
            $table->timestamp('start_time');
            $table->timestamp('end_time');

            // Timestamp
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('trip_id', 'idx_trip_locations');
            $table->index(['start_time', 'end_time'], 'idx_time_range');
            $table->index('country_code', 'idx_country');
            $table->index(['lat', 'lng', 'start_time', 'end_time'], 'idx_coords_time');
        });

        // Add POINT column for SPATIAL INDEX (MySQL specific)
        // This enables efficient geo-proximity queries using ST_Distance_Sphere
        // Note: POINT column must be NOT NULL for SPATIAL INDEX
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE td_trip_locations ADD COLUMN point POINT NOT NULL SRID 4326 AFTER lng');
            DB::statement('CREATE SPATIAL INDEX idx_point ON td_trip_locations (point)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_trip_locations');
    }
};
