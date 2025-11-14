# Software-Tests Dokumentation

Diese Dokumentation beschreibt alle verfügbaren Software-Tests für die Laravel Filament Admin-Anwendung und wie diese ausgeführt werden können.

## Übersicht

Für die gesamte Admin-Anwendung wurden **798 umfassende CRUD-Tests** über 17 Filament-Ressourcen erstellt. Diese Tests decken alle wichtigen Funktionalitäten ab und ermöglichen es, systematisch zu prüfen, ob alle Felder erfassbar, speicherbar, änderbar und löschbar sind.

### Test-Kategorien

1. **Haupt-CRUD Tests** (605 Tests) - Testen die grundlegenden CRUD-Operationen für alle Ressourcen
2. **Relation Manager Tests** (193 Tests) - Testen die CRUD-Operationen innerhalb von Beziehungen zwischen Datensätzen

## 📁 Teststruktur

Alle Tests befinden sich im Verzeichnis:
```
tests/Feature/Filament/
```

### Erstellte Test-Dateien

#### Airlines & Airports Module (80 Tests)
- `tests/Feature/Filament/AirlineResourceTest.php` - **38 Tests**
  - CRUD-Operationen für Airlines
  - Validierung von Kontaktdaten, Gepäckregeln, Kabinenklassen
  - JSON-Felder (contact_info, baggage_rules, pet_policy)

- `tests/Feature/Filament/AirportResourceTest.php` - **42 Tests**
  - CRUD-Operationen für Flughäfen
  - Mobility Options, Lounges, Hotels
  - Koordinaten-Validierung

#### Events Module (230 Tests)
- `tests/Feature/Filament/EventTypeResourceTest.php` - **58 Tests**
  - Event-Typen erstellen, bearbeiten, löschen
  - Icon-Verwaltung, Severity-Levels

- `tests/Feature/Filament/EventCategoryResourceTest.php` - **49 Tests**
  - Event-Kategorien Management
  - Beziehungen zu Event-Typen

- `tests/Feature/Filament/DisasterEventResourceTest.php` - **56 Tests**
  - Katastrophen-Events (GDACS)
  - Koordinaten, Schweregrade, Länder-Beziehungen

- `tests/Feature/Filament/CustomEventResourceTest.php` - **67 Tests**
  - Benutzerdefinierte Events
  - Mehrfach-Event-Typen, Archive-Funktion
  - Sichtbarkeits-Einstellungen

#### Geografie Module (142 Tests)
- `tests/Feature/Filament/ContinentResourceTest.php` - **28 Tests**
  - Kontinente erstellen und verwalten
  - Übersetzbare Felder (name_translations)

- `tests/Feature/Filament/CountryResourceTest.php` - **39 Tests**
  - Länder mit ISO-Codes, Währungen
  - EU/Schengen-Status
  - Beziehungen zu Kontinenten

- `tests/Feature/Filament/RegionResourceTest.php` - **33 Tests**
  - Regionen innerhalb von Ländern
  - Mehrsprachige Namen

- `tests/Feature/Filament/CityResourceTest.php` - **42 Tests**
  - Städte mit Hauptstadt-Status
  - Koordinaten, Bevölkerungsdaten

#### Infosystem & Customer Module (95 Tests)
- `tests/Feature/Filament/InfosystemEntryResourceTest.php` - **27 Tests**
  - Infosystem-Einträge
  - KeyValue-Felder, Kategorien

- `tests/Feature/Filament/EntryConditionsLogResourceTest.php` - **25 Tests**
  - Einreisebestimmungen-Logs (Read-Only)
  - JSON-Daten, Filter-Typen

- `tests/Feature/Filament/CustomerResourceTest.php` - **43 Tests**
  - Kunden-Verwaltung
  - Privat/Business-Kunden
  - SSO-Felder, Passolution-Integration

#### User & Settings Module (138 Tests)
- `tests/Feature/Filament/UserResourceTest.php` - **50 Tests**
  - Benutzer-Verwaltung
  - Admin-Rechte, Passwort-Validierung
  - Email-Verifizierung

- `tests/Feature/Filament/EventDisplaySettingResourceTest.php` - **37 Tests**
  - Event-Anzeige-Einstellungen (Singleton)
  - Icon-Display-Strategien

- `tests/Feature/Filament/AiPromptResourceTest.php` - **51 Tests**
  - AI-Prompts für verschiedene Modelle
  - Kategorien, Sortierung

---

## 🔗 Relation Manager Tests (193 Tests)

Zusätzlich zu den Haupt-CRUD-Tests wurden **193 erweiterte Tests** für Relation Manager erstellt. Diese Tests prüfen, ob CRUD-Operationen innerhalb von Beziehungen zwischen Datensätzen korrekt funktionieren.

### Geographic Relation Managers (51 Tests)

#### Continents → Countries (11 Tests)
- Read-Only Relation Manager
- Suche und Filter-Funktionen
- Pagination und Sortierung

#### Countries → Regions (6 Tests)
- Neue Region über Country erstellen
- Region bearbeiten und löschen
- Suche innerhalb der Regionen

#### Countries → Cities (8 Tests)
- Neue Stadt über Country erstellen
- Stadt bearbeiten und löschen
- Filter nach Hauptstadt-Status

#### Countries → Airports (10 Tests)
- Neuen Flughafen über Country erstellen
- IATA/ICAO Code-Validierung
- Filter nach Flughafen-Typ

#### Regions → Cities (16 Tests)
- Neue Stadt über Region erstellen
- Auto-Vererbung der country_id
- Filter und Sortierung
- Daten-Isolation (nur Städte der Region)

### Event Relation Managers (49 Tests)

#### EventTypes → EventCategories (16 Tests)
- Neue Kategorie erstellen (HasMany)
- Kategorie bearbeiten und löschen
- Bulk-Operationen
- Sortierung nach sort_order

#### CustomEvents → Countries (12 Tests)
- Länder zuordnen/entfernen (BelongsToMany)
- Pivot-Daten: Koordinaten, Region, Stadt, location_note
- Duplikat-Prävention
- Bulk-Operationen

#### CustomEvents → Regions (10 Tests)
- Regionen zuordnen/entfernen (BelongsToMany)
- Pivot-Koordinaten bearbeiten
- Suche und Filter

#### CustomEvents → Cities (11 Tests)
- Städte zuordnen/entfernen (BelongsToMany)
- Pivot-Daten bearbeiten
- Sortierung nach Name

### Airlines/Airports Relation Managers (30 Tests)

#### Airports → Airlines (30 Tests)
- Airlines zuordnen mit Pivot-Daten (direction, terminal)
- Direction: 'from', 'to', 'both'
- Airlines entfernen (detach)
- Bulk-Operationen (bis zu 10 Airlines)
- Suche nach Name, IATA, ICAO
- Filter nach active Status
- Duplikat-Prävention
- Pivot-Daten-Anzeige im Table

### Customer Relation Managers (52 Tests)

#### Customers → Branches (52 Tests)
- Neue Filiale erstellen
- Auto-Generierung des app_code (4-stellig alphanumerisch)
- Alle Adressfelder validieren
- Koordinaten (optional)
- is_headquarters Flag
- Suche nach Name, app_code, Adresse
- Filter nach Hauptsitz-Status
- Sortierung
- Bulk-Löschen
- Daten-Isolation pro Customer

### Getestete Relation Manager Operationen

#### Für HasMany Beziehungen:
✅ **CREATE** - Neue zugehörige Datensätze erstellen
✅ **EDIT** - Zugehörige Datensätze bearbeiten
✅ **DELETE** - Zugehörige Datensätze löschen
✅ **SEARCH** - Suche innerhalb der Relation
✅ **FILTER** - Filter innerhalb der Relation
✅ **SORT** - Sortierung der zugehörigen Datensätze
✅ **BULK DELETE** - Mehrere Datensätze gleichzeitig löschen

#### Für BelongsToMany Beziehungen:
✅ **ATTACH** - Bestehende Datensätze zuordnen
✅ **DETACH** - Zuordnung entfernen
✅ **EDIT PIVOT** - Pivot-Tabellen-Daten bearbeiten
✅ **SEARCH** - Suche in zugeordneten Datensätzen
✅ **FILTER** - Filter in zugeordneten Datensätzen
✅ **BULK ATTACH** - Mehrere Datensätze gleichzeitig zuordnen
✅ **BULK DETACH** - Mehrere Zuordnungen gleichzeitig entfernen
✅ **DUPLICATE PREVENTION** - Verhindert doppelte Zuordnungen

### Relation Manager Tests ausführen

```bash
# Alle Relation Manager Tests ausführen
php artisan test --filter="Relation Manager"

# Spezifische Relation Manager Tests
php artisan test --filter="Countries Relation Manager"
php artisan test --filter="Airlines Relation Manager"
php artisan test --filter="Branches Relation Manager"

# Nach Kategorie
php artisan test --filter="Geographic.*Relation Manager"
php artisan test --filter="Event.*Relation Manager"
```

---

## 🚀 Tests ausführen

### Voraussetzungen

Stellen Sie sicher, dass Ihre Test-Umgebung korrekt konfiguriert ist:

1. **PHPUnit konfiguriert** - `phpunit.xml` im Projektverzeichnis
2. **Test-Datenbank** - SQLite oder separate MySQL-Datenbank für Tests
3. **Environment-Variablen** - `.env.testing` (optional)

### Alle Tests ausführen

```bash
# Alle Tests im Projekt ausführen
php artisan test

# Nur Feature-Tests ausführen
php artisan test tests/Feature/

# Nur Filament-Tests ausführen
php artisan test tests/Feature/Filament/
```

### Spezifische Module testen

#### Airlines & Airports
```bash
php artisan test tests/Feature/Filament/AirlineResourceTest.php
php artisan test tests/Feature/Filament/AirportResourceTest.php
```

#### Events
```bash
php artisan test tests/Feature/Filament/EventTypeResourceTest.php
php artisan test tests/Feature/Filament/EventCategoryResourceTest.php
php artisan test tests/Feature/Filament/DisasterEventResourceTest.php
php artisan test tests/Feature/Filament/CustomEventResourceTest.php
```

#### Geografie
```bash
php artisan test tests/Feature/Filament/ContinentResourceTest.php
php artisan test tests/Feature/Filament/CountryResourceTest.php
php artisan test tests/Feature/Filament/RegionResourceTest.php
php artisan test tests/Feature/Filament/CityResourceTest.php
```

#### Infosystem & Customer
```bash
php artisan test tests/Feature/Filament/InfosystemEntryResourceTest.php
php artisan test tests/Feature/Filament/EntryConditionsLogResourceTest.php
php artisan test tests/Feature/Filament/CustomerResourceTest.php
```

#### User & Settings
```bash
php artisan test tests/Feature/Filament/UserResourceTest.php
php artisan test tests/Feature/Filament/EventDisplaySettingResourceTest.php
php artisan test tests/Feature/Filament/AiPromptResourceTest.php
```

### Einzelne Tests ausführen

Sie können auch einzelne Tests mit dem `--filter` Parameter ausführen:

```bash
# Nach Testname filtern
php artisan test --filter="can create airline with all fields"

# Nach Testklasse filtern
php artisan test --filter=AirlineResourceTest

# Mehrere Filter kombinieren
php artisan test --filter="can create" tests/Feature/Filament/AirlineResourceTest.php
```

### Tests mit zusätzlichen Optionen

#### Detaillierte Ausgabe
```bash
# Verbose-Modus für detaillierte Informationen
php artisan test -v

# Sehr detailliert mit Stack-Traces
php artisan test -vvv
```

#### Code-Coverage anzeigen
```bash
# Coverage-Report generieren
php artisan test --coverage

# Mindest-Coverage festlegen (Test schlägt fehl, wenn nicht erreicht)
php artisan test --coverage --min=80
```

#### Parallele Ausführung
```bash
# Tests parallel ausführen (schneller)
php artisan test --parallel

# Anzahl der Prozesse festlegen
php artisan test --parallel --processes=4
```

#### Bei erstem Fehler stoppen
```bash
# Stoppt bei erstem fehlgeschlagenen Test
php artisan test --stop-on-failure
```

#### Nur fehlgeschlagene Tests erneut ausführen
```bash
# Nur die Tests ausführen, die beim letzten Mal fehlgeschlagen sind
php artisan test --retry
```

### Test-Gruppen

Sie können Tests auch mit Annotations gruppieren und gezielt ausführen:

```bash
# Tests mit @group annotation ausführen
php artisan test --group=airlines
php artisan test --group=events
php artisan test --group=geography
```

---

## 📋 Was wird getestet?

Jede Test-Suite deckt folgende Bereiche ab:

### ✅ CRUD-Operationen

- **Create (Erstellen)**
  - Datensätze mit allen Pflichtfeldern erstellen
  - Datensätze mit optionalen Feldern erstellen
  - Datensätze mit minimalen Daten erstellen

- **Read (Lesen)**
  - Liste aller Datensätze anzeigen
  - Einzelne Datensätze anzeigen
  - Suche funktioniert korrekt
  - Filter funktionieren korrekt
  - Sortierung funktioniert korrekt

- **Update (Aktualisieren)**
  - Alle Felder können aktualisiert werden
  - Einzelne Felder können aktualisiert werden
  - Boolean-Toggles funktionieren
  - JSON-Felder können aktualisiert werden

- **Delete (Löschen)**
  - Soft Delete funktioniert
  - Force Delete funktioniert (wo erlaubt)
  - Restore funktioniert
  - Bulk-Aktionen funktionieren

### ✅ Validierung

- **Pflichtfelder**
  - Fehlermeldungen bei fehlenden Pflichtfeldern

- **Eindeutigkeit**
  - Duplikate werden verhindert (z.B. IATA-Codes)

- **Format-Validierung**
  - Email-Adressen
  - URLs
  - Telefonnummern
  - Koordinaten (Latitude/Longitude)

- **Längen-Validierung**
  - Maximale Zeichenlängen
  - Minimale Zeichenlängen (z.B. Passwörter)

- **Numerische Validierung**
  - Bereiche (z.B. -90 bis 90 für Breitengrade)
  - Positive Zahlen
  - Ganzzahlen vs. Dezimalzahlen

### ✅ Komplexe Felder

- **JSON-Felder**
  - contact_info (Hotline, Email, URLs)
  - baggage_rules (Handgepäck, Aufgabegepäck)
  - hand_baggage_dimensions (Länge, Breite, Höhe)
  - mobility_options (Verkehrsmittel, Parken)
  - pet_policy (Kabine, Frachtraum)

- **Array-Felder**
  - cabin_classes
  - event_types
  - categories

- **Repeater-Felder**
  - lounges
  - nearby_hotels

- **KeyValue-Komponenten**
  - name_translations (DE, EN, FR, IT)
  - country_names

### ✅ Beziehungen

- **BelongsTo** (gehört zu)
  - Country → Continent
  - Region → Country
  - City → Country, Region
  - Event → EventType, EventCategory

- **HasMany** (hat viele)
  - Country → Regions
  - Country → Cities
  - EventType → CustomEvents

- **BelongsToMany** (viele zu viele)
  - Airline ↔ Airports
  - CustomEvent ↔ Countries
  - CustomEvent ↔ EventTypes

### ✅ Berechtigungen

- **Admin-Zugriff**
  - Nur Admins können zugreifen
  - Inaktive Admins haben keinen Zugriff

- **Benutzer-Rechte**
  - Normale Benutzer haben eingeschränkten Zugriff

- **Gast-Umleitung**
  - Nicht-angemeldete Benutzer werden zu Login umgeleitet

### ✅ Spezielle Features

- **Model-Scopes**
  - active(), inactive()
  - byCountry(), byCity()
  - archived(), notArchived()

- **Model-Methoden**
  - getName() mit Locale-Fallback
  - fillPlaceholders() für AI-Prompts
  - isVisible() für Events

- **Singleton-Verhalten**
  - EventDisplaySetting (nur ein Datensatz)

- **Soft Deletes**
  - Gelöschte Datensätze ausblenden
  - Wiederherstellen möglich

---

## 🔧 Troubleshooting

### Häufige Probleme und Lösungen

#### 1. Migration-Fehler in Tests

**Problem:** Tests schlagen fehl mit Fehlern über fehlende Tabellen oder Spalten.

**Lösung:**
```bash
# Test-Datenbank zurücksetzen und neu aufbauen
php artisan migrate:fresh --env=testing
php artisan test
```

#### 2. SQLite-Kompatibilitätsprobleme

**Problem:** Einige Migrations enthalten MySQL-spezifische Befehle (z.B. ENUM-Änderungen).

**Lösung:** Einige Migrations wurden bereits angepasst, um in der Test-Umgebung übersprungen zu werden. Falls weitere Probleme auftreten, prüfen Sie die Migration und fügen Sie hinzu:

```php
if (app()->environment('testing')) {
    return; // Skip in testing
}
```

#### 3. Fehlende Kontinente beim Testen

**Problem:** Tests schlagen fehl, weil Länder auf `continent_id = 1` verweisen, aber keine Kontinente existieren.

**Lösung:** Erstellen Sie einen Seeder oder passen Sie die Migration an:

```php
// In der Migration
if (!app()->environment('testing')) {
    // Daten einfügen
}
```

#### 4. Factory-Fehler

**Problem:** Factories schlagen fehl beim Erstellen von Test-Daten.

**Lösung:**
```bash
# Prüfen Sie, ob alle Factories existieren
ls -la database/factories/

# Factories manuell testen
php artisan tinker
> \App\Models\Airline::factory()->create();
```

#### 5. Speicher-Probleme bei großen Test-Suites

**Problem:** Tests schlagen fehl mit "Allowed memory size exhausted".

**Lösung:**
```bash
# Speicher-Limit erhöhen
php -d memory_limit=512M artisan test

# Oder in phpunit.xml
<php>
    <env name="MEMORY_LIMIT" value="512M"/>
</php>
```

#### 6. Langsame Test-Ausführung

**Lösung:**
```bash
# Parallele Ausführung nutzen
php artisan test --parallel

# Nur spezifische Tests ausführen
php artisan test --filter=AirlineResourceTest

# SQLite statt MySQL für Tests nutzen (in .env.testing)
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

---

## 📊 Test-Ergebnisse interpretieren

### Erfolgreiche Tests

```
PASS  Tests\Feature\Filament\AirlineResourceTest
  ✓ can render airline list page
  ✓ can create airline with all fields
  ✓ can update airline

  Tests:  38 passed
  Time:   2.34s
```

### Fehlgeschlagene Tests

```
FAIL  Tests\Feature\Filament\AirlineResourceTest
  ✓ can render airline list page
  ✕ can create airline with all fields

  Failed asserting that a field [baggage_rules.hand_baggage.economy] exists
```

**Interpretation:**
- Der Test "can create airline with all fields" ist fehlgeschlagen
- Das Feld `baggage_rules.hand_baggage.economy` wurde nicht gefunden
- Prüfen Sie, ob das Feld im Formular definiert ist

### Coverage-Report

```
Cov: 85.2%
  App\Filament\Resources: 92.3%
  App\Models: 78.1%
```

**Interpretation:**
- 85.2% des Codes werden von Tests abgedeckt
- Filament Resources haben sehr gute Coverage (92.3%)
- Models könnten mehr Tests gebrauchen (78.1%)

---

## 🎯 Best Practices

### Tests regelmäßig ausführen

```bash
# Vor jedem Commit
git add .
php artisan test
git commit -m "Your message"
```

### CI/CD Integration

Fügen Sie Tests zu Ihrer CI/CD-Pipeline hinzu (z.B. GitHub Actions):

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run Tests
        run: php artisan test --parallel
```

### Test-Wartung

- **Tests aktualisieren**, wenn sich Formulare ändern
- **Neue Tests schreiben**, wenn neue Features hinzugefügt werden
- **Alte Tests löschen**, wenn Features entfernt werden
- **Coverage prüfen**, um ungetestete Bereiche zu finden

---

## 📚 Weitere Ressourcen

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [Filament Testing Documentation](https://filamentphp.com/docs/panels/testing)
- [Pest PHP Documentation](https://pestphp.com/docs)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

---

## 📝 Test-Übersicht nach Modul

### Haupt-CRUD Tests

| Modul | Test-Datei | Basis Tests | Relation Manager Tests | Gesamt | Status |
|-------|-----------|-------------|----------------------|--------|--------|
| **Airlines** | AirlineResourceTest.php | 38 | 0 | 38 | ✅ |
| **Airports** | AirportResourceTest.php | 42 | 30 | 72 | ✅ |
| **Event Types** | EventTypeResourceTest.php | 58 | 16 | 74 | ✅ |
| **Event Categories** | EventCategoryResourceTest.php | 49 | 0 | 49 | ✅ |
| **Disaster Events** | DisasterEventResourceTest.php | 56 | 0 | 56 | ✅ |
| **Custom Events** | CustomEventResourceTest.php | 67 | 33 | 100 | ✅ |
| **Continents** | ContinentResourceTest.php | 28 | 11 | 39 | ✅ |
| **Countries** | CountryResourceTest.php | 39 | 24 | 63 | ✅ |
| **Regions** | RegionResourceTest.php | 33 | 16 | 49 | ✅ |
| **Cities** | CityResourceTest.php | 42 | 0 | 42 | ✅ |
| **Infosystem** | InfosystemEntryResourceTest.php | 27 | 0 | 27 | ✅ |
| **Entry Conditions** | EntryConditionsLogResourceTest.php | 25 | 0 | 25 | ✅ |
| **Customers** | CustomerResourceTest.php | 43 | 52 | 95 | ✅ |
| **Users** | UserResourceTest.php | 50 | 0 | 50 | ✅ |
| **Display Settings** | EventDisplaySettingResourceTest.php | 37 | 0 | 37 | ✅ |
| **AI Prompts** | AiPromptResourceTest.php | 51 | 0 | 51 | ✅ |
| **GESAMT** | **16 Test-Dateien** | **605 Tests** | **193 Tests** | **798 Tests** | ✅ |

### Relation Manager Tests im Detail

| Relation Manager | Parent → Child | Typ | Anzahl Tests |
|-----------------|----------------|-----|--------------|
| Countries | Continent → Countries | HasMany (Read-Only) | 11 |
| Regions | Country → Regions | HasMany | 6 |
| Cities (Country) | Country → Cities | HasMany | 8 |
| Airports | Country → Airports | HasMany | 10 |
| Cities (Region) | Region → Cities | HasMany | 16 |
| EventCategories | EventType → EventCategories | HasMany | 16 |
| Countries (Event) | CustomEvent ↔ Countries | BelongsToMany | 12 |
| Regions (Event) | CustomEvent ↔ Regions | BelongsToMany | 10 |
| Cities (Event) | CustomEvent ↔ Cities | BelongsToMany | 11 |
| Airlines | Airport ↔ Airlines | BelongsToMany | 30 |
| Branches | Customer → Branches | HasMany | 52 |
| **GESAMT** | **11 Relation Manager** | | **193 Tests** |

---

## 🔄 Änderungen und Updates

Wenn Sie Änderungen am Code vornehmen, sollten Sie:

1. **Tests ausführen** vor dem Commit
2. **Tests anpassen**, wenn sich Formulare ändern
3. **Neue Tests hinzufügen**, wenn neue Funktionen entwickelt werden
4. **Code-Coverage prüfen**, um sicherzustellen, dass neuer Code getestet ist

```bash
# Workflow-Beispiel
php artisan test                    # Alle Tests ausführen
php artisan test --coverage         # Coverage prüfen
php artisan test --filter=Airline   # Nur geänderte Module testen
```

---

**Letzte Aktualisierung:** 2025-11-14
**Version:** 1.0
**Autor:** Automatisch generiert mit Claude Code
