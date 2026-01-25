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
        Schema::create('folder_car_rental_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('itinerary_id')->constrained('folder_itineraries')->cascadeOnDelete();
            $table->foreignUuid('folder_id')->constrained('folder_folders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('rental_company', 128)->nullable();
            $table->string('booking_reference', 64)->nullable();
            $table->string('pickup_location', 255);
            $table->string('pickup_country_code', 2)->nullable()->index();
            $table->decimal('pickup_lat', 10, 8)->nullable();
            $table->decimal('pickup_lng', 11, 8)->nullable();
            $table->timestamp('pickup_datetime')->index();
            $table->string('return_location', 255);
            $table->string('return_country_code', 2)->nullable()->index();
            $table->decimal('return_lat', 10, 8)->nullable();
            $table->decimal('return_lng', 11, 8)->nullable();
            $table->timestamp('return_datetime')->index();
            $table->string('vehicle_category', 64)->nullable();
            $table->string('vehicle_type', 128)->nullable();
            $table->string('vehicle_make_model', 255)->nullable();
            $table->enum('transmission', ['manual', 'automatic'])->nullable();
            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid'])->nullable();
            $table->unsignedInteger('rental_days')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->json('insurance_options')->nullable();
            $table->json('extras')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'picked_up', 'returned', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'itinerary_id']);
            $table->index(['pickup_datetime', 'return_datetime']);
            $table->index(['pickup_lat', 'pickup_lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_car_rental_services');
    }
};
