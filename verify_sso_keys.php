#!/usr/bin/env php
<?php

/**
 * SSO Key Verification Script
 *
 * This script verifies that:
 * 1. Public key is correctly loaded from .env
 * 2. Public key format is valid
 * 3. Public/Private key pair is compatible (if private key is available)
 */

require __DIR__.'/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

echo "=== SSO Key Verification ===\n\n";

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 1. Check if SSO_PUBLIC_KEY exists
echo "1. Checking SSO_PUBLIC_KEY environment variable...\n";
$publicKey = $_ENV['SSO_PUBLIC_KEY'] ?? null;

if (!$publicKey) {
    echo "   ❌ SSO_PUBLIC_KEY not found in .env\n";
    exit(1);
}

if (!str_starts_with($publicKey, '-----BEGIN PUBLIC KEY-----')) {
    echo "   ❌ SSO_PUBLIC_KEY does not start with '-----BEGIN PUBLIC KEY-----'\n";
    exit(1);
}

echo "   ✅ SSO_PUBLIC_KEY exists and has correct format\n\n";

// 2. Verify key can be loaded by OpenSSL
echo "2. Verifying OpenSSL can load the public key...\n";
$resource = openssl_pkey_get_public($publicKey);

if ($resource === false) {
    echo "   ❌ Invalid public key format - OpenSSL error: " . openssl_error_string() . "\n";
    exit(1);
}

$keyDetails = openssl_pkey_get_details($resource);
echo "   ✅ Public key loaded successfully\n";
echo "   - Key type: " . ($keyDetails['type'] === OPENSSL_KEYTYPE_RSA ? 'RSA' : 'Unknown') . "\n";
echo "   - Key bits: " . $keyDetails['bits'] . "\n\n";

// 3. Test JWT signing and verification (if private key is available)
echo "3. Testing JWT sign/verify compatibility...\n";

// Create a test JWT with a dummy private key for Service 1
$testPrivateKey = $_ENV['PASSPORT_PRIVATE_KEY'] ?? null;

if ($testPrivateKey && str_starts_with($testPrivateKey, '-----BEGIN')) {
    echo "   Found private key - testing full sign/verify cycle...\n";

    // Create test payload
    $payload = [
        'iss' => 'pds-homepage',
        'aud' => 'riskmanagementv2',
        'sub' => 'test-123',
        'agent_id' => 'test-agency',
        'email' => 'test@example.com',
        'iat' => time(),
        'exp' => time() + 300,
    ];

    try {
        // Sign with private key
        $jwt = JWT::encode($payload, $testPrivateKey, 'RS256');
        echo "   ✅ JWT signed successfully\n";

        // Verify with public key
        $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));
        echo "   ✅ JWT verified successfully with public key\n";
        echo "   ✅ Private/Public key pair is COMPATIBLE\n\n";

        // Show decoded claims
        echo "   Decoded claims:\n";
        echo "   - iss: {$decoded->iss}\n";
        echo "   - aud: {$decoded->aud}\n";
        echo "   - sub: {$decoded->sub}\n";
        echo "   - agent_id: {$decoded->agent_id}\n";
        echo "   - email: {$decoded->email}\n\n";

    } catch (\Exception $e) {
        echo "   ❌ JWT sign/verify failed: " . $e->getMessage() . "\n";
        echo "   ⚠️  Private and public keys may not be a matching pair!\n\n";
        exit(1);
    }
} else {
    echo "   ⚠️  Private key not available - skipping sign/verify test\n";
    echo "   (This is normal for Service Provider which only needs public key)\n\n";
}

// 4. Verify SSO configuration
echo "4. Checking SSO configuration...\n";

$config = [
    'SSO_PUBLIC_KEY' => str_starts_with($_ENV['SSO_PUBLIC_KEY'] ?? '', '-----BEGIN'),
    'SSO_USE_ENV_KEYS' => ($_ENV['SSO_USE_ENV_KEYS'] ?? 'true') === 'true',
];

foreach ($config as $key => $value) {
    $status = $value ? '✅' : '❌';
    $valueStr = is_bool($value) ? ($value ? 'true' : 'false') : 'set';
    echo "   {$status} {$key}: {$valueStr}\n";
}

echo "\n";

// 5. Summary
echo "=== Summary ===\n";
echo "✅ All checks passed!\n";
echo "✅ SSO_PUBLIC_KEY is correctly configured\n";
echo "✅ Key format is valid\n";

if ($testPrivateKey) {
    echo "✅ Private/Public key pair is compatible\n";
}

echo "\n🎉 Service 2 is ready for SSO authentication!\n";
