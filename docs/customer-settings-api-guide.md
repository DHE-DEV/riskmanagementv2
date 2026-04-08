# Customer Settings API – Kundenanleitung

## Übersicht

Die Customer Settings API ermöglicht die Verwaltung von Kundenstammdaten, Niederlassungen/Adressen, Kontaktinformationen (Rufnummern, E-Mail-Adressen, Webseiten), Organisationsstrukturen, Abteilungen, Benutzer (Mitarbeiter) und Benutzergruppen. Alle Änderungen werden sofort wirksam.

---

## Authentifizierung

Alle API-Aufrufe erfordern einen **Bearer-Token** im HTTP-Header:

```
Authorization: Bearer {API_TOKEN}
```

### Token generieren

Der Token wird über die Web-Oberfläche generiert (erfordert eine aktive Session):

```
POST /customer/api-tokens/generate
```

**Response:**

```json
{
  "success": true,
  "token": "2|RHej0fNgjGSzvPrEcSuY7nMGI7fldCnOMoBrpl2T173373b5",
  "message": "API Token erfolgreich generiert"
}
```

> **Wichtig:** Speichern Sie den Token sicher ab. Er wird nur einmal im Klartext angezeigt.

---

## Base-URL

```
https://platform.passolution.de/api
```

---

## 1. Stammdaten (Master Data)

### Firmendaten abrufen

```
GET /v1/customer/settings
```

Gibt die vollständigen Kundenstammdaten zurück, inklusive Firmenanschrift, Rechnungsadresse, Kundentyp und Branchentyp.

**Beispiel:**

```bash
curl -X GET https://platform.passolution.de/api/v1/customer/settings \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "company_name": "Musterfirma GmbH",
    "customer_type": "business",
    "business_type": "travel_agency",
    "company_address": {
      "name": "Musterfirma GmbH",
      "street": "Musterstraße",
      "house_number": "1",
      "postal_code": "80331",
      "city": "München",
      "country": "DE"
    },
    "billing_address": {
      "name": "Musterfirma GmbH – Buchhaltung",
      "street": "Musterstraße",
      "house_number": "1",
      "postal_code": "80331",
      "city": "München",
      "country": "DE"
    }
  }
}
```

---

### Firmenanschrift aktualisieren

```
PUT /v1/customer/settings/company-address
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `name` | string | Ja | Firmenname |
| `additional` | string | Nein | Adresszusatz |
| `street` | string | Ja | Straße |
| `house_number` | string | Nein | Hausnummer |
| `postal_code` | string | Ja | Postleitzahl |
| `city` | string | Ja | Stadt |
| `country` | string | Ja | Ländercode (ISO alpha-2, z.B. `DE`) |

**Beispiel:**

```bash
curl -X PUT https://platform.passolution.de/api/v1/customer/settings/company-address \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Musterfirma GmbH",
    "street": "Neue Straße",
    "house_number": "42",
    "postal_code": "80333",
    "city": "München",
    "country": "DE"
  }'
```

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Firmenanschrift erfolgreich aktualisiert"
}
```

> **Hinweis:** Die Firmenanschrift kann über die API aktualisiert, aber nicht gelöscht werden.

---

### Rechnungsadresse aktualisieren

```
PUT /v1/customer/settings/billing-address
```

Die Felder sind identisch zur Firmenanschrift (siehe oben).

> **Hinweis:** Die Rechnungsadresse kann über die API aktualisiert, aber nicht gelöscht werden.

---

### Kundentyp ändern

```
PUT /v1/customer/settings/customer-type
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `customer_type` | string | Ja | `private` oder `business` |

---

### Branchentyp ändern

```
PUT /v1/customer/settings/business-type
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `business_type` | string | Ja | Branchentyp (z.B. `travel_agency`, `corporate`, `insurance`) |

---

## 2. Adressen (Branches)

### Alle Adressen auflisten

```
GET /v1/customer/branches
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `search` | string | Volltextsuche über Name, Straße, Stadt |
| `city` | string | Filterung nach Stadt |

**Beispiel:**

```bash
curl -X GET "https://platform.passolution.de/api/v1/customer/branches?city=München" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"
```

---

### Einzelne Adresse abrufen

```
GET /v1/customer/branches/{id}
```

---

### Neue Adresse anlegen

```
POST /v1/customer/branches
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `name` | string | Ja | Name der Niederlassung |
| `additional` | string | Nein | Adresszusatz |
| `street` | string | Ja | Straße |
| `house_number` | string | Nein | Hausnummer |
| `postal_code` | string | Ja | Postleitzahl |
| `city` | string | Ja | Stadt |
| `country` | string | Ja | Ländercode (ISO alpha-2, z.B. `DE`) |
| `org_node_ids` | array | Nein | IDs bestehender Organisationsknoten |
| `org_node_data` | array | Nein | Daten für neue Organisationsknoten |

**Beispiel:**

```bash
curl -X POST https://platform.passolution.de/api/v1/customer/branches \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Niederlassung Hamburg",
    "street": "Jungfernstieg",
    "house_number": "10",
    "postal_code": "20095",
    "city": "Hamburg",
    "country": "DE"
  }'
```

**Response (201 Created):**

```json
{
  "success": true,
  "data": {
    "id": "019bef38-a1b2-c3d4-e5f6-789012345678",
    "name": "Niederlassung Hamburg",
    "street": "Jungfernstieg",
    "house_number": "10",
    "postal_code": "20095",
    "city": "Hamburg",
    "country": "DE"
  }
}
```

---

### Adresse aktualisieren

```
PUT /v1/customer/branches/{id}
```

Die Felder sind identisch zur Erstellung (siehe oben).

---

### Adresse löschen

```
DELETE /v1/customer/branches/{id}
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `scheduled_deletion_at` | datetime | Optionales Datum für geplante Löschung (YYYY-MM-DD HH:MM:SS) |

> **Hinweis:** Firmenanschrift und Hauptsitz-Adressen (`is_headquarters=true`) können nicht gelöscht werden.

---

### Geplante Löschung abbrechen

```
POST /v1/customer/branches/{id}/cancel-deletion
```

Bricht eine zuvor geplante Löschung ab.

---

## 3. Rufnummern (Phone Numbers)

### Alle Rufnummern auflisten

```
GET /v1/customer/phone-numbers
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `branch_id` | string | Filterung nach Niederlassung |

---

### Rufnummer anlegen

```
POST /v1/customer/phone-numbers
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `label` | string | Ja | Bezeichnung (z.B. "Zentrale", "Notfall") |
| `number` | string | Ja | Rufnummer |
| `type` | string | Ja | `phone`, `mobile` oder `fax` |
| `is_primary` | boolean | Nein | Als primäre Rufnummer setzen (Standard: `false`) |
| `notes` | string | Nein | Notizen |
| `department_id` | string | Nein | Zugehörige Abteilung |
| `branch_id` | string | Nein | Zugehörige Niederlassung |

> **Hinweis:** Wenn `is_primary` auf `true` gesetzt wird, werden alle anderen Rufnummern automatisch auf nicht-primär zurückgesetzt.

---

### Rufnummer aktualisieren

```
PUT /v1/customer/phone-numbers/{id}
```

Die Felder sind identisch zur Erstellung.

---

### Rufnummer löschen

```
DELETE /v1/customer/phone-numbers/{id}
```

---

### Sortierung ändern

```
POST /v1/customer/phone-numbers/reorder
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `ids` | array | Ja | Geordnete Liste der Rufnummern-IDs |

---

## 4. E-Mail-Adressen

### Alle E-Mail-Adressen auflisten

```
GET /v1/customer/email-addresses
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `branch_id` | string | Filterung nach Niederlassung |

---

### E-Mail-Adresse anlegen

```
POST /v1/customer/email-addresses
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `label` | string | Ja | Bezeichnung (z.B. "Allgemein", "Buchhaltung") |
| `email` | string | Ja | E-Mail-Adresse |
| `is_primary` | boolean | Nein | Als primäre E-Mail setzen (Standard: `false`) |
| `notes` | string | Nein | Notizen |
| `department_id` | string | Nein | Zugehörige Abteilung |
| `branch_id` | string | Nein | Zugehörige Niederlassung |

> **Hinweis:** Wenn `is_primary` auf `true` gesetzt wird, werden alle anderen E-Mail-Adressen automatisch auf nicht-primär zurückgesetzt.

---

### E-Mail-Adresse aktualisieren

```
PUT /v1/customer/email-addresses/{id}
```

Die Felder sind identisch zur Erstellung.

---

### E-Mail-Adresse löschen

```
DELETE /v1/customer/email-addresses/{id}
```

---

## 5. Webseiten

### Alle Webseiten auflisten

```
GET /v1/customer/websites
```

---

### Webseite anlegen

```
POST /v1/customer/websites
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `label` | string | Ja | Bezeichnung (z.B. "Homepage", "Online-Shop") |
| `url` | string | Ja | URL der Webseite |
| `is_primary` | boolean | Nein | Als primäre Webseite setzen (Standard: `false`) |
| `notes` | string | Nein | Notizen |
| `branch_id` | string | Nein | Zugehörige Niederlassung |

> **Hinweis:** Wenn `is_primary` auf `true` gesetzt wird, werden alle anderen Webseiten automatisch auf nicht-primär zurückgesetzt.

---

### Webseite aktualisieren

```
PUT /v1/customer/websites/{id}
```

Die Felder sind identisch zur Erstellung.

---

### Webseite löschen

```
DELETE /v1/customer/websites/{id}
```

---

## 6. Ansprechpartner (Branch Contacts)

### Ansprechpartner auflisten

```
GET /v1/customer/branch-contacts
```

**Query-Parameter:**

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|--------------|
| `branch_id` | string | Ja | Niederlassungs-ID (Pflichtparameter) |

**Beispiel:**

```bash
curl -X GET "https://platform.passolution.de/api/v1/customer/branch-contacts?branch_id=019bef38-a1b2-c3d4-e5f6-789012345678" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"
```

---

### Ansprechpartner anlegen

```
POST /v1/customer/branch-contacts
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `branch_id` | string | Ja | Zugehörige Niederlassung |
| `salutation` | string | Nein | Anrede |
| `title` | string | Nein | Titel (z.B. "Dr.", "Prof.") |
| `first_name` | string | Ja | Vorname |
| `last_name` | string | Ja | Nachname |
| `function` | string | Nein | Funktion/Position |
| `department` | string | Nein | Abteilung |
| `phone` | string | Nein | Telefonnummer |
| `mobile` | string | Nein | Mobilnummer |
| `fax` | string | Nein | Faxnummer |
| `email` | string | Nein | E-Mail-Adresse |
| `notes` | string | Nein | Notizen |

---

### Ansprechpartner aktualisieren

```
PUT /v1/customer/branch-contacts/{id}
```

Die Felder sind identisch zur Erstellung (ohne `branch_id`).

---

### Ansprechpartner löschen

```
DELETE /v1/customer/branch-contacts/{id}
```

---

## 7. Organisationsstruktur (Org Nodes)

### Hierarchische Struktur abrufen

```
GET /v1/customer/org-nodes
```

Gibt die gesamte Organisationsstruktur als hierarchischen Baum zurück.

---

### Einzelnen Knoten abrufen

```
GET /v1/customer/org-nodes/{id}
```

---

### Knoten erstellen

```
POST /v1/customer/org-nodes
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `name` | string | Ja | Name des Knotens |
| `code` | string | Nein | Kurzcode (z.B. "DE-MUC") |
| `relation_label` | string | Nein | Beziehungsbezeichnung |
| `description` | string | Nein | Beschreibung |
| `parent_id` | string | Nein | ID des übergeordneten Knotens |
| `after_id` | string | Nein | ID des vorhergehenden Geschwisterknotens (für Sortierung) |
| `color` | string | Nein | Farbcode (z.B. `#FF5733`) |

**Beispiel:**

```bash
curl -X POST https://platform.passolution.de/api/v1/customer/org-nodes \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Region Süd",
    "code": "REG-SUED",
    "description": "Alle Niederlassungen in Süddeutschland",
    "parent_id": "019bef38-0000-0000-0000-000000000001",
    "color": "#2196F3"
  }'
```

**Response (201 Created):**

```json
{
  "success": true,
  "data": {
    "id": "019bef38-a1b2-c3d4-e5f6-000000000099",
    "name": "Region Süd",
    "code": "REG-SUED",
    "description": "Alle Niederlassungen in Süddeutschland",
    "parent_id": "019bef38-0000-0000-0000-000000000001",
    "color": "#2196F3"
  }
}
```

---

### Knoten aktualisieren

```
PUT /v1/customer/org-nodes/{id}
```

Die Felder sind identisch zur Erstellung.

---

### Knoten löschen

```
DELETE /v1/customer/org-nodes/{id}
```

> **Hinweis:** Beim Löschen eines Knotens werden alle Unterknoten ebenfalls gelöscht.

---

### Sortierung ändern

```
POST /v1/customer/org-nodes/reorder
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `ids` | array | Ja | Geordnete Liste der Knoten-IDs |

---

### Knoten verschieben

```
POST /v1/customer/org-nodes/{id}/move
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `parent_id` | string | Nein | Neue übergeordnete Knoten-ID (`null` für Wurzelebene) |
| `after_id` | string | Nein | ID des vorhergehenden Geschwisterknotens |

---

## 8. Abteilungen (Departments)

### Alle Abteilungen auflisten

```
GET /v1/customer/departments
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `is_active` | boolean | Filterung nach Status (`true`/`false`) |

---

### Abteilung anlegen

```
POST /v1/customer/departments
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `name` | string | Ja | Name der Abteilung |
| `description` | string | Nein | Beschreibung |
| `code` | string | Nein | Kurzcode |
| `is_active` | boolean | Nein | Aktiv-Status (Standard: `true`) |

---

### Abteilung aktualisieren

```
PUT /v1/customer/departments/{id}
```

Die Felder sind identisch zur Erstellung.

---

### Abteilung löschen

```
DELETE /v1/customer/departments/{id}
```

---

### Sortierung ändern

```
POST /v1/customer/departments/reorder
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `ids` | array | Ja | Geordnete Liste der Abteilungs-IDs |

---

## 9. Benutzer (Employees)

### Alle Benutzer auflisten

```
GET /v1/customer/employees
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `search` | string | Volltextsuche über Name, E-Mail, Personalnummer |
| `branch_id` | string | Filterung nach Niederlassung |
| `department_id` | string | Filterung nach Abteilung |
| `group_id` | string | Filterung nach Benutzergruppe |
| `is_active` | boolean | Filterung nach Status (`true`/`false`) |
| `per_page` | integer | Einträge pro Seite (Standard: 50, Maximum: 200, `0` = alle) |

**Beispiel:**

```bash
curl -X GET "https://platform.passolution.de/api/v1/customer/employees?search=Müller&is_active=true&per_page=25" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "id": "019bef38-a1b2-c3d4-e5f6-789012345678",
      "salutation": "herr",
      "first_name": "Thomas",
      "last_name": "Müller",
      "email": "t.mueller@musterfirma.de",
      "phone": "+49 89 12345678",
      "position": "Reiseberater",
      "department": "Vertrieb",
      "personnel_number": "P-1001",
      "is_active": true,
      "branch_id": "019bef38-0000-0000-0000-000000000001",
      "group_ids": [
        "019bef38-0000-0000-0000-000000000010"
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 1
  }
}
```

---

### Einzelnen Benutzer abrufen

```
GET /v1/customer/employees/{id}
```

---

### Benutzer anlegen

```
POST /v1/customer/employees
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `salutation` | string | Nein | `herr`, `frau` oder `divers` |
| `title` | string | Nein | Titel (z.B. "Dr.", "Prof.") |
| `first_name` | string | Ja | Vorname |
| `last_name` | string | Ja | Nachname |
| `email` | string | Nein | E-Mail-Adresse |
| `phone` | string | Nein | Telefonnummer |
| `mobile` | string | Nein | Mobilnummer |
| `position` | string | Nein | Position/Funktion |
| `department` | string | Nein | Abteilung (Freitext) |
| `department_id` | string | Nein | Zugehörige Abteilungs-ID |
| `personnel_number` | string | Nein | Personalnummer |
| `branch_id` | string | Nein | Zugehörige Niederlassung |
| `is_active` | boolean | Nein | Aktiv-Status (Standard: `true`) |
| `notes` | string | Nein | Notizen |
| `active_from` | date | Nein | Aktiv ab (YYYY-MM-DD) |
| `active_until` | date | Nein | Aktiv bis (YYYY-MM-DD) |
| `group_ids` | array | Nein | Liste von Benutzergruppen-IDs |

---

### Benutzer aktualisieren

```
PUT /v1/customer/employees/{id}
```

Die Felder sind identisch zur Erstellung.

---

### Benutzer löschen

```
DELETE /v1/customer/employees/{id}
```

---

## 10. Benutzergruppen (Employee Groups)

### Alle Benutzergruppen auflisten

```
GET /v1/customer/employee-groups
```

---

### Benutzergruppe anlegen

```
POST /v1/customer/employee-groups
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `name` | string | Ja | Name der Gruppe |
| `description` | string | Nein | Beschreibung |

---

### Benutzergruppe aktualisieren

```
PUT /v1/customer/employee-groups/{id}
```

Die Felder sind identisch zur Erstellung.

> **Hinweis:** Systemgruppen (`is_system=true`) können nicht bearbeitet werden.

---

### Benutzergruppe löschen

```
DELETE /v1/customer/employee-groups/{id}
```

> **Hinweis:** Systemgruppen (`is_system=true`) können nicht gelöscht werden.

---

## Fehlerbehandlung

| HTTP-Code | Bedeutung |
|-----------|-----------|
| `200` | Anfrage erfolgreich |
| `201` | Ressource erfolgreich erstellt |
| `401` | Nicht authentifiziert (Token fehlt oder ungültig) |
| `403` | Keine Berechtigung für diese Aktion |
| `404` | Ressource nicht gefunden |
| `422` | Validierungsfehler (ungültige Daten) |
| `429` | Zu viele Anfragen (Rate Limit überschritten) |

**Beispiel Authentifizierungsfehler (401):**

```json
{
  "message": "Unauthenticated."
}
```

**Beispiel Validierungsfehler (422):**

```json
{
  "success": false,
  "errors": {
    "name": ["The name field is required."],
    "street": ["The street field is required."],
    "city": ["The city field is required."]
  }
}
```

**Beispiel Nicht gefunden (404):**

```json
{
  "success": false,
  "message": "Resource not found."
}
```

**Beispiel Keine Berechtigung (403):**

```json
{
  "success": false,
  "message": "Systemgruppen können nicht gelöscht werden."
}
```

**Beispiel Rate Limit (429):**

```json
{
  "message": "Too Many Attempts."
}
```

---

## Hinweise

- **Firmenanschrift und Rechnungsadresse** können über die API aktualisiert, aber nicht gelöscht werden
- **Hauptsitz-Adressen** (`is_headquarters=true`) können nicht gelöscht werden
- **Systemgruppen** (`is_system=true`) können nicht bearbeitet oder gelöscht werden
- **Primär-Markierung:** Bei Rufnummern, E-Mail-Adressen und Webseiten gilt: Wenn `is_primary` auf `true` gesetzt wird, werden alle anderen Einträge desselben Typs automatisch auf nicht-primär zurückgesetzt
- **Geplante Löschung:** Adressen können mit `scheduled_deletion_at` zur Löschung vorgemerkt werden. Die geplante Löschung kann jederzeit über den `cancel-deletion`-Endpunkt abgebrochen werden
- **Pagination:** Bei Benutzern kann mit `per_page=0` die Pagination deaktiviert werden, um alle Einträge auf einmal abzurufen (maximal 200 pro Seite, `0` = alle)

---

## Support

Bei Fragen zur API wenden Sie sich an Ihren Ansprechpartner bei Passolution.
