<?php

namespace App\Support\AdminV2;

/**
 * Die Stammdaten-Bereiche des Admin-Bereichs – eine Liste fuer Navigation,
 * Routen und Seiten. Solange ein Bereich noch nicht umgezogen ist, verweist
 * "legacy" auf seine Seite im bisherigen Admin.
 */
class MasterData
{
    /**
     * @return array<string, array{label: string, icon: string, description: string, legacy: ?string}>
     */
    public static function sections(): array
    {
        return [
            'continents' => [
                'label' => 'Kontinente',
                'icon' => 'globe-europe-africa',
                'description' => 'Die Kontinente, denen Länder zugeordnet sind.',
                'legacy' => '/admin/continents',
            ],
            'countries' => [
                'label' => 'Länder',
                'icon' => 'flag',
                'description' => 'Länder mit ISO-Codes, Übersetzungen und Koordinaten.',
                'legacy' => '/admin/countries',
            ],
            'regions' => [
                'label' => 'Regionen',
                'icon' => 'map',
                'description' => 'Regionen und Bundesstaaten der Länder.',
                'legacy' => '/admin/regions',
            ],
            'cities' => [
                'label' => 'Städte',
                'icon' => 'building-office-2',
                'description' => 'Städte mit Zuordnung zu Land und Region.',
                'legacy' => '/admin/cities',
            ],
            'airports' => [
                'label' => 'Flughäfen',
                'icon' => 'paper-airplane',
                'description' => 'Flughäfen mit Lage und Zuordnung zu Stadt und Land.',
                'legacy' => '/admin/airports',
            ],
            'airport-codes' => [
                'label' => 'Flughafen-Codes',
                'icon' => 'hashtag',
                'description' => 'IATA- und ICAO-Codes der Flughäfen.',
                'legacy' => '/admin/airport-codes',
            ],
            'airlines' => [
                'label' => 'Airlines',
                'icon' => 'ticket',
                'description' => 'Fluggesellschaften mit ihren Codes.',
                'legacy' => '/admin/airlines',
            ],
            'country-information' => [
                'label' => 'Länderinformationen',
                'icon' => 'document-text',
                'description' => 'Informationen zu den einzelnen Ländern.',
                'legacy' => null,
            ],
        ];
    }
}
