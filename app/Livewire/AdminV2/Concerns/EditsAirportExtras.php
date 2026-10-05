<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Support\AdminV2\AirportExtras;
use Illuminate\Database\Eloquent\Model;

/**
 * Lounges, Mobilitaetsangebote und Hotels – fuer Flughaefen und
 * Flughafen-Codes gleich.
 */
trait EditsAirportExtras
{
    /** @var array<int, array<string, mixed>> */
    public array $lounges = [];

    /** @var array<string, array<string, mixed>> */
    public array $mobility = [];

    /** @var array<int, array<string, mixed>> */
    public array $hotels = [];

    protected function fillAirportExtras(?Model $record): void
    {
        $this->lounges = AirportExtras::loungesToForm($record?->lounges);
        $this->mobility = AirportExtras::mobilityToForm($record?->mobility_options);
        $this->hotels = AirportExtras::hotelsToForm($record?->nearby_hotels);
    }

    /**
     * @return array<string, mixed>
     */
    protected function airportExtrasValues(): array
    {
        return [
            'lounges' => AirportExtras::loungesFromForm($this->lounges),
            'mobility_options' => AirportExtras::mobilityFromForm($this->mobility),
            'nearby_hotels' => AirportExtras::hotelsFromForm($this->hotels),
        ];
    }

    /**
     * Welche der drei Bereiche sich gegenueber dem gespeicherten Stand nicht
     * geaendert haben – verglichen in der vereinheitlichten Form, die auch das
     * Speichern schreibt. Vor dem Speichern aufrufen.
     *
     * @return array<int, string>
     */
    protected function unchangedAirportExtras(?Model $record): array
    {
        if (! $record?->exists) {
            return [];
        }

        $before = [
            'lounges' => AirportExtras::loungesFromForm(AirportExtras::loungesToForm($record->lounges)),
            'mobility_options' => AirportExtras::mobilityFromForm(AirportExtras::mobilityToForm($record->mobility_options)),
            'nearby_hotels' => AirportExtras::hotelsFromForm(AirportExtras::hotelsToForm($record->nearby_hotels)),
        ];
        $after = $this->airportExtrasValues();

        return array_keys(array_filter($before, fn (array $value, string $key) => $value == $after[$key], ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Lounges, Mobilitaet und Hotels in lesbarer Form fuer die KI-Pruefungen.
     *
     * @return array{lounges: array<int, string>, mobility: array<int, string>, hotels: array<int, string>}
     */
    protected function airportExtrasContext(): array
    {
        return AirportExtras::describe($this->lounges, $this->mobility, $this->hotels) + $this->airportExtrasRowContext();
    }

    /**
     * Felder einer Lounge bzw. eines Hotels fuer die Feldpruefung: Feld => Bezeichnung.
     *
     * @return array<string, array<string, string>>
     */
    protected function airportExtrasRowFields(): array
    {
        return [
            'lounge' => ['name' => 'Name der Lounge', 'location' => 'Standort', 'access' => 'Zugang', 'price_per_person' => 'Preis pro Person ab', 'url' => 'Website/Info-URL', 'children_welcome' => 'Kinder willkommen'],
            'hotel' => ['name' => 'Name des Hotels', 'distance_km' => 'Entfernung (km)', 'booking_url' => 'Buchungs-URL', 'notes' => 'Zusätzliche Informationen', 'shuttle' => 'Shuttle-Service verfügbar'],
        ];
    }

    /**
     * Die Felder der Abschnitte "lounges" und "hotels" fuer die Feldpruefung:
     * jedes Feld jedes Eintrags einzeln ("lounge_0_name" …) und dazu, was fehlt
     * ("lounges_new"). Fuer andere Abschnitte null.
     *
     * @return array<string, string>|null
     */
    protected function airportExtrasReviewLabels(string $section): ?array
    {
        if ($section === 'mobility') {
            return $this->mobilityReviewLabels();
        }

        [$kind, $rows, $noun, $plural] = match ($section) {
            'lounges' => ['lounge', $this->lounges, 'Lounge', 'Lounges'],
            'hotels' => ['hotel', $this->hotels, 'Hotel', 'Hotels'],
            default => [null, [], '', ''],
        };

        if ($kind === null) {
            return null;
        }

        // Die Sammelangabe bleibt fuer hinterlegte Pruefungen und eigene Prompts.
        $labels = [$section => $plural.' (Liste)'];

        foreach ($rows as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));

            foreach ($this->airportExtrasRowFields()[$kind] as $field => $label) {
                $labels[$kind.'_'.$index.'_'.$field] = $noun.' '.($index + 1).($name !== '' ? ' – '.$name : '').' › '.$label;
            }
        }

        return $labels + [$section.'_new' => 'Fehlende '.$plural];
    }

    /**
     * Die Felder des Abschnitts "mobility" fuer die Feldpruefung – je Angebot:
     * verfuegbar ("mobility_taxi_available"), seine festen Felder
     * ("mobility_taxi_info"), jede Zeile seiner Liste
     * ("mobility_parking_0_name") und was in der Liste fehlt ("mobility_parking_new").
     *
     * @return array<string, string>
     */
    protected function mobilityReviewLabels(): array
    {
        // Die Sammelangabe bleibt fuer hinterlegte Pruefungen und eigene Prompts.
        $labels = ['mobility' => 'Mobilitätsangebote (Liste)'];

        foreach (AirportExtras::mobility() as $option => $definition) {
            $group = $definition['label'].' › ';
            $labels['mobility_'.$option.'_available'] = $group.'Verfügbar';

            foreach ($definition['fields'] ?? [] as $field => $meta) {
                $labels['mobility_'.$option.'_'.$field] = $group.$meta['label'];
            }

            if ($list = $definition['list'] ?? null) {
                foreach ($this->mobility[$option][$list['key']] ?? [] as $index => $row) {
                    $name = trim((string) ($row['name'] ?? ''));

                    foreach ($list['fields'] as $field => $label) {
                        $labels['mobility_'.$option.'_'.$index.'_'.$field] = $group.'Zeile '.($index + 1).($name !== '' ? ' ('.$name.')' : '').': '.$label;
                    }
                }

                $labels['mobility_'.$option.'_new'] = $group.'Fehlende '.$list['label'];
            }
        }

        return $labels;
    }

    /**
     * Die Werte zu mobilityReviewLabels().
     *
     * @return array<string, mixed>
     */
    protected function mobilityReviewContext(): array
    {
        $context = [];

        foreach (AirportExtras::mobility() as $option => $definition) {
            $values = $this->mobility[$option] ?? [];
            $context['mobility_'.$option.'_available'] = (bool) ($values['available'] ?? false);

            foreach ($definition['fields'] ?? [] as $field => $meta) {
                $context['mobility_'.$option.'_'.$field] = $values[$field] ?? '';
            }

            if ($list = $definition['list'] ?? null) {
                foreach ($values[$list['key']] ?? [] as $index => $row) {
                    foreach (array_keys($list['fields']) as $field) {
                        $context['mobility_'.$option.'_'.$index.'_'.$field] = $row[$field] ?? '';
                    }
                }

                $context['mobility_'.$option.'_new'] = '';
            }
        }

        return $context;
    }

    /**
     * Vorschlag der KI zu einem Feld der Mobilitaetsangebote uebernehmen.
     * null, wenn der Schluessel nicht hierher gehoert.
     */
    protected function aiApplyMobility(string $key, string $value): ?bool
    {
        foreach (AirportExtras::mobility() as $option => $definition) {
            if (! str_starts_with($key, 'mobility_'.$option.'_')) {
                continue;
            }

            $field = substr($key, strlen('mobility_'.$option.'_'));
            $list = $definition['list'] ?? null;

            if ($field === 'available') {
                $this->mobility[$option]['available'] = $this->aiBool($value);

                return true;
            }

            if (isset($definition['fields'][$field])) {
                $this->mobility[$option][$field] = $value;

                return true;
            }

            // Fehlende Zeilen der Liste: je Zeile eine, die Angaben durch Strichpunkt getrennt.
            if ($list && $field === 'new') {
                $known = array_map(fn ($row) => mb_strtolower(trim((string) ($row['name'] ?? ''))), $this->mobility[$option][$list['key']] ?? []);
                $added = 0;

                foreach (preg_split('/\R/u', $value) ?: [] as $line) {
                    $parts = array_map('trim', explode(';', preg_replace('/^\s*(?:[-*•]\s+|\d+[.)]\s+)/u', '', $line)));

                    if (($parts[0] ?? '') === '' || in_array(mb_strtolower($parts[0]), $known, true)) {
                        continue;
                    }

                    $row = [];
                    foreach (array_keys($list['fields']) as $position => $name) {
                        $row[$name] = $parts[$position] ?? '';
                    }

                    $this->mobility[$option][$list['key']][] = $row;
                    $known[] = mb_strtolower($parts[0]);
                    $added++;
                }

                // Mit einem Eintrag ist das Angebot auch verfuegbar.
                if ($added > 0) {
                    $this->mobility[$option]['available'] = true;
                }

                return $added > 0;
            }

            if ($list && preg_match('/^(\d+)_([a-z_]+)$/', $field, $match) && isset($list['fields'][$match[2]], $this->mobility[$option][$list['key']][(int) $match[1]])) {
                $this->mobility[$option][$list['key']][(int) $match[1]][$match[2]] = $value;

                return true;
            }

            return false;
        }

        return null;
    }

    /**
     * Die Werte zu airportExtrasReviewLabels().
     *
     * @return array<string, mixed>
     */
    protected function airportExtrasRowContext(): array
    {
        $context = ['lounges_new' => '', 'hotels_new' => ''] + $this->mobilityReviewContext();

        foreach (['lounge' => $this->lounges, 'hotel' => $this->hotels] as $kind => $rows) {
            foreach ($rows as $index => $row) {
                foreach (array_keys($this->airportExtrasRowFields()[$kind]) as $field) {
                    $context[$kind.'_'.$index.'_'.$field] = $row[$field] ?? '';
                }
            }
        }

        return $context;
    }

    /**
     * Hinweis an die KI zur Feldpruefung von Lounges bzw. Hotels.
     */
    protected function airportExtrasReviewHint(string $section): ?string
    {
        return match ($section) {
            'lounges' => 'Die Felder gehören zu einzelnen Lounges („Lounge 1“, „Lounge 2“ …): prüfe jede eingetragene Lounge Feld für Feld. '
                .'Gibt es eine eingetragene Lounge nicht mehr, vermerke das in der Begründung („note“) zu ihrem Namen. '
                .'Nenne unter „Fehlende Lounges“ jede Lounge des Flughafens, die nicht eingetragen ist – je Lounge eine Zeile in der Form „Name; Standort; Zugang; Preis pro Person; Website“ (Unbekanntes leer lassen); fehlt keine, ist der Status "ok". '
                .'Preise als Zahl ohne Währung.',
            'hotels' => 'Die Felder gehören zu einzelnen Hotels („Hotel 1“, „Hotel 2“ …): prüfe jedes eingetragene Hotel Feld für Feld. '
                .'Gibt es ein eingetragenes Hotel nicht mehr, vermerke das in der Begründung („note“) zu seinem Namen. '
                .'Nenne unter „Fehlende Hotels“ wichtige Hotels in unmittelbarer Nähe des Flughafens, die nicht eingetragen sind – je Hotel eine Zeile in der Form „Name; Entfernung in km; Buchungs-URL; Hinweise“ (Unbekanntes leer lassen); fehlt keines, ist der Status "ok". '
                .'Entfernungen als Zahl in Kilometern.',
            'mobility' => 'Die Felder gehören zu den Mobilitätsangeboten am Flughafen ('.implode(', ', array_column(AirportExtras::mobility(), 'label')).'). '
                .'Prüfe je Angebot zuerst „Verfügbar“. Ist es verfügbar, gib zu seinen weiteren Feldern die zutreffende Angabe an – auch zu bisher leeren und auch dann, wenn es im Eintrag bisher nicht als verfügbar markiert ist; ist es nicht verfügbar, ist der Status seiner weiteren Felder "ok". '
                .'Nenne unter „Fehlende …“ jeden Eintrag, der in der jeweiligen Liste fehlt – je Eintrag eine Zeile, die Angaben mit Strichpunkt getrennt (Unbekanntes leer lassen): '
                .collect(AirportExtras::mobility())->filter(fn (array $definition) => isset($definition['list']))->map(fn (array $definition) => $definition['label'].' „'.implode('; ', $definition['list']['fields']).'“')->implode(', ')
                .'. Fehlt nichts, ist der Status "ok".',
            default => null,
        };
    }

    /**
     * Vorschlag der KI zu einem Feld einer Lounge bzw. eines Hotels uebernehmen
     * oder die fehlenden Eintraege anhaengen. null, wenn der Schluessel nicht hierher gehoert.
     */
    protected function aiApplyAirportExtras(string $key, string $value): ?bool
    {
        if (str_starts_with($key, 'mobility_')) {
            return $this->aiApplyMobility($key, $value);
        }

        // Fehlende Eintraege: je Zeile einer, die Angaben durch Strichpunkt getrennt.
        if (in_array($key, ['lounges_new', 'hotels_new'], true)) {
            $property = $key === 'lounges_new' ? 'lounges' : 'hotels';
            $known = array_map(fn (array $row) => mb_strtolower(trim((string) ($row['name'] ?? ''))), $this->{$property});
            $added = 0;

            foreach (preg_split('/\R/u', $value) ?: [] as $line) {
                $parts = array_map('trim', explode(';', preg_replace('/^\s*(?:[-*•]\s+|\d+[.)]\s+)/u', '', $line)));
                $name = $parts[0] ?? '';

                if ($name === '' || in_array(mb_strtolower($name), $known, true)) {
                    continue;
                }

                $this->{$property}[] = $property === 'lounges'
                    ? ['name' => $name, 'location' => $parts[1] ?? '', 'access' => $parts[2] ?? '', 'price_per_person' => $this->aiNumber($parts[3] ?? ''), 'url' => $parts[4] ?? ''] + AirportExtras::emptyLounge()
                    : ['name' => $name, 'distance_km' => $this->aiNumber($parts[1] ?? ''), 'booking_url' => $parts[2] ?? '', 'notes' => $parts[3] ?? ''] + AirportExtras::emptyHotel();
                $known[] = mb_strtolower($name);
                $added++;
            }

            return $added > 0;
        }

        if (! preg_match('/^(lounge|hotel)_(\d+)_([a-z_]+)$/', $key, $match)) {
            return null;
        }

        [$kind, $index, $field] = [$match[1], (int) $match[2], $match[3]];
        $property = $kind === 'lounge' ? 'lounges' : 'hotels';

        if (! isset($this->{$property}[$index]) || ! isset($this->airportExtrasRowFields()[$kind][$field])) {
            return false;
        }

        $this->{$property}[$index][$field] = match (true) {
            in_array($field, ['children_welcome', 'shuttle'], true) => $this->aiBool($value),
            in_array($field, ['price_per_person', 'distance_km'], true) => $this->aiNumber($value),
            default => $value,
        };

        return true;
    }

    /**
     * Mit dem Entfernen eines Eintrags verschieben sich die folgenden – die
     * Hinweise der Feldpruefung stuenden dann am falschen Eintrag.
     */
    protected function dismissAirportExtrasReview(string $section): void
    {
        if (property_exists($this, 'aiReview') && ($this->aiReview['section'] ?? null) === $section) {
            $this->aiReview = null;
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function airportExtrasRules(): array
    {
        return [
            'lounges.*.name' => ['nullable', 'string', 'max:255'],
            'lounges.*.url' => ['nullable', 'string', 'max:2000'],
            'lounges.*.price_per_person' => ['nullable', 'string', 'max:20'],
            'hotels.*.name' => ['nullable', 'string', 'max:255'],
            'hotels.*.distance_km' => ['nullable', 'string', 'max:20'],
            'hotels.*.booking_url' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function addLounge(): void
    {
        $this->lounges[] = AirportExtras::emptyLounge();
    }

    public function removeLounge(int $index): void
    {
        unset($this->lounges[$index]);
        $this->lounges = array_values($this->lounges);
        $this->dismissAirportExtrasReview('lounges');
    }

    public function addHotel(): void
    {
        $this->hotels[] = AirportExtras::emptyHotel();
    }

    public function removeHotel(int $index): void
    {
        unset($this->hotels[$index]);
        $this->hotels = array_values($this->hotels);
        $this->dismissAirportExtrasReview('hotels');
    }

    /**
     * Eine Zeile zu einem Mobilitaetsangebot mit Liste (Mietwagen-Anbieter, ÖPNV, Parken).
     */
    public function addMobilityRow(string $option): void
    {
        $list = AirportExtras::mobility()[$option]['list'] ?? null;

        if (! $list) {
            return;
        }

        $this->mobility[$option][$list['key']][] = array_fill_keys(array_keys($list['fields']), '');
    }

    public function removeMobilityRow(string $option, int $index): void
    {
        $list = AirportExtras::mobility()[$option]['list'] ?? null;

        if (! $list) {
            return;
        }

        unset($this->mobility[$option][$list['key']][$index]);
        $this->mobility[$option][$list['key']] = array_values($this->mobility[$option][$list['key']]);
        $this->dismissAirportExtrasReview('mobility');
    }
}
