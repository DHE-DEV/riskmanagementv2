# Folder Import API - Dokumentation für Integration

## 📚 Verfügbare Dokumentationsdateien

Dieses Verzeichnis enthält eine vollständige API-Dokumentation für die Folder Import API:

1. **FOLDER_IMPORT_API_DOCUMENTATION.md** - Ausführliche Markdown-Dokumentation
2. **folder-import-openapi.yaml** - OpenAPI 3.0 Spezifikation (Swagger)

## 🎯 Verwendung in anderen Claude-Projekten

### Option 1: Markdown-Dokumentation verwenden

Kopieren Sie die Datei `FOLDER_IMPORT_API_DOCUMENTATION.md` in Ihr Projekt und geben Sie Claude folgenden Prompt:

```
Ich möchte die Folder Import API integrieren. Die vollständige API-Dokumentation
findest du in der Datei FOLDER_IMPORT_API_DOCUMENTATION.md.

Bitte lies die Dokumentation und hilf mir dabei, [IHRE AUFGABE].
```

**Beispiel-Prompts:**

```
Erstelle mir einen Python-Client für die Folder Import API basierend auf
der Dokumentation in FOLDER_IMPORT_API_DOCUMENTATION.md.
```

```
Ich möchte von unserem Booking-System automatisch Folders an die API senden.
Lies bitte FOLDER_IMPORT_API_DOCUMENTATION.md und zeige mir, wie ich einen
Folder mit Hotel und Flug senden kann.
```

### Option 2: OpenAPI YAML verwenden

Die `folder-import-openapi.yaml` kann für folgende Zwecke verwendet werden:

#### A) Code-Generierung mit OpenAPI Generator

```bash
# Client für verschiedene Sprachen generieren
openapi-generator-cli generate \
  -i folder-import-openapi.yaml \
  -g python \
  -o ./generated-client/python

# Weitere Sprachen: typescript-fetch, php, java, go, ruby, etc.
```

#### B) Mit Claude Code in anderem Projekt

```
Ich habe eine OpenAPI-Spezifikation in folder-import-openapi.yaml.
Generiere mir daraus einen TypeScript-Client mit Axios.
```

#### C) Swagger UI / API-Dokumentation

Importieren Sie die YAML-Datei in:
- **Swagger UI**: https://editor.swagger.io/
- **Postman**: Import → OpenAPI 3.0
- **Insomnia**: Import → OpenAPI
- **Stoplight Studio**: Für API-Design und Dokumentation

## 📝 Schnellstart-Beispiele

### Python

```python
import requests
from datetime import date, timedelta

# Token (einmalig generieren)
token = "IHR_API_TOKEN"

# Folder Import
endpoint = "https://your-domain.com/api/customer/folders/import"
headers = {
    "Authorization": f"Bearer {token}",
    "Content-Type": "application/json",
    "Accept": "application/json"
}

folder_data = {
    "source": "api",
    "provider": "Ihr System",
    "data": {
        "folder": {
            "folder_name": "Testbuchung",
            "travel_start_date": str(date.today()),
            "travel_end_date": str(date.today() + timedelta(days=7)),
            "primary_destination": "München, Deutschland",
            "travel_type": "leisure",
            "status": "confirmed",
            "currency": "EUR"
        },
        "customer": {
            "first_name": "Max",
            "last_name": "Mustermann",
            "email": "max@example.com"
        },
        "participants": [
            {
                "first_name": "Max",
                "last_name": "Mustermann",
                "is_main_contact": True,
                "participant_type": "adult"
            }
        ],
        "itineraries": [
            {
                "itinerary_name": "Städtereise",
                "hotels": [
                    {
                        "hotel_name": "Hotel München City",
                        "city": "München",
                        "country_code": "DE",
                        "check_in_date": str(date.today()),
                        "check_out_date": str(date.today() + timedelta(days=7)),
                        "nights": 7,
                        "status": "confirmed"
                    }
                ]
            }
        ]
    }
}

response = requests.post(endpoint, headers=headers, json=folder_data)
result = response.json()

if result["success"]:
    print(f"Import erfolgreich: {result['log_id']}")

    # Status abfragen
    status_url = f"https://your-domain.com/api/customer/folders/imports/{result['log_id']}/status"
    status_response = requests.get(status_url, headers=headers)
    print(status_response.json())
else:
    print(f"Fehler: {result}")
```

### PHP

```php
<?php

$token = "IHR_API_TOKEN";
$endpoint = "https://your-domain.com/api/customer/folders/import";

$folderData = [
    "source" => "api",
    "provider" => "Ihr System",
    "data" => [
        "folder" => [
            "folder_name" => "Testbuchung",
            "travel_start_date" => date('Y-m-d'),
            "travel_end_date" => date('Y-m-d', strtotime('+7 days')),
            "travel_type" => "leisure",
            "status" => "confirmed"
        ],
        "customer" => [
            "first_name" => "Max",
            "last_name" => "Mustermann",
            "email" => "max@example.com"
        ],
        "participants" => [
            [
                "first_name" => "Max",
                "last_name" => "Mustermann",
                "is_main_contact" => true,
                "participant_type" => "adult"
            ]
        ],
        "itineraries" => [
            [
                "itinerary_name" => "Städtereise",
                "hotels" => [
                    [
                        "hotel_name" => "Hotel München City",
                        "city" => "München",
                        "country_code" => "DE",
                        "check_in_date" => date('Y-m-d'),
                        "check_out_date" => date('Y-m-d', strtotime('+7 days')),
                        "nights" => 7,
                        "status" => "confirmed"
                    ]
                ]
            ]
        ]
    ]
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Content-Type: application/json",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($folderData));

$response = curl_exec($ch);
$result = json_decode($response, true);

if ($result['success']) {
    echo "Import erfolgreich: " . $result['log_id'] . "\n";
} else {
    echo "Fehler: " . print_r($result, true);
}

curl_close($ch);
```

### Node.js / TypeScript

```typescript
import axios from 'axios';

const token = "IHR_API_TOKEN";
const endpoint = "https://your-domain.com/api/customer/folders/import";

const folderData = {
  source: "api",
  provider: "Ihr System",
  data: {
    folder: {
      folder_name: "Testbuchung",
      travel_start_date: new Date().toISOString().split('T')[0],
      travel_end_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      travel_type: "leisure",
      status: "confirmed"
    },
    customer: {
      first_name: "Max",
      last_name: "Mustermann",
      email: "max@example.com"
    },
    participants: [
      {
        first_name: "Max",
        last_name: "Mustermann",
        is_main_contact: true,
        participant_type: "adult"
      }
    ],
    itineraries: [
      {
        itinerary_name: "Städtereise",
        hotels: [
          {
            hotel_name: "Hotel München City",
            city: "München",
            country_code: "DE",
            check_in_date: new Date().toISOString().split('T')[0],
            check_out_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            nights: 7,
            status: "confirmed"
          }
        ]
      }
    ]
  }
};

async function importFolder() {
  try {
    const response = await axios.post(endpoint, folderData, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    });

    if (response.data.success) {
      console.log(`Import erfolgreich: ${response.data.log_id}`);

      // Status abfragen
      const statusResponse = await axios.get(
        `https://your-domain.com/api/customer/folders/imports/${response.data.log_id}/status`,
        { headers: { 'Authorization': `Bearer ${token}` } }
      );
      console.log(statusResponse.data);
    }
  } catch (error) {
    console.error('Fehler:', error.response?.data || error.message);
  }
}

importFolder();
```

## 🔑 Token-Generierung

Vor der Verwendung der API muss ein Token generiert werden:

### Via Browser (eingeloggt als Customer)
```
POST https://your-domain.com/my-travelers/api-tokens/generate
```

### Via Laravel Tinker
```bash
php artisan tinker

$customer = \App\Models\Customer::find(CUSTOMER_ID);
$token = $customer->createToken('api-access-token', [
    'folder:import',
    'folder:read',
    'folder:write'
]);
echo $token->plainTextToken;
```

## ✅ Best Practices

### 1. Error Handling
```python
try:
    response = requests.post(endpoint, headers=headers, json=folder_data)
    response.raise_for_status()
    result = response.json()

    if result["success"]:
        # Erfolg
        log_id = result["log_id"]
    else:
        # API-Fehler
        print(f"API Error: {result.get('message')}")

except requests.exceptions.HTTPError as e:
    # HTTP-Fehler (422, 401, etc.)
    print(f"HTTP Error: {e.response.status_code}")
    print(e.response.json())
except Exception as e:
    # Sonstige Fehler
    print(f"Error: {str(e)}")
```

### 2. Status Polling
```python
import time

def wait_for_import_completion(log_id, max_wait=60):
    """Wartet auf Completion des Imports (max 60 Sekunden)"""
    start_time = time.time()

    while time.time() - start_time < max_wait:
        status_response = requests.get(
            f"{base_url}/imports/{log_id}/status",
            headers=headers
        )
        status = status_response.json()["data"]["status"]

        if status == "completed":
            return True
        elif status == "failed":
            return False

        time.sleep(2)  # Alle 2 Sekunden prüfen

    return False  # Timeout
```

### 3. Batch-Import
```python
def import_multiple_folders(folders_list):
    """Importiert mehrere Folders nacheinander"""
    results = []

    for folder in folders_list:
        response = requests.post(endpoint, headers=headers, json=folder)
        result = response.json()
        results.append({
            "folder_name": folder["data"]["folder"]["folder_name"],
            "log_id": result.get("log_id"),
            "success": result.get("success")
        })
        time.sleep(1)  # Rate limiting beachten

    return results
```

## 📋 Wichtige Hinweise

### Automatisches Matching
Das System führt automatisch folgendes Matching durch:

1. **Airport-Codes** (IATA 3-Letter) → `airport_codes_1` Tabelle
2. **Länder-Codes** (ISO 3166-1 alpha-2) → `countries` Tabelle
3. **Geokoordinaten** werden automatisch aus Airport-DB übernommen

**Sie müssen nur die IATA-Codes angeben!**

```json
{
  "departure_airport_code": "MUC",
  "arrival_airport_code": "JFK"
}
```

Das System ergänzt automatisch:
- Geokoordinaten
- Ländercodes
- Airport-IDs
- Country-IDs

### Custom Fields
5 flexible Freifelder pro Folder verfügbar:

```json
{
  "custom_field_1_label": "Externe Buchungsnummer",
  "custom_field_1_value": "EXT-12345",
  "custom_field_2_label": "Versicherung",
  "custom_field_2_value": "https://insurance.com/policy/123"
}
```

URLs werden automatisch erkannt und als anklickbare Links dargestellt!

## 🔧 Troubleshooting

### Problem: "Nicht authentifiziert" (401)
- Prüfen Sie, ob der Token korrekt ist
- Token muss im Header als `Bearer {TOKEN}` übergeben werden
- Token könnte abgelaufen oder widerrufen sein

### Problem: Validierungsfehler (422)
- Prüfen Sie alle erforderlichen Felder
- Datumsformat: `YYYY-MM-DD`
- DateTime-Format: `YYYY-MM-DD HH:MM:SS`
- Airport-Codes: Exakt 3 Buchstaben (z.B. "MUC")
- Ländercodes: Exakt 2 Buchstaben (z.B. "DE")

### Problem: Import bleibt bei "processing" hängen
- Prüfen Sie die Queue: `php artisan queue:work`
- Prüfen Sie die Logs: `storage/logs/laravel.log`

## 📞 Support

Bei Fragen zur Integration:
1. Lesen Sie die vollständige Dokumentation in `FOLDER_IMPORT_API_DOCUMENTATION.md`
2. Prüfen Sie die OpenAPI-Spezifikation in `folder-import-openapi.yaml`
3. Testen Sie mit den Beispiel-Scripts

## 📄 Lizenz

Diese Dokumentation ist Teil des Folder Import Systems.

---

**Version:** 1.0
**Letzte Aktualisierung:** 2026-01-24
