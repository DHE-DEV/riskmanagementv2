# Folder Management System (Reisemappen-Verwaltung)

## Übersicht

Das Folder Management System ist ein umfassendes Vorgangsverwaltungssystem für Reisebüros mit folgenden Features:

- **Eigener Customer-Admin-Bereich** - Separates Admin-Panel pro Kunde
- **Map-Visualisierung mit Clustering** - Geografische Darstellung aller Reiseziele
- **UUIDs als Primary Keys** - Höhere Sicherheit und Skalierbarkeit
- **Strikte Datenisolierung** - Jeder Kunde sieht nur seine eigenen Daten
- **Multi-Service Support** - Flight, Hotel, Ship, Car Rental
- **Import-Funktionalität** - Automatischer Import aus externen Systemen
- **Performance-Optimierung** - Optimiert für Millionen von Datensätzen

## Architektur

### Datenbank-Hierarchie

```
folder_folders (Hauptvorgang)
├── folder_customers (Kundendaten-Snapshot)
├── folder_participants (Reiseteilnehmer)
└── folder_itineraries (Buchungen/Reisen)
    ├── folder_flight_services
    │   └── folder_flight_segments
    ├── folder_hotel_services
    ├── folder_ship_services
    └── folder_car_rental_services

+ folder_timeline_locations (Denormalisiert für Performance)
```

### Sicherheitskonzept

- **UUIDs** als Primary Keys (nicht integer IDs)
- **customer_id** auf ALLEN Tabellen als Foreign Key
- **Global Query Scopes** erzwingen automatisch customer_id Filter
- **Middleware** validiert Customer-Zugriff
- **Policy-based Authorization** auf Model-Ebene

## Installation

### 1. Migrationen ausführen

```bash
php artisan migrate
```

Dies erstellt alle 12 Tabellen:
- `folder_folders`
- `folder_customers`
- `folder_participants`
- `folder_itineraries`
- `folder_flight_services`
- `folder_flight_segments`
- `folder_hotel_services`
- `folder_ship_services`
- `folder_car_rental_services`
- `folder_timeline_locations` (mit SPATIAL INDEX)
- `folder_itinerary_participant`
- `folder_import_logs`

### 2. Konfiguration

Die Konfiguration befindet sich in `config/folder.php`. Wichtige Einstellungen:

```php
// .env Beispiel
FOLDER_PROXIMITY_RADIUS=100
FOLDER_IMPORT_MAX_SIZE=10
FOLDER_MAP_ENABLED=true
FOLDER_TIMELINE_AUTO_REBUILD=true
```

### 3. Queue Worker starten

Für Background-Jobs (Timeline-Rebuild, Import):

```bash
php artisan queue:work
```

## Verwendung

### Models

Alle Models befinden sich in `app/Models/Folder/` und erweitern `BaseCustomerModel`:

```php
use App\Models\Folder\Folder;
use App\Models\Folder\FolderItinerary;
use App\Models\Folder\FolderParticipant;

// Folder erstellen (customer_id wird automatisch gesetzt)
$folder = Folder::create([
    'folder_number' => Folder::generateFolderNumber(),
    'folder_name' => 'Sommerurlaub 2026',
    'travel_type' => 'leisure',
    'status' => 'draft',
]);

// Teilnehmer hinzufügen
$participant = $folder->participants()->create([
    'first_name' => 'Max',
    'last_name' => 'Mustermann',
    'nationality' => 'DE',
    'participant_type' => 'adult',
]);

// Itinerary erstellen
$itinerary = $folder->itineraries()->create([
    'itinerary_name' => 'Mallorca Flug + Hotel',
    'start_date' => '2026-07-01',
    'end_date' => '2026-07-14',
]);
```

### Services

#### TimelineBuilderService

Erstellt denormalisierte Timeline-Einträge für schnelle Geo-Queries:

```php
use App\Services\Folder\TimelineBuilderService;

$service = app(TimelineBuilderService::class);

// Timeline für gesamten Folder neu erstellen
$locationsCreated = $service->rebuildForFolder($folder);

// Timeline nur für ein Itinerary
$locationsCreated = $service->buildForItinerary($itinerary);
```

#### FolderProximityService

Findet Reisende in geografischer Nähe:

```php
use App\Services\Folder\FolderProximityService;

$service = app(FolderProximityService::class);

// Reisende in 100km Radius um Frankfurt finden
$travelers = $service->findTravelersNearPoint(
    lat: 50.1109,
    lng: 8.6821,
    radiusKm: 100,
    startTime: '2026-07-01',
    endTime: '2026-07-31',
    nationalities: ['DE', 'AT', 'CH']
);

// Alle Reisenden in einem Land
$travelers = $service->findTravelersInCountry(
    countryCode: 'ES',
    startTime: '2026-07-01',
    endTime: '2026-07-31'
);

// Betroffene Folders bei einem Event
$folders = $service->getAffectedFolders(
    lat: 39.5696,
    lng: 2.6502,
    radiusKm: 50,
    startTime: '2026-07-15 00:00:00',
    endTime: '2026-07-15 23:59:59'
);
```

#### FolderImportService

Importiert Folder-Daten aus externen Quellen:

```php
use App\Models\Folder\FolderImportLog;
use App\Jobs\Folder\ProcessFolderImportJob;

// Import-Log erstellen
$log = FolderImportLog::create([
    'import_source' => 'api',
    'provider_name' => 'TourOperator XYZ',
    'source_data' => $importData,
]);

// Import-Job dispatchen
ProcessFolderImportJob::dispatch($log->id);
```

### Background Jobs

#### RebuildFolderTimelineJob

Wird automatisch nach Service-Änderungen dispatched:

```php
use App\Jobs\Folder\RebuildFolderTimelineJob;

RebuildFolderTimelineJob::dispatch($folder->id);
```

#### ProcessFolderImportJob

Verarbeitet Import-Daten im Hintergrund:

```php
use App\Jobs\Folder\ProcessFolderImportJob;

ProcessFolderImportJob::dispatch($importLogId);
```

#### CalculateFolderStatisticsJob

Berechnet Folder-Statistiken:

```php
use App\Jobs\Folder\CalculateFolderStatisticsJob;

CalculateFolderStatisticsJob::dispatch($folder->id);
```

## API Endpoints

Alle API-Endpoints sind unter `/api/customer/folders` verfügbar und benötigen Customer-Authentication.

### Folder Management

```http
GET /api/customer/folders
GET /api/customer/folders/{id}
```

### Map-Daten

```http
GET /api/customer/folders/map-locations?folder_id={uuid}&start_date={date}&end_date={date}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "folder_id": "uuid",
      "folder_number": "2026-123456",
      "lat": 39.5696,
      "lng": 2.6502,
      "location_type": "hotel",
      "location_name": "Hotel Playa",
      "country_code": "ES",
      "start_time": "2026-07-01T15:00:00Z",
      "end_time": "2026-07-14T11:00:00Z",
      "participant_count": 4
    }
  ]
}
```

### Proximity Queries

```http
POST /api/customer/folders/near-point
Content-Type: application/json

{
  "lat": 39.5696,
  "lng": 2.6502,
  "radius_km": 100,
  "start_time": "2026-07-01T00:00:00Z",
  "end_time": "2026-07-31T23:59:59Z",
  "nationalities": ["DE", "AT"]
}
```

```http
POST /api/customer/folders/in-country
Content-Type: application/json

{
  "country_code": "ES",
  "start_time": "2026-07-01T00:00:00Z",
  "end_time": "2026-07-31T23:59:59Z"
}
```

```http
POST /api/customer/folders/affected-folders
Content-Type: application/json

{
  "lat": 39.5696,
  "lng": 2.6502,
  "radius_km": 50,
  "start_time": "2026-07-15T00:00:00Z",
  "end_time": "2026-07-15T23:59:59Z"
}
```

### Import

```http
POST /api/customer/folders/import
Content-Type: application/json

{
  "source": "api",
  "provider": "TourOperator XYZ",
  "data": {
    "folder": {
      "folder_name": "Sommerurlaub",
      "travel_type": "leisure"
    },
    "participants": [...],
    "itineraries": [...]
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Import queued successfully",
  "log_id": "uuid"
}
```

Status abfragen:
```http
GET /api/customer/folders/imports/{logId}/status
```

## Performance-Optimierung

### Spatial Index

Die Tabellen `folder_timeline_locations` und `folder_hotel_services` nutzen MySQL SPATIAL INDEX für geografische Queries:

```sql
-- Automatisch erstellt in Migrationen
ALTER TABLE folder_timeline_locations
  ADD COLUMN point POINT NOT NULL SRID 4326;
CREATE SPATIAL INDEX idx_timeline_point
  ON folder_timeline_locations (point);
```

### Denormalisierung

`folder_timeline_locations` ist eine denormalisierte Tabelle, die alle geografischen Locations aus allen Services aggregiert. Dies ermöglicht:

- **Schnelle Proximity-Queries** (< 300ms bei 100k+ Records)
- **Einfache Filtering** nach Zeit, Land, Nationalität
- **Optimierte Map-Darstellung** mit Clustering

### Caching

Wichtige Queries werden gecacht:

```php
use Illuminate\Support\Facades\Cache;

// Map-Locations für 10 Minuten cachen
$locations = Cache::tags(['customer', "customer.{$customerId}"])
    ->remember("customer.{$customerId}.map_locations", 600, function () {
        return $proximityService->getMapLocations();
    });

// Cache invalidieren
Cache::tags(['customer', "customer.{$customerId}"])->flush();
```

### Query-Optimierung

**✅ RICHTIG - Nutzt SPATIAL INDEX:**
```php
FolderTimelineLocation::query()
    ->where('customer_id', $customerId)
    ->withinRadius($lat, $lng, $radiusKm)
    ->activeDuring($startTime, $endTime)
    ->get();
```

**❌ FALSCH - Langsam ohne Index:**
```php
Folder::whereHas('itineraries.hotelServices', function ($q) use ($lat, $lng) {
    // Nested whereHas sind sehr langsam!
})->get();
```

## Sicherheit

### Global Scope

Alle Models haben einen Global Scope, der automatisch `customer_id` filtert:

```php
// BaseCustomerModel.php
static::addGlobalScope('customer', function (Builder $builder) {
    if (auth('customer')->check()) {
        $builder->where(
            $builder->getModel()->getTable().'.customer_id',
            auth('customer')->id()
        );
    }
});
```

### Policies

Policies erzwingen zusätzliche Berechtigungen:

```php
// FolderPolicy.php
public function view(Customer $customer, Folder $folder): bool
{
    return $customer->id === $folder->customer_id;
}

public function delete(Customer $customer, Folder $folder): bool
{
    // Nur draft-Folders können gelöscht werden
    return $customer->id === $folder->customer_id
        && $folder->status === 'draft';
}
```

## Testing

### Unit Tests

```bash
php artisan test --filter FolderTest
```

Beispiel-Tests:

```php
test('customer can only see own folders', function () {
    $customer1 = Customer::factory()->create();
    $customer2 = Customer::factory()->create();

    $folder1 = Folder::factory()->create(['customer_id' => $customer1->id]);
    $folder2 = Folder::factory()->create(['customer_id' => $customer2->id]);

    actingAs($customer1, 'customer');

    $folders = Folder::all();

    expect($folders)->toHaveCount(1)
        ->and($folders->first()->id)->toBe($folder1->id);
});

test('geographic scope finds locations within radius', function () {
    $location = FolderTimelineLocation::factory()->create([
        'customer_id' => $customerId,
        'lat' => 52.5200,
        'lng' => 13.4050,
    ]);

    $results = FolderTimelineLocation::withinRadius(52.5200, 13.4050, 10)->get();

    expect($results)->toContain($location);
});
```

## Troubleshooting

### Timeline-Locations fehlen

Wenn Timeline-Locations fehlen, kann die Timeline manuell neu erstellt werden:

```bash
php artisan tinker
```

```php
use App\Models\Folder\Folder;
use App\Services\Folder\TimelineBuilderService;

$folder = Folder::find('folder-uuid');
$service = app(TimelineBuilderService::class);
$service->rebuildForFolder($folder);
```

### Performance-Probleme

1. **Spatial Index prüfen:**
```sql
SHOW INDEX FROM folder_timeline_locations WHERE Key_name = 'idx_timeline_point';
```

2. **Query-Analyse:**
```php
DB::enableQueryLog();
// ... Query ausführen ...
dd(DB::getQueryLog());
```

3. **Cache leeren:**
```bash
php artisan cache:clear
```

### Import schlägt fehl

Import-Logs prüfen:

```php
use App\Models\Folder\FolderImportLog;

$log = FolderImportLog::find('log-uuid');
dd($log->error_message, $log->source_data);
```

## Erweiterung

### Neue Service-Typen hinzufügen

1. **Migration erstellen** (z.B. für "Train Services")
2. **Model erstellen** (`FolderTrainService extends BaseCustomerModel`)
3. **TimelineBuilderService erweitern** (neue Locations hinzufügen)
4. **Import-Mapping hinzufügen** (in FolderImportService)

### Custom Location Types

In `TimelineBuilderService.php` neue Location-Types hinzufügen:

```php
$this->createTimelineLocation([
    'location_type' => 'train_departure', // Neuer Type
    'source_type' => 'train_service',
    // ...
]);
```

Location-Type in Migration hinzufügen:

```php
$table->enum('location_type', [
    'flight_departure',
    'flight_arrival',
    'hotel',
    'train_departure', // NEU
    'train_arrival',   // NEU
    // ...
]);
```

## Support

Bei Fragen oder Problemen:
- Dokumentation: `/docs/folder-system`
- Issue Tracker: GitHub Issues
- Slack Channel: #folder-system

## Changelog

### v1.0.0 (2026-01-23)
- Initiales Release
- 12 Datenbank-Tabellen
- 11 Eloquent Models
- 3 Service-Klassen
- 3 Background Jobs
- 2 Policies
- 2 API Controllers
- Spatial Index für Performance
- Timeline-Denormalisierung
- Import-System
- Proximity-Queries
