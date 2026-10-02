@php
    use App\Models\CustomEvent;
    use App\Support\AdminV2\EventState;

    $user = auth('web')->user();
    $pendingReview = EventState::PendingReview->apply(CustomEvent::query())->count();
    // Offene Aufgaben, die gerade beim angemeldeten Benutzer liegen.
    // Offene KI-Vorschlaege fuer Ereignisse.
    $openAiSuggestions = \App\Models\AiEventSuggestion::query()->open()->count();
    $myOpenTasks = $user ? \App\Models\AdminTask::query()->open()->handledBy($user->id)->count() : 0;
@endphp
<!DOCTYPE html>
<html lang="de">
    <head>
        @include('components.layouts.adminv2.head')
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-900">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('adminv2.dashboard') }}" class="flex items-center gap-3 px-2 py-1">
                <img src="{{ asset('logo.png') }}" alt="" class="h-7 w-auto" />
                <span class="flex flex-col leading-tight">
                    <span class="text-sm font-semibold tracking-tight text-zinc-900 dark:text-white">Passolution</span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Travel Information Platform</span>
                </span>
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.item icon="squares-2x2" :href="route('adminv2.dashboard')" :current="request()->routeIs('adminv2.dashboard')">
                    Dashboard
                </flux:navlist.item>

                <flux:navlist.item
                    icon="clipboard-document-check"
                    :href="route('adminv2.tasks.index')"
                    :current="request()->routeIs('adminv2.tasks.*')"
                    :badge="$myOpenTasks ?: null"
                >
                    Aufgaben
                </flux:navlist.item>

                <flux:navlist.group heading="Ereignisse" class="mt-4">
                    <flux:navlist.item icon="chart-bar" :href="route('adminv2.events.overview')" :current="request()->routeIs('adminv2.events.overview')">
                        Übersicht
                    </flux:navlist.item>
                    <flux:navlist.item
                        icon="map-pin"
                        :href="route('adminv2.events.index')"
                        :current="request()->routeIs('adminv2.events.index', 'adminv2.events.create', 'adminv2.events.edit')"
                        :badge="$pendingReview ?: null"
                        badge-color="amber"
                    >
                        Passolution Ereignisse
                    </flux:navlist.item>
                    <flux:navlist.item
                        icon="sparkles"
                        :href="route('adminv2.events.ai-results')"
                        :current="request()->routeIs('adminv2.events.ai-results')"
                        :badge="$openAiSuggestions ?: null"
                    >
                        KI Suchergebnisse
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group heading="Stammdaten" class="mt-4">
                    @foreach (\App\Support\AdminV2\MasterData::sections() as $sectionKey => $sectionDefinition)
                        <flux:navlist.item
                            :icon="$sectionDefinition['icon']"
                            :href="\App\Support\AdminV2\MasterData::url($sectionKey)"
                            :current="\App\Support\AdminV2\MasterData::isCurrent($sectionKey)"
                        >
                            {{ $sectionDefinition['label'] }}
                        </flux:navlist.item>
                    @endforeach
                </flux:navlist.group>

                <flux:navlist.group heading="System" class="mt-4">
                    <flux:navlist.item icon="sparkles" :href="route('adminv2.system.ai')" :current="request()->routeIs('adminv2.system.ai', 'adminv2.system.ai.*')">
                        KI
                    </flux:navlist.item>
                    <flux:navlist.item icon="arrow-path" :href="route('adminv2.system.recurring-tasks.index')" :current="request()->routeIs('adminv2.system.recurring-tasks.*')">
                        Wiederkehrende Aufgaben
                    </flux:navlist.item>
                    <flux:navlist.item icon="user-group" :href="route('adminv2.system.teams')" :current="request()->routeIs('adminv2.system.teams')">
                        Teams
                    </flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="arrow-uturn-left" href="{{ url('/admin') }}">
                    Bisheriger Admin
                </flux:navlist.item>
                <flux:navlist.item icon="globe-alt" href="{{ url('/') }}" target="_blank">
                    Zur Plattform
                </flux:navlist.item>
            </flux:navlist>

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:profile :name="$user?->name" :initials="\Illuminate\Support\Str::of($user?->name)->explode(' ')->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('')" icon-trailing="chevron-up-down" />

                <flux:menu class="w-56">
                    <div class="px-2 py-1.5 text-sm">
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $user?->name }}</div>
                        <div class="truncate text-xs text-zinc-500">{{ $user?->email }}</div>
                    </div>
                    <flux:menu.separator />
                    <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">Hell</flux:menu.item>
                    <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">Dunkel</flux:menu.item>
                    <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">Wie das System</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('adminv2.logout') }}">
                        @csrf
                        <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle" class="w-full">Abmelden</flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header class="border-b border-zinc-200 bg-white lg:hidden dark:border-zinc-800 dark:bg-zinc-950">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <span class="ms-2 truncate text-sm font-semibold text-zinc-900 dark:text-white">Passolution Travel Information Platform</span>
            <flux:spacer />
            <form method="POST" action="{{ route('adminv2.logout') }}">
                @csrf
                <flux:button type="submit" variant="ghost" size="sm" icon="arrow-right-start-on-rectangle">Abmelden</flux:button>
            </form>
        </flux:header>

        <flux:main class="!p-0">
            {{-- Bewusst ohne Maximalbreite: der Inhalt nutzt auf breiten Bildschirmen den ganzen Platz. --}}
            <div class="w-full px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
                {{ $slot }}
            </div>
        </flux:main>

        <x-adminv2.toast />

        @fluxScripts
        @stack('adminv2-scripts')
    </body>
</html>
