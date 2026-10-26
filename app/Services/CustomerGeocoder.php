<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Koordinaten der Firmenadresse eines Kunden ueber OpenStreetMap (Nominatim).
 * Haelt die Nutzungsregeln ein: hoechstens eine Anfrage je
 * services.nominatim.throttle Sekunden, eigener User-Agent.
 */
class CustomerGeocoder
{
    protected float $lastRequest = 0.0;

    public function enabled(): bool
    {
        return (bool) config('services.nominatim.enabled', true);
    }

    /**
     * Ermittelt und speichert die Koordinaten; company_geocoded_at wird auch
     * ohne Treffer gesetzt. true, wenn Koordinaten gefunden wurden.
     */
    public function geocode(Customer $customer): bool
    {
        $coordinates = $this->hasAddress($customer)
            ? ($this->search(array_filter([
                'street' => trim($customer->company_street.' '.$customer->company_house_number) ?: null,
                'postalcode' => $customer->company_postal_code,
                'city' => $customer->company_city,
                'countrycodes' => $this->countryCode($customer),
            ])) ?? $this->search(array_filter([
                // Fallback: nur PLZ und Ort
                'postalcode' => $customer->company_postal_code,
                'city' => $customer->company_city,
                'countrycodes' => $this->countryCode($customer),
            ])))
            : null;

        $customer->forceFill([
            'company_latitude' => $coordinates['lat'] ?? null,
            'company_longitude' => $coordinates['lng'] ?? null,
            'company_geocoded_at' => now(),
        ])->saveQuietly();

        return $coordinates !== null;
    }

    public function hasAddress(Customer $customer): bool
    {
        return filled($customer->company_city) || filled($customer->company_postal_code);
    }

    protected function countryCode(Customer $customer): ?string
    {
        $country = strtolower(trim((string) $customer->company_country));

        return strlen($country) === 2 ? $country : null;
    }

    /**
     * @param  array<string, string>  $query
     * @return array{lat: float, lng: float}|null
     */
    protected function search(array $query): ?array
    {
        $wait = (float) config('services.nominatim.throttle', 1.1) - (microtime(true) - $this->lastRequest);

        if ($wait > 0) {
            usleep((int) ($wait * 1_000_000));
        }

        try {
            $response = Http::withHeaders(['User-Agent' => (string) config('services.nominatim.user_agent', 'Passolution Travel Information Platform (platform.passolution.de)')])
                ->timeout(10)
                ->get(rtrim((string) config('services.nominatim.url', 'https://nominatim.openstreetmap.org'), '/').'/search', [...$query, 'format' => 'json', 'limit' => 1]);
        } catch (Throwable $e) {
            Log::warning('Nominatim nicht erreichbar: '.$e->getMessage());

            return null;
        } finally {
            $this->lastRequest = microtime(true);
        }

        $hit = $response->successful() ? ($response->json()[0] ?? null) : null;

        return isset($hit['lat'], $hit['lon']) ? ['lat' => (float) $hit['lat'], 'lng' => (float) $hit['lon']] : null;
    }
}
