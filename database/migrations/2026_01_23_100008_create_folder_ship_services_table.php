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
        Schema::create('folder_ship_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('itinerary_id')->constrained('folder_itineraries')->cascadeOnDelete();
            $table->foreignUuid('folder_id')->constrained('folder_folders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('ship_name', 255);
            $table->string('cruise_line', 128)->nullable();
            $table->string('ship_code', 64)->nullable();
            $table->date('embarkation_date')->index();
            $table->date('disembarkation_date')->index();
            $table->unsignedInteger('nights')->nullable();
            $table->string('embarkation_port', 128)->nullable();
            $table->string('embarkation_country_code', 2)->nullable()->index();
            $table->decimal('embarkation_lat', 10, 8)->nullable();
            $table->decimal('embarkation_lng', 11, 8)->nullable();
            $table->string('disembarkation_port', 128)->nullable();
            $table->string('disembarkation_country_code', 2)->nullable();
            $table->decimal('disembarkation_lat', 10, 8)->nullable();
            $table->decimal('disembarkation_lng', 11, 8)->nullable();
            $table->string('cabin_number', 32)->nullable();
            $table->string('cabin_type', 128)->nullable();
            $table->string('cabin_category', 64)->nullable();
            $table->string('deck', 32)->nullable();
            $table->string('booking_reference', 64)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->json('port_calls')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'itinerary_id']);
            $table->index(['embarkation_date', 'disembarkation_date']);
            $table->index(['embarkation_lat', 'embarkation_lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_ship_services');
    }
};
