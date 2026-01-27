# Global Travel Monitor (GTM) API - Dokumentation

## Einleitung

Die Global Travel Monitor API stellt aktuelle Sicherheits- und Reiserisiko-Ereignisse weltweit als JSON bereit. Sie ermöglicht das Abrufen von Ereignissen mit Filtern nach Priorität, Land, Ereignistyp und Region sowie eine Länderübersicht mit Anzahl aktiver Ereignisse.

**Basis-URL:** `https://global-travel-monitor.eu/api/v1/gtm`

---

## Authentifizierung

Alle Endpunkte erfordern einen gültigen API-Token. Dieser wird vom Administrator bereitgestellt.

Der Token muss als Bearer-Token im `Authorization`-Header mitgesendet werden:

```
Authorization: Bearer IHR_API_TOKEN
```

### Beispiel mit cURL

```bash
curl -H "Authorization: Bearer IHR_API_TOKEN" \
     https://global-travel-monitor.eu/api/v1/gtm/events
```

---

## Rate Limiting

API-Anfragen unterliegen einem Rate-Limit. Bei Überschreitung wird eine `429`-Antwort zurückgegeben. Der `Retry-After`-Header enthält die Anzahl Sekunden bis zur nächsten erlaubten Anfrage.

---

## Endpunkte

### 1. Ereignisse auflisten

```
GET /api/v1/gtm/events
```

Gibt eine paginierte Liste von Sicherheits- und Reiseereignissen zurück, sortiert nach Erstellungsdatum (neueste zuerst).

#### Filter-Parameter (alle optional)

| Parameter   | Typ     | Beschreibung                                    | Beispiel           |
|-------------|---------|------------------------------------------------|--------------------|
| `priority`  | String  | Prioritätsstufe: `high`, `medium`, `low`, `info` | `high`            |
| `country`   | String  | ISO 3166-1 Alpha-2 Ländercode                   | `DE`              |
| `event_type`| String  | Ereignistyp-Code                                | `natural_disaster` |
| `region`    | Integer | Regions-ID                                      | `1`               |
| `per_page`  | Integer | Ergebnisse pro Seite (1-100, Standard: 25)      | `50`              |
| `page`      | Integer | Seitennummer (Standard: 1)                      | `2`               |

#### Beispiel-Anfrage

```bash
# Alle Ereignisse mit hoher Priorität in Deutschland, 10 pro Seite
curl -H "Authorization: Bearer IHR_API_TOKEN" \
     "https://global-travel-monitor.eu/api/v1/gtm/events?priority=high&country=DE&per_page=10"
```

#### Beispiel-Antwort

```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "title": "Earthquake in Turkey",
      "description": "A 6.2 magnitude earthquake struck southeastern Turkey.",
      "priority": "high",
      "start_date": "2025-03-15T08:30:00Z",
      "end_date": null,
      "latitude": 37.7749,
      "longitude": 35.3214,
      "event_types": [
        {
          "code": "natural_disaster",
          "name": "Natural Disaster",
          "color": "#e74c3c",
          "icon": "earthquake"
        }
      ],
      "event_type": {
        "code": "natural_disaster",
        "name": "Natural Disaster",
        "color": "#e74c3c",
        "icon": "earthquake"
      },
      "category": {
        "id": 2,
        "name": "Naturkatastrophe",
        "color": "#e74c3c"
      },
      "countries": [
        {
          "iso_code": "TR",
          "iso3_code": "TUR",
          "name": "Tuerkei",
          "name_en": "Turkey",
          "continent": "Asia"
        }
      ],
      "country": {
        "iso_code": "TR",
        "iso3_code": "TUR",
        "name": "Tuerkei",
        "name_en": "Turkey",
        "continent": "Asia"
      },
      "created_at": "2025-03-15T09:00:00Z",
      "updated_at": "2025-03-15T10:15:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 142,
    "last_page": 6
  }
}
```

---

### 2. Einzelnes Ereignis abrufen

```
GET /api/v1/gtm/events/{id}
```

Gibt ein einzelnes Ereignis anhand seiner UUID zurück.

#### Beispiel-Anfrage

```bash
curl -H "Authorization: Bearer IHR_API_TOKEN" \
     https://global-travel-monitor.eu/api/v1/gtm/events/550e8400-e29b-41d4-a716-446655440000
```

#### Beispiel-Antwort

```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Earthquake in Turkey",
    "description": "A 6.2 magnitude earthquake struck southeastern Turkey.",
    "priority": "high",
    "start_date": "2025-03-15T08:30:00Z",
    "end_date": null,
    "latitude": 37.7749,
    "longitude": 35.3214,
    "event_types": [
      {
        "code": "natural_disaster",
        "name": "Natural Disaster",
        "color": "#e74c3c",
        "icon": "earthquake"
      }
    ],
    "event_type": {
      "code": "natural_disaster",
      "name": "Natural Disaster",
      "color": "#e74c3c",
      "icon": "earthquake"
    },
    "category": {
      "id": 2,
      "name": "Naturkatastrophe",
      "color": "#e74c3c"
    },
    "countries": [
      {
        "iso_code": "TR",
        "iso3_code": "TUR",
        "name": "Tuerkei",
        "name_en": "Turkey",
        "continent": "Asia"
      }
    ],
    "country": {
      "iso_code": "TR",
      "iso3_code": "TUR",
      "name": "Tuerkei",
      "name_en": "Turkey",
      "continent": "Asia"
    },
    "created_at": "2025-03-15T09:00:00Z",
    "updated_at": "2025-03-15T10:15:00Z"
  }
}
```

---

### 3. Länder mit aktiven Ereignissen

```
GET /api/v1/gtm/countries
```

Gibt alle Länder mit der Anzahl aktuell aktiver Ereignisse zurück. Diese Liste ist nicht paginiert.

#### Beispiel-Anfrage

```bash
curl -H "Authorization: Bearer IHR_API_TOKEN" \
     https://global-travel-monitor.eu/api/v1/gtm/countries
```

#### Beispiel-Antwort

```json
{
  "success": true,
  "data": [
    {
      "iso_code": "DE",
      "iso3_code": "DEU",
      "name": "Deutschland",
      "name_en": "Germany",
      "continent": "Europe",
      "continent_de": "Europa",
      "lat": 51.1657,
      "lng": 10.4515,
      "is_eu_member": true,
      "is_schengen_member": true,
      "active_events_count": 3
    },
    {
      "iso_code": "TR",
      "iso3_code": "TUR",
      "name": "Tuerkei",
      "name_en": "Turkey",
      "continent": "Asia",
      "continent_de": "Asien",
      "lat": 38.9637,
      "lng": 35.2433,
      "is_eu_member": false,
      "is_schengen_member": false,
      "active_events_count": 7
    }
  ]
}
```

---

## Datenstruktur

### Ereignis (Event)

| Feld          | Typ            | Beschreibung                                         |
|---------------|----------------|------------------------------------------------------|
| `id`          | UUID (String)  | Eindeutige ID des Ereignisses                        |
| `title`       | String         | Kurztitel des Ereignisses                            |
| `description` | String / null  | Ausführliche Beschreibung                            |
| `priority`    | String         | Priorität: `high`, `medium`, `low`, `info`           |
| `start_date`  | DateTime / null| Beginn des Ereignisses (ISO 8601)                    |
| `end_date`    | DateTime / null| Ende des Ereignisses (null = andauernd)              |
| `latitude`    | Number / null  | Breitengrad des Ereignisorts                         |
| `longitude`   | Number / null  | Längengrad des Ereignisorts                          |
| `event_types` | Array          | Liste der Ereignistypen                              |
| `event_type`  | Object / null  | Primärer Ereignistyp                                 |
| `category`    | Object / null  | Kategorie des Ereignisses                            |
| `countries`   | Array          | Liste betroffener Länder                             |
| `country`     | Object / null  | Primäres betroffenes Land                            |
| `created_at`  | DateTime       | Erstellungszeitpunkt (ISO 8601)                      |
| `updated_at`  | DateTime       | Letzter Aktualisierungszeitpunkt (ISO 8601)          |

### Ereignistyp (EventType)

| Feld    | Typ    | Beschreibung                       |
|---------|--------|------------------------------------|
| `code`  | String | Maschinenlesbarer Code             |
| `name`  | String | Anzeigename                        |
| `color` | String | Hex-Farbcode für UI-Darstellung    |
| `icon`  | String | Icon-Bezeichnung für UI-Darstellung|

### Kategorie (Category)

| Feld    | Typ     | Beschreibung                    |
|---------|---------|---------------------------------|
| `id`    | Integer | Eindeutige ID                   |
| `name`  | String  | Kategoriename                   |
| `color` | String  | Hex-Farbcode für UI-Darstellung |

### Land (Country) - im Ereignis

| Feld        | Typ    | Beschreibung                |
|-------------|--------|-----------------------------|
| `iso_code`  | String | ISO 3166-1 Alpha-2 Code     |
| `iso3_code` | String | ISO 3166-1 Alpha-3 Code     |
| `name`      | String | Ländername (Deutsch)         |
| `name_en`   | String | Ländername (Englisch)        |
| `continent` | String | Kontinent                    |

### Land (Country) - Länder-Endpunkt

Zusätzlich zu den oben genannten Feldern:

| Feld                  | Typ            | Beschreibung                         |
|-----------------------|----------------|--------------------------------------|
| `continent_de`        | String / null  | Kontinent (Deutsch)                  |
| `lat`                 | Number / null  | Breitengrad des Landeszentrums       |
| `lng`                 | Number / null  | Längengrad des Landeszentrums        |
| `is_eu_member`        | Boolean        | EU-Mitglied                          |
| `is_schengen_member`  | Boolean        | Schengen-Mitglied                    |
| `active_events_count` | Integer        | Anzahl aktuell aktiver Ereignisse    |

---

## Fehlercodes

| HTTP-Status | Bedeutung                                             |
|-------------|-------------------------------------------------------|
| `200`       | Anfrage erfolgreich                                   |
| `401`       | Token fehlt oder ist ungültig                         |
| `403`       | Token hat keine GTM-Berechtigung                      |
| `404`       | Ereignis nicht gefunden                               |
| `422`       | Ungültige Parameter (Details im `errors`-Objekt)      |
| `429`       | Rate-Limit überschritten (`Retry-After`-Header beachten) |

### Fehler-Antwortformat

```json
{
  "success": false,
  "message": "Fehlerbeschreibung"
}
```

Bei Validierungsfehlern (422) zusätzlich:

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "priority": ["The selected priority is invalid."],
    "per_page": ["The per page field must be between 1 and 100."]
  }
}
```

---

## Integrationsbeispiele

### Python

```python
import requests

BASE_URL = "https://global-travel-monitor.eu/api/v1/gtm"
TOKEN = "IHR_API_TOKEN"

headers = {"Authorization": f"Bearer {TOKEN}"}

# Alle Ereignisse mit hoher Priorität abrufen
response = requests.get(f"{BASE_URL}/events", headers=headers, params={
    "priority": "high",
    "per_page": 50
})
data = response.json()

for event in data["data"]:
    print(f"[{event['priority']}] {event['title']}")
    for country in event["countries"]:
        print(f"  Land: {country['name']}")
```

### JavaScript (fetch)

```javascript
const BASE_URL = "https://global-travel-monitor.eu/api/v1/gtm";
const TOKEN = "IHR_API_TOKEN";

async function getEvents(filters = {}) {
  const params = new URLSearchParams(filters);
  const response = await fetch(`${BASE_URL}/events?${params}`, {
    headers: { "Authorization": `Bearer ${TOKEN}` }
  });
  return response.json();
}

// Alle Ereignisse in Deutschland abrufen
const result = await getEvents({ country: "DE" });
console.log(`${result.meta.total} Ereignisse gefunden`);
result.data.forEach(event => {
  console.log(`[${event.priority}] ${event.title}`);
});
```

### PHP

```php
$baseUrl = "https://global-travel-monitor.eu/api/v1/gtm";
$token = "IHR_API_TOKEN";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/events?priority=high&per_page=10",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
]);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

foreach ($response['data'] as $event) {
    echo "[{$event['priority']}] {$event['title']}\n";
}
```
