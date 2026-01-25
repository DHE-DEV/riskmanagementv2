# Folder Import Test - Bangkok Reise

## Übersicht

Diese Dateien enthalten einen vollständigen Beispiel-Import für das Folder Management System:

- **Kunde**: Max Mustermann - Mustermann Reisen GmbH
- **Reise**: Bangkok, Thailand
- **Zeitraum**: 12.10.2025 - 26.10.2025 (14 Tage)
- **Personen**: 2 Erwachsene
- **Flugsegmente**: 4 Segmente (Hin- und Rückflug über Dubai)
- **Hotels**: 2 Hotels (Bangkok Hauptaufenthalt + Dubai Transit)

## Reiseverlauf

### Hinflug (2 Segmente)
1. **FRA → DXB**: 12.10.2025, 22:15 - 13.10.2025, 07:05 (Lufthansa LH630, Boeing 747-8)
2. **DXB → BKK**: 13.10.2025, 09:45 - 19:20 (Emirates EK384, Airbus A380)

### Hotels
1. **Dubai Airport Hotel**: Transit-Aufenthalt am 13.10.2025 (Tagesraum)
2. **Mandarin Oriental Bangkok**: 13.10.2025 - 25.10.2025 (12 Nächte)
   - Deluxe River View Room mit Frühstück
   - Late Check-Out 14:00 Uhr

### Rückflug (2 Segmente)
3. **BKK → DXB**: 25.10.2025, 23:55 - 26.10.2025, 03:30 (Emirates EK385, Airbus A380)
4. **DXB → FRA**: 26.10.2025, 08:10 - 13:00 (Lufthansa LH631, Boeing 747-8)

## Verwendung

### 1. Voraussetzungen

Stelle sicher, dass:
- Die Laravel-Anwendung läuft
- Die Datenbank migriert ist (`php artisan migrate`)
- Der API Token gültig ist
- Der Queue Worker läuft (`php artisan queue:work`)

### 2. Import ausführen

#### Option A: Bash-Script (empfohlen für Unix/Linux/Mac)

```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
./folder_import_curl.sh
```

#### Option B: Manuell kopieren und einfügen

Öffne die Datei `folder_import_curl.sh`, kopiere den gesamten Inhalt und füge ihn ins Terminal ein.

### 3. Antwort prüfen

Erfolgreiche Antwort:
```json
{
  "success": true,
  "message": "Import queued successfully",
  "log_id": "uuid-hier"
}
```

Fehlerhafte Antwort:
```json
{
  "success": false,
  "message": "...",
  "error": "..."
}
```

### 4. Import-Status prüfen

Verwende die `log_id` aus der Antwort:

```bash
curl -X GET "http://localhost/api/customer/folders/imports/{log_id}/status" \
-H "Accept: application/json" \
-H "Authorization: Bearer 2|RHej0fNgjGSzvPrEcSuY7nMGI7fldCnOMoBrpl2T173373b5"
```

### 5. Importierte Folder anzeigen

```bash
curl -X GET "http://localhost/api/customer/folders" \
-H "Accept: application/json" \
-H "Authorization: Bearer 2|RHej0fNgjGSzvPrEcSuY7nMGI7fldCnOMoBrpl2T173373b5"
```

## Erwartete Daten

Nach erfolgreichem Import sollten folgende Daten vorhanden sein:

### Folder
- 1 Folder mit Nummer (automatisch generiert)
- Status: confirmed
- Travel Type: leisure

### Customer Data
- Max Mustermann
- Mustermann Reisen GmbH
- Frankfurt am Main, Deutschland

### Participants
- Max Mustermann (Hauptkontakt)
- Erika Mustermann

### Itinerary
- 1 Itinerary "Hauptreise Bangkok mit Flügen und Hotel"
- Booking Reference: BKK2025MUS

### Flight Services
- 1 Flight Service (Multi-Leg)
- 4 Flight Segments
- Status: ticketed
- Total: 1.850,00 EUR

### Hotel Services
- 2 Hotel Services
  - Mandarin Oriental Bangkok: 3.600,00 EUR
  - Dubai Airport Hotel: 0,00 EUR (Transit)

### Timeline Locations
Nach dem Import wird automatisch die Timeline erstellt mit:
- 8 Flight Locations (4 Abflüge + 4 Ankünfte)
- 2 Hotel Locations

## Troubleshooting

### Fehler: 401 Unauthorized
- API Token ist ungültig oder abgelaufen
- Neuen Token generieren im Customer-Dashboard unter "API Tokens"

### Fehler: 422 Validation Error
- Prüfe die Datenstruktur im JSON
- Stelle sicher, dass alle Pflichtfelder vorhanden sind

### Import hängt bei "pending"
- Queue Worker läuft nicht
- Starte: `php artisan queue:work`
- Prüfe Logs: `storage/logs/laravel.log`

### Timeline-Locations fehlen
- Manuell neu erstellen:
```bash
php artisan tinker
```
```php
use App\Models\Folder\Folder;
use App\Services\Folder\TimelineBuilderService;

$folder = Folder::latest()->first();
$service = app(TimelineBuilderService::class);
$service->rebuildForFolder($folder);
```

## API Endpoint Details

**Endpoint**: `POST /api/customer/folders/import`

**Headers**:
- `Content-Type: application/json`
- `Accept: application/json`
- `Authorization: Bearer {token}`

**Body**: JSON mit folgender Struktur:
```json
{
  "source": "api|file|manual",
  "provider": "Provider Name",
  "data": {
    "folder": {...},
    "customer": {...},
    "participants": [...],
    "itineraries": [...]
  }
}
```

## Weitere Tests

### Test 2: Proximity Query
Finde Reisende in Bangkok:

```bash
curl -X POST "http://localhost/api/customer/folders/near-point" \
-H "Content-Type: application/json" \
-H "Authorization: Bearer 2|RHej0fNgjGSzvPrEcSuY7nMGI7fldCnOMoBrpl2T173373b5" \
-d '{
  "lat": 13.7563,
  "lng": 100.5018,
  "radius_km": 10,
  "start_time": "2025-10-13T00:00:00Z",
  "end_time": "2025-10-25T23:59:59Z"
}'
```

### Test 3: Map Locations
Hole alle Map-Locations:

```bash
curl -X GET "http://localhost/api/customer/folders/map-locations" \
-H "Accept: application/json" \
-H "Authorization: Bearer 2|RHej0fNgjGSzvPrEcSuY7nMGI7fldCnOMoBrpl2T173373b5"
```

## Realistische Daten

Alle Daten im Import sind realistisch:

- **Flugrouten**: Tatsächliche Lufthansa/Emirates Verbindungen
- **Flughafencodes**: IATA-Codes (FRA, DXB, BKK)
- **Koordinaten**: Echte GPS-Koordinaten
- **Flugzeiten**: Realistische Flugdauern
- **Hotel**: Existierendes 5-Sterne-Hotel in Bangkok
- **Preise**: Marktübliche Preise für 2025

## Support

Bei Fragen oder Problemen siehe:
- `FOLDER_SYSTEM_README.md` für System-Dokumentation
- `storage/logs/laravel.log` für Fehler-Logs
- Filament Admin-Panel: `/customer/admin` für manuelle Prüfung
