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
        // Fix migrations table first (wichtig für Laravel selbst)
        // Prüfe ob migrations Tabelle bereits einen PRIMARY KEY hat
        $hasPrimaryKey = DB::select("SHOW KEYS FROM migrations WHERE Key_name = 'PRIMARY'");
        if (empty($hasPrimaryKey)) {
            DB::statement('ALTER TABLE migrations MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
        } else {
            // Nur AUTO_INCREMENT hinzufügen wenn PRIMARY KEY schon existiert
            DB::statement('ALTER TABLE migrations MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        // Liste aller Tabellen, die AUTO_INCREMENT auf id benötigen
        $tables = [
            'ai_cost_alerts',
            'ai_examples',
            'ai_job_progress',
            'ai_processing_logs',
            'ai_prompts',
            'ai_quotas',
            'ai_system_health',
            'ai_usage_tracking',
            'airports',
            'continents',
            'country_custom_event',
            'custom_event_event_type',
            'disaster_events',
            'event_categories',
            'event_clicks',
            'event_display_settings',
            'prompt_templates',
            'social_links',
            'user_language_preferences',
            'users',
        ];

        // Setze AUTO_INCREMENT für alle id Felder
        foreach ($tables as $table) {
            // Prüfe ob Tabelle bereits einen PRIMARY KEY auf id hat
            $hasPrimaryKey = DB::select("SHOW KEYS FROM {$table} WHERE Key_name = 'PRIMARY' AND Column_name = 'id'");

            if (empty($hasPrimaryKey)) {
                // Kein PRIMARY KEY -> setze PRIMARY KEY mit AUTO_INCREMENT
                DB::statement("ALTER TABLE {$table} MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY");
            } else {
                // PRIMARY KEY existiert bereits -> nur AUTO_INCREMENT hinzufügen
                DB::statement("ALTER TABLE {$table} MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
            }
        }

        // Setze Default-Werte für sort_order Felder
        DB::statement('ALTER TABLE event_types MODIFY sort_order INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE event_categories MODIFY sort_order INT NOT NULL DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse migration ist nicht sinnvoll, da AUTO_INCREMENT nicht entfernt werden sollte
        // und Default-Werte die Datenintegrität verbessern
    }
};
