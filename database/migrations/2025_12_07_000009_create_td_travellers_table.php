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
        Schema::create('td_travellers', function (Blueprint $table) {
            $table->id();

            // Foreign key to trip
            $table->foreignId('trip_id')->constrained('td_trips')->cascadeOnDelete();

            // External identifier from provider
            $table->string('external_traveller_id', 128);

            // Traveller type
            $table->enum('traveller_type', ['adult', 'child', 'infant'])->default('adult');

            // Personal data (may be partial)
            $table->string('first_name', 128)->nullable();
            $table->string('last_name', 128)->nullable();
            $table->string('salutation', 16)->nullable(); // Mr, Mrs, Ms, etc.
            $table->date('date_of_birth')->nullable();

            // Nationality (ISO 3166-1 alpha-2)
            $table->string('nationality', 2)->nullable();

            // Contact (optional)
            $table->string('email', 255)->nullable();
            $table->string('phone', 64)->nullable();

            // Document info (optional)
            $table->string('passport_country', 2)->nullable();

            // Additional metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['trip_id', 'external_traveller_id'], 'uk_trip_traveller');
            $table->index('nationality', 'idx_nationality');
            $table->index('passport_country', 'idx_passport_country');
            $table->index(['trip_id', 'nationality'], 'idx_trip_nationality');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_travellers');
    }
};
