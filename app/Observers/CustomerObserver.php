<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\BookingLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomerObserver
{
    /**
     * Handle the Customer "updated" event.
     */
    public function updated(Customer $customer): void
    {
        // Prüfen ob directory_listing_active oder company_address Felder geändert wurden
        if ($customer->wasChanged(['directory_listing_active', 'company_name', 'company_street', 'company_house_number', 'company_postal_code', 'company_city', 'company_country'])) {
            $this->syncBookingLocation($customer);

            // Wenn directory_listing_active geändert wurde, alle Filialen synchronisieren
            if ($customer->wasChanged('directory_listing_active')) {
                $this->syncAllBranches($customer);
            }
        }
    }

    /**
     * Synchronisiert die BookingLocation basierend auf der Firmenadresse
     */
    protected function syncBookingLocation(Customer $customer): void
    {
        // Nur für Firmenkunden
        if ($customer->customer_type !== 'business') {
            return;
        }

        // Wenn directory_listing_active deaktiviert ist, BookingLocation löschen
        if (!$customer->directory_listing_active) {
            $customer->bookingLocations()->delete();
            return;
        }

        // Prüfen ob alle notwendigen Firmenadress-Daten vorhanden sind
        if (empty($customer->company_name) || empty($customer->company_postal_code) || empty($customer->company_city)) {
            Log::warning("Customer {$customer->id}: Firmenadresse unvollständig für BookingLocation");
            return;
        }

        // Geocoding der Adresse
        $coordinates = $this->geocodeAddress($customer);

        // BookingLocation erstellen oder aktualisieren
        $bookingLocation = $customer->bookingLocations()->first();

        $locationData = [
            'type' => 'stationary',
            'name' => $customer->company_name,
            'description' => $this->getBusinessTypeDescription($customer),
            'address' => trim(($customer->company_street ?? '') . ' ' . ($customer->company_house_number ?? '')),
            'postal_code' => $customer->company_postal_code,
            'city' => $customer->company_city,
            'latitude' => $coordinates['lat'] ?? null,
            'longitude' => $coordinates['lng'] ?? null,
            'phone' => null, // Kann später erweitert werden
            'email' => $customer->email,
        ];

        if ($bookingLocation) {
            $bookingLocation->update($locationData);
        } else {
            $customer->bookingLocations()->create($locationData);
        }

        Log::info("BookingLocation für Customer {$customer->id} synchronisiert");
    }

    /**
     * Geocoding der Firmenadresse
     */
    protected function geocodeAddress(Customer $customer): ?array
    {
        try {
            $address = sprintf(
                '%s %s, %s %s, %s',
                $customer->company_street ?? '',
                $customer->company_house_number ?? '',
                $customer->company_postal_code ?? '',
                $customer->company_city ?? '',
                $customer->company_country ?? 'Deutschland'
            );

            $headers = [
                'User-Agent' => 'Global Travel Monitor/1.0 (Laravel)',
            ];

            $response = Http::timeout(10)->withHeaders($headers)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 1,
            ]);

            if ($response->successful() && count($response->json()) > 0) {
                $result = $response->json()[0];
                return [
                    'lat' => (float) $result['lat'],
                    'lng' => (float) $result['lon'],
                ];
            }

            // Fallback: Nur PLZ und Stadt
            $response = Http::timeout(10)->withHeaders($headers)->get('https://nominatim.openstreetmap.org/search', [
                'postalcode' => $customer->company_postal_code,
                'city' => $customer->company_city,
                'country' => $customer->company_country ?? 'Deutschland',
                'format' => 'json',
                'limit' => 1,
            ]);

            if ($response->successful() && count($response->json()) > 0) {
                $result = $response->json()[0];
                return [
                    'lat' => (float) $result['lat'],
                    'lng' => (float) $result['lon'],
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Geocoding fehlgeschlagen für Customer {$customer->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generiert eine Beschreibung basierend auf business_type
     */
    protected function getBusinessTypeDescription(Customer $customer): ?string
    {
        if (empty($customer->business_type)) {
            return null;
        }

        $labels = [
            'travel_agency' => 'Reisebüro',
            'organizer' => 'Veranstalter',
            'online_provider' => 'Online Anbieter',
            'mobile_travel_consultant' => 'Mobiler Reiseberater'
        ];

        $types = array_map(function($type) use ($labels) {
            return $labels[$type] ?? $type;
        }, $customer->business_type);

        return implode(', ', $types);
    }

    /**
     * Synchronisiert alle Filialen des Kunden
     * Wird aufgerufen wenn directory_listing_active geändert wird
     */
    protected function syncAllBranches(Customer $customer): void
    {
        // Hole alle Filialen des Kunden
        $branches = $customer->branches()->get();

        foreach ($branches as $branch) {
            // Triggere das updated Event für jede Filiale
            // Dies wird den BranchObserver aufrufen
            $branch->touch();
        }

        Log::info("Alle Filialen für Customer {$customer->id} synchronisiert (directory_listing_active: " . ($customer->directory_listing_active ? 'aktiv' : 'inaktiv') . ")");
    }
}
