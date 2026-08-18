# Benutzeranleitung - Informationssystem Passolution

## Inhaltsverzeichnis

1. [Einführung](#einführung)
2. [Systemzugang](#systemzugang)
3. [Dashboard-Übersicht](#dashboard-übersicht)
4. [Hauptfunktionen](#hauptfunktionen)
   - [Ereignisverwaltung](#ereignisverwaltung)
   - [Kartenansicht](#kartenansicht)
   - [Filteroptionen](#filteroptionen)
   - [Statistiken](#statistiken)
5. [Benutzerverwaltung](#benutzerverwaltung)
6. [Datenimport und -export](#datenimport-und--export)
7. [Berichterstellung](#berichterstellung)
8. [Systemeinstellungen](#systemeinstellungen)
9. [Sicherheitshinweise](#sicherheitshinweise)
10. [Häufig gestellte Fragen (FAQ)](#häufig-gestellte-fragen-faq)
11. [Support und Kontakt](#support-und-kontakt)

---

## Einführung

Das Informationssystem Passolution ist eine webbasierte Plattform zur Verwaltung und Visualisierung von Ereignissen und Informationen. Das System bietet eine intuitive Benutzeroberfläche zur Erfassung, Bearbeitung und Analyse von Daten mit geografischem Bezug.

### Systemanforderungen

- Moderner Webbrowser (Chrome, Firefox, Safari, Edge - aktuelle Version)
- Stabile Internetverbindung
- JavaScript aktiviert
- Cookies aktiviert für Sitzungsverwaltung

### Zugriff auf das System

Das System ist unter folgender Adresse erreichbar:
**https://info.passolution.eu/**

---

## Systemzugang

### Anmeldung

1. Öffnen Sie Ihren Webbrowser
2. Navigieren Sie zu https://info.passolution.eu/
3. Geben Sie Ihre Zugangsdaten ein:
   - **Benutzername**: Ihr zugewiesener Benutzername
   - **Passwort**: Ihr sicheres Passwort
4. Klicken Sie auf "Anmelden"

### Passwort zurücksetzen

Falls Sie Ihr Passwort vergessen haben:

1. Klicken Sie auf "Passwort vergessen?" auf der Anmeldeseite
2. Geben Sie Ihre registrierte E-Mail-Adresse ein
3. Folgen Sie den Anweisungen in der E-Mail zum Zurücksetzen

### Erste Schritte nach der Anmeldung

Nach erfolgreicher Anmeldung gelangen Sie automatisch zum Dashboard. Nehmen Sie sich Zeit, die verschiedenen Bereiche zu erkunden.

---

## Dashboard-Übersicht

Das Dashboard bietet einen schnellen Überblick über alle wichtigen Informationen:

### Hauptbereiche

1. **Navigationsleiste** (oben)
   - Zugriff auf alle Hauptfunktionen
   - Benutzermenü rechts oben
   - Systembenachrichtigungen

2. **Seitenleiste** (links)
   - Schnellzugriff auf häufig verwendete Funktionen
   - Filteroptionen
   - Kategorieauswahl

3. **Hauptbereich** (Mitte)
   - Aktuelle Ereignisse
   - Interaktive Karte
   - Detailansichten

4. **Informationsleiste** (rechts)
   - Statistiken
   - Aktuelle Meldungen
   - Systemnachrichten

### Widget-Bereiche

- **Ereigniszähler**: Zeigt die Anzahl aktiver Ereignisse
- **Kartenübersicht**: Geografische Verteilung der Ereignisse
- **Zeitliche Verteilung**: Chronologische Darstellung
- **Prioritätsanzeige**: Ereignisse nach Dringlichkeit

---

## Hauptfunktionen

### Ereignisverwaltung

Ereignisse werden im Admin-Bereich unter **Event Management → Passolution Events** gepflegt.
Was dort erfasst wird, steuert zugleich die Kartenanzeige, die Feeds, die Schnittstellen und
den Versand der Benachrichtigungen.

> **Ausführliche Anleitung:** Der komplette Redaktionsablauf – Feld für Feld, inklusive
> Versionierung und Entscheidungshilfe „wann mache ich was" – steht in
> [docs/ereignisse-erfassen-anleitung.pdf](docs/ereignisse-erfassen-anleitung.pdf).

#### Neues Ereignis erstellen

1. **Event Management → Passolution Events** öffnen und ein neues Ereignis anlegen
2. Formular ausfüllen:
   - **Titel und Beschreibung je Sprache**: Sprach-Reiter ganz oben. Der Reiter mit dem Zusatz
     *(Ausgangssprache)* ist Pflicht; nicht gefüllte Sprachen fallen auf ihn zurück
   - **Quellen-Informationen**: beliebig viele Einträge aus Link-Text und Link-URL, je Zeile
     mit dem Schalter „Quelle im Frontend anzeigen"
   - **Event-Typen**: Pflicht, Mehrfachauswahl. Bestimmt das Kartensymbol und ist das Merkmal,
     über das die Benachrichtigungsregeln der Kunden greifen
   - **Priorität**: Information, Niedrig, Mittel, Hoch – Pflicht
   - **Landesweit**: einschalten, wenn das Ereignis im gesamten Land gilt
   - **Aktiv / Archiviert**: steuert die Sichtbarkeit nach außen
   - **Start- und Enddatum**: Startdatum Pflicht, Enddatum optional (leer = andauernd)
3. Speichern
4. **Länder und Standorte ergänzen**: Reiter „Länder & Standorte" → „Standorte verwalten".
   Pro Land sind beliebig viele Einträge mit Region, Stadt, Koordinaten und Notiz möglich

> **Ohne Länderzuordnung** ist ein Ereignis auf der Karte kaum auffindbar, und Travel Alert
> verschickt dazu keine einzige E-Mail: Der Abgleich mit den Reisen der Kunden läuft
> ausschließlich über die zugeordneten Länder.

#### Ereignis ändern (Versionierung)

Ein veröffentlichtes Ereignis wird nicht überschrieben. Inhaltliche Änderungen entstehen als
neue Version desselben Ereignisses:

1. Ereignis öffnen, **Duplizieren (neue Version)** – dabei eine kurze Änderungsnotiz eintragen
2. Die Kopie ist zunächst **inaktiv** und enthält alle Zuordnungen der Vorversion; sie in Ruhe
   bearbeiten
3. **Version aktivieren** – die neue Fassung geht live, die vorherige wird automatisch
   deaktiviert und als abgelöst gekennzeichnet
4. Alle Fassungen stehen im Reiter **Versionen** am Ereignis. Kunden sehen sie in der
   Detailansicht unter „Versionshistorie", inklusive Gültigkeitszeitraum und Änderungen

Reine Tippfehlerkorrekturen ohne inhaltliche Folge dürfen direkt gespeichert werden – sie
lösen dann allerdings auch keine Benachrichtigung aus. Alles, was Bedeutung, Zeitraum, Länder
oder Priorität berührt, gehört in eine neue Version.

In der Ereignisliste ist der Filter **Nur aktuelle Versionen** voreingestellt. Abgelöste
Fassungen erreichen Sie über diesen Filter oder über den Reiter „Versionen" – gelöscht wird
nichts.

#### Ereignis beenden

| Situation | Vorgehen |
|---|---|
| Ereignis endet absehbar | Enddatum setzen – es verschwindet danach von selbst |
| Vorbei, soll als Nachweis sichtbar bleiben | **Archiviert** einschalten. Noch ein Jahr nach dem Enddatum sichtbar |
| Meldung war falsch oder hat sich erledigt | **Aktiv** ausschalten. Nicht löschen |

#### Fremde Ereignisse freigeben

Ereignisse, die Partner über die Schnittstelle einliefern, können den Status **Ausstehend**
haben. Sie sind dann noch nicht aktiv und lösen keine Benachrichtigung aus. In der
Ereignisliste stehen dafür die Aktionen **Freigeben** und **Ablehnen**; die Freigabe schaltet
das Ereignis zugleich aktiv. Prüfen Sie vorher Länderzuordnung und Priorität.

### Kartenansicht

#### Navigation auf der Karte

- **Zoomen**: Mausrad oder +/- Buttons
- **Verschieben**: Klicken und ziehen
- **Vollbild**: F11 oder Vollbild-Button

#### Marker-Funktionen

- **Klick auf Marker**: Öffnet Detailinformationen
- **Hover über Marker**: Zeigt Kurzinformation
- **Marker-Farben**: Repräsentieren verschiedene Kategorien

#### Layer-Steuerung

1. Klicken Sie auf das Layer-Symbol (rechts oben auf der Karte)
2. Wählen Sie die anzuzeigenden Ebenen:
   - Ereignisse
   - Regionen
   - Zusatzinformationen

### Filteroptionen

#### Verfügbare Filter

- **Zeitraum**: Datum von/bis
- **Kategorie**: Mehrfachauswahl möglich
- **Priorität**: Nach Dringlichkeit
- **Status**: Aktiv, Archiviert, Alle
- **Region**: Nach geografischen Gebieten
- **Schlagwörter**: Freitextsuche

#### Filter anwenden

1. Öffnen Sie das Filtermenü (Seitenleiste)
2. Wählen Sie die gewünschten Kriterien
3. Klicken Sie auf "Filter anwenden"
4. Zum Zurücksetzen: "Filter löschen"

### Statistiken

#### Verfügbare Statistiken

- **Ereignisstatistik**: Anzahl und Verteilung
- **Zeitverlauf**: Entwicklung über Zeit
- **Kategorieverteilung**: Prozentuale Aufteilung
- **Geografische Verteilung**: Nach Regionen

#### Statistiken exportieren

1. Wählen Sie die gewünschte Statistik
2. Klicken Sie auf "Export"
3. Wählen Sie das Format (PDF, Excel, CSV)

---

## Benutzerverwaltung

### Profilverwaltung

1. Klicken Sie auf Ihren Benutzernamen (rechts oben)
2. Wählen Sie "Profil bearbeiten"
3. Aktualisierbare Informationen:
   - Name
   - E-Mail-Adresse
   - Telefonnummer
   - Benachrichtigungseinstellungen

### Passwort ändern

1. Navigieren Sie zu "Kontoeinstellungen"
2. Wählen Sie "Passwort ändern"
3. Geben Sie Ihr aktuelles Passwort ein
4. Geben Sie das neue Passwort zweimal ein
5. Klicken Sie auf "Ändern"

### Benachrichtigungen

Konfigurieren Sie Ihre Benachrichtigungen:
- E-Mail-Benachrichtigungen
- System-Benachrichtigungen
- Häufigkeit der Updates

---

## Datenimport und -export

### Datenimport

#### Unterstützte Formate
- CSV (Comma-Separated Values)
- Excel (XLSX)
- JSON
- XML

#### Import-Prozess

1. Navigieren Sie zu "Daten" → "Import"
2. Wählen Sie die Datei aus
3. Ordnen Sie die Spalten den Systemfeldern zu
4. Überprüfen Sie die Vorschau
5. Starten Sie den Import

### Datenexport

1. Wählen Sie die zu exportierenden Daten
2. Klicken Sie auf "Export"
3. Wählen Sie das Format
4. Konfigurieren Sie die Exportoptionen
5. Download startet automatisch

---

## Berichterstellung

### Standardberichte

Das System bietet vorkonfigurierte Berichte:
- Tagesbericht
- Wochenbericht
- Monatsbericht
- Jahresbericht

### Benutzerdefinierten Bericht erstellen

1. Navigieren Sie zu "Berichte" → "Neu"
2. Wählen Sie die Datenquellen
3. Definieren Sie Filter und Zeiträume
4. Wählen Sie das Layout
5. Speichern Sie als Vorlage (optional)

### Bericht planen

1. Wählen Sie einen Bericht
2. Klicken Sie auf "Planung"
3. Definieren Sie:
   - Häufigkeit (täglich, wöchentlich, monatlich)
   - Empfänger
   - Format

---

## Systemeinstellungen

### Allgemeine Einstellungen

Nur für Administratoren zugänglich:
- Systemsprache
- Zeitzone
- Datumsformat
- Währung

### Kategorieverwaltung

1. Navigieren Sie zu "Einstellungen" → "Kategorien"
2. Funktionen:
   - Neue Kategorie hinzufügen
   - Bestehende bearbeiten
   - Reihenfolge ändern
   - Farben zuweisen

### Benutzerrollen

Verfügbare Rollen:
- **Administrator**: Vollzugriff
- **Editor**: Erstellen und Bearbeiten
- **Betrachter**: Nur Lesezugriff

---

## Sicherheitshinweise

### Passwortrichtlinien

- Mindestens 8 Zeichen
- Kombination aus Buchstaben, Zahlen und Sonderzeichen
- Regelmäßige Änderung empfohlen
- Keine Weitergabe an Dritte

### Datenschutz

- Alle Übertragungen sind SSL-verschlüsselt
- Regelmäßige Sicherheitsupdates
- Datensicherung erfolgt automatisch
- DSGVO-konform

### Best Practices

1. Melden Sie sich nach der Nutzung ab
2. Verwenden Sie keine öffentlichen Computer für sensible Daten
3. Halten Sie Ihre Zugangsdaten geheim
4. Melden Sie verdächtige Aktivitäten

---

## Häufig gestellte Fragen (FAQ)

### Allgemeine Fragen

**F: Wie kann ich mein Passwort zurücksetzen?**
A: Nutzen Sie die "Passwort vergessen"-Funktion auf der Anmeldeseite.

**F: Warum werden manche Ereignisse nicht angezeigt?**
A: Überprüfen Sie Ihre Filtereinstellungen und Berechtigungen.

**F: Kann ich Daten offline bearbeiten?**
A: Nein, das System benötigt eine aktive Internetverbindung.

### Technische Fragen

**F: Welche Browser werden unterstützt?**
A: Chrome, Firefox, Safari und Edge in den jeweils aktuellen Versionen.

**F: Wie oft werden die Daten aktualisiert?**
A: Die Daten werden in Echtzeit aktualisiert.

**F: Gibt es eine mobile App?**
A: Das System ist mobiloptimiert und funktioniert im Browser auf allen Geräten.

### Fehlerbehebung

**F: Die Karte wird nicht geladen**
A: Prüfen Sie Ihre Internetverbindung und JavaScript-Einstellungen.

**F: Export funktioniert nicht**
A: Deaktivieren Sie Pop-up-Blocker für diese Seite.

**F: Anmeldung schlägt fehl**
A: Überprüfen Sie Benutzername/Passwort und Caps-Lock-Taste.

---

## Support und Kontakt

### Support-Optionen

#### E-Mail-Support
- **Adresse**: support@passolution.eu
- **Antwortzeit**: Innerhalb von 24 Stunden an Werktagen

#### Telefon-Support
- **Hotline**: +49 (0) XXX XXXXXXX
- **Erreichbarkeit**: Mo-Fr 9:00-17:00 Uhr

#### Online-Hilfe
- Integrierte Hilfe-Funktion im System
- Tooltips und Kontexthilfe
- Video-Tutorials (im Hilfebereich)

### Feedback und Verbesserungsvorschläge

Wir freuen uns über Ihr Feedback:
- Nutzen Sie das Feedback-Formular im System
- Senden Sie Vorschläge an feedback@passolution.eu

### Schulungen

Regelmäßige Schulungen werden angeboten:
- Grundlagenschulung für neue Benutzer
- Fortgeschrittenen-Schulung
- Administrator-Schulung

Termine und Anmeldung unter: https://info.passolution.eu/schulungen

---

## Anhang

### Tastaturkürzel

| Aktion | Windows/Linux | Mac |
|--------|--------------|-----|
| Suche | Strg + K | Cmd + K |
| Neues Ereignis | Strg + N | Cmd + N |
| Speichern | Strg + S | Cmd + S |
| Filter | Strg + F | Cmd + F |
| Hilfe | F1 | F1 |

### Glossar

- **Dashboard**: Hauptübersichtsseite
- **Ereignis**: Einzelner Datensatz im System
- **Layer**: Ebene auf der Karte
- **Widget**: Einzelnes Informationselement auf dem Dashboard
- **Archivierung**: Langzeitspeicherung inaktiver Ereignisse

### Versionshinweise

**Version 2.0** (Aktuell)
- Verbesserte Kartensteuerung
- Erweiterte Filteroptionen
- Neue Statistik-Widgets
- Performance-Optimierungen

---

*Stand: August 2026*
*© 2026 Passolution - Alle Rechte vorbehalten*