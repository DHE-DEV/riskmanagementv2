# Dokumentation

## Übersicht

Diese Dokumentation beschreibt die verschiedenen Systeme und Funktionen der Risk Management Anwendung.

## Verfügbare Dokumentation

### [Geolocation-System](./geolocation.md)
Umfassende Dokumentation des Geolocation-Systems, das Geokoordinaten mit Datenbanktabellen abgleicht.

**Inhalt:**
- Architektur und Services
- API-Endpunkte und Verwendung
- Algorithmen und Entfernungsberechnung
- Integration mit externen APIs (OpenStreetMap, Google)
- Performance-Optimierung und Caching
- Testing und Troubleshooting
- Beispiele für Frontend- und Backend-Integration

**Schnellstart:**
```bash
# API testen
curl "http://localhost/api/geolocation/find-location?lat=52.5200&lng=13.4050"

# Artisan Command verwenden
php artisan geolocation:test 52.5200 13.4050

# Tests ausführen
php artisan test tests/Feature/GeolocationTest.php
```

## Weitere Dokumentation

Weitere Dokumentation wird hier hinzugefügt, sobald neue Systeme implementiert werden.

## Support

Bei Fragen zur Dokumentation oder technischen Problemen:

1. Überprüfen Sie die entsprechenden Test-Dateien
2. Verwenden Sie die bereitgestellten Artisan Commands
3. Konsultieren Sie die Laravel-Dokumentation
4. Überprüfen Sie die Logs in `storage/logs/`
