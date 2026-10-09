<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passolution Travel Information Platform - REST API</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Passolution-Farben wie auf der Plattform: Navy als Marke, Lime als Akzent, Hellblau auf dunklem Grund. */
        :root { --navy: #002742; --navy-dark: #021a2b; --navy-mid: #043451; --lime: #cee741; --sky: #91daf2; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Archivo', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
        }
        .topbar .brand { display: flex; align-items: center; gap: 28px; color: #1f2937; text-decoration: none; }
        .topbar .brand img { height: 32px; width: auto; margin-left: -5px; }
        .topbar .brand span { font-size: 1.25rem; font-weight: 300; letter-spacing: 0.025em; }
        .topbar .platform-link {
            background: var(--navy); color: #fff; text-decoration: none; font-size: 0.875rem; font-weight: 500;
            padding: 0.5rem 1rem; border-radius: 8px;
        }
        .topbar .platform-link:hover { background: var(--navy-mid); }
        @media (max-width: 640px) { .topbar .brand span { display: none; } }
        .header {
            background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy) 60%, var(--navy-mid) 100%);
            color: #fff;
            padding: 3.5rem 1.5rem;
            text-align: center;
        }
        .header h1 {
            font-size: 2.25rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            margin-bottom: 0.5rem;
        }
        .header h1 em { font-style: normal; color: var(--lime); }
        .header p {
            color: var(--sky);
            font-size: 1.1rem;
        }
        .header .base-url {
            display: inline-block;
            margin-top: 1.25rem;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(206,231,65,0.35);
            padding: 0.5rem 1.25rem;
            border-radius: 6px;
            font-family: 'SF Mono', SFMono-Regular, Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 0.95rem;
            color: var(--lime);
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
            transition: border-color 0.15s;
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
            background: var(--navy);
            color: #fff;
        }
        .btn-primary:hover { background: var(--navy-mid); }
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
        .doc-section h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .doc-section h2 {
            color: var(--navy);
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
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #1e293b;
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
    <div class="topbar">
        <a href="/docs/api" class="brand">
            <img src="/logo.png" alt="Passolution">
            <span>Passolution Travel Information Platform</span>
        </a>
        <a href="https://platform.passolution.de/customer/dashboard" class="platform-link">Zur Plattform</a>
    </div>
    <div class="header">
        <h1>REST <em>API</em></h1>
        <p>Dokumentation der Passolution Travel Information Platform</p>
        <div class="base-url">{{ $apiBase }}</div>
    </div>

    <div class="container">
        <div class="auth-box">
            <h2>Authentifizierung</h2>
            <code>Authorization: Bearer {API_TOKEN}</code>
            <p>Den Token erhalten Sie von Ihrem Ansprechpartner bei Passolution.</p>
        </div>

        <div class="apis">
            {{-- Events API (Read-Only) --}}
            <div class="api-card">
                <span class="badge badge-auth">Bearer Token (Kunde)</span>
                <h3>Events API</h3>
                <p class="description">Für Kunden: Read-only Zugriff auf alle aktiven Events aller Anbieter. Mit dem <code>source</code>-Filter können Events nach Herkunft gefiltert werden (z.B. nur Events von einem bestimmten Partner). Benötigt einen Kunden-Token.</p>
                <ul class="endpoints">
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/events</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/events/{uuid}</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/events/nearby</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/events/countries</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/continents</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/countries</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/countries/{code}</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/countries/{code}/boundary</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/boundaries</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/airports</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/airports/{code}</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/airlines</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/airlines/{code}</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/exchange-rates</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/regions</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/event-categories</span></li>
                </ul>
                <div class="downloads">
                    <a href="#events-api-guide" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        Anleitung
                    </a>
                    <a href="{{ $downloadBase }}/gtm-api-openapi.yaml" class="btn btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        OpenAPI Spec
                    </a>
                </div>
            </div>

            {{-- Custom Event API (Partner) --}}
            <div class="api-card">
                <span class="badge badge-auth">Bearer Token (API-Partner)</span>
                <h3>Custom Event API</h3>
                <p class="description">Für API-Partner: Eigene Sicherheits-Events erstellen, aktualisieren und löschen. Jeder Partner verwaltet nur seine eigenen Events. Benötigt einen API-Client-Token.</p>
                <ul class="endpoints">
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/custom/events</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/custom/events/nearby</span></li>
                    <li><span class="method method-post">POST</span> <span class="endpoint-path">/v1/custom/events</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/custom/events/{uuid}</span></li>
                    <li><span class="method method-put">PUT</span> <span class="endpoint-path">/v1/custom/events/{uuid}</span></li>
                    <li><span class="method method-delete">DEL</span> <span class="endpoint-path">/v1/custom/events/{uuid}</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/custom/event-categories</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/custom/countries</span></li>
                </ul>
                <div class="downloads">
                    <a href="#event-api-guide" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        Anleitung
                    </a>
                    <a href="{{ $downloadBase }}/event-api-openapi.yaml" class="btn btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        OpenAPI Spec
                    </a>
                </div>
            </div>

            {{-- Folder Import API --}}
            <div class="api-card">
                <span class="badge badge-auth">Bearer Token</span>
                <h3>Folder Import API</h3>
                <p class="description">Import von Reisedaten mit Hotels, Flügen, Kreuzfahrten und Mietwagen. Queue-basierte Verarbeitung.</p>
                <ul class="endpoints">
                    <li><span class="method method-post">POST</span> <span class="endpoint-path">/v1/folders/import</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/folders</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/folders/{id}</span></li>
                </ul>
                <div class="downloads">
                    <a href="#folder-import-api-guide" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        Anleitung
                    </a>
                    <a href="{{ $downloadBase }}/folder-import-api-openapi.yaml" class="btn btn-outline">
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
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">https://platform.passolution.de/feed/events</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">https://platform.passolution.de/feed/countries</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">https://platform.passolution.de/feed/events/meta.json</span></li>
                </ul>
                <div class="downloads">
                    <a href="#feed-api-guide" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        Anleitung
                    </a>
                    <a href="{{ $downloadBase }}/feed-api-openapi.yaml" class="btn btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        OpenAPI Spec
                    </a>
                </div>
            </div>

            {{-- Plugin Domain Management API --}}
            <div class="api-card">
                <span class="badge badge-auth">Plugin-Key</span>
                <h3>Plugin Domain API</h3>
                <p class="description">REST API zur programmatischen Verwaltung erlaubter Domains für das Plugin. Unterstützt Einzel- und Massenoperationen (bis 1.000 Domains pro Aufruf).</p>
                <ul class="endpoints">
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/plugin/gtm/domains</span></li>
                    <li><span class="method method-post">POST</span> <span class="endpoint-path">/v1/plugin/gtm/domains</span></li>
                    <li><span class="method method-post">POST</span> <span class="endpoint-path">/v1/plugin/gtm/domains/bulk</span></li>
                    <li><span class="method method-get">GET</span> <span class="endpoint-path">/v1/plugin/gtm/domains/{uuid}</span></li>
                    <li><span class="method method-put">PUT</span> <span class="endpoint-path">/v1/plugin/gtm/domains/{uuid}</span></li>
                    <li><span class="method method-delete">DEL</span> <span class="endpoint-path">/v1/plugin/gtm/domains/{uuid}</span></li>
                    <li><span class="method method-delete">DEL</span> <span class="endpoint-path">/v1/plugin/gtm/domains/bulk</span></li>
                </ul>
                <div class="downloads">
                    <a href="#plugin-domain-api-guide" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        Anleitung
                    </a>
                    <a href="{{ $downloadBase }}/plugin-domain-api-openapi.yaml" class="btn btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        OpenAPI Spec
                    </a>
                </div>
            </div>
        </div>

        {{-- Anleitungen aus docs/*.md, gerendert durch App\Services\ApiDocRenderer --}}
        @foreach ($sections as $section)
            <div class="doc-section" id="{{ $section['anchor'] }}">
                {!! $section['html'] !!}
            </div>
        @endforeach
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} <a href="https://passolution.de" target="_blank">Passolution GmbH</a>
            &middot; <a href="https://www.passolution.de/impressum/" target="_blank" rel="noopener noreferrer">Impressum</a>
            &middot; <a href="https://www.passolution.de/datenschutz/" target="_blank" rel="noopener noreferrer">Datenschutz</a>
            &middot; <a href="https://www.passolution.de/agb/" target="_blank" rel="noopener noreferrer">AGB</a>
            &middot; <a href="https://global-travel-monitor.eu">Global Travel Monitor</a></p>
    </div>
</body>
</html>
