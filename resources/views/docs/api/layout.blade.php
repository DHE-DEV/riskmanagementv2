<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Passolution API Dokumentation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --api-color: @yield('api_color', '#3b82f6');
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
            color: var(--api-color);
            border-left-color: var(--api-color);
            background: color-mix(in srgb, var(--api-color) 8%, white);
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
            color: #111827;
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
            color: #1f2937;
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
            border-left: 4px solid var(--api-color);
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
            background: #1e293b;
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
            background: rgba(34,197,94,0.2);
            color: #4ade80;
            border-color: rgba(34,197,94,0.3);
        }

        .code-block .code-label {
            display: inline-block;
            background: rgba(255,255,255,0.08);
            color: #94a3b8;
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
            background: #1e293b;
            border-radius: 8px;
            margin: 1rem 0;
            overflow: hidden;
            border-left: 4px solid #22c55e;
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
            background: var(--api-color);
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

            <a href="/docs/api" class="flex items-center gap-2 text-gray-900 hover:text-gray-600 transition font-semibold text-lg no-underline">
                <i class="fas fa-book text-blue-500"></i>
                <span>Passolution</span>
            </a>
        </div>

        <div class="flex items-center gap-3">
            <a href="/docs/api" class="hidden sm:inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition no-underline">
                <i class="fas fa-arrow-left text-xs"></i>
                Zurück zur Übersicht
            </a>
            <a href="/customer/dashboard" class="inline-flex items-center gap-1.5 text-sm bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition no-underline">
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

    {{-- ── Back to Top ── --}}
    <button id="back-to-top" class="back-to-top" title="Nach oben">
        <i class="fas fa-chevron-up"></i>
    </button>

    {{-- ── Footer ── --}}
    <footer class="border-t border-gray-200 bg-gray-50 py-8 text-center text-sm text-gray-400" style="margin-left: 280px;">
        <div class="px-6">
            &copy; {{ date('Y') }} Passolution Travel Information Platform &mdash; API Dokumentation
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

</body>
</html>
