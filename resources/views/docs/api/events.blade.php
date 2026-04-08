@extends('docs.api.layout')

@section('title', 'Custom Event API')
@section('api_color', '#f97316')

@section('sidebar')
    <span class="sidebar-heading">Custom Event API</span>
    <a href="#uebersicht">Übersicht</a>
    <a href="#authentifizierung">Authentifizierung</a>
    <a href="#base-url">Base-URL</a>
    <a href="#rate-limit">Rate Limit</a>

    <span class="sidebar-heading">Referenzdaten</span>
    <a href="#event-kategorien">Event-Kategorien abrufen</a>
    <a href="#laender">Länder abrufen</a>

    <span class="sidebar-heading">Events</span>
    <a href="#event-erstellen">Event erstellen</a>
    <a href="#events-auflisten">Eigene Events auflisten</a>
    <a href="#event-anzeigen">Einzelnes Event anzeigen</a>
    <a href="#event-aktualisieren">Event aktualisieren</a>
    <a href="#event-loeschen">Event löschen</a>

    <span class="sidebar-heading">Sonstiges</span>
    <a href="#fehlercodes">Fehlercodes</a>
    <a href="#review-workflow">Review-Workflow</a>
    <a href="#logo">Logo auf dem Dashboard</a>
    <a href="#support">Support</a>
@endsection

@section('content')

    {{-- ── Übersicht ── --}}
    <h1 id="uebersicht">Custom Event API &mdash; Partneranleitung</h1>

    <p>Die Custom Event API ermöglicht es API-Partnern, eigene Sicherheits-Events auf dem Risk Management Dashboard zu erstellen, zu aktualisieren und zu löschen. Jeder Partner verwaltet ausschließlich seine eigenen Events.</p>

    <blockquote>
        <strong>Wichtig:</strong> Das Erstellen, Aktualisieren und Löschen von Events erfordert eine separate Freischaltung Ihres Accounts durch Passolution. Ohne diese Freischaltung können Sie die API nur zum Lesen von Events nutzen. Bei einem Versuch ohne Freischaltung erhalten Sie einen <code>403 Forbidden</code> Response.
    </blockquote>

    <hr>

    {{-- ── Authentifizierung ── --}}
    <h2 id="authentifizierung">Authentifizierung</h2>

    <p>Alle API-Aufrufe erfordern einen <strong>Bearer-Token</strong> im HTTP-Header:</p>

    <div class="code-block">
        <span class="code-label">Header</span>
        <pre><code>Authorization: Bearer {API_TOKEN}</code></pre>
    </div>

    <p>Den Token erhalten Sie von Ihrem Ansprechpartner bei Passolution. Er ist 1 Jahr gültig.</p>

    <hr>

    {{-- ── Base-URL ── --}}
    <h2 id="base-url">Base-URL</h2>

    <div class="code-block">
        <span class="code-label">URL</span>
        <pre><code>https://api.global-travel-monitor.de/v1/custom</code></pre>
    </div>

    <hr>

    {{-- ── Rate Limit ── --}}
    <h2 id="rate-limit">Rate Limit</h2>

    <p>Standardmäßig sind <strong>60 Requests pro Minute</strong> erlaubt. Bei Überschreitung erhalten Sie einen <code>429 Too Many Requests</code> Response.</p>

    <hr>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- ── Referenzdaten                               ── --}}
    {{-- ══════════════════════════════════════════════════ --}}

    <h2 id="event-kategorien">Event-Kategorien abrufen</h2>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/custom/event-categories</span>
    </div>

    <h4>Aktuell verfügbare Kategorien</h4>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><code>environment</code></td><td>Umweltereignisse</td></tr>
                <tr><td><code>traffic</code></td><td>Reiseverkehr</td></tr>
                <tr><td><code>security</code></td><td>Sicherheit</td></tr>
                <tr><td><code>entry</code></td><td>Einreisebestimmungen</td></tr>
                <tr><td><code>general</code></td><td>Allgemein</td></tr>
                <tr><td><code>health</code></td><td>Gesundheit</td></tr>
            </tbody>
        </table>
    </div>

    <blockquote>
        <strong>Hinweis:</strong> Diese Liste kann sich ändern. Nutzen Sie den Endpoint <code>GET /v1/custom/event-categories</code>, um stets die aktuellen Kategorien abzurufen.
    </blockquote>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  https://api.global-travel-monitor.de/v1/custom/event-categories</code></pre>
    </div>

    <div class="response-block">
        <span class="response-label">Response 200</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": [
    {
      "code": "environment",
      "name": "Umweltereignisse",
      "color": "#059669",
      "icon": "fa-leaf"
    },
    {
      "code": "security",
      "name": "Sicherheit",
      "color": "#DC2626",
      "icon": "fa-shield-alt"
    }
  ]
}</code></pre>
    </div>

    <hr>

    {{-- ── Länder abrufen ── --}}
    <h2 id="laender">Länder abrufen</h2>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/custom/countries</span>
    </div>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  https://api.global-travel-monitor.de/v1/custom/countries</code></pre>
    </div>

    <div class="response-block">
        <span class="response-label">Response 200</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": [
    {
      "iso_code": "DE",
      "iso3_code": "DEU",
      "name_de": "Deutschland",
      "name_en": "Germany"
    },
    {
      "iso_code": "TH",
      "iso3_code": "THA",
      "name_de": "Thailand",
      "name_en": "Thailand"
    }
  ]
}</code></pre>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- ── Events                                      ── --}}
    {{-- ══════════════════════════════════════════════════ --}}

    {{-- ── Event erstellen ── --}}
    <h2 id="event-erstellen">Event erstellen</h2>

    <blockquote>
        Erfordert Freischaltung der Event-Erstellung für Ihren Account.
    </blockquote>

    <div class="endpoint-block">
        <span class="method method-post">POST</span>
        <span class="path">/v1/custom/events</span>
    </div>

    <h4>Request-Body (JSON)</h4>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>title</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>Titel des Events (max. 255 Zeichen)</td>
                </tr>
                <tr>
                    <td><code>description</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Beschreibung (max. 10.000 Zeichen, HTML erlaubt: p, br, strong, em, ul, ol, li, a)</td>
                </tr>
                <tr>
                    <td><code>risk_level</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Risikostufe: <code>info</code>, <code>low</code>, <code>medium</code> (Standard), <code>high</code></td>
                </tr>
                <tr>
                    <td><code>start_date</code></td>
                    <td>datetime</td>
                    <td>Ja</td>
                    <td>Startdatum (ISO 8601, z.B. <code>2026-02-11T08:00:00Z</code>)</td>
                </tr>
                <tr>
                    <td><code>end_date</code></td>
                    <td>datetime</td>
                    <td>Nein</td>
                    <td>Enddatum (muss gleich oder nach start_date liegen)</td>
                </tr>
                <tr>
                    <td><code>event_category_codes</code></td>
                    <td>array</td>
                    <td>Ja</td>
                    <td>Event-Kategorie-Codes (mindestens 1, z.B. <code>["security", "environment"]</code>)</td>
                </tr>
                <tr>
                    <td><code>country_codes</code></td>
                    <td>array</td>
                    <td>Ja</td>
                    <td>ISO-2-Ländercodes (mindestens 1, z.B. <code>["DE", "AT"]</code>)</td>
                </tr>
                <tr>
                    <td><code>latitude</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Breitengrad (-90 bis 90)</td>
                </tr>
                <tr>
                    <td><code>longitude</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Längengrad (-180 bis 180)</td>
                </tr>
                <tr>
                    <td><code>tags</code></td>
                    <td>array</td>
                    <td>Nein</td>
                    <td>Schlagwörter (z.B. <code>["flooding", "bangkok"]</code>)</td>
                </tr>
                <tr>
                    <td><code>external_id</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Ihre interne Referenz-ID (max. 255 Zeichen)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X POST https://api.global-travel-monitor.de/v1/custom/events \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Überschwemmung Bangkok",
    "description": "&lt;p&gt;Schwere Überschwemmungen im Großraum Bangkok.&lt;/p&gt;",
    "risk_level": "high",
    "start_date": "2026-02-11T08:00:00Z",
    "end_date": "2026-02-18T08:00:00Z",
    "event_category_codes": ["environment"],
    "country_codes": ["TH"],
    "latitude": 13.7563,
    "longitude": 100.5018,
    "tags": ["flooding", "bangkok"],
    "external_id": "EXT-2026-001"
  }'</code></pre>
    </div>

    <div class="response-block">
        <span class="response-label">Response 201 Created</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "message": "Event created and published successfully.",
  "data": {
    "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "title": "Überschwemmung Bangkok",
    "description": "Schwere Überschwemmungen im Großraum Bangkok.",
    "risk_level": "high",
    "start_date": "2026-02-11T08:00:00+00:00",
    "end_date": "2026-02-18T08:00:00+00:00",
    "latitude": 13.7563,
    "longitude": 100.5018,
    "review_status": "approved",
    "is_active": true,
    "tags": ["flooding", "bangkok"],
    "event_categories": [
      {
        "code": "environment",
        "name": "Umweltereignisse",
        "color": "#059669",
        "icon": "fa-leaf"
      }
    ],
    "countries": [
      {
        "iso_code": "TH",
        "name_de": "Thailand",
        "name_en": "Thailand"
      }
    ],
    "created_at": "2026-02-11T10:30:00+00:00",
    "updated_at": "2026-02-11T10:30:00+00:00"
  }
}</code></pre>
    </div>

    <blockquote>
        <strong>Hinweis:</strong> Wenn für Ihren Account die Auto-Freigabe nicht aktiviert ist, lautet der <code>review_status</code> <code>pending_review</code> und <code>is_active</code> ist <code>false</code>. Das Event wird erst nach manueller Freigabe durch das Passolution-Team auf dem Dashboard sichtbar.
    </blockquote>

    <hr>

    {{-- ── Eigene Events auflisten ── --}}
    <h2 id="events-auflisten">Eigene Events auflisten</h2>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/custom/events</span>
    </div>

    <p>Standardmäßig werden nur <strong>eigene Events</strong> zurückgegeben &mdash; also Events, die über Ihren API-Token erstellt wurden. Mit dem Parameter <code>scope</code> können Sie zusätzlich <strong>Passolution-Events</strong> und <strong>Events von Partner-Gruppen</strong> abrufen.</p>

    <p>Der <code>scope</code>-Parameter unterstützt <strong>kommagetrennte Werte</strong>, um mehrere Quellen gleichzeitig abzufragen.</p>

    <h4>Query-Parameter</h4>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Typ</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>scope</code></td>
                    <td>string</td>
                    <td>Kommagetrennte Liste von Scope-Werten (Standard: <code>own</code>)</td>
                </tr>
                <tr>
                    <td><code>per_page</code></td>
                    <td>integer</td>
                    <td>Einträge pro Seite (Standard: 25)</td>
                </tr>
                <tr>
                    <td><code>page</code></td>
                    <td>integer</td>
                    <td>Seitennummer</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Scope-Werte</h4>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Wert</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>own</code></td>
                    <td>Nur Ihre eigenen Events (Standard)</td>
                </tr>
                <tr>
                    <td><code>passolution</code></td>
                    <td>Nur von Passolution bereitgestellte Events (aktiv und freigegeben)</td>
                </tr>
                <tr>
                    <td><code>all</code></td>
                    <td>Ihre eigenen Events + Passolution-Events zusammen</td>
                </tr>
                <tr>
                    <td><code>{gruppen-slug}</code></td>
                    <td>Events der API-Kunden in der angegebenen Event-Gruppe (aktiv, freigegeben, nicht archiviert). Wenn die Gruppe <code>include_passolution_events</code> aktiviert hat, werden zusätzlich Passolution-Events mitgeliefert.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <blockquote>
        <strong>Hinweis:</strong> Partner-Events (über Gruppen) und Passolution-Events werden nur angezeigt, wenn sie aktiv, freigegeben und nicht archiviert sind.
    </blockquote>

    <h4>Beispiele</h4>

    <div class="code-block">
        <span class="code-label">cURL &mdash; Eigene Events (Standard)</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/custom/events?per_page=10&amp;page=1"</code></pre>
    </div>

    <div class="code-block">
        <span class="code-label">cURL &mdash; Nur Passolution-Events</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/custom/events?scope=passolution"</code></pre>
    </div>

    <div class="code-block">
        <span class="code-label">cURL &mdash; Alle Events (eigene + Passolution)</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/custom/events?scope=all"</code></pre>
    </div>

    <div class="code-block">
        <span class="code-label">cURL &mdash; Eigene + Passolution (kommagetrennt)</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/custom/events?scope=own,passolution"</code></pre>
    </div>

    <div class="code-block">
        <span class="code-label">cURL &mdash; Events einer Partner-Gruppe</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/custom/events?scope=meine-partner-gruppe"</code></pre>
    </div>

    <div class="code-block">
        <span class="code-label">cURL &mdash; Eigene Events + Partner-Gruppe</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/custom/events?scope=own,meine-partner-gruppe"</code></pre>
    </div>

    <hr>

    {{-- ── Einzelnes Event anzeigen ── --}}
    <h2 id="event-anzeigen">Einzelnes Event anzeigen</h2>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/custom/events/{uuid}</span>
    </div>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  https://api.global-travel-monitor.de/v1/custom/events/a1b2c3d4-e5f6-7890-abcd-ef1234567890</code></pre>
    </div>

    <hr>

    {{-- ── Event aktualisieren ── --}}
    <h2 id="event-aktualisieren">Event aktualisieren</h2>

    <blockquote>
        Erfordert Freischaltung der Event-Erstellung für Ihren Account.
    </blockquote>

    <div class="endpoint-block">
        <span class="method method-put">PUT</span>
        <span class="path">/v1/custom/events/{uuid}</span>
    </div>

    <p>Es müssen nur die zu ändernden Felder gesendet werden.</p>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X PUT https://api.global-travel-monitor.de/v1/custom/events/a1b2c3d4-e5f6-7890-abcd-ef1234567890 \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Überschwemmung Bangkok - Entwarnung",
    "risk_level": "low"
  }'</code></pre>
    </div>

    <div class="response-block">
        <span class="response-label">Response 200 OK</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "message": "Event updated successfully.",
  "data": { ... }
}</code></pre>
    </div>

    <hr>

    {{-- ── Event löschen ── --}}
    <h2 id="event-loeschen">Event löschen</h2>

    <blockquote>
        Erfordert Freischaltung der Event-Erstellung für Ihren Account.
    </blockquote>

    <div class="endpoint-block">
        <span class="method method-delete">DELETE</span>
        <span class="path">/v1/custom/events/{uuid}</span>
    </div>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X DELETE https://api.global-travel-monitor.de/v1/custom/events/a1b2c3d4-e5f6-7890-abcd-ef1234567890 \
  -H "Authorization: Bearer {TOKEN}"</code></pre>
    </div>

    <div class="response-block">
        <span class="response-label">Response 200 OK</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "message": "Event deleted successfully."
}</code></pre>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- ── Sonstiges                                   ── --}}
    {{-- ══════════════════════════════════════════════════ --}}

    {{-- ── Fehlercodes ── --}}
    <h2 id="fehlercodes">Fehlercodes</h2>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>HTTP-Code</th>
                    <th>Bedeutung</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><code>200</code></td><td>Erfolgreich</td></tr>
                <tr><td><code>201</code></td><td>Erfolgreich erstellt</td></tr>
                <tr><td><code>401</code></td><td>Nicht authentifiziert (Token fehlt oder ungültig)</td></tr>
                <tr><td><code>403</code></td><td>Zugriff verweigert (Token hat keine Berechtigung oder Account deaktiviert)</td></tr>
                <tr><td><code>404</code></td><td>Event nicht gefunden</td></tr>
                <tr><td><code>422</code></td><td>Validierungsfehler (ungültige Daten)</td></tr>
                <tr><td><code>429</code></td><td>Rate Limit überschritten</td></tr>
                <tr><td><code>500</code></td><td>Serverfehler</td></tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel Validierungsfehler (422)</h4>

    <div class="response-block">
        <span class="response-label">Response 422</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "message": "The title field is required.",
  "errors": {
    "title": ["The title field is required."],
    "event_category_codes": ["At least one event type code is required."]
  }
}</code></pre>
    </div>

    <hr>

    {{-- ── Review-Workflow ── --}}
    <h2 id="review-workflow">Review-Workflow</h2>

    <p>Je nach Konfiguration Ihres Accounts gibt es zwei Modi:</p>

    <ol>
        <li><strong>Auto-Freigabe aktiviert:</strong> Events werden sofort veröffentlicht (<code>review_status: approved</code>, <code>is_active: true</code>)</li>
        <li><strong>Auto-Freigabe deaktiviert:</strong> Events werden zur Prüfung eingereicht (<code>review_status: pending_review</code>, <code>is_active: false</code>) und erst nach Freigabe durch das Passolution-Team sichtbar</li>
    </ol>

    <hr>

    {{-- ── Logo ── --}}
    <h2 id="logo">Logo auf dem Dashboard</h2>

    <p>Wenn ein Firmenlogo in Ihrem API-Account hinterlegt ist, wird dieses als Quellen-Logo neben Ihren Events auf dem Dashboard angezeigt. Ohne Logo erscheint Ihr Firmenname als Text.</p>

    <hr>

    {{-- ── Support ── --}}
    <h2 id="support">Support</h2>

    <p>Bei Fragen zur API wenden Sie sich an Ihren Ansprechpartner bei Passolution.</p>

@endsection
