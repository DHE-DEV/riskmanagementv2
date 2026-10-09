<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Wechselkurse fuer Apps und Partner: holt die Kurse einmal pro Stunde beim
 * Anbieter (Standard: offener Endpunkt von ExchangeRate-API, Basis EUR) und
 * haelt sie im Cache. Faellt der Anbieter aus, bleiben die letzten Kurse bis zu
 * einem Tag gueltig.
 */
class ExchangeRateService
{
    public const BASE = 'EUR';

    /**
     * Kurse mit Basis EUR: ['base' => 'EUR', 'rates' => ['USD' => 1.08, …], 'updated_at' => …, 'source' => …].
     *
     * @return array{base: string, rates: array<string, float>, updated_at: string|null, next_update_at: string|null, source: string}
     */
    public function latest(): array
    {
        $cached = Cache::get('exchange-rates:'.self::BASE);

        if (is_array($cached) && $cached['fetched_at'] > now()->subHour()->timestamp) {
            return $cached['data'];
        }

        try {
            $data = $this->fetch();
            Cache::put('exchange-rates:'.self::BASE, ['fetched_at' => now()->timestamp, 'data' => $data], now()->addDay());

            return $data;
        } catch (RuntimeException $exception) {
            // Anbieter nicht erreichbar: alte Kurse weiter ausliefern, solange welche da sind.
            if (is_array($cached)) {
                return $cached['data'];
            }

            throw $exception;
        }
    }

    /**
     * Kurse umgerechnet auf eine andere Basiswaehrung (Kreuzkurs ueber EUR).
     *
     * @return array{base: string, rates: array<string, float>, updated_at: string|null, next_update_at: string|null, source: string}
     */
    public function withBase(string $base): array
    {
        $data = $this->latest();
        $base = strtoupper($base);

        if ($base === self::BASE) {
            return $data;
        }

        if (! isset($data['rates'][$base]) || $data['rates'][$base] <= 0) {
            throw new RuntimeException('Unknown base currency '.$base);
        }

        $factor = $data['rates'][$base];
        $rates = [];

        foreach ($data['rates'] as $code => $rate) {
            $rates[$code] = round($rate / $factor, 6);
        }

        $rates[self::BASE] = round(1 / $factor, 6);
        ksort($rates);

        return ['base' => $base, 'rates' => $rates] + $data;
    }

    /**
     * @return array{base: string, rates: array<string, float>, updated_at: string|null, next_update_at: string|null, source: string}
     */
    private function fetch(): array
    {
        $url = rtrim((string) config('services.exchange_rates.url', 'https://open.er-api.com/v6/latest'), '/').'/'.self::BASE;
        $response = Http::timeout(10)->acceptJson()->get($url);

        if (! $response->ok() || ($response->json('result') ?? 'success') !== 'success' || ! is_array($response->json('rates'))) {
            throw new RuntimeException('Exchange rates not available: HTTP '.$response->status());
        }

        $rates = [];
        foreach ($response->json('rates') as $code => $rate) {
            if (is_numeric($rate) && preg_match('/^[A-Z]{3}$/', (string) $code)) {
                $rates[$code] = round((float) $rate, 6);
            }
        }
        ksort($rates);

        return [
            'base' => self::BASE,
            'rates' => $rates,
            'updated_at' => $this->timestamp($response->json('time_last_update_unix')),
            'next_update_at' => $this->timestamp($response->json('time_next_update_unix')),
            'source' => (string) config('services.exchange_rates.attribution', 'Rates by ExchangeRate-API (https://www.exchangerate-api.com)'),
        ];
    }

    private function timestamp(mixed $unix): ?string
    {
        return is_numeric($unix) ? CarbonImmutable::createFromTimestampUTC((int) $unix)->toIso8601String() : null;
    }
}
