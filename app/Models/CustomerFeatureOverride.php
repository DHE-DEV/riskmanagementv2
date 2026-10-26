<?php

namespace App\Models;

use App\Observers\CustomerFeatureOverrideObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([CustomerFeatureOverrideObserver::class])]
class CustomerFeatureOverride extends Model
{
    protected $fillable = [
        'customer_id',
        'navigation_events_enabled',
        'navigation_entry_conditions_enabled',
        'navigation_booking_enabled',
        'navigation_airports_enabled',
        'navigation_branches_enabled',
        'navigation_my_travelers_enabled',
        'navigation_risk_overview_enabled',
        'navigation_cruise_enabled',
        'navigation_business_visa_enabled',
        'navigation_center_map_enabled',
        'navigation_visumpoint_enabled',
        'navigation_customer_events_enabled',
        'navigation_doctors_network_enabled',
        'settings_master_data_enabled',
        'settings_organization_enabled',
        'settings_users_enabled',
    ];

    /**
     * Einstellungsbereich (customer_settings.sections) je Feature-Key – der
     * globale Standard kommt von dort statt aus app.*.
     */
    public const SETTINGS_SECTIONS = [
        'settings_master_data_enabled' => 'master-data',
        'settings_organization_enabled' => 'organization',
        'settings_users_enabled' => 'users',
    ];

    /**
     * Diese Keys uebernehmen Unteraccounts (customers.assign_to) von ihrer
     * Zentrale, solange bei ihnen selbst nichts gesetzt ist.
     */
    public const INHERITED_BY_SUB_ACCOUNTS = [
        'settings_master_data_enabled',
        'settings_organization_enabled',
        'settings_users_enabled',
    ];

    protected $casts = [
        'navigation_events_enabled' => 'boolean',
        'navigation_entry_conditions_enabled' => 'boolean',
        'navigation_booking_enabled' => 'boolean',
        'navigation_airports_enabled' => 'boolean',
        'navigation_branches_enabled' => 'boolean',
        'navigation_my_travelers_enabled' => 'boolean',
        'navigation_risk_overview_enabled' => 'boolean',
        'navigation_cruise_enabled' => 'boolean',
        'navigation_business_visa_enabled' => 'boolean',
        'navigation_center_map_enabled' => 'boolean',
        'navigation_visumpoint_enabled' => 'boolean',
        'navigation_customer_events_enabled' => 'boolean',
        'navigation_doctors_network_enabled' => 'boolean',
        'settings_master_data_enabled' => 'boolean',
        'settings_organization_enabled' => 'boolean',
        'settings_users_enabled' => 'boolean',
    ];

    /**
     * Get the customer that owns this override.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the list of all feature keys.
     */
    public static function getFeatureKeys(): array
    {
        return [
            'navigation_events_enabled',
            'navigation_entry_conditions_enabled',
            'navigation_booking_enabled',
            'navigation_airports_enabled',
            'navigation_branches_enabled',
            'navigation_my_travelers_enabled',
            'navigation_risk_overview_enabled',
            'navigation_cruise_enabled',
            'navigation_business_visa_enabled',
            'navigation_center_map_enabled',
            'navigation_visumpoint_enabled',
            'navigation_customer_events_enabled',
            'navigation_doctors_network_enabled',
            'settings_master_data_enabled',
            'settings_organization_enabled',
            'settings_users_enabled',
        ];
    }

    /**
     * Ausfuehrliche Erklaerung je Feature fuer den Admin (Info-Symbol):
     * was der Schalter in der Travel Information Platform bewirkt.
     *
     * @return array<string, string>
     */
    public static function getFeatureDescriptions(): array
    {
        return [
            'navigation_events_enabled' => 'Menüpunkt „Global Travel Monitor“: die Karte mit den aktuellen Ereignissen (Unruhen, Naturereignisse, Gesundheit usw.) weltweit. Ausgeschaltet verschwindet der Menüpunkt.',
            'navigation_entry_conditions_enabled' => 'Menüpunkt „Einreisebestimmungen“: Einreise-, Visa- und Gesundheitsbestimmungen je Reiseziel und Nationalität. Ausgeschaltet verschwindet der Menüpunkt, auch in der mobilen Ansicht.',
            'navigation_booking_enabled' => 'Menüpunkt „Buchungsmöglichkeit“ (/booking): das Adressverzeichnis mit Reisebüros und Standorten, bei denen gebucht werden kann. Ausgeschaltet ist die Seite nicht erreichbar, auch die Verweise aus den Einreisebestimmungen entfallen.',
            'navigation_airports_enabled' => 'Menüpunkt „Flughäfen“ mit Flughafeninformationen. Ausgeschaltet ist die Seite nicht erreichbar.',
            'navigation_branches_enabled' => 'Menüpunkt „Filialen & Standorte“. Er erscheint nur, wenn zusätzlich „Filialen-Verwaltung aktiv“ eingeschaltet ist. Die Filialen im Dashboard und in den Einstellungen hängen allein an der Filialen-Verwaltung.',
            'navigation_my_travelers_enabled' => 'Menüpunkt „Meine Reisenden“: die Reisenden und Reisen des Kunden mit den für sie relevanten Ereignissen. Ausgeschaltet verschwindet der Menüpunkt.',
            'navigation_risk_overview_enabled' => 'Travel Alert: die Risikoübersicht zu den Reisen des Kunden samt Benachrichtigungseinstellungen und eingebetteter Ansicht. Ausgeschaltet sind diese Seiten und Schnittstellen gesperrt. Wird bei einer Travel-Alert-Bestellung automatisch freigeschaltet.',
            'navigation_cruise_enabled' => 'Menüpunkt „Kreuzfahrt“: Routen, Häfen, Einreisebestimmungen und Sicherheitshinweise für Kreuzfahrten. Ausgeschaltet verschwindet der Menüpunkt, auch in der mobilen Ansicht und bei den Einreisebestimmungen.',
            'navigation_business_visa_enabled' => 'Menüpunkt „Business Visum“ samt zugehörigem Einstellungsbereich. Ausgeschaltet sind Seite und Einstellungen nicht erreichbar.',
            'navigation_center_map_enabled' => 'Schaltfläche „Karte zentrieren“ in der Navigation des Dashboards. Ausgeschaltet fehlt nur diese Schaltfläche.',
            'navigation_visumpoint_enabled' => 'Menüpunkt „Visum Check (VisumPoint)“: Visa-Prüfung über VisumPoint. Ausgeschaltet ist die Seite nicht erreichbar.',
            'navigation_customer_events_enabled' => 'Menüpunkt „Meine Ereignisse“: eigene Ereignisse, die der Kunde selbst anlegt und auf seiner Karte zeigt. Ausgeschaltet verschwindet der Menüpunkt.',
            'navigation_doctors_network_enabled' => 'Menüpunkt „Doctors Network“: Ärzte und medizinische Einrichtungen am Reiseziel. Ausgeschaltet ist die Seite nicht erreichbar.',
            'settings_master_data_enabled' => 'Einstellungsbereich „Stammdaten“ unter Organisation, in dem der Kunde seine Firmendaten pflegt.',
            'settings_organization_enabled' => 'Einstellungsbereich „Organisationsstruktur“ unter Organisation: Adressen, Struktur, Abteilungen und – bei einer Zentrale – die zugeordneten Agenturen.',
            'settings_users_enabled' => 'Einstellungsbereich „Benutzerverwaltung“ unter Organisation, in dem der Kunde weitere Benutzer (Mitarbeiter) anlegt und Gruppen zuordnet.',
        ];
    }

    /**
     * Get feature labels for display.
     */
    public static function getFeatureLabels(): array
    {
        return [
            'navigation_events_enabled' => 'Ereignisse',
            'navigation_entry_conditions_enabled' => 'Einreisebestimmungen',
            'navigation_booking_enabled' => 'Buchung',
            'navigation_airports_enabled' => 'Flughäfen',
            'navigation_branches_enabled' => 'Filialen',
            'navigation_my_travelers_enabled' => 'Meine Reisenden',
            'navigation_risk_overview_enabled' => 'Travel Alert',
            'navigation_cruise_enabled' => 'Kreuzfahrten',
            'navigation_business_visa_enabled' => 'Business Visum',
            'navigation_center_map_enabled' => 'Karte zentrieren',
            'navigation_visumpoint_enabled' => 'VisumPoint',
            'navigation_customer_events_enabled' => 'Eigene Ereignisse',
            'navigation_doctors_network_enabled' => 'Doctors Network',
            'settings_master_data_enabled' => 'Einstellungen: Stammdaten',
            'settings_organization_enabled' => 'Einstellungen: Organisationsstruktur',
            'settings_users_enabled' => 'Einstellungen: Benutzerverwaltung',
        ];
    }
}
