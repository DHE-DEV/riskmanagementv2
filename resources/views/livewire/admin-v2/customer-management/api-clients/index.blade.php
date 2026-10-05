@php
    use App\Livewire\AdminV2\CustomerManagement\ApiClients\Index;

    $rows = $this->rows;
    $selectedCount = count($selected);
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.customer-management.list-header section="api-clients" create-label="Neuer API-Kunde" />

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name oder Firma suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-44">
            <flux:select wire:model.live="status" aria-label="Status">
                <flux:select.option value="">Alle Status</flux:select.option>
                @foreach (Index::STATUSES as $value => [$label])
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['API-Kunde', 'API-Kunden']" />

    {{-- Mehrere auf einmal: erscheint, sobald etwas angehakt ist --}}
    @if ($selectedCount > 0)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-5 py-3 text-sm dark:border-zinc-800 dark:bg-zinc-900">
            <span class="font-medium text-zinc-900 tabular-nums dark:text-white">{{ $selectedCount }} ausgewählt</span>
            <div class="flex flex-wrap items-center gap-2">
                <flux:button size="sm" variant="ghost" wire:click="selectPage">Alle auf dieser Seite</flux:button>
                <flux:button size="sm" variant="ghost" wire:click="clearSelection">Auswahl aufheben</flux:button>
                <flux:button size="sm" variant="danger" icon="trash" wire:click="deleteSelected" wire:confirm="Die {{ $selectedCount }} ausgewählten API-Kunden löschen? Ihre API-Tokens funktionieren danach nicht mehr.">Löschen</flux:button>
            </div>
        </div>
    @endif

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="API-Kunden" />
        </x-adminv2.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, status, sort, toggleDirection, resetFilters, delete, deleteSelected, gotoPage, nextPage, previousPage">
            @foreach ($rows as $client)
                @php
                    $editUrl = route('adminv2.customer-management.api-clients.edit', $client->id);
                    [$statusLabel, $statusColor] = Index::STATUSES[$client->status] ?? [$client->status, 'zinc'];
                @endphp
                <article
                    wire:key="api-client-{{ $client->id }}"
                    @class([
                        'group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
                        'opacity-70' => $client->status !== 'active',
                    ])
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="relative z-10 pt-0.5">
                                <flux:checkbox wire:model.live="selected" value="{{ $client->id }}" aria-label="{{ $client->name }} auswählen" />
                            </div>
                            <div class="min-w-0">
                                <h2 class="min-w-0 text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                                    <a href="{{ $editUrl }}" class="line-clamp-2 after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $client->name }}</a>
                                </h2>
                                <p class="mt-0.5 truncate text-sm text-zinc-500">{{ $client->company_name }}</p>
                            </div>
                        </div>

                        <div class="relative z-10 -me-1.5 -mt-1 shrink-0">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen für {{ $client->name }}" />

                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" :href="$editUrl">Anzeigen und bearbeiten</flux:menu.item>
                                    <flux:menu.item icon="arrow-top-right-on-square" :href="$editUrl" target="_blank">In neuem Tab öffnen</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $client->id }})" wire:confirm="„{{ $client->name }}“ löschen? Die API-Tokens dieses Kunden funktionieren danach nicht mehr.">Löschen</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                        <flux:badge size="sm" :color="$statusColor" inset="top bottom">{{ $statusLabel }}</flux:badge>
                        <flux:badge size="sm" :color="$client->can_create_events ? 'green' : 'zinc'" inset="top bottom">Event-Erstellung {{ $client->can_create_events ? 'erlaubt' : 'aus' }}</flux:badge>
                        <flux:badge size="sm" :color="$client->auto_approve_events ? 'green' : 'zinc'" inset="top bottom">Auto-Freigabe {{ $client->auto_approve_events ? 'an' : 'aus' }}</flux:badge>
                    </div>

                    <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                        <x-adminv2.master-data.card-row icon="envelope" label="E-Mail">
                            <span class="break-all">{{ $client->contact_email }}</span>
                        </x-adminv2.master-data.card-row>
                        <x-adminv2.master-data.card-row icon="calendar-days" label="Events">
                            {{ $client->custom_events_count }} {{ $client->custom_events_count === 1 ? 'Event' : 'Events' }}
                        </x-adminv2.master-data.card-row>
                    </dl>

                    <div class="mt-auto flex flex-wrap items-center justify-between gap-x-4 gap-y-1 pt-4 text-xs text-zinc-500">
                        <span class="tabular-nums">erstellt {{ $client->created_at?->format('d.m.Y H:i') ?? '–' }}</span>
                    </div>
                </article>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif
</div>
