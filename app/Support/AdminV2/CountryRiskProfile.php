<?php

namespace App\Support\AdminV2;

use App\Models\CustomEvent;

/**
 * Aufbau des Risikoprofils eines Landes (countries.risk_profile): Bereiche
 * und ihre Felder – eine Liste fuer Formular und Speichern.
 *
 * Feldarten: level (Stufe 1–5), bool, text, textarea, tags, number.
 *
 * Zu jedem Punkt ohne eigenes Textfeld gibt es eine Notiz je Sprache; sie
 * liegen im Bereich unter "notes": Feld => Sprache => Text.
 */
class CountryRiskProfile
{
    public const LEVELS = [
        1 => 'Sehr niedrig',
        2 => 'Niedrig',
        3 => 'Mittel',
        4 => 'Hoch',
        5 => 'Sehr hoch',
    ];

    /**
     * @return array<string, array{label: string, icon: string, fields: array<string, array{label: string, type: string, wide?: bool, placeholder?: string}>}>
     */
    public static function categories(): array
    {
        return [
            'security' => [
                'label' => 'Sicherheit',
                'icon' => 'shield-check',
                'fields' => [
                    'overall_risk_level' => ['label' => 'Gesamt-Sicherheitsrisiko', 'type' => 'level'],
                    'political_stability' => ['label' => 'Politische Stabilität', 'type' => 'level'],
                    'crime_level' => ['label' => 'Kriminalitätsniveau', 'type' => 'level'],
                    'terrorism_risk' => ['label' => 'Terrorismusrisiko', 'type' => 'level'],
                    'description' => ['label' => 'Beschreibung', 'type' => 'textarea'],
                ],
            ],
            'health' => [
                'label' => 'Gesundheit',
                'icon' => 'heart',
                'fields' => [
                    'health_risk_level' => ['label' => 'Gesundheitsrisiko', 'type' => 'level'],
                    'healthcare_quality' => ['label' => 'Gesundheitsversorgung', 'type' => 'level'],
                    'malaria_risk' => ['label' => 'Malaria-Risiko', 'type' => 'bool'],
                    'drinking_water_safe' => ['label' => 'Trinkwasser sicher', 'type' => 'bool'],
                    'malaria_description' => ['label' => 'Malaria-Beschreibung', 'type' => 'textarea'],
                    'required_vaccinations' => ['label' => 'Pflichtimpfungen', 'type' => 'tags', 'placeholder' => 'z. B. Gelbfieber, Polio'],
                    'recommended_vaccinations' => ['label' => 'Empfohlene Impfungen', 'type' => 'tags', 'placeholder' => 'z. B. Hepatitis A, Typhus'],
                    'description' => ['label' => 'Beschreibung', 'type' => 'textarea'],
                ],
            ],
            'natural_hazards' => [
                'label' => 'Naturgefahren',
                'icon' => 'bolt',
                'fields' => [
                    'natural_hazard_level' => ['label' => 'Gesamt-Naturgefahren', 'type' => 'level', 'wide' => true],
                    'earthquake_risk' => ['label' => 'Erdbeben', 'type' => 'level'],
                    'flood_risk' => ['label' => 'Überschwemmungen', 'type' => 'level'],
                    'hurricane_risk' => ['label' => 'Hurrikane/Stürme', 'type' => 'level'],
                    'volcano_risk' => ['label' => 'Vulkane', 'type' => 'level'],
                    'wildfire_risk' => ['label' => 'Waldbrände', 'type' => 'level'],
                    'tsunami_risk' => ['label' => 'Tsunamis', 'type' => 'level'],
                    'description' => ['label' => 'Beschreibung', 'type' => 'textarea'],
                ],
            ],
            'infrastructure' => [
                'label' => 'Infrastruktur',
                'icon' => 'building-office',
                'fields' => [
                    'infrastructure_level' => ['label' => 'Infrastruktur-Niveau', 'type' => 'level'],
                    'road_safety' => ['label' => 'Straßensicherheit', 'type' => 'level'],
                    'public_transport_quality' => ['label' => 'ÖPNV-Qualität', 'type' => 'level'],
                    'medical_infrastructure' => ['label' => 'Medizinische Infrastruktur', 'type' => 'level'],
                    'internet_availability' => ['label' => 'Internet-Verfügbarkeit', 'type' => 'level'],
                    'description' => ['label' => 'Beschreibung', 'type' => 'textarea'],
                ],
            ],
            'entry' => [
                'label' => 'Einreise',
                'icon' => 'identification',
                'fields' => [
                    'visa_required' => ['label' => 'Visum erforderlich', 'type' => 'bool'],
                    'passport_validity_months' => ['label' => 'Reisepass-Gültigkeit (Monate)', 'type' => 'number'],
                    'visa_notes' => ['label' => 'Visum-Hinweise', 'type' => 'textarea'],
                    'special_requirements' => ['label' => 'Besondere Anforderungen', 'type' => 'textarea'],
                    'description' => ['label' => 'Beschreibung', 'type' => 'textarea'],
                ],
            ],
            'climate' => [
                'label' => 'Klima',
                'icon' => 'sun',
                'fields' => [
                    'climate_zone' => ['label' => 'Klimazone', 'type' => 'text'],
                    'extreme_weather_risk' => ['label' => 'Extremwetter-Risiko', 'type' => 'level'],
                    'best_travel_months' => ['label' => 'Beste Reisemonate', 'type' => 'tags', 'placeholder' => 'z. B. April, Mai, Oktober'],
                    'rainy_season' => ['label' => 'Regenzeit', 'type' => 'text'],
                    'description' => ['label' => 'Beschreibung', 'type' => 'textarea'],
                ],
            ],
            'culture_law' => [
                'label' => 'Kultur & Recht',
                'icon' => 'scale',
                'fields' => [
                    'drug_laws_severity' => ['label' => 'Drogengesetze (Strenge)', 'type' => 'level'],
                    'lgbtq_safety' => ['label' => 'LGBTQ+-Sicherheit', 'type' => 'level'],
                    'women_safety' => ['label' => 'Sicherheit für Frauen', 'type' => 'level'],
                    'cultural_notes' => ['label' => 'Kulturelle Hinweise', 'type' => 'textarea'],
                    'legal_warnings' => ['label' => 'Rechtliche Warnungen', 'type' => 'textarea'],
                    'dress_code_notes' => ['label' => 'Kleidungsvorschriften', 'type' => 'textarea'],
                    'alcohol_regulations' => ['label' => 'Alkoholbestimmungen', 'type' => 'textarea'],
                    'description' => ['label' => 'Beschreibung', 'type' => 'textarea'],
                ],
            ],
        ];
    }

    /**
     * Ob zu diesem Feld eine Notiz gehoert – Textfelder sind selbst Fliesstext.
     */
    public static function hasNote(array $meta): bool
    {
        return $meta['type'] !== 'textarea';
    }

    /**
     * Platzhalter-Schluessel aller Felder mit Notiz.
     *
     * @return array<int, string>
     */
    public static function noteKeys(): array
    {
        $keys = [];

        foreach (self::categories() as $category => $definition) {
            foreach ($definition['fields'] as $field => $meta) {
                if (self::hasNote($meta)) {
                    $keys[] = self::placeholderKey($category, $field);
                }
            }
        }

        return $keys;
    }

    /**
     * Sprachen der Notizen, die Ausgangssprache zuerst – wie bei den Ereignissen.
     *
     * @return array<int, string>
     */
    public static function noteLocales(): array
    {
        return CustomEvent::translationLocales();
    }

    /**
     * Platzhalter-Schluessel eines Feldes, z. B. risk_security_crime_level.
     */
    public static function placeholderKey(string $category, string $field): string
    {
        return 'risk_'.$category.'_'.$field;
    }

    /**
     * Alle Felder als Platzhalter: Schluessel => "Bereich › Feld".
     *
     * @return array<string, string>
     */
    public static function placeholders(): array
    {
        $placeholders = [];

        foreach (self::categories() as $category => $definition) {
            foreach ($definition['fields'] as $field => $meta) {
                $placeholders[self::placeholderKey($category, $field)] = $definition['label'].' › '.$meta['label'];
            }
        }

        return $placeholders;
    }

    /**
     * Platzhalter-Schluessel -> [Bereich, Feld, Felddefinition] oder null.
     *
     * @return array{0: string, 1: string, 2: array{label: string, type: string}}|null
     */
    public static function resolvePlaceholder(string $key): ?array
    {
        foreach (self::categories() as $category => $definition) {
            foreach ($definition['fields'] as $field => $meta) {
                if (self::placeholderKey($category, $field) === $key) {
                    return [$category, $field, $meta];
                }
            }
        }

        return null;
    }

    /**
     * Formularwert eines Feldes in lesbarer Form fuer die KI.
     */
    public static function describe(array $meta, mixed $value): mixed
    {
        return match ($meta['type']) {
            'level' => $value === '' || $value === null ? null : $value.' – '.(self::LEVELS[(int) $value] ?? ''),
            'bool' => (bool) $value,
            default => $value === '' ? null : $value,
        };
    }

    /**
     * Vorschlag der KI in den Formularwert eines Feldes uebersetzen; null, wenn
     * er nicht passt (z. B. unbekannte Stufe).
     */
    public static function parseSuggestion(array $meta, string $value): mixed
    {
        $value = trim($value);

        return match ($meta['type']) {
            'level' => self::parseLevel($value),
            'bool' => in_array(mb_strtolower($value), ['ja', 'yes', 'true', '1', 'wahr'], true),
            'number' => preg_match('/-?\d+/', $value, $match) ? $match[0] : null,
            default => $value,
        };
    }

    protected static function parseLevel(string $value): ?string
    {
        if (preg_match('/^\s*([1-5])\b/', $value, $match)) {
            return $match[1];
        }

        foreach (self::LEVELS as $level => $label) {
            if (mb_strtolower($label) === mb_strtolower($value)) {
                return (string) $level;
            }
        }

        return null;
    }

    /**
     * Gespeichertes Profil -> Formularwerte (alle Felder vorhanden, Listen als
     * kommagetrennter Text).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function toForm(?array $profile): array
    {
        $form = [];

        foreach (self::categories() as $category => $definition) {
            foreach ($definition['fields'] as $field => $meta) {
                $value = $profile[$category][$field] ?? null;

                $form[$category][$field] = match ($meta['type']) {
                    'bool' => (bool) $value,
                    'tags' => is_array($value) ? implode(', ', $value) : (string) $value,
                    default => $value === null ? '' : (string) $value,
                };

                if (self::hasNote($meta)) {
                    foreach (self::noteLocales() as $locale) {
                        $form[$category]['notes'][$field][$locale] = (string) ($profile[$category]['notes'][$field][$locale] ?? '');
                    }
                }
            }
        }

        return $form;
    }

    /**
     * Formularwerte -> zu speicherndes Profil. Leere Felder fallen heraus,
     * Angaben ausserhalb des Formulars bleiben erhalten. Ein Profil ohne jede
     * Angabe wird zu null – das Land gilt dann als nicht bewertet.
     *
     * @param  array<string, array<string, mixed>>  $form
     */
    public static function fromForm(array $form, ?array $existing): ?array
    {
        $profile = is_array($existing) ? $existing : [];
        $hasContent = false;

        foreach (self::categories() as $category => $definition) {
            $values = is_array($profile[$category] ?? null) ? $profile[$category] : [];

            foreach ($definition['fields'] as $field => $meta) {
                $raw = $form[$category][$field] ?? null;

                $value = match ($meta['type']) {
                    'level' => in_array((int) $raw, array_keys(self::LEVELS), true) ? (int) $raw : null,
                    'bool' => (bool) $raw,
                    'number' => is_numeric($raw) ? (int) $raw : null,
                    'tags' => array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $raw)), fn ($tag) => $tag !== ''))),
                    default => trim((string) $raw) === '' ? null : trim((string) $raw),
                };

                if ($value === null) {
                    unset($values[$field]);

                    continue;
                }

                $values[$field] = $value;
                $hasContent = $hasContent || ($value !== false && $value !== []);
            }

            // Notizen: leere fallen heraus, Sprachen ausserhalb des Formulars bleiben erhalten.
            $notes = is_array($values['notes'] ?? null) ? $values['notes'] : [];
            foreach ($definition['fields'] as $field => $meta) {
                foreach ((array) ($form[$category]['notes'][$field] ?? []) as $locale => $text) {
                    if (self::hasNote($meta) && trim((string) $text) !== '') {
                        $notes[$field][$locale] = trim((string) $text);
                    } else {
                        unset($notes[$field][$locale]);
                    }
                }
            }
            $notes = array_filter($notes);
            unset($values['notes']);
            if ($notes !== []) {
                $values['notes'] = $notes;
            }

            // Angaben, die das Formular nicht kennt, zaehlen ebenfalls als Inhalt.
            $hasContent = $hasContent || array_diff_key($values, $definition['fields']) !== [];

            if ($values === []) {
                unset($profile[$category]);
            } else {
                $profile[$category] = $values;
            }
        }

        $hasContent = $hasContent || array_diff_key($profile, self::categories()) !== [];

        return $hasContent ? $profile : null;
    }

    /**
     * Farbe der Risikostufe fuer flux:badge.
     */
    public static function color(?int $level): string
    {
        return match ($level) {
            1 => 'green',
            2 => 'lime',
            3 => 'amber',
            4 => 'orange',
            5 => 'red',
            default => 'zinc',
        };
    }
}
