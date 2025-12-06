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
        Schema::create('airport_codes_2', function (Blueprint $table) {
            $table->id();
            $table->string('ident', 10)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('name', 255)->nullable();
            $table->integer('elevation_ft')->nullable();
            $table->string('continent', 5)->nullable();
            $table->string('iso_country', 5)->nullable();
            $table->string('iso_region', 10)->nullable();
            $table->string('municipality', 100)->nullable();
            $table->string('icao_code', 10)->nullable();
            $table->string('iata_code', 10)->nullable();
            $table->string('gps_code', 10)->nullable();
            $table->string('local_code', 20)->nullable();
            $table->string('coordinates', 100)->nullable();
            $table->decimal('latitude', 15, 8)->nullable();
            $table->decimal('longitude', 15, 8)->nullable();
            $table->timestamps();

            // Indexes
            $table->index('ident');
            $table->index('iata_code');
            $table->index('icao_code');
            $table->index('iso_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airport_codes_2');
    }
};
