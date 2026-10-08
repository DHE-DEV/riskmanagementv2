<?php

use App\Models\Country;
use App\Support\AdminV2\CountryTravelInfo;
use Illuminate\Database\Migrations\Migration;

/**
 * Laenderbeschreibung fuer alle Laender: Kurzbeschreibung, ausfuehrliche
 * Beschreibung und "Bekannt fuer" in Deutsch, Englisch und Niederlaendisch.
 *
 * Die deutschen Texte wurden redaktionell mit KI-Unterstuetzung verfasst
 * (Stand Oktober 2026) und per DeepL uebersetzt; sie liegen in
 * database/data/country-descriptions.json. Sie sind als Grundlage gedacht
 * und koennen im Admin je Land ueberarbeitet werden.
 *
 * Es wird nur gefuellt, was leer ist – je Feld und Sprache.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $path = database_path('data/country-descriptions.json');

        if (! is_file($path)) {
            return;
        }

        $data = json_decode((string) file_get_contents($path), true) ?: [];

        foreach (Country::query()->withTrashed()->get() as $country) {
            $texts = $data[strtoupper((string) $country->iso_code)] ?? null;

            if (! $texts) {
                continue;
            }

            $info = is_array($country->travel_info) ? $country->travel_info : [];
            $changed = false;

            foreach (['short_description', 'description', 'known_for'] as $field) {
                $current = is_array($info[$field] ?? null) ? $info[$field] : [];

                foreach ($texts[$field] ?? [] as $locale => $value) {
                    if (! empty($current[$locale])) {
                        continue;
                    }

                    $clean = $field === 'known_for' ? CountryTravelInfo::tags((string) $value) : trim((string) $value);

                    if ($clean === [] || $clean === '') {
                        continue;
                    }

                    $current[$locale] = $clean;
                    $changed = true;
                }

                if ($current !== []) {
                    $info[$field] = $current;
                }
            }

            if ($changed) {
                $country->forceFill(['travel_info' => $info])->saveQuietly();
            }
        }
    }

    public function down(): void
    {
        // Die Texte lassen sich im Admin aendern – ein Zuruecknehmen wuerde auch das treffen.
    }
};
