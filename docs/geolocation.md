# Geolocation-System Dokumentation

## Übersicht

Das Geolocation-System ermöglicht es, Geokoordinaten (Breiten- und Längengrad) mit den Datenbanktabellen `continents`, `countries` und `cities` abzugleichen. Es bietet sowohl datenbankbasierte als auch API-basierte Lösungen für Reverse Geocoding.

## Architektur

### Services

#### 1. GeolocationService
**Datei:** `app/Services/GeolocationService.php`

Der primäre Service für datenbankbasierte Geolocation-Funktionen.

**Hauptfunktionen:**
- `findNearestCity(float $lat, float $lng, int $maxDistanceKm = 50): ?City`
- `findNearestCountry(float $lat, float $lng, int $maxDistanceKm = 500): ?Country`
- `findNearestContinent(float $lat, float $lng): ?Continent`
- `findLocationInfo(float $lat, float $lng): array`
- `findCitiesInRadius(float $lat, float $lng, int $radiusKm): Collection`

#### 2. ReverseGeocodingService
**Datei:** `app/Services/ReverseGeocodingService.php`

Service für API-basierte Reverse Geocoding mit Fallback zur datenbankbasierten Lösung.

**Hauptfunktionen:**
- `getLocationFromCoordinates(float $lat, float $lng): array` (OpenStreetMap Nominatim)
- `getLocationFromGoogle(float $lat, float $lng): array` (Google Geocoding API)

### Controller

#### GeolocationController
**Datei:** `app/Http/Controllers/GeolocationController.php`

RESTful API-Endpunkte für Geolocation-Funktionen.

**Endpunkte:**
- `GET /api/geolocation/find-location`
- `GET /api/geolocation/nearest-city`
- `GET /api/geolocation/cities-in-radius`
- `GET /api/geolocation/test`

## Installation & Setup

### 1. Voraussetzungen

Das System nutzt die bestehenden Modelle:
- `Continent` (mit `lat`, `lng` Feldern)
- `Country` (mit `lat`, `lng` Feldern)
- `City` (mit `lat`, `lng` Feldern)

### 2. Konfiguration

#### Google Maps API (Optional)
Fügen Sie in `config/services.php` hinzu:

```php
'google' => [
    'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
],
```

Und in `.env`:
```env
GOOGLE_MAPS_API_KEY=your_google_maps_api_key_here
```

### 3. Routen

Die API-Routen sind automatisch in `routes/api.php` registriert:

```php
Route::prefix('geolocation')->group(function () {
    Route::get('/find-location', [GeolocationController::class, 'findLocation']);
    Route::get('/nearest-city', [GeolocationController::class, 'findNearestCity']);
    Route::get('/cities-in-radius', [GeolocationController::class, 'findCitiesInRadius']);
    Route::get('/test', [GeolocationController::class, 'test']);
});
```

## Verwendung

### 1. API-Endpunkte

#### Standort finden
```http
GET /api/geolocation/find-location?lat=52.5200&lng=13.4050&method=database
```

**Parameter:**
- `lat` (required): Breitengrad (-90 bis 90)
- `lng` (required): Längengrad (-180 bis 180)
- `method` (optional): `database`, `nominatim`, oder `google`

**Beispiel-Response:**
```json
{
    "success": true,
    "data": {
        "coordinates": {
            "lat": 52.5200,
            "lng": 13.4050
        },
        "city": {
            "id": 1,
            "name": "Berlin",
            "distance_km": 0.5,
            "is_capital": true
        },
        "country": {
            "id": 1,
            "name": "Deutschland",
            "iso_code": "DE",
            "distance_km": 150.2
        },
        "continent": {
            "id": 1,
            "name": "Europa",
            "code": "EU",
            "distance_km": 1200.8
        }
    },
    "method": "database"
}
```

#### Nächstgelegene Stadt finden
```http
GET /api/geolocation/nearest-city?lat=52.5200&lng=13.4050&max_distance_km=50
```

**Parameter:**
- `lat` (required): Breitengrad
- `lng` (required): Längengrad
- `max_distance_km` (optional): Maximale Entfernung in km (Standard: 50)

#### Städte im Radius finden
```http
GET /api/geolocation/cities-in-radius?lat=52.5200&lng=13.4050&radius_km=100
```

**Parameter:**
- `lat` (required): Breitengrad
- `lng` (required): Längengrad
- `radius_km` (required): Radius in Kilometern

### 2. Programmatische Verwendung

#### Service Injection
```php
use App\Services\GeolocationService;
use App\Services\ReverseGeocodingService;

class YourController extends Controller
{
    public function __construct(
        private GeolocationService $geolocationService,
        private ReverseGeocodingService $reverseGeocodingService
    ) {}

    public function someMethod()
    {
        // Datenbankbasierte Lösung
        $locationInfo = $this->geolocationService->findLocationInfo(52.5200, 13.4050);
        
        // API-basierte Lösung
        $apiLocationInfo = $this->reverseGeocodingService->getLocationFromCoordinates(52.5200, 13.4050);
        
        // Nächstgelegene Stadt
        $nearestCity = $this->geolocationService->findNearestCity(52.5200, 13.4050, 50);
        
        // Städte im Radius
        $citiesInRadius = $this->geolocationService->findCitiesInRadius(52.5200, 13.4050, 100);
    }
}
```

#### Service Container
```php
$geolocationService = app(GeolocationService::class);
$locationInfo = $geolocationService->findLocationInfo(52.5200, 13.4050);
```

### 3. Artisan Commands

#### Test Command
```bash
# Teste mit Datenbank-Methode
php artisan geolocation:test 52.5200 13.4050

# Teste mit Nominatim API
php artisan geolocation:test 52.5200 13.4050 --method=nominatim
```

## Algorithmen

### Entfernungsberechnung

Das System verwendet die **Haversine-Formel** für präzise Entfernungsberechnungen zwischen zwei Koordinaten:

```php
private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadius = 6371; // Erdradius in Kilometern

    $latDelta = deg2rad($lat2 - $lat1);
    $lngDelta = deg2rad($lng2 - $lng1);

    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lngDelta / 2) * sin($lngDelta / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}
```

### Suchalgorithmen

#### Nächstgelegener Ort
1. Lade alle Orte mit Koordinaten aus der Datenbank
2. Berechne Entfernung zu jedem Ort
3. Wähle den nächstgelegenen Ort innerhalb der maximalen Entfernung

#### Radius-Suche
1. Lade alle Städte mit Koordinaten
2. Filtere nach Entfernung ≤ Radius
3. Sortiere nach Entfernung
4. Füge Entfernungsinformationen hinzu

## API-Integration

### OpenStreetMap Nominatim

**Vorteile:**
- Kostenlos
- Keine API-Key erforderlich
- Open Source

**Nutzungsbedingungen:**
- Maximal 1 Request pro Sekunde
- User-Agent Header erforderlich (wird automatisch gesetzt)

**Beispiel-Request:**
```http
GET https://nominatim.openstreetmap.org/reverse?lat=52.5200&lon=13.4050&format=json&addressdetails=1&accept-language=de,en
```

### Google Geocoding API

**Vorteile:**
- Höhere Genauigkeit
- Mehrsprachige Unterstützung
- Umfangreiche Adressdaten

**Voraussetzungen:**
- Google Maps API Key
- Kostenpflichtig (nach Freemium-Limit)

## Caching

Alle API-Aufrufe werden für 1 Stunde gecacht:

```php
Cache::remember($cacheKey, 3600, function () {
    // API-Aufruf
});
```

**Cache-Keys:**
- `reverse_geocode_{lat}_{lng}` für Nominatim
- `google_reverse_geocode_{lat}_{lng}` für Google

## Fehlerbehandlung

### Fallback-Mechanismus

1. **API-basierte Lösung** versucht zuerst
2. Bei Fehler → **Datenbankbasierte Lösung**
3. Bei Datenbankfehler → **Exception**

### Validierung

Alle Eingabeparameter werden validiert:

```php
$request->validate([
    'lat' => 'required|numeric|between:-90,90',
    'lng' => 'required|numeric|between:-180,180',
    'method' => 'nullable|string|in:database,nominatim,google',
]);
```

### HTTP-Status-Codes

- `200`: Erfolgreich
- `422`: Validierungsfehler
- `404`: Keine Ergebnisse gefunden
- `500`: Server-Fehler

## Performance-Optimierung

### Datenbank-Indizes

Stellen Sie sicher, dass folgende Indizes existieren:

```sql
-- Für Cities
CREATE INDEX idx_cities_coordinates ON cities(lat, lng);
CREATE INDEX idx_cities_country_id ON cities(country_id);

-- Für Countries
CREATE INDEX idx_countries_coordinates ON countries(lat, lng);
CREATE INDEX idx_countries_continent_id ON countries(continent_id);

-- Für Continents
CREATE INDEX idx_continents_coordinates ON continents(lat, lng);
```

### Query-Optimierung

- Eager Loading für Beziehungen
- Nur notwendige Felder laden
- Caching für wiederholte Anfragen

## Testing

### Test-Suite ausführen

```bash
# Alle Geolocation-Tests
php artisan test tests/Feature/GeolocationTest.php

# Spezifische Tests
php artisan test --filter="findet geografische Informationen"
```

### Test-Daten

Die Tests verwenden Factory-Data:
- Kontinent: Europa (EU)
- Land: Deutschland (DE)
- Städte: Berlin, München

### Test-Szenarien

1. **Erfolgreiche Standortsuche**
2. **Validierung von Koordinaten**
3. **404 bei keinen Ergebnissen**
4. **Verschiedene Suchmethoden**
5. **Entfernungsberechnungen**
6. **Radius-Suche**

## Beispiele

### Frontend-Integration (JavaScript)

```javascript
// Standort finden
async function findLocation(lat, lng) {
    const response = await fetch(`/api/geolocation/find-location?lat=${lat}&lng=${lng}`);
    const data = await response.json();
    
    if (data.success) {
        console.log('Stadt:', data.data.city.name);
        console.log('Land:', data.data.country.name);
        console.log('Kontinent:', data.data.continent.name);
    }
}

// Städte im Radius finden
async function findCitiesInRadius(lat, lng, radius) {
    const response = await fetch(`/api/geolocation/cities-in-radius?lat=${lat}&lng=${lng}&radius_km=${radius}`);
    const data = await response.json();
    
    if (data.success) {
        data.data.cities.forEach(city => {
            console.log(`${city.name}: ${city.distance_km}km`);
        });
    }
}
```

### Livewire-Integration

```php
use App\Services\GeolocationService;

class LocationComponent extends Component
{
    public function findLocation($lat, $lng)
    {
        $geolocationService = app(GeolocationService::class);
        $locationInfo = $geolocationService->findLocationInfo($lat, $lng);
        
        $this->city = $locationInfo['city']['name'] ?? null;
        $this->country = $locationInfo['country']['name'] ?? null;
        $this->continent = $locationInfo['continent']['name'] ?? null;
    }
}
```

## Troubleshooting

### Häufige Probleme

#### 1. Keine Ergebnisse gefunden
**Ursache:** Keine Orte in der Datenbank oder zu große Entfernung
**Lösung:** 
- Überprüfen Sie die Datenbank-Daten
- Erhöhen Sie `max_distance_km`
- Fügen Sie mehr Orte hinzu

#### 2. API-Fehler
**Ursache:** Rate Limiting oder Netzwerkprobleme
**Lösung:**
- Fallback zur datenbankbasierten Lösung
- Überprüfen Sie API-Keys
- Warten Sie bei Rate Limiting

#### 3. Performance-Probleme
**Ursache:** Große Datenmengen oder fehlende Indizes
**Lösung:**
- Datenbank-Indizes erstellen
- Caching aktivieren
- Pagination für große Resultate

### Debugging

#### Logs aktivieren
```php
// In .env
LOG_LEVEL=debug
```

#### Cache leeren
```bash
php artisan cache:clear
```

#### API-Status prüfen
```bash
php artisan geolocation:test 52.5200 13.4050 --method=nominatim
```

## Erweiterungen

### Zukünftige Features

1. **Polygon-basierte Suche** für administrative Grenzen
2. **Zeitzonen-Integration** basierend auf Koordinaten
3. **Wetter-API-Integration** für Standort-spezifische Daten
4. **Batch-Processing** für mehrere Koordinaten
5. **Geofencing** für ereignisbasierte Benachrichtigungen

### Custom Implementierungen

#### Eigene Suchlogik
```php
class CustomGeolocationService extends GeolocationService
{
    public function findNearestCityWithCustomLogic(float $lat, float $lng): ?City
    {
        // Ihre eigene Implementierung
        return City::where('custom_field', 'value')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get()
            ->sortBy(function ($city) use ($lat, $lng) {
                return $this->calculateDistance($lat, $lng, $city->lat, $city->lng);
            })
            ->first();
    }
}
```

## Support

Bei Fragen oder Problemen:

1. Überprüfen Sie die Tests: `php artisan test tests/Feature/GeolocationTest.php`
2. Testen Sie die API: `/api/geolocation/test`
3. Verwenden Sie den Debug-Command: `php artisan geolocation:test 52.5200 13.4050`
4. Überprüfen Sie die Logs: `storage/logs/laravel.log`

## Changelog

### Version 1.0.0
- Initiale Implementierung
- Datenbankbasierte Geolocation
- OpenStreetMap Nominatim Integration
- Google Geocoding API Integration
- RESTful API-Endpunkte
- Umfassende Test-Suite
- Artisan Commands
- Caching-System
