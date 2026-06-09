<?php

namespace App\Support;

/**
 * Prozessweiter Sammler fuer Service-Token-/__internal-API-Aufrufe, damit sie
 * im Debug-Panel von /travel-alert (Tab "Debug") sichtbar werden. Die per-User-
 * Aufrufe werden bereits von PdsApiService::collectDebug gesammelt; dieser
 * statische Sammler ergaenzt die Aufrufe ueber den Service-Token
 * (PassolutionApiService::fetch*), die sonst nirgends auftauchen.
 *
 * Eintragsformat ist identisch zu PdsApiService::collectDebug, damit das
 * bestehende Frontend (debug.pds_api_calls) sie ohne Aenderung rendern kann.
 */
class PdsDebug
{
    protected static bool $enabled = false;

    protected static array $log = [];

    public static function enable(): void
    {
        self::$enabled = true;
        self::$log = [];
    }

    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Einen API-Aufruf protokollieren (Authorization/Token wird NICHT uebergeben).
     */
    public static function record(
        string $method,
        string $url,
        array $requestParams,
        ?int $status,
        float $startTime,
        mixed $responseBody = null,
        ?string $error = null
    ): void {
        if (! self::$enabled) {
            return;
        }

        $entry = [
            'method' => $method,
            'url' => $url,
            'request_body' => $requestParams,
            'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'status' => $status,
        ];

        if (is_array($responseBody) && isset($responseBody['data']) && is_array($responseBody['data']) && count($responseBody['data']) > 5) {
            $entry['response_body'] = array_merge($responseBody, [
                'data' => array_slice($responseBody['data'], 0, 5),
                '_truncated' => count($responseBody['data']).' total items, showing first 5',
            ]);
        } else {
            $entry['response_body'] = $responseBody;
        }

        if ($error !== null) {
            $entry['error'] = $error;
        }

        self::$log[] = $entry;
    }

    public static function all(): array
    {
        return self::$log;
    }
}
