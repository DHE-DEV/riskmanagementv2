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
        Schema::create('booking_locations', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'online' or 'stationary'
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('url')->nullable(); // für Online-Buchungen
            $table->string('address')->nullable(); // für stationäre Reisebüros
            $table->string('postal_code', 5)->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();

            // Indexes für schnelle Suche
            $table->index('type');
            $table->index('postal_code');
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_locations');
    }
};
