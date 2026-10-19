<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wichtige Saetze je Sprache (ISO-639-Code wie in countries.languages) fuer die TripData-App: Text in der Sprache,
 * bei nichtlateinischer Schrift eine Umschrift. Schluessel siehe App\Support\Phrases\PhraseCatalog.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_phrases', function (Blueprint $table) {
            $table->id();
            $table->string('language', 8);
            $table->string('key', 40);
            $table->string('text', 255);
            $table->string('romanization', 255)->nullable();
            // ai_draft = KI-Entwurf, ungeprueft; reviewed = geprueft
            $table->string('status', 20)->default('ai_draft');
            $table->timestamps();
            $table->unique(['language', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_phrases');
    }
};
