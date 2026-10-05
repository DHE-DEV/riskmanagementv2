@php
    use App\Livewire\AdminV2\CustomerManagement\PluginClients\Index;

    $rows = $this->rows;
    $stats = $this->stats;
    $number = fn (int $value) => number_format($value, 0, ',', '.');
    $copy = 'livewire.admin-v2.customer-management.plugin-clients.copy';

    $tiles = [
        ['label' => 'Plugin-Kunden', 'value' => $number($stats['clients']), 'hint' => $number($stats['active']).' aktiv', 'icon' => 'users'],
        ['label' => 'Neue Kunden (Monat)', 'value' => $number($stats['new']), 'hint' => 'Diesen Monat registriert', 'icon' => 'user-plus'],
        ['label' => 'Aufrufe heute', 'value' => $number($stats['today']), 'hint' => $number($stats['month']).' diesen Monat', 'icon' => 'cursor-arrow-rays'],
        ['label' => 'Aufrufe gesamt', 'value' => $number($stats['total']), 'hint' => 'Alle Plugin-Aufrufe', 'icon' => 'signal'],
    ];
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.customer-management.list-header section="plugin-clients" create-label="Neuer Plugin-Kunde" />

    {{-- Kennzahlen --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($tiles as $tile)
            <div class="flex items-start justify-between gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
                <div class="min-w-0">
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $tile['label'] }}</div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $tile['value'] }}</div>
                    <div class="mt-0.5 text-xs tabular-nums text-zinc-500">{{ $tile['hint'] }}</div>
                </div>
                <flux:icon :name="$tile['icon']" class="size-6 shrink-0 text-zinc-300 dark:text-zinc-600" />
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Firma, Ansprechpartner oder E-Mail suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-44">
            <flux:select wire:model.live="status" aria-label="Status">
                <flux:select.option value="">Alle Status</flux:select.option>
                @foreach (Index::STATUSES as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-48">
            <flux:select wire:model.live="usage" aria-label="Aufrufe">
                <flux:select.option value="">Mit und ohne Aufrufe</flux:select.option>
                <flux:select.option value="yes">Mit Aufrufen</flux:select.option>
            </flux:select>
        </div>
        <div class="w-56">
            <flux:select wire:model.live="registered" aria-label="Registrierung">
                <flux:select.option value="">Registriert: jederzeit</flux:select.option>
                <flux:select.option value="month">Diesen Monat registriert</flux:select.option>
            </flux:select>
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Plugin-Kunde', 'Plugin-Kunden']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            @if ($this->hasFilters())
                <x-adminv2.master-data.empty filtered noun="Plugin-Kunden" />
            @else
                <div class="flex flex-col items-center gap-3 px-4 py-14 text-center">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
                        <flux:icon.puzzle-piece class="size-6" />
                    </span>
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine Plugin-Kunden</p>
                        <p class="mt-1 text-sm text-zinc-500">Es haben sich noch keine Kunden für das Plugin registriert.</p>
                    </div>
                </div>
            @endif
        </x-adminv2.card>
    @else
        {{-- Auswahl mehrerer Kunden --}}
        <div class="-mb-2 flex min-h-8 flex-wrap items-center gap-3 text-sm text-zinc-600 dark:text-zinc-400">
            @if ($selected === [])
                <flux:button variant="ghost" size="sm" icon="check-circle" wire:click="selectPage">Alle auf dieser Seite auswählen</flux:button>
            @else
                <span class="font-medium tabular-nums text-zinc-900 dark:text-white">{{ count($selected) }} ausgewählt</span>
                <flux:button variant="ghost" size="sm" wire:click="selectPage">Alle auf dieser Seite</flux:button>
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearSelection">Auswahl aufheben</flux:button>
                <flux:button variant="danger" size="sm" icon="trash" wire:click="deleteSelected" wire:confirm="Die ausgewählten Plugin-Kunden löschen? Alle zugehörigen Daten (Domains, API-Keys, Nutzungsstatistiken) werden ebenfalls gelöscht.">Löschen</flux:button>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" wire:loading.class="opacity-60" wire:target="search, status, usage, registered, sort, toggleDirection, resetFilters, toggleStatus, delete, deleteSelected, gotoPage, nextPage, previousPage">
            @foreach ($rows as $client)
                @php
                    $editUrl = route('adminv2.customer-management.plugin-clients.edit', $client->id);
                    $key = $client->activeKey?->public_key;
                    $place = $client->city ?: $client->customer?->company_city;
                    $isActive = $client->status === 'active';
                @endphp
                <article
                    wire:key="plugin-client-{{ $client->id }}"
                    @class([
                        'group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
                        'opacity-70' => ! $isActive,
                    ])
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-2.5">
                            <input type="checkbox" wire:model.live="selected" value="{{ $client->id }}" aria-label="{{ $client->company_name }} auswählen" class="relative z-10 mt-1 size-4 shrink-0 rounded accent-[var(--color-accent)]" />
                            <div class="min-w-0">
                                <h2 class="min-w-0 text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                                    <a href="{{ $editUrl }}" class="line-clamp-2 after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $client->company_name }}</a>
                                </h2>
                                @if (filled($client->contact_name))
                                    <p class="mt-0.5 truncate text-sm text-zinc-500">{{ $client->contact_name }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="relative z-10 -me-1.5 -mt-1 shrink-0">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen für {{ $client->company_name }}" />

                                <flux:menu>
                                    <flux:menu.item icon="eye" :href="$editUrl">Details und Bearbeiten</flux:menu.item>
                                    <flux:menu.item icon="arrow-top-right-on-square" :href="$editUrl" target="_blank">In neuem Tab öffnen</flux:menu.item>
                                    <flux:menu.item
                                        :icon="$isActive ? 'x-circle' : 'check-circle'"
                                        wire:click="toggleStatus({{ $client->id }})"
                                        wire:confirm="{{ $isActive ? 'Diesen Plugin-Kunden deaktivieren? Das Plugin funktioniert dann auf seinen Seiten nicht mehr.' : 'Diesen Plugin-Kunden aktivieren?' }}"
                                    >
                                        {{ $isActive ? 'Deaktivieren' : 'Aktivieren' }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $client->id }})" wire:confirm="Diesen Plugin-Kunden löschen? Alle zugehörigen Daten (Domains, API-Keys, Nutzungsstatistiken) werden ebenfalls gelöscht.">Löschen</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                        <flux:badge size="sm" inset="top bottom" :color="Index::STATUS_COLORS[$client->status] ?? 'zinc'">{{ Index::STATUSES[$client->status] ?? $client->status }}</flux:badge>
                        <flux:badge size="sm" inset="top bottom" color="blue">{{ $number($client->domains_count) }} {{ $client->domains_count === 1 ? 'Domain' : 'Domains' }}</flux:badge>
                        <flux:badge size="sm" inset="top bottom" color="green">{{ $number($client->usage_events_count) }} {{ $client->usage_events_count === 1 ? 'Aufruf' : 'Aufrufe' }}</flux:badge>
                        @if ($client->allow_app_access)
                            <flux:badge size="sm" inset="top bottom" color="zinc" icon="device-phone-mobile">App-Zugang</flux:badge>
                        @endif
                    </div>

                    <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                        <x-adminv2.master-data.card-row icon="envelope" label="E-Mail">
                            <span class="break-all">{{ $client->email }}</span>
                            @include($copy, ['text' => $client->email, 'label' => 'E-Mail kopieren'])
                        </x-adminv2.master-data.card-row>
                        @if (filled($place))
                            <x-adminv2.master-data.card-row icon="map-pin" label="Ort">{{ $place }}</x-adminv2.master-data.card-row>
                        @endif
                        <x-adminv2.master-data.card-row icon="key" label="API-Key">
                            @if ($key)
                                <span class="font-mono text-xs" title="{{ $key }}">{{ \Illuminate\Support\Str::limit($key, 20) }}</span>
                                @include($copy, ['text' => $key, 'label' => 'API-Key kopieren'])
                            @else
                                Kein API-Key vorhanden
                            @endif
                        </x-adminv2.master-data.card-row>
                        @if ($client->customer)
                            <x-adminv2.master-data.card-row icon="user" label="Verknüpfter Kunde">
                                <a href="{{ route('adminv2.customer-management.customers.edit', $client->customer_id) }}" class="relative z-10 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $client->customer->name ?: $client->customer->email }}</a>
                            </x-adminv2.master-data.card-row>
                        @endif
                    </dl>

                    <div class="mt-auto flex flex-wrap items-center justify-between gap-x-4 gap-y-1 pt-4 text-xs text-zinc-500">
                        <span class="tabular-nums">registriert {{ $client->created_at?->format('d.m.Y H:i') ?? '–' }}</span>
                    </div>
                </article>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif
</div>
