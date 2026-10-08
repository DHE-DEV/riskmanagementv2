@php
    $sourceLocale = \App\Models\CustomEvent::sourceLocale();
    $operators = $this->operators;
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Mobilfunkanbieter</flux:heading>
            <flux:subheading>System · Mobilfunkanbieter, die sich bei den Ländern als verbreitete Anbieter zuordnen lassen.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('adminv2.system.mobile-operators.create')">Neuer Anbieter</flux:button>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 max-w-md flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Anbieter suchen …" aria-label="Suche" clearable />
        </div>
        <span class="text-sm text-zinc-500 tabular-nums">{{ number_format($operators->total(), 0, ',', '.') }} {{ $operators->total() === 1 ? 'Anbieter' : 'Anbieter' }}</span>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($operators as $operator)
            @php $editUrl = route('adminv2.system.mobile-operators.edit', $operator); @endphp
            <article wire:key="operator-{{ $operator->id }}" @class([
                'group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
                'opacity-70' => ! $operator->is_active,
            ])>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <x-adminv2.provider-logo :url="$operator->logo_url" :name="$operator->name" class="size-12" />
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                                <a href="{{ $editUrl }}" class="after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $operator->name }}</a>
                            </h2>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                <flux:badge size="sm" :color="$operator->is_active ? 'green' : 'zinc'" inset="top bottom">{{ $operator->is_active ? 'Aktiv' : 'Inaktiv' }}</flux:badge>
                                @if ($operator->offers_esim) <flux:badge size="sm" color="sky" inset="top bottom">eSIM</flux:badge> @endif
                                <flux:badge size="sm" color="zinc" inset="top bottom">{{ $operator->countries_count }} {{ $operator->countries_count === 1 ? 'Land' : 'Länder' }}</flux:badge>
                            </div>
                        </div>
                    </div>

                    <div class="relative z-10 -me-1.5 -mt-1 shrink-0">
                        <flux:dropdown align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen für {{ $operator->name }}" />
                            <flux:menu>
                                <flux:menu.item icon="pencil-square" :href="$editUrl">Bearbeiten</flux:menu.item>
                                <flux:menu.item :icon="$operator->is_active ? 'pause' : 'play'" wire:click="toggleActive({{ $operator->id }})">{{ $operator->is_active ? 'Deaktivieren' : 'Aktivieren' }}</flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $operator->id }})" wire:confirm="„{{ $operator->name }}“ löschen? Die Zuordnung zu {{ $operator->countries_count }} {{ $operator->countries_count === 1 ? 'Land' : 'Ländern' }} geht mit.">Löschen</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>

                @if ($operator->description($sourceLocale))
                    <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $operator->description($sourceLocale) }}</p>
                @endif

                <div class="relative z-10 mt-auto flex flex-wrap gap-x-4 gap-y-1 pt-4 text-xs">
                    @if ($operator->website_url)
                        <a href="{{ $operator->website_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300"><flux:icon.globe-alt variant="micro" /> Website</a>
                    @endif
                    @if ($operator->prepaid_url)
                        <a href="{{ $operator->prepaid_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300"><flux:icon.credit-card variant="micro" /> Prepaid</a>
                    @endif
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 px-5 py-16 text-center dark:border-zinc-700">
                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                    <flux:icon.signal class="size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $search !== '' ? 'Kein Anbieter zur Suche' : 'Noch keine Mobilfunkanbieter' }}</p>
                    <p class="text-sm text-zinc-500">{{ $search !== '' ? 'Zur Suche passt kein Eintrag.' : 'Lege Anbieter wie Telekom, Vodafone oder Orange an. Danach lassen sie sich bei den Ländern zuordnen.' }}</p>
                    @if ($search === '')
                        <flux:button size="sm" icon="plus" :href="route('adminv2.system.mobile-operators.create')" class="mt-1">Neuer Anbieter</flux:button>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    @if ($operators->isNotEmpty())
        <x-adminv2.pagination :paginator="$operators" />
    @endif
</div>
