<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Travel Monitor (GTM) - REST API</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }
        .header {
            background: #0f172a;
            color: #fff;
            padding: 3rem 1.5rem;
            text-align: center;
        }
        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .header p {
            color: #94a3b8;
            font-size: 1.1rem;
        }
        .header .base-url {
            display: inline-block;
            margin-top: 1.25rem;
            background: #1e293b;
            padding: 0.5rem 1.25rem;
            border-radius: 6px;
            font-family: 'SF Mono', SFMono-Regular, Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 0.95rem;
            color: #38bdf8;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
        }
        .auth-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .auth-box h2 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .auth-box code {
            display: block;
            background: #f1f5f9;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            color: #334155;
        }
        .auth-box p {
            margin-top: 0.75rem;
            font-size: 0.9rem;
            color: #64748b;
        }
        .apis {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 640px) {
            .apis { grid-template-columns: 1fr 1fr; }
        }
        .api-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        .api-card h3 {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .api-card .description {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 1rem;
            flex: 1;
        }
        .api-card .badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }
        .badge-auth {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-public {
            background: #d1fae5;
            color: #065f46;
        }
        .endpoints {
            list-style: none;
            margin-bottom: 1.25rem;
        }
        .endpoints li {
            font-size: 0.85rem;
            padding: 0.35rem 0;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .endpoints li:last-child { border-bottom: none; }
        .method {
            display: inline-block;
            font-family: 'SF Mono', SFMono-Regular, Consolas, monospace;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.1rem 0.35rem;
            border-radius: 3px;
            min-width: 3rem;
            text-align: center;
        }
        .method-get { background: #dbeafe; color: #1e40af; }
        .method-post { background: #d1fae5; color: #065f46; }
        .method-put { background: #fef3c7; color: #92400e; }
        .method-delete { background: #fee2e2; color: #991b1b; }
        .endpoint-path {
            font-family: 'SF Mono', SFMono-Regular, Consolas, monospace;
            font-size: 0.8rem;
            color: #334155;
        }
        .downloads {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.15s;
        }
        .btn-primary {
            background: #0f172a;
            color: #fff;
        }
        .btn-primary:hover { background: #1e293b; }
        .btn-outline {
            background: #fff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-outline:hover { background: #f8fafc; }
        .btn svg {
            width: 14px;
            height: 14px;
        }
        .footer {
            text-align: center;
            padding: 2rem 1.5rem;
            color: #94a3b8;
            font-size: 0.85rem;
            border-top: 1px solid #e2e8f0;
        }
        .footer a {
            color: #64748b;
            text-decoration: none;
        }
        .footer a:hover { text-decoration: underline; }

        /* Documentation section styles */
        .doc-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 2rem;
            margin-top: 2rem;
        }
        .doc-section h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #0f172a;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .doc-section h3 {
            font-size: 1.15rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
            color: #1e293b;
        }
        .doc-section h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            color: #334155;
        }
        .doc-section p {
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
            color: #475569;
        }
        .doc-section hr {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 1.5rem 0;
        }
        .doc-section pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem 1.25rem;
            border-radius: 6px;
            overflow-x: auto;
            margin-bottom: 1rem;
            font-size: 0.8rem;
            line-height: 1.5;
        }
        .doc-section pre code {
            background: none;
            padding: 0;
            color: inherit;
            font-size: inherit;
        }
        .doc-section code {
            background: #f1f5f9;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-family: 'SF Mono', SFMono-Regular, Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 0.8rem;
            color: #334155;
        }
        .doc-section table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }
        .doc-section table th {
            background: #f8fafc;
            text-align: left;
            padding: 0.6rem 0.75rem;
            border: 1px solid #e2e8f0;
            font-weight: 600;
            color: #334155;
        }
        .doc-section table td {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            color: #475569;
        }
        .doc-section table tr:hover td {
            background: #f8fafc;
        }
        .doc-section ul, .doc-section ol {
            margin-bottom: 0.75rem;
            padding-left: 1.5rem;
            font-size: 0.9rem;
            color: #475569;
        }
        .doc-section li {
            margin-bottom: 0.35rem;
        }
        .doc-section blockquote {
            border-left: 3px solid #38bdf8;
            background: #f0f9ff;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: 0 6px 6px 0;
        }
        .doc-section blockquote p {
            margin-bottom: 0;
            color: #0c4a6e;
        }
        .doc-section .table-responsive {
            overflow-x: auto;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Global Travel Monitor (GTM)</h1>
        <p>Dokumentation REST API</p>
        <div class="base-url">https://api.global-travel-monitor.de/v1</div>
    </div>

    <div class="container">
        <div class="auth-box">
            <h2>Authentifizierung</h2>
            <code>Authorization: Bearer {API_TOKEN}</code>
            <p>Den Token erhalten Sie von Ihrem Ansprechpartner bei Passolution.</p>
        </div>

        <div class="apis">
            {{-- Event API --}}
            <div class="api-card">
                <span class="badge badge-auth">Bearer Token</span>
                <h3>Event API</h3>
                <p class="description">Events abrufen, erstellen und verwalten. Unterstützt Multi-Scope-Abfragen und Event-Gruppen.</p>
                <ul class="endpoints">
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/events</span></li>
                    <li><span class="method method-post">POST</span> <span class="endpoint-path">/v1/events</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/events/{uuid}</span></li>
                    <li><span class="method method-put">PUT</span> <span class="endpoint-path">/v1/events/{uuid}</span></li>
                    <li><span class="method method-delete">DEL</span> <span class="endpoint-path">/v1/events/{uuid}</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/event-types</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/countries</span></li>
                </ul>
                <div class="downloads">
                    <a href="/docs/event-api-openapi.yaml" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        OpenAPI Spec
                    </a>
                </div>
            </div>

            {{-- GTM API - ausgeblendet
            <div class="api-card">
                <span class="badge badge-auth">Bearer Token</span>
                <h3>GTM API</h3>
                <p class="description">Read-only Zugriff auf alle aktiven Sicherheits- und Reiserisiko-Events sowie Länder-Übersichten.</p>
                <ul class="endpoints">
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/gtm/events</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/gtm/events/{id}</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/gtm/countries</span></li>
                </ul>
                <div class="downloads">
                    <a href="/docs/gtm-api-openapi.yaml" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        OpenAPI Spec
                    </a>
                </div>
            </div>
            -->

            {{-- Folder Import API --}}
            <div class="api-card">
                <span class="badge badge-auth">Bearer Token</span>
                <h3>Folder Import API</h3>
                <p class="description">Import von Reisedaten mit Hotels, Flügen, Kreuzfahrten und Mietwagen. Queue-basierte Verarbeitung.</p>
                <ul class="endpoints">
                    <li><span class="method method-post">POST</span> <span class="endpoint-path">/api/customer/folders/import</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/api/customer/folders</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/api/customer/folders/{id}</span></li>
                </ul>
                <div class="downloads">
                    <a href="/docs/folder-import-api-openapi.yaml" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        OpenAPI Spec
                    </a>
                </div>
            </div>

            {{-- Feed API --}}
            <div class="api-card">
                <span class="badge badge-public">Öffentlich</span>
                <h3>Feed API</h3>
                <p class="description">RSS/Atom-Feeds für aktuelle Sicherheits- und Reiserisiko-Events. Keine Authentifizierung erforderlich.</p>
                <ul class="endpoints">
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/feed/events</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/feed/countries</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/feed/events/meta.json</span></li>
                </ul>
                <div class="downloads">
                    <a href="/docs/feed-api-openapi.yaml" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        OpenAPI Spec
                    </a>
                    <a href="/docs/feed-api-guide.md" class="btn btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        Anleitung
                    </a>
                </div>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- Event API Guide --}}
        {{-- ========================================== --}}
        <div class="doc-section" id="event-api-guide">
            <h2>Event API – Anleitung</h2>

            <h3>Übersicht</h3>
            <p>Die Event API ermöglicht es externen Partnern, Events auf dem Risk Management Dashboard abzurufen sowie — bei entsprechender Freischaltung — eigene Events zu erstellen und zu verwalten.</p>
            <blockquote>
                <p><strong>Wichtig:</strong> Das Erstellen, Aktualisieren und Löschen von Events erfordert eine separate Freischaltung Ihres Accounts durch Passolution. Ohne diese Freischaltung können Sie die API nur zum Lesen von Events nutzen. Bei einem Versuch ohne Freischaltung erhalten Sie einen <code>403 Forbidden</code> Response.</p>
            </blockquote>

            <hr>

            <h3>Authentifizierung</h3>
            <p>Alle API-Aufrufe erfordern einen <strong>Bearer-Token</strong> im HTTP-Header:</p>
            <pre><code>Authorization: Bearer {API_TOKEN}</code></pre>
            <p>Den Token erhalten Sie von Ihrem Ansprechpartner bei Passolution. Er ist 1 Jahr gültig.</p>

            <hr>

            <h3>Base-URL</h3>
            <pre><code>https://api.global-travel-monitor.de/v1</code></pre>

            <hr>

            <h3>Rate Limit</h3>
            <p>Standardmäßig sind <strong>60 Requests pro Minute</strong> erlaubt. Bei Überschreitung erhalten Sie einen <code>429 Too Many Requests</code> Response.</p>

            <hr>

            <h3>Referenzdaten</h3>
            <p>Bevor Sie Events erstellen, fragen Sie die gültigen Event-Typen und Ländercodes ab.</p>

            <h4>Event-Typen abrufen</h4>
            <pre><code>GET /v1/event-types</code></pre>
            <p><strong>Beispiel:</strong></p>
            <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  https://api.global-travel-monitor.de/v1/event-types</code></pre>
            <p><strong>Response:</strong></p>
            <pre><code>{
  "success": true,
  "data": [
    {
      "code": "earthquake",
      "name": "Erdbeben",
      "color": "#FF0000",
      "icon": "fa-house-crack"
    },
    {
      "code": "flood",
      "name": "Überschwemmung",
      "color": "#0066CC",
      "icon": "fa-water"
    }
  ]
}</code></pre>

            <h4>Länder abrufen</h4>
            <pre><code>GET /v1/countries</code></pre>
            <p><strong>Beispiel:</strong></p>
            <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  https://api.global-travel-monitor.de/v1/countries</code></pre>
            <p><strong>Response:</strong></p>
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

            <hr>

            <h3>Events</h3>

            <h4>Event erstellen</h4>
            <blockquote><p>Erfordert Freischaltung der Event-Erstellung für Ihren Account.</p></blockquote>
            <pre><code>POST /v1/events</code></pre>
            <p><strong>Request-Body (JSON):</strong></p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Feld</th><th>Typ</th><th>Pflicht</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>title</code></td><td>string</td><td>Ja</td><td>Titel des Events (max. 255 Zeichen)</td></tr>
                        <tr><td><code>description</code></td><td>string</td><td>Nein</td><td>Beschreibung (max. 10.000 Zeichen, HTML erlaubt: p, br, strong, em, ul, ol, li, a)</td></tr>
                        <tr><td><code>priority</code></td><td>string</td><td>Nein</td><td>Priorität: <code>info</code>, <code>low</code>, <code>medium</code> (Standard), <code>high</code></td></tr>
                        <tr><td><code>start_date</code></td><td>datetime</td><td>Ja</td><td>Startdatum (ISO 8601, z.B. <code>2026-02-11T08:00:00Z</code>)</td></tr>
                        <tr><td><code>end_date</code></td><td>datetime</td><td>Nein</td><td>Enddatum (muss gleich oder nach start_date liegen)</td></tr>
                        <tr><td><code>event_type_codes</code></td><td>array</td><td>Ja</td><td>Event-Typ-Codes (mindestens 1, aus <code>/event-types</code>)</td></tr>
                        <tr><td><code>country_codes</code></td><td>array</td><td>Ja</td><td>ISO-2-Ländercodes (mindestens 1, z.B. <code>["DE", "AT"]</code>)</td></tr>
                        <tr><td><code>latitude</code></td><td>number</td><td>Nein</td><td>Breitengrad (-90 bis 90)</td></tr>
                        <tr><td><code>longitude</code></td><td>number</td><td>Nein</td><td>Längengrad (-180 bis 180)</td></tr>
                        <tr><td><code>tags</code></td><td>array</td><td>Nein</td><td>Schlagwörter (z.B. <code>["flooding", "bangkok"]</code>)</td></tr>
                        <tr><td><code>external_id</code></td><td>string</td><td>Nein</td><td>Ihre interne Referenz-ID (max. 255 Zeichen)</td></tr>
                    </tbody>
                </table>
            </div>

            <p><strong>Beispiel:</strong></p>
            <pre><code>curl -X POST https://api.global-travel-monitor.de/v1/events \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Überschwemmung Bangkok",
    "description": "&lt;p&gt;Schwere Überschwemmungen im Großraum Bangkok.&lt;/p&gt;",
    "priority": "high",
    "start_date": "2026-02-11T08:00:00Z",
    "end_date": "2026-02-18T08:00:00Z",
    "event_type_codes": ["flood"],
    "country_codes": ["TH"],
    "latitude": 13.7563,
    "longitude": 100.5018,
    "tags": ["flooding", "bangkok"],
    "external_id": "EXT-2026-001"
  }'</code></pre>

            <p><strong>Response (201 Created):</strong></p>
            <pre><code>{
  "success": true,
  "message": "Event created and published successfully.",
  "data": {
    "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "title": "Überschwemmung Bangkok",
    "description": "Schwere Überschwemmungen im Großraum Bangkok.",
    "priority": "high",
    "start_date": "2026-02-11T08:00:00+00:00",
    "end_date": "2026-02-18T08:00:00+00:00",
    "latitude": 13.7563,
    "longitude": 100.5018,
    "review_status": "approved",
    "is_active": true,
    "tags": ["flooding", "bangkok"],
    "event_types": [
      {
        "code": "flood",
        "name": "Überschwemmung",
        "color": "#0066CC",
        "icon": "fa-water"
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

            <blockquote>
                <p><strong>Hinweis:</strong> Wenn für Ihren Account die Auto-Freigabe nicht aktiviert ist, lautet der <code>review_status</code> <code>pending_review</code> und <code>is_active</code> ist <code>false</code>. Das Event wird erst nach manueller Freigabe durch Passolution auf dem Dashboard sichtbar.</p>
            </blockquote>

            <hr>

            <h4>Eigene Events auflisten</h4>
            <pre><code>GET /v1/events</code></pre>
            <p>Standardmäßig werden nur <strong>eigene Events</strong> zurückgegeben — also Events, die über Ihren API-Token erstellt wurden. Mit dem Parameter <code>scope</code> können Sie zusätzlich <strong>Passolution-Events</strong> und <strong>Events von Partner-Gruppen</strong> abrufen.</p>
            <p>Der <code>scope</code>-Parameter unterstützt <strong>kommagetrennte Werte</strong>, um mehrere Quellen gleichzeitig abzufragen.</p>

            <p><strong>Query-Parameter:</strong></p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Parameter</th><th>Typ</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>scope</code></td><td>string</td><td>Kommagetrennte Liste von Scope-Werten (Standard: <code>own</code>)</td></tr>
                        <tr><td><code>per_page</code></td><td>integer</td><td>Einträge pro Seite (Standard: 25)</td></tr>
                        <tr><td><code>page</code></td><td>integer</td><td>Seitennummer</td></tr>
                    </tbody>
                </table>
            </div>

            <p><strong>Scope-Werte:</strong></p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Wert</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>own</code></td><td>Nur Ihre eigenen Events (Standard)</td></tr>
                        <tr><td><code>passolution</code></td><td>Nur von Passolution bereitgestellte Events (aktiv und freigegeben)</td></tr>
                        <tr><td><code>all</code></td><td>Ihre eigenen Events + Passolution-Events zusammen</td></tr>
                        <tr><td><code>{gruppen-slug}</code></td><td>Events der API-Kunden in der angegebenen Event-Gruppe (aktiv, freigegeben, nicht archiviert). Wenn die Gruppe <code>include_passolution_events</code> aktiviert hat, werden zusätzlich Passolution-Events mitgeliefert.</td></tr>
                    </tbody>
                </table>
            </div>

            <blockquote><p><strong>Hinweis:</strong> Partner-Events (über Gruppen) und Passolution-Events werden nur angezeigt, wenn sie aktiv, freigegeben und nicht archiviert sind.</p></blockquote>

            <p><strong>Beispiele:</strong></p>
            <pre><code># Eigene Events (Standard)
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?per_page=10&amp;page=1"

# Nur Passolution-Events
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?scope=passolution"

# Alle Events (eigene + Passolution)
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?scope=all"

# Eigene + Passolution (kommagetrennt, entspricht scope=all)
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?scope=own,passolution"

# Events einer Partner-Gruppe
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?scope=meine-partner-gruppe"

# Eigene Events + Partner-Gruppe kombiniert
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/events?scope=own,meine-partner-gruppe"</code></pre>

            <hr>

            <h4>Einzelnes Event anzeigen</h4>
            <pre><code>GET /v1/events/{uuid}</code></pre>
            <p><strong>Beispiel:</strong></p>
            <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  https://api.global-travel-monitor.de/v1/events/a1b2c3d4-e5f6-7890-abcd-ef1234567890</code></pre>

            <hr>

            <h4>Event aktualisieren</h4>
            <blockquote><p>Erfordert Freischaltung der Event-Erstellung für Ihren Account.</p></blockquote>
            <pre><code>PUT /v1/events/{uuid}</code></pre>
            <p>Es müssen nur die zu ändernden Felder gesendet werden.</p>
            <p><strong>Beispiel:</strong></p>
            <pre><code>curl -X PUT https://api.global-travel-monitor.de/v1/events/a1b2c3d4-e5f6-7890-abcd-ef1234567890 \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Überschwemmung Bangkok - Entwarnung",
    "priority": "low"
  }'</code></pre>
            <p><strong>Response (200 OK):</strong></p>
            <pre><code>{
  "success": true,
  "message": "Event updated successfully.",
  "data": { ... }
}</code></pre>

            <hr>

            <h4>Event löschen</h4>
            <blockquote><p>Erfordert Freischaltung der Event-Erstellung für Ihren Account.</p></blockquote>
            <pre><code>DELETE /v1/events/{uuid}</code></pre>
            <p><strong>Beispiel:</strong></p>
            <pre><code>curl -X DELETE https://api.global-travel-monitor.de/v1/events/a1b2c3d4-e5f6-7890-abcd-ef1234567890 \
  -H "Authorization: Bearer {TOKEN}"</code></pre>
            <p><strong>Response (200 OK):</strong></p>
            <pre><code>{
  "success": true,
  "message": "Event deleted successfully."
}</code></pre>

            <hr>

            <h3>Fehlercodes</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>HTTP-Code</th><th>Bedeutung</th></tr>
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

            <p><strong>Beispiel Validierungsfehler (422):</strong></p>
            <pre><code>{
  "message": "The title field is required.",
  "errors": {
    "title": ["The title field is required."],
    "event_type_codes": ["At least one event type code is required."]
  }
}</code></pre>

            <hr>

            <h3>Review-Workflow</h3>
            <p>Je nach Konfiguration Ihres Accounts gibt es zwei Modi:</p>
            <ol>
                <li><strong>Auto-Freigabe aktiviert:</strong> Events werden sofort veröffentlicht (<code>review_status: approved</code>, <code>is_active: true</code>)</li>
                <li><strong>Auto-Freigabe deaktiviert:</strong> Events werden zur Prüfung eingereicht (<code>review_status: pending_review</code>, <code>is_active: false</code>) und erst nach Freigabe durch das Passolution-Team sichtbar</li>
            </ol>

            <hr>

            <h3>Logo auf dem Dashboard</h3>
            <p>Wenn ein Firmenlogo in Ihrem API-Account hinterlegt ist, wird dieses als Quellen-Logo neben Ihren Events auf dem Dashboard angezeigt. Ohne Logo erscheint Ihr Firmenname als Text.</p>

            <hr>

            <h3>Support</h3>
            <p>Bei Fragen zur API wenden Sie sich an Ihren Ansprechpartner bei Passolution.</p>
        </div>

        {{-- ========================================== --}}
        {{-- Folder Import API Guide --}}
        {{-- ========================================== --}}
        <div class="doc-section" id="folder-import-api-guide">
            <h2>Folder Import API – Anleitung</h2>

            <h3>Übersicht</h3>
            <p>Die Folder Import API ermöglicht den Import von Reisedaten (Folders) mit Hotels, Flügen, Kreuzfahrten und Mietwagen. Der Import läuft queue-basiert im Hintergrund und bietet automatisches Airport-Matching, Country-Matching, Timeline-Generierung und Geocoding.</p>

            <hr>

            <h3>Authentifizierung</h3>
            <p>Alle API-Aufrufe erfordern einen <strong>Bearer-Token</strong> im HTTP-Header:</p>
            <pre><code>Authorization: Bearer {API_TOKEN}</code></pre>

            <h4>Token generieren</h4>
            <p>Der Token wird über die Web-Oberfläche generiert (erfordert eine aktive Session):</p>
            <pre><code>POST /customer/api-tokens/generate</code></pre>
            <p><strong>Response:</strong></p>
            <pre><code>{
  "success": true,
  "token": "2|RHej0fNgjGSzvPrEcSuY7nMGI7fldCnOMoBrpl2T173373b5",
  "message": "API Token erfolgreich generiert"
}</code></pre>
            <blockquote><p><strong>Wichtig:</strong> Speichern Sie den Token sicher ab. Er wird nur einmal im Klartext angezeigt.</p></blockquote>

            <hr>

            <h3>Base-URL</h3>
            <pre><code>https://global-travel-monitor.eu/api</code></pre>

            <hr>

            <h3>Folder importieren</h3>
            <pre><code>POST /customer/folders/import</code></pre>
            <p>Importiert einen kompletten Folder mit allen zugehörigen Daten. Der Import wird in eine Queue eingereiht und im Hintergrund verarbeitet. Die Response enthält eine <code>log_id</code> zum Status-Tracking.</p>

            <h4>Request-Struktur</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Feld</th><th>Typ</th><th>Pflicht</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>source</code></td><td>string</td><td>Ja</td><td>Import-Quelle: <code>api</code>, <code>file</code>, <code>manual</code></td></tr>
                        <tr><td><code>provider</code></td><td>string</td><td>Ja</td><td>Name des Datenlieferanten (max. 128 Zeichen)</td></tr>
                        <tr><td><code>data</code></td><td>object</td><td>Ja</td><td>Die eigentlichen Reisedaten (siehe unten)</td></tr>
                        <tr><td><code>mapping_config</code></td><td>object</td><td>Nein</td><td>Optionale Mapping-Konfiguration</td></tr>
                    </tbody>
                </table>
            </div>

            <h4>Daten-Struktur (<code>data</code>)</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Feld</th><th>Typ</th><th>Pflicht</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>folder</code></td><td>object</td><td>Ja</td><td>Vorgangsdaten</td></tr>
                        <tr><td><code>customer</code></td><td>object</td><td>Nein</td><td>Kundendaten</td></tr>
                        <tr><td><code>participants</code></td><td>array</td><td>Nein</td><td>Reiseteilnehmer</td></tr>
                        <tr><td><code>itineraries</code></td><td>array</td><td>Ja</td><td>Reiseleistungen (Hotels, Flüge, etc.)</td></tr>
                    </tbody>
                </table>
            </div>

            <hr>

            <h4>Folder (Vorgangsdaten)</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Feld</th><th>Typ</th><th>Pflicht</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>folder_number</code></td><td>string</td><td>Nein</td><td>Eindeutige Vorgangsnummer (wird automatisch generiert)</td></tr>
                        <tr><td><code>folder_name</code></td><td>string</td><td>Nein</td><td>Name der Reise (max. 255 Zeichen)</td></tr>
                        <tr><td><code>travel_start_date</code></td><td>date</td><td>Nein</td><td>Reisebeginn (YYYY-MM-DD)</td></tr>
                        <tr><td><code>travel_end_date</code></td><td>date</td><td>Nein</td><td>Reiseende (YYYY-MM-DD)</td></tr>
                        <tr><td><code>primary_destination</code></td><td>string</td><td>Nein</td><td>Hauptreiseziel</td></tr>
                        <tr><td><code>status</code></td><td>string</td><td>Nein</td><td><code>draft</code>, <code>confirmed</code>, <code>active</code>, <code>completed</code>, <code>cancelled</code> (Standard: <code>draft</code>)</td></tr>
                        <tr><td><code>travel_type</code></td><td>string</td><td>Nein</td><td><code>business</code>, <code>leisure</code>, <code>mixed</code> (Standard: <code>leisure</code>)</td></tr>
                        <tr><td><code>agent_name</code></td><td>string</td><td>Nein</td><td>Name des Bearbeiters</td></tr>
                        <tr><td><code>notes</code></td><td>string</td><td>Nein</td><td>Notizen</td></tr>
                        <tr><td><code>currency</code></td><td>string</td><td>Nein</td><td>Währung als ISO-Code (Standard: <code>EUR</code>)</td></tr>
                        <tr><td><code>custom_field_1_label</code></td><td>string</td><td>Nein</td><td>Label für eigenes Feld 1 (max. 100 Zeichen)</td></tr>
                        <tr><td><code>custom_field_1_value</code></td><td>string</td><td>Nein</td><td>Wert für eigenes Feld 1</td></tr>
                        <tr><td><code>custom_field_2_label</code></td><td>string</td><td>Nein</td><td>Label für eigenes Feld 2</td></tr>
                        <tr><td><code>custom_field_2_value</code></td><td>string</td><td>Nein</td><td>Wert für eigenes Feld 2</td></tr>
                        <tr><td><code>custom_field_3_label</code></td><td>string</td><td>Nein</td><td>Label für eigenes Feld 3</td></tr>
                        <tr><td><code>custom_field_3_value</code></td><td>string</td><td>Nein</td><td>Wert für eigenes Feld 3</td></tr>
                        <tr><td><code>custom_field_4_label</code></td><td>string</td><td>Nein</td><td>Label für eigenes Feld 4</td></tr>
                        <tr><td><code>custom_field_4_value</code></td><td>string</td><td>Nein</td><td>Wert für eigenes Feld 4</td></tr>
                        <tr><td><code>custom_field_5_label</code></td><td>string</td><td>Nein</td><td>Label für eigenes Feld 5</td></tr>
                        <tr><td><code>custom_field_5_value</code></td><td>string</td><td>Nein</td><td>Wert für eigenes Feld 5</td></tr>
                    </tbody>
                </table>
            </div>

            <hr>

            <h4>Customer (Kundendaten)</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Feld</th><th>Typ</th><th>Pflicht</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>salutation</code></td><td>string</td><td>Nein</td><td>Anrede: <code>mr</code>, <code>mrs</code>, <code>diverse</code> (auch <code>Herr</code>, <code>Frau</code>, <code>Divers</code> wird gemappt)</td></tr>
                        <tr><td><code>title</code></td><td>string</td><td>Nein</td><td>Titel (max. 64 Zeichen)</td></tr>
                        <tr><td><code>first_name</code></td><td>string</td><td>Ja</td><td>Vorname (max. 128 Zeichen)</td></tr>
                        <tr><td><code>last_name</code></td><td>string</td><td>Ja</td><td>Nachname (max. 128 Zeichen)</td></tr>
                        <tr><td><code>email</code></td><td>string</td><td>Nein</td><td>E-Mail-Adresse</td></tr>
                        <tr><td><code>phone</code></td><td>string</td><td>Nein</td><td>Telefonnummer</td></tr>
                        <tr><td><code>mobile</code></td><td>string</td><td>Nein</td><td>Mobilnummer</td></tr>
                        <tr><td><code>street</code></td><td>string</td><td>Nein</td><td>Straße</td></tr>
                        <tr><td><code>house_number</code></td><td>string</td><td>Nein</td><td>Hausnummer</td></tr>
                        <tr><td><code>postal_code</code></td><td>string</td><td>Nein</td><td>Postleitzahl</td></tr>
                        <tr><td><code>city</code></td><td>string</td><td>Nein</td><td>Stadt</td></tr>
                        <tr><td><code>country_code</code></td><td>string</td><td>Nein</td><td>Ländercode (ISO alpha-2, z.B. <code>DE</code>)</td></tr>
                        <tr><td><code>birth_date</code></td><td>date</td><td>Nein</td><td>Geburtsdatum (YYYY-MM-DD)</td></tr>
                        <tr><td><code>nationality</code></td><td>string</td><td>Nein</td><td>Staatsangehörigkeit (ISO alpha-2)</td></tr>
                        <tr><td><code>notes</code></td><td>string</td><td>Nein</td><td>Notizen</td></tr>
                    </tbody>
                </table>
            </div>

            <hr>

            <h4>Participant (Reiseteilnehmer)</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Feld</th><th>Typ</th><th>Pflicht</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>salutation</code></td><td>string</td><td>Nein</td><td><code>mr</code>, <code>mrs</code>, <code>child</code>, <code>infant</code>, <code>diverse</code></td></tr>
                        <tr><td><code>title</code></td><td>string</td><td>Nein</td><td>Titel</td></tr>
                        <tr><td><code>first_name</code></td><td>string</td><td>Ja</td><td>Vorname</td></tr>
                        <tr><td><code>last_name</code></td><td>string</td><td>Ja</td><td>Nachname</td></tr>
                        <tr><td><code>birth_date</code></td><td>date</td><td>Nein</td><td>Geburtsdatum</td></tr>
                        <tr><td><code>nationality</code></td><td>string</td><td>Nein</td><td>Staatsangehörigkeit (ISO alpha-2)</td></tr>
                        <tr><td><code>passport_number</code></td><td>string</td><td>Nein</td><td>Reisepassnummer</td></tr>
                        <tr><td><code>passport_issue_date</code></td><td>date</td><td>Nein</td><td>Ausstellungsdatum Pass</td></tr>
                        <tr><td><code>passport_expiry_date</code></td><td>date</td><td>Nein</td><td>Ablaufdatum Pass</td></tr>
                        <tr><td><code>passport_issuing_country</code></td><td>string</td><td>Nein</td><td>Ausstellungsland Pass (ISO alpha-2)</td></tr>
                        <tr><td><code>email</code></td><td>string</td><td>Nein</td><td>E-Mail-Adresse</td></tr>
                        <tr><td><code>phone</code></td><td>string</td><td>Nein</td><td>Telefonnummer</td></tr>
                        <tr><td><code>dietary_requirements</code></td><td>string</td><td>Nein</td><td>Ernährungsanforderungen</td></tr>
                        <tr><td><code>medical_conditions</code></td><td>string</td><td>Nein</td><td>Medizinische Hinweise</td></tr>
                        <tr><td><code>notes</code></td><td>string</td><td>Nein</td><td>Notizen</td></tr>
                        <tr><td><code>is_main_contact</code></td><td>boolean</td><td>Nein</td><td>Hauptansprechpartner (Standard: <code>false</code>)</td></tr>
                        <tr><td><code>participant_type</code></td><td>string</td><td>Nein</td><td><code>adult</code>, <code>child</code>, <code>infant</code> (Standard: <code>adult</code>)</td></tr>
                    </tbody>
                </table>
            </div>

            <hr>

            <h4>Itinerary (Reiseleistung)</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Feld</th><th>Typ</th><th>Pflicht</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>booking_reference</code></td><td>string</td><td>Nein</td><td>Buchungsreferenz</td></tr>
                        <tr><td><code>itinerary_name</code></td><td>string</td><td>Nein</td><td>Name der Leistung</td></tr>
                        <tr><td><code>start_date</code></td><td>date</td><td>Nein</td><td>Startdatum</td></tr>
                        <tr><td><code>end_date</code></td><td>date</td><td>Nein</td><td>Enddatum</td></tr>
                        <tr><td><code>status</code></td><td>string</td><td>Nein</td><td><code>pending</code>, <code>confirmed</code>, <code>cancelled</code>, <code>completed</code> (Standard: <code>pending</code>)</td></tr>
                        <tr><td><code>provider_name</code></td><td>string</td><td>Nein</td><td>Anbietername</td></tr>
                        <tr><td><code>provider_reference</code></td><td>string</td><td>Nein</td><td>Anbieterreferenz</td></tr>
                        <tr><td><code>currency</code></td><td>string</td><td>Nein</td><td>Währung (Standard: <code>EUR</code>)</td></tr>
                        <tr><td><code>notes</code></td><td>string</td><td>Nein</td><td>Notizen</td></tr>
                        <tr><td><code>hotels</code></td><td>array</td><td>Nein</td><td>Hotels (siehe unten)</td></tr>
                        <tr><td><code>flights</code></td><td>array</td><td>Nein</td><td>Flüge (siehe unten)</td></tr>
                        <tr><td><code>ships</code></td><td>array</td><td>Nein</td><td>Kreuzfahrten (siehe unten)</td></tr>
                        <tr><td><code>car_rentals</code></td><td>array</td><td>Nein</td><td>Mietwagen (siehe unten)</td></tr>
                    </tbody>
                </table>
            </div>

            <hr>

            <h4>Hotel</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Feld</th><th>Typ</th><th>Pflicht</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>hotel_name</code></td><td>string</td><td>Ja</td><td>Hotelname</td></tr>
                        <tr><td><code>hotel_code</code></td><td>string</td><td>Nein</td><td>Hotel-Code</td></tr>
                        <tr><td><code>hotel_code_type</code></td><td>string</td><td>Nein</td><td>Typ des Hotel-Codes</td></tr>
                        <tr><td><code>street</code></td><td>string</td><td>Nein</td><td>Straße</td></tr>
                        <tr><td><code>postal_code</code></td><td>string</td><td>Nein</td><td>Postleitzahl</td></tr>
                        <tr><td><code>city</code></td><td>string</td><td>Nein</td><td>Stadt</td></tr>
                        <tr><td><code>country_code</code></td><td>string</td><td>Nein</td><td>Ländercode (ISO alpha-2)</td></tr>
                        <tr><td><code>lat</code></td><td>number</td><td>Nein</td><td>Breitengrad (-90 bis 90)</td></tr>
                        <tr><td><code>lng</code></td><td>number</td><td>Nein</td><td>Längengrad (-180 bis 180)</td></tr>
                        <tr><td><code>check_in_date</code></td><td>date</td><td>Ja</td><td>Check-in-Datum</td></tr>
                        <tr><td><code>check_out_date</code></td><td>date</td><td>Ja</td><td>Check-out-Datum</td></tr>
                        <tr><td><code>nights</code></td><td>integer</td><td>Nein</td><td>Anzahl Nächte</td></tr>
                        <tr><td><code>room_type</code></td><td>string</td><td>Nein</td><td>Zimmertyp</td></tr>
                        <tr><td><code>room_count</code></td><td>integer</td><td>Nein</td><td>Zimmeranzahl (Standard: 1)</td></tr>
                        <tr><td><code>board_type</code></td><td>string</td><td>Nein</td><td>Verpflegung (z.B. "All Inclusive")</td></tr>
                        <tr><td><code>booking_reference</code></td><td>string</td><td>Nein</td><td>Buchungsreferenz</td></tr>
                        <tr><td><code>total_amount</code></td><td>number</td><td>Nein</td><td>Gesamtbetrag</td></tr>
                        <tr><td><code>currency</code></td><td>string</td><td>Nein</td><td>Währung (Standard: <code>EUR</code>)</td></tr>
                        <tr><td><code>status</code></td><td>string</td><td>Nein</td><td><code>pending</code>, <code>confirmed</code>, <code>cancelled</code> (Standard: <code>pending</code>)</td></tr>
                        <tr><td><code>notes</code></td><td>string</td><td>Nein</td><td>Notizen</td></tr>
                    </tbody>
                </table>
            </div>

            <hr>

            <h4>Flight (Flug)</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Feld</th><th>Typ</th><th>Pflicht</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>booking_reference</code></td><td>string</td><td>Nein</td><td>Buchungsreferenz</td></tr>
                        <tr><td><code>service_type</code></td><td>string</td><td>Nein</td><td><code>outbound</code>, <code>return</code>, <code>multi_leg</code> (Standard: <code>outbound</code>)</td></tr>
                        <tr><td><code>airline_pnr</code></td><td>string</td><td>Nein</td><td>Airline PNR</td></tr>
                        <tr><td><code>ticket_numbers</code></td><td>array</td><td>Nein</td><td>Ticketnummern</td></tr>
                        <tr><td><code>total_amount</code></td><td>number</td><td>Nein</td><td>Gesamtbetrag</td></tr>
                        <tr><td><code>currency</code></td><td>string</td><td>Nein</td><td>Währung (Standard: <code>EUR</code>)</td></tr>
                        <tr><td><code>status</code></td><td>string</td><td>Nein</td><td><code>pending</code>, <code>ticketed</code>, <code>cancelled</code> (Standard: <code>pending</code>)</td></tr>
                        <tr><td><code>segments</code></td><td>array</td><td>Ja</td><td>Flugsegmente (mindestens 1)</td></tr>
                    </tbody>
                </table>
            </div>

            <p><strong>Flight Segment:</strong></p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Feld</th><th>Typ</th><th>Pflicht</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>segment_number</code></td><td>integer</td><td>Nein</td><td>Segmentnummer (Standard: 1)</td></tr>
                        <tr><td><code>departure_airport_code</code></td><td>string</td><td>Ja</td><td>IATA-Code Abflughafen (z.B. <code>MUC</code>) – automatisches Matching</td></tr>
                        <tr><td><code>departure_time</code></td><td>datetime</td><td>Ja</td><td>Abflugzeit</td></tr>
                        <tr><td><code>departure_terminal</code></td><td>string</td><td>Nein</td><td>Terminal</td></tr>
                        <tr><td><code>arrival_airport_code</code></td><td>string</td><td>Ja</td><td>IATA-Code Zielflughafen (z.B. <code>PMI</code>) – automatisches Matching</td></tr>
                        <tr><td><code>arrival_time</code></td><td>datetime</td><td>Ja</td><td>Ankunftszeit</td></tr>
                        <tr><td><code>arrival_terminal</code></td><td>string</td><td>Nein</td><td>Terminal</td></tr>
                        <tr><td><code>airline_code</code></td><td>string</td><td>Nein</td><td>Airline-Code (z.B. <code>LH</code>)</td></tr>
                        <tr><td><code>flight_number</code></td><td>string</td><td>Nein</td><td>Flugnummer</td></tr>
                        <tr><td><code>aircraft_type</code></td><td>string</td><td>Nein</td><td>Flugzeugtyp (z.B. <code>A320</code>)</td></tr>
                        <tr><td><code>duration_minutes</code></td><td>integer</td><td>Nein</td><td>Flugdauer in Minuten</td></tr>
                        <tr><td><code>booking_class</code></td><td>string</td><td>Nein</td><td>Buchungsklasse</td></tr>
                        <tr><td><code>cabin_class</code></td><td>string</td><td>Nein</td><td><code>economy</code>, <code>premium_economy</code>, <code>business</code>, <code>first</code> (Standard: <code>economy</code>)</td></tr>
                    </tbody>
                </table>
            </div>
            <blockquote><p><strong>Hinweis:</strong> <code>departure_country_code</code>, <code>departure_lat</code>, <code>departure_lng</code>, <code>arrival_country_code</code>, <code>arrival_lat</code>, <code>arrival_lng</code> werden automatisch aus den IATA-Codes ermittelt.</p></blockquote>

            <hr>

            <h4>Ship (Kreuzfahrt)</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Feld</th><th>Typ</th><th>Pflicht</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>ship_name</code></td><td>string</td><td>Ja</td><td>Schiffsname</td></tr>
                        <tr><td><code>cruise_line</code></td><td>string</td><td>Nein</td><td>Reederei</td></tr>
                        <tr><td><code>ship_code</code></td><td>string</td><td>Nein</td><td>Schiffs-Code</td></tr>
                        <tr><td><code>embarkation_date</code></td><td>date</td><td>Ja</td><td>Einschiffungsdatum</td></tr>
                        <tr><td><code>disembarkation_date</code></td><td>date</td><td>Ja</td><td>Ausschiffungsdatum</td></tr>
                        <tr><td><code>nights</code></td><td>integer</td><td>Nein</td><td>Anzahl Nächte</td></tr>
                        <tr><td><code>embarkation_port</code></td><td>string</td><td>Nein</td><td>Einschiffungshafen</td></tr>
                        <tr><td><code>embarkation_country_code</code></td><td>string</td><td>Nein</td><td>Ländercode Einschiffung (ISO alpha-2)</td></tr>
                        <tr><td><code>embarkation_lat</code></td><td>number</td><td>Nein</td><td>Breitengrad Einschiffung</td></tr>
                        <tr><td><code>embarkation_lng</code></td><td>number</td><td>Nein</td><td>Längengrad Einschiffung</td></tr>
                        <tr><td><code>disembarkation_port</code></td><td>string</td><td>Nein</td><td>Ausschiffungshafen</td></tr>
                        <tr><td><code>disembarkation_country_code</code></td><td>string</td><td>Nein</td><td>Ländercode Ausschiffung</td></tr>
                        <tr><td><code>disembarkation_lat</code></td><td>number</td><td>Nein</td><td>Breitengrad Ausschiffung</td></tr>
                        <tr><td><code>disembarkation_lng</code></td><td>number</td><td>Nein</td><td>Längengrad Ausschiffung</td></tr>
                        <tr><td><code>cabin_number</code></td><td>string</td><td>Nein</td><td>Kabinennummer</td></tr>
                        <tr><td><code>cabin_type</code></td><td>string</td><td>Nein</td><td>Kabinentyp</td></tr>
                        <tr><td><code>cabin_category</code></td><td>string</td><td>Nein</td><td>Kabinenkategorie</td></tr>
                        <tr><td><code>deck</code></td><td>string</td><td>Nein</td><td>Deck</td></tr>
                        <tr><td><code>booking_reference</code></td><td>string</td><td>Nein</td><td>Buchungsreferenz</td></tr>
                        <tr><td><code>total_amount</code></td><td>number</td><td>Nein</td><td>Gesamtbetrag</td></tr>
                        <tr><td><code>currency</code></td><td>string</td><td>Nein</td><td>Währung (Standard: <code>EUR</code>)</td></tr>
                        <tr><td><code>status</code></td><td>string</td><td>Nein</td><td><code>pending</code>, <code>confirmed</code>, <code>cancelled</code> (Standard: <code>pending</code>)</td></tr>
                        <tr><td><code>port_calls</code></td><td>array</td><td>Nein</td><td>Hafenstopps (siehe unten)</td></tr>
                        <tr><td><code>notes</code></td><td>string</td><td>Nein</td><td>Notizen</td></tr>
                    </tbody>
                </table>
            </div>

            <p><strong>Port Call (Hafenstopp):</strong></p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Feld</th><th>Typ</th><th>Pflicht</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>port</code></td><td>string</td><td>Nein</td><td>Hafenname</td></tr>
                        <tr><td><code>country</code></td><td>string</td><td>Nein</td><td>Ländercode (ISO alpha-2)</td></tr>
                        <tr><td><code>arrival</code></td><td>date</td><td>Nein</td><td>Ankunftsdatum</td></tr>
                        <tr><td><code>departure</code></td><td>date</td><td>Nein</td><td>Abreisedatum</td></tr>
                    </tbody>
                </table>
            </div>

            <hr>

            <h4>Car Rental (Mietwagen)</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Feld</th><th>Typ</th><th>Pflicht</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>rental_company</code></td><td>string</td><td>Nein</td><td>Mietwagenfirma</td></tr>
                        <tr><td><code>booking_reference</code></td><td>string</td><td>Nein</td><td>Buchungsreferenz</td></tr>
                        <tr><td><code>pickup_location</code></td><td>string</td><td>Ja</td><td>Abholort</td></tr>
                        <tr><td><code>pickup_country_code</code></td><td>string</td><td>Nein</td><td>Ländercode Abholung (ISO alpha-2)</td></tr>
                        <tr><td><code>pickup_lat</code></td><td>number</td><td>Nein</td><td>Breitengrad Abholung</td></tr>
                        <tr><td><code>pickup_lng</code></td><td>number</td><td>Nein</td><td>Längengrad Abholung</td></tr>
                        <tr><td><code>pickup_datetime</code></td><td>datetime</td><td>Ja</td><td>Abholdatum/-zeit</td></tr>
                        <tr><td><code>return_location</code></td><td>string</td><td>Ja</td><td>Rückgabeort</td></tr>
                        <tr><td><code>return_country_code</code></td><td>string</td><td>Nein</td><td>Ländercode Rückgabe</td></tr>
                        <tr><td><code>return_lat</code></td><td>number</td><td>Nein</td><td>Breitengrad Rückgabe</td></tr>
                        <tr><td><code>return_lng</code></td><td>number</td><td>Nein</td><td>Längengrad Rückgabe</td></tr>
                        <tr><td><code>return_datetime</code></td><td>datetime</td><td>Ja</td><td>Rückgabedatum/-zeit</td></tr>
                        <tr><td><code>vehicle_category</code></td><td>string</td><td>Nein</td><td>Fahrzeugkategorie</td></tr>
                        <tr><td><code>vehicle_type</code></td><td>string</td><td>Nein</td><td>Fahrzeugtyp</td></tr>
                        <tr><td><code>vehicle_make_model</code></td><td>string</td><td>Nein</td><td>Marke/Modell</td></tr>
                        <tr><td><code>transmission</code></td><td>string</td><td>Nein</td><td><code>manual</code>, <code>automatic</code></td></tr>
                        <tr><td><code>fuel_type</code></td><td>string</td><td>Nein</td><td><code>petrol</code>, <code>diesel</code>, <code>electric</code>, <code>hybrid</code></td></tr>
                        <tr><td><code>rental_days</code></td><td>integer</td><td>Nein</td><td>Mietdauer in Tagen</td></tr>
                        <tr><td><code>total_amount</code></td><td>number</td><td>Nein</td><td>Gesamtbetrag</td></tr>
                        <tr><td><code>currency</code></td><td>string</td><td>Nein</td><td>Währung (Standard: <code>EUR</code>)</td></tr>
                        <tr><td><code>insurance_options</code></td><td>array</td><td>Nein</td><td>Versicherungsoptionen</td></tr>
                        <tr><td><code>extras</code></td><td>array</td><td>Nein</td><td>Zusatzleistungen</td></tr>
                        <tr><td><code>status</code></td><td>string</td><td>Nein</td><td><code>pending</code>, <code>confirmed</code>, <code>picked_up</code>, <code>returned</code>, <code>cancelled</code> (Standard: <code>pending</code>)</td></tr>
                        <tr><td><code>notes</code></td><td>string</td><td>Nein</td><td>Notizen</td></tr>
                    </tbody>
                </table>
            </div>

            <hr>

            <h3>Beispiele</h3>

            <h4>Minimaler Import (nur Hotel)</h4>
            <pre><code>curl -X POST https://global-travel-monitor.eu/api/customer/folders/import \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "source": "api",
    "provider": "Test System",
    "data": {
      "folder": {
        "folder_name": "Test Reise",
        "travel_start_date": "2026-06-01",
        "travel_end_date": "2026-06-14"
      },
      "customer": {
        "first_name": "Max",
        "last_name": "Mustermann"
      },
      "participants": [
        {
          "first_name": "Max",
          "last_name": "Mustermann",
          "is_main_contact": true
        }
      ],
      "itineraries": [
        {
          "itinerary_name": "Hauptreise",
          "hotels": [
            {
              "hotel_name": "Test Hotel",
              "check_in_date": "2026-06-01",
              "check_out_date": "2026-06-14"
            }
          ]
        }
      ]
    }
  }'</code></pre>

            <h4>Vollständiger Import (Hotel + Flug)</h4>
            <pre><code>curl -X POST https://global-travel-monitor.eu/api/customer/folders/import \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "source": "api",
    "provider": "TUI Reisebüro München",
    "data": {
      "folder": {
        "folder_name": "Mallorca Sommerurlaub 2026",
        "travel_start_date": "2026-07-15",
        "travel_end_date": "2026-07-29",
        "primary_destination": "Palma, Spanien",
        "travel_type": "leisure",
        "status": "confirmed",
        "currency": "EUR",
        "custom_field_1_label": "TUI Buchungsnummer",
        "custom_field_1_value": "TUI-2026-12345"
      },
      "customer": {
        "salutation": "Frau",
        "first_name": "Anna",
        "last_name": "Müller",
        "email": "anna.mueller@example.com",
        "phone": "+49 89 12345678",
        "city": "München",
        "country_code": "DE"
      },
      "participants": [
        {
          "salutation": "Frau",
          "first_name": "Anna",
          "last_name": "Müller",
          "birth_date": "1985-03-15",
          "nationality": "DE",
          "passport_number": "C01X12345",
          "is_main_contact": true,
          "participant_type": "adult"
        }
      ],
      "itineraries": [
        {
          "itinerary_name": "Mallorca Hauptreise",
          "start_date": "2026-07-15",
          "end_date": "2026-07-29",
          "status": "confirmed",
          "booking_reference": "MAL-2026-001",
          "currency": "EUR",
          "hotels": [
            {
              "hotel_name": "Hotel Paraíso del Mar",
              "city": "Palma",
              "country_code": "ES",
              "lat": 39.5699,
              "lng": 2.6509,
              "check_in_date": "2026-07-15",
              "check_out_date": "2026-07-29",
              "nights": 14,
              "room_type": "Superior Doppelzimmer",
              "board_type": "All Inclusive",
              "booking_reference": "HTL-001",
              "total_amount": 2450.00,
              "status": "confirmed"
            }
          ],
          "flights": [
            {
              "booking_reference": "LH-PMI-001",
              "service_type": "outbound",
              "status": "ticketed",
              "segments": [
                {
                  "segment_number": 1,
                  "departure_airport_code": "MUC",
                  "departure_time": "2026-07-15 10:00:00",
                  "arrival_airport_code": "PMI",
                  "arrival_time": "2026-07-15 12:15:00",
                  "airline_code": "LH",
                  "flight_number": "1802",
                  "cabin_class": "economy"
                }
              ]
            }
          ]
        }
      ]
    }
  }'</code></pre>

            <p><strong>Response (202 Accepted):</strong></p>
            <pre><code>{
  "success": true,
  "message": "Import queued successfully",
  "log_id": "019bef38-f2bc-73fc-bdbc-228ff5a8421e"
}</code></pre>

            <hr>

            <h3>Import-Status abfragen</h3>

            <h4>Status eines einzelnen Imports</h4>
            <pre><code>GET /customer/folders/imports/{log_id}/status</code></pre>
            <p><strong>Beispiel:</strong></p>
            <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://global-travel-monitor.eu/api/customer/folders/imports/019bef38-f2bc-73fc-bdbc-228ff5a8421e/status"</code></pre>

            <p><strong>Response (200 OK):</strong></p>
            <pre><code>{
  "success": true,
  "data": {
    "id": "019bef38-f2bc-73fc-bdbc-228ff5a8421e",
    "status": "completed",
    "folder_id": "019bef39-a1b2-c3d4-e5f6-789012345678",
    "records_imported": 5,
    "records_failed": 0,
    "error_message": null,
    "started_at": "2026-06-01T10:00:01Z",
    "completed_at": "2026-06-01T10:00:03Z",
    "duration_seconds": 2
  }
}</code></pre>

            <p><strong>Mögliche Status-Werte:</strong></p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Status</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>pending</code></td><td>Import wartet auf Verarbeitung</td></tr>
                        <tr><td><code>processing</code></td><td>Import wird gerade verarbeitet</td></tr>
                        <tr><td><code>completed</code></td><td>Import erfolgreich abgeschlossen</td></tr>
                        <tr><td><code>failed</code></td><td>Import fehlgeschlagen (siehe <code>error_message</code>)</td></tr>
                    </tbody>
                </table>
            </div>

            <hr>

            <h4>Liste aller Imports</h4>
            <pre><code>GET /customer/folders/imports</code></pre>

            <p><strong>Query-Parameter:</strong></p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Parameter</th><th>Typ</th><th>Beschreibung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>per_page</code></td><td>integer</td><td>Einträge pro Seite (Standard: 15, Maximum: 100)</td></tr>
                    </tbody>
                </table>
            </div>

            <p><strong>Beispiel:</strong></p>
            <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://global-travel-monitor.eu/api/customer/folders/imports?per_page=10"</code></pre>

            <hr>

            <h3>Fehlercodes</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>HTTP-Code</th><th>Bedeutung</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>202</code></td><td>Import erfolgreich in Queue eingereiht</td></tr>
                        <tr><td><code>200</code></td><td>Statusabfrage erfolgreich</td></tr>
                        <tr><td><code>401</code></td><td>Nicht authentifiziert (Token fehlt oder ungültig)</td></tr>
                        <tr><td><code>404</code></td><td>Import-Log nicht gefunden</td></tr>
                        <tr><td><code>422</code></td><td>Validierungsfehler (ungültige Daten)</td></tr>
                        <tr><td><code>500</code></td><td>Serverfehler</td></tr>
                    </tbody>
                </table>
            </div>

            <p><strong>Beispiel Validierungsfehler (422):</strong></p>
            <pre><code>{
  "success": false,
  "errors": {
    "source": ["The source field is required."],
    "data.folder.folder_name": ["The folder name must not exceed 255 characters."]
  }
}</code></pre>

            <hr>

            <h3>Automatische Features</h3>
            <ul>
                <li><strong>Airport-Matching:</strong> IATA-Codes (z.B. <code>MUC</code>, <code>PMI</code>) werden automatisch zu vollständigen Flughafendaten aufgelöst inkl. Koordinaten und Ländercode</li>
                <li><strong>Country-Matching:</strong> Ländercodes werden automatisch validiert und zugeordnet</li>
                <li><strong>Timeline-Generierung:</strong> Aus Hotels, Flügen, Kreuzfahrten und Mietwagen wird automatisch eine Reise-Timeline erstellt</li>
                <li><strong>Geocoding:</strong> Hotel- und Standortdaten werden für die Kartendarstellung geocodiert</li>
            </ul>

            <hr>

            <h3>Support</h3>
            <p>Bei Fragen zur API wenden Sie sich an Ihren Ansprechpartner bei Passolution.</p>
        </div>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} <a href="https://passolution.de" target="_blank">Passolution GmbH</a> &middot; <a href="https://global-travel-monitor.eu">Global Travel Monitor</a></p>
    </div>
</body>
</html>
