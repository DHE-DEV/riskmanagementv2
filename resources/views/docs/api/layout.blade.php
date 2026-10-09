<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Passolution API Dokumentation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Passolution-Farben wie auf der Plattform: Navy als Marke, Lime als Akzent. */
        :root {
            --api-color: @yield('api_color', '#002742');
            --navy: #002742;
            --navy-dark: #021a2b;
            --lime: #cee741;
            --sky: #91daf2;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Archivo', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: 280px;
            position: fixed;
            top: 64px;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            background: #fff;
            border-right: 1px solid #e5e7eb;
            z-index: 30;
            transition: transform 0.25s ease;
        }

        .sidebar a {
            display: block;
            padding: 6px 20px;
            font-size: 0.85rem;
            color: #4b5563;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.15s ease;
        }

        .sidebar a:hover {
            color: #111827;
            background: #f3f4f6;
        }

        .sidebar a.active {
            color: var(--navy);
            border-left-color: var(--lime);
            background: color-mix(in srgb, var(--lime) 18%, white);
            font-weight: 600;
        }

        .sidebar .sidebar-heading {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            padding: 16px 20px 4px;
            border-left: none;
        }

        .sidebar .sidebar-heading:hover {
            background: transparent;
            color: #9ca3af;
        }

        @media (max-width: 1023px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
                box-shadow: 4px 0 24px rgba(0,0,0,0.12);
            }
        }

        /* ── Main Content Area ── */
        .main-content {
            margin-left: 280px;
            padding: 40px 48px 80px;
            max-width: 960px;
        }

        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0;
                padding: 24px 16px 64px;
            }
        }

        /* ── Prose-like content styles ── */
        .prose h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--navy);
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .prose h1:first-child {
            margin-top: 0;
        }

        .prose h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--navy);
            margin: 2.5rem 0 0.75rem;
            padding-bottom: 0.35rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .prose h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #374151;
            margin: 1.75rem 0 0.5rem;
        }

        .prose h4 {
            font-size: 1.05rem;
            font-weight: 600;
            color: #4b5563;
            margin: 1.25rem 0 0.5rem;
        }

        .prose p {
            color: #374151;
            line-height: 1.75;
            margin: 0.75rem 0;
        }

        .prose ul, .prose ol {
            margin: 0.75rem 0;
            padding-left: 1.5rem;
            color: #374151;
        }

        .prose li {
            margin: 0.35rem 0;
            line-height: 1.65;
        }

        .prose ul li {
            list-style-type: disc;
        }

        .prose ol li {
            list-style-type: decimal;
        }

        .prose a {
            color: var(--api-color);
            text-decoration: underline;
        }

        .prose a:hover {
            opacity: 0.8;
        }

        .prose strong {
            font-weight: 600;
            color: #111827;
        }

        .prose hr {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 2rem 0;
        }

        .prose blockquote {
            border-left: 4px solid var(--lime);
            background: #f9fafb;
            padding: 12px 16px;
            margin: 1rem 0;
            border-radius: 0 8px 8px 0;
            color: #4b5563;
        }

        /* ── Inline code ── */
        .prose code:not(.code-block code):not(.response-block code) {
            background: #f3f4f6;
            color: #dc2626;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', 'Consolas', monospace;
            font-size: 0.85em;
            font-weight: 500;
        }

        /* ── Code Block ── */
        .code-block {
            position: relative;
            background: var(--navy-dark);
            border-radius: 8px;
            margin: 1rem 0;
            overflow: hidden;
        }

        .code-block pre {
            padding: 16px 20px;
            overflow-x: auto;
            margin: 0;
        }

        .code-block code {
            font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', 'Consolas', monospace;
            font-size: 0.82rem;
            line-height: 1.6;
            color: #e2e8f0;
        }

        .code-block .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            color: #94a3b8;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .code-block .copy-btn:hover {
            background: rgba(255,255,255,0.2);
            color: #e2e8f0;
        }

        .code-block .copy-btn.copied {
            background: rgba(206,231,65,0.2);
            color: var(--lime);
            border-color: rgba(206,231,65,0.35);
        }

        .code-block .code-label {
            display: inline-block;
            background: rgba(206,231,65,0.14);
            color: var(--lime);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 4px 12px;
            border-radius: 0 0 6px 0;
            font-family: sans-serif;
        }

        /* ── Response Block (alias for code-block with JSON styling) ── */
        .response-block {
            position: relative;
            background: var(--navy-dark);
            border-radius: 8px;
            margin: 1rem 0;
            overflow: hidden;
            border-left: 4px solid var(--lime);
        }

        .response-block pre {
            padding: 16px 20px;
            overflow-x: auto;
            margin: 0;
        }

        .response-block code {
            font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', 'Consolas', monospace;
            font-size: 0.82rem;
            line-height: 1.6;
            color: #e2e8f0;
        }

        .response-block .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            color: #94a3b8;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .response-block .copy-btn:hover {
            background: rgba(255,255,255,0.2);
            color: #e2e8f0;
        }

        .response-block .copy-btn.copied {
            background: rgba(34,197,94,0.2);
            color: #4ade80;
            border-color: rgba(34,197,94,0.3);
        }

        .response-block .response-label {
            display: inline-block;
            background: rgba(34,197,94,0.15);
            color: #4ade80;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 4px 12px;
            border-radius: 0 0 6px 0;
            font-family: sans-serif;
        }

        /* ── HTTP Method Badges ── */
        .method-get {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            font-weight: 700;
            font-size: 0.75rem;
            padding: 2px 10px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.03em;
        }

        .method-post {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            font-weight: 700;
            font-size: 0.75rem;
            padding: 2px 10px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.03em;
        }

        .method-put {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            font-weight: 700;
            font-size: 0.75rem;
            padding: 2px 10px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.03em;
        }

        .method-delete {
            display: inline-block;
            background: #fee2e2;
            color: #991b1b;
            font-weight: 700;
            font-size: 0.75rem;
            padding: 2px 10px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.03em;
        }

        /* ── Endpoint Block ── */
        .endpoint-block {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin: 1rem 0;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
        }

        .endpoint-block .method {
            flex-shrink: 0;
        }

        .endpoint-block .path {
            color: #1e293b;
            font-weight: 500;
            word-break: break-all;
        }

        /* ── Field / Parameter Table ── */
        .field-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            font-size: 0.875rem;
        }

        .field-table thead th {
            background: #f8fafc;
            color: #374151;
            font-weight: 600;
            text-align: left;
            padding: 10px 14px;
            border-bottom: 2px solid #e5e7eb;
            white-space: nowrap;
        }

        .field-table tbody td {
            padding: 10px 14px;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
            vertical-align: top;
        }

        .field-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .field-table tbody tr:hover {
            background: #f3f4f6;
        }

        .field-table code {
            background: #f3f4f6;
            color: #dc2626;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 0.8rem;
        }

        /* Responsive table wrapper */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 1rem 0;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .table-responsive .field-table {
            margin: 0;
        }

        .docs-footer { margin-left: 280px; }
        @media (max-width: 1023px) { .docs-footer { margin-left: 0; } }
        .code-block .code-caption {
            display: inline-block;
            color: #cbd5e1;
            font-size: 0.78rem;
            font-weight: 500;
            padding: 5px 12px;
            font-family: 'Archivo', sans-serif;
        }
        /* ── Code-Aktionen: Testen + Kopieren ── */
        .code-block .code-actions, .response-block .code-actions {
            position: absolute;
            top: 8px;
            right: 8px;
            display: flex;
            gap: 6px;
        }
        .code-block .code-actions .copy-btn, .response-block .code-actions .copy-btn {
            position: static;
        }
        .code-block .try-btn {
            background: var(--lime);
            border: 1px solid var(--lime);
            color: var(--navy);
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }
        .code-block .try-btn:hover { background: #dff26a; }
        /* ── Testbereich rechts: volle Hoehe unter der Navigation ── */
        .test-panel {
            position: fixed;
            top: 64px;
            right: 0;
            bottom: 0;
            width: 440px;
            max-width: 100vw;
            background: #fff;
            border-left: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            z-index: 35;
            transform: translateX(100%);
            transition: transform 0.25s ease;
            box-shadow: -4px 0 24px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        body.panel-open .test-panel { transform: translateX(0); }
        @media (min-width: 1280px) {
            body.panel-open .main-content { margin-right: 440px; max-width: none; }
            body.panel-open .docs-footer { margin-right: 440px; }
        }
        .test-panel .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            background: var(--navy);
            color: #fff;
        }
        .test-panel .panel-head h2 { font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .test-panel .panel-head h2 i { color: var(--lime); }
        .test-panel .panel-head button { color: #cbd5e1; background: none; border: none; cursor: pointer; font-size: 1rem; }
        .test-panel .panel-head button:hover { color: #fff; }
        .test-panel .panel-form { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; }
        .test-panel label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; }
        .test-panel input, .test-panel select, .test-panel textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 6px 8px;
            font-size: 0.8rem;
            font-family: 'JetBrains Mono', 'Fira Code', Consolas, monospace;
            color: #111827;
            background: #fff;
        }
        .test-panel input:focus, .test-panel select:focus, .test-panel textarea:focus { outline: 2px solid var(--lime); outline-offset: -1px; border-color: var(--lime); }
        .test-panel textarea { resize: vertical; min-height: 54px; }
        .test-panel input { min-width: 0; }
        .test-panel .row { display: flex; gap: 6px; }
        .test-panel .row input { flex: 1; }
        .test-panel .row select { width: 96px; flex-shrink: 0; font-weight: 700; }
        .test-panel .token-row { display: flex; gap: 6px; align-items: center; }
        .test-panel .token-row input { flex: 1; }
        .test-panel .token-row button { flex-shrink: 0; }
        .test-panel .token-row button { border: 1px solid #d1d5db; background: #f9fafb; border-radius: 6px; padding: 6px 9px; cursor: pointer; color: #6b7280; }
        .test-panel .hint { font-size: 0.7rem; color: #9ca3af; }
        .test-panel .actions { display: flex; gap: 8px; align-items: center; margin-top: 2px; }
        .test-panel .run-btn {
            background: var(--navy);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .test-panel .run-btn i { color: var(--lime); }
        .test-panel .run-btn:hover { background: #043451; }
        .test-panel .run-btn:disabled { opacity: 0.6; cursor: wait; }
        .test-panel .reset-btn { background: none; border: 1px solid #d1d5db; color: #6b7280; border-radius: 6px; padding: 8px 12px; font-size: 0.8rem; cursor: pointer; }
        .test-panel .panel-result { flex: 1; min-height: 0; display: flex; flex-direction: column; }
        .test-panel .result-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            font-size: 0.75rem;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
            flex-wrap: wrap;
        }
        .test-panel .status { font-weight: 700; padding: 2px 8px; border-radius: 4px; background: #e5e7eb; color: #374151; }
        .test-panel .status.ok { background: #dcfce7; color: #166534; }
        .test-panel .status.warn { background: #fef3c7; color: #92400e; }
        .test-panel .status.err { background: #fee2e2; color: #991b1b; }
        .test-panel .result-body {
            flex: 1;
            min-height: 0;
            overflow: auto;
            background: var(--navy-dark);
            color: #e2e8f0;
            margin: 0;
            padding: 14px 16px;
            font-family: 'JetBrains Mono', 'Fira Code', Consolas, monospace;
            font-size: 0.78rem;
            line-height: 1.55;
            white-space: pre;
        }
        .test-panel .result-body.empty { color: #64748b; white-space: normal; font-family: 'Archivo', sans-serif; font-size: 0.85rem; }
        .test-panel .result-headers { font-size: 0.7rem; color: #6b7280; padding: 6px 16px; border-top: 1px solid #e5e7eb; background: #f9fafb; white-space: pre-wrap; max-height: 90px; overflow: auto; font-family: 'JetBrains Mono', monospace; }
        .test-panel .result-headers:empty { display: none; }
        .test-toggle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.875rem;
            border: 1px solid var(--lime);
            background: color-mix(in srgb, var(--lime) 25%, white);
            color: var(--navy);
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .test-toggle:hover { background: color-mix(in srgb, var(--lime) 45%, white); }
        .code-block.highlight { outline: 2px solid var(--lime); }
        @media (min-width: 1280px) { body.panel-open .back-to-top { right: 464px; } }
        /* ── Sidebar overlay on mobile ── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.3);
            z-index: 25;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* ── Scrollbar in sidebar ── */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 2px;
        }

        /* ── Back to top ── */
        .back-to-top {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--navy);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 40;
            border: none;
        }

        .back-to-top.visible {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-white text-gray-800 antialiased">

    {{-- ── Top Navigation Bar ── --}}
    <nav class="fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-40 flex items-center justify-between px-4 lg:px-6">
        <div class="flex items-center gap-3">
            {{-- Mobile hamburger --}}
            <button id="sidebar-toggle" class="lg:hidden p-2 -ml-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                <i class="fas fa-bars text-lg"></i>
            </button>

            <a href="/docs/api" class="flex items-center gap-4 lg:gap-7 text-gray-800 no-underline">
                <img src="/logo.png" alt="Passolution" class="h-8 w-auto" style="margin-left:-5px">
                <span class="text-xl font-light tracking-wide hidden md:inline">Passolution Travel Information Platform</span>
                <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-700 px-2.5 py-0.5 text-xs font-semibold tracking-wide">API</span>
            </a>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" id="test-toggle" class="test-toggle" title="Testbereich ein-/ausblenden">
                <i class="fas fa-flask"></i>
                <span class="hidden sm:inline">Testen</span>
            </button>
            <a href="/docs/api" class="hidden sm:inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition no-underline">
                <i class="fas fa-arrow-left text-xs"></i>
                Zurück zur Übersicht
            </a>
            <a href="/customer/dashboard" class="inline-flex items-center gap-1.5 text-sm text-white px-4 py-2 rounded-lg transition no-underline" style="background: var(--navy);">
                <i class="fas fa-external-link-alt text-xs"></i>
                Zur Plattform
            </a>
        </div>
    </nav>

    {{-- ── Sidebar Overlay (mobile) ── --}}
    <div id="sidebar-overlay" class="sidebar-overlay"></div>

    {{-- ── Sidebar ── --}}
    <aside id="sidebar" class="sidebar">
        <div class="pt-4 pb-6">
            @yield('sidebar')
        </div>
    </aside>

    {{-- ── Main Content ── --}}
    <main class="main-content pt-16">
        <div class="prose">
            @yield('content')
        </div>
    </main>

    {{-- ── Testbereich: Anfrage bearbeiten, ausfuehren, Antwort darunter ── --}}
    <aside id="test-panel" class="test-panel" aria-label="API testen">
        <div class="panel-head">
            <h2><i class="fas fa-flask"></i> API testen</h2>
            <button type="button" id="test-close" title="Schließen"><i class="fas fa-times"></i></button>
        </div>
        <form class="panel-form" id="test-form" autocomplete="off">
            <div>
                <label for="test-token">API-Token</label>
                <div class="token-row">
                    <input type="password" id="test-token" placeholder="Bearer-Token einfügen">
                    <button type="button" id="test-token-toggle" title="Token anzeigen"><i class="fas fa-eye"></i></button>
                    <button type="button" id="test-token-clear" title="Token löschen und aus dem Browser entfernen"><i class="fas fa-trash"></i></button>
                </div>
                <div class="hint" id="test-token-hint">Wird für alle Anfragen verwendet und nur in diesem Browser gespeichert.</div>
            </div>
            <div>
                <label for="test-url">Anfrage</label>
                <div class="row">
                    <select id="test-method">
                        <option>GET</option><option>POST</option><option>PUT</option><option>PATCH</option><option>DELETE</option>
                    </select>
                    <input type="text" id="test-url" placeholder="{{ request()->getSchemeAndHttpHost() }}/api/v1/events?per_page=5">
                </div>
            </div>
            <div>
                <label for="test-headers">Header (eine Zeile je Header)</label>
                <textarea id="test-headers" rows="2">Accept: application/json</textarea>
            </div>
            <div id="test-body-wrap" hidden>
                <label for="test-body">Body (JSON)</label>
                <textarea id="test-body" rows="4"></textarea>
            </div>
            <div class="actions">
                <button type="submit" class="run-btn" id="test-run"><i class="fas fa-play"></i> Ausführen</button>
                <button type="button" class="reset-btn" id="test-reset">Zurücksetzen</button>
                <span class="hint" id="test-note"></span>
            </div>
        </form>
        <div class="panel-result">
            <div class="result-head">
                <span>Antwort</span>
                <span class="status" id="test-status">–</span>
                <span id="test-meta"></span>
            </div>
            <pre class="result-body empty" id="test-result">Ein Beispiel links über „Testen“ laden oder eine Anfrage eingeben und ausführen. Die Antwort erscheint hier.</pre>
            <div class="result-headers" id="test-result-headers"></div>
        </div>
    </aside>

    {{-- ── Back to Top ── --}}
    <button id="back-to-top" class="back-to-top" title="Nach oben">
        <i class="fas fa-chevron-up"></i>
    </button>

    {{-- ── Footer ── --}}
    <footer class="border-t border-gray-200 bg-gray-50 py-6 text-sm text-gray-500 docs-footer">
        <div class="px-6 flex flex-wrap items-center gap-x-6 gap-y-2">
            <span>&copy; {{ date('Y') }} Passolution GmbH</span>
            <a href="https://www.passolution.de/impressum/" target="_blank" rel="noopener noreferrer" class="hover:text-gray-900 no-underline">Impressum</a>
            <a href="https://www.passolution.de/datenschutz/" target="_blank" rel="noopener noreferrer" class="hover:text-gray-900 no-underline">Datenschutz</a>
            <a href="https://www.passolution.de/agb/" target="_blank" rel="noopener noreferrer" class="hover:text-gray-900 no-underline">AGB</a>
            <span class="ml-auto text-gray-400">API-Dokumentation</span>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ── Mobile Sidebar Toggle ──
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebarOverlay = document.getElementById('sidebar-overlay');

            function openSidebar() {
                sidebar.classList.add('open');
                sidebarOverlay.classList.add('active');
            }

            function closeSidebar() {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('active');
            }

            sidebarToggle.addEventListener('click', function () {
                if (sidebar.classList.contains('open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            sidebarOverlay.addEventListener('click', closeSidebar);

            // Close sidebar on link click (mobile)
            sidebar.querySelectorAll('a:not(.sidebar-heading)').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.innerWidth < 1024) {
                        closeSidebar();
                    }
                });
            });

            // ── Smooth Scroll for Sidebar Links ──
            sidebar.querySelectorAll('a[href^="#"]').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    const targetId = this.getAttribute('href').slice(1);
                    const target = document.getElementById(targetId);
                    if (target) {
                        e.preventDefault();
                        const offset = 80; // navbar height + padding
                        const top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                        window.scrollTo({ top: top, behavior: 'smooth' });

                        // Update URL without jumping
                        history.pushState(null, '', '#' + targetId);
                    }
                });
            });

            // ── Active Section Tracking (IntersectionObserver) ──
            const sidebarLinks = sidebar.querySelectorAll('a[href^="#"]');
            const sectionIds = [];

            sidebarLinks.forEach(function (link) {
                const id = link.getAttribute('href').slice(1);
                if (id && document.getElementById(id)) {
                    sectionIds.push(id);
                }
            });

            if (sectionIds.length > 0) {
                const observerOptions = {
                    rootMargin: '-80px 0px -60% 0px',
                    threshold: 0
                };

                let currentActive = null;

                const observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            const id = entry.target.id;
                            if (currentActive !== id) {
                                currentActive = id;
                                sidebarLinks.forEach(function (link) {
                                    link.classList.remove('active');
                                    if (link.getAttribute('href') === '#' + id) {
                                        link.classList.add('active');
                                    }
                                });
                            }
                        }
                    });
                }, observerOptions);

                sectionIds.forEach(function (id) {
                    const el = document.getElementById(id);
                    if (el) observer.observe(el);
                });
            }

            // ── Copy to Clipboard for Code Blocks ──
            document.querySelectorAll('.code-block .copy-btn, .response-block .copy-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const block = this.closest('.code-block, .response-block');
                    const code = block.querySelector('code');
                    if (!code) return;

                    const text = code.textContent;
                    navigator.clipboard.writeText(text).then(function () {
                        btn.classList.add('copied');
                        const originalHTML = btn.innerHTML;
                        btn.innerHTML = '<i class="fas fa-check"></i> Kopiert';
                        setTimeout(function () {
                            btn.classList.remove('copied');
                            btn.innerHTML = originalHTML;
                        }, 2000);
                    });
                });
            });

            // ── Back to Top Button ──
            const backToTop = document.getElementById('back-to-top');

            window.addEventListener('scroll', function () {
                if (window.pageYOffset > 400) {
                    backToTop.classList.add('visible');
                } else {
                    backToTop.classList.remove('visible');
                }
            });

            backToTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    </script>

    <script>
        // ── Testbereich: Anfrage aus Beispiel laden, ausfuehren, Antwort anzeigen ──
        (function () {
            var origin = window.location.origin;
            var panel = document.getElementById('test-panel');
            var tokenInput = document.getElementById('test-token');
            var methodSelect = document.getElementById('test-method');
            var urlInput = document.getElementById('test-url');
            var headersInput = document.getElementById('test-headers');
            var bodyWrap = document.getElementById('test-body-wrap');
            var bodyInput = document.getElementById('test-body');
            var runBtn = document.getElementById('test-run');
            var statusEl = document.getElementById('test-status');
            var metaEl = document.getElementById('test-meta');
            var resultEl = document.getElementById('test-result');
            var resultHeadersEl = document.getElementById('test-result-headers');
            var noteEl = document.getElementById('test-note');

            function store(key, value) { try { localStorage.setItem(key, value); } catch (e) {} }
            function load(key) { try { return localStorage.getItem(key); } catch (e) { return null; } }

            // Panel standardmaessig auf breiten Bildschirmen offen, Zustand wird gemerkt.
            function setOpen(open) {
                document.body.classList.toggle('panel-open', open);
                store('api-docs-panel', open ? '1' : '0');
            }
            var remembered = load('api-docs-panel');
            setOpen(remembered === null ? window.innerWidth >= 1400 : remembered === '1');
            document.getElementById('test-toggle').addEventListener('click', function () { setOpen(!document.body.classList.contains('panel-open')); });
            document.getElementById('test-close').addEventListener('click', function () { setOpen(false); });

            // Token im Browser merken (localStorage), damit er auf allen Doku-Seiten und beim naechsten Besuch da ist.
            var tokenHint = document.getElementById('test-token-hint');
            function syncTokenHint() {
                tokenHint.textContent = tokenInput.value.trim()
                    ? 'Gespeichert – wird als Bearer-Token für alle Anfragen verwendet. Bleibt nur in diesem Browser.'
                    : 'Wird für alle Anfragen verwendet und nur in diesem Browser gespeichert.';
            }
            tokenInput.value = load('api-docs-token') || '';
            syncTokenHint();
            tokenInput.addEventListener('input', function () { store('api-docs-token', tokenInput.value.trim()); syncTokenHint(); });
            document.getElementById('test-token-toggle').addEventListener('click', function () {
                tokenInput.type = tokenInput.type === 'password' ? 'text' : 'password';
            });
            document.getElementById('test-token-clear').addEventListener('click', function () {
                tokenInput.value = '';
                try { localStorage.removeItem('api-docs-token'); } catch (e) {}
                syncTokenHint();
                tokenInput.focus();
            });

            function syncBody() { bodyWrap.hidden = methodSelect.value === 'GET' || methodSelect.value === 'DELETE'; }
            methodSelect.addEventListener('change', syncBody);

            // Bekannte Hosts auf diese Installation umbiegen, damit der Browser ohne CORS-Probleme anfragen kann.
            function localize(url) {
                return url
                    .replace(/^https?:\/\/api\.global-travel-monitor\.(de|eu)(?=\/|$)/, origin + '/api')
                    .replace(/^https?:\/\/global-travel-monitor\.(eu|de)\/api(?=\/|$)/, origin + '/api')
                    .replace(/^https?:\/\/global-travel-monitor\.(eu|de)\/feed(?=\/|$)/, origin + '/feed')
                    .replace(/^https?:\/\/platform\.passolution\.de(?=\/|$)/, origin);
            }

            // Einfache Shell-Zerlegung: Anfuehrungszeichen und Zeilenfortsetzungen beachten.
            function tokenize(text) {
                var tokens = [], current = '', quote = null, i, ch;
                text = text.replace(/\\\r?\n/g, ' ');
                for (i = 0; i < text.length; i++) {
                    ch = text[i];
                    if (quote) {
                        if (ch === quote) { quote = null; } else { current += ch; }
                    } else if (ch === '"' || ch === "'") {
                        quote = ch;
                    } else if (/\s/.test(ch)) {
                        if (current !== '') { tokens.push(current); current = ''; }
                    } else {
                        current += ch;
                    }
                }
                if (current !== '') tokens.push(current);
                return tokens;
            }

            function parseRequest(text) {
                var request = { method: 'GET', url: '', headers: [], body: '' };
                var bare = text.trim().match(/^(GET|POST|PUT|PATCH|DELETE)\s+(\S+)$/);
                if (bare) {
                    request.method = bare[1];
                    request.url = origin + (bare[2].indexOf('/feed') === 0 ? '' : '/api') + bare[2];
                    return request;
                }
                var tokens = tokenize(text), i, t, explicitMethod = false;
                for (i = 1; i < tokens.length; i++) {
                    t = tokens[i];
                    if (t === '-X' || t === '--request') { request.method = (tokens[++i] || 'GET').toUpperCase(); explicitMethod = true; }
                    else if (t === '-H' || t === '--header') { request.headers.push(tokens[++i] || ''); }
                    else if (t === '-d' || t === '--data' || t === '--data-raw' || t === '--data-binary' || t === '--json') {
                        request.body = tokens[++i] || '';
                        if (t === '--json') request.headers.push('Content-Type: application/json');
                        if (!explicitMethod) request.method = 'POST';
                    }
                    else if (t === '-G' || t === '--get') { request.method = 'GET'; }
                    else if (/^https?:\/\//.test(t)) { request.url = t; }
                }
                return request;
            }

            function loadIntoPanel(text) {
                var request = parseRequest(text);
                methodSelect.value = request.method;
                urlInput.value = localize(request.url);
                var headers = request.headers.filter(function (h) { return h.trim() !== ''; });
                if (!headers.some(function (h) { return /^accept\s*:/i.test(h); }) && urlInput.value.indexOf('/feed/') === -1) {
                    headers.push('Accept: application/json');
                }
                headersInput.value = headers.join('\n');
                bodyInput.value = request.body;
                syncBody();
                setOpen(true);
                noteEl.textContent = request.url !== urlInput.value ? 'Host auf diese Installation umgestellt.' : '';
                urlInput.focus();
            }

            document.querySelectorAll('.try-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.code-block.highlight').forEach(function (b) { b.classList.remove('highlight'); });
                    btn.closest('.code-block').classList.add('highlight');
                    loadIntoPanel(btn.dataset.request);
                });
            });

            document.getElementById('test-reset').addEventListener('click', function () {
                methodSelect.value = 'GET'; urlInput.value = ''; headersInput.value = 'Accept: application/json'; bodyInput.value = '';
                syncBody(); showEmpty();
            });

            function showEmpty() {
                statusEl.textContent = '–'; statusEl.className = 'status'; metaEl.textContent = '';
                resultEl.className = 'result-body empty';
                resultEl.textContent = 'Ein Beispiel links über „Testen“ laden oder eine Anfrage eingeben und ausführen. Die Antwort erscheint hier.';
                resultHeadersEl.textContent = '';
            }

            function fillPlaceholders(value) {
                var token = tokenInput.value.trim();
                return token ? value.replace(/\{(TOKEN|API_TOKEN|API_KEY)\}/g, token) : value;
            }

            document.getElementById('test-form').addEventListener('submit', function (e) {
                e.preventDefault();
                var url = fillPlaceholders(urlInput.value.trim());
                if (!url) { urlInput.focus(); return; }
                if (/\{[A-Za-z_]+\}/.test(url)) {
                    noteEl.textContent = 'Platzhalter in der URL ersetzen: ' + url.match(/\{[A-Za-z_]+\}/)[0];
                    urlInput.focus();
                    return;
                }
                var headers = {};
                headersInput.value.split('\n').forEach(function (line) {
                    var idx = line.indexOf(':');
                    if (idx > 0) headers[line.slice(0, idx).trim()] = fillPlaceholders(line.slice(idx + 1).trim());
                });
                if (tokenInput.value.trim() && !Object.keys(headers).some(function (k) { return k.toLowerCase() === 'authorization'; })) {
                    headers['Authorization'] = 'Bearer ' + tokenInput.value.trim();
                }
                var options = { method: methodSelect.value, headers: headers };
                if (!bodyWrap.hidden && bodyInput.value.trim() !== '') {
                    options.body = bodyInput.value;
                    if (!Object.keys(headers).some(function (k) { return k.toLowerCase() === 'content-type'; })) headers['Content-Type'] = 'application/json';
                }

                runBtn.disabled = true;
                noteEl.textContent = '';
                statusEl.textContent = '…'; statusEl.className = 'status'; metaEl.textContent = 'läuft';
                var started = performance.now();

                fetch(url, options).then(function (response) {
                    return response.text().then(function (text) { return { response: response, text: text }; });
                }).then(function (r) {
                    var ms = Math.round(performance.now() - started);
                    var size = new Blob([r.text]).size;
                    statusEl.textContent = r.response.status + ' ' + r.response.statusText;
                    statusEl.className = 'status ' + (r.response.ok ? 'ok' : (r.response.status >= 500 ? 'err' : 'warn'));
                    metaEl.textContent = ms + ' ms · ' + (size > 1024 ? (size / 1024).toFixed(1) + ' KB' : size + ' B');
                    var body = r.text;
                    try { body = JSON.stringify(JSON.parse(r.text), null, 2); } catch (err) {}
                    resultEl.className = 'result-body';
                    resultEl.textContent = body;
                    var shown = ['content-type', 'x-ratelimit-limit', 'x-ratelimit-remaining', 'retry-after', 'cache-control'];
                    resultHeadersEl.textContent = shown.filter(function (h) { return r.response.headers.get(h); })
                        .map(function (h) { return h + ': ' + r.response.headers.get(h); }).join('\n');
                }).catch(function (err) {
                    statusEl.textContent = 'Fehler'; statusEl.className = 'status err'; metaEl.textContent = '';
                    resultEl.className = 'result-body';
                    resultEl.textContent = 'Anfrage fehlgeschlagen: ' + err.message + '\n\nMögliche Ursachen: Host nicht erreichbar, CORS bei fremder Domain oder ungültige URL.';
                    resultHeadersEl.textContent = '';
                }).finally(function () { runBtn.disabled = false; });
            });
        })();
    </script>
</body>
</html>
