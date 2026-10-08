<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alle Waehrungen nach ISO 4217 – fuer Auswahlfelder (Trinkgeld, Land).
 * Gefuellt vom CurrencySeeder aus den ICU-Daten (symfony/intl).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 3)->primary();
            $table->unsignedSmallInteger('numeric_code')->nullable();
            $table->json('name_translations');
            $table->string('symbol', 10)->nullable();
            // Nachkommastellen, z. B. 2 bei EUR, 0 bei JPY
            $table->unsignedTinyInteger('minor_unit')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
