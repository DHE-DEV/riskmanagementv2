# Push-Benachrichtigungen

## Überblick

Reisende, die eine Reise aus dem **Reise-Link ihres Reisebüros** (Travel Details Link, travel-details.eu) in die **TripData-App** übernommen haben, bekommen eine Push-Benachrichtigung, sobald sich für diese Reise etwas **Wichtiges** ändert, z. B. neue Einreise-, Visa- oder Gesundheitsbestimmungen.

Voraussetzungen:

- Der Reisende hat Mitteilungen in iOS bzw. Android **erlaubt**.
- In der App ist unter **Mehr › Benachrichtigungen** der Schalter an.
- Bei der Reise steht **„Bei wichtigen Änderungen benachrichtigen“** auf an (Vorgabe).

Was eine wichtige Änderung ist, entscheidet wie bisher die Redaktion in **pds-admin**. Es sind dieselben Änderungen, für die travel-details.eu heute schon E-Mails verschickt.

<div class="doc-cards">
<div><b>pds unverändert</b><span>Die pds-Dienste kennen die Plattform nicht. Die Plattform holt sich die Änderungen selbst.</span></div>
<div><b>100.000+ Geräte</b><span>Erkennung über einen Delta-Feed, Versand in Paketen zu 500 Geräten über HTTP/2.</span></div>
<div><b>anonym</b><span>Gespeichert werden nur Push-Token, Sprache und Reise-IDs. Kein Name, keine E-Mail.</span></div>
</div>

## Ablauf

<div class="doc-flow">
<div><strong>Gerät anmelden</strong><span>Die App meldet sich mit Push-Token und Sprache an der Plattform an und bekommt Geräte-ID und Secret.</span></div>
<div><strong>Reise-Links melden</strong><span>Die App schickt die Liste ihrer Reise-IDs, für die sie benachrichtigt werden will.</span></div>
<div><strong>Änderung in pds-admin</strong><span>Die Redaktion speichert eine wichtige Änderung. pds berechnet für jeden betroffenen Link <code>important_change_at</code> neu.</span></div>
<div><strong>Delta-Feed lesen</strong><span>Alle 2 Minuten holt die Plattform aus api-internal nur die Links, die sich seitdem geändert haben.</span></div>
<div><strong>Geräte finden</strong><span>Die Plattform gleicht die geänderten Reise-IDs mit den gemeldeten Reise-Links ab und merkt die Geräte vor.</span></div>
<div><strong>Push verschicken</strong><span>Versand-Jobs schicken je 500 Geräte über APNs (iOS) bzw. FCM (Android). Tippen öffnet die Reise in der App.</span></div>
</div>

## Beteiligte Systeme

| System | Aufgabe |
|---|---|
| **pds-admin** | Redaktion speichert Änderungen und markiert sie als wichtig (`DataChange`). |
| **pds-queue / data-service** | Job `UpdateLastImportantChangeTime` rechnet für jeden betroffenen Link `shared_travel_infos.important_change_at` neu. Er berücksichtigt Ziele, Nationalitäten, Reiseart, Reisewarnungen, Kreuzfahrten und Anpassungen des Reisebüros. |
| **pds-api (api-internal)** | Delta-Feed `GET /api/v2/__internal/travel-details/changes`: account-übergreifend alle Links mit `last_change_at` (= größter Wert von `updated_at` und `important_change_at`) nach einem Cursor, nur Reisen, die noch nicht vorbei sind. |
| **Plattform** | Geräte-API, Scanner `push:scan-travel-changes`, Versand-Jobs, Push-API für eigene Nachrichten. |
| **Apple / Google** | APNs (iOS) und Firebase Cloud Messaging (Android) stellen zu. |
| **TripData-App** | Erlaubnis, Anmeldung, Schalter je Reise, Öffnen der Reise beim Tippen. |

> Die E-Mail-Benachrichtigung von travel-details.eu (`SendImportantChangeNotifications` in pds-queue) läuft unabhängig davon weiter. Ein Reisender kann beides bekommen.

## Wie Änderungen erkannt werden

Die Plattform fragt **nicht** jeden Reise-Link einzeln ab. Ein Abruf der Reisenden-Seite dauert rund 7 Sekunden, weil die ganze Reise mitkommt, und das skaliert nicht. Stattdessen liest der Scanner den **Delta-Feed** der pds-api:

1. Den Cursor (`last_change_at`, `id`) hält die Tabelle `pds_global_sync_states` unter dem Schlüssel `travel_details_push`. Sie ist unabhängig vom Sync `td:sync-pds-changes`.
2. Je Lauf holt der Scanner bis zu 50 Seiten zu 1.000 Links. Bleibt etwas übrig, macht der nächste Lauf dort weiter.
3. Für jede Seite sucht er die gemeldeten Reise-Links mit diesen Reise-IDs, über einen Index auf `tid`.
4. Ein Gerät ist **fällig**, wenn `last_important_change_at` neuer ist als
   - der Zeitpunkt, zu dem das Gerät den Link gemeldet hat, und
   - die letzte Benachrichtigung für diesen Link (`notified_change_at`).
5. Fällige Geräte werden **vor** dem Versand vorgemerkt (`notified_change_at`). Ein zweiter Lauf schickt dieselbe Änderung also nicht noch einmal.
6. Sind auf einer Seite mehrere Reisen eines Geräts betroffen, bekommt es **eine** Benachrichtigung.

Beim allerersten Lauf beginnt die Beobachtung „ab jetzt“. Der Bestand wird nicht nachträglich durchgegangen.

### Nur aktive und kommende Reisen

Abgelaufene Reise-Links werden an keiner Stelle abgefragt:

- **Feed:** Der Delta-Feed liefert nur Links mit `end_date` ≥ jetzt. Auch pds selbst rechnet `important_change_at` nur für Reisen neu, die höchstens 7 Tage vorbei sind.
- **App:** Sie meldet nur Reisen, deren Ende frühestens gestern ist. Reisen ohne Datum zählen mit. Ist eine Reise vorbei, fällt ihre Reise-ID beim nächsten App-Start aus der Liste.
- **Plattform:** `push:prune-devices` entfernt täglich um 04:20 Geräte, die sich 180 Tage nicht gemeldet haben, samt ihren Reise-Links.

### Reisen verschiedener Reisebüros

Jedes Reisebüro und jeder Veranstalter hat ein eigenes Konto in pds und stellt eigene Reise-Links aus. Das spielt hier keine Rolle:

- Die Reise-ID ist über alle Konten eindeutig.
- Die App holt Reisen über die öffentliche Reisenden-Seite, unabhängig vom Konto.
- Der Delta-Feed arbeitet account-übergreifend und liefert die Änderungen aller Konten.

Ein Reisender kann also Reisen mehrerer Reisebüros in der App haben und bekommt für jede eine Benachrichtigung.

<div class="doc-note"><strong>Warum der Feed reicht:</strong> Ob 1.000 oder 500.000 Geräte angemeldet sind, der Feed liefert nur geänderte Links. Die Last hängt also an der Zahl der Änderungen, nicht an der Zahl der Nutzer.</div>

## Geräte-API (für die App)

Alle Aufrufe gehen an `https://platform.passolution.de/api/v1/devices` mit dem Kunden-Token der App (`Authorization: Bearer …`, Berechtigung `gtm:read`). Alles außer der Anmeldung braucht zusätzlich den Header `X-Device-Secret`.

| Aufruf | Zweck |
|---|---|
| `POST /v1/devices` | Gerät anmelden. Antwort 201 mit `id` (ULID) und `secret`. Das Secret gibt es **nur hier**. |
| `PUT /v1/devices/{id}` | Push-Token, Sprache, App-Version, `notifications_enabled` ändern |
| `PUT /v1/devices/{id}/travel-links` | Vollständige Liste der Reise-IDs (`tids`, höchstens 200) ersetzen |
| `DELETE /v1/devices/{id}` | Gerät abmelden |

```http
POST /api/v1/devices
Authorization: Bearer <App-Token>
Content-Type: application/json

{
  "app": "tripdata",
  "platform": "ios",
  "push_token": "a1b2…",
  "push_environment": "production",
  "locale": "de",
  "app_version": "1.0 (20261017.1000)",
  "notifications_enabled": true
}
```

```http
PUT /api/v1/devices/01JABCDEF…/travel-links
Authorization: Bearer <App-Token>
X-Device-Secret: <secret>
Content-Type: application/json

{ "tids": ["ZIPTHRG1Z6Y5", "ABCD-EFGH-IJKL"] }
```

Regeln:

- **Reise-IDs** werden normalisiert: 12 Zeichen, Großbuchstaben, ohne Striche. Ungültige fallen weg.
- **404** heißt, das Gerät ist unbekannt. **403** heißt, das Secret ist falsch. In beiden Fällen meldet sich die App neu an.
- **Ein Push-Token gehört genau einem Gerät.** Meldet sich die App neu an, etwa nach einer Neuinstallation, verliert der alte Eintrag den Token.
- **`push_environment`:** `sandbox` für Entwickler-Builds aus Xcode, `production` für TestFlight und App Store.
- **Rate-Limit:** 30 Anfragen pro Minute je Gerät und 600 je IP. Es gilt bewusst nicht je Kunde, denn alle Installationen teilen sich einen Token.

## Inhalt der Benachrichtigung

| Sprache | Titel | Text |
|---|---|---|
| de | Wichtige Änderung für deine Reise | Für „{Reisename}“ gibt es neue Einreise- oder Gesundheitsinformationen. Tippe, um sie anzusehen. |
| en | Important update for your trip | There is new entry or health information for “{trip}”. Tap to view it. |
| nl | Belangrijke wijziging voor je reis | Er is nieuwe informatie over inreis- of gezondheidsregels voor “{trip}”. Tik om te bekijken. |

Die Texte liegen in `app/Services/Push/PushTexts.php`. Zusätzlich schickt die Plattform:

- `type` = `travel_important_change`
- `tid`
- `url` = `tripdata://reise/{tid}`: öffnet in der App direkt die Reise
- `thread-id` bzw. `collapse_key` `trip-{tid}`: Mehrere Pushs zur selben Reise ersetzen sich auf dem Gerät.

## Eigene Nachrichten über die Plattform-API

Mit einem Token mit der Berechtigung **`push:send`** kann die Plattform-API selbst Pushs verschicken, z. B. Hinweise des Global Travel Monitor oder eine Ankündigung an alle Nutzer. Den Token vergibt nur ein Admin, unter **Kundenverwaltung › Kunde › API Tokens**. Er erreicht **alle Geräte der App**.

```http
POST /api/v1/push/messages
Authorization: Bearer <Token mit push:send>
Content-Type: application/json

{
  "title": { "de": "Streik in Frankreich", "en": "Strike in France" },
  "body":  { "de": "Am Freitag fallen viele Flüge aus.", "en": "Many flights are cancelled on Friday." },
  "data":  { "url": "tripdata://reise/ZIPTHRG1Z6Y5" },
  "target": { "tids": ["ZIPTHRG1Z6Y5"] }
}
```

- `target`: genau eines von
  - `{"all": true}`: alle erreichbaren Geräte
  - `{"tids": […]}`: Geräte mit diesen Reisen, höchstens 1.000 IDs
  - `{"device_ids": […]}`: höchstens 1.000 Geräte
- `title` und `body`: Text oder je Sprache. Geräte bekommen ihre Sprache, sonst Deutsch.
- Antwort **202** mit der Versand-ID. Der Versand läuft im Hintergrund.
- `GET /api/v1/push/messages/{id}` liefert `status` (`sending`/`done`), `targeted`, `sent`, `failed` und `invalid`.
- Rate-Limit: 60 Anfragen pro Minute je Kunde.

## Skalierung

| Baustein | Wie es skaliert |
|---|---|
| **Erkennung** | Delta-Feed mit Keyset-Cursor. Die Last hängt an der Zahl der Änderungen, nicht der Nutzer. Bis zu 50.000 geänderte Links je Lauf, alle 2 Minuten. |
| **Abgleich** | Index auf `app_device_travel_links.tid`. Gelesen wird in Paketen zu 5.000 Zeilen (`chunkById`), damit der Speicher konstant bleibt. |
| **Versand** | Jobs zu je 500 Geräten (`PUSH_BATCH_SIZE`). Innerhalb eines Jobs gehen alle Anfragen gleichzeitig über **eine HTTP/2-Verbindung** zu Apple bzw. Google. |
| **Speicher** | Eine Zeile je Versand mit Zählern, **keine** Zeile je Empfänger |
| **Ungültige Tokens** | Meldet Apple 410 bzw. `Unregistered` oder Google `UNREGISTERED`, wird das Gerät gesperrt (`token_invalid_at`), bis die App einen neuen Token schickt. Geräte, die sich 180 Tage nicht gemeldet haben, bekommen nichts mehr. |

**Rechenbeispiel:** Eine wichtige Änderung für Spanien betrifft 100.000 Geräte. Das sind 200 Versand-Jobs. Ein Job braucht über HTTP/2 typischerweise 2–5 Sekunden. Mit 4 Workern auf der Queue `push` sind alle in etwa 2–4 Minuten zugestellt. Mehr Worker heißt entsprechend schneller.

<div class="doc-warn"><strong>Wichtig für viele Nutzer:</strong> Die übrigen App-Aufrufe (Länder, Regionen, Wetter …) laufen über das Limit <code>gtm-api</code>. Es gilt <em>je Kunde</em>, standardmäßig 60 pro Minute. Alle TripData-Installationen teilen sich einen Kunden. Vor einem großen Rollout muss das Limit des App-Kunden deutlich hoch (<code>gtm_api_rate_limit</code>) oder für App-Tokens je IP gelten.</div>

## Einrichtung

### Apple

1. Im Apple Developer Portal bei der App-ID `de.passolution.tripdata.app` die Fähigkeit **Push Notifications** einschalten. Bei automatischer Signierung erzeugt Xcode die Profile neu.
2. Unter **Keys** einen Schlüssel mit **Apple Push Notifications service (APNs)** anlegen oder einen vorhandenen nutzen. Die `.p8`-Datei gibt es nur einmal zum Download.
3. Notieren: Key-ID und Team-ID (`PFC8Z32F99`).

### Google (Android)

1. Ein Firebase-Projekt anlegen und die Android-App `de.passolution.tripdata` hinzufügen.
2. Ein Service-Konto mit der Rolle **Firebase Cloud Messaging API Admin** anlegen und den JSON-Schlüssel herunterladen.

### Plattform (.env auf Forge)

```dotenv
# APNs (iOS)
APNS_KEY_ID=XXXXXXXXXX
APNS_TEAM_ID=PFC8Z32F99
APNS_TOPIC=de.passolution.tripdata.app
APNS_KEY_PATH=/home/forge/keys/AuthKey_XXXXXXXXXX.p8   # oder APNS_KEY=<Base64 der .p8>

# FCM (Android)
FCM_CREDENTIALS_PATH=/home/forge/keys/fcm-service-account.json   # oder FCM_CREDENTIALS=<Base64>
FCM_PROJECT_ID=tripdata-xxxxx

# Scanner im Zeitplan einschalten (alle 2 Minuten)
PUSH_TRAVEL_CHANGES_ENABLED=true

# Delta-Feed (sind für den Infosystem-Abgleich schon gesetzt)
PASSOLUTION_INTERNAL_API_URL=https://api-internal-dot-pds-prod-dataservice.appspot.com/api/v2
PASSOLUTION_INTERNAL_TOKEN=…
```

Danach `php artisan config:cache`. Schlüsseldateien nie ins Repo legen.

### Queue-Worker

Die Versand-Jobs laufen auf der Queue **`push`**. Auf Forge einen eigenen Daemon bzw. Worker anlegen:

```bash
php artisan queue:work --queue=push --sleep=1 --tries=1 --timeout=150
```

Für viele Geräte mehrere Prozesse starten, z. B. 4. Jobs werden **nicht wiederholt** (`--tries=1`), weil ein zweiter Lauf schon Benachrichtigte doppelt treffen würde.

### Benutzer der App

Nichts zu tun. Die App fragt beim ersten Übernehmen einer Reise aus einem Reise-Link nach der Erlaubnis.

## Betrieb

| Was | Wo |
|---|---|
| Scanner von Hand | `php artisan push:scan-travel-changes` (Optionen `--max-pages`, `--per-page`) |
| Log des Scanners | `storage/logs/push-travel-changes.log` |
| Aufräumen | `php artisan push:prune-devices` (täglich 04:20): Geräte, die sich 180 Tage nicht gemeldet haben |
| Cursor | Tabelle `pds_global_sync_states`, Schlüssel `travel_details_push`. Zeile löschen = beim nächsten Lauf neu „ab jetzt“ |
| Geräte | `app_devices` (ohne Namen; Token und Secret-Hash sind verborgen) |
| Gemeldete Reisen | `app_device_travel_links` (`tid`, `notified_change_at`) |
| Versände und Zähler | `app_push_messages` (`targeted`, `sent`, `failed`, `invalid`, `status`) |
| Fehlgeschlagene Jobs | `php artisan queue:failed` |

### Typische Fehlerbilder

| Bild | Ursache und Abhilfe |
|---|---|
| `sent` bleibt 0, `failed` steigt | APNs- oder FCM-Zugang fehlt oder ist falsch: `.env` prüfen, `config:cache`. |
| Viele `invalid` | Normal nach Neuinstallationen. Apple meldet alte Tokens als ungültig. |
| Keine Versände, obwohl sich etwas geändert hat | Prüfen: Läuft der Scanner (Log, `PUSH_TRAVEL_CHANGES_ENABLED`)? Ist api-internal erreichbar (Delta-Feed, Log-Eintrag „Delta-Feed nicht erreichbar“)? Hat das Gerät die Reise gemeldet und den Schalter an? |
| Pushs nur im Debug-Build | Ein Sandbox-Token schickt die Plattform an `api.sandbox.push.apple.com`. TestFlight und App Store nutzen production. |
| Versand steht auf `sending` | Ein Worker auf der Queue `push` fehlt. |

## Datenschutz

- Die Plattform speichert je Gerät nur Push-Token, Plattform, Sprache, App-Version und die gemeldeten Reise-IDs. Personen erkennt sie daran nicht.
- Das Geräte-Secret liegt nur als SHA-256-Hash vor.
- Abmelden: Schalter in der App aus (`notifications_enabled=false`), Reise-Schalter aus (Reise-ID entfernt) oder `DELETE /v1/devices/{id}`.
- Über die Reise-ID lässt sich ermitteln, wie viele Reisende eines Reisebüros die App nutzen, ohne dass jemand identifiziert wird.

## Code-Überblick

| Datei | Inhalt |
|---|---|
| `app/Http/Controllers/Api/V1/AppDeviceController.php` | Geräte-API |
| `app/Http/Controllers/Api/V1/PushMessageController.php` | Push-API (`push:send`) |
| `app/Services/Push/TravelChangeScanner.php` | Delta-Feed lesen, fällige Geräte vormerken |
| `app/Services/Push/PushDispatcher.php` | Verteilung auf Versand-Jobs, Zähler |
| `app/Jobs/Push/SendPushBatch.php`, `FanOutPushMessage.php` | Versand-Jobs |
| `app/Services/Push/ApnsClient.php`, `FcmClient.php` | HTTP/2 zu Apple und Google, JWT, ungültige Tokens |
| `app/Services/Push/PushSender.php`, `PushTexts.php` | Inhalt je Gerät und Sprache |
| `config/push.php` | Einstellungen |
| `tests/Feature/Api/AppPushTest.php` | Tests |
| TripData iOS: `PushManager.swift`, `AppDelegate.swift` | Erlaubnis, Anmeldung, Abgleich der Reise-IDs, Öffnen beim Tippen |
