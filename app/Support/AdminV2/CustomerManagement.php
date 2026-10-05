<?php

namespace App\Support\AdminV2;

/**
 * Die Bereiche der Kundenverwaltung im Admin-Bereich – eine Liste fuer
 * Navigation und Seitenkoepfe. "routes" nennt den Routen-Namen des Bereichs
 * (".index", ggf. ".create"), "legacy" seine Seite im bisherigen Admin.
 */
class CustomerManagement
{
    /**
     * @return array<string, array{label: string, icon: string, description: string, legacy: string, routes: string}>
     */
    public static function sections(): array
    {
        return [
            'customers' => [
                'label' => 'Kunden',
                'icon' => 'users',
                'description' => 'Kundenkonten mit Filialen, Zugängen und API-Tokens.',
                'legacy' => '/admin/customers',
                'routes' => 'adminv2.customer-management.customers',
            ],
            'feature-preauthorizations' => [
                'label' => 'Feature-Vormerkungen',
                'icon' => 'bookmark',
                'description' => 'Funktionen, die für Kunden schon vor der Registrierung vorgemerkt sind.',
                'legacy' => '/admin/customer-feature-preauthorizations',
                'routes' => 'adminv2.customer-management.feature-preauthorizations',
            ],
            'travel-alert-orders' => [
                'label' => 'TravelAlert Bestellungen',
                'icon' => 'shopping-cart',
                'description' => 'Bestellungen des TravelAlert.',
                'legacy' => '/admin/travel-alert-orders',
                'routes' => 'adminv2.customer-management.travel-alert-orders',
            ],
            'plugin-clients' => [
                'label' => 'Plugin-Kunden',
                'icon' => 'puzzle-piece',
                'description' => 'Kunden des Website-Plugins mit Schlüsseln, Domains und Nutzung.',
                'legacy' => '/admin/plugin-clients',
                'routes' => 'adminv2.customer-management.plugin-clients',
            ],
            'plugin-registrations' => [
                'label' => 'Ausstehende Registrierungen',
                'icon' => 'envelope',
                'description' => 'Plugin-Registrierungen, deren E-Mail-Adresse noch nicht bestätigt ist.',
                'legacy' => '/admin/plugin-email-verifications',
                'routes' => 'adminv2.customer-management.plugin-registrations',
            ],
            'api-clients' => [
                'label' => 'API-Kunden',
                'icon' => 'key',
                'description' => 'Kunden der Ereignis-API mit Zugangsschlüsseln.',
                'legacy' => '/admin/api-clients',
                'routes' => 'adminv2.customer-management.api-clients',
            ],
        ];
    }
}
