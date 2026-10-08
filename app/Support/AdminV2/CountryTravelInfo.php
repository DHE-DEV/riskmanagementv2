<?php

namespace App\Support\AdminV2;

use App\Models\CustomEvent;

/**
 * Praktische Reiseinformationen eines Landes (countries.travel_info) – das,
 * was eine Endkunden-App neben den Grunddaten zeigt und was sich selten
 * aendert: Steckertypen, Strom, Notrufnummern, Zeitzonen, Trinkgeld und
 * mehrsprachige Texte. Gebietstyp, Mutterland und Fahrseite stehen als
 * eigene Spalten am Land; Waehrung, Vorwahl und Zeitzone gab es dort schon.
 *
 * Gespeichert wird:
 *   plug_types: ["C", "F"], voltage: 230, frequency: 50,
 *   emergency: {general: "112", police: "110", …}, religions: ["christianity", "islam"],
 *   national_day: {date: "1990-10-03", name: {de: "Tag der Deutschen Einheit", en: "German Unity Day"}},
 *   tipping: {restaurants: {mode: "range", from: 5, to: 10, unit: "percent", currency: "EUR", description: {de: "…"}}, …}
 *   (mode "fixed": nur "from" als fester Wert),
 *   intro: {de: "…", en: "…"}, known_for: {de: ["…"], en: ["…"]}
 */
class CountryTravelInfo
{
    public const TERRITORY_TYPES = [
        'sovereign' => 'Souveräner Staat',
        'dependent' => 'Abhängiges Gebiet',
        'disputed' => 'Umstrittenes Gebiet',
        'special' => 'Sonderfall',
    ];

    public const DRIVING_SIDES = [
        'right' => 'Rechtsverkehr',
        'left' => 'Linksverkehr',
    ];

    /** Steckertypen nach IEC – Buchstabe => Beschreibung */
    public const PLUG_TYPES = [
        'A' => 'Zwei flache Stifte (USA, Japan)',
        'B' => 'Zwei flache Stifte mit Erdung (USA, Kanada)',
        'C' => 'Eurostecker',
        'D' => 'Drei runde Stifte (Indien)',
        'E' => 'Erdungsstift in der Dose (Frankreich)',
        'F' => 'Schuko (Deutschland)',
        'G' => 'Drei rechteckige Stifte (Großbritannien)',
        'H' => 'Drei Stifte (Israel)',
        'I' => 'Schräge Flachstifte (Australien, China)',
        'J' => 'Drei runde Stifte (Schweiz)',
        'K' => 'Drei Stifte (Dänemark)',
        'L' => 'Drei runde Stifte in Reihe (Italien)',
        'M' => 'Drei dicke runde Stifte (Südafrika)',
        'N' => 'Drei runde Stifte (Brasilien)',
        'O' => 'Drei runde Stifte (Thailand)',
    ];

    /** Die grossen Religionen weltweit: Schluessel => [Deutsch, Englisch] */
    public const RELIGIONS = [
        'christianity' => ['Christentum', 'Christianity'],
        'islam' => ['Islam', 'Islam'],
        'hinduism' => ['Hinduismus', 'Hinduism'],
        'buddhism' => ['Buddhismus', 'Buddhism'],
        'judaism' => ['Judentum', 'Judaism'],
        'sikhism' => ['Sikhismus', 'Sikhism'],
        'jainism' => ['Jainismus', 'Jainism'],
        'bahai' => ['Bahaitum', 'Baháʼí Faith'],
        'shinto' => ['Shintoismus', 'Shinto'],
        'taoism' => ['Daoismus', 'Taoism'],
        'confucianism' => ['Konfuzianismus', 'Confucianism'],
        'chinese_folk' => ['Chinesische Volksreligion', 'Chinese folk religion'],
        'traditional' => ['Traditionelle / indigene Religionen', 'Traditional / indigenous religions'],
        'none' => ['Konfessionslos', 'No religion'],
    ];

    /** Trinkgeld: Bereich => Bezeichnung */
    public const TIPPING_CATEGORIES = [
        'hotels' => 'Hotels',
        'guides' => 'Guides',
        'restaurants' => 'Restaurants',
        'taxi' => 'Taxi',
    ];

    /** Art der Angabe: Spanne von–bis oder ein fester Wert */
    public const TIPPING_MODES = [
        'range' => 'Von – bis',
        'fixed' => 'Fester Wert',
    ];

    /** Einheit der Von-bis-Angabe */
    public const TIPPING_UNITS = [
        'percent' => '% des Preises',
        'amount' => 'Betrag',
    ];

    public const EMERGENCY = [
        'general' => 'Allgemeiner Notruf',
        'police' => 'Polizei',
        'ambulance' => 'Rettungsdienst',
        'fire' => 'Feuerwehr',
    ];

    /** Mehrsprachige Texte: Feld => [Bezeichnung, Art (text | textarea | tags)] */
    public const TEXTS = [
        'intro' => ['Einleitung', 'textarea'],
        'known_for' => ['Bekannt für', 'tags'],
    ];

    /** Mehrsprachige Texte des Abschnitts "Strom" */
    public const POWER_TEXTS = [
        'power_notes' => ['Bemerkung zum Strom', 'textarea'],
    ];

    /**
     * Alle mehrsprachigen Textfelder: Feld => [Bezeichnung, Art].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function allTexts(): array
    {
        return self::TEXTS + self::POWER_TEXTS;
    }

    /**
     * Schematisches Bild einer Steckdose dieses Typs (eigene Zeichnung unter
     * public/images/plug-types) – fuer Formular, Apps und Feeds.
     */
    public static function plugImage(string $type): ?string
    {
        $type = strtoupper($type);

        return isset(self::PLUG_TYPES[$type]) ? asset('images/plug-types/'.strtolower($type).'.svg') : null;
    }

    /**
     * Steckertypen eines Landes fuer eine API: Buchstabe, Beschreibung, Bild.
     *
     * @return array<int, array{type: string, description: string, image: ?string}>
     */
    public static function plugTypesForApi(?array $info): array
    {
        return array_values(array_map(fn (string $type) => [
            'type' => $type,
            'description' => self::PLUG_TYPES[$type] ?? '',
            'image' => self::plugImage($type),
        ], array_filter((array) ($info['plug_types'] ?? []), fn ($type) => isset(self::PLUG_TYPES[$type]))));
    }

    /** Deutsche Wochentage, Montag = 1 */
    public const WEEKDAYS = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];

    public static function weekday(\DateTimeInterface $date): string
    {
        return self::WEEKDAYS[(int) $date->format('N')] ?? '';
    }

    /**
     * @return array<int, string>
     */
    public static function locales(): array
    {
        return CustomEvent::translationLocales();
    }

    /**
     * Gespeicherte Angaben -> Formularwerte (alle Felder vorhanden, Listen
     * als kommagetrennter Text).
     *
     * @return array<string, mixed>
     */
    public static function toForm(?array $info): array
    {
        $form = [
            'plug_types' => array_values(array_filter(array_map('strval', (array) ($info['plug_types'] ?? [])), fn ($type) => isset(self::PLUG_TYPES[$type]))),
            'voltage' => isset($info['voltage']) ? (string) $info['voltage'] : '',
            'frequency' => isset($info['frequency']) ? (string) $info['frequency'] : '',
            'emergency' => [],
            'religions' => self::religionKeys((array) ($info['religions'] ?? [])),
            'national_day' => ['date' => (string) ($info['national_day']['date'] ?? ''), 'name' => []],
            'tipping' => [],
            'texts' => [],
        ];

        foreach (self::locales() as $locale) {
            $form['national_day']['name'][$locale] = (string) ($info['national_day']['name'][$locale] ?? '');
        }

        foreach (self::EMERGENCY as $key => $label) {
            $form['emergency'][$key] = (string) ($info['emergency'][$key] ?? '');
        }

        foreach (self::TIPPING_CATEGORIES as $category => $label) {
            $stored = $info['tipping'][$category] ?? [];
            $row = [
                'mode' => isset(self::TIPPING_MODES[$stored['mode'] ?? '']) ? $stored['mode'] : 'range',
                'from' => isset($stored['from']) ? self::number($stored['from']) : '',
                'to' => isset($stored['to']) ? self::number($stored['to']) : '',
                'unit' => isset(self::TIPPING_UNITS[$stored['unit'] ?? '']) ? $stored['unit'] : 'percent',
                'currency' => (string) ($stored['currency'] ?? ''),
                'description' => [],
            ];
            foreach (self::locales() as $locale) {
                $row['description'][$locale] = (string) ($stored['description'][$locale] ?? '');
            }
            $form['tipping'][$category] = $row;
        }

        foreach (self::allTexts() as $field => [, $type]) {
            foreach (self::locales() as $locale) {
                $value = $info[$field][$locale] ?? null;
                $form['texts'][$field][$locale] = $type === 'tags'
                    ? implode(', ', (array) $value)
                    : (string) ($value ?? '');
            }
        }

        return $form;
    }

    /**
     * Formularwerte -> zu speichernde Angaben. Leeres faellt heraus; Sprachen
     * ausserhalb des Formulars bleiben erhalten. Ohne jede Angabe: null.
     *
     * @param  array<string, mixed>  $form
     */
    public static function fromForm(array $form, ?array $existing): ?array
    {
        $info = is_array($existing) ? $existing : [];

        $plugTypes = array_values(array_unique(array_filter(array_map(fn ($type) => strtoupper(trim((string) $type)), (array) ($form['plug_types'] ?? [])), fn ($type) => isset(self::PLUG_TYPES[$type]))));
        sort($plugTypes);
        self::put($info, 'plug_types', $plugTypes);
        self::put($info, 'voltage', is_numeric($form['voltage'] ?? null) ? (int) $form['voltage'] : null);
        self::put($info, 'frequency', is_numeric($form['frequency'] ?? null) ? (int) $form['frequency'] : null);

        $emergency = [];
        foreach (self::EMERGENCY as $key => $label) {
            $number = trim((string) ($form['emergency'][$key] ?? ''));
            if ($number !== '') {
                $emergency[$key] = $number;
            }
        }
        self::put($info, 'emergency', $emergency);

        // Nationaltag: Datum (das Jahr darf das historische sein) und Bezeichnung je Sprache.
        $nationalDay = is_array($info['national_day'] ?? null) ? $info['national_day'] : [];
        $date = trim((string) ($form['national_day']['date'] ?? ''));
        self::put($nationalDay, 'date', preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null);
        $names = is_array($nationalDay['name'] ?? null) ? $nationalDay['name'] : [];
        foreach ((array) ($form['national_day']['name'] ?? []) as $locale => $text) {
            if (trim((string) $text) === '') {
                unset($names[$locale]);
            } else {
                $names[$locale] = trim((string) $text);
            }
        }
        self::put($nationalDay, 'name', $names);
        self::put($info, 'national_day', $nationalDay);

        // Religionen in der Reihenfolge des Anklickens – sie bestimmt die Reihenfolge in Apps und Feeds.
        self::put($info, 'religions', self::religionKeys((array) ($form['religions'] ?? [])));

        $tipping = is_array($info['tipping'] ?? null) ? $info['tipping'] : [];
        foreach (self::TIPPING_CATEGORIES as $category => $label) {
            $raw = (array) ($form['tipping'][$category] ?? []);
            $row = is_array($tipping[$category] ?? null) ? $tipping[$category] : [];

            $mode = isset(self::TIPPING_MODES[$raw['mode'] ?? '']) ? $raw['mode'] : 'range';
            self::put($row, 'mode', $mode === 'fixed' ? 'fixed' : null);
            self::put($row, 'from', self::parseNumber($raw['from'] ?? null));
            // Ein fester Wert hat kein "bis".
            self::put($row, 'to', $mode === 'fixed' ? null : self::parseNumber($raw['to'] ?? null));
            self::put($row, 'unit', isset(self::TIPPING_UNITS[$raw['unit'] ?? '']) ? $raw['unit'] : null);
            self::put($row, 'currency', strtoupper(trim((string) ($raw['currency'] ?? ''))) ?: null);

            $description = is_array($row['description'] ?? null) ? $row['description'] : [];
            foreach ((array) ($raw['description'] ?? []) as $locale => $text) {
                if (trim((string) $text) === '') {
                    unset($description[$locale]);
                } else {
                    $description[$locale] = trim((string) $text);
                }
            }
            self::put($row, 'description', $description);

            // Einheit und Art allein sind keine Angabe.
            if (array_diff(array_keys($row), ['unit', 'mode']) === []) {
                $row = [];
            }
            self::put($tipping, $category, $row);
        }
        self::put($info, 'tipping', $tipping);

        foreach (self::allTexts() as $field => [, $type]) {
            $texts = is_array($info[$field] ?? null) ? $info[$field] : [];
            foreach ((array) ($form['texts'][$field] ?? []) as $locale => $value) {
                $clean = $type === 'tags' ? self::tags((string) $value) : trim((string) $value);
                if ($clean === [] || $clean === '') {
                    unset($texts[$locale]);
                } else {
                    $texts[$locale] = $clean;
                }
            }
            self::put($info, $field, $texts);
        }

        return $info === [] ? null : $info;
    }

    /**
     * @param  array<string, mixed>  $info
     */
    protected static function put(array &$info, string $key, mixed $value): void
    {
        if ($value === null || $value === [] || $value === '') {
            unset($info[$key]);
        } else {
            $info[$key] = $value;
        }
    }

    /**
     * Zahl aus dem Formular (auch "7,5"); null, wenn leer oder keine Zahl.
     */
    public static function parseNumber(mixed $value): int|float|null
    {
        $text = str_replace(',', '.', trim((string) $value));

        if ($text === '' || ! is_numeric($text)) {
            return null;
        }

        return floor((float) $text) == (float) $text ? (int) $text : round((float) $text, 2);
    }

    /**
     * Zahl fuer das Formular in deutscher Schreibweise.
     */
    public static function number(int|float $value): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.'));
    }

    /**
     * @return array<int, string>
     */
    public static function tags(string $text): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode(',', $text)), fn ($tag) => $tag !== '')));
    }

    /**
     * Validierungsregeln der Formularwerte (Praefix = Name der Eigenschaft,
     * $form = die aktuellen Werte fuer Pruefungen ueber mehrere Felder).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $prefix = 'travelInfo', array $form = []): array
    {
        $rules = [
            $prefix.'.plug_types' => ['array'],
            $prefix.'.plug_types.*' => ['string', 'in:'.implode(',', array_keys(self::PLUG_TYPES))],
            $prefix.'.voltage' => ['nullable', 'integer', 'between:100,250'],
            $prefix.'.frequency' => ['nullable', 'integer', 'in:50,60'],
            $prefix.'.national_day.date' => ['nullable', 'date_format:Y-m-d'],
            $prefix.'.national_day.name.*' => ['nullable', 'string', 'max:255'],
            $prefix.'.religions' => ['array'],
            $prefix.'.religions.*' => ['string', 'in:'.implode(',', array_keys(self::RELIGIONS))],
        ];

        foreach (self::TIPPING_CATEGORIES as $category => $label) {
            $number = ['nullable', 'regex:/^\d{1,6}([.,]\d{1,2})?$/'];
            $rules[$prefix.'.tipping.'.$category.'.from'] = $number;
            $rules[$prefix.'.tipping.'.$category.'.to'] = [...$number, function (string $attribute, mixed $value, \Closure $fail) use ($form, $category) {
                if (($form['tipping'][$category]['mode'] ?? 'range') === 'fixed') {
                    return;
                }
                $from = self::parseNumber($form['tipping'][$category]['from'] ?? null);
                $to = self::parseNumber($value);
                if ($from !== null && $to !== null && $to < $from) {
                    $fail('„Bis“ darf nicht kleiner als „Von“ sein.');
                }
            }];
            $rules[$prefix.'.tipping.'.$category.'.mode'] = ['nullable', 'in:'.implode(',', array_keys(self::TIPPING_MODES))];
            $rules[$prefix.'.tipping.'.$category.'.unit'] = ['nullable', 'in:'.implode(',', array_keys(self::TIPPING_UNITS))];
            $rules[$prefix.'.tipping.'.$category.'.currency'] = ['nullable', 'string', 'size:3', \Illuminate\Validation\Rule::exists('currencies', 'code')->where('is_active', true)];
            $rules[$prefix.'.tipping.'.$category.'.description.*'] = ['nullable', 'string', 'max:2000'];
        }

        foreach (self::EMERGENCY as $key => $label) {
            $rules[$prefix.'.emergency.'.$key] = ['nullable', 'string', 'max:30', 'regex:/^[0-9][0-9 +\-\/]*$/'];
        }

        foreach (self::allTexts() as $field => [, $type]) {
            $rules[$prefix.'.texts.'.$field.'.*'] = ['nullable', 'string', $type === 'textarea' ? 'max:5000' : 'max:1000'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function messages(string $prefix = 'travelInfo'): array
    {
        $messages = [
            $prefix.'.plug_types.*.in' => 'Unbekannter Steckertyp.',
            $prefix.'.religions.*.in' => 'Unbekannte Religion.',
            $prefix.'.national_day.date.date_format' => 'Der Nationaltag ist ein Datum (TT.MM.JJJJ).',
            $prefix.'.voltage.integer' => 'Die Netzspannung ist eine ganze Zahl in Volt.',
            $prefix.'.voltage.between' => 'Die Netzspannung liegt zwischen 100 und 250 Volt.',
            $prefix.'.frequency.in' => 'Die Netzfrequenz ist 50 oder 60 Hz.',
        ];

        foreach (self::TIPPING_CATEGORIES as $category => $label) {
            $messages[$prefix.'.tipping.'.$category.'.from.regex'] = 'Trinkgeld '.$label.': „Von“ ist eine Zahl, z. B. 5 oder 7,5.';
            $messages[$prefix.'.tipping.'.$category.'.to.regex'] = 'Trinkgeld '.$label.': „Bis“ ist eine Zahl, z. B. 10.';
            $messages[$prefix.'.tipping.'.$category.'.currency.size'] = 'Trinkgeld '.$label.': Bitte eine Währung aus der Liste wählen.';
            $messages[$prefix.'.tipping.'.$category.'.currency.exists'] = 'Trinkgeld '.$label.': Bitte eine Währung aus der Liste wählen.';
        }

        foreach (self::EMERGENCY as $key => $label) {
            $messages[$prefix.'.emergency.'.$key.'.regex'] = 'Die Nummer „'.$label.'“ darf nur Ziffern, Leerzeichen, + - / enthalten.';
            $messages[$prefix.'.emergency.'.$key.'.max'] = 'Die Nummer „'.$label.'“ ist zu lang.';
        }

        return $messages;
    }

    // ------------------------------------------------------------------
    // KI-Pruefung
    // ------------------------------------------------------------------

    /**
     * Platzhalter des Abschnitts "Strom": Schluessel => Bezeichnung.
     *
     * @return array<string, string>
     */
    public static function powerPlaceholders(): array
    {
        $placeholders = [
            'plug_types' => 'Steckertypen',
            'voltage' => 'Netzspannung (V)',
            'frequency' => 'Netzfrequenz (Hz)',
        ];

        foreach (self::POWER_TEXTS as $field => [$label]) {
            foreach (self::locales() as $locale) {
                $placeholders[$field.'_'.$locale] = $label.' ('.strtoupper($locale).')';
            }
        }

        return $placeholders;
    }

    /**
     * Hinweis an die KI fuer den Abschnitt "Strom".
     */
    public static function powerReviewHint(): string
    {
        return 'Steckertypen als Buchstaben A–O nach IEC, mehrere mit Komma (z. B. "C, F"). Netzspannung in Volt (z. B. 230), Netzfrequenz 50 oder 60.'
            .' Bemerkung: Besonderheiten wie mehrere Spannungen oder Frequenzen je Region, in der jeweils angegebenen Sprache.';
    }

    /**
     * Platzhalter des Abschnitts "Reiseinformationen": Schluessel => Bezeichnung.
     *
     * @return array<string, string>
     */
    public static function placeholders(): array
    {
        $placeholders = [
            'territory_type' => 'Gebietstyp',
            'parent_country' => 'Mutterland',
            'driving_side' => 'Fahrseite',
        ];

        foreach (self::EMERGENCY as $key => $label) {
            $placeholders['emergency_'.$key] = 'Notruf › '.$label;
        }

        $placeholders['religions'] = 'Religionen';
        $placeholders['national_day_date'] = 'Nationaltag › Datum';
        foreach (self::locales() as $locale) {
            $placeholders['national_day_name_'.$locale] = 'Nationaltag › Bezeichnung ('.strtoupper($locale).')';
        }

        foreach (self::TEXTS as $field => [$label]) {
            foreach (self::locales() as $locale) {
                $placeholders[$field.'_'.$locale] = $label.' ('.strtoupper($locale).')';
            }
        }

        return $placeholders;
    }

    /**
     * Hinweis an die KI, welche Werte die Felder annehmen.
     */
    public static function reviewHint(): string
    {
        return 'Gebietstyp: '.implode(', ', self::TERRITORY_TYPES).'. Fahrseite: '.implode(', ', self::DRIVING_SIDES)
            .'. Notrufnummern als Ziffern. Zeitzone als IANA-Name (z. B. Europe/Berlin), mehrere mit Komma.'
            .' Religionen: die im Land verbreiteten aus '.implode(', ', array_map(fn (array $labels) => $labels[0], self::RELIGIONS)).', mehrere mit Komma, die verbreitetste zuerst.'
            .' Nationaltag als Datum JJJJ-MM-TT (Jahr = Ursprungsjahr, falls bekannt) und Bezeichnung je Sprache.'
            .' „Bekannt für“: 3–6 Stichworte, mit Komma getrennt. Texte in der jeweils angegebenen Sprache.';
    }

    /**
     * Platzhalter des Abschnitts "Trinkgeld": je Bereich Von, Bis, Einheit und Beschreibung je Sprache.
     *
     * @return array<string, string>
     */
    public static function tippingPlaceholders(): array
    {
        $placeholders = [];

        foreach (self::TIPPING_CATEGORIES as $category => $label) {
            $placeholders['tipping_'.$category.'_mode'] = $label.' › Art (Spanne / fester Wert)';
            $placeholders['tipping_'.$category.'_from'] = $label.' › Von bzw. fester Wert';
            $placeholders['tipping_'.$category.'_to'] = $label.' › Bis';
            $placeholders['tipping_'.$category.'_unit'] = $label.' › Einheit';
            $placeholders['tipping_'.$category.'_currency'] = $label.' › Währung';
            foreach (self::locales() as $locale) {
                $placeholders['tipping_'.$category.'_description_'.$locale] = $label.' › Beschreibung ('.strtoupper($locale).')';
            }
        }

        return $placeholders;
    }

    /**
     * Hinweis an die KI fuer den Abschnitt "Trinkgeld".
     */
    public static function tippingReviewHint(): string
    {
        return 'Je Bereich (Hotels, Guides, Restaurants, Taxi) ein üblicher Rahmen als Zahlen „Von“ und „Bis“ – oder ein fester Wert (Art „fester Wert“, nur „Von“) – mit Einheit: '
            .implode(' oder ', self::TIPPING_UNITS).'. Bei Beträgen die Währung als ISO-4217-Code (z. B. EUR, USD) und den Bezug (pro Tag, pro Gepäckstück) in der Beschreibung nennen. Beschreibung in der jeweils angegebenen Sprache.';
    }

    /**
     * Steckertypen aus einem KI-Vorschlag wie "C, F" oder "Typ C und F".
     *
     * @return array<int, string>
     */
    public static function parsePlugTypes(string $value): array
    {
        preg_match_all('/\b([A-O])\b/u', strtoupper($value), $matches);
        $types = array_values(array_unique($matches[1]));
        sort($types);

        return $types;
    }

    /**
     * Nur bekannte Religionen, jede einmal, in der gegebenen Reihenfolge.
     *
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    public static function religionKeys(array $values): array
    {
        return array_values(array_unique(array_filter(array_map('strval', $values), fn ($key) => isset(self::RELIGIONS[$key]))));
    }

    /**
     * Religionen aus einem KI-Vorschlag wie "Christentum, Islam" – Schluessel,
     * deutsche oder englische Bezeichnung, in der genannten Reihenfolge.
     *
     * @return array<int, string>
     */
    public static function parseReligions(string $value): array
    {
        $found = [];

        foreach (preg_split('/[,;\/]+|\bund\b|\band\b/u', $value) ?: [] as $part) {
            $needle = mb_strtolower(trim($part));
            if ($needle === '') {
                continue;
            }
            foreach (self::RELIGIONS as $key => [$de, $en]) {
                $labels = array_map('mb_strtolower', [$key, $de, $en]);
                if (in_array($needle, $labels, true) || array_filter($labels, fn ($label) => str_contains($needle, $label) || str_contains($label, $needle))) {
                    $found[] = $key;
                    break;
                }
            }
        }

        return self::religionKeys($found);
    }

    /**
     * Schluessel einer Auswahl aus einem KI-Vorschlag – als Schluessel oder Bezeichnung.
     *
     * @param  array<string, string>  $options
     */
    public static function parseOption(array $options, string $value): ?string
    {
        $needle = mb_strtolower(trim($value));

        foreach ($options as $key => $label) {
            if ($needle === mb_strtolower($key) || $needle === mb_strtolower($label)) {
                return $key;
            }
        }

        foreach ($options as $key => $label) {
            if (str_contains(mb_strtolower($label), $needle) || str_contains($needle, mb_strtolower($key))) {
                return $key;
            }
        }

        return null;
    }
}
