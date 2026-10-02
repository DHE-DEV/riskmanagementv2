@php
    $firstName = \Illuminate\Support\Str::before(auth('web')->user()->name, ' ');
    $hour = now()->hour;
    $greeting = match (true) {
        $hour < 11 => 'Guten Morgen',
        $hour < 18 => 'Guten Tag',
        default => 'Guten Abend',
    };

    $areas = [
        [
            'title' => 'Aufgaben',
            'text' => 'Eigene Aufgaben und Aufgaben für andere – mit Rubrik, Termin, Erinnerung, Notizen und Verlauf.',
            'icon' => 'clipboard-document-check',
            'href' => route('adminv2.tasks.index'),
        ],
        [
            'title' => 'Ereignisse – Übersicht',
            'text' => 'Was gerade live ist, was auf Freigabe wartet und was in den nächsten Tagen endet.',
            'icon' => 'chart-bar',
            'href' => route('adminv2.events.overview'),
        ],
        [
            'title' => 'Passolution Ereignisse',
            'text' => 'Ereignisse erfassen, übersetzen, mit Standorten versehen und veröffentlichen.',
            'icon' => 'map-pin',
            'href' => route('adminv2.events.index'),
        ],
        [
            'title' => 'Stammdaten',
            'text' => 'Kontinente, Länder, Regionen, Städte, Flughäfen, Airlines und Länderinformationen – im Aufbau.',
            'icon' => 'circle-stack',
            'href' => route('adminv2.master-data.section', 'continents'),
        ],
    ];
@endphp

<div class="flex flex-col gap-8">
    {{-- Begruessung --}}
    <section class="relative overflow-hidden rounded-3xl bg-[#0b3250] px-6 py-8 sm:px-10 sm:py-10">
        <div class="absolute -end-24 -top-28 size-96 rounded-full bg-[#c8dc3c]/25 blur-3xl"></div>
        <div class="absolute -bottom-40 start-1/3 size-[28rem] rounded-full bg-sky-400/15 blur-3xl"></div>

        <div class="relative flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <div class="flex items-center gap-4">
                    <span class="flex shrink-0 items-center rounded-xl bg-white px-3 py-2.5 shadow-sm">
                        <img src="{{ asset('logo.png') }}" alt="" class="h-8 w-auto" />
                    </span>
                    <span class="text-sm font-medium text-white/70">Passolution Travel Information Platform</span>
                </div>

                {{-- Gleiche Groesse wie die Seitenueberschriften (flux:heading size="xl"). --}}
                <h1 class="mt-6 text-2xl font-semibold tracking-tight text-white">
                    {{ $greeting }}, {{ $firstName }}.
                </h1>
                <p class="mt-2 text-sm text-white/70">
                    Willkommen im Admin-Bereich. Hier verwalten wir alles rund um die Passolution Travel Information Platform.
                </p>
            </div>

            <div class="flex shrink-0 flex-col items-start gap-3 lg:items-end">
                <p class="text-sm text-white/60">{{ now()->translatedFormat('l, j. F Y') }}</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('adminv2.events.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#c8dc3c] px-4 py-2.5 text-sm font-semibold text-[#0b3250] transition hover:bg-[#d6e85a]">
                        <flux:icon.plus variant="mini" /> Neues Ereignis
                    </a>
                    <a href="{{ route('adminv2.events.overview') }}" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/20">
                        Zu den Ereignissen <flux:icon.arrow-right variant="mini" />
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Bereiche --}}
    <section>
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Bereiche</h2>

        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3 min-[1900px]:grid-cols-4">
            @foreach ($areas as $area)
                <a
                    href="{{ $area['href'] }}"
                    class="group flex flex-col rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs transition hover:-translate-y-0.5 hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700"
                >
                    <span class="flex size-11 items-center justify-center rounded-xl bg-[#0b3250]/5 text-[#0b3250] dark:bg-[#c8dc3c]/10 dark:text-[#c8dc3c]">
                        <flux:icon :icon="$area['icon']" />
                    </span>
                    <span class="mt-5 text-lg font-semibold text-zinc-900 dark:text-white">{{ $area['title'] }}</span>
                    <span class="mt-1.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $area['text'] }}</span>
                    <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-medium text-zinc-900 dark:text-white">
                        Öffnen
                        <flux:icon.arrow-right variant="micro" class="transition group-hover:translate-x-0.5" />
                    </span>
                </a>
            @endforeach

            <a
                href="{{ url('/admin') }}"
                class="group flex flex-col rounded-2xl border border-dashed border-zinc-300 p-6 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-600"
            >
                <span class="flex size-11 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <flux:icon.squares-plus />
                </span>
                <span class="mt-5 text-lg font-semibold text-zinc-900 dark:text-white">Weitere Bereiche folgen</span>
                <span class="mt-1.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    Kunden, Stammdaten und Einstellungen ziehen nach und nach hierher um. Bis dahin liegen sie im bisherigen Admin.
                </span>
                <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-medium text-zinc-900 dark:text-white">
                    Zum bisherigen Admin
                    <flux:icon.arrow-up-right variant="micro" />
                </span>
            </a>
        </div>
    </section>
</div>
