# Keycloak User-Synchronisation – Entwickler-Anleitung

Anleitung zur Synchronisierung von Benutzern mit dem Passolution-Keycloak über die **Admin REST API**.
Keine Abhängigkeit zu einer bestimmten Anwendung – funktioniert mit jeder Sprache/Plattform, die HTTP sprechen kann.

---

## 1. Zugangsdaten

Die Admin-REST-API wird über einen **Service-Account-Client** (Machine-to-Machine,
`grant_type=client_credentials`) angesprochen – **nicht** mehr über einen Admin-User
mit Passwort.

| Wert | Beschreibung |
|------|--------------|
| `BASE_URL` | `https://auth.passolution.de` |
| `REALM` | `passolution` |
| `CLIENT_ID` | Service-Account-Client, z.B. `platform-passolution-admin` |
| `CLIENT_SECRET` | (wird separat zugesendet) |

**Hinweis:** Im Export sind `username` und `email` identisch (E-Mail = Username). Diese Konvention sollte beibehalten werden.

### Service-Account-Client einrichten (einmalig, im `passolution`-Realm)

1. **Client anlegen**, z.B. `platform-passolution-admin`:
   - `Client authentication: On` (confidential)
   - `Service accounts roles: On` (= `serviceAccountsEnabled`)
   - Standard-/Direct-Access-Flows können aus bleiben (reiner M2M-Client).
2. **Rollen zuweisen** – Tab *Service account roles* → *Assign role* → Filter `realm-management`:
   - **`manage-users`** – User anlegen, ändern, Passwort setzen, löschen
   - **`view-users`** – User suchen/auflisten (enthält `query-users`)
   - (Alternativ die Composite-Rolle `realm-admin` – für reines User-Management meist überdimensioniert.)
3. **Secret** aus dem Tab *Credentials* kopieren → `CLIENT_SECRET`.

---

## 2. Authentifizierung – Admin-Token holen

Vor jedem API-Aufruf wird ein Access-Token benötigt. Token gilt standardmäßig **60 Sekunden** – also entweder bei jedem Sync-Lauf neu holen oder cachen + bei `401` neu holen.

Das Token wird per **Client-Credentials-Grant** beim **`passolution`-Realm** geholt (nicht mehr beim `master`-Realm).

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

(Client-Credentials liefert kein `refresh_token` – bei Ablauf einfach neu holen.)

→ Den Wert `access_token` für alle folgenden Calls als Header verwenden:
```
Authorization: Bearer <access_token>
```

---

## 3. User anlegen

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
      "value": "StartPasswort123",
      "temporary": false
    }
  ]
}
```

### Response
- **201 Created** – User angelegt, ID steht im `Location`-Header:
  ```
  Location: https://auth.passolution.de/admin/realms/passolution/users/abc-def-123
  ```
  → User-ID = letzter Pfad-Bestandteil (`abc-def-123`)
- **409 Conflict** – Username oder Email existiert bereits
- **400 Bad Request** – ungültiges JSON / Pflichtfeld fehlt
- **401 Unauthorized** – Token abgelaufen → neu holen

### Felder im Detail

| Feld | Pflicht | Beschreibung |
|------|---------|--------------|
| `username` | ja | Bei uns immer = `email` |
| `email` | ja | E-Mail-Adresse |
| `enabled` | nein (default `false`) | **Muss `true` sein**, sonst kann sich der User nicht einloggen |
| `emailVerified` | nein | `true` empfohlen für migrierte User |
| `firstName`, `lastName` | nein | Vor-/Nachname |
| `attributes` | nein | Beliebige Key-Value-Paare (Werte als Array!), z.B. interne IDs |
| `credentials` | nein | Passwort (siehe Abschnitt 5) |

### Variante: bcrypt-Hash statt Klartext-Passwort

Wenn ein bcrypt-Hash aus einer bestehenden DB übernommen werden soll:

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

**Achtung:** Laravel/PHP-Hashes beginnen mit `$2y$` – vor dem Senden ersetzen durch `$2a$`, sonst akzeptiert Keycloak den Hash nicht.

---

## 4. User aktualisieren

### a) User-ID anhand der Email finden

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

→ Leeres Array `[]` = User existiert nicht.

### b) User-Daten ändern

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

Response: **204 No Content** = erfolgreich.

**Wichtig:** Felder, die im Request fehlen, werden **nicht** gelöscht – PUT verhält sich hier wie PATCH. `attributes` ist jedoch ein vollständiger Ersatz: was nicht mitgeschickt wird, ist weg.

---

## 5. Passwort ändern

### Endpoint
```http
PUT https://auth.passolution.de/admin/realms/passolution/users/{user-id}/reset-password
Authorization: Bearer <token>
Content-Type: application/json

{
  "type": "password",
  "value": "NeuesPasswort123",
  "temporary": false
}
```

Response: **204 No Content** = erfolgreich.

| Parameter | Wirkung |
|-----------|---------|
| `temporary: false` | User kann sich direkt mit dem neuen Passwort anmelden |
| `temporary: true` | User muss das Passwort beim nächsten Login selbst neu setzen |

---

## 6. User löschen

```http
DELETE https://auth.passolution.de/admin/realms/passolution/users/{user-id}
Authorization: Bearer <token>
```

Response: **204 No Content**.

---

## 7. Bulk-Sync (Create-or-Update in einem Call)

Für größere Synchronisations-Läufe ist `partialImport` effizienter – ein einziger Call für viele User.

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

### `ifResourceExists`-Optionen

| Wert | Verhalten bei bereits existierendem User |
|------|------------------------------------------|
| `SKIP` | Bestehenden User unverändert lassen |
| `OVERWRITE` | Bestehenden User komplett überschreiben |
| `FAIL` | Mit Fehler abbrechen |

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

## 8. Empfohlener Sync-Workflow

Pro Quelldatensatz (User aus eigener DB):

```
1. Admin-Token holen (oder gecachtes verwenden)
2. GET /users?email=<email>&exact=true
   ├─ Array leer    → POST /users (anlegen)
   └─ Array gefüllt → PUT  /users/{id} (aktualisieren)
3. Wenn Passwort geändert wurde:
   PUT /users/{id}/reset-password
```

**Alternative (effizienter bei vielen Usern):** `partialImport` mit `ifResourceExists: OVERWRITE` und allen Usern im Batch.

---

## 9. Fehler-Handling

| HTTP-Status | Bedeutung | Reaktion |
|-------------|-----------|----------|
| `200`/`201`/`204` | Erfolg | – |
| `400` | Ungültige Daten | JSON prüfen, Pflichtfelder |
| `401` | Token abgelaufen oder ungültig | Neuen Token holen, einmal retryen |
| `403` | Service-Account hat keine Rechte | `realm-management`-Rollen `manage-users` / `view-users` fehlen (siehe §1) |
| `404` | User-ID existiert nicht | – |
| `409` | Username/Email existiert bereits | Auf UPDATE umschalten |
| `5xx` | Server-Fehler | Mit Exponential-Backoff retryen |

Bei `401`: einmal Token erneuern und retryen – nicht in Endlosschleife.

---

## 10. Komplettes Beispiel (curl)

```bash
# 1. Token holen (Service Account / client_credentials)
TOKEN=$(curl -s -X POST \
  "https://auth.passolution.de/realms/passolution/protocol/openid-connect/token" \
  -d "client_id=$CLIENT_ID" \
  -d "client_secret=$CLIENT_SECRET" \
  -d "grant_type=client_credentials" \
  | jq -r .access_token)

# 2. User anlegen
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
# → 201, Location-Header enthält neue User-ID

# 3. User-ID abrufen
USER_ID=$(curl -s \
  "https://auth.passolution.de/admin/realms/passolution/users?email=max@example.com&exact=true" \
  -H "Authorization: Bearer $TOKEN" \
  | jq -r '.[0].id')

# 4. Passwort ändern
curl -i -X PUT \
  "https://auth.passolution.de/admin/realms/passolution/users/$USER_ID/reset-password" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"type":"password","value":"NeuesPasswort","temporary":false}'
# → 204
```

---

## 11. Wichtige Hinweise

- **`username` ist nicht änderbar** über `PUT /users/{id}` (offiziell nur falls Realm `editUsernameAllowed=true` hat). Bei Email-Änderung wird `username` separat behandelt.
- **`duplicateEmailsAllowed: false`** im Realm – pro Email nur ein User.
- **`loginWithEmailAllowed: true`** – Login funktioniert mit Username **oder** Email.
- **Token-Lebensdauer ist kurz** (60 s). Bei längeren Sync-Läufen vor jedem Batch neu holen oder `expires_in` auswerten.
- **Rate-Limiting:** Keycloak hat default keine harten Limits, aber Bulk-Operationen besser über `partialImport` als 1000 einzelne Calls.
- **Attribute-Werte** müssen immer **Arrays** sein, auch bei nur einem Wert: `"platform_customer_id": ["123"]`, nicht `"platform_customer_id": "123"`.

---

## 12. Weiterführende Dokumentation

- Offizielle Keycloak Admin REST API: https://www.keycloak.org/docs-api/latest/rest-api/index.html
- User-Endpoints insbesondere: Suche nach `UserRepresentation` und `CredentialRepresentation` in der Doku.
