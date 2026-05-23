<?php

namespace App\Services;

class IframeAuthToken
{
    private const TTL_SECONDS = 60;

    public static function create(string $email, string $secret): string
    {
        $payload = json_encode([
            'email' => $email,
            'exp' => time() + self::TTL_SECONDS,
        ]);
        $encoded = self::base64url($payload);
        $signature = self::base64url(hash_hmac('sha256', $encoded, $secret, true));

        return $encoded.'.'.$signature;
    }

    public static function verify(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        [$encoded, $signature] = $parts;

        $expected = self::base64url(hash_hmac('sha256', $encoded, $secret, true));
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode(self::base64urlDecode($encoded), true);
        if (! is_array($payload)) {
            return null;
        }
        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }

    private static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
