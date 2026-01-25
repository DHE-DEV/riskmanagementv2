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
        Schema::create('folder_flight_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('itinerary_id')->constrained('folder_itineraries')->cascadeOnDelete();
            $table->foreignUuid('folder_id')->constrained('folder_folders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('booking_reference', 64)->nullable();
            $table->enum('service_type', ['outbound', 'return', 'multi_leg'])->default('outbound');
            $table->timestamp('departure_time')->nullable()->index();
            $table->timestamp('arrival_time')->nullable()->index();
            $table->string('origin_airport_code', 3)->nullable()->index();
            $table->string('destination_airport_code', 3)->nullable()->index();
            $table->string('origin_country_code', 2)->nullable()->index();
            $table->string('destination_country_code', 2)->nullable()->index();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->string('airline_pnr', 32)->nullable();
            $table->json('ticket_numbers')->nullable();
            $table->enum('status', ['pending', 'ticketed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['customer_id', 'itinerary_id']);
            $table->index(['origin_airport_code', 'destination_airport_code'], 'idx_flight_origin_dest');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_flight_services');
    }
};
