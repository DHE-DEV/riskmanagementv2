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
        Schema::create('folder_itinerary_participant', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('itinerary_id')->constrained('folder_itineraries')->cascadeOnDelete();
            $table->foreignUuid('participant_id')->constrained('folder_participants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['itinerary_id', 'participant_id']);
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_itinerary_participant');
    }
};
