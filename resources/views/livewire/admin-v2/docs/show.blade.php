<div class="flex flex-col gap-6">
    {{-- Kopf: Titel, Kurzbeschreibung, Stand der Datei --}}
    <div class="overflow-hidden rounded-2xl bg-[#002742] p-6 text-white shadow-sm sm:p-8">
        <a href="{{ route('adminv2.docs.index') }}" wire:navigate class="text-sm text-white/70 hover:text-white">← Doku</a>
        <div class="mt-3 flex items-start gap-4">
            <span class="hidden size-12 shrink-0 items-center justify-center rounded-xl bg-[#CEE741] text-[#002742] sm:flex">
                <flux:icon :name="$meta['icon']" class="size-6" />
            </span>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ $meta['title'] }}</h1>
                <p class="mt-2 max-w-3xl text-white/80">{{ $meta['description'] }}</p>
                @if ($rendered['updated_at'])
                    <p class="mt-3 text-xs text-white/60">Stand {{ \Illuminate\Support\Carbon::createFromTimestamp($rendered['updated_at'])->timezone(config('app.timezone'))->format('d.m.Y, H:i') }} · Quelle docs/admin/{{ $meta['file'] }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-6">
        {{-- Inhaltsverzeichnis: Hauptpunkte fortlaufend nummeriert (wie die Ueberschriften im Text), auf-/zuklappbar; der Zustand bleibt gemerkt. --}}
        @php
            $number = 0;
            $toc = [];
            foreach ($rendered['toc'] as $item) {
                if ($item['level'] === 2) {
                    $toc[] = $item + ['number' => ++$number, 'children' => []];
                } elseif ($item['level'] === 3 && $toc !== []) {
                    $toc[array_key_last($toc)]['children'][] = $item;
                }
            }
        @endphp
        <aside>
            <nav class="doc-toc" x-data="{ open: $persist(true).as('adminv2-doc-toc') }" aria-label="Inhaltsverzeichnis">
                <button type="button" class="doc-toc-head" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()">
                    <span>Inhaltsverzeichnis</span>
                    <svg class="doc-toc-chevron" x-bind:class="{ 'is-open': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.06l3.71-3.83a.75.75 0 1 1 1.08 1.04l-4.25 4.39a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
                </button>
                <ol class="doc-toc-list" x-show="open" x-collapse>
                    @foreach ($toc as $item)
                        <li>
                            <a href="#{{ $item['id'] }}" class="doc-toc-main">
                                <span class="doc-toc-number">{{ $item['number'] }}.</span>
                                <span class="doc-toc-text">{{ $item['text'] }}</span>
                            </a>
                            @if ($item['children'] !== [])
                                <ol class="doc-toc-sub">
                                    @foreach ($item['children'] as $child)
                                        <li><a href="#{{ $child['id'] }}">{{ $child['text'] }}</a></li>
                                    @endforeach
                                </ol>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        </aside>

        <article class="adminv2-doc min-w-0 rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs sm:p-8 dark:border-zinc-800 dark:bg-zinc-950">
            {!! $rendered['html'] !!}
        </article>
    </div>

    {{-- Eigene Typografie: Admin v2 hat kein Typography-Plugin, und Klassen aus dem Markdown kennt Tailwind nicht. --}}
    <style>
        .adminv2-doc { color: #3f3f46; line-height: 1.7; font-size: 0.95rem; }
        .dark .adminv2-doc { color: #d4d4d8; }
        .adminv2-doc > :first-child { margin-top: 0; }
        .adminv2-doc h1 { display: none; }
        /* Hauptpunkte fortlaufend nummeriert – passend zum Inhaltsverzeichnis */
        .adminv2-doc { counter-reset: doc-h2; }
        .adminv2-doc h2 { counter-increment: doc-h2; }
        .adminv2-doc h2::before { content: counter(doc-h2) "."; display: inline-block; min-width: 2rem; margin-right: 0.35rem; color: #94a3b8; font-variant-numeric: tabular-nums; }
        .dark .adminv2-doc h2::before { color: #71717a; }
        .adminv2-doc h2 { margin: 2.5rem 0 0.75rem; padding-top: 1.5rem; border-top: 1px solid #e4e4e7; font-size: 1.35rem; font-weight: 600; color: #002742; scroll-margin-top: 1.5rem; }
        .adminv2-doc h2:first-of-type { border-top: 0; padding-top: 0; }
        .adminv2-doc h3 { margin: 1.75rem 0 0.5rem; font-size: 1.05rem; font-weight: 600; color: #18181b; scroll-margin-top: 1.5rem; }
        .adminv2-doc h4 { margin: 1.25rem 0 0.4rem; font-weight: 600; color: #18181b; }
        .dark .adminv2-doc h2 { color: #CEE741; border-color: #27272a; }
        .dark .adminv2-doc h3, .dark .adminv2-doc h4 { color: #fafafa; }
        .adminv2-doc p, .adminv2-doc ul, .adminv2-doc ol, .adminv2-doc table, .adminv2-doc pre { margin: 0.75rem 0; }
        .adminv2-doc ul { list-style: disc; padding-left: 1.4rem; }
        .adminv2-doc ol { list-style: decimal; padding-left: 1.4rem; }
        .adminv2-doc li { margin: 0.25rem 0; }
        .adminv2-doc a { color: #0369a1; text-decoration: underline; text-underline-offset: 2px; }
        .dark .adminv2-doc a { color: #7dd3fc; }
        .adminv2-doc strong { color: #18181b; font-weight: 600; }
        .dark .adminv2-doc strong { color: #fafafa; }
        .adminv2-doc code { font-size: 0.85em; background: #f4f4f5; border-radius: 0.3rem; padding: 0.1rem 0.35rem; color: #be185d; }
        .dark .adminv2-doc code { background: #27272a; color: #f9a8d4; }
        .adminv2-doc pre { background: #0f172a; color: #e2e8f0; border-radius: 0.75rem; padding: 1rem 1.1rem; overflow-x: auto; font-size: 0.82rem; line-height: 1.6; }
        .adminv2-doc pre code { background: transparent; color: inherit; padding: 0; font-size: inherit; }
        .adminv2-doc .table-responsive { overflow-x: auto; }
        .adminv2-doc table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .adminv2-doc th { text-align: left; font-weight: 600; background: #f4f4f5; color: #18181b; }
        .adminv2-doc th, .adminv2-doc td { border: 1px solid #e4e4e7; padding: 0.5rem 0.7rem; vertical-align: top; }
        .dark .adminv2-doc th { background: #18181b; color: #fafafa; }
        .dark .adminv2-doc th, .dark .adminv2-doc td { border-color: #27272a; }
        .adminv2-doc blockquote { margin: 1rem 0; border-left: 4px solid #CEE741; background: #f7fbe6; padding: 0.75rem 1rem; border-radius: 0 0.5rem 0.5rem 0; }
        .dark .adminv2-doc blockquote { background: #1c2210; }
        .adminv2-doc blockquote p { margin: 0.25rem 0; }

        /* Inhaltsverzeichnis */
        .doc-toc { border: 1px solid #e4e4e7; border-radius: 1rem; background: #fff; font-size: 0.875rem; overflow: hidden; }
        .dark .doc-toc { border-color: #27272a; background: #09090b; }
        .doc-toc-head { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: 0.5rem; padding: 0.85rem 1rem; font-weight: 600; color: #002742; cursor: pointer; }
        .dark .doc-toc-head { color: #CEE741; }
        .doc-toc-head:hover { background: #f4f4f5; }
        .dark .doc-toc-head:hover { background: #18181b; }
        .doc-toc-chevron { width: 1.1rem; height: 1.1rem; transition: transform 0.2s; transform: rotate(-90deg); }
        .doc-toc-chevron.is-open { transform: rotate(0deg); }
        .doc-toc-list { margin: 0; padding: 0.5rem 0.75rem 1rem; columns: 1; list-style: none; border-top: 1px solid #f4f4f5; }
        .dark .doc-toc-list { border-color: #18181b; }
        .doc-toc-list > li { margin-top: 0.15rem; max-width: 46rem; }
        .doc-toc a { display: block; border-radius: 0.4rem; color: #3f3f46; text-decoration: none; }
        .dark .doc-toc a { color: #d4d4d8; }
        .doc-toc a:hover { background: #f4f4f5; color: #002742; }
        .dark .doc-toc a:hover { background: #18181b; color: #fff; }
        .doc-toc-main { display: flex !important; align-items: baseline; gap: 0.4rem; padding: 0.35rem 0.5rem; font-weight: 500; }
        .doc-toc-main .doc-toc-text { flex: 1; display: flex; align-items: baseline; gap: 0.4rem; }
        .doc-toc-main .doc-toc-text::after { content: ""; flex: 1; border-bottom: 1px dotted #d4d4d8; transform: translateY(-0.2rem); }
        .dark .doc-toc-main .doc-toc-text::after { border-color: #3f3f46; }
        .doc-toc-number { min-width: 1.6rem; color: #94a3b8; font-variant-numeric: tabular-nums; }
        .doc-toc-sub { margin: 0 0 0.25rem 2.1rem; padding: 0; list-style: none; border-left: 1px solid #e4e4e7; }
        .dark .doc-toc-sub { border-color: #27272a; }
        .doc-toc-sub a { padding: 0.2rem 0.6rem; font-size: 0.8rem; color: #71717a; }

        /* Bausteine aus dem Markdown (HTML) */
        .adminv2-doc .doc-flow { display: grid; gap: 0.75rem; margin: 1.25rem 0; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); counter-reset: flow; }
        .adminv2-doc .doc-flow > div { position: relative; border: 1px solid #e4e4e7; border-radius: 1rem; padding: 1rem 1rem 0.9rem; background: linear-gradient(180deg, #ffffff, #f8fafc); }
        .dark .adminv2-doc .doc-flow > div { border-color: #27272a; background: linear-gradient(180deg, #09090b, #18181b); }
        .adminv2-doc .doc-flow > div::before { counter-increment: flow; content: counter(flow); display: inline-flex; width: 1.6rem; height: 1.6rem; align-items: center; justify-content: center; border-radius: 999px; background: #002742; color: #CEE741; font-weight: 700; font-size: 0.8rem; margin-bottom: 0.5rem; }
        .adminv2-doc .doc-flow strong { display: block; margin-bottom: 0.25rem; }
        .adminv2-doc .doc-flow span { display: block; font-size: 0.85rem; color: #52525b; line-height: 1.5; }
        .dark .adminv2-doc .doc-flow span { color: #a1a1aa; }
        .adminv2-doc .doc-cards { display: grid; gap: 0.75rem; margin: 1rem 0; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); }
        .adminv2-doc .doc-cards > div { border-radius: 1rem; padding: 1rem; background: #002742; color: #e2e8f0; }
        .adminv2-doc .doc-cards > div b { display: block; color: #CEE741; font-size: 1.4rem; line-height: 1.2; }
        .adminv2-doc .doc-cards > div span { font-size: 0.85rem; }
        .adminv2-doc .doc-note, .adminv2-doc .doc-warn { margin: 1rem 0; border-radius: 0.75rem; padding: 0.8rem 1rem; font-size: 0.9rem; }
        .adminv2-doc .doc-note { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; }
        .adminv2-doc .doc-warn { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; }
        .dark .adminv2-doc .doc-note { background: #172554; border-color: #1e3a8a; color: #dbeafe; }
        .dark .adminv2-doc .doc-warn { background: #431407; border-color: #9a3412; color: #ffedd5; }
    </style>
</div>
