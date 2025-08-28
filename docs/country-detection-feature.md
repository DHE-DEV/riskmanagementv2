# Länderermittlung aus Geokoordinaten - Feature Dokumentation

## Übersicht

Die Länderermittlung aus Geokoordinaten ist eine neue Funktionalität, die es ermöglicht, automatisch das Land basierend auf Breiten- und Längengrad-Koordinaten zu ermitteln. Diese Funktion ist in das GDACS-Datensatz-Bearbeitungsformular integriert.

## Funktionalität

### Hauptmerkmale

- **Automatische Länderermittlung**: Ermittelt das Land basierend auf eingegebenen Koordinaten
- **OpenStreetMap Integration**: Nutzt die Nominatim API für präzise Reverse Geocoding
- **Fallback-Mechanismus**: Fällt bei API-Fehlern auf die datenbankbasierte Lösung zurück
- **Benutzerfreundliche UI**: Schaltfläche direkt neben dem Land-Auswahlfeld
- **Validierung**: Überprüft Koordinaten auf Gültigkeit
- **Caching**: Speichert API-Ergebnisse für bessere Performance

### Integration in Filament

Die Funktionalität ist in das `DisasterEventForm` integriert und fügt eine Schaltfläche neben dem Land-Auswahlfeld hinzu:

```php
// Land-Auswahl mit Länderermittlung-Schaltfläche
Group::make([
    Select::make('country_id')
        ->label('Land')
        ->relationship('country', 'iso_code')
        ->columnSpan(2),
    
    // Länderermittlung-Schaltfläche
    Action::make('detect_country')
        ->label('Land aus Koordinaten ermitteln')
        ->icon('heroicon-o-map-pin')
        ->color('info')
        ->size('sm')
        ->tooltip('Ermittelt automatisch das Land basierend auf den Geokoordinaten')
        ->requiresConfirmation()
        ->action(function ($record, $livewire) {
            return self::detectCountryFromCoordinates($record, $livewire);
        })
        ->columnSpan(1),
])->columns(3)
```

## Verwendung

### Schritt-für-Schritt Anleitung

1. **Koordinaten eingeben**: Geben Sie Breiten- und Längengrad in die entsprechenden Felder ein
2. **Schaltfläche klicken**: Klicken Sie auf "Land aus Koordinaten ermitteln"
3. **Bestätigung**: Bestätigen Sie die Aktion im Modal-Dialog
4. **Ergebnis**: Das Land wird automatisch im Land-Auswahlfeld gesetzt

### Beispiel

```
Koordinaten: 52.5200, 13.4050 (Berlin)
→ Klick auf "Land aus Koordinaten ermitteln"
→ Ergebnis: Deutschland (DE) wird automatisch ausgewählt
```

## Technische Implementierung

### Services

#### ReverseGeocodingService
Hauptservice für die API-basierte Länderermittlung:

```php
$reverseGeocodingService = app(ReverseGeocodingService::class);
$locationInfo = $reverseGeocodingService->getLocationFromCoordinates($lat, $lng);
```

#### GeolocationService
Fallback-Service für datenbankbasierte Länderermittlung:

```php
$geolocationService = app(GeolocationService::class);
$nearestCountry = $geolocationService->findNearestCountry($lat, $lng);
```

### API-Integration

#### OpenStreetMap Nominatim
- **URL**: `https://nominatim.openstreetmap.org/reverse`
- **Parameter**: `lat`, `lon`, `format=json`, `addressdetails=1`
- **Rate Limiting**: 1 Request pro Sekunde
- **Kostenlos**: Keine API-Key erforderlich

#### Google Geocoding API (Optional)
- **URL**: `https://maps.googleapis.com/maps/api/geocode/json`
- **Parameter**: `latlng`, `key`, `language=de`
- **Voraussetzung**: Google Maps API Key
- **Kostenpflichtig**: Nach Freemium-Limit

### Caching

Alle API-Aufrufe werden für 1 Stunde gecacht:

```php
Cache::remember($cacheKey, 3600, function () {
    // API-Aufruf
});
```

**Cache-Keys:**
- `reverse_geocode_{lat}_{lng}` für Nominatim
- `google_reverse_geocode_{lat}_{lng}` für Google

## Validierung

### Koordinaten-Validierung

```php
// Prüfe ob Koordinaten vorhanden sind
if (empty($lat) || empty($lng)) {
    // Warnung: Keine Koordinaten vorhanden
    return;
}

// Validiere Koordinaten
if (!is_numeric($lat) || !is_numeric($lng) || 
    $lat < -90 || $lat > 90 || 
    $lng < -180 || $lng > 180) {
    // Warnung: Ungültige Koordinaten
    return;
}
```

### Fehlerbehandlung

1. **Keine Koordinaten**: Warnung anzeigen
2. **Ungültige Koordinaten**: Validierungsfehler
3. **API-Fehler**: Fallback zur Datenbank
4. **Kein Land gefunden**: Warnung anzeigen
5. **System-Fehler**: Fehlermeldung mit Logging

## Benutzeroberfläche

### Schaltfläche
- **Icon**: `heroicon-o-map-pin`
- **Farbe**: `info` (blau)
- **Größe**: `sm` (klein)
- **Tooltip**: "Ermittelt automatisch das Land basierend auf den Geokoordinaten"

### Modal-Dialog
- **Titel**: "Land aus Koordinaten ermitteln"
- **Beschreibung**: Erklärt die Funktionalität
- **Buttons**: "Land ermitteln" / "Abbrechen"

### Benachrichtigungen

#### Erfolg
```php
Notification::make()
    ->title('Land erfolgreich ermittelt')
    ->body("Land: Deutschland (DE)")
    ->success()
    ->send();
```

#### Warnung
```php
Notification::make()
    ->title('Keine Koordinaten vorhanden')
    ->body('Bitte geben Sie zuerst Breiten- und Längengrad ein.')
    ->warning()
    ->send();
```

#### Fehler
```php
Notification::make()
    ->title('Fehler bei der Länderermittlung')
    ->body('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.')
    ->danger()
    ->send();
```

## Logging

### Erfolgreiche Länderermittlung
```php
Log::info('Land automatisch aus Koordinaten ermittelt', [
    'coordinates' => ['lat' => 52.5200, 'lng' => 13.4050],
    'country' => ['id' => 1, 'name' => 'Deutschland', 'iso_code' => 'DE'],
    'record_id' => 123,
]);
```

### Fehler-Logging
```php
Log::error('Fehler bei der automatischen Länderermittlung', [
    'record_id' => 123,
    'error' => 'API timeout',
]);
```

## Testing

### Test-Suite
```bash
# Alle Tests ausführen
php artisan test tests/Feature/CountryDetectionTest.php

# Spezifische Tests
php artisan test --filter="ermittelt Land aus Koordinaten korrekt"
```

### Test-Szenarien
1. **Erfolgreiche Länderermittlung**
2. **Ungültige Koordinaten**
3. **API-Fehler**
4. **Caching-Funktionalität**
5. **Fallback-Mechanismus**

## Performance

### Optimierungen
- **Caching**: 1 Stunde Cache für API-Aufrufe
- **Fallback**: Datenbankbasierte Lösung bei API-Fehlern
- **Validierung**: Frühe Validierung vor API-Aufrufen
- **Logging**: Strukturiertes Logging für Debugging

### Monitoring
- **API-Response-Zeiten**: Überwacht über Logs
- **Cache-Hit-Rate**: Überwacht über Cache-Statistiken
- **Fehler-Rate**: Überwacht über Error-Logs

## Erweiterungen

### Zukünftige Features
1. **Batch-Processing**: Mehrere Koordinaten gleichzeitig
2. **Erweiterte Geodaten**: Stadt, Region, Kontinent
3. **Zeitzonen-Integration**: Automatische Zeitzonenermittlung
4. **Wetter-Integration**: Standort-spezifische Wetterdaten
5. **Polygon-Suche**: Administrative Grenzen

### Custom Implementierungen
```php
class CustomCountryDetectionService extends ReverseGeocodingService
{
    public function detectCountryWithCustomLogic(float $lat, float $lng): ?Country
    {
        // Ihre eigene Implementierung
        return Country::where('custom_field', 'value')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get()
            ->sortBy(function ($country) use ($lat, $lng) {
                return $this->calculateDistance($lat, $lng, $country->lat, $country->lng);
            })
            ->first();
    }
}
```

## Troubleshooting

### Häufige Probleme

#### 1. Kein Land gefunden
**Ursache**: Koordinaten außerhalb bekannter Länder
**Lösung**: 
- Überprüfen Sie die Koordinaten
- Verwenden Sie manuelle Landauswahl
- Erweitern Sie die Datenbank um mehr Länder

#### 2. API-Fehler
**Ursache**: Rate Limiting oder Netzwerkprobleme
**Lösung**:
- Warten Sie bei Rate Limiting
- Überprüfen Sie die Internetverbindung
- Fallback zur datenbankbasierten Lösung

#### 3. Ungültige Koordinaten
**Ursache**: Falsche Koordinaten-Eingabe
**Lösung**:
- Überprüfen Sie das Koordinaten-Format
- Verwenden Sie Dezimalgrad (z.B. 52.5200)
- Stellen Sie sicher, dass Koordinaten im gültigen Bereich sind

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
# Test mit Artisan Command
php artisan geolocation:test 52.5200 13.4050 --method=nominatim
```

## Support

Bei Fragen oder Problemen:

1. **Überprüfen Sie die Logs**: `storage/logs/laravel.log`
2. **Testen Sie die API**: `/api/geolocation/test`
3. **Verwenden Sie den Debug-Command**: `php artisan geolocation:test 52.5200 13.4050`
4. **Konsultieren Sie die Dokumentation**: `docs/geolocation.md`

## Changelog

### Version 1.0.0
- Initiale Implementierung der Länderermittlung
- Integration in DisasterEventForm
- OpenStreetMap Nominatim Integration
- Fallback-Mechanismus zur Datenbank
- Caching-System
- Umfassende Validierung
- Benutzerfreundliche UI
- Vollständige Test-Suite
