@php
    use App\Models\CustomEvent;

    $sourceLocale = CustomEvent::sourceLocale();
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Event-Typen</flux:heading>
            <flux:subheading>System · Kategorien der Ereignisse mit ihren Namen je Sprache – so erscheinen sie in API, Feeds und Filtern.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('adminv2.system.event-types.create')">Neuer Event-Typ</flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->types as $type)
            @php
                $editUrl = route('adminv2.system.event-types.edit', $type);
                $missing = $this->missingLocales($type);
            @endphp
            <article wire:key="event-type-{{ $type->id }}" @class([
                'group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
                'opacity-70' => ! $type->is_active,
            ])>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="mt-1 size-3 shrink-0 rounded-full" style="background-color: {{ $type->color ?: '#a1a1aa' }}"></span>
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                                <a href="{{ $editUrl }}" class="after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $type->name }}</a>
                            </h2>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                <flux:badge size="sm" color="zinc" inset="top bottom" class="font-mono">{{ $type->code }}</flux:badge>
                                <flux:badge size="sm" :color="$type->is_active ? 'green' : 'zinc'" inset="top bottom">{{ $type->is_active ? 'Aktiv' : 'Inaktiv' }}</flux:badge>
                                <flux:badge size="sm" color="zinc" inset="top bottom">{{ $type->events_count }} {{ $type->events_count === 1 ? 'Ereignis' : 'Ereignisse' }}</flux:badge>
                            </div>
                        </div>
                    </div>

                    <div class="relative z-10 -me-1.5 -mt-1 shrink-0">
                        <flux:dropdown align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen für {{ $type->name }}" />
                            <flux:menu>
                                <flux:menu.item icon="pencil-square" :href="$editUrl">Bearbeiten</flux:menu.item>
                                <flux:menu.item :icon="$type->is_active ? 'pause' : 'play'" wire:click="toggleActive({{ $type->id }})">{{ $type->is_active ? 'Deaktivieren' : 'Aktivieren' }}</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>

                <dl class="mt-3 flex flex-col gap-1 text-sm">
                    @foreach (CustomEvent::translationLocales() as $locale)
                        @continue($locale === $sourceLocale)
                        <div class="flex items-baseline gap-2">
                            <dt class="w-8 shrink-0 font-mono text-xs uppercase text-zinc-400">{{ $locale }}</dt>
                            <dd @class(['min-w-0 truncate', 'text-zinc-700 dark:text-zinc-300' => ! in_array($locale, $missing, true), 'italic text-amber-600 dark:text-amber-400' => in_array($locale, $missing, true)])>
                                {{ $type->name_translations[$locale] ?? 'fehlt' }}
                            </dd>
                        </div>
                    @endforeach
                </dl>

                @if ($type->description)
                    <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $type->description }}</p>
                @endif
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 px-5 py-16 text-center dark:border-zinc-700">
                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                    <flux:icon.tag class="size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Noch keine Event-Typen</p>
                    <flux:button size="sm" icon="plus" :href="route('adminv2.system.event-types.create')" class="mt-1">Neuer Event-Typ</flux:button>
                </div>
            </div>
        @endforelse
    </div>
</div>
