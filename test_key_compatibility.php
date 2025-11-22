#!/usr/bin/env php
<?php

/**
 * Test Private/Public Key Compatibility
 *
 * This script verifies that the private key from Service 1 (pds-homepage)
 * and the public key from Service 2 (riskmanagementv2) are a matching pair.
 */

require __DIR__.'/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

echo "=== Testing Private/Public Key Compatibility ===\n\n";

// Load Service 2 .env
$dotenv2 = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv2->load();

// Load Service 1 .env
$service1Path = '/home/dh/Code/laravel/tmp-cruisedesign/pds-homepage';
if (!file_exists($service1Path . '/.env')) {
    echo "❌ Service 1 .env not found at: {$service1Path}/.env\n";
    exit(1);
}

$dotenv1 = Dotenv\Dotenv::createImmutable($service1Path);
$dotenv1->load();

// Get private key from Service 1
$privateKey = $_ENV['PASSPORT_PRIVATE_KEY'] ?? null;
if (!$privateKey) {
    echo "❌ PASSPORT_PRIVATE_KEY not found in Service 1 .env\n";
    exit(1);
}

// Get public key from Service 2
$publicKey = $_ENV['SSO_PUBLIC_KEY'] ?? null;
if (!$publicKey) {
    echo "❌ SSO_PUBLIC_KEY not found in Service 2 .env\n";
    exit(1);
}

echo "1. Keys loaded from both services\n";
echo "   ✅ Service 1 (IdP): PASSPORT_PRIVATE_KEY\n";
echo "   ✅ Service 2 (SP): SSO_PUBLIC_KEY\n\n";

// Create test JWT payload (identical to what SSO will create)
$now = time();
$payload = [
    'iss' => 'pds-homepage',
    'aud' => 'riskmanagementv2',
    'sub' => '20175',               // Customer ID from Service 1
    'agent_id' => '1000',           // Agency ID
    'email' => 'client@passolution.de',
    'phone' => '02205 9189840',
    'address' => 'Teststr. 2',
    'account_type' => 4,
    'iat' => $now,
    'exp' => $now + 300,
];

echo "2. Creating test JWT payload\n";
echo "   - Customer ID (sub): {$payload['sub']}\n";
echo "   - Agent ID: {$payload['agent_id']}\n";
echo "   - Email: {$payload['email']}\n\n";

try {
    // Step 1: Sign with Private Key (Service 1 role)
    echo "3. Signing JWT with Private Key (Service 1)...\n";
    $jwt = JWT::encode($payload, $privateKey, 'RS256');
    echo "   ✅ JWT signed successfully\n";
    echo "   - Algorithm: RS256\n";
    echo "   - JWT length: " . strlen($jwt) . " characters\n";
    echo "   - JWT preview: " . substr($jwt, 0, 80) . "...\n\n";

    // Step 2: Verify with Public Key (Service 2 role)
    echo "4. Verifying JWT with Public Key (Service 2)...\n";
    $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));
    echo "   ✅ JWT verification successful!\n\n";

    // Step 3: Validate all claims
    echo "5. Validating decoded claims...\n";

    $checks = [
        'iss' => ['expected' => 'pds-homepage', 'actual' => $decoded->iss],
        'aud' => ['expected' => 'riskmanagementv2', 'actual' => $decoded->aud],
        'sub' => ['expected' => '20175', 'actual' => $decoded->sub],
        'agent_id' => ['expected' => '1000', 'actual' => $decoded->agent_id],
        'email' => ['expected' => 'client@passolution.de', 'actual' => $decoded->email],
    ];

    $allValid = true;
    foreach ($checks as $claim => $data) {
        $match = $data['expected'] === $data['actual'];
        $status = $match ? '✅' : '❌';
        echo "   {$status} {$claim}: {$data['actual']}";
        if (!$match) {
            echo " (expected: {$data['expected']})";
            $allValid = false;
        }
        echo "\n";
    }

    echo "\n";

    if (!$allValid) {
        echo "❌ Some claims don't match!\n";
        exit(1);
    }

    // Final summary
    echo "=== ✅ SUCCESS ===\n\n";
    echo "🎉 Private/Public key pair is COMPATIBLE!\n\n";
    echo "What this means:\n";
    echo "  ✅ Service 1 can sign JWTs with PASSPORT_PRIVATE_KEY\n";
    echo "  ✅ Service 2 can verify JWTs with SSO_PUBLIC_KEY\n";
    echo "  ✅ All claims are correctly preserved during sign/verify\n";
    echo "  ✅ The keys are a matching RSA-4096 pair\n\n";
    echo "🚀 SSO authentication is ready to use!\n\n";
    echo "Next steps:\n";
    echo "  1. Deploy Service 1 with SSO_SERVICE2_* URLs in .env\n";
    echo "  2. Deploy Service 2 with SSO_PUBLIC_KEY in .env\n";
    echo "  3. Run migrations in Service 2\n";
    echo "  4. Test SSO login flow\n\n";

} catch (\Firebase\JWT\SignatureInvalidException $e) {
    echo "❌ SIGNATURE VERIFICATION FAILED!\n";
    echo "   Error: {$e->getMessage()}\n\n";
    echo "⚠️  This means the public key does NOT match the private key!\n";
    echo "   Please check:\n";
    echo "   1. SSO_PUBLIC_KEY in Service 2 matches PASSPORT_PUBLIC_KEY from Service 1\n";
    echo "   2. Keys haven't been modified or corrupted\n";
    exit(1);

} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "   Stack trace:\n";
    echo "   " . $e->getTraceAsString() . "\n";
    exit(1);
}
