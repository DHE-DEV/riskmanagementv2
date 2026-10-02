<?php

namespace App\Support\AdminV2;

/**
 * Lounges, Mobilitaetsangebote und Hotels eines Flughafens – gleiche
 * JSON-Struktur in "airports" und "airport_codes_1".
 */
class AirportExtras
{
    /** @return array{name: string, location: string, access: string, children_welcome: bool, price_per_person: string, url: string} */
    public static function emptyLounge(): array
    {
        return ['name' => '', 'location' => '', 'access' => '', 'children_welcome' => false, 'price_per_person' => '', 'url' => ''];
    }

    /** @return array{name: string, distance_km: string, shuttle: bool, booking_url: string, notes: string} */
    public static function emptyHotel(): array
    {
        return ['name' => '', 'distance_km' => '', 'shuttle' => false, 'booking_url' => '', 'notes' => ''];
    }

    /**
     * Die fuenf Mobilitaetsangebote mit ihren Feldern.
     *
     * @return array<string, array{label: string, list?: array{key: string, label: string, fields: array<string, string>}, fields?: array<string, array{label: string, type: string}>}>
     */
    public static function mobility(): array
    {
        return [
            'car_rental' => [
                'label' => 'Mietwagen',
                'list' => ['key' => 'providers', 'label' => 'Anbieter', 'fields' => ['name' => 'Anbieter', 'url' => 'Website/Buchungs-URL']],
            ],
            'public_transport' => [
                'label' => 'Öffentlicher Nahverkehr (ÖPNV)',
                'list' => ['key' => 'types', 'label' => 'Verkehrsmittel', 'fields' => ['name' => 'Verkehrsmittel', 'url' => 'Info-URL']],
            ],
            'airport_shuttle' => [
                'label' => 'Airport Shuttle',
                'fields' => ['info' => ['label' => 'Informationen', 'type' => 'textarea'], 'url' => ['label' => 'Info-URL', 'type' => 'url']],
            ],
            'taxi' => [
                'label' => 'Taxi',
                'fields' => ['info' => ['label' => 'Informationen', 'type' => 'textarea'], 'approx_cost' => ['label' => 'Ungefähre Kosten', 'type' => 'text']],
            ],
            'parking' => [
                'label' => 'Parkhäuser',
                'list' => ['key' => 'options', 'label' => 'Parkmöglichkeiten', 'fields' => ['name' => 'Name', 'distance' => 'Entfernung', 'price_info' => 'Preisinformation', 'url' => 'Website/Buchungs-URL']],
            ],
        ];
    }

    /**
     * Gespeicherte Lounges -> Formularzeilen.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function loungesToForm(?array $lounges): array
    {
        return array_values(array_map(fn (array $lounge) => [
            'name' => (string) ($lounge['name'] ?? ''),
            'location' => (string) ($lounge['location'] ?? ''),
            'access' => (string) ($lounge['access'] ?? ''),
            'children_welcome' => (bool) ($lounge['children_welcome'] ?? false),
            'price_per_person' => isset($lounge['price_per_person']) && $lounge['price_per_person'] !== '' ? (string) $lounge['price_per_person'] : '',
            'url' => (string) ($lounge['url'] ?? ''),
        ], array_filter($lounges ?? [], 'is_array')));
    }

    /**
     * Formularzeilen -> zu speichernde Lounges; Zeilen ohne Namen fallen weg.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public static function loungesFromForm(array $rows): array
    {
        $lounges = [];

        foreach ($rows as $row) {
            if (trim((string) ($row['name'] ?? '')) === '') {
                continue;
            }

            $price = str_replace(',', '.', trim((string) ($row['price_per_person'] ?? '')));

            $lounges[] = [
                'name' => trim((string) $row['name']),
                'location' => self::text($row['location'] ?? null),
                'access' => self::text($row['access'] ?? null),
                'children_welcome' => (bool) ($row['children_welcome'] ?? false),
                'price_per_person' => is_numeric($price) ? (float) $price : null,
                'url' => self::text($row['url'] ?? null),
            ];
        }

        return $lounges;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function hotelsToForm(?array $hotels): array
    {
        return array_values(array_map(fn (array $hotel) => [
            'name' => (string) ($hotel['name'] ?? ''),
            'distance_km' => isset($hotel['distance_km']) && $hotel['distance_km'] !== '' ? (string) $hotel['distance_km'] : '',
            'shuttle' => (bool) ($hotel['shuttle'] ?? false),
            'booking_url' => (string) ($hotel['booking_url'] ?? ''),
            'notes' => (string) ($hotel['notes'] ?? ''),
        ], array_filter($hotels ?? [], 'is_array')));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public static function hotelsFromForm(array $rows): array
    {
        $hotels = [];

        foreach ($rows as $row) {
            if (trim((string) ($row['name'] ?? '')) === '') {
                continue;
            }

            $distance = str_replace(',', '.', trim((string) ($row['distance_km'] ?? '')));

            $hotels[] = [
                'name' => trim((string) $row['name']),
                'distance_km' => is_numeric($distance) ? (float) $distance : null,
                'shuttle' => (bool) ($row['shuttle'] ?? false),
                'booking_url' => self::text($row['booking_url'] ?? null),
                'notes' => self::text($row['notes'] ?? null),
            ];
        }

        return $hotels;
    }

    /**
     * Gespeicherte Mobilitaetsangebote -> Formularwerte (alle Angebote vorhanden).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function mobilityToForm(?array $options): array
    {
        $form = [];

        foreach (self::mobility() as $key => $definition) {
            $stored = is_array($options[$key] ?? null) ? $options[$key] : [];
            $form[$key] = ['available' => (bool) ($stored['available'] ?? false)];

            if (isset($definition['list'])) {
                $rows = [];
                foreach (array_filter($stored[$definition['list']['key']] ?? [], 'is_array') as $item) {
                    $rows[] = array_map(fn ($field) => (string) ($item[$field] ?? ''), array_combine(array_keys($definition['list']['fields']), array_keys($definition['list']['fields'])));
                }
                $form[$key][$definition['list']['key']] = $rows;
            }

            foreach ($definition['fields'] ?? [] as $field => $meta) {
                $form[$key][$field] = (string) ($stored[$field] ?? '');
            }
        }

        return $form;
    }

    /**
     * Formularwerte -> zu speichernde Mobilitaetsangebote.
     *
     * @param  array<string, array<string, mixed>>  $form
     * @return array<string, array<string, mixed>>
     */
    public static function mobilityFromForm(array $form): array
    {
        $options = [];

        foreach (self::mobility() as $key => $definition) {
            $values = $form[$key] ?? [];
            $entry = ['available' => (bool) ($values['available'] ?? false)];

            if (isset($definition['list'])) {
                $rows = [];
                foreach ($values[$definition['list']['key']] ?? [] as $row) {
                    $row = array_map(fn ($value) => self::text($value), is_array($row) ? $row : []);
                    // Zeilen ohne Namen fallen weg.
                    if (($row['name'] ?? null) === null) {
                        continue;
                    }
                    $rows[] = array_intersect_key($row, $definition['list']['fields']);
                }
                $entry[$definition['list']['key']] = $rows;
            }

            foreach ($definition['fields'] ?? [] as $field => $meta) {
                $entry[$field] = self::text($values[$field] ?? null);
            }

            $options[$key] = $entry;
        }

        return $options;
    }

    protected static function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
