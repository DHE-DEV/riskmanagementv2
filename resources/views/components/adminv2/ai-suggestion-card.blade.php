{{--
    Karte eines KI-Vorschlags fuer ein Ereignis – in der Ereignisliste und
    unter "KI Suchergebnisse". Die Aktionen rufen createDraftFromSuggestion,
    dismissSuggestion bzw. restoreSuggestion der umgebenden Livewire-Komponente auf.

    - typeNames / typeIcons: Event-Typen nach Code
    - countryNames: Laendernamen nach ISO-Code
--}}
@props(['suggestion', 'typeNames' => [], 'typeIcons' => [], 'countryNames' => []])

@php
    $status = $suggestion->status;
    $priorityEdges = ['high' => 'border-s-red-500', 'medium' => 'border-s-amber-500', 'low' => 'border-s-sky-500', 'info' => 'border-s-zinc-300 dark:border-s-zinc-600'];
@endphp

<article {{ $attributes->class(['flex flex-col rounded-2xl border border-s-4 border-dashed border-zinc-300 bg-white p-4 shadow-xs dark:border-zinc-700 dark:bg-zinc-950', $priorityEdges[$suggestion->priority] ?? $priorityEdges['info'], 'opacity-75' => $status !== \App\Models\AiEventSuggestion::STATUS_NEW]) }}>
    <h3 class="text-base font-semibold leading-snug text-zinc-900 dark:text-white">{{ $suggestion->title }}</h3>

    <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1.5">
        <span class="inline-flex items-center gap-1 rounded-md bg-[var(--color-accent)]/10 px-2 py-0.5 text-xs font-medium text-[var(--color-accent)]">
            <flux:icon.sparkles variant="micro" /> KI-Vorschlag
        </span>
        @if ($status === \App\Models\AiEventSuggestion::STATUS_CONVERTED)
            <flux:badge size="sm" color="green" inset="top bottom">Als Entwurf angelegt</flux:badge>
        @elseif ($status === \App\Models\AiEventSuggestion::STATUS_DISMISSED)
            <flux:badge size="sm" color="zinc" inset="top bottom">Verworfen</flux:badge>
        @endif
        <x-adminv2.priority-badge :priority="$suggestion->priority" />
        @foreach ($suggestion->event_type_codes ?? [] as $code)
            @if (isset($typeNames[$code]))
                <span class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                    <i class="fas {{ $typeIcons[$code] ?: 'fa-map-marker' }} text-[0.65rem] opacity-60" aria-hidden="true"></i>
                    {{ $typeNames[$code] }}
                </span>
            @endif
        @endforeach
    </div>

    <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
        <div class="flex items-center gap-2">
            <dt class="shrink-0"><flux:icon.calendar variant="mini" class="text-zinc-400" /><span class="sr-only">Zeitraum</span></dt>
            <dd class="tabular-nums whitespace-nowrap">{{ $suggestion->start_date?->format('d.m.Y') ?? 'Beginn unklar' }} – {{ $suggestion->end_date?->format('d.m.Y') ?? 'offen' }}</dd>
        </div>
        <div class="flex min-w-0 items-start gap-2">
            <dt class="mt-0.5 shrink-0"><flux:icon.map-pin variant="mini" class="text-zinc-400" /><span class="sr-only">Länder</span></dt>
            <dd class="min-w-0">
                @php $countryList = collect($suggestion->country_codes ?? [])->map(fn ($code) => $countryNames[$code] ?? $code); @endphp
                @if ($countryList->isEmpty())
                    <span class="font-medium text-amber-600 dark:text-amber-400">Kein Land erkannt</span>
                @else
                    {{ $countryList->implode(', ') }}
                @endif
                @if ($suggestion->location)
                    <span class="text-zinc-500">· {{ $suggestion->location }}</span>
                @endif
            </dd>
        </div>
    </dl>

    @if ($suggestion->summary)
        <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $suggestion->summary }}</p>
    @endif

    @if (! empty($suggestion->sources))
        <ul class="mt-3 flex flex-col gap-1 text-sm">
            @foreach ($suggestion->sources as $source)
                <li class="flex min-w-0 items-center gap-1.5">
                    <flux:icon.link variant="micro" class="shrink-0 text-zinc-400" />
                    <a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer" class="truncate text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:text-zinc-900 hover:decoration-zinc-900 dark:text-zinc-300 dark:hover:text-white">{{ $source['title'] ?: $source['url'] }}</a>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Was daraus im Passolution Ereignis geworden ist --}}
    @if ($status === \App\Models\AiEventSuggestion::STATUS_CONVERTED && ($event = $suggestion->customEvent))
        @php
            $plain = fn (?string $html) => trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) preg_replace('#<(br|/p|/div|/li)[^>]*>#i', ' ', (string) $html)))));
            $eventTitle = (string) $event->getTitle('de');
            $eventText = $plain($event->getPopupContent('de'));
            $unchanged = trim($eventTitle) === trim($suggestion->title) && $eventText === $plain($suggestion->summary);
        @endphp
        <div class="mt-3 rounded-lg border border-green-200 bg-green-50/70 px-3 py-2.5 dark:border-green-400/20 dark:bg-green-400/5">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium uppercase tracking-wide text-green-800 dark:text-green-300">Übernommen ins Passolution Ereignis</span>
                @unless ($event->trashed())
                    <x-adminv2.state-badge :state="\App\Support\AdminV2\EventState::of($event)" />
                @endunless
            </div>
            <p class="mt-1.5 text-sm font-semibold text-zinc-900 dark:text-white">{{ $eventTitle !== '' ? $eventTitle : 'Ohne Titel' }}</p>
            @if ($eventText !== '')
                <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ \Illuminate\Support\Str::limit($eventText, 500) }}</p>
            @endif
            <p class="mt-1.5 text-xs text-zinc-600 dark:text-zinc-400">
                {{ $unchanged
                    ? 'Titel und Text wurden unverändert aus dem Vorschlag übernommen.'
                    : 'Titel oder Text wurden im Ereignis angepasst – oben steht der Vorschlag der KI, hier der Stand im Ereignis.' }}
            </p>
        </div>
    @endif

    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-4">
        <div class="flex flex-wrap items-center gap-2">
            @if ($status === \App\Models\AiEventSuggestion::STATUS_NEW)
                <flux:button size="sm" variant="primary" icon="document-plus" wire:click="createDraftFromSuggestion({{ $suggestion->id }})">Als Entwurf anlegen</flux:button>
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="dismissSuggestion({{ $suggestion->id }})">Verwerfen</flux:button>
            @elseif ($status === \App\Models\AiEventSuggestion::STATUS_CONVERTED)
                @if ($suggestion->customEvent && ! $suggestion->customEvent->trashed())
                    <flux:button size="sm" icon="arrow-top-right-on-square" :href="route('adminv2.events.edit', $suggestion->custom_event_id)">Zum Ereignis</flux:button>
                @else
                    <span class="text-sm text-zinc-500">Das Ereignis wurde gelöscht.</span>
                @endif
            @else
                <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" wire:click="restoreSuggestion({{ $suggestion->id }})">Wieder vorschlagen</flux:button>
            @endif
        </div>
        <span class="text-xs tabular-nums text-zinc-500">
            gefunden {{ $suggestion->created_at?->format('d.m.Y H:i') }}{{ $suggestion->search?->profile ? ' · Suche „'.$suggestion->search->profile->name.'“' : '' }}
        </span>
    </div>
</article>
