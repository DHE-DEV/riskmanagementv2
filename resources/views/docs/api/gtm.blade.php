@extends('docs.api.layout')

@section('title', 'Events API (GTM)')
@section('api_color', '#ef4444')

@section('sidebar')
    <span class="sidebar-heading">Events API (GTM)</span>
    <a href="#uebersicht">Übersicht</a>
    <a href="#authentifizierung">Authentifizierung</a>
    <a href="#base-url">Base-URL</a>
    <a href="#rate-limit">Rate Limit</a>
    <a href="#pagination">Pagination</a>
    <a href="#herkunft-der-events">Herkunft der Events (Source)</a>

    <span class="sidebar-heading">Events</span>
    <a href="#events">Events</a>
    <a href="#events-auflisten">Events auflisten</a>
    <a href="#einzelnes-event-anzeigen">Einzelnes Event anzeigen</a>

    <span class="sidebar-heading">Länder</span>
    <a href="#laender-mit-aktiven-events">Länder mit aktiven Events</a>

    <span class="sidebar-heading">Basisdaten</span>
    <a href="#basisdaten">Basisdaten</a>
    <a href="#kontinente">Kontinente</a>
    <a href="#laender">Länder</a>
    <a href="#regionen">Regionen</a>
    <a href="#event-kategorien">Event-Kategorien</a>

    <span class="sidebar-heading">Referenz</span>
    <a href="#datenmodelle">Datenmodelle</a>
    <a href="#fehlercodes">Fehlercodes</a>
    <a href="#support">Support</a>
@endsection

@section('content')

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Übersicht                                                     --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

    <h1 id="uebersicht">Global Travel Monitor Events API &ndash; Kundenanleitung</h1>

    <p>
        Die Events API bietet <strong>read-only</strong> Zugriff auf alle aktuell aktiven Sicherheits- und Reiserisiko-Events.
        Dies umfasst sowohl von Global Travel Monitor gepflegte Events als auch Events, die von API-Partnern eingestellt wurden.
        Es werden nur freigegebene, aktive und nicht archivierte Events angezeigt.
    </p>

    <p>
        Die API ermöglicht die Abfrage aktueller Events gefiltert nach Risikostufe, Land, Event-Typ und Region
        sowie Länder-Übersichten mit Anzahl aktiver Events.
    </p>

    <hr>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Authentifizierung                                             --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

    <h2 id="authentifizierung">Authentifizierung</h2>

    <p>Alle API-Aufrufe erfordern einen <strong>Bearer-Token</strong> im HTTP-Header:</p>

    <div class="code-block">
        <span class="code-label">HTTP Header</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>Authorization: Bearer {API_TOKEN}</code></pre>
    </div>

    <p>Den Token erhalten Sie von Ihrem Ansprechpartner bei Global Travel Monitor.</p>

    <hr>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Base-URL                                                      --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

    <h2 id="base-url">Base-URL</h2>

    <div class="code-block">
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>https://api.global-travel-monitor.de/v1</code></pre>
    </div>

    <p>
        Alternativ ist die API auch unter <code>https://global-travel-monitor.eu/api/v1</code> erreichbar.
        Wir empfehlen die Verwendung der API-Subdomain für neue Integrationen.
    </p>

    <hr>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Rate Limit                                                    --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

    <h2 id="rate-limit">Rate Limit</h2>

    <p>
        Standardmäßig sind <strong>60 Requests pro Minute</strong> erlaubt.
        Bei Überschreitung erhalten Sie einen <code>429 Too Many Requests</code>-Response.
        Prüfen Sie den <code>Retry-After</code>-Header für die Wartezeit in Sekunden.
    </p>

    <hr>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Pagination                                                    --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

    <h2 id="pagination">Pagination</h2>

    <p>
        Der Events-Endpoint liefert alle aktiven Events (einschließlich zukünftiger Events), paginiert über die Query-Parameter
        <code>page</code> und <code>per_page</code> (Standard: 25, Maximum: <strong>100</strong> pro Seite).
        Pagination-Metadaten sind im <code>meta</code>-Objekt jeder Antwort enthalten.
    </p>

    <p>
        Es werden nur Events zurückgegeben, die freigegeben (<code>approved</code>), aktiv und nicht archiviert sind.
        Mit den Parametern <code>start_date</code> und <code>end_date</code> kann der Zeitraum eingegrenzt werden.
    </p>

    <hr>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Herkunft der Events (Source)                                   --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

    <h2 id="herkunft-der-events">Herkunft der Events (Source)</h2>

    <p>Jedes Event enthält ein <code>source</code>-Objekt, das die Herkunft anzeigt:</p>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th><code>source.type</code></th>
                    <th>Bedeutung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>manual</code></td>
                    <td>Manuell von Global Travel Monitor erstellt</td>
                </tr>
                <tr>
                    <td><code>api_client</code></td>
                    <td>Von einem API-Partner über die Event API eingestellt</td>
                </tr>
                <tr>
                    <td><code>passolution_infosystem</code></td>
                    <td>Automatisch aus dem Passolution Infosystem importiert</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p>Bei Events vom Typ <code>api_client</code> enthält <code>source.name</code> den Namen des Partners (z.B. "Partner XY GmbH").</p>

    <p>Mit dem <code>source</code>-Filter können Sie gezielt Events einer bestimmten Herkunft abfragen:</p>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code># Nur manuell erstellte Events
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?source=manual"

# Nur Events von einem bestimmten Partner (nach Name)
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?source=Partner%20XY%20GmbH"</code></pre>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Events                                                        --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

    <h2 id="events">Events</h2>

    <h3 id="events-auflisten">Events auflisten</h3>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/events</span>
    </div>

    <h4>Query-Parameter</h4>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>risk_level</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Filter nach Risikostufe: <code>high</code>, <code>medium</code>, <code>low</code>, <code>info</code></td>
                </tr>
                <tr>
                    <td><code>country</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Filter nach Ländercode &ndash; ISO alpha-2 (z.B. <code>DE</code>) oder alpha-3 (z.B. <code>DEU</code>)</td>
                </tr>
                <tr>
                    <td><code>event_category</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Filter nach Event-Kategorie-Code (z.B. <code>security</code>, siehe Tabelle unten)</td>
                </tr>
                <tr>
                    <td><code>region</code></td>
                    <td>integer</td>
                    <td>Nein</td>
                    <td>Filter nach Region-ID (numerische ID)</td>
                </tr>
                <tr>
                    <td><code>source</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Filter nach Event-Herkunft (z.B. <code>manual</code>, <code>passolution_infosystem</code> oder Name des API-Partners)</td>
                </tr>
                <tr>
                    <td><code>start_date</code></td>
                    <td>date</td>
                    <td>Nein</td>
                    <td>Nur Events ab diesem Datum (z.B. <code>2026-03-01</code>)</td>
                </tr>
                <tr>
                    <td><code>end_date</code></td>
                    <td>date</td>
                    <td>Nein</td>
                    <td>Nur Events bis zu diesem Datum (z.B. <code>2026-04-30</code>)</td>
                </tr>
                <tr>
                    <td><code>per_page</code></td>
                    <td>integer</td>
                    <td>Nein</td>
                    <td>Einträge pro Seite (Standard: 25, Maximum: 100)</td>
                </tr>
                <tr>
                    <td><code>page</code></td>
                    <td>integer</td>
                    <td>Nein</td>
                    <td>Seitennummer (Standard: 1)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiele</h4>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code># Alle aktiven Events (paginiert)
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?per_page=25&amp;page=1"

# Nur Events mit hoher Risikostufe
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?risk_level=high"

# Events für ein bestimmtes Land
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?country=TR"

# Events eines bestimmten Typs
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?event_category=security"

# Nur manuell erstellte Events
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?source=manual"

# Events in einem Zeitraum
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?start_date=2026-03-01&amp;end_date=2026-03-31"

# Filter kombinieren
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?risk_level=high&amp;country=TR&amp;source=manual&amp;per_page=10"</code></pre>
    </div>

    <h4>Response (200 OK)</h4>

    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "title": "Earthquake in Turkey",
      "description": "A 6.2 magnitude earthquake struck southeastern Turkey.",
      "risk_level": "high",
      "start_date": "2025-03-15T08:30:00Z",
      "end_date": null,
      "latitude": 37.7749,
      "longitude": 35.3214,
      "event_categories": [
        {
          "code": "security",
          "name": "Sicherheit"
        }
      ],
      "countries": [
        {
          "iso_code": "TR",
          "iso3_code": "TUR",
          "name_de": "Tuerkei",
          "name_en": "Turkey",
          "continent": "Asia",
          "latitude": 37.7749,
          "longitude": 35.3214
        }
      ],
      "source": {
        "type": "api_client",
        "name": "Partner XY GmbH"
      },
      "created_at": "2025-03-15T09:00:00Z",
      "updated_at": "2025-03-15T10:15:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 142,
    "last_page": 15
  }
}</code></pre>
    </div>

    <hr>

    {{-- ── Einzelnes Event anzeigen ── --}}

    <h3 id="einzelnes-event-anzeigen">Einzelnes Event anzeigen</h3>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/events/{uuid}</span>
    </div>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events/550e8400-e29b-41d4-a716-446655440000"</code></pre>
    </div>

    <h4>Response (200 OK)</h4>

    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Earthquake in Turkey",
    "description": "A 6.2 magnitude earthquake struck southeastern Turkey.",
    "risk_level": "high",
    "start_date": "2025-03-15T08:30:00Z",
    "end_date": null,
    "latitude": 37.7749,
    "longitude": 35.3214,
    "event_categories": [
      {
        "code": "security",
        "name": "Sicherheit"
      }
    ],
    "countries": [
      {
        "iso_code": "TR",
        "iso3_code": "TUR",
        "name_de": "Tuerkei",
        "name_en": "Turkey",
        "continent": "Asia",
        "latitude": 37.7749,
        "longitude": 35.3214
      }
    ],
    "source": {
      "type": "api_client",
      "name": "Partner XY GmbH"
    },
    "created_at": "2025-03-15T09:00:00Z",
    "updated_at": "2025-03-15T10:15:00Z"
  }
}</code></pre>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Länder mit aktiven Events                                     --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

    <h2 id="laender-mit-aktiven-events">Länder mit aktiven Events</h2>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/events/countries</span>
    </div>

    <p>
        Gibt eine Liste aller Länder zurück, die mindestens ein aktives Event haben,
        zusammen mit der Anzahl aktiver Events. Sortiert nach Anzahl (absteigend). Nicht paginiert.
    </p>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events/countries"</code></pre>
    </div>

    <h4>Response (200 OK)</h4>

    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": [
    {
      "iso_code": "DE",
      "iso3_code": "DEU",
      "name_de": "Deutschland",
      "name_en": "Germany",
      "continent": "Europe",
      "continent_de": "Europa",
      "lat": 51.1657,
      "lng": 10.4515,
      "is_eu_member": true,
      "is_schengen_member": true,
      "active_events_count": 3
    },
    {
      "iso_code": "TR",
      "iso3_code": "TUR",
      "name_de": "Tuerkei",
      "name_en": "Turkey",
      "continent": "Asia",
      "continent_de": "Asien",
      "lat": 38.9637,
      "lng": 35.2433,
      "is_eu_member": false,
      "is_schengen_member": false,
      "active_events_count": 7
    }
  ]
}</code></pre>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Basisdaten                                                    --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

    <h2 id="basisdaten">Basisdaten</h2>

    {{-- ── Kontinente ── --}}

    <h3 id="kontinente">Kontinente</h3>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/continents</span>
    </div>

    <p>Gibt eine Liste aller Kontinente zurück.</p>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/continents"</code></pre>
    </div>

    <h4>Response (200 OK)</h4>

    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": [
    {
      "code": "EU",
      "name_de": "Europa",
      "name_en": "Europe",
      "lat": 54.526,
      "lng": 15.2551
    },
    {
      "code": "AS",
      "name_de": "Asien",
      "name_en": "Asia",
      "lat": 34.0479,
      "lng": 100.6197
    }
  ]
}</code></pre>
    </div>

    <hr>

    {{-- ── Länder ── --}}

    <h3 id="laender">Länder</h3>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/countries</span>
    </div>

    <p>Gibt eine Liste aller Länder zurück. Optional nach Kontinent filterbar.</p>

    <h4>Query-Parameter</h4>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>continent</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Filter nach Kontinent-Code (z.B. <code>EU</code>, <code>AS</code>)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiele</h4>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code># Alle Länder
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/countries"

# Nur europäische Länder
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/countries?continent=EU"</code></pre>
    </div>

    <h4>Response (200 OK)</h4>

    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": [
    {
      "iso_code": "DE",
      "iso3_code": "DEU",
      "name_de": "Deutschland",
      "name_en": "Germany",
      "continent": "Europe",
      "continent_de": "Europa",
      "lat": 51.1657,
      "lng": 10.4515,
      "is_eu_member": true,
      "is_schengen_member": true
    }
  ]
}</code></pre>
    </div>

    <hr>

    {{-- ── Regionen ── --}}

    <h3 id="regionen">Regionen</h3>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/regions</span>
    </div>

    <p>Gibt eine Liste aller Regionen zurück. Optional nach Land filterbar. Nützlich um die gültigen Werte für den <code>region</code>-Filter zu ermitteln.</p>

    <h4>Query-Parameter</h4>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>country</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Filter nach Ländercode &ndash; ISO alpha-2 (z.B. <code>DE</code>) oder alpha-3 (z.B. <code>DEU</code>)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiele</h4>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code># Alle Regionen
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/regions"

# Nur Regionen in Deutschland
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/regions?country=DE"</code></pre>
    </div>

    <h4>Response (200 OK)</h4>

    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": [
    {
      "id": 42,
      "name_de": "Bayern",
      "name_en": "Bavaria",
      "code": "BY",
      "country_iso_code": "DE",
      "country_name_de": "Deutschland",
      "lat": 48.7904,
      "lng": 11.4979
    }
  ]
}</code></pre>
    </div>

    <hr>

    {{-- ── Event-Kategorien ── --}}

    <h3 id="event-kategorien">Event-Kategorien</h3>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/event-categories</span>
    </div>

    <p>Gibt eine Liste aller verfügbaren Event-Kategorien zurück. Nützlich um die gültigen Werte für den <code>event_category</code>-Filter zu ermitteln.</p>

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
                <tr>
                    <td><code>environment</code></td>
                    <td>Umweltereignisse</td>
                </tr>
                <tr>
                    <td><code>traffic</code></td>
                    <td>Reiseverkehr</td>
                </tr>
                <tr>
                    <td><code>security</code></td>
                    <td>Sicherheit</td>
                </tr>
                <tr>
                    <td><code>entry</code></td>
                    <td>Einreisebestimmungen</td>
                </tr>
                <tr>
                    <td><code>general</code></td>
                    <td>Allgemein</td>
                </tr>
                <tr>
                    <td><code>health</code></td>
                    <td>Gesundheit</td>
                </tr>
            </tbody>
        </table>
    </div>

    <blockquote>
        <strong>Hinweis:</strong> Diese Liste kann sich ändern. Nutzen Sie den Endpoint <code>GET /v1/event-categories</code>, um stets die aktuellen Kategorien abzurufen.
    </blockquote>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/event-categories"</code></pre>
    </div>

    <h4>Response (200 OK)</h4>

    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": [
    {
      "code": "environment",
      "name": "Umweltereignisse"
    },
    {
      "code": "security",
      "name": "Sicherheit"
    }
  ]
}</code></pre>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Datenmodelle                                                  --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

    <h2 id="datenmodelle">Datenmodelle</h2>

    <h3>Event</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>id</code></td>
                    <td>string (UUID)</td>
                    <td>Eindeutige ID des Events</td>
                </tr>
                <tr>
                    <td><code>title</code></td>
                    <td>string</td>
                    <td>Kurzer Titel des Events</td>
                </tr>
                <tr>
                    <td><code>description</code></td>
                    <td>string / null</td>
                    <td>Detaillierte Beschreibung</td>
                </tr>
                <tr>
                    <td><code>risk_level</code></td>
                    <td>string</td>
                    <td>Risikostufe: <code>high</code>, <code>medium</code>, <code>low</code>, <code>info</code></td>
                </tr>
                <tr>
                    <td><code>start_date</code></td>
                    <td>datetime / null</td>
                    <td>Startdatum (ISO 8601)</td>
                </tr>
                <tr>
                    <td><code>end_date</code></td>
                    <td>datetime / null</td>
                    <td>Enddatum (null = andauernd)</td>
                </tr>
                <tr>
                    <td><code>latitude</code></td>
                    <td>number / null</td>
                    <td>Breitengrad</td>
                </tr>
                <tr>
                    <td><code>longitude</code></td>
                    <td>number / null</td>
                    <td>Längengrad</td>
                </tr>
                <tr>
                    <td><code>event_categories</code></td>
                    <td>array</td>
                    <td>Liste der zugewiesenen Event-Typen</td>
                </tr>
                <tr>
                    <td><code>countries</code></td>
                    <td>array</td>
                    <td>Liste betroffener Länder</td>
                </tr>
                <tr>
                    <td><code>source</code></td>
                    <td>object</td>
                    <td>Herkunft des Events</td>
                </tr>
                <tr>
                    <td><code>source.type</code></td>
                    <td>string</td>
                    <td>Quelle: <code>manual</code>, <code>api_client</code>, <code>passolution_infosystem</code>, etc.</td>
                </tr>
                <tr>
                    <td><code>source.name</code></td>
                    <td>string / null</td>
                    <td>Name des API-Partners (bei API-Client-Events)</td>
                </tr>
                <tr>
                    <td><code>created_at</code></td>
                    <td>datetime</td>
                    <td>Erstellungszeitpunkt</td>
                </tr>
                <tr>
                    <td><code>updated_at</code></td>
                    <td>datetime</td>
                    <td>Letzter Änderungszeitpunkt</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3>Event-Typ</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>code</code></td>
                    <td>string</td>
                    <td>Maschinenlesbarer Code (z.B. <code>security</code>)</td>
                </tr>
                <tr>
                    <td><code>name</code></td>
                    <td>string</td>
                    <td>Anzeigename</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3>Land (Event-Kontext)</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>iso_code</code></td>
                    <td>string</td>
                    <td>ISO 3166-1 alpha-2 Code (z.B. <code>DE</code>)</td>
                </tr>
                <tr>
                    <td><code>iso3_code</code></td>
                    <td>string</td>
                    <td>ISO 3166-1 alpha-3 Code (z.B. <code>DEU</code>)</td>
                </tr>
                <tr>
                    <td><code>name_de</code></td>
                    <td>string</td>
                    <td>Ländername (deutsch)</td>
                </tr>
                <tr>
                    <td><code>name_en</code></td>
                    <td>string</td>
                    <td>Ländername (englisch)</td>
                </tr>
                <tr>
                    <td><code>continent</code></td>
                    <td>string</td>
                    <td>Kontinent</td>
                </tr>
                <tr>
                    <td><code>latitude</code></td>
                    <td>number / null</td>
                    <td>Breitengrad (Event-Standort im Land)</td>
                </tr>
                <tr>
                    <td><code>longitude</code></td>
                    <td>number / null</td>
                    <td>Längengrad (Event-Standort im Land)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3>Land (Countries-Endpoint)</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>iso_code</code></td>
                    <td>string</td>
                    <td>ISO 3166-1 alpha-2 Code</td>
                </tr>
                <tr>
                    <td><code>iso3_code</code></td>
                    <td>string</td>
                    <td>ISO 3166-1 alpha-3 Code</td>
                </tr>
                <tr>
                    <td><code>name_de</code></td>
                    <td>string</td>
                    <td>Ländername (deutsch)</td>
                </tr>
                <tr>
                    <td><code>name_en</code></td>
                    <td>string</td>
                    <td>Ländername (englisch)</td>
                </tr>
                <tr>
                    <td><code>continent</code></td>
                    <td>string / null</td>
                    <td>Kontinent (englisch)</td>
                </tr>
                <tr>
                    <td><code>continent_de</code></td>
                    <td>string / null</td>
                    <td>Kontinent (deutsch)</td>
                </tr>
                <tr>
                    <td><code>lat</code></td>
                    <td>number / null</td>
                    <td>Breitengrad (Zentroid)</td>
                </tr>
                <tr>
                    <td><code>lng</code></td>
                    <td>number / null</td>
                    <td>Längengrad (Zentroid)</td>
                </tr>
                <tr>
                    <td><code>is_eu_member</code></td>
                    <td>boolean</td>
                    <td>EU-Mitglied</td>
                </tr>
                <tr>
                    <td><code>is_schengen_member</code></td>
                    <td>boolean</td>
                    <td>Schengen-Mitglied</td>
                </tr>
                <tr>
                    <td><code>active_events_count</code></td>
                    <td>integer</td>
                    <td>Anzahl aktiver Events</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Fehlercodes                                                   --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

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
                <tr>
                    <td><code>200</code></td>
                    <td>Erfolgreich</td>
                </tr>
                <tr>
                    <td><code>401</code></td>
                    <td>Nicht authentifiziert (Token fehlt oder ungültig)</td>
                </tr>
                <tr>
                    <td><code>403</code></td>
                    <td>Zugriff verweigert</td>
                </tr>
                <tr>
                    <td><code>404</code></td>
                    <td>Ressource nicht gefunden</td>
                </tr>
                <tr>
                    <td><code>422</code></td>
                    <td>Validierungsfehler (ungültige Filter-Parameter)</td>
                </tr>
                <tr>
                    <td><code>429</code></td>
                    <td>Rate Limit überschritten</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel Fehler-Response</h4>

    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": false,
  "message": "Unauthenticated."
}</code></pre>
    </div>

    <h4>Beispiel Validierungsfehler (422)</h4>

    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "risk_level": ["The selected risk_level is invalid."],
    "per_page": ["The per page field must be between 1 and 100."]
  }
}</code></pre>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Support                                                       --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}

    <h2 id="support">Support</h2>

    <p>Bei Fragen zur API wenden Sie sich an Ihren Ansprechpartner bei Global Travel Monitor.</p>

@endsection
