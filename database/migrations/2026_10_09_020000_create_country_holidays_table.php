<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feiertage je Land mit konkretem Datum (bewegliche Feiertage je Jahr),
 * Name und Kommentar je Sprache.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->date('date');
            $table->json('name_translations');
            $table->json('comment_translations')->nullable();
            // landesweit (sonst nur regional)
            $table->boolean('is_national')->default(true);
            // Herkunft, z. B. "holidays-lib" oder "manual"
            $table->string('source', 50)->nullable();
            $table->timestamps();

            $table->index(['country_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_holidays');
    }
};
