# Filialen-Import Setup

## Voraussetzungen

Um den Filialen-Import nutzen zu können, müssen folgende Voraussetzungen erfüllt sein:

### 1. Branch Management aktivieren

Im Customer-Dashboard muss "Filialen & Standorte" aktiviert sein:
- Dashboard öffnen
- "Filialen & Standorte" Toggle aktivieren
- Import/Export Buttons erscheinen

### 2. Queue-Tabellen migrieren

```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

### 3. Queue-Worker starten

Der Queue-Worker MUSS laufen, damit Import/Export-Jobs verarbeitet werden:

```bash
# Im Hintergrund starten
php artisan queue:work --tries=3 --timeout=300 &

# Oder mit Supervisor (empfohlen für Produktion)
# siehe: https://laravel.com/docs/11.x/queues#supervisor-configuration
```

**WICHTIG**: Ohne laufenden Queue-Worker wird der Import nicht verarbeitet!

### 4. Storage-Link erstellen

```bash
php artisan storage:link
```

## CSV-Format

Die Import-CSV muss folgendes Format haben:

```csv
Name,Zusatz,Straße,Hausnummer,PLZ,Stadt,Land
Musterfirma GmbH,Hauptsitz,Musterstraße,123,12345,Berlin,Deutschland
Zweigstelle Nord,,Nordweg,45,54321,Hamburg,Deutschland
```

**Pflichtfelder**: Name, Straße, PLZ, Stadt
**Optionale Felder**: Zusatz, Hausnummer, Land (Standard: Deutschland)

## Duplikat-Prüfung

Beim Import werden Duplikate automatisch erkannt und übersprungen.

**Duplikat-Kriterien**:
- Gleicher Name UND
- Gleiche Straße UND
- Gleiche Hausnummer UND
- Gleiche PLZ UND
- Gleiche Stadt

## Benachrichtigungen

Nach Abschluss des Imports/Exports erhalten Sie eine Benachrichtigung:
- In der App (Bell-Icon im Header)
- Per E-Mail

Die Benachrichtigung zeigt:
- Anzahl importierter Einträge
- Anzahl übersprungener Einträge (Duplikate)
- Anzahl fehlgeschlagener Einträge
- Ggf. Fehlermeldungen

## Fehlersuche

Falls der Import nicht funktioniert:

```bash
# Diagnose-Script ausführen
./diagnose-import.sh
```

### Häufige Fehler:

1. **"Fehler beim Starten des Imports"**
   - Queue-Worker läuft nicht → `php artisan queue:work &`
   - Jobs-Tabelle fehlt → `php artisan queue:table && php artisan migrate`

2. **Import wird nicht verarbeitet**
   - Queue-Worker läuft nicht
   - Job ist fehlgeschlagen → `php artisan queue:failed`

3. **Keine Benachrichtigung**
   - Queue-Worker läuft nicht
   - Notifications-Tabelle fehlt → `php artisan notifications:table && php artisan migrate`

4. **Download funktioniert nicht**
   - Storage-Link fehlt → `php artisan storage:link`
   - Exports-Verzeichnis fehlt → wird automatisch erstellt

## Logs

Import-Logs finden Sie in:
- `storage/logs/laravel.log`

Queue-Worker Logs:
```bash
# Aktuelle Jobs ansehen
php artisan queue:work --verbose

# Fehlgeschlagene Jobs
php artisan queue:failed
```
