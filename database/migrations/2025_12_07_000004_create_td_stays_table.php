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
        Schema::create('td_stays', function (Blueprint $table) {
            $table->id();

            // Foreign key to trip
            $table->foreignId('trip_id')->constrained('td_trips')->cascadeOnDelete();

            // Stay identification
            $table->string('stay_id', 64);
            $table->enum('stay_type', ['hotel', 'apartment', 'resort', 'hostel', 'other'])->default('hotel');

            // Location info
            $table->string('location_name', 255)->nullable();
            $table->unsignedInteger('giata_id')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->string('country_code', 2)->nullable();

            // Address details (for extended info)
            $table->json('address_json')->nullable();

            // Stay timing
            $table->timestamp('check_in');
            $table->timestamp('check_out');
            $table->unsignedInteger('duration_nights')->nullable();

            // Additional details
            $table->json('details_json')->nullable();

            // Raw metadata
            $table->json('raw_meta')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['trip_id', 'stay_id'], 'idx_trip_stay');
            $table->index(['check_in', 'check_out'], 'idx_stay_dates');
            $table->index(['lat', 'lng'], 'idx_stay_coords');
            $table->index('giata_id', 'idx_giata');
            $table->index(['lat', 'lng', 'check_in', 'check_out'], 'idx_location_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_stays');
    }
};
