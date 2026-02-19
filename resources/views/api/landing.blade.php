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
                    <a href="/docs/event-api-guide.md" class="btn btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        Anleitung
                    </a>
                </div>
            </div>

            {{-- GTM API --}}
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
                    <a href="/docs/gtm-api-guide.md" class="btn btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        Anleitung
                    </a>
                </div>
            </div>

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
                    <a href="/docs/folder-import-api-guide.md" class="btn btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        Anleitung
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
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} <a href="https://passolution.de" target="_blank">Passolution GmbH</a> &middot; <a href="https://global-travel-monitor.eu">Global Travel Monitor</a></p>
    </div>
</body>
</html>
