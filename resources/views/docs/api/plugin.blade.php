@extends('docs.api.layout')

@section('title', 'Plugin Domain API')
@section('api_color', '#14b8a6')

@section('sidebar')
    <span class="sidebar-heading">Plugin Domain API</span>
    <a href="#uebersicht">Übersicht</a>
    <a href="#authentifizierung">Authentifizierung</a>
    <a href="#base-url">Base-URL</a>
    <a href="#rate-limit">Rate Limit</a>
    <a href="#domain-normalisierung">Domain-Normalisierung</a>
    <a href="#domain-identifikation">Domain-Identifikation (UUID)</a>

    <span class="sidebar-heading">Endpunkte</span>
    <a href="#domains-auflisten">Domains auflisten</a>
    <a href="#einzelne-domain-abrufen">Einzelne Domain abrufen</a>
    <a href="#domain-hinzufuegen">Domain hinzufügen</a>
    <a href="#domains-bulk-importieren">Domains im Bulk importieren</a>
    <a href="#domain-aktualisieren">Domain aktualisieren</a>
    <a href="#domain-loeschen">Domain löschen</a>
    <a href="#domains-bulk-loeschen">Domains im Bulk löschen</a>

    <span class="sidebar-heading">Referenz</span>
    <a href="#fehlercodes">Fehlercodes</a>
    <a href="#anwendungsfaelle">Typische Anwendungsfälle</a>
    <a href="#support">Support &amp; Kontakt</a>
@endsection

@section('content')

    {{-- ── Übersicht ── --}}
    <h1 id="uebersicht">Plugin Domain API</h1>

    <p>Die Plugin Domain API ermöglicht es Plugin-Kunden, ihre erlaubten Domains <strong>programmatisch</strong> zu verwalten. Domains bestimmen, welche Websites das Plugin per iframe einbetten dürfen.</p>

    <p>Die API unterstützt das Anlegen, Abrufen, Aktualisieren und Löschen einzelner Domains sowie <strong>Massenoperationen</strong> für den Import und die Löschung von bis zu 1.000 Domains pro Aufruf.</p>

    <hr>

    {{-- ── Authentifizierung ── --}}
    <h2 id="authentifizierung">Authentifizierung</h2>

    <p>Alle API-Aufrufe erfordern Ihren <strong>Plugin-Key</strong> als Bearer-Token im HTTP-Header:</p>

    <div class="code-block">
        <span class="code-label">Header</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>Authorization: Bearer pk_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</code></pre>
    </div>

    <p>Den Key finden Sie in Ihrem Plugin-Dashboard unter <code>https://global-travel-monitor.eu/plugin/dashboard</code>.</p>

    <blockquote>
        <strong>Hinweis:</strong> Der Plugin-Key ist derselbe Key, den Sie auch für die iframe-Integration verwenden.
    </blockquote>

    <hr>

    {{-- ── Base-URL ── --}}
    <h2 id="base-url">Base-URL</h2>

    <div class="code-block">
        <span class="code-label">URL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>https://api.global-travel-monitor.de/v1/plugin</code></pre>
    </div>

    <p>Alternativ: <code>https://global-travel-monitor.eu/api/v1/plugin</code></p>

    <hr>

    {{-- ── Rate Limit ── --}}
    <h2 id="rate-limit">Rate Limit</h2>

    <p>Standardmäßig sind <strong>120 Requests pro Minute</strong> erlaubt. Bei Überschreitung erhalten Sie einen <code>429 Too Many Requests</code>-Response. Prüfen Sie den <code>Retry-After</code>-Header für die Wartezeit in Sekunden.</p>

    <hr>

    {{-- ── Domain-Normalisierung ── --}}
    <h2 id="domain-normalisierung">Domain-Normalisierung</h2>

    <p>Domains werden beim Speichern automatisch normalisiert:</p>

    <ul>
        <li>Protokoll wird entfernt (<code>https://example.com</code> &rarr; <code>example.com</code>)</li>
        <li><code>www.</code>-Prefix wird entfernt (<code>www.example.com</code> &rarr; <code>example.com</code>)</li>
        <li>Pfade werden entfernt (<code>example.com/page</code> &rarr; <code>example.com</code>)</li>
        <li>Ports werden entfernt (<code>example.com:8080</code> &rarr; <code>example.com</code>)</li>
        <li>Alles wird zu Kleinbuchstaben konvertiert</li>
    </ul>

    <hr>

    {{-- ── Domain-Identifikation ── --}}
    <h2 id="domain-identifikation">Domain-Identifikation (UUID)</h2>

    <p>Jede Domain erhält eine eindeutige <strong>UUID</strong>. Alle Operationen auf einzelne Domains (Abrufen, Aktualisieren, Löschen) verwenden diese UUID als Identifikator.</p>

    <hr>

    {{-- ══════════════════════════════════════════════ --}}
    {{-- ── ENDPUNKTE                                ── --}}
    {{-- ══════════════════════════════════════════════ --}}

    <h1 id="endpunkte">Endpunkte</h1>

    {{-- ── Domains auflisten ── --}}
    <h2 id="domains-auflisten">Domains auflisten</h2>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/plugin/gtm/domains</span>
    </div>

    <p>Gibt eine paginierte Liste aller registrierten Domains zurück.</p>

    <h4>Query-Parameter</h4>
    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Typ</th>
                    <th>Standard</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>per_page</code></td>
                    <td>integer</td>
                    <td>50</td>
                    <td>Einträge pro Seite (max. 200)</td>
                </tr>
                <tr>
                    <td><code>page</code></td>
                    <td>integer</td>
                    <td>1</td>
                    <td>Seitennummer</td>
                </tr>
                <tr>
                    <td><code>search</code></td>
                    <td>string</td>
                    <td>&ndash;</td>
                    <td>Volltextsuche im Domain-Namen</td>
                </tr>
                <tr>
                    <td><code>is_active</code></td>
                    <td>boolean</td>
                    <td>&ndash;</td>
                    <td>Filter: nur aktive (<code>true</code>) oder inaktive (<code>false</code>) Domains</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel</h4>
    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer pk_live_xxx" \
  "https://api.global-travel-monitor.de/v1/plugin/gtm/domains?per_page=100&amp;search=example"</code></pre>
    </div>

    <h4>Response (200)</h4>
    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "data": [
    {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "domain": "example.com",
      "is_active": true,
      "created_at": "2026-03-15T10:30:00+00:00",
      "updated_at": "2026-03-15T10:30:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 100,
    "total": 1
  }
}</code></pre>
    </div>

    <hr>

    {{-- ── Einzelne Domain abrufen ── --}}
    <h2 id="einzelne-domain-abrufen">Einzelne Domain abrufen</h2>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/plugin/gtm/domains/{uuid}</span>
    </div>

    <h4>Beispiel</h4>
    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer pk_live_xxx" \
  "https://api.global-travel-monitor.de/v1/plugin/gtm/domains/550e8400-e29b-41d4-a716-446655440000"</code></pre>
    </div>

    <h4>Response (200)</h4>
    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "domain": "example.com",
    "is_active": true,
    "created_at": "2026-03-15T10:30:00+00:00",
    "updated_at": "2026-03-15T10:30:00+00:00"
  }
}</code></pre>
    </div>

    <hr>

    {{-- ── Domain hinzufügen ── --}}
    <h2 id="domain-hinzufuegen">Domain hinzufügen</h2>

    <div class="endpoint-block">
        <span class="method method-post">POST</span>
        <span class="path">/v1/plugin/gtm/domains</span>
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
                    <td><code>domain</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>Die Domain (z.B. <code>example.com</code>)</td>
                </tr>
                <tr>
                    <td><code>is_active</code></td>
                    <td>boolean</td>
                    <td>Nein</td>
                    <td>Aktiv-Status (Standard: <code>true</code>)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel</h4>
    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X POST -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{"domain": "neue-website.de"}' \
  "https://api.global-travel-monitor.de/v1/plugin/gtm/domains"</code></pre>
    </div>

    <h4>Response (201)</h4>
    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "data": {
    "uuid": "660e8400-e29b-41d4-a716-446655440001",
    "domain": "neue-website.de",
    "is_active": true,
    "created_at": "2026-03-31T12:00:00+00:00",
    "updated_at": "2026-03-31T12:00:00+00:00"
  }
}</code></pre>
    </div>

    <blockquote>
        <strong>Fehler (409):</strong> Domain bereits registriert.
    </blockquote>

    <hr>

    {{-- ── Domains im Bulk importieren ── --}}
    <h2 id="domains-bulk-importieren">Domains im Bulk importieren</h2>

    <div class="endpoint-block">
        <span class="method method-post">POST</span>
        <span class="path">/v1/plugin/gtm/domains/bulk</span>
    </div>

    <p>Importiert bis zu <strong>1.000 Domains</strong> in einem Aufruf. Bereits vorhandene Domains werden übersprungen, ungültige Domains werden gemeldet.</p>

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
                    <td><code>domains</code></td>
                    <td>string[]</td>
                    <td>Ja</td>
                    <td>Array von Domain-Strings (max. 1.000)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel</h4>
    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X POST -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{
    "domains": [
      "website1.de",
      "website2.com",
      "app.website3.de",
      "ungültig..domain"
    ]
  }' \
  "https://api.global-travel-monitor.de/v1/plugin/gtm/domains/bulk"</code></pre>
    </div>

    <h4>Response (201)</h4>
    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "data": {
    "created": [
      {
        "uuid": "770e8400-e29b-41d4-a716-446655440002",
        "domain": "website1.de",
        "is_active": true,
        "created_at": "2026-03-31T12:00:00+00:00",
        "updated_at": "2026-03-31T12:00:00+00:00"
      },
      {
        "uuid": "770e8400-e29b-41d4-a716-446655440003",
        "domain": "website2.com",
        "is_active": true,
        "created_at": "2026-03-31T12:00:00+00:00",
        "updated_at": "2026-03-31T12:00:00+00:00"
      },
      {
        "uuid": "770e8400-e29b-41d4-a716-446655440004",
        "domain": "app.website3.de",
        "is_active": true,
        "created_at": "2026-03-31T12:00:00+00:00",
        "updated_at": "2026-03-31T12:00:00+00:00"
      }
    ],
    "skipped": [],
    "invalid": ["ungültig..domain"]
  },
  "meta": {
    "created_count": 3,
    "skipped_count": 0,
    "invalid_count": 1
  }
}</code></pre>
    </div>

    <hr>

    {{-- ── Domain aktualisieren ── --}}
    <h2 id="domain-aktualisieren">Domain aktualisieren</h2>

    <div class="endpoint-block">
        <span class="method method-put">PUT</span>
        <span class="path">/v1/plugin/gtm/domains/{uuid}</span>
    </div>

    <p>Aktualisiert eine Domain. Kann zum Umbenennen oder Aktivieren/Deaktivieren verwendet werden.</p>

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
                    <td><code>domain</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Neuer Domain-Name</td>
                </tr>
                <tr>
                    <td><code>is_active</code></td>
                    <td>boolean</td>
                    <td>Nein</td>
                    <td>Aktiv-Status ändern</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel &ndash; Domain deaktivieren</h4>
    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X PUT -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{"is_active": false}' \
  "https://api.global-travel-monitor.de/v1/plugin/gtm/domains/550e8400-e29b-41d4-a716-446655440000"</code></pre>
    </div>

    <h4>Response (200)</h4>
    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "domain": "example.com",
    "is_active": false,
    "created_at": "2026-03-15T10:30:00+00:00",
    "updated_at": "2026-03-31T14:00:00+00:00"
  }
}</code></pre>
    </div>

    <hr>

    {{-- ── Domain löschen ── --}}
    <h2 id="domain-loeschen">Domain löschen</h2>

    <div class="endpoint-block">
        <span class="method method-delete">DELETE</span>
        <span class="path">/v1/plugin/gtm/domains/{uuid}</span>
    </div>

    <p>Löscht eine einzelne Domain. Es muss immer mindestens eine Domain verbleiben.</p>

    <h4>Beispiel</h4>
    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X DELETE -H "Authorization: Bearer pk_live_xxx" \
  "https://api.global-travel-monitor.de/v1/plugin/gtm/domains/550e8400-e29b-41d4-a716-446655440000"</code></pre>
    </div>

    <p><strong>Response:</strong> <code>204 No Content</code></p>

    <blockquote>
        <strong>Fehler (422):</strong> Mindestens eine Domain muss verbleiben.
    </blockquote>

    <hr>

    {{-- ── Domains im Bulk löschen ── --}}
    <h2 id="domains-bulk-loeschen">Domains im Bulk löschen</h2>

    <div class="endpoint-block">
        <span class="method method-delete">DELETE</span>
        <span class="path">/v1/plugin/gtm/domains/bulk</span>
    </div>

    <p>Löscht bis zu <strong>1.000 Domains</strong> anhand ihrer UUIDs in einem Aufruf. Es muss mindestens eine Domain verbleiben.</p>

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
                    <td><code>uuids</code></td>
                    <td>string[]</td>
                    <td>Ja</td>
                    <td>Array von Domain-UUIDs (max. 1.000)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel</h4>
    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X DELETE -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{
    "uuids": [
      "550e8400-e29b-41d4-a716-446655440000",
      "660e8400-e29b-41d4-a716-446655440001"
    ]
  }' \
  "https://api.global-travel-monitor.de/v1/plugin/gtm/domains/bulk"</code></pre>
    </div>

    <h4>Response (200)</h4>
    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "data": {
    "deleted_count": 2
  }
}</code></pre>
    </div>

    <blockquote>
        <strong>Fehler (422):</strong> Mindestens eine Domain muss verbleiben.
    </blockquote>

    <hr>

    {{-- ══════════════════════════════════════════════ --}}
    {{-- ── FEHLERCODES                              ── --}}
    {{-- ══════════════════════════════════════════════ --}}

    <h1 id="fehlercodes">Fehlercodes</h1>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>HTTP-Code</th>
                    <th>Bedeutung</th>
                    <th>Typische Ursache</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>401</code></td>
                    <td>Nicht authentifiziert</td>
                    <td>Fehlender oder ungültiger API-Key</td>
                </tr>
                <tr>
                    <td><code>403</code></td>
                    <td>Zugriff verweigert</td>
                    <td>Plugin-Konto nicht aktiv</td>
                </tr>
                <tr>
                    <td><code>404</code></td>
                    <td>Nicht gefunden</td>
                    <td>Domain-UUID existiert nicht</td>
                </tr>
                <tr>
                    <td><code>409</code></td>
                    <td>Konflikt</td>
                    <td>Domain bereits registriert</td>
                </tr>
                <tr>
                    <td><code>422</code></td>
                    <td>Validierungsfehler</td>
                    <td>Ungültiges Domain-Format oder Mindestanzahl unterschritten</td>
                </tr>
                <tr>
                    <td><code>429</code></td>
                    <td>Rate Limit</td>
                    <td>Zu viele Requests &ndash; warten und erneut versuchen</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p>Alle Fehler-Responses folgen dem Format:</p>

    <div class="response-block">
        <span class="response-label">Error Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "error": "Beschreibung des Fehlers.",
  "details": {}
}</code></pre>
    </div>

    <p>Das <code>details</code>-Feld ist nur bei Validierungsfehlern (422) vorhanden und enthält feldspezifische Fehlermeldungen.</p>

    <hr>

    {{-- ══════════════════════════════════════════════ --}}
    {{-- ── TYPISCHE ANWENDUNGSFÄLLE                 ── --}}
    {{-- ══════════════════════════════════════════════ --}}

    <h1 id="anwendungsfaelle">Typische Anwendungsfälle</h1>

    {{-- ── Initiale Einrichtung ── --}}
    <h3>Initiale Einrichtung mit vielen Domains</h3>

    <div class="code-block">
        <span class="code-label">Bash</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code># Alle Domains aus einer Textdatei importieren
DOMAINS=$(cat domains.txt | jq -R -s 'split("\n") | map(select(length > 0))')

curl -X POST -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d "{\"domains\": $DOMAINS}" \
  "https://api.global-travel-monitor.de/v1/plugin/gtm/domains/bulk"</code></pre>
    </div>

    {{-- ── Domains synchronisieren ── --}}
    <h3>Domains synchronisieren (vollständiger Abgleich)</h3>

    <div class="code-block">
        <span class="code-label">Bash</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code># 1. Alle bestehenden Domains abrufen
EXISTING=$(curl -s -H "Authorization: Bearer pk_live_xxx" \
  "https://api.global-travel-monitor.de/v1/plugin/gtm/domains?per_page=200")

# 2. Nicht mehr benötigte Domains löschen
curl -X DELETE -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{"uuids": ["uuid1", "uuid2"]}' \
  "https://api.global-travel-monitor.de/v1/plugin/gtm/domains/bulk"

# 3. Neue Domains importieren
curl -X POST -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{"domains": ["new1.com", "new2.com"]}' \
  "https://api.global-travel-monitor.de/v1/plugin/gtm/domains/bulk"</code></pre>
    </div>

    {{-- ── Domain vorübergehend deaktivieren ── --}}
    <h3>Domain vorübergehend deaktivieren</h3>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X PUT -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{"is_active": false}' \
  "https://api.global-travel-monitor.de/v1/plugin/gtm/domains/{uuid}"</code></pre>
    </div>

    <hr>

    {{-- ── Support ── --}}
    <h2 id="support">Support &amp; Kontakt</h2>

    <p>Bei Fragen oder Problemen:</p>

    <ul>
        <li><strong>Plugin-Dashboard</strong>: <a href="https://global-travel-monitor.eu/plugin/dashboard">https://global-travel-monitor.eu/plugin/dashboard</a></li>
        <li><strong>E-Mail</strong>: <a href="mailto:support@passolution.de">support@passolution.de</a></li>
    </ul>

    <hr>

    <p><em>Letzte Aktualisierung: März 2026</em></p>

@endsection
