# Flowchart - Risk Management System

> Dieses Diagramm kann im [Mermaid Live Editor](https://mermaid.live) visualisiert werden.
>
> **Begleitende Erklaerung:** Jede Station dieses Plans ist in
> [ablauf-erklaert-anleitung.pdf](ablauf-erklaert-anleitung.pdf) in nicht-technischer
> Sprache beschrieben – gedacht fuer Kolleginnen und Kollegen ohne technischen Hintergrund.
>
> Stand: 11.08.2026

```mermaid
flowchart TD
    classDef admin fill:#4338ca,color:#fff,stroke:#3730a3
    classDef system fill:#0f766e,color:#fff,stroke:#0d9488
    classDef customer fill:#0369a1,color:#fff,stroke:#0284c7
    classDef api fill:#9333ea,color:#fff,stroke:#a855f7
    classDef output fill:#ea580c,color:#fff,stroke:#f97316
    classDef data fill:#475569,color:#fff,stroke:#64748b
    classDef decision fill:#ca8a04,color:#fff,stroke:#eab308
    classDef feed fill:#be123c,color:#fff,stroke:#e11d48
    classDef approved fill:#16a34a,color:#fff,stroke:#15803d
    classDef cron fill:#7c2d12,color:#fff,stroke:#9a3412
    classDef doc fill:#f8fafc,color:#002742,stroke:#94a3b8,stroke-width:1px,stroke-dasharray: 5 4

    %% === HINWEIS AUF DIE BEGLEITENDE ERKLAERUNG ===
    DOC["Jede Station dieses Plans in nicht-technischer Sprache erklaert:\ndocs/ablauf-erklaert-anleitung.pdf"]:::doc

    %% === EINGABE ===
    ADMIN["Admin Panel (Filament)\nEvent manuell erstellen"]:::admin
    GDACS["gdacs:update-events (stuendlich)\nGDACS API\nDERZEIT DEAKTIVIERT (GDACS_ENABLED=false)"]:::cron
    APICLIENT["API Client\nPOST /api/v1/custom/events"]:::api
    CUSTEVENT["Kunde: eigenes Event\n(Livewire Event-Manager)"]:::customer
    INFOSOURCE["feeds:fetch (stuendlich)\nRSS Info Sources"]:::cron
    INFOSYS["infosystem:sync (stuendlich)\nPassolution Infosystem"]:::cron

    %% === ADMIN FLOW ===
    ADMIN --> META["Metadaten setzen\nTyp, Kategorie, Laender\nPriority, Datum, Sprachen"]:::admin
    META --> PUBLISH["Veroeffentlichen\nis_active = true"]:::admin
    PUBLISH --> APPROVED

    %% === AUTO IMPORT ===
    GDACS --> DISASTER["DisasterEvent anlegen"]:::system
    DISASTER --> QUEUEIN

    %% === INFO-QUELLEN: NUR ABLAGE, KEINE EVENTS ===
    INFOSOURCE --> ITEMS["InfoSourceItem\n(Ansicht im Admin Panel)"]:::data
    INFOSYS --> ENTRIES["InfosystemEntry\n(Laenderinformationen)"]:::data

    %% === API FLOW ===
    APICLIENT --> AUTOCHECK{{"API-Client\nauto_approve_events?"}}:::decision
    AUTOCHECK -->|Ja| APPROVED
    AUTOCHECK -->|Nein| PENDING["pending_review\nis_active = false"]:::decision
    PENDING --> REVIEW{{"Admin prueft"}}:::decision
    REVIEW -->|Freigeben| APPROVED
    REVIEW -->|Ablehnen| REJECTED["rejected"]:::decision

    %% === KUNDENEIGENE EVENTS ===
    CUSTEVENT --> OWN["review_status = approved\ncustomer_id gesetzt"]:::customer
    OWN --> ONLYOWN["Nur im eigenen Konto sichtbar\nkeine Benachrichtigungs-Queue"]:::customer

    %% === APPROVED = ZENTRALER KNOTEN ===
    APPROVED(["CustomEvent\nis_active + approved\ncustomer_id = NULL"]):::approved

    %% === OBSERVER: NUR CACHE ===
    APPROVED --> OBSERVER["CustomEventObserver (saved/deleted)\nmarker_icon setzen\nFeed- und GTM-Cache leeren"]:::system
    OBSERVER --> CACHE["Cache invalidiert"]:::system
    APPROVED --> QUEUEIN

    %% === REISEDATEN ===
    TLSYNC["travel-links:sync (alle 30 Min)\nKunden mit travel_links_enabled"]:::cron
    TLSYNC --> PDSSYNC["PdsTripSyncService\nPDS travel-details abrufen"]:::system
    PDSSYNC --> TRIPS[("td_trips\ncountries_visited\ncomputed_start/end_at")]:::data

    %% === BENACHRICHTIGUNGS-QUEUES ===
    QUEUEIN(["Zu verarbeitende Events\nletzte 24 h (lookback_hours)"]):::approved
    QUEUEIN --> QGTM["notifications:process-gtm\n(alle 5 Min, GTM_NOTIFICATION_INTERVAL)"]:::cron
    QUEUEIN --> QTA["notifications:process-travel-alert\n(alle 5 Min, TRAVEL_ALERT_NOTIFICATION_INTERVAL)"]:::cron
    MANUAL["Admin: 'Benachrichtigungen senden'\nSendGtmNotifications / SendTravelAlertNotifications\n(force = true)"]:::admin --> RULES

    QGTM --> QLOG["NotificationQueueLog\nStart / Ende / Fehler"]:::data
    QTA --> QLOG
    QGTM --> RULES
    QTA --> RULES

    %% === REGELPRUEFUNG ===
    RULES["NotificationRuleService\nRegeln laden: is_active,\nKunde notifications_enabled,\nsource = Queue"]:::system
    RULES --> MATCH{{"Regel passt?\nRisk Level / Kategorie\nLaender (nur GTM)"}}:::decision
    MATCH -->|Nein| STOP["Keine E-Mail\n(Grund im Dry-Run sichtbar)"]:::data
    MATCH -->|Ja| RATE{{"Rate-Limit\n50 Mails/Stunde je Kunde?"}}:::decision
    RATE -->|Erreicht| STOP
    RATE -->|OK| SOURCE{{"Quelle der Regel?"}}:::decision

    %% === TRAVEL ALERT ZWEIG ===
    SOURCE -->|Travel Alert| TRIPMATCH["findAffectedTrips\nReisen im Eventzeitraum,\nLaender-Abgleich"]:::system
    TRIPS --> TRIPMATCH
    TRIPMATCH -.->|keine td_trips vorhanden| PDSLIVE["Fallback: Reisen live\nueber pds_account_id holen"]:::system
    PDSLIVE --> TRIPMATCH
    TRIPMATCH --> HASTRIPS{{"Betroffene Reisen?"}}:::decision
    HASTRIPS -->|Nein| STOP
    HASTRIPS -->|Ja| TACOUNT{{"Mehr Reisen als\nbeim letzten Versand?"}}:::decision
    TACOUNT -->|Nein| STOP
    TACOUNT -->|Ja| BUILDMAIL

    %% === GTM ZWEIG ===
    SOURCE -->|Global Travel Monitor| DUPECHECK{{"Fuer diese Regel\nbereits versendet?"}}:::decision
    DUPECHECK -->|Ja| STOP
    DUPECHECK -->|Nein| BUILDMAIL

    %% === MAILVERSAND ===
    BUILDMAIL["Vorlage + Platzhalter fuellen\nTO-Empfaenger pruefen\nEmpfaenger-Dedup, Abmeldung\nAbmelde-Token erzeugen"]:::system
    BUILDMAIL --> SENDMAIL["RiskEventMail versenden\nTO / CC / BCC"]:::output
    SENDMAIL --> LOGMAIL["NotificationLog\nStatus, Betreff, affected_trips_count"]:::data
    SENDMAIL --> MAILBOX["Empfaenger Mailbox"]:::output
    MAILBOX -.->|Abmelde-Link| UNSUB["/notifications/unsubscribe/{token}"]:::customer

    %% === AUSGABE: KUNDEN ===
    CACHE --> DASH["Weltkarte + Event-Liste\nDashboard (oeffentlich)"]:::customer
    CACHE --> RISK["/travel-alert\nRisiko-Uebersicht + Meine Reisen"]:::customer
    CACHE --> EMBED["/embed/dashboard, /embed/map\n/embed/events, /embed/travel-alert"]:::customer

    %% === AUSGABE: API ===
    CACHE --> GTMAPI["GTM API (Kunden)\nGET /api/v1/events\nGET /api/v1/events/countries\nGET /api/v1/events/nearby"]:::api
    CACHE --> EVENTAPI["Custom Event API\nGET /api/v1/custom/events\nScope: own / passolution / all"]:::api
    CACHE --> FEEDS["/feed/events/all.xml\n/feed/events/priority/{p}.xml\n/feed/events/countries/{iso}.xml\n/feed/countries/..."]:::feed

    %% === KUNDEN SELF-SERVICE ===
    RISK -.-> SETTINGS["Benachrichtigungs-Einstellungen\nRegeln, Vorlagen, Empfaenger"]:::customer
    SETTINGS -.-> RULES

    %% === DATENBANK ===
    APPROVED --> DB[("Datenbank\nCustom Events, Disaster Events\nNotification Logs, Queue Logs")]:::data
    DISASTER --> DB
```

## Wichtige Punkte

**Benachrichtigungen laufen nicht mehr ueber den Observer.**
`CustomEventObserver` setzt nur noch `marker_icon` und leert die Feed-/GTM-Caches.
Der Versand liegt bei zwei getrennten Cron-Queues:

| Befehl | Quelle (`source`) | Intervall |
|---|---|---|
| `notifications:process-gtm` | `global_travel_monitor` | `GTM_NOTIFICATION_INTERVAL` (Standard 5 Min) |
| `notifications:process-travel-alert` | `travel_alert` | `TRAVEL_ALERT_NOTIFICATION_INTERVAL` (Standard 5 Min) |
| `travel-links:sync` | Reisedaten fuer Travel Alert | `notifications.travel_links_sync_interval` (Standard 30 Min) |

Jeder Lauf betrachtet Events der letzten `NOTIFICATION_LOOKBACK_HOURS` Stunden (Standard 24)
und schreibt einen `NotificationQueueLog`-Eintrag.

**Beruecksichtigte Events je Lauf**
- `CustomEvent`: `is_active = true`, `review_status = approved`, `customer_id IS NULL`
- `DisasterEvent`: alle im Lookback-Zeitraum
- Kundeneigene Events (`customer_id` gesetzt) werden bewusst nicht versendet.

**Unterschied der beiden Quellen**
- *GTM*: Laenderfilter der Regel greift, Duplikatpruefung ueber `NotificationLog`.
- *Travel Alert*: Laenderfilter der Regel greift nicht; stattdessen Abgleich der
  Event-Laender mit `countries_visited` der Reisen. Ohne betroffene Reise keine Mail.
  Erneuter Versand nur, wenn mehr Reisen betroffen sind als beim letzten Mal.
  Liegen lokal keine `td_trips` vor, werden die Reisen live ueber die
  `pds_account_id` des Kunden bei PDS geholt.

**GDACS ist derzeit abgeschaltet.** `GDACS_ENABLED=false` – die stuendliche Einplanung in
`routes/console.php:12` greift damit nicht, es entstehen keine neuen `DisasterEvent`-Saetze.
Bereits vorhandene bleiben erhalten und werden weiter mitverarbeitet.

**Info-Quellen erzeugen keine Events.** `feeds:fetch` legt `InfoSourceItem` an,
`infosystem:sync` legt `InfosystemEntry` an – beides reine Ablage fuer das Admin Panel.

**Diagnose**
- `notifications:process-gtm --dry-run` / `notifications:process-travel-alert --dry-run`
  zeigen je Regel, wer eine Mail bekaeme bzw. woran es scheitert.
- `notifications:rules` listet die hinterlegten Regeln.
