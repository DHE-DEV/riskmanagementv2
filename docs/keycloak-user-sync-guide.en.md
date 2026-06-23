# Keycloak User Synchronization – Developer Guide

Guide for synchronizing users with the Passolution Keycloak via the **Admin REST API**.
No dependency on any specific application – works with any language/platform that can speak HTTP.

---

## 1. Access Credentials

The Admin REST API is accessed via a **service-account client** (machine-to-machine,
`grant_type=client_credentials`) – **no longer** via an admin user with a password.

| Value | Description |
|-------|-------------|
| `BASE_URL` | `https://auth.passolution.de` |
| `REALM` | `passolution` |
| `CLIENT_ID` | Service-account client, e.g. `platform-passolution-admin` |
| `CLIENT_SECRET` | (will be provided separately) |

**Note:** In the export, `username` and `email` are identical (email = username). This convention should be kept.

### Setting up the service-account client (one-time, in the `passolution` realm)

1. **Create a client**, e.g. `platform-passolution-admin`:
   - `Client authentication: On` (confidential)
   - `Service accounts roles: On` (= `serviceAccountsEnabled`)
   - Standard/direct-access flows can stay off (pure M2M client).
2. **Assign roles** – *Service account roles* tab → *Assign role* → filter `realm-management`:
   - **`manage-users`** – create, update, set password, delete users
   - **`view-users`** – search/list users (includes `query-users`)
   - (Alternatively the composite role `realm-admin` – usually overkill for plain user management.)
3. **Copy the secret** from the *Credentials* tab → `CLIENT_SECRET`.

---

## 2. Authentication – Obtain Admin Token

An access token is required before every API call. Token is valid for **60 seconds by default** – either fetch a new one on each sync run, or cache it + refresh on `401`.

The token is obtained via the **client-credentials grant** against the **`passolution` realm** (no longer the `master` realm).

### Request
```http
POST https://auth.passolution.de/realms/passolution/protocol/openid-connect/token
Content-Type: application/x-www-form-urlencoded

client_id=<CLIENT_ID>
&client_secret=<CLIENT_SECRET>
&grant_type=client_credentials
```

### Response (200 OK)
```json
{
  "access_token": "eyJhbGciOi...",
  "expires_in": 60,
  "token_type": "Bearer"
}
```

(Client credentials returns no `refresh_token` – just fetch a new one when it expires.)

→ Use the `access_token` value for all subsequent calls as a header:
```
Authorization: Bearer <access_token>
```

---

## 3. Create User

### Request
```http
POST https://auth.passolution.de/admin/realms/passolution/users
Authorization: Bearer <token>
Content-Type: application/json

{
  "username": "max@example.com",
  "email": "max@example.com",
  "emailVerified": true,
  "enabled": true,
  "firstName": "Max",
  "lastName": "Mustermann",
  "attributes": {
    "platform_customer_id": ["123"]
  },
  "credentials": [
    {
      "type": "password",
      "value": "StartPassword123",
      "temporary": false
    }
  ]
}
```

### Response
- **201 Created** – User created, ID is in the `Location` header:
  ```
  Location: https://auth.passolution.de/admin/realms/passolution/users/abc-def-123
  ```
  → User ID = last path segment (`abc-def-123`)
- **409 Conflict** – Username or email already exists
- **400 Bad Request** – Invalid JSON / required field missing
- **401 Unauthorized** – Token expired → fetch a new one

### Fields in Detail

| Field | Required | Description |
|-------|----------|-------------|
| `username` | yes | We always use the same value as `email` |
| `email` | yes | Email address |
| `enabled` | no (default `false`) | **Must be `true`**, otherwise the user cannot log in |
| `emailVerified` | no | `true` recommended for migrated users |
| `firstName`, `lastName` | no | First and last name |
| `attributes` | no | Arbitrary key-value pairs (values as arrays!), e.g. internal IDs |
| `credentials` | no | Password (see section 5) |

### Variant: bcrypt hash instead of plaintext password

If a bcrypt hash from an existing database should be used:

```json
"credentials": [
  {
    "type": "password",
    "hashedSaltedValue": "$2a$10$....",
    "algorithm": "bcrypt",
    "hashIterations": 10
  }
]
```

**Caution:** Laravel/PHP hashes start with `$2y$` – before sending, replace with `$2a$`, otherwise Keycloak will reject the hash.

---

## 4. Update User

### a) Find user ID by email

```http
GET https://auth.passolution.de/admin/realms/passolution/users?email=max@example.com&exact=true
Authorization: Bearer <token>
```

Response (200 OK):
```json
[
  {
    "id": "abc-def-123",
    "username": "max@example.com",
    "email": "max@example.com",
    "firstName": "Max",
    "lastName": "Mustermann",
    "enabled": true,
    ...
  }
]
```

→ Empty array `[]` = user does not exist.

### b) Update user data

```http
PUT https://auth.passolution.de/admin/realms/passolution/users/abc-def-123
Authorization: Bearer <token>
Content-Type: application/json

{
  "firstName": "Maximilian",
  "lastName": "Mustermann",
  "email": "max@example.com",
  "enabled": true,
  "attributes": {
    "platform_customer_id": ["123"],
    "department": ["Sales"]
  }
}
```

Response: **204 No Content** = successful.

**Important:** Fields not present in the request are **not** deleted – PUT behaves like PATCH here. `attributes`, however, is a full replacement: anything not sent will be gone.

---

## 5. Change Password

### Endpoint
```http
PUT https://auth.passolution.de/admin/realms/passolution/users/{user-id}/reset-password
Authorization: Bearer <token>
Content-Type: application/json

{
  "type": "password",
  "value": "NewPassword123",
  "temporary": false
}
```

Response: **204 No Content** = successful.

| Parameter | Effect |
|-----------|--------|
| `temporary: false` | User can log in directly with the new password |
| `temporary: true` | User must set a new password on next login |

---

## 6. Delete User

```http
DELETE https://auth.passolution.de/admin/realms/passolution/users/{user-id}
Authorization: Bearer <token>
```

Response: **204 No Content**.

---

## 7. Bulk Sync (Create-or-Update in one call)

For larger synchronization runs, `partialImport` is more efficient – one single call for many users.

```http
POST https://auth.passolution.de/admin/realms/passolution/partialImport
Authorization: Bearer <token>
Content-Type: application/json

{
  "ifResourceExists": "SKIP",
  "users": [
    {
      "username": "user1@example.com",
      "email": "user1@example.com",
      "enabled": true,
      "emailVerified": true,
      "firstName": "User",
      "lastName": "One",
      "attributes": { "platform_customer_id": ["1"] },
      "credentials": [
        {
          "type": "password",
          "hashedSaltedValue": "$2a$10$....",
          "algorithm": "bcrypt",
          "hashIterations": 10
        }
      ]
    },
    { "username": "user2@example.com", "email": "user2@example.com", ... }
  ]
}
```

### `ifResourceExists` options

| Value | Behavior when user already exists |
|-------|-----------------------------------|
| `SKIP` | Leave existing user unchanged |
| `OVERWRITE` | Completely overwrite existing user |
| `FAIL` | Abort with an error |

### Response (200 OK)
```json
{
  "overwritten": 0,
  "added": 2,
  "skipped": 0,
  "results": [
    { "action": "ADDED", "resourceType": "USER", "resourceName": "user1@example.com", "id": "abc-123" },
    { "action": "ADDED", "resourceType": "USER", "resourceName": "user2@example.com", "id": "def-456" }
  ]
}
```

---

## 8. Recommended Sync Workflow

For each source record (user from your own database):

```
1. Obtain admin token (or use cached one)
2. GET /users?email=<email>&exact=true
   ├─ Array empty   → POST /users (create)
   └─ Array filled  → PUT  /users/{id} (update)
3. If the password has changed:
   PUT /users/{id}/reset-password
```

**Alternative (more efficient for many users):** Use `partialImport` with `ifResourceExists: OVERWRITE` and send all users as a batch.

---

## 9. Error Handling

| HTTP Status | Meaning | Response |
|-------------|---------|----------|
| `200`/`201`/`204` | Success | – |
| `400` | Invalid data | Check JSON, required fields |
| `401` | Token expired or invalid | Fetch new token, retry once |
| `403` | Service account lacks permissions | `realm-management` roles `manage-users` / `view-users` missing (see §1) |
| `404` | User ID does not exist | – |
| `409` | Username/email already exists | Switch to UPDATE |
| `5xx` | Server error | Retry with exponential backoff |

On `401`: refresh token once and retry – do not loop indefinitely.

---

## 10. Complete Example (curl)

```bash
# 1. Get token (service account / client_credentials)
TOKEN=$(curl -s -X POST \
  "https://auth.passolution.de/realms/passolution/protocol/openid-connect/token" \
  -d "client_id=$CLIENT_ID" \
  -d "client_secret=$CLIENT_SECRET" \
  -d "grant_type=client_credentials" \
  | jq -r .access_token)

# 2. Create user
curl -i -X POST \
  "https://auth.passolution.de/admin/realms/passolution/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "max@example.com",
    "email": "max@example.com",
    "enabled": true,
    "emailVerified": true,
    "firstName": "Max",
    "lastName": "Mustermann",
    "credentials": [{"type":"password","value":"Start123","temporary":false}]
  }'
# → 201, Location header contains the new user ID

# 3. Fetch user ID
USER_ID=$(curl -s \
  "https://auth.passolution.de/admin/realms/passolution/users?email=max@example.com&exact=true" \
  -H "Authorization: Bearer $TOKEN" \
  | jq -r '.[0].id')

# 4. Change password
curl -i -X PUT \
  "https://auth.passolution.de/admin/realms/passolution/users/$USER_ID/reset-password" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"type":"password","value":"NewPassword","temporary":false}'
# → 204
```

---

## 11. Important Notes

- **`username` is not editable** via `PUT /users/{id}` (officially only if the realm has `editUsernameAllowed=true`). When the email changes, `username` is handled separately.
- **`duplicateEmailsAllowed: false`** in the realm – only one user per email.
- **`loginWithEmailAllowed: true`** – login works with username **or** email.
- **Token lifetime is short** (60 s). For longer sync runs, fetch a new token before each batch or evaluate `expires_in`.
- **Rate limiting:** Keycloak has no hard limits by default, but for bulk operations prefer `partialImport` over 1000 individual calls.
- **Attribute values** must always be **arrays**, even for a single value: `"platform_customer_id": ["123"]`, not `"platform_customer_id": "123"`.

---

## 12. Further Documentation

- Official Keycloak Admin REST API: https://www.keycloak.org/docs-api/latest/rest-api/index.html
- Particularly relevant endpoints: search for `UserRepresentation` and `CredentialRepresentation` in the docs.
