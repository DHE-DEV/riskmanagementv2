<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('td_flight_segments', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('air_leg_id')->constrained('td_air_legs')->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained('td_trips')->cascadeOnDelete();

            // Segment identification
            $table->string('segment_id', 64);
            $table->unsignedInteger('sequence_in_leg')->default(0);

            // Departure info
            $table->string('departure_airport_code', 10);
            $table->decimal('departure_lat', 10, 8)->nullable();
            $table->decimal('departure_lng', 11, 8)->nullable();
            $table->string('departure_country_code', 2)->nullable();
            $table->timestamp('departure_time');
            $table->string('departure_terminal', 16)->nullable();

            // Arrival info
            $table->string('arrival_airport_code', 10);
            $table->decimal('arrival_lat', 10, 8)->nullable();
            $table->decimal('arrival_lng', 11, 8)->nullable();
            $table->string('arrival_country_code', 2)->nullable();
            $table->timestamp('arrival_time');
            $table->string('arrival_terminal', 16)->nullable();

            // Flight details
            $table->string('marketing_airline_code', 10)->nullable();
            $table->string('flight_number', 16)->nullable();
            $table->string('operating_airline_code', 10)->nullable();

            // Transfer role
            $table->enum('transfer_role_hint', ['in', 'out', 'none'])->default('none');

            // Computed fields
            $table->unsignedInteger('duration_minutes')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['air_leg_id', 'sequence_in_leg'], 'idx_leg_sequence');
            $table->index('trip_id', 'idx_trip_segment');
            $table->index('departure_time', 'idx_departure_time');
            $table->index('arrival_time', 'idx_arrival_time');
            $table->index(['departure_lat', 'departure_lng'], 'idx_departure_coords');
            $table->index(['arrival_lat', 'arrival_lng'], 'idx_arrival_coords');
            $table->index('departure_airport_code', 'idx_departure_airport');
            $table->index('arrival_airport_code', 'idx_arrival_airport');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_flight_segments');
    }
};
