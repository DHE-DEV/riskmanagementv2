@php
    use App\Support\AdminV2\EventState;

    $tiles = [
        ['state' => EventState::Live, 'label' => 'Live', 'hint' => 'werden gerade ausgeliefert', 'dot' => 'bg-green-500'],
        ['state' => EventState::Scheduled, 'label' => 'Geplant', 'hint' => 'veröffentlicht, Beginn steht noch aus', 'dot' => 'bg-sky-500'],
        ['state' => EventState::Draft, 'label' => 'Entwürfe', 'hint' => 'noch nicht veröffentlicht', 'dot' => 'bg-indigo-500'],
        ['state' => EventState::PendingReview, 'label' => 'Prüfung ausstehend', 'hint' => 'warten auf Freigabe', 'dot' => 'bg-amber-500'],
    ];
@endphp

<div class="flex flex-col gap-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Ereignisse</flux:heading>
            <flux:subheading>Stand am {{ now()->translatedFormat('l, j. F Y') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('adminv2.events.create')">Neues Ereignis</flux:button>
    </div>

    {{-- Kennzahlen: jede Kachel fuehrt in die passend gefilterte Liste --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($tiles as $tile)
            <a
                href="{{ route('adminv2.events.index', ['tab' => $tile['state']->value]) }}"
                class="group rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700"
            >
                <div class="flex items-center gap-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                    <span class="size-2 rounded-full {{ $tile['dot'] }}"></span>
                    {{ $tile['label'] }}
                </div>
                <div class="mt-3 text-4xl font-semibold tracking-tight text-zinc-900 tabular-nums dark:text-white">
                    {{ number_format($this->counts[$tile['state']->value], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $tile['hint'] }}</div>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3 min-[1900px]:grid-cols-4">
        <div class="flex flex-col gap-6 xl:col-span-2 min-[1900px]:col-span-3">
            {{-- Handlungsbedarf --}}
            @if ($this->pendingReview->isNotEmpty())
                <x-adminv2.card heading="Prüfung ausstehend" description="Von außen eingereichte Ereignisse, die auf Freigabe warten." flush>
                    <x-slot:actions>
                        <flux:button size="sm" variant="ghost" :href="route('adminv2.events.index', ['tab' => 'pending'])">Alle anzeigen</flux:button>
                    </x-slot:actions>

                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($this->pendingReview as $event)
                            <li>
                                <a href="{{ route('adminv2.events.edit', $event) }}" class="flex items-center justify-between gap-4 px-5 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $event->getTitle('de') }}</div>
                                        <div class="truncate text-xs text-zinc-500">{{ $event->countries->map->getName('de')->unique()->implode(', ') ?: 'Kein Standort' }}</div>
                                    </div>
                                    <x-adminv2.priority-badge :priority="$event->priority" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-adminv2.card>
            @endif

            @if ($this->liveWithoutLocationCount > 0)
                <x-adminv2.card flush>
                    <div class="flex items-start gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                        <flux:icon.exclamation-triangle variant="mini" class="mt-0.5 text-amber-500" />
                        <div>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                                {{ $this->liveWithoutLocationCount }} {{ $this->liveWithoutLocationCount === 1 ? 'Ereignis ist' : 'Ereignisse sind' }} live, aber ohne Standort
                            </h2>
                            <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                                Ohne Standort erscheinen sie nicht auf der Karte und lösen keine Travel-Alert-Benachrichtigung aus.
                            </p>
                        </div>
                    </div>

                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($this->liveWithoutLocation as $event)
                            <li>
                                <a href="{{ route('adminv2.events.edit', $event) }}" class="flex items-center justify-between gap-4 px-5 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                                    <span class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $event->getTitle('de') }}</span>
                                    <span class="shrink-0 text-xs text-zinc-500 tabular-nums">seit {{ $event->start_date?->format('d.m.Y') }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-adminv2.card>
            @endif

            <x-adminv2.card heading="Zuletzt bearbeitet" flush>
                <x-slot:actions>
                    <flux:button size="sm" variant="ghost" :href="route('adminv2.events.index', ['tab' => 'all', 'sort' => 'updated_at'])">Alle anzeigen</flux:button>
                </x-slot:actions>

                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->recentlyChanged as $event)
                        <li>
                            <a href="{{ route('adminv2.events.edit', $event) }}" class="flex items-center gap-4 px-5 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $event->getTitle('de') }}</div>
                                    <div class="truncate text-xs text-zinc-500">
                                        {{ $event->countries->map->getName('de')->unique()->implode(', ') ?: 'Kein Standort' }}
                                    </div>
                                </div>
                                <x-adminv2.state-badge :state="EventState::of($event)" />
                                <span class="hidden w-28 shrink-0 text-end text-xs text-zinc-500 sm:block">{{ $event->updated_at?->diffForHumans() }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-adminv2.card>
        </div>

        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Enden in den nächsten 7 Tagen" flush>
                @forelse ($this->endingSoon as $event)
                    <a href="{{ route('adminv2.events.edit', $event) }}" class="flex items-center justify-between gap-4 border-b border-zinc-100 px-5 py-3 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">
                        <span class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $event->getTitle('de') }}</span>
                        <span class="shrink-0 text-xs text-zinc-500 tabular-nums">{{ $event->end_date->format('d.m.') }}</span>
                    </a>
                @empty
                    <p class="px-5 py-6 text-sm text-zinc-500">In den nächsten sieben Tagen läuft kein Ereignis aus.</p>
                @endforelse
            </x-adminv2.card>

            <x-adminv2.card heading="Aufrufe" description="Klicks auf Ereignisse in Karte und Liste.">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-zinc-500">Heute</dt>
                        <dd class="mt-1 text-2xl font-semibold text-zinc-900 tabular-nums dark:text-white">{{ number_format($this->clicks['today'], 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Diese Woche</dt>
                        <dd class="mt-1 text-2xl font-semibold text-zinc-900 tabular-nums dark:text-white">{{ number_format($this->clicks['week'], 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </x-adminv2.card>

            <x-adminv2.card heading="Automatische Läufe" description="Letzter und nächster Lauf der protokollierten Hintergrundaufgaben." flush>
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->automations as $automation)
                        <li class="px-5 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $automation['label'] }}</span>
                                <span class="text-xs text-zinc-500">alle {{ $automation['interval'] }} Min.</span>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-500">
                                @if ($automation['last'])
                                    <span class="inline-flex items-center gap-1.5">
                                        <span @class([
                                            'size-1.5 rounded-full',
                                            'bg-green-500' => $automation['last']->status === 'completed',
                                            'bg-red-500' => $automation['last']->status === 'failed',
                                            'bg-sky-500' => $automation['last']->status === 'running',
                                        ])></span>
                                        Zuletzt {{ $automation['last']->started_at->format('d.m.Y H:i') }}
                                    </span>
                                @else
                                    <span>Noch kein Lauf protokolliert</span>
                                @endif
                                <span>Nächster {{ $automation['next']->format('H:i') }} Uhr</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-adminv2.card>
        </div>
    </div>
</div>
