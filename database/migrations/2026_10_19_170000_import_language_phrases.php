<?php

use App\Models\LanguagePhrase;
use Illuminate\Database\Migrations\Migration;

/**
 * Wichtige Saetze aller Landessprachen aus database/data/phrases.json (lokal per `php artisan phrases:generate`
 * erzeugt und mit --export geschrieben) – als KI-Entwurf. Vorhandene Saetze bleiben. In Unit-Tests uebersprungen.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/phrases.json');
        if (app()->runningUnitTests() || ! is_file($path)) {
            return;
        }

        $now = now();
        foreach (json_decode(file_get_contents($path), true) as $language => $phrases) {
            $existing = LanguagePhrase::where('language', $language)->pluck('key')->all();
            $rows = [];
            foreach ($phrases as $key => $phrase) {
                if (in_array($key, $existing, true)) {
                    continue;
                }
                $rows[] = [
                    'language' => $language, 'key' => $key, 'text' => $phrase['text'], 'romanization' => $phrase['romanization'] ?? null,
                    'status' => LanguagePhrase::STATUS_AI_DRAFT, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                LanguagePhrase::insert($chunk);
            }
        }
    }

    public function down(): void
    {
        // Daten bleiben.
    }
};
