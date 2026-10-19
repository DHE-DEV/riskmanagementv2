<?php

namespace App\Services\Push;

use Firebase\JWT\JWT;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Firebase Cloud Messaging (HTTP v1) fuer Android. Ein Request je Geraet, alle
 * Requests eines Aufrufs gleichzeitig ueber HTTP/2. Zugriffstoken per Service-Account.
 */
class FcmClient
{
    private const TOKEN_CACHE_KEY = 'push:fcm:access-token';

    public function configured(): bool
    {
        return filled($this->projectId()) && $this->credentials() !== null;
    }

    /**
     * @param  array<string, array{token: string, title?: string, body?: string, data: array<string, string>, collapse_key?: ?string, silent?: bool}>  $messages  silent = Datennachricht ohne Anzeige
     * @return array<string, string> Schluessel => sent | invalid | failed
     */
    public function send(array $messages): array
    {
        if ($messages === []) {
            return [];
        }

        if (! $this->configured() || ($accessToken = $this->accessToken()) === null) {
            return array_fill_keys(array_keys($messages), ApnsClient::RESULT_FAILED);
        }

        $url = 'https://fcm.googleapis.com/v1/projects/'.$this->projectId().'/messages:send';
        $timeout = (int) config('push.fcm.timeout', 15);

        $responses = Http::pool(function (Pool $pool) use ($messages, $url, $accessToken, $timeout) {
            foreach ($messages as $key => $message) {
                $android = ['priority' => 'HIGH', 'ttl' => '86400s'];
                if (! empty($message['collapse_key'])) {
                    $android['collapse_key'] = $message['collapse_key'];
                }

                $payload = ['token' => $message['token'], 'data' => $message['data'], 'android' => $android];
                if (! ($message['silent'] ?? false)) {
                    $payload['notification'] = ['title' => $message['title'], 'body' => $message['body']];
                }

                $pool->as((string) $key)
                    ->withOptions(['version' => 2.0])
                    ->withToken($accessToken)
                    ->timeout($timeout)
                    ->post($url, ['message' => $payload]);
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
            return ApnsClient::RESULT_FAILED;
        }

        if ($response->successful()) {
            return ApnsClient::RESULT_SENT;
        }

        $code = (string) ($response->json('error.details.0.errorCode') ?? $response->json('error.status') ?? '');

        if ($response->status() === 404 || in_array($code, ['UNREGISTERED', 'INVALID_ARGUMENT', 'SENDER_ID_MISMATCH'], true)) {
            return ApnsClient::RESULT_INVALID;
        }

        if ($response->status() === 401) {
            Cache::forget(self::TOKEN_CACHE_KEY);
        }

        return ApnsClient::RESULT_FAILED;
    }

    private function accessToken(): ?string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $credentials = (array) $this->credentials();
        $tokenUri = (string) ($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token');
        $now = time();

        $assertion = JWT::encode([
            'iss' => $credentials['client_email'] ?? '',
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ], (string) ($credentials['private_key'] ?? ''), 'RS256');

        $response = Http::asForm()->timeout(15)->post($tokenUri, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ]);

        $token = $response->successful() ? (string) $response->json('access_token') : '';
        if ($token === '') {
            return null;
        }

        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addSeconds(max(60, (int) $response->json('expires_in', 3600) - 300)));

        return $token;
    }

    private function projectId(): ?string
    {
        return config('push.fcm.project_id') ?: ($this->credentials()['project_id'] ?? null);
    }

    private function credentials(): ?array
    {
        $raw = config('push.fcm.credentials');

        if (blank($raw) && filled($path = config('push.fcm.credentials_path')) && is_readable($path)) {
            $raw = file_get_contents($path);
        }

        if (blank($raw)) {
            return null;
        }

        $raw = trim((string) $raw);
        if (! str_starts_with($raw, '{')) {
            $raw = (string) base64_decode($raw, true);
        }

        $json = json_decode($raw, true);

        return is_array($json) && ! empty($json['client_email']) && ! empty($json['private_key']) ? $json : null;
    }
}
