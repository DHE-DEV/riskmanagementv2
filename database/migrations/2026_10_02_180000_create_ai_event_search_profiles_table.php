<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hinterlegte KI-Suchen nach Ereignissen: eigener Auftrag, Filter und ein
     * Zeitplan, nach dem der Zeitplaner sie automatisch ausfuehrt.
     */
    public function up(): void
    {
        Schema::create('ai_event_search_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Eigener Auftrag an die KI; null = der Standard-Auftrag aus System > KI.
            $table->text('prompt')->nullable();
            $table->boolean('exclude_existing')->default(true);

            // Filter wie in der Ereignisliste.
            $table->json('country_codes')->nullable();
            $table->json('event_type_codes')->nullable();
            $table->json('priorities')->nullable();
            $table->string('keyword')->nullable();
            // Zeitraum der Auswirkungen: heute bis in so vielen Tagen; null = keine Eingrenzung.
            $table->unsignedSmallInteger('days_ahead')->nullable();

            // Zeitplan: Wochentage 1 (Mo) bis 7 (So), leer = jeden Tag; Uhrzeiten "HH:MM".
            $table->json('weekdays')->nullable();
            $table->json('times')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('next_run_at')->nullable()->index();
            $table->dateTime('last_run_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('ai_event_searches', function (Blueprint $table) {
            // Aus welcher hinterlegten Suche der Lauf stammt und mit welchem Auftrag er lief.
            $table->foreignId('profile_id')->nullable()->after('id')->constrained('ai_event_search_profiles')->nullOnDelete();
            $table->text('prompt')->nullable()->after('filters');
        });
    }

    public function down(): void
    {
        Schema::table('ai_event_searches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('profile_id');
            $table->dropColumn('prompt');
        });

        Schema::dropIfExists('ai_event_search_profiles');
    }
};
