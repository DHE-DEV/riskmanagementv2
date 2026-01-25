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
        Schema::create('folder_flight_segments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('flight_service_id')->constrained('folder_flight_services')->cascadeOnDelete();
            $table->foreignUuid('folder_id')->constrained('folder_folders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unsignedInteger('segment_number')->default(1);
            $table->string('departure_airport_code', 3)->index();
            $table->decimal('departure_lat', 10, 8)->nullable();
            $table->decimal('departure_lng', 11, 8)->nullable();
            $table->string('departure_country_code', 2)->nullable()->index();
            $table->timestamp('departure_time')->index();
            $table->string('departure_terminal', 16)->nullable();
            $table->string('arrival_airport_code', 3)->index();
            $table->decimal('arrival_lat', 10, 8)->nullable();
            $table->decimal('arrival_lng', 11, 8)->nullable();
            $table->string('arrival_country_code', 2)->nullable()->index();
            $table->timestamp('arrival_time')->index();
            $table->string('arrival_terminal', 16)->nullable();
            $table->string('airline_code', 3)->nullable();
            $table->string('flight_number', 10)->nullable();
            $table->string('aircraft_type', 32)->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('booking_class', 2)->nullable();
            $table->enum('cabin_class', ['economy', 'premium_economy', 'business', 'first'])->default('economy');
            $table->timestamps();

            $table->index(['customer_id', 'flight_service_id'], 'idx_segment_customer_flight');
            $table->index(['departure_airport_code', 'arrival_airport_code'], 'idx_segment_airports');
            $table->index(['departure_lat', 'departure_lng'], 'idx_segment_dep_coords');
            $table->index(['arrival_lat', 'arrival_lng'], 'idx_segment_arr_coords');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_flight_segments');
    }
};
