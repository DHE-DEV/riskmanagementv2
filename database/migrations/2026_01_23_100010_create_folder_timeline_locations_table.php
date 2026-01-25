<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('folder_timeline_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('folder_id')->constrained('folder_folders')->cascadeOnDelete();
            $table->foreignUuid('itinerary_id')->nullable()->constrained('folder_itineraries')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->enum('location_type', [
                'flight_departure',
                'flight_arrival',
                'hotel',
                'cruise_embark',
                'cruise_disembark',
                'cruise_port',
                'car_pickup',
                'car_return',
            ]);
            $table->enum('source_type', [
                'flight_segment',
                'hotel_service',
                'ship_service',
                'car_rental_service',
            ]);
            $table->uuid('source_id');
            $table->decimal('lat', 10, 8)->index();
            $table->decimal('lng', 11, 8)->index();
            $table->string('location_code', 10)->nullable()->index();
            $table->string('location_name', 255)->nullable();
            $table->string('country_code', 2)->nullable()->index();
            $table->timestamp('start_time')->index();
            $table->timestamp('end_time')->index();
            $table->json('participant_ids')->nullable();
            $table->json('participant_nationalities')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Performance-critical indexes
            $table->index(['customer_id', 'folder_id']);
            $table->index(['customer_id', 'start_time', 'end_time']);
            $table->index(['start_time', 'end_time', 'country_code']);
            $table->index(['lat', 'lng', 'start_time', 'end_time']);
        });

        // SPATIAL column for geographic queries
        // Point is nullable - will be NULL if no coordinates are provided
        DB::statement('ALTER TABLE folder_timeline_locations ADD COLUMN point POINT NULL SRID 4326 AFTER lng');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_timeline_locations');
    }
};
