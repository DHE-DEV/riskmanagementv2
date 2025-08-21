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
        if (!Schema::hasTable('airports')) {
            Schema::create('airports', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('iata_code', 3)->unique();
                $table->string('icao_code', 4)->unique();
                $table->enum('type', ['domestic', 'international', 'military'])->default('domestic');
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->foreignId('country_id')->constrained()->onDelete('cascade');
                $table->foreignId('city_id')->constrained()->onDelete('cascade');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airports');
    }
};
