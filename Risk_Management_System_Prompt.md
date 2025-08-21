# Erweiterter Prompt für Risk Management System mit Aufgabenunterteilung und Subagenten

**Erstelle ein modernes Risk Management System für globale Katastrophenereignisse mit Laravel Filament Admin-Panel, erweiterten Funktionen, Wetter-Integration und Integration in die bestehende MySQL 8 Datenbankstruktur. Das Projekt soll in sinnvolle Aufgaben unterteilt und mit Subagenten umgesetzt werden:**

## 🎯 **Hauptfunktionen**

### **1. Echtzeit-Katastrophenmonitoring**
- Integration mit GDACS (Global Disaster Alert and Coordination System) API
- Automatische Aktualisierung von Katastrophendaten alle 30 Minuten
- Echtzeit-Benachrichtigungen über neue Ereignisse via Server-Sent Events
- Manueller Refresh-Button für sofortige Datenaktualisierung

### **2. Interaktive Weltkarte**
- Leaflet.js-basierte Karte mit deutschen Kartendaten (OpenStreetMap DE)
- Farbcodierte Marker: Rot (kritisch), Orange (hoch), Grün (niedrig)
- Blaue Flughafen-Marker mit weißem Flugzeug-Icon
- **Benutzerdefinierte Marker**: Eigene Farben, FontAwesome-Symbole und Symbol-Farben
- Satelliten-/Straßenansicht-Toggle
- Zoom-Kontrollen und Kartenzentrierung
- Marker-Größe basierend auf Risikostufe

### **3. Erweiterte Filterfunktionen**
- Warnstufen-Filter (Rot/Orange/Grün)
- Event-Typ-Filter (Erdbeben, Hurrikan, Überschwemmung, Waldbrand, Vulkan, Dürre)
- Zeitraum-Filter (24h, 7 Tage, 30 Tage, alle)
- Länder-/Regionssuche mit deutscher Namenszuordnung
- **Kontinent-Filter** mit Buttons für alle Kontinente
- **Flughafen-Suche** nach Land oder 3-Letter-Code
- **Benutzerdefinierte Event-Filter**: Nach Marker-Farbe, Symbol, Erstellungsdatum

### **4. Live-Statistiken**
- Gesamtanzahl Events
- Aktive Events
- Events der letzten 7 Tage
- Hochrisiko-Events (Rot/Orange)
- **Manuelle Events**: Anzahl und Verteilung

### **5. Event-Details & Modal**
- Detaillierte Event-Informationen in Modal-Fenster
- GDACS-Bericht-Links
- Bevölkerungsdaten und Magnitude-Informationen
- Kartenfokus-Funktion

## 🌤️ **Wetter- und Zeitzonen-Integration**

### **Marker-Popup-Erweiterungen**
- **Aktuelle Ortszeit**: Anzeige der lokalen Zeit am Event-Standort
- **Zeitzonen-Berechnung**: Automatische Berechnung basierend auf Koordinaten
- **Vergleich mit Berlin-Zeit**: Anzeige der Zeitdifferenz zu Europe/Berlin
- **Wetter-Informationen**: Aktuelle Temperatur und Wetterbedingungen
- **Bedingte Anzeige**: Wetter nur anzeigen wenn `WEATHER=true` in .env

### **OpenWeatherMap Integration**
- API-Key aus `OPENWEATHER_API_KEY` in .env
- Automatische Wetterabfrage für Event-Standorte
- Caching der Wetterdaten (60 Minuten)
- Fallback bei API-Fehlern
- Wetter-Icons und Beschreibungen

### **Zeitzonen-Service**
- Automatische Zeitzonen-Erkennung basierend auf Koordinaten
- Verwendung der `timezone` API oder lokaler Zeitzonen-Datenbank
- Formatierung der Zeiten in deutscher Lokalisierung
- Sommer-/Winterzeit-Berücksichtigung

## 🏗️ **Laravel Filament Admin-Panel (Version 4)**

### **Benutzerverwaltung**
- Benutzer anlegen, bearbeiten und löschen (basierend auf `users` Tabelle)
- Rollen und Berechtigungen (admin/user Rollen)
- Benutzerprofile und Einstellungen

### **Event-Management**
- **GDACS Events**: Automatisch importierte Events aus `disaster_events` Tabelle anzeigen und bearbeiten
- **Manuelle Events**: Eigene Events in `custom_events` Tabelle erstellen und verwalten
- **Erweiterter Event-Editor** mit allen Feldern:
  - Titel, Beschreibung, Typ, Koordinaten
  - **Marker-Farbe**: Farbauswahl (HEX, RGB, vordefinierte Farben)
  - **FontAwesome-Symbol**: Symbol-Auswahl mit Vorschau
  - **Symbol-Farbe**: Separate Farbauswahl für das Symbol
  - **Google Maps Koordinaten-Paste**: Automatische Lat/Lng-Aufteilung
  - **Marker-Größe**: Anpassbare Größe (klein, mittel, groß)
  - **Popup-Inhalt**: Benutzerdefinierter HTML-Content
  - **Zeitstempel**: Start- und Enddatum
  - **Priorität**: Niedrig, Mittel, Hoch, Kritisch
  - **Kategorien**: Benutzerdefinierte Kategorien
  - **Tags**: Schlagworte für bessere Organisation
- Bulk-Operationen für Events
- Event-Status-Management (aktiv/inaktiv)

### **Flughafen-Management**
- Flughäfen aus `airports` Tabelle anzeigen, bearbeiten und verwalten
- IATA/ICAO Code und Name verwalten
- Länderzuordnung über `countries` Tabelle
- Koordinaten-Management

### **Kontinent-Management**
- Kontinente aus `continents` Tabelle verwalten
- Geografische Grenzen definieren
- Kartenzentrierung für jeden Kontinent

### **System-Verwaltung**
- GDACS-API-Einstellungen
- OpenWeatherMap API-Konfiguration
- Datenimport/Export
- System-Logs und Monitoring
- Backup-Management

## 🎨 **UI/UX Design (wie im Bild)**

### **Layout-Struktur**
- **Fester Header oben**: Logo, Suchleiste, Status-Anzeige, Refresh-Button
- **Schwarzer Balken links**: Navigation mit Icons (Hamburger, Liste, Ziel, Einstellungen)
- **Grauer Sidebar-Bereich**: Collapsible Panels für Filter und Events
- **Hauptkartenbereich**: Dynamisch anpassende Größe
- **Fester Footer unten**: Copyright, Links, System-Informationen

### **Header (Feststehend)**
- **Logo**: Grünes/weißes Logo links (stylisierte 'A' oder Dreieck)
- **Suchleiste**: "Land suchen..." mit Dropdown-Funktionalität
- **Status-Anzeige**: "Aktualisiert: 1 hour ago" mit Refresh-Icon
- **Benutzer-Menü**: Profil, Einstellungen, Logout (falls authentifiziert)
- **Admin-Link**: Direkter Link zum Filament Admin-Panel

### **Footer (Feststehend)**
- **Copyright**: "© 2025 Risk Management System"
- **Links**: Impressum, Datenschutz, Hilfe, API-Dokumentation
- **System-Info**: Version, Build-Datum, Support-Kontakt
- **Powered By**: "Powered by Passolution GmbH" (wie im Bild)

### **Sidebar-Panels**
- **Live Statistiken**: Collapsible mit Dropdown-Pfeil
- **Aktuelle Ereignisse**: Event-Liste mit farbigen Dots und Icons
- **Filter**: Erweiterte Filteroptionen
- **Kontinente**: Button-Reihe für Kontinent-Auswahl
- **Flughafen**: Suchfelder für Land und Flughafen-Code
- **Karten-Steuerung**: "Karte zentrieren" Button

### **Responsive Design**
- Desktop: 3-Spalten-Layout (Navigation, Sidebar, Karte) mit Header/Footer
- Mobile: Overlay-Sidebar mit Touch-Gesten, angepasster Header/Footer
- Tablet: Angepasste Breakpoints mit Header/Footer

### **Dynamische Anpassung**
- Karte passt sich automatisch an verfügbaren Platz zwischen Header und Footer an
- Sidebar-Panels können ein-/ausgeklappt werden ohne Header/Footer zu beeinflussen
- Smooth Transitions beim Ein-/Ausklappen von Panels

## 📊 **Datenbankintegration (MySQL 8)**

### **Bestehende Tabellen nutzen:**
- `disaster_events` - GDACS Events mit allen Feldern
- `custom_events` - Manuelle Events (erweitert)
- `airports` - Flughafen-Daten mit IATA/ICAO Codes
- `continents` - Kontinent-Informationen
- `countries` - Länder-Daten mit Übersetzungen
- `cities` - Städte-Daten
- `regions` - Regionen-Daten
- `users` - Benutzer-Verwaltung

### **Erweiterte `custom_events` Tabelle:**
```sql
ALTER TABLE `custom_events` ADD COLUMN `marker_color` varchar(7) DEFAULT '#FF0000';
ALTER TABLE `custom_events` ADD COLUMN `marker_icon` varchar(100) DEFAULT 'fa-map-marker';
ALTER TABLE `custom_events` ADD COLUMN `icon_color` varchar(7) DEFAULT '#FFFFFF';
ALTER TABLE `custom_events` ADD COLUMN `marker_size` enum('small','medium','large') DEFAULT 'medium';
ALTER TABLE `custom_events` ADD COLUMN `popup_content` text;
ALTER TABLE `custom_events` ADD COLUMN `category` varchar(100);
ALTER TABLE `custom_events` ADD COLUMN `tags` json;
ALTER TABLE `custom_events` ADD COLUMN `created_by` bigint UNSIGNED;
ALTER TABLE `custom_events` ADD COLUMN `updated_by` bigint UNSIGNED;
```

### **Erweiterte Felder für bestehende Tabellen:**
- `continents`: `bounds` (JSON), `center_lat`, `center_lng`, `zoom_level`
- `airports`: `is_active` (Boolean)

### **Neue Tabellen (falls benötigt):**
- `continent_bounds` - Geografische Grenzen für Kontinente
- `airport_markers` - Flughafen-Marker-Konfiguration
- `weather_cache` - Gecachte Wetterdaten
- `custom_event_categories` - Kategorien für manuelle Events
- `custom_event_tags` - Tags für manuelle Events

## 🚀 **Technische Implementierung**

### **Laravel Filament 4 Setup**
- Vollständige Admin-Panel-Integration
- Custom Resources für alle Models
- Custom Widgets für Dashboard
- Custom Actions für Bulk-Operationen
- Custom Filters und Suchen
- **Custom Form Components** für:
  - Farbauswahl (Color Picker)
  - FontAwesome-Symbol-Auswahl mit Vorschau
  - Google Maps Koordinaten-Paste-Funktion
  - Marker-Größen-Auswahl
  - Kategorie- und Tag-Management

### **API-Erweiterungen**
- `GET /api/airports` - Flughafen-Daten aus `airports` Tabelle
- `GET /api/continents` - Kontinent-Daten aus `continents` Tabelle
- `GET /api/manual-events` - Manuelle Events aus `custom_events` Tabelle
- `POST /api/events` - Event erstellen/bearbeiten
- `GET /api/countries` - Länder-Daten mit Übersetzungen
- `GET /api/weather/{lat}/{lng}` - Wetterdaten für Koordinaten
- `GET /api/timezone/{lat}/{lng}` - Zeitzonen-Daten für Koordinaten
- `POST /api/parse-coordinates` - Google Maps Koordinaten parsen
- `GET /api/fontawesome-icons` - Verfügbare FontAwesome-Symbole

### **Frontend-Erweiterungen**
- Flughafen-Marker-Layer basierend auf `airports` Tabelle
- Kontinent-Button-Komponente basierend auf `continents` Tabelle
- Erweiterte Filter-Logik mit Datenbank-Integration
- Dynamische Kartenanpassung
- **Erweiterte Popup-Inhalte** mit Wetter und Zeitzonen
- **Feststehende Header/Footer-Komponenten**
- **Benutzerdefinierte Marker-Rendering** mit:
  - Individuellen Farben
  - FontAwesome-Symbolen
  - Symbol-Farben
  - Anpassbaren Größen
  - Benutzerdefinierten Popup-Inhalten

### **Wetter-Service-Integration**
- OpenWeatherMap API-Client
- Wetterdaten-Caching (Redis/Database)
- Fallback-Mechanismen bei API-Fehlern
- Wetter-Icon-Integration

### **Zeitzonen-Service**
- Zeitzonen-API-Integration
- Lokale Zeitzonen-Datenbank als Fallback
- Automatische Sommer-/Winterzeit-Erkennung
- Deutsche Zeitformatierung

### **Koordinaten-Parsing-Service**
- Google Maps Koordinaten-Format-Erkennung
- Automatische Lat/Lng-Extraktion
- Validierung der Koordinaten
- Fallback bei ungültigen Formaten

## 📱 **Mobile Optimierung**

### **Touch-Freundlich**
- Große Touch-Targets für Kontinent-Buttons
- Swipe-Gesten für Sidebar
- Optimierte Marker-Größen
- Responsive Typography
- Angepasster Header/Footer für Mobile

### **Performance**
- Lazy Loading für alle Daten
- Wetterdaten-Caching
- Optimierte Bildkompression
- Caching-Strategien
- CDN-Integration

## 🔒 **Sicherheit & Berechtigungen**

### **Filament Admin-Sicherheit**
- Rollenbasierte Zugriffskontrolle basierend auf `users.is_admin`
- Admin-Bereich-Authentifizierung
- Audit-Logging für Änderungen
- Backup-Strategien

### **API-Sicherheit**
- CSRF-Protection
- Rate Limiting
- Input Validation
- SQL Injection Prevention
- API-Key-Sicherheit für OpenWeatherMap

## 🗄️ **Datenbank-Konfiguration**

### **MySQL 8 Setup**
- Verwendung der bestehenden `riskmanagement` Datenbank
- Alle vorhandenen Tabellen und Beziehungen beibehalten
- Optimierte Indizes für Performance
- UTF8MB4 Zeichensatz für vollständige Unicode-Unterstützung

### **Datenbank-Verbindung**
- Konfiguration in `.env` Datei
- Connection Pooling für bessere Performance
- Backup-Strategien implementieren

## ⚙️ **Umgebungsvariablen (.env)**

### **Bestehende Variablen:**
- `DB_CONNECTION=mysql`
- `DB_HOST=db-mysql-fra1-54684-do-user-3259482-0.f.db.ondigitalocean.com`
- `DB_PORT=25060`
- `DB_DATABASE=riskmanagement`
- `DB_USERNAME=riskmanagementuser`
- `DB_PASSWORD=***REDACTED***`

### **Neue Variablen:**
- `OPENWEATHER_API_KEY=a3bebb1992c8a0cc627e5d315d12f249`
- `WEATHER=true` (Boolean für Wetter-Anzeige)
- `WEATHER_CACHE_DURATION=900` (60 Minuten in Sekunden)
- `TIMEZONE_API_PROVIDER=open-meteo`
- `TIMEZONE_API_URL=https://api.open-meteo.com/v1/forecast`
- `APP_NAME="Risk Management System"`
- `APP_VERSION=1.0.0`
- `FONTAWESOME_VERSION=7` (<script src="https://kit.fontawesome.com/d559c9a3b2.js" crossorigin="anonymous"></script>)
- `FONT_AWESOME_KEY=FAPS-PRRC-GCWN-APNT-1156` (license key)



## 🌍 **Popup-Inhalt Beispiel**

### **GDACS Event-Marker-Popup:**
```
🌍 Erdbeben - Rot Alert
📍 New Caledonia
📅 16/08/2025
👥 120.000 Betroffene
📊 Magnitude: 5.6M
----------
🕐 Lokale Zeit: 14:30 (UTC+11)
🕐 Berlin-Zeit: 05:30 (+9h)
🌡️ 24°C, ☀️ Sonnig
```

### **Benutzerdefinierter Event-Marker-Popup:**
```
🏢 Bürogebäude - Brandschutzübung
📍 Frankfurt am Main, Deutschland
📅 20/08/2025
🏷️ Kategorie: Übung
📝 Notizen: Jährliche Brandschutzübung
----------
🕐 Lokale Zeit: 15:45 (UTC+1)
🕐 Berlin-Zeit: 15:45 (0h)
🌡️ 18°C, 🌧️ Leichter Regen
```

### **Flughafen-Marker-Popup:**
```
✈️ FRA - Frankfurt Airport
📍 Frankfurt am Main, Deutschland
----------
🕐 Lokale Zeit: 15:45 (UTC+1)
🕐 Berlin-Zeit: 15:45 (0h)
🌡️ 18°C, 🌧️ Leichter Regen
```

## 🎨 **Filament Admin-Formular für manuelle Events**

### **Formular-Felder:**
- **Titel**: Text-Input
- **Beschreibung**: Textarea
- **Event-Typ**: Select mit vordefinierten Optionen
- **Koordinaten**: 
  - Lat/Lng separate Felder
  - **Google Maps Paste-Feld**: Automatische Aufteilung
- **Marker-Farbe**: Color Picker
- **FontAwesome-Symbol**: Select mit Vorschau
- **Symbol-Farbe**: Color Picker
- **Marker-Größe**: Radio Buttons (klein, mittel, groß)
- **Popup-Inhalt**: Rich Text Editor
- **Kategorie**: Select mit Autocomplete
- **Tags**: Multi-Select
- **Start-/Enddatum**: Date/Time Picker
- **Priorität**: Select (niedrig, mittel, hoch, kritisch)
- **Status**: Toggle (aktiv/inaktiv)

---

## 📋 **Aufgabenunterteilung und Subagenten**

### **Phase 1: Grundlagen und Setup**
**Subagent: Backend-Architekt**
- Laravel 12 Projekt-Setup
- MySQL 8 Datenbank-Integration
- Basis-Models und Migrationen
- CI/CD Pipeline

**Subagent: Frontend-Architekt**
- Vue.js/React Setup
- Tailwind CSS Integration
- Leaflet.js Karten-Setup
- Responsive Design-Framework
- Build-System

### **Phase 2: Datenbank und API**
**Subagent: Datenbank-Spezialist**
- Bestehende Tabellen-Analyse
- Erweiterte Migrationen für `custom_events`
- Indizes und Performance-Optimierung
- Backup-Strategien
- Datenbank-Dokumentation

**Subagent: API-Entwickler**
- RESTful API-Endpunkte
- GDACS-Service-Integration
- OpenWeatherMap API-Integration
- Zeitzonen-Service
- API-Dokumentation

### **Phase 3: Laravel Filament Admin**
**Subagent: Filament-Spezialist**
- Filament 4 Installation und Konfiguration
- Custom Resources für alle Models
- Custom Form Components
- Admin-Dashboard
- Benutzerverwaltung

### **Phase 4: Frontend-Komponenten**
**Subagent: UI/UX-Designer**
- Header/Footer-Design
- Sidebar-Komponenten
- Responsive Layout
- Mobile-Optimierung
- Design-System

**Subagent: Karten-Spezialist**
- Leaflet.js Integration
- Marker-System
- Popup-Komponenten
- Filter-Integration
- Karten-Interaktionen

### **Phase 5: Wetter und Zeitzonen**
**Subagent: Wetter-API-Spezialist**
- OpenWeatherMap Integration
- Wetterdaten-Caching
- Wetter-Icons
- Fallback-Mechanismen
- Performance-Optimierung

**Subagent: Zeitzonen-Spezialist**
- Zeitzonen-API-Integration
- Lokale Zeitzonen-Datenbank
- Sommer-/Winterzeit-Logik
- Deutsche Formatierung
- Koordinaten-Parsing

### **Phase 6: Benutzerdefinierte Events**
**Subagent: Custom-Events-Spezialist**
- Erweiterte `custom_events` Tabelle
- Marker-Farben und -Symbole
- FontAwesome-Integration
- Google Maps Koordinaten-Parsing
- Kategorie- und Tag-System

### **Phase 7: Testing und Optimierung**
**Subagent: QA-Spezialist**
- Unit Tests
- Integration Tests
- Performance Tests
- Security Tests
- Browser-Kompatibilität

**Subagent: Performance-Spezialist**
- Caching-Strategien
- Lazy Loading
- CDN-Integration
- Database-Optimierung
- Frontend-Optimierung

### **Phase 8: Deployment und Dokumentation**
**Subagent: DevOps-Spezialist**
- Production-Deployment
- Monitoring-Setup
- Backup-Strategien
- Security-Hardening
- Performance-Monitoring

**Subagent: Dokumentations-Spezialist**
- API-Dokumentation
- Benutzerhandbuch
- Entwickler-Dokumentation
- Deployment-Guide
- Troubleshooting-Guide

### **Projekt-Management**
**Hauptagent: Projekt-Koordinator**
- Aufgabenverteilung an Subagenten
- Fortschrittsüberwachung
- Qualitätskontrolle
- Kommunikation zwischen Subagenten
- Risikomanagement

---

**Erstelle dieses erweiterte System mit Laravel Filament 4, vollständiger Admin-Funktionalität, erweiterten Karten-Features, benutzerdefinierten Markern, Wetter- und Zeitzonen-Integration, feststehenden Header/Footer, Integration in die bestehende MySQL 8 Datenbankstruktur und dem exakten Design wie im Bild dargestellt. Das System soll die vorhandenen Daten nutzen und erweitern, skalierbar, wartbar und benutzerfreundlich sein. Die Umsetzung erfolgt durch koordinierte Subagenten in definierten Phasen.**
