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
        Schema::create('td_air_legs', function (Blueprint $table) {
            $table->id();

            // Foreign key to trip
            $table->foreignId('trip_id')->constrained('td_trips')->cascadeOnDelete();

            // Leg identification
            $table->string('leg_id', 64);
            $table->enum('mode', ['air', 'rail', 'bus', 'ferry', 'car'])->default('air');

            // Computed leg summary
            $table->timestamp('leg_start_at')->nullable();
            $table->timestamp('leg_end_at')->nullable();
            $table->unsignedInteger('total_duration_minutes')->nullable();
            $table->unsignedInteger('segment_count')->default(0);

            // Origin summary
            $table->string('origin_airport_code', 10)->nullable();
            $table->decimal('origin_lat', 10, 8)->nullable();
            $table->decimal('origin_lng', 11, 8)->nullable();
            $table->string('origin_country_code', 2)->nullable();

            // Destination summary
            $table->string('destination_airport_code', 10)->nullable();
            $table->decimal('destination_lat', 10, 8)->nullable();
            $table->decimal('destination_lng', 11, 8)->nullable();
            $table->string('destination_country_code', 2)->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['trip_id', 'leg_id'], 'idx_trip_leg');
            $table->index(['leg_start_at', 'leg_end_at'], 'idx_leg_dates');
            $table->index(['origin_lat', 'origin_lng'], 'idx_origin_coords');
            $table->index(['destination_lat', 'destination_lng'], 'idx_destination_coords');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_air_legs');
    }
};
