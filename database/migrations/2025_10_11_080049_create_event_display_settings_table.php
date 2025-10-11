<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_display_settings', function (Blueprint $table) {
            $table->id();

            // Icon-Strategie für Multi-Event-Anzeige
            $table->enum('multi_event_icon_strategy', [
                'default',           // Standard: Erstes Icon verwenden (aktuelles Verhalten)
                'manual_select',     // Manuell ein Icon aus den gewählten Event-Typen auswählen
                'multi_event_type',  // Spezial-Icon für Multi-Event verwenden
                'show_all',          // Alle Icons auf der Karte anzeigen (gestapelt)
                'show_icon_preview'  // Nur Vorschau im Formular anzeigen
            ])->default('default');

            // ID des Multi-Event-Typs (z.B. Event-Typ 15)
            $table->foreignId('multi_event_type_id')
                ->nullable()
                ->constrained('event_types')
                ->nullOnDelete();

            // Icon-Vorschau im Formular anzeigen
            $table->boolean('show_icon_preview_in_form')->default(true);

            // Beschreibungen/Hilfetexte
            $table->text('strategy_description')->nullable();

            $table->timestamps();
        });

        // Initialen Datensatz erstellen
        DB::table('event_display_settings')->insert([
            'multi_event_icon_strategy' => 'default',
            'show_icon_preview_in_form' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_display_settings');
    }
};
