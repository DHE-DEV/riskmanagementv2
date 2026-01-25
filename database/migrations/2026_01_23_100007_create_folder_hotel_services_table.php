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
        Schema::create('folder_hotel_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('itinerary_id')->constrained('folder_itineraries')->cascadeOnDelete();
            $table->foreignUuid('folder_id')->constrained('folder_folders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('hotel_name', 255);
            $table->string('hotel_code', 64)->nullable();
            $table->string('hotel_code_type', 32)->nullable();
            $table->string('street', 255)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 128)->nullable()->index();
            $table->string('country_code', 2)->nullable()->index();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->date('check_in_date')->index();
            $table->date('check_out_date')->index();
            $table->unsignedInteger('nights')->nullable();
            $table->string('room_type', 128)->nullable();
            $table->unsignedInteger('room_count')->default(1);
            $table->string('board_type', 64)->nullable();
            $table->string('booking_reference', 64)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'itinerary_id']);
            $table->index(['check_in_date', 'check_out_date']);
            $table->index(['lat', 'lng']);
        });

        // Add SPATIAL column for hotel coordinates
        // Point is nullable - will be NULL if no coordinates are provided
        DB::statement('ALTER TABLE folder_hotel_services ADD COLUMN point POINT NULL SRID 4326 AFTER lng');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_hotel_services');
    }
};
