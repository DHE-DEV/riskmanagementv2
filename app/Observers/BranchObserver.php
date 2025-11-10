<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\BookingLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BranchObserver
{
    /**
     * Handle the Branch "created" event.
     */
    public function created(Branch $branch): void
    {
        $this->syncBookingLocation($branch);
    }

    /**
     * Handle the Branch "updated" event.
     */
    public function updated(Branch $branch): void
    {
        $this->syncBookingLocation($branch);
    }

    /**
     * Handle the Branch "deleted" event.
     */
    public function deleted(Branch $branch): void
    {
        // Delete associated booking location
        $branch->bookingLocation()?->delete();
    }

    /**
     * Handle the Branch "restored" event.
     */
    public function restored(Branch $branch): void
    {
        $this->syncBookingLocation($branch);
    }

    /**
     * Handle the Branch "force deleted" event.
     */
    public function forceDeleted(Branch $branch): void
    {
        // Delete associated booking location
        $branch->bookingLocation()?->delete();
    }

    /**
     * Synchronisiert die BookingLocation für diese Filiale
     */
    protected function syncBookingLocation(Branch $branch): void
    {
        // Lade Customer-Beziehung wenn nicht bereits geladen
        if (!$branch->relationLoaded('customer')) {
            $branch->load('customer');
        }

        $customer = $branch->customer;

        // Nur synchronisieren wenn Customer business ist und directory_listing_active hat
        if (!$customer || $customer->customer_type !== 'business' || !$customer->directory_listing_active) {
            // Wenn Bedingungen nicht erfüllt, existierende BookingLocation löschen
            $branch->bookingLocation()?->delete();
            return;
        }

        // Prüfen ob alle notwendigen Adress-Daten vorhanden sind
        if (empty($branch->name) || empty($branch->postal_code) || empty($branch->city)) {
            Log::warning("Branch {$branch->id}: Adresse unvollständig für BookingLocation");
            // Existierende BookingLocation löschen wenn Daten unvollständig
            $branch->bookingLocation()?->delete();
            return;
        }

        // Geocoding der Adresse (nur wenn nicht bereits vorhanden)
        $coordinates = null;
        if ($branch->latitude && $branch->longitude) {
            $coordinates = [
                'lat' => $branch->latitude,
                'lng' => $branch->longitude,
            ];
        } else {
            $coordinates = $this->geocodeAddress($branch);
        }

        // BookingLocation erstellen oder aktualisieren
        $bookingLocation = $branch->bookingLocation;

        $locationData = [
            'customer_id' => $customer->id,
            'type' => 'stationary',
            'name' => $branch->name,
            'description' => $this->getBranchDescription($branch, $customer),
            'address' => trim(($branch->street ?? '') . ' ' . ($branch->house_number ?? '')),
            'postal_code' => $branch->postal_code,
            'city' => $branch->city,
            'latitude' => $coordinates['lat'] ?? null,
            'longitude' => $coordinates['lng'] ?? null,
            'phone' => null, // Kann später erweitert werden
            'email' => null, // Kann später erweitert werden
        ];

        if ($bookingLocation) {
            $bookingLocation->update($locationData);
        } else {
            $branch->bookingLocation()->create($locationData);
        }

        Log::info("BookingLocation für Branch {$branch->id} synchronisiert");
    }

    /**
     * Geocoding der Filialadresse
     */
    protected function geocodeAddress(Branch $branch): ?array
    {
        try {
            $address = sprintf(
                '%s %s, %s %s, %s',
                $branch->street ?? '',
                $branch->house_number ?? '',
                $branch->postal_code ?? '',
                $branch->city ?? '',
                $branch->country ?? 'Deutschland'
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
                'postalcode' => $branch->postal_code,
                'city' => $branch->city,
                'country' => $branch->country ?? 'Deutschland',
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
            Log::error("Geocoding fehlgeschlagen für Branch {$branch->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generiert eine Beschreibung für die Filiale
     */
    protected function getBranchDescription(Branch $branch, $customer): ?string
    {
        $parts = [];

        // Haupttyp des Kunden
        if (!empty($customer->business_type)) {
            $labels = [
                'travel_agency' => 'Reisebüro',
                'organizer' => 'Veranstalter',
                'online_provider' => 'Online Anbieter',
                'mobile_travel_consultant' => 'Mobiler Reiseberater'
            ];

            $types = array_map(function($type) use ($labels) {
                return $labels[$type] ?? $type;
            }, $customer->business_type);

            $parts[] = implode(', ', $types);
        }

        // Filiale-Kennzeichnung
        if ($branch->is_headquarters) {
            $parts[] = 'Zentrale';
        } else {
            $parts[] = 'Filiale';
        }

        return !empty($parts) ? implode(' - ', $parts) : null;
    }
}
