@php
    $sourceLocale = \App\Models\CustomEvent::sourceLocale();
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Taxi Apps</flux:heading>
            <flux:subheading>System · Taxi- und Mobilitäts-Apps, die sich bei den Ländern als verbreitete Anbieter zuordnen lassen.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('adminv2.system.taxi-apps.create')">Neue Taxi App</flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->apps as $app)
            @php $editUrl = route('adminv2.system.taxi-apps.edit', $app); @endphp
            <article wire:key="taxi-app-{{ $app->id }}" @class([
                'group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
                'opacity-70' => ! $app->is_active,
            ])>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <x-adminv2.taxi-app-logo :taxi-app="$app" class="size-12" />
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                                <a href="{{ $editUrl }}" class="after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $app->name }}</a>
                            </h2>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                <flux:badge size="sm" :color="$app->is_active ? 'green' : 'zinc'" inset="top bottom">{{ $app->is_active ? 'Aktiv' : 'Inaktiv' }}</flux:badge>
                                <flux:badge size="sm" color="zinc" inset="top bottom">{{ $app->countries_count }} {{ $app->countries_count === 1 ? 'Land' : 'Länder' }}</flux:badge>
                            </div>
                        </div>
                    </div>

                    <div class="relative z-10 -me-1.5 -mt-1 shrink-0">
                        <flux:dropdown align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen für {{ $app->name }}" />
                            <flux:menu>
                                <flux:menu.item icon="pencil-square" :href="$editUrl">Bearbeiten</flux:menu.item>
                                <flux:menu.item :icon="$app->is_active ? 'pause' : 'play'" wire:click="toggleActive({{ $app->id }})">{{ $app->is_active ? 'Deaktivieren' : 'Aktivieren' }}</flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $app->id }})" wire:confirm="„{{ $app->name }}“ löschen? Die Zuordnung zu {{ $app->countries_count }} {{ $app->countries_count === 1 ? 'Land' : 'Ländern' }} geht mit.">Löschen</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>

                @if ($app->description($sourceLocale))
                    <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $app->description($sourceLocale) }}</p>
                @endif

                <div class="relative z-10 mt-auto flex flex-wrap gap-x-4 gap-y-1 pt-4 text-xs">
                    @if ($app->website_url)
                        <a href="{{ $app->website_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300"><flux:icon.globe-alt variant="micro" /> Website</a>
                    @endif
                    @if ($app->app_store_url)
                        <a href="{{ $app->app_store_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300"><flux:icon.device-phone-mobile variant="micro" /> App Store</a>
                    @endif
                    @if ($app->play_store_url)
                        <a href="{{ $app->play_store_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300"><flux:icon.device-phone-mobile variant="micro" /> Google Play</a>
                    @endif
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 px-5 py-16 text-center dark:border-zinc-700">
                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                    <flux:icon.device-phone-mobile class="size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Noch keine Taxi Apps</p>
                    <p class="text-sm text-zinc-500">Lege Anbieter wie Uber, Bolt oder FreeNow an. Danach lassen sie sich bei den Ländern zuordnen.</p>
                    <flux:button size="sm" icon="plus" :href="route('adminv2.system.taxi-apps.create')" class="mt-1">Neue Taxi App</flux:button>
                </div>
            </div>
        @endforelse
    </div>
</div>
