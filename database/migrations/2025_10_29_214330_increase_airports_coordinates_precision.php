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
        Schema::table('airports', function (Blueprint $table) {
            // Erhöhe die Präzision für maximale Genauigkeit bei GPS-Koordinaten
            // decimal(20,16) erlaubt bis zu 16 Dezimalstellen nach dem Komma
            $table->decimal('lat', 20, 16)->nullable()->change();
            $table->decimal('lng', 20, 16)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            // Zurück auf die ursprüngliche Präzision
            $table->decimal('lat', 10, 8)->nullable()->change();
            $table->decimal('lng', 11, 8)->nullable()->change();
        });
    }
};
