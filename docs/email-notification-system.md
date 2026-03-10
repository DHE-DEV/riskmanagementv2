# E-Mail Benachrichtigungssystem - Dokumentation

## Inhaltsverzeichnis

1. [Systemarchitektur](#1-systemarchitektur)
2. [Benachrichtigungsregeln](#2-benachrichtigungsregeln)
3. [E-Mail-Vorlagen](#3-e-mail-vorlagen)
4. [Empfängerverwaltung](#4-empfängerverwaltung)
5. [Versandlogik](#5-versandlogik)
6. [Duplikat-Vermeidung](#6-duplikat-vermeidung)
7. [Rate Limiting](#7-rate-limiting)
8. [Abmeldefunktion (Unsubscribe)](#8-abmeldefunktion-unsubscribe)
9. [Versandprotokoll](#9-versandprotokoll)
10. [Test-Mail Funktion](#10-test-mail-funktion)
11. [Event-Trigger](#11-event-trigger)
12. [Queue-Konfiguration](#12-queue-konfiguration)
13. [Mail-Konfiguration](#13-mail-konfiguration)
14. [Routen](#14-routen)
15. [Datenbank-Tabellen](#15-datenbank-tabellen)
16. [Dateien und Klassen](#16-dateien-und-klassen)

---

## 1. Systemarchitektur

Das System versendet automatisch E-Mail-Benachrichtigungen an Kunden, wenn neue Risk-Events (CustomEvents oder DisasterEvents/GDACS) erstellt oder genehmigt werden. Kunden koennen Regeln definieren, die festlegen, bei welchen Events sie benachrichtigt werden.

### Ablauf

```
Event erstellt/genehmigt
    |
    v
CustomEventObserver / GdacsApiService
    |
    v
SendRiskEventNotifications (Queued Job)
    |
    v
NotificationRuleService
    |-- Pruefe: Kunde hat notifications_enabled?
    |-- Pruefe: Regel ist aktiv?
    |-- Pruefe: Regel passt zum Event? (Risk Level, Kategorie, Land)
    |-- Pruefe: Bereits versendet? (Duplikat-Check)
    |-- Pruefe: Rate-Limit erreicht?
    |-- Pruefe: Empfaenger abgemeldet?
    |-- Generiere Unsubscribe-Token
    |
    v
RiskEventMail (Mailable mit Template + Platzhalter)
    |
    v
SMTP-Versand mit TO/CC/BCC + Unsubscribe-Header
    |
    v
NotificationLog (Protokollierung: sent/failed)
```

---

## 2. Benachrichtigungsregeln

Kunden koennen beliebig viele Regeln erstellen, um festzulegen, bei welchen Events sie benachrichtigt werden.

### Filterkriterien

**Risikostufen** (`risk_levels`) - Leeres Array = alle Stufen

| Wert     | Bezeichnung  |
|----------|-------------|
| `high`   | Hoch        |
| `medium` | Mittel      |
| `low`    | Niedrig     |
| `info`   | Information |

**Kategorien** (`categories`) - Leeres Array = alle Kategorien

| Wert          | Bezeichnung          |
|---------------|---------------------|
| `environment` | Umweltereignisse    |
| `traffic`     | Reiseverkehr        |
| `security`    | Sicherheit          |
| `entry`       | Einreisebestimmungen|
| `health`      | Gesundheit          |
| `general`     | Allgemein           |

**Laender** (`country_ids`) - Leeres Array = alle Laender

- Laender werden ueber eine Suche hinzugefuegt (Name oder ISO-Code)
- Mehrfachauswahl moeglich

### Matching-Logik

- **Leerer Filter = alles matcht**: Wenn z.B. keine Risikostufen gesetzt sind, matcht die Regel bei jeder Risikostufe
- **Risk Level**: Event-Priority muss in der Liste enthalten sein
- **Kategorie**: Event-Kategorie muss in der Liste enthalten sein
- **Laender**: Mindestens ein Land des Events muss in der Liste enthalten sein

### Mapping Event → Regel

| Event-Typ      | Risk Level kommt von | Kategorie kommt von   |
|----------------|---------------------|-----------------------|
| CustomEvent    | `priority`-Feld     | `category`-Feld       |
| DisasterEvent  | `severity`-Feld (`critical` → `high`) | Immer `environment` |

### Verwaltung

- **Erstellen**: `/customer/notification-settings/rules/create`
- **Bearbeiten**: `/customer/notification-settings/rules/{id}/edit`
- **Loeschen**: Soft Delete ueber das Bearbeitungsformular
- **Aktivieren/Deaktivieren**: Per Regel individuell moeglich

---

## 3. E-Mail-Vorlagen

### Systemvorlage (Standard)

Wird automatisch verwendet, wenn keine benutzerdefinierte Vorlage zugewiesen ist.

**Betreff:** `Reisewarnung: {event_title} - {country_name}`

**Body:**
```html
<h2>Reisewarnung: {event_title}</h2>
<p><strong>Land:</strong> {country_name}</p>
<p><strong>Risikostufe:</strong> {risk_level}</p>
<p><strong>Kategorie:</strong> {category}</p>
<p><strong>Datum:</strong> {event_date}</p>
<hr>
<p>{description}</p>
<hr>
<p style="color: #666; font-size: 12px;">
  Diese Benachrichtigung wurde automatisch vom Global Travel Monitor gesendet.
</p>
```

### Platzhalter

| Platzhalter       | Beschreibung                    | Beispiel                     |
|-------------------|---------------------------------|------------------------------|
| `{event_title}`   | Titel des Ereignisses           | Tropensturm Tapah            |
| `{country_name}`  | Name des Landes/der Laender     | Japan, Philippinen           |
| `{risk_level}`    | Risikostufe (deutsch)           | Hoch                         |
| `{category}`      | Kategorie (deutsch)             | Umweltereignisse             |
| `{description}`   | Vollstaendige Beschreibung (HTML) | Kompletter Event-Text       |
| `{event_date}`    | Datum im Format TT.MM.JJJJ     | 10.03.2026                   |
| `{unsubscribe_url}` | Abmelde-Link (automatisch)   | (wird automatisch eingefuegt) |

### Benutzerdefinierte Vorlagen

- Kunden koennen eigene Vorlagen erstellen mit individuellem Betreff und HTML-Body
- Vorlagen koennen pro Regel zugewiesen werden
- Systemvorlagen koennen nicht bearbeitet werden
- Verwaltung unter `/customer/notification-settings/templates`

---

## 4. Empfaengerverwaltung

Jede Regel hat mindestens einen Empfaenger. Empfaenger werden pro Regel konfiguriert.

### Empfaengertypen

| Typ   | Beschreibung                              |
|-------|------------------------------------------|
| `to`  | Direkter Empfaenger (mindestens 1 noetig) |
| `cc`  | Kopie (Carbon Copy)                       |
| `bcc` | Blindkopie (Blind Carbon Copy)            |

- Beliebig viele Empfaenger pro Typ moeglich
- E-Mail-Adressen werden bei der Eingabe validiert
- Der erste TO-Empfaenger ist der Hauptempfaenger

---

## 5. Versandlogik

### Ablauf im Detail

1. **Event-Trigger**: CustomEvent wird erstellt/genehmigt oder GDACS-Event wird importiert
2. **Job-Dispatch**: `SendRiskEventNotifications` wird in die Queue gestellt
3. **Kunden laden**: Alle Kunden mit `notifications_enabled = true`
4. **Regeln pruefen**: Alle aktiven Regeln dieser Kunden durchgehen
5. **Matching**: Regel-Filter gegen Event-Daten pruefen
6. **Schutzpruefungen**:
   - Duplikat-Check (bereits versendet?)
   - Rate-Limit-Check (< 50/Stunde?)
   - Empfaenger-Deduplizierung (gleiche E-Mail fuer gleiches Event?)
   - Unsubscribe-Check (abgemeldet?)
7. **Token generieren**: Unsubscribe-Token fuer jeden Empfaenger
8. **Mail senden**: Via SMTP mit TO/CC/BCC
9. **Protokollieren**: NotificationLog erstellen (sent/failed)

### Job-Konfiguration

- **Queue**: Database (asynchron)
- **Retries**: 3 Versuche bei Fehler
- **Timeout**: 120 Sekunden

---

## 6. Duplikat-Vermeidung

### Regel-Event-Deduplizierung

Verhindert, dass dieselbe Regel fuer dasselbe Event mehrfach versendet wird.

- **Pruefung**: `NotificationLog` mit Kombination `(rule_id, event_id, event_type, status='sent')`
- **Wirkung**: Wenn bereits ein erfolgreicher Eintrag existiert, wird nicht erneut versendet

### Empfaenger-Deduplizierung

Verhindert, dass derselbe Empfaenger fuer dasselbe Event mehrere E-Mails erhaelt (z.B. wenn mehrere Regeln verschiedener Kunden dieselbe E-Mail-Adresse verwenden).

- **Tracking**: In-Memory-Array waehrend einer Event-Verarbeitung
- **Schluessel**: `{email}|{event_id}|{event_type}`
- **Wirkung**: Nur die erste passende Regel sendet an diese Adresse

---

## 7. Rate Limiting

### Konfiguration

- **Limit**: 50 E-Mails pro Kunde pro Stunde
- **Konstante**: `NotificationRuleService::RATE_LIMIT_PER_HOUR = 50`

### Funktionsweise

- Zaehlt `NotificationLog`-Eintraege mit `status='sent'` der letzten Stunde pro `customer_id`
- Bei Ueberschreitung werden weitere Benachrichtigungen uebersprungen
- Warnung wird im Log protokolliert

---

## 8. Abmeldefunktion (Unsubscribe)

### E-Mail-Integration

Jede versendete E-Mail enthaelt:

1. **List-Unsubscribe Header** (RFC 8058): Wird von E-Mail-Clients erkannt und zeigt einen Abmelde-Button an
2. **Footer-Link**: Sichtbarer Abmelde-Link am Ende jeder E-Mail

### Abmelde-Ablauf

1. Empfaenger klickt auf Abmelde-Link in der E-Mail
2. Bestaetigungsseite wird angezeigt (zeigt Regelname oder "alle Benachrichtigungen")
3. Empfaenger bestaetigt die Abmeldung per Button
4. System verarbeitet die Abmeldung:
   - **Mit Regel-ID**: Nur die spezifische Regel wird deaktiviert (`is_active = false`)
   - **Ohne Regel-ID**: Alle Benachrichtigungen des Kunden werden deaktiviert (`notifications_enabled = false`)
5. Bestaetigungsseite wird angezeigt

### Technische Details

- Token: 64 Zeichen, zufaellig generiert (`Str::random(64)`)
- Kein Login erforderlich (oeffentliche Route)
- Einmalverwendung: Nach Nutzung wird `unsubscribed_at` gesetzt
- Vor jedem Versand wird geprueft, ob die E-Mail-Adresse sich abgemeldet hat

### Routen (oeffentlich, kein Login noetig)

- `GET /notifications/unsubscribe/{token}` - Bestaetigungsseite
- `POST /notifications/unsubscribe/{token}` - Abmeldung durchfuehren

---

## 9. Versandprotokoll

### Kundensicht

Kunden koennen ihr Versandprotokoll einsehen unter:
`/customer/notification-settings/history`

**Angezeigte Informationen:**

| Spalte     | Beschreibung                          |
|------------|--------------------------------------|
| Datum      | Versandzeitpunkt (TT.MM.JJJJ HH:MM) |
| Betreff    | E-Mail-Betreff (mit ersetzten Platzhaltern) |
| Empfaenger | E-Mail-Adresse des Empfaengers       |
| Regel      | Name der ausloesenden Regel          |
| Status     | Versendet (gruen) / Fehlgeschlagen (rot) |

- Paginierung: 25 Eintraege pro Seite
- Sortierung: Neueste zuerst
- Bei fehlgeschlagenen Versendungen wird die Fehlermeldung angezeigt
- Link zum Versandprotokoll befindet sich auf der Benachrichtigungseinstellungen-Seite

### Datenbank-Felder (NotificationLog)

| Feld                  | Typ     | Beschreibung                      |
|-----------------------|---------|----------------------------------|
| `notification_rule_id`| FK      | Zugehoerige Regel                |
| `customer_id`         | FK      | Zugehoeriger Kunde               |
| `event_id`            | Integer | ID des ausloesenden Events       |
| `event_type`          | String  | Morph-Klasse (CustomEvent/DisasterEvent) |
| `recipient_email`     | String  | E-Mail-Adresse des Empfaengers   |
| `subject`             | String  | Versendeter Betreff              |
| `status`              | Enum    | `sent` oder `failed`             |
| `error_message`       | Text    | Fehlermeldung bei `failed`       |

---

## 10. Test-Mail Funktion

### In der Regel-Bearbeitung

- **Verfuegbar**: Nur bei gespeicherten Regeln (Bearbeitungsmodus)
- **Button**: "Test-Mail senden" (bernsteinfarben)
- **Empfaenger**: Erster TO-Empfaenger der Regel
- **Inhalt**: Test-Platzhalter mit Beispieldaten:
  - Titel: "Test-Ereignis"
  - Land: "Deutschland"
  - Risikostufe: "Hoch"
  - Kategorie: "Allgemein"
  - Beschreibung: "Dies ist eine Test-Benachrichtigung um den E-Mail-Versand zu pruefen."
  - Datum: Aktuelles Datum
- **Feedback**: Erfolgs- oder Fehlermeldung wird direkt im Formular angezeigt

### In der Vorlagen-Bearbeitung

- **Verfuegbar**: Nur bei gespeicherten Vorlagen (Bearbeitungsmodus)
- **Button**: "Test-Mail senden" (bernsteinfarben)
- **Empfaenger**: E-Mail-Adresse des eingeloggten Kunden
- **Inhalt**: Vorlage mit gleichen Test-Platzhaltern
- **Feedback**: Erfolgs- oder Fehlermeldung mit Empfaenger-Adresse

---

## 11. Event-Trigger

### CustomEvent (Manuell/API erstellt)

| Situation                           | Benachrichtigung? |
|------------------------------------|-------------------|
| Event erstellt + sofort approved    | Ja                |
| Event erstellt + pending_review     | Nein              |
| Event wird spaeter approved         | Ja                |
| Event wird aktualisiert (nicht Status) | Nein           |
| Event wird rejected                 | Nein              |

**Technisch**: `CustomEventObserver` in den Methoden `created()` und `updated()`.

### DisasterEvent (GDACS Import)

| Situation                    | Benachrichtigung? |
|-----------------------------|-------------------|
| Neues GDACS Event importiert | Ja                |
| Bestehendes Event aktualisiert | Nein           |

**Technisch**: `GdacsApiService::saveEventsToDatabase()` dispatcht Job nur bei `create`, nicht bei `update`.

---

## 12. Queue-Konfiguration

| Einstellung        | Wert       | Beschreibung                              |
|-------------------|------------|------------------------------------------|
| `QUEUE_CONNECTION` | `database` | Jobs werden in der Datenbank gespeichert  |
| Job-Retries       | 3          | Maximal 3 Versuche bei Fehler            |
| Job-Timeout       | 120s       | Maximale Ausfuehrungszeit pro Job         |

### Queue Worker starten

```bash
# Entwicklung
php artisan queue:work

# Produktion (mit Supervisor empfohlen)
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

**Hinweis**: Ohne laufenden Queue Worker werden keine E-Mails versendet. Alternativ kann `QUEUE_CONNECTION=sync` gesetzt werden fuer synchronen Versand (blockiert den Request).

---

## 13. Mail-Konfiguration

### SMTP-Einstellungen (.env)

| Einstellung         | Wert                                |
|--------------------|-------------------------------------|
| `MAIL_MAILER`      | `smtp`                              |
| `MAIL_HOST`        | `209.38.112.214`                    |
| `MAIL_PORT`        | `1026`                              |
| `MAIL_USERNAME`    | `smtp-user`                         |
| `MAIL_ENCRYPTION`  | `null` (keine Verschluesselung)     |
| `MAIL_FROM_ADDRESS`| `noreply@stage.passolution.de`      |
| `MAIL_FROM_NAME`   | `Passolution Stage Web-Portal`      |

---

## 14. Routen

### Kunden-Bereich (Login erforderlich, Guard: `customer`)

| Methode | Pfad | Beschreibung |
|---------|------|-------------|
| GET | `/customer/notification-settings` | Uebersicht Benachrichtigungseinstellungen |
| POST | `/customer/notification-settings/toggle` | Benachrichtigungen ein/ausschalten |
| GET | `/customer/notification-settings/stats` | JSON-Statistiken |
| GET | `/customer/notification-settings/history` | Versandprotokoll |
| GET | `/customer/notification-settings/rules/create` | Neue Regel erstellen |
| GET | `/customer/notification-settings/rules/{id}/edit` | Regel bearbeiten |
| GET | `/customer/notification-settings/templates` | Vorlagen-Uebersicht |
| GET | `/customer/notification-settings/templates/create` | Neue Vorlage erstellen |
| GET | `/customer/notification-settings/templates/{id}/edit` | Vorlage bearbeiten |

### Oeffentlich (kein Login noetig)

| Methode | Pfad | Beschreibung |
|---------|------|-------------|
| GET | `/notifications/unsubscribe/{token}` | Abmelde-Bestaetigungsseite |
| POST | `/notifications/unsubscribe/{token}` | Abmeldung durchfuehren |

### Feature-Gate

Alle Kunden-Routen erfordern die Aktivierung des Features `navigation_risk_overview_enabled` via `CustomerFeatureService`.

---

## 15. Datenbank-Tabellen

### notification_rules

| Spalte                    | Typ      | Beschreibung |
|--------------------------|----------|-------------|
| id                       | PK       | Primary Key |
| customer_id              | FK       | Zugehoeriger Kunde |
| name                     | String   | Regelname |
| is_active                | Boolean  | Aktiv/Inaktiv |
| risk_levels              | JSON     | Gefilterte Risikostufen |
| categories               | JSON     | Gefilterte Kategorien |
| country_ids              | JSON     | Gefilterte Laender-IDs |
| notification_template_id | FK (null)| Zugewiesene Vorlage |
| created_at / updated_at  | Timestamp| Zeitstempel |
| deleted_at               | Timestamp| Soft Delete |

### notification_templates

| Spalte      | Typ     | Beschreibung |
|------------|---------|-------------|
| id         | PK      | Primary Key |
| customer_id| FK (null)| Null = Systemvorlage |
| name       | String  | Vorlagenname |
| subject    | String  | E-Mail-Betreff (mit Platzhaltern) |
| body_html  | Text    | HTML-Body (mit Platzhaltern) |
| is_system  | Boolean | Systemvorlage (nicht editierbar) |
| created_at / updated_at | Timestamp | Zeitstempel |
| deleted_at | Timestamp | Soft Delete |

### notification_rule_recipients

| Spalte               | Typ    | Beschreibung |
|---------------------|--------|-------------|
| id                  | PK     | Primary Key |
| notification_rule_id| FK     | Zugehoerige Regel |
| email               | String | E-Mail-Adresse |
| recipient_type      | Enum   | `to`, `cc`, `bcc` |
| created_at / updated_at | Timestamp | Zeitstempel |

### notification_logs

| Spalte               | Typ     | Beschreibung |
|---------------------|---------|-------------|
| id                  | PK      | Primary Key |
| notification_rule_id| FK      | Ausloesende Regel |
| customer_id         | FK      | Zugehoeriger Kunde |
| event_id            | Integer | Event-ID |
| event_type          | String  | Morph-Klasse |
| recipient_email     | String  | Empfaenger |
| subject             | String  | Versendeter Betreff |
| status              | Enum    | `sent` / `failed` |
| error_message       | Text    | Fehlermeldung |
| created_at / updated_at | Timestamp | Zeitstempel |

### notification_unsubscribe_tokens

| Spalte               | Typ       | Beschreibung |
|---------------------|-----------|-------------|
| id                  | PK        | Primary Key |
| token               | String    | 64-Zeichen Token (unique) |
| email               | String    | E-Mail-Adresse |
| notification_rule_id| FK (null) | Spezifische Regel (optional) |
| customer_id         | FK        | Zugehoeriger Kunde |
| unsubscribed_at     | Timestamp | Abmeldezeitpunkt |
| created_at / updated_at | Timestamp | Zeitstempel |

---

## 16. Dateien und Klassen

### Models

| Datei | Beschreibung |
|-------|-------------|
| `app/Models/NotificationRule.php` | Benachrichtigungsregel |
| `app/Models/NotificationTemplate.php` | E-Mail-Vorlage |
| `app/Models/NotificationRuleRecipient.php` | Empfaenger einer Regel |
| `app/Models/NotificationLog.php` | Versandprotokoll-Eintrag |
| `app/Models/NotificationUnsubscribeToken.php` | Abmelde-Token |

### Services

| Datei | Beschreibung |
|-------|-------------|
| `app/Services/NotificationRuleService.php` | Kern-Versandlogik (Matching, Dedup, Rate Limit, Versand) |

### Mail

| Datei | Beschreibung |
|-------|-------------|
| `app/Mail/RiskEventMail.php` | Mailable mit Template-Rendering und Unsubscribe-Header |

### Jobs

| Datei | Beschreibung |
|-------|-------------|
| `app/Jobs/SendRiskEventNotifications.php` | Queued Job fuer asynchronen Versand |

### Controllers

| Datei | Beschreibung |
|-------|-------------|
| `app/Http/Controllers/Customer/NotificationSettingsController.php` | Kundenbereich: Regeln, Vorlagen, History |
| `app/Http/Controllers/NotificationUnsubscribeController.php` | Oeffentlich: Abmeldefunktion |

### Livewire-Komponenten

| Datei | Beschreibung |
|-------|-------------|
| `app/Livewire/Customer/NotificationRuleForm.php` | Regel-Formular mit Test-Mail |
| `app/Livewire/Customer/NotificationTemplateForm.php` | Vorlagen-Formular mit Test-Mail |

### Observer

| Datei | Beschreibung |
|-------|-------------|
| `app/Observers/CustomEventObserver.php` | Trigger bei Event-Erstellung/Genehmigung |

### Views

| Datei | Beschreibung |
|-------|-------------|
| `resources/views/customer/notification-settings/index.blade.php` | Uebersichtsseite |
| `resources/views/customer/notification-settings/history.blade.php` | Versandprotokoll |
| `resources/views/customer/notification-settings/rules/form.blade.php` | Regel-Formular |
| `resources/views/customer/notification-settings/templates/index.blade.php` | Vorlagen-Liste |
| `resources/views/customer/notification-settings/templates/form.blade.php` | Vorlagen-Formular |
| `resources/views/livewire/customer/notification-rule-form.blade.php` | Livewire Regel-Formular |
| `resources/views/livewire/customer/notification-template-form.blade.php` | Livewire Vorlagen-Formular |
| `resources/views/notifications/unsubscribe.blade.php` | Abmelde-Bestaetigungsseite |
| `resources/views/notifications/unsubscribed.blade.php` | Abmeldung-Erfolgsseite |

### Migrations

| Datei | Beschreibung |
|-------|-------------|
| `database/migrations/2026_03_04_100000_add_notifications_enabled_to_customers_table.php` | Customer-Flag |
| `database/migrations/2026_03_04_100001_create_notification_templates_table.php` | Vorlagen-Tabelle |
| `database/migrations/2026_03_04_100002_create_notification_rules_table.php` | Regeln-Tabelle |
| `database/migrations/2026_03_04_100003_create_notification_rule_recipients_table.php` | Empfaenger-Tabelle |
| `database/migrations/2026_03_10_100000_create_notification_logs_table.php` | Versandprotokoll |
| `database/migrations/2026_03_10_100001_create_notification_unsubscribe_tokens_table.php` | Abmelde-Tokens |
