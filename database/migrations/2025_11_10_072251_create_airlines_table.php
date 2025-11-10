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
        Schema::create('airlines', function (Blueprint $table) {
            $table->id();

            // Allgemeine Airline-Informationen
            $table->string('name');
            $table->string('iata_code', 2)->unique()->nullable();
            $table->string('icao_code', 3)->unique()->nullable();
            $table->foreignId('home_country_id')->nullable()->constrained('countries')->onDelete('set null');
            $table->string('headquarters')->nullable(); // Hauptsitz (Stadt)
            $table->string('website')->nullable();
            $table->string('booking_url')->nullable();

            // Kontaktmöglichkeiten (JSON)
            // Struktur: {"hotline": "+49 123...", "chat_url": "https://...", "email": "..."}
            $table->json('contact_info')->nullable();

            // Service & Boardinformationen
            // Freigepäck & Handgepäck (JSON)
            // Struktur: {
            //   "checked_baggage": {"economy": "1x23kg", "business": "2x32kg", ...},
            //   "hand_baggage": {"economy": "1x8kg", "business": "2x8kg", ...}
            // }
            $table->json('baggage_rules')->nullable();

            // Tarifarten (JSON - Array von verfügbaren Klassen)
            // ["economy", "premium_economy", "business", "first"]
            $table->json('cabin_classes')->nullable();

            // Spezielle Services
            // Haustiermitnahme (JSON)
            // Struktur: {
            //   "allowed": true,
            //   "in_cabin": {"max_weight": "8kg", "carrier_size": "..."},
            //   "in_hold": {"max_weight": "75kg"},
            //   "info_url": "...",
            //   "notes": "..."
            // }
            $table->json('pet_policy')->nullable();

            // Lounges (JSON - Array)
            // Struktur ähnlich wie bei Airports
            $table->json('lounges')->nullable();

            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        // Pivot-Tabelle für Direktverbindungen (airline_airport)
        Schema::create('airline_airport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airline_id')->constrained()->onDelete('cascade');
            $table->foreignId('airport_id')->constrained()->onDelete('cascade');
            $table->enum('direction', ['from', 'to', 'both'])->default('both');
            $table->timestamps();

            $table->unique(['airline_id', 'airport_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airline_airport');
        Schema::dropIfExists('airlines');
    }
};
