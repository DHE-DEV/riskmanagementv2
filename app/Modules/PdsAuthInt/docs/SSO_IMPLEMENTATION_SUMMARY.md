# PdsAuthInt SSO Implementation - Comprehensive Summary

Umfassende Zusammenfassung der PdsAuthInt SSO-Implementierung

---

## Table of Contents / Inhaltsverzeichnis

1. [Project Overview](#project-overview)
2. [Files Created](#files-created)
3. [Key Features](#key-features)
4. [Integration with Existing PASSPORT Keys](#integration-with-existing-passport-keys)
5. [System Architecture](#system-architecture)
6. [Next Steps for Implementation](#next-steps-for-implementation)
7. [Quick Start Guide](#quick-start-guide)
8. [Testing & Troubleshooting](#testing--troubleshooting)

---

## Project Overview

### What was Implemented / Was wurde implementiert

The **PdsAuthInt SSO (Single Sign-On) System** is a comprehensive authentication integration module that enables secure, token-based authentication between multiple Laravel services (microservices):

**PdsAuthInt SSO-System** ist ein umfassendes Authentifizierungs-Integrations-Modul, das sichere, tokenbasierte Authentifizierung zwischen mehreren Laravel-Services (Microservices) ermöglicht:

- **Identity Provider (IdP)** Role: `pds-homepage` - authenticates users and issues JWTs
- **Service Provider (SP)** Role: `riskmanagementv2` - receives JWTs and provisions users via JIT

#### Key Implementation Goals / Hauptziele der Implementierung

1. Eliminate duplicate user authentication across services
2. Enable seamless single sign-on experience
3. Support just-in-time (JIT) user provisioning
4. Google App Engine compatibility (environment variable support for keys)
5. Multi-tenancy support via agent_id and service1_customer_id

#### Technologies Used / Verwendete Technologien

- **JWT (JSON Web Tokens)**: RS256 asymmetric encryption with Firebase/JWT library
- **Laravel 11**: Framework for both IdP and SP applications
- **Laravel Passport**: For RSA key pair management (PASSPORT_PRIVATE_KEY, PASSPORT_PUBLIC_KEY)
- **Cache System**: For One-Time Token (OTT) temporary storage
- **Database**: Customer model with SSO-specific fields
- **HTTP Client**: Guzzle HTTP for inter-service communication
- **Logging**: Laravel logging system for security audit trail

---

## Files Created

### Directory Structure / Verzeichnisstruktur

```
riskmanagementv2/
├── app/Modules/PdsAuthInt/
│   ├── Http/Controllers/
│   │   └── SPController.php                    # Service Provider Controller
│   ├── Providers/
│   │   └── PdsAuthIntServiceProvider.php       # Service Provider Registration
│   ├── routes/
│   │   ├── api.php                             # API Routes (JWT Exchange)
│   │   └── web.php                             # Web Routes (Login Handler)
│   └── config/
│       └── pdsauthint.php                      # Configuration (SP Version)
└── database/migrations/
    └── 2025_11_11_063922_add_sso_fields_to_customers_table.php

pds-homepage/
└── app/Modules/PdsAuthInt/
    ├── Http/Controllers/
    │   └── IdPController.php                   # Identity Provider Controller
    ├── Providers/
    │   └── PdsAuthIntServiceProvider.php       # Service Provider Registration
    ├── routes/
    │   └── web.php                             # Web Routes (SSO Redirect)
    └── config/
        └── pdsauthint.php                      # Configuration (IdP Version)
```

### Complete File Listing / Vollständige Dateiliste

#### **1. Service Provider (riskmanagementv2) - SPController.php**

**Path**: `/home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/app/Modules/PdsAuthInt/Http/Controllers/SPController.php`

**Purpose**: Handles JWT validation and One-Time Token generation for the Service Provider

**Key Methods**:
- `exchangeToken(Request $request): JsonResponse` - Validates JWT and generates OTT
- `handleLogin(Request $request): RedirectResponse` - Performs JIT provisioning and login

---

#### **2. Identity Provider (pds-homepage) - IdPController.php**

**Path**: `/home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/app/Modules/PdsAuthInt/Http/Controllers/IdPController.php`

**Purpose**: Creates JWT tokens and exchanges them with Service Provider for OTT

**Key Methods**:
- `redirectToServiceProvider(): JsonResponse` - Creates JWT, exchanges for OTT, returns login URL

---

#### **3. Service Provider Service Provider**

**Path**: `/home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/app/Modules/PdsAuthInt/Providers/PdsAuthIntServiceProvider.php`

**Purpose**: Registers module configuration and loads routes

---

#### **4. Identity Provider Service Provider**

**Path**: `/home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/app/Modules/PdsAuthInt/Providers/PdsAuthIntServiceProvider.php`

**Purpose**: Registers module configuration and loads routes

---

#### **5. Service Provider Configuration**

**Path**: `/home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/app/Modules/PdsAuthInt/config/pdsauthint.php`

**Purpose**: SP-specific configuration for JWT validation and OTT handling

**Key Settings**:
```php
'role' => 'sp',                              // Service Provider role
'public_key' => env('SSO_PUBLIC_KEY') ?: env('PASSPORT_PUBLIC_KEY') ?: storage_path(...),
'jwt_issuer' => 'pds-homepage',
'jwt_audience' => 'riskmanagementv2',
'ott_ttl' => 60,                             // 60 seconds
'customer_guard' => 'customer',
'customer_dashboard_route' => 'customer.dashboard',
```

---

#### **6. Identity Provider Configuration**

**Path**: `/home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/app/Modules/PdsAuthInt/config/pdsauthint.php`

**Purpose**: IdP-specific configuration for JWT creation and exchange

**Key Settings**:
```php
'role' => 'idp',                             // Identity Provider role
'private_key' => env('PASSPORT_PRIVATE_KEY') ?: storage_path(...),
'public_key' => env('PASSPORT_PUBLIC_KEY') ?: storage_path(...),
'service2_exchange_url' => env('SSO_SERVICE2_EXCHANGE_URL', 'http://127.0.0.1:8000/api/sso/exchange'),
'jwt_ttl' => 300,                            // 5 minutes
'service2_login_url' => env('SSO_SERVICE2_LOGIN_URL', 'http://127.0.0.1:8000/sso/login'),
```

---

#### **7. Service Provider Routes (API)**

**Path**: `/home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/app/Modules/PdsAuthInt/routes/api.php`

**Routes**:
- `POST /api/sso/exchange` → `SPController@exchangeToken`

---

#### **8. Service Provider Routes (Web)**

**Path**: `/home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/app/Modules/PdsAuthInt/routes/web.php`

**Routes**:
- `GET /sso/login?ott=...` → `SPController@handleLogin`

---

#### **9. Identity Provider Routes**

**Path**: `/home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/app/Modules/PdsAuthInt/routes/web.php`

**Routes**:
- `POST /pdsauthint/redirect` → `IdPController@redirectToServiceProvider` (auth required)

---

#### **10. Database Migration**

**Path**: `/home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/database/migrations/2025_11_11_063922_add_sso_fields_to_customers_table.php`

**Purpose**: Adds SSO-specific fields to customers table

**New Fields**:
- `agent_id` (string, nullable) - Links to agent/broker in Service 1
- `service1_customer_id` (string, nullable) - Customer ID in pds-homepage
- `phone` (string, nullable) - Customer phone number
- `address` (json, nullable) - Customer address (structured JSON)
- `account_type` (string) - Account classification (standard, premium, vip)
- **Unique Constraint**: `(agent_id, service1_customer_id)` - Ensures multi-tenant isolation

---

#### **11. App Service Provider Registration**

**Paths**:
- `/home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/app/Providers/AppServiceProvider.php`
- `/home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/app/Providers/AppServiceProvider.php`

**Purpose**: Registers PdsAuthIntServiceProvider in the application container

**Code**:
```php
public function register(): void
{
    $this->app->register(\App\Modules\PdsAuthInt\Providers\PdsAuthIntServiceProvider::class);
}
```

---

## Key Features

### 1. Security Features / Sicherheitsmerkmale

#### JWT Signing with RS256
- **Asymmetric Encryption**: Uses RSA key pairs (private and public keys)
- **Algorithm**: RS256 (RSASSA-PKCS1-v1_5 with SHA-256)
- **Signature Verification**: Service Provider verifies JWT signature with IdP's public key
- **Protection Against**: Token tampering, unauthorized token creation

#### Claims Validation
- **iss (Issuer)**: Validates JWT source (`pds-homepage`)
- **aud (Audience)**: Validates JWT recipient (`riskmanagementv2`)
- **exp (Expiration)**: Validates token freshness (default 5 minutes)
- **Protection Against**: Token reuse, cross-service token attacks

#### One-Time Token (OTT) Pattern
- **Security**: JWT claims cached only temporarily (60 seconds default)
- **One-Time Use**: Cache::pull() retrieves and deletes token atomically
- **Protection Against**: Token replay attacks, interception of login links

#### Secure Password Generation
- When creating new customers via JIT provisioning, random passwords are generated using `Str::random(32)`
- Passwords are bcrypt hashed and stored (though not used for SSO login)

#### Comprehensive Logging
- All authentication steps logged (JWT creation, validation, OTT generation, login)
- All errors logged with context for security audit trails
- Sensitive data (JWT, OTT) partially masked in logs

### 2. Multi-Tenancy Support / Multi-Mandantenfähigkeit

#### Agent-Based Multi-Tenancy
- **agent_id**: Identifies the broker/agent in Service 1
- **service1_customer_id**: Customer's ID in pds-homepage
- **Unique Constraint**: Combination of `(agent_id, service1_customer_id)` ensures isolation

#### Benefits
- Customers from different agents are isolated
- Same customer can exist under different agents without conflicts
- Supports complex broker/customer relationships
- Enables SaaS multi-tenant scenarios

**Example**:
```php
$customer = Customer::where('agent_id', $agentId)
    ->where('service1_customer_id', $service1CustomerId)
    ->first();
```

### 3. Just-In-Time (JIT) Provisioning / Just-In-Time Bereitstellung

#### Automatic User Creation
- First-time users are automatically created during SSO login
- No pre-registration required
- Reduces user management overhead
- Perfect for scenarios with dynamic customer bases

#### Automatic User Updates
- Existing users are updated with latest information from IdP
- Keeps profile data in sync across services
- Supports: name, email, phone, address, account_type

#### Database Fields
```php
Customer::create([
    'agent_id' => $agentId,                    // From JWT sub claim
    'service1_customer_id' => $customerId,     // From JWT customer_id claim
    'name' => $claims['name'],                 // From JWT
    'email' => $claims['email'],               // From JWT
    'phone' => $claims['phone'],               // From JWT
    'address' => $claims['address'],           // From JWT
    'account_type' => $claims['account_type'], // From JWT
    'password' => bcrypt(Str::random(32)),    // Random, not used for SSO
]);
```

### 4. Google App Engine Compatibility / Google App Engine Kompatibilität

#### Environment Variable Key Support
- Private and public keys can be stored in environment variables
- **No file system access required** for Google App Engine (ephemeral file system)
- Supports Passport key format with PEM headers

#### Key Configuration Options
```php
// Option 1: Environment variables (Recommended for GAE)
'private_key' => env('PASSPORT_PRIVATE_KEY'),
'public_key' => env('PASSPORT_PUBLIC_KEY'),
'use_env_keys' => true,

// Option 2: File paths (Traditional Laravel)
'private_key' => storage_path('app/sso/sso-private.key'),
'public_key' => storage_path('app/sso/sso-public.key'),
'use_env_keys' => false,

// Option 3: Fallback chain
'public_key' => env('SSO_PUBLIC_KEY') ?: env('PASSPORT_PUBLIC_KEY') ?: storage_path(...)
```

#### Benefits for GAE Deployment
- No persistent storage needed for keys
- Works seamlessly in containerized environments
- Compatible with Google Cloud Secret Manager
- Enables horizontal scaling without file system concerns

---

## Integration with Existing PASSPORT Keys

### How the System Uses PASSPORT Keys

The SSO implementation is designed to leverage Laravel Passport's existing RSA key pair infrastructure:

#### Passport Key Generation
```bash
php artisan passport:install
# Generates:
# - storage/oauth-private.key
# - storage/oauth-public.key
```

#### Environment Variables
These keys are typically stored in environment variables:
```bash
PASSPORT_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----\n..."
PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n..."
```

### Configuration Fallback Strategy

#### Service Provider (riskmanagementv2)
```php
'public_key' => env('SSO_PUBLIC_KEY') ?: env('PASSPORT_PUBLIC_KEY') ?: storage_path('app/sso/sso-public.key'),
```

**Fallback Chain**:
1. `SSO_PUBLIC_KEY` - Custom SSO-specific public key
2. `PASSPORT_PUBLIC_KEY` - Laravel Passport public key (recommended)
3. File path - Fallback to file-based key

#### Identity Provider (pds-homepage)
```php
'private_key' => env('PASSPORT_PRIVATE_KEY') ?: storage_path('app/sso/sso-private.key'),
'public_key' => env('PASSPORT_PUBLIC_KEY') ?: storage_path('app/sso/sso-public.key'),
```

**Fallback Chain**:
1. `PASSPORT_PRIVATE_KEY` - Laravel Passport private key (recommended)
2. `PASSPORT_PUBLIC_KEY` - Laravel Passport public key
3. File paths - Fallback to file-based keys

### Using Passport Keys Directly

If Passport is already configured in your services:

**1. In .env file (Development)**:
```bash
# Copy these from your Passport installation
PASSPORT_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA...
-----END RSA PRIVATE KEY-----"

PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG...
-----END PUBLIC KEY-----"

# Optional: Custom SSO keys
SSO_PUBLIC_KEY=${PASSPORT_PUBLIC_KEY}
SSO_USE_ENV_KEYS=true
```

**2. In Google Cloud (GAE/Production)**:
```bash
# Set via Google Cloud Secret Manager or Cloud Run secrets
gcloud secrets create passport-private-key --data-file=-
gcloud secrets create passport-public-key --data-file=-

# Reference in app.yaml or Cloud Run configuration
env:
  PASSPORT_PRIVATE_KEY: "secret://passport-private-key"
  PASSPORT_PUBLIC_KEY: "secret://passport-public-key"
  SSO_USE_ENV_KEYS: "true"
```

### File-Based Fallback (Traditional)

If you prefer file-based keys:

**1. Generate separate SSO keys**:
```bash
# Generate RSA key pair for SSO
openssl genrsa -out storage/app/sso/sso-private.key 2048
openssl rsa -in storage/app/sso/sso-private.key -pubout -out storage/app/sso/sso-public.key
```

**2. Configure in pdsauthint.php**:
```php
'use_env_keys' => false,
'private_key' => storage_path('app/sso/sso-private.key'),
'public_key' => storage_path('app/sso/sso-public.key'),
```

### Key Detection Logic

The system implements intelligent key detection in both controllers:

**In SPController.php (Service Provider)**:
```php
$useEnvKeys = config('pdsauthint.use_env_keys', true);

if ($useEnvKeys && is_string($publicKeyConfig) && str_starts_with($publicKeyConfig, '-----BEGIN')) {
    // Key provided directly in environment variable
    $publicKey = $publicKeyConfig;
    Log::debug('SSO: Using public key from environment variable');
} else {
    // Key is a file path
    $publicKey = file_get_contents($publicKeyConfig);
    Log::debug('SSO: Using public key from file', ['path' => $publicKeyConfig]);
}
```

**In IdPController.php (Identity Provider)**:
```php
$useEnvKeys = config('pdsauthint.use_env_keys', true);

if ($useEnvKeys && is_string($privateKeyConfig) && str_starts_with($privateKeyConfig, '-----BEGIN')) {
    // Key provided directly in environment variable
    $privateKey = $privateKeyConfig;
    Log::debug('PdsAuthInt: Using private key from environment variable');
} else {
    // Key is a file path
    $privateKey = file_get_contents($privateKeyConfig);
    Log::debug('PdsAuthInt: Using private key from file', ['path' => $privateKeyPath]);
}
```

---

## System Architecture

### SSO Flow Diagram / SSO-Ablaufdiagramm

```
┌─────────────────┐                        ┌──────────────────────┐
│  pds-homepage   │                        │ riskmanagementv2     │
│  (IdP)          │                        │ (SP)                 │
└────────┬────────┘                        └──────────┬───────────┘
         │                                            │
         │ 1. User clicks "SSO to Service 2"         │
         │    (Authenticated in pds-homepage)        │
         │                                            │
         ├─────────────────────────────────────────> POST /pdsauthint/redirect
         │                                            │
         │ 2. Create JWT payload                      │
         │    - sub: customer.id                      │
         │    - iss: 'pds-homepage'                   │
         │    - aud: 'riskmanagementv2'               │
         │    - exp: now + 300 seconds                │
         │    - customer_id: from JWT payload         │
         │    - agent_id: from JWT payload            │
         │                                            │
         │ 3. Sign JWT with RS256 + PASSPORT_PRIVATE_KEY
         │                                            │
         │ 4. Exchange JWT for OTT                    │
         │    POST request to Service 2               │
         │    Body: { "jwt": "eyJ..." }               │
         │                                            │
         ├────────────────────────────────────────> POST /api/sso/exchange
         │                                            │
         │                        5. Validate JWT
         │                           - Load PASSPORT_PUBLIC_KEY
         │                           - Verify RS256 signature
         │                           - Check iss = 'pds-homepage'
         │                           - Check aud = 'riskmanagementv2'
         │                           - Check exp > now
         │                           │
         │                        6. Generate OTT
         │                           - Random 60-char string
         │                           - Cache claims for 60 seconds
         │                           - Key: 'sso_ott_' . $ott
         │                           │
         │ <─────────────────────────────────────── { "ott": "abc...", "redirect_url": "..." }
         │                                            │
         │ 7. Return login URL to frontend            │
         │    (with OTT as query parameter)           │
         │ <─ { "login_url": "http://sp/sso/login?ott=abc..." }
         │                                            │
         │                        8. Frontend redirects to OTT URL
         │                           GET /sso/login?ott=abc...
         │                           │
         │                        9. Validate OTT
         │                           - Retrieve from cache
         │                           - Delete from cache (atomic pull)
         │                           │
         │                        10. JIT Provisioning
         │                           - Find customer by:
         │                             agent_id + service1_customer_id
         │                           - If not found: Create new customer
         │                           - If found: Update existing customer
         │                           │
         │                        11. Login customer
         │                           Auth::guard('customer')->login($customer)
         │                           │
         │                        12. Redirect to dashboard
         │                           GET /customer/dashboard
         │ <─────────────────────────────────────── (Authenticated)
         │
```

### Key Components

#### 1. Identity Provider (pds-homepage)
- **Role**: Creates and signs JWT tokens
- **Responsibility**: Authenticating users, creating JWT with user claims
- **Key Library**: Firebase/JWT for JWT encoding with RS256
- **Private Key**: Used for signing JWTs

#### 2. Service Provider (riskmanagementv2)
- **Role**: Validates JWTs and logs in users
- **Responsibility**: Validating JWT signatures, exchanging for OTT, JIT provisioning
- **Key Library**: Firebase/JWT for JWT decoding and verification
- **Public Key**: Used for verifying JWT signatures

#### 3. Cache System
- **Purpose**: Temporary storage of JWT claims via OTT
- **TTL**: 60 seconds (configurable)
- **Pattern**: Atomic read-delete (Cache::pull)
- **Security**: One-time use prevents replay attacks

#### 4. Database
- **Customer Model**: Enhanced with SSO fields
- **Unique Constraint**: (agent_id, service1_customer_id) for multi-tenancy
- **JIT Provisioning**: Automatic customer creation/update during first login

#### 5. HTTP Communication
- **Exchange Endpoint**: Service 1 → Service 2 API call
- **Library**: Laravel HTTP client (Guzzle)
- **Timeout**: 10 seconds
- **Error Handling**: Comprehensive logging and error responses

---

## Next Steps for Implementation

### Phase 1: Environment Setup (1-2 hours)

#### 1.1 Generate or Obtain RSA Key Pairs

**Option A: Use Passport Keys (Recommended)**
```bash
# If Passport is already installed
php artisan passport:install

# Keys are generated at:
# - storage/oauth-private.key
# - storage/oauth-public.key
```

**Option B: Generate Custom SSO Keys**
```bash
# Create storage/app/sso directory
mkdir -p storage/app/sso

# Generate private key
openssl genrsa -out storage/app/sso/sso-private.key 2048

# Generate public key from private key
openssl rsa -in storage/app/sso/sso-private.key -pubout -out storage/app/sso/sso-public.key

# Secure permissions
chmod 600 storage/app/sso/sso-private.key
chmod 644 storage/app/sso/sso-public.key
```

#### 1.2 Configure Environment Variables

**In pds-homepage/.env**:
```bash
# For local development (paste actual key content)
PASSPORT_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----
[Your actual private key content with newlines]
-----END RSA PRIVATE KEY-----"

PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
[Your actual public key content with newlines]
-----END PUBLIC KEY-----"

SSO_USE_ENV_KEYS=true

# Service 2 (riskmanagementv2) endpoints
SSO_SERVICE2_EXCHANGE_URL=http://127.0.0.1:8001/api/sso/exchange
SSO_SERVICE2_LOGIN_URL=http://127.0.0.1:8001/sso/login
```

**In riskmanagementv2/.env**:
```bash
# Same keys (public key is shared)
PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
[Your actual public key content with newlines]
-----END PUBLIC KEY-----"

SSO_USE_ENV_KEYS=true
```

**For Google Cloud / Production**:
```bash
# Use Google Cloud Secret Manager
gcloud secrets create passport-private-key --data-file=storage/oauth-private.key
gcloud secrets create passport-public-key --data-file=storage/oauth-public.key

# Configure in app.yaml or Cloud Run
env:
  PASSPORT_PRIVATE_KEY: "@secret://passport-private-key"
  PASSPORT_PUBLIC_KEY: "@secret://passport-public-key"
  SSO_USE_ENV_KEYS: "true"
  SSO_SERVICE2_EXCHANGE_URL: "https://riskmanagement.example.com/api/sso/exchange"
  SSO_SERVICE2_LOGIN_URL: "https://riskmanagement.example.com/sso/login"
```

#### 1.3 Verify Configuration

```bash
# Test key loading
php artisan tinker
>>> config('pdsauthint.private_key')
>>> config('pdsauthint.public_key')
>>> config('pdsauthint.use_env_keys')

# Should return key content (not file path) if using env vars
```

### Phase 2: Database Setup (30 minutes)

#### 2.1 Run Migrations

**In riskmanagementv2**:
```bash
php artisan migrate

# Should create:
# - agent_id column
# - service1_customer_id column
# - phone column
# - address column (JSON)
# - account_type column
# - unique constraint on (agent_id, service1_customer_id)
```

#### 2.2 Verify Migration

```bash
php artisan tinker
>>> \DB::table('customers')->getConnection()->getSchemaBuilder()->getColumns('customers')
# Should show new SSO fields
```

#### 2.3 Update Customer Model (if needed)

Ensure Customer model includes SSO fields in `$fillable`:
```php
protected $fillable = [
    // ... existing fields
    'agent_id',
    'service1_customer_id',
    'phone',
    'address',
    'account_type',
];

protected $casts = [
    'address' => 'json',
    // ... other casts
];
```

### Phase 3: Configuration Verification (30 minutes)

#### 3.1 Verify Service Provider Registration

**In riskmanagementv2/app/Providers/AppServiceProvider.php**:
```php
public function register(): void
{
    $this->app->register(\App\Modules\PdsAuthInt\Providers\PdsAuthIntServiceProvider::class);
}
```

**In pds-homepage/app/Providers/AppServiceProvider.php**:
```php
public function register(): void
{
    $this->app->register(\App\Modules\PdsAuthInt\Providers\PdsAuthIntServiceProvider::class);
}
```

#### 3.2 Check Routes

```bash
# In riskmanagementv2
php artisan route:list | grep sso

# Should show:
# - POST /api/sso/exchange
# - GET /sso/login

# In pds-homepage
php artisan route:list | grep pdsauthint

# Should show:
# - POST /pdsauthint/redirect (auth required)
```

#### 3.3 Verify Configuration Loading

```bash
php artisan config:show pdsauthint

# Should display all SSO settings for current service
```

### Phase 4: Testing & Validation (1-2 hours)

See [Testing & Troubleshooting](#testing--troubleshooting) section below.

### Phase 5: Frontend Integration (1-2 hours)

#### 5.1 In pds-homepage (IdP)

Create SSO trigger button/link:
```blade
<form action="{{ route('pdsauthint.redirect') }}" method="POST" style="display: inline;">
    @csrf
    <button type="submit" class="btn btn-primary">
        Sign in to Service 2
    </button>
</form>
```

Handle response (JavaScript):
```javascript
document.querySelector('form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const response = await fetch('{{ route("pdsauthint.redirect") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
        },
    });

    const data = await response.json();

    if (data.success) {
        window.location.href = data.data.login_url;
    } else {
        alert('SSO failed: ' + data.message);
    }
});
```

#### 5.2 In riskmanagementv2 (SP)

The SSO login endpoint (`/sso/login?ott=...`) redirects automatically to the dashboard after successful authentication. No additional UI needed.

### Phase 6: Deployment Considerations

#### 6.1 Key Management in Production

**Using Passport Keys with Google Cloud Secret Manager**:
```bash
# 1. Store keys in Secret Manager
gcloud secrets create passport-private-key --data-file=storage/oauth-private.key
gcloud secrets create passport-public-key --data-file=storage/oauth-public.key

# 2. Grant permissions
gcloud projects add-iam-policy-binding [PROJECT] \
    --member=serviceAccount:[SERVICE_ACCOUNT] \
    --role=roles/secretmanager.secretAccessor

# 3. Reference in app.yaml
env:
  PASSPORT_PRIVATE_KEY: "@secret://passport-private-key"
  PASSPORT_PUBLIC_KEY: "@secret://passport-public-key"
```

#### 6.2 Update Service URLs

**In pds-homepage/.env (Production)**:
```bash
SSO_SERVICE2_EXCHANGE_URL=https://riskmanagement.yourdomain.com/api/sso/exchange
SSO_SERVICE2_LOGIN_URL=https://riskmanagement.yourdomain.com/sso/login
```

#### 6.3 Security Checklist

- [ ] Keys stored in secure Secret Manager (not in code/repo)
- [ ] `use_env_keys` set to `true` for cloud deployments
- [ ] HTTPS enforced for all SSO endpoints
- [ ] CORS configured properly if services on different domains
- [ ] Rate limiting enabled on `/api/sso/exchange` endpoint
- [ ] Logging configured for security audit trail
- [ ] Cache TTL appropriate for your use case
- [ ] Customer guard configured correctly
- [ ] Dashboard route accessible after login

---

## Quick Start Guide

### Minimum Configuration (5 minutes)

#### Step 1: Generate or Copy Keys
```bash
# If using existing Passport keys, skip to Step 2

# Otherwise, generate new keys
mkdir -p storage/app/sso
openssl genrsa -out storage/app/sso/sso-private.key 2048
openssl rsa -in storage/app/sso/sso-private.key -pubout -out storage/app/sso/sso-public.key
```

#### Step 2: Configure Environment Variables

**pds-homepage/.env**:
```bash
# Option A: Using environment variables (recommended)
PASSPORT_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----
... [key content] ...
-----END RSA PRIVATE KEY-----"

PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
... [key content] ...
-----END PUBLIC KEY-----"

SSO_USE_ENV_KEYS=true

# Service 2 endpoints
SSO_SERVICE2_EXCHANGE_URL=http://127.0.0.1:8001/api/sso/exchange
SSO_SERVICE2_LOGIN_URL=http://127.0.0.1:8001/sso/login

# Option B: Using file paths
# Leave PASSPORT_PRIVATE_KEY and PASSPORT_PUBLIC_KEY empty
# And set SSO_USE_ENV_KEYS=false
```

**riskmanagementv2/.env**:
```bash
PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
... [same key content as above] ...
-----END PUBLIC KEY-----"

SSO_USE_ENV_KEYS=true
```

#### Step 3: Run Migration

```bash
cd riskmanagementv2
php artisan migrate
```

#### Step 4: Test the Flow

See [Quick Test Commands](#quick-test-commands) below.

### Quick Test Commands

#### Test 1: Verify Key Loading
```bash
# In riskmanagementv2
php artisan tinker
>>> config('pdsauthint')

# Should show all settings including loaded keys
>>> config('pdsauthint.public_key') // Should return key content, not file path
```

#### Test 2: Test JWT Exchange (API)

**Using curl**:
```bash
# Step 1: Create a JWT in pds-homepage
php artisan tinker
>>> use Firebase\JWT\JWT;
>>> $payload = [
>>>     'iss' => 'pds-homepage',
>>>     'aud' => 'riskmanagementv2',
>>>     'sub' => '123',
>>>     'customer_id' => 'cust_456',
>>>     'agent_id' => 'agent_789',
>>>     'name' => 'Test User',
>>>     'email' => 'test@example.com',
>>>     'exp' => time() + 300,
>>>     'iat' => time(),
>>> ];
>>> $privateKey = config('pdsauthint.private_key');
>>> $jwt = JWT::encode($payload, $privateKey, 'RS256');
>>> echo $jwt;

# Step 2: Exchange JWT for OTT in riskmanagementv2
curl -X POST http://127.0.0.1:8001/api/sso/exchange \
  -H "Content-Type: application/json" \
  -d '{"jwt":"[JWT_FROM_STEP_1]"}'

# Should return:
# {
#   "success": true,
#   "ott": "abc123...",
#   "redirect_url": "http://127.0.0.1:8001/sso/login?ott=abc123...",
#   "expires_in": 60
# }
```

#### Test 3: Complete SSO Flow

**Manually test the complete flow**:
```bash
# 1. Start both services locally
# Terminal 1 (pds-homepage)
cd pds-homepage
php artisan serve --port=8000

# Terminal 2 (riskmanagementv2)
cd riskmanagementv2
php artisan serve --port=8001

# 2. Create a test customer in pds-homepage
php artisan tinker
>>> $customer = \App\Models\Customer::first();
>>> // Make sure customer is created

# 3. Login to pds-homepage at http://127.0.0.1:8000/login

# 4. Navigate to SSO trigger and click "Sign in to Service 2"
# (Or use the test endpoint below)

# 5. You should be redirected and logged into riskmanagementv2

# 6. Check logs for SSO flow
tail -f storage/logs/laravel.log | grep SSO
tail -f storage/logs/laravel.log | grep PdsAuthInt
```

#### Test 4: Verify Customer Creation

```bash
# In riskmanagementv2, check if customer was created via JIT
php artisan tinker
>>> \App\Models\Customer::where('service1_customer_id', 'cust_456')->first()

# Should return the provisioned customer with:
# - agent_id: 'agent_789'
# - service1_customer_id: 'cust_456'
# - name, email, phone, etc. from JWT
```

---

## Testing & Troubleshooting

### Common Issues

#### Issue 1: "Public key not found"

**Error Message**:
```
SSO public key file not found
Path: storage/app/sso/sso-public.key
```

**Solution**:
```php
// In riskmanagementv2/config/pdsauthint.php
// Either:
// 1. Create the file
mkdir -p storage/app/sso
cp [path-to-your-public-key] storage/app/sso/sso-public.key

// 2. Or use environment variable
// In .env:
SSO_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
... [key content] ...
-----END PUBLIC KEY-----"
SSO_USE_ENV_KEYS=true
```

#### Issue 2: "JWT signature validation failed"

**Error Message**:
```
JWT signature validation failed
```

**Causes**:
- Public key doesn't match the private key used to sign
- Key format is incorrect
- Key has extra newlines or spaces

**Solution**:
```bash
# Verify keys match
# 1. Extract public key from private key
openssl rsa -in storage/app/sso/sso-private.key -pubout -text

# 2. Compare with your public key
cat storage/app/sso/sso-public.key

# 3. If not matching, regenerate both
openssl genrsa -out storage/app/sso/sso-private.key 2048
openssl rsa -in storage/app/sso/sso-private.key -pubout -out storage/app/sso/sso-public.key

# 4. Update environment variables with new keys
```

#### Issue 3: "Invalid issuer" or "Invalid audience"

**Error Message**:
```
Invalid issuer: expected 'pds-homepage', received 'some-other-value'
Invalid audience: expected 'riskmanagementv2', received 'wrong-service'
```

**Solution**:
```php
// Ensure JWT payload matches configuration

// In pds-homepage (IdP):
// config/pdsauthint.php must have:
'jwt_issuer' => 'pds-homepage',
'jwt_audience' => 'riskmanagementv2',

// When creating JWT:
$payload = [
    'iss' => 'pds-homepage',  // Must match config
    'aud' => 'riskmanagementv2', // Must match config
    // ... other claims
];
```

#### Issue 4: "Invalid or expired OTT"

**Error Message**:
```
Invalid or expired login token. Please try again.
```

**Causes**:
- OTT expired (default 60 seconds)
- OTT was already used (Cache::pull is atomic)
- Cache not properly configured

**Solution**:
```bash
# 1. Check cache configuration
php artisan config:show cache

# 2. Ensure cache is working
php artisan tinker
>>> Cache::put('test', 'value', 60)
>>> Cache::get('test')

# 3. Increase OTT TTL if needed (in pdsauthint.php)
'ott_ttl' => 300, // 5 minutes instead of 60 seconds

# 4. Check logs
tail -f storage/logs/laravel.log | grep "Invalid or expired OTT"
```

#### Issue 5: "Customer has no associated agent"

**Error Message**:
```
Customer has no associated agent
```

**In pds-homepage**:
```php
// IdPController checks for agent relationship:
if (!$customer || !$customer->agent) {
    // Error
}

// Solution: Ensure customer has agent relationship
$customer = Customer::findOrFail($id);
$customer->agent()->associate($agent)->save();
```

#### Issue 6: "Service 2 exchange endpoint error"

**Error Message**:
```
Failed to exchange JWT for OTT
Status: 500
Response: [response body]
```

**Solution**:
```bash
# 1. Verify Service 2 is running
curl http://127.0.0.1:8001/api/sso/exchange

# 2. Check network connectivity
ping [service2-host]

# 3. Verify service URL is correct
php artisan config:show pdsauthint | grep service2

# 4. Check Service 2 logs
tail -f riskmanagementv2/storage/logs/laravel.log
```

### Debugging Tips

#### Enable Debug Logging
```bash
# In .env
LOG_LEVEL=debug

# Then check logs
tail -f storage/logs/laravel.log | grep -E "SSO|PdsAuthInt"
```

#### Test Routes
```bash
php artisan route:list | grep sso
php artisan route:list | grep pdsauthint
```

#### Test Configuration
```php
php artisan tinker
>>> config('pdsauthint')
>>> config('pdsauthint.use_env_keys')
>>> strlen(config('pdsauthint.public_key'))
```

#### Test JWT Encoding/Decoding
```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Test encoding
$payload = ['iss' => 'test', 'aud' => 'test'];
$privateKey = config('pdsauthint.private_key');
$jwt = JWT::encode($payload, $privateKey, 'RS256');

// Test decoding
$publicKey = config('pdsauthint.public_key');
$decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));
```

#### Test Cache
```php
$cacheKey = 'sso_ott_test123';
Cache::put($cacheKey, ['test' => 'data'], 60);
$data = Cache::pull($cacheKey); // Retrieves and deletes
Cache::get($cacheKey); // Should return null
```

### Security Testing

#### Test 1: Verify JWT Signature Verification

```bash
# Try to modify JWT and ensure it fails
curl -X POST http://127.0.0.1:8001/api/sso/exchange \
  -H "Content-Type: application/json" \
  -d '{"jwt":"[VALID_JWT_WITH_LAST_CHAR_CHANGED]"}'

# Should return 401 Unauthorized with "JWT signature validation failed"
```

#### Test 2: Verify OTT One-Time Use

```bash
# Get valid OTT from first exchange
# Try to use it again immediately
curl http://127.0.0.1:8001/sso/login?ott=[OTT]
# First request: Success (login and redirect)
# Second request: Failed (OTT consumed)
```

#### Test 3: Verify Expiration Checks

```bash
# Create JWT with exp in the past
$payload = [
    'iss' => 'pds-homepage',
    'aud' => 'riskmanagementv2',
    'exp' => time() - 300, // Expired
    'sub' => '123',
    'customer_id' => 'cust_456',
];

# Try to exchange
# Should return 401 Unauthorized with "Token expired"
```

#### Test 4: Multi-Tenancy Isolation

```php
// Verify unique constraint prevents same customer under different agents
$customer1 = Customer::create([
    'agent_id' => 'agent_1',
    'service1_customer_id' => 'cust_123',
    // ...
]);

$customer2 = Customer::create([
    'agent_id' => 'agent_2',
    'service1_customer_id' => 'cust_123', // Same customer ID
    // ...
]);

// Both should be created (different agents)

$customer3 = Customer::create([
    'agent_id' => 'agent_1',
    'service1_customer_id' => 'cust_123', // Same agent + customer ID
    // ...
]);

// Should fail with unique constraint violation
```

---

## Summary & Checklist

### Implementation Checklist

- [ ] RSA key pair generated or obtained
- [ ] Environment variables configured (PASSPORT_PRIVATE_KEY, PASSPORT_PUBLIC_KEY)
- [ ] SSO configuration files in place (pdsauthint.php)
- [ ] Routes registered (api.php, web.php)
- [ ] Service Providers registered in AppServiceProvider
- [ ] Migration run and SSO fields added to customers table
- [ ] Customer model updated with SSO fields
- [ ] Configuration tested (keys loading correctly)
- [ ] JWT exchange endpoint tested
- [ ] OTT login endpoint tested
- [ ] JIT provisioning tested
- [ ] Multi-tenancy constraints verified
- [ ] Frontend integration completed
- [ ] Logging configured for security audit
- [ ] Production deployment strategy finalized

### What's Ready for Testing

1. **Identity Provider (pds-homepage)**:
   - JWT token creation with RS256 signature
   - Exchange with Service Provider for OTT
   - Integration with existing customer authentication

2. **Service Provider (riskmanagementv2)**:
   - JWT signature validation
   - OTT exchange and caching
   - JIT provisioning (customer creation/update)
   - Seamless login and redirection

3. **Security**:
   - Asymmetric encryption with RS256
   - Claims validation (iss, aud, exp)
   - One-time token pattern
   - Multi-tenant isolation via unique constraint
   - Comprehensive audit logging

### Production Readiness

- [ ] Keys stored in secure Secret Manager (Google Cloud, AWS, etc.)
- [ ] Database backups configured
- [ ] Monitoring and alerting setup (especially for failed SSO attempts)
- [ ] Load testing completed
- [ ] HTTPS enforced for all endpoints
- [ ] CORS configured for cross-domain scenarios
- [ ] Rate limiting enabled on exchange endpoint
- [ ] Customer support documentation prepared

---

## Additional Resources

### Configuration Reference

**pdsauthint.php (Service Provider - riskmanagementv2)**:
```php
[
    'role' => 'sp',
    'public_key' => env('SSO_PUBLIC_KEY') ?: env('PASSPORT_PUBLIC_KEY'),
    'use_env_keys' => true,
    'jwt_issuer' => 'pds-homepage',
    'jwt_audience' => 'riskmanagementv2',
    'ott_ttl' => 60,
    'ott_cache_prefix' => 'sso_ott_',
    'customer_guard' => 'customer',
    'customer_dashboard_route' => 'customer.dashboard',
]
```

**pdsauthint.php (Identity Provider - pds-homepage)**:
```php
[
    'role' => 'idp',
    'private_key' => env('PASSPORT_PRIVATE_KEY'),
    'public_key' => env('PASSPORT_PUBLIC_KEY'),
    'use_env_keys' => true,
    'service2_exchange_url' => env('SSO_SERVICE2_EXCHANGE_URL'),
    'jwt_issuer' => 'pds-homepage',
    'jwt_audience' => 'riskmanagementv2',
    'jwt_ttl' => 300,
    'service2_login_url' => env('SSO_SERVICE2_LOGIN_URL'),
]
```

### Environment Variables Template

```bash
# pds-homepage/.env
PASSPORT_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----
... [key content] ...
-----END RSA PRIVATE KEY-----"

PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
... [key content] ...
-----END PUBLIC KEY-----"

SSO_USE_ENV_KEYS=true
SSO_SERVICE2_EXCHANGE_URL=http://127.0.0.1:8001/api/sso/exchange
SSO_SERVICE2_LOGIN_URL=http://127.0.0.1:8001/sso/login

# riskmanagementv2/.env
PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
... [key content] ...
-----END PUBLIC KEY-----"

SSO_USE_ENV_KEYS=true
```

---

**Document Version**: 1.0
**Last Updated**: November 11, 2025
**Status**: Ready for Testing & Implementation
