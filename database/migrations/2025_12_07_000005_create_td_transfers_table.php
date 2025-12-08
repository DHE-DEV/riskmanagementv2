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
        Schema::create('td_transfers', function (Blueprint $table) {
            $table->id();

            // Foreign key to trip
            $table->foreignId('trip_id')->constrained('td_trips')->cascadeOnDelete();

            // From segment reference
            $table->enum('from_segment_type', ['flight', 'stay']);
            $table->unsignedBigInteger('from_segment_id');

            // To segment reference
            $table->enum('to_segment_type', ['flight', 'stay']);
            $table->unsignedBigInteger('to_segment_id');

            // Transfer location details
            $table->string('transfer_location_code', 10)->nullable();
            $table->decimal('transfer_lat', 10, 8)->nullable();
            $table->decimal('transfer_lng', 11, 8)->nullable();
            $table->string('transfer_country_code', 2)->nullable();

            // Timing
            $table->unsignedInteger('connection_time_minutes')->nullable();
            $table->timestamp('from_arrival_time')->nullable();
            $table->timestamp('to_departure_time')->nullable();

            // Transfer type classification
            $table->enum('transfer_type', ['airport', 'city', 'same_location'])->default('airport');
            $table->boolean('is_tight_connection')->default(false);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('trip_id', 'idx_trip_transfers');
            $table->index(['transfer_lat', 'transfer_lng'], 'idx_transfer_location');
            $table->index(['from_arrival_time', 'to_departure_time'], 'idx_transfer_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_transfers');
    }
};
