<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lookup / dropdown data for the Travel Requirements Service v2.
 *
 * Public lists (countries, languages, nationalities) are fetched from the
 * public pds-api endpoints and cached. Tour operators require an authenticated
 * customer token and are therefore loaded through {@see PdsApiService}.
 *
 * Nachschlagedaten für das Travel Requirements Service v2. Öffentliche Listen
 * werden gecacht; Veranstalter benötigen ein Customer-Token.
 */
class TrsV2DataService
{
    /** Cache lifetime for public lookup lists (24h). */
    protected const TTL = 86400;

    /** Bump to invalidate all cached lists at once. */
    protected const VERSION = 'v1';

    protected string $baseUrl;

    protected int $timeout;

    public function __construct(protected PdsApiService $pdsApi)
    {
        $this->baseUrl = rtrim(config('services.pds_api.base_url', 'https://api.passolution.eu/api/v2'), '/');
        $this->timeout = (int) config('services.pds_api.timeout', 30);
    }

    /**
     * Countries (destinations). Each entry: code + localized names.
     *
     * @return array<int, array{code:string, name_de:string, name_en:string, name_nl:string}>
     */
    public function countries(): array
    {
        return Cache::remember(self::VERSION.':trs:countries', self::TTL, function () {
            $rows = $this->fetchAll('countries', ['limit' => 1000, 'sort' => 'name']);

            return collect($rows)
                ->filter(fn ($c) => ($c['active'] ?? 1) == 1 && ! empty($c['code']))
                ->map(fn ($c) => [
                    'code' => $c['code'],
                    'name_de' => $c['name'] ?? $c['code'],          // countries: `name` is German
                    'name_en' => $c['name_en'] ?? ($c['name'] ?? $c['code']),
                    'name_nl' => $c['name_nl'] ?: ($c['name_en'] ?? $c['name'] ?? $c['code']),
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Nationalities. Each entry: code + localized names.
     *
     * @return array<int, array{code:string, name_de:string, name_en:string, name_nl:string}>
     */
    public function nationalities(): array
    {
        return Cache::remember(self::VERSION.':trs:nationalities', self::TTL, function () {
            $rows = $this->fetchAll('nationalities', ['limit' => 1000, 'sort' => 'name']);

            return collect($rows)
                ->filter(fn ($n) => ($n['active'] ?? 1) == 1 && ! empty($n['code']))
                ->map(fn ($n) => [
                    'code' => $n['code'],
                    'name_de' => $n['name_de'] ?: ($n['name'] ?? $n['code']),
                    'name_en' => $n['name_en'] ?: ($n['name'] ?? $n['code']),   // nationalities: `name` is English
                    'name_nl' => $n['name_nl'] ?: ($n['name_en'] ?? $n['name'] ?? $n['code']),
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Output languages. Each entry: code + display name.
     *
     * @return array<int, array{code:string, name:string}>
     */
    public function languages(): array
    {
        return Cache::remember(self::VERSION.':trs:languages', self::TTL, function () {
            $rows = $this->fetchAll('languages', ['limit' => 200]);

            return collect($rows)
                ->filter(fn ($l) => ($l['active'] ?? 1) == 1 && ! empty($l['code']))
                ->map(fn ($l) => [
                    'code' => $l['code'],
                    'name' => $l['name'] ?? strtoupper($l['code']),
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Tour operators for the authenticated customer (requires a valid token).
     *
     * @return array<int, array{code:string, name:string}>
     */
    public function tourOperators(?Customer $customer): array
    {
        if (! $customer || ! $this->pdsApi->hasValidToken($customer)) {
            return [];
        }

        $response = $this->pdsApi->get($customer, 'tour-operators');
        if (! $response || ! $response->successful()) {
            return [];
        }

        $data = $response->json('tour_operators')
            ?? $response->json('result.data')
            ?? $response->json('result')
            ?? [];

        return collect($data)
            ->filter(fn ($o) => ! empty($o['code'] ?? $o['name'] ?? null))
            ->map(fn ($o) => [
                'code' => (string) ($o['code'] ?? $o['name']),
                'name' => (string) ($o['name'] ?? $o['code']),
            ])
            ->values()
            ->all();
    }

    /**
     * Cruise lines that currently have future cruises (pds-api __internal).
     *
     * @return array<int, array{id:int, name:string}>
     */
    public function cruiseLines(?Customer $customer): array
    {
        return $this->cruiseLookup($customer, '__internal/cruise/lines', []);
    }

    /**
     * Ships of a cruise line (optionally narrowed to a cruise date range).
     *
     * @return array<int, array{id:int, name:string}>
     */
    public function cruiseShips(?Customer $customer, int $lineId, ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->cruiseLookup($customer, '__internal/cruise/ships', array_filter([
            'line_id' => $lineId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * Routes of a cruise ship (optionally narrowed to a cruise date range).
     *
     * @return array<int, array{id:int, name:string}>
     */
    public function cruiseRoutes(?Customer $customer, int $shipId, ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->cruiseLookup($customer, '__internal/cruise/routes', array_filter([
            'ship_id' => $shipId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * Concrete future cruises of a route (dates + cruise-compass ids).
     *
     * @return array<int, array{date:string, duration_in_days:int, cruise_compass_cruise_id:?string, cruise_compass_route_id:?int}>
     */
    public function cruiseCruises(?Customer $customer, int $routeId): array
    {
        return $this->cruiseLookup($customer, '__internal/cruise/cruises', ['route_id' => $routeId]);
    }

    /**
     * POST a cruise lookup to a pds-api __internal endpoint and return its data list.
     *
     * @param  array<string, mixed>  $body
     * @return array<int, array<string, mixed>>
     */
    protected function cruiseLookup(?Customer $customer, string $endpoint, array $body): array
    {
        if (! $customer || ! $this->pdsApi->hasValidToken($customer)) {
            return [];
        }

        $response = $this->pdsApi->post($customer, $endpoint, $body);
        if (! $response || ! $response->successful()) {
            return [];
        }

        $data = $response->json('data')
            ?? $response->json('result.data')
            ?? $response->json('result')
            ?? [];

        return is_array($data) ? array_values($data) : [];
    }

    /**
     * Fetch every page of a paginated public endpoint and flatten `result.data`.
     */
    protected function fetchAll(string $endpoint, array $query = []): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout($this->timeout)
                ->get($this->baseUrl.'/'.ltrim($endpoint, '/'), $query);

            if (! $response->successful()) {
                Log::warning('TrsV2DataService: lookup request failed', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $result = $response->json('result');
            $rows = $result['data'] ?? [];
            $lastPage = (int) ($result['last_page'] ?? 1);

            // Walk remaining pages if the list was larger than one page.
            for ($page = 2; $page <= $lastPage; $page++) {
                $next = Http::acceptJson()
                    ->timeout($this->timeout)
                    ->get($this->baseUrl.'/'.ltrim($endpoint, '/'), array_merge($query, ['page' => $page]));
                if ($next->successful()) {
                    $rows = array_merge($rows, $next->json('result.data') ?? []);
                }
            }

            return $rows;
        } catch (\Throwable $e) {
            Log::error('TrsV2DataService: lookup exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
