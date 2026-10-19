<?php

namespace App\Services\Push;

use Firebase\JWT\JWT;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Apple Push Notification service ueber HTTP/2 mit Token-Authentifizierung (.p8-Schluessel).
 * Alle Nachrichten eines Aufrufs gehen gleichzeitig ueber eine HTTP/2-Verbindung
 * (curl multiplext), ein Job mit 500 Geraeten braucht so nur wenige Sekunden.
 */
class ApnsClient
{
    public const RESULT_SENT = 'sent';

    public const RESULT_INVALID = 'invalid';

    public const RESULT_FAILED = 'failed';

    /** Antworten von Apple, nach denen der Token nie wieder funktioniert. */
    private const INVALID_REASONS = ['BadDeviceToken', 'Unregistered', 'DeviceTokenNotForTopic', 'ExpiredToken'];

    private const JWT_CACHE_KEY = 'push:apns:jwt';

    public function configured(): bool
    {
        return filled(config('push.apns.key_id')) && filled(config('push.apns.team_id')) && $this->privateKey() !== null;
    }

    /**
     * @param  array<string, array{token: string, environment: string, payload: array, collapse_id?: ?string, silent?: bool}>  $messages  je Schluessel (Geraete-ID) eine Nachricht; silent = Hintergrund-Push ohne Anzeige
     * @return array<string, string> Schluessel => sent | invalid | failed
     */
    public function send(array $messages): array
    {
        if ($messages === []) {
            return [];
        }

        if (! $this->configured()) {
            return array_fill_keys(array_keys($messages), self::RESULT_FAILED);
        }

        $jwt = $this->jwt();
        $topic = (string) config('push.apns.topic');
        $timeout = (int) config('push.apns.timeout', 15);

        $responses = Http::pool(function (Pool $pool) use ($messages, $jwt, $topic, $timeout) {
            foreach ($messages as $key => $message) {
                $host = $message['environment'] === 'sandbox' ? 'https://api.sandbox.push.apple.com' : 'https://api.push.apple.com';
                $headers = [
                    'authorization' => 'bearer '.$jwt,
                    'apns-topic' => $topic,
                    // Stille Pushs muessen als background mit Prioritaet 5 kommen, sonst verwirft Apple sie.
                    'apns-push-type' => ($message['silent'] ?? false) ? 'background' : 'alert',
                    'apns-priority' => ($message['silent'] ?? false) ? '5' : '10',
                    // Ungeliefertes verfaellt nach einem Tag (Geraet aus).
                    'apns-expiration' => (string) (time() + 86400),
                ];
                if (! empty($message['collapse_id'])) {
                    $headers['apns-collapse-id'] = substr($message['collapse_id'], 0, 64);
                }

                $pool->as((string) $key)
                    ->withOptions(['version' => 2.0])
                    ->withHeaders($headers)
                    ->timeout($timeout)
                    ->post($host.'/3/device/'.$message['token'], $message['payload']);
            }
        });

        $results = [];
        foreach ($messages as $key => $message) {
            $results[$key] = $this->result($responses[(string) $key] ?? null);
        }

        return $results;
    }

    private function result(mixed $response): string
    {
        if (! $response instanceof Response) {
            return self::RESULT_FAILED;
        }

        if ($response->successful()) {
            return self::RESULT_SENT;
        }

        $reason = (string) $response->json('reason', '');

        if ($response->status() === 410 || in_array($reason, self::INVALID_REASONS, true)) {
            return self::RESULT_INVALID;
        }

        // Abgelaufener Anbieter-Token: neu erzeugen lassen, der Push gilt als fehlgeschlagen.
        if ($reason === 'ExpiredProviderToken' || $reason === 'InvalidProviderToken') {
            Cache::forget(self::JWT_CACHE_KEY);
        }

        return self::RESULT_FAILED;
    }

    /**
     * Anbieter-Token (ES256). Apple verlangt einen neuen nach spaetestens einer Stunde
     * und lehnt zu haeufig neue ab – deshalb 45 Minuten im Cache.
     */
    private function jwt(): string
    {
        return Cache::remember(self::JWT_CACHE_KEY, now()->addMinutes(45), fn () => JWT::encode(
            ['iss' => (string) config('push.apns.team_id'), 'iat' => time()],
            (string) $this->privateKey(),
            'ES256',
            (string) config('push.apns.key_id'),
        ));
    }

    private function privateKey(): ?string
    {
        $key = config('push.apns.key');

        if (blank($key) && filled($path = config('push.apns.key_path')) && is_readable($path)) {
            $key = file_get_contents($path);
        }

        if (blank($key)) {
            return null;
        }

        $key = trim((string) $key);
        if (! str_contains($key, 'BEGIN PRIVATE KEY')) {
            try {
                $key = trim((string) base64_decode($key, true));
            } catch (Throwable) {
                return null;
            }
        }

        return str_contains($key, 'BEGIN PRIVATE KEY') ? $key : null;
    }
}
