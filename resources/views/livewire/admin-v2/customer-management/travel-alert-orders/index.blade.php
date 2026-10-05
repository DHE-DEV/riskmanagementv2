@php
    use App\Models\TravelAlertOrder;

    $rows = $this->rows;
    $statusColors = [
        TravelAlertOrder::STATUS_ACTIVE => 'green',
        TravelAlertOrder::STATUS_PENDING_APPROVAL => 'amber',
        TravelAlertOrder::STATUS_REJECTED => 'red',
        TravelAlertOrder::STATUS_PENDING_CONFIRMATION => 'zinc',
    ];
    $pageIds = $rows->getCollection()->map(fn ($order) => (string) $order->id)->all();
    $pageSelected = $pageIds !== [] && array_diff($pageIds, $selected) === [];
    $selectedCount = count($selected);
@endphp

<div class="flex flex-col gap-6">
    <x-adminv2.customer-management.list-header section="travel-alert-orders" />

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Firma, Ansprechpartner, E-Mail oder Stadt suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-60">
            <flux:select wire:model.live="status" aria-label="Status">
                <flux:select.option value="">Alle Status</flux:select.option>
                @foreach ($this->statusOptions() as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Bestellung', 'Bestellungen']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            <x-adminv2.master-data.empty :filtered="$this->hasFilters()" noun="Bestellungen" />
        </x-adminv2.card>
    @else
        {{-- Auswahl und gemeinsames Loeschen --}}
        <div class="flex min-h-9 flex-wrap items-center gap-x-4 gap-y-2 text-sm text-zinc-600 dark:text-zinc-400">
            <label class="inline-flex cursor-pointer items-center gap-2">
                <input type="checkbox" wire:click="togglePage" @checked($pageSelected) wire:key="page-toggle-{{ $pageSelected ? 'on' : 'off' }}" class="size-4 rounded border-zinc-300 accent-[var(--color-accent)]" />
                Alle auf dieser Seite auswählen
            </label>

            @if ($selectedCount > 0)
                <span class="font-medium tabular-nums text-zinc-900 dark:text-white">{{ $selectedCount }} ausgewählt</span>
                <flux:button
                    size="sm"
                    variant="danger"
                    icon="trash"
                    wire:click="deleteSelected"
                    wire:confirm="{{ $selectedCount === 1 ? 'Die ausgewählte Bestellung' : 'Die '.$selectedCount.' ausgewählten Bestellungen' }} löschen?"
                >Ausgewählte löschen</flux:button>
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="$set('selected', [])">Auswahl aufheben</flux:button>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, status, sort, toggleDirection, resetFilters, approve, reject, deleteSelected, gotoPage, nextPage, previousPage">
            @foreach ($rows as $order)
                @php
                    $showUrl = route('adminv2.customer-management.travel-alert-orders.show', $order->id);
                    $title = $order->company ?: 'Ohne Firma';
                    $contact = trim(($order->first_name ?? '').' '.($order->last_name ?? ''));
                    $canApprove = ! $order->isApproved() && ! $order->isRejected();
                    $trialExpired = (bool) $order->trial_expires_at?->isPast();
                @endphp
                <article
                    wire:key="order-{{ $order->id }}"
                    @class([
                        'group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
                        'opacity-70' => $order->isRejected(),
                    ])
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-2.5">
                            <input type="checkbox" wire:model.live="selected" value="{{ $order->id }}" aria-label="Bestellung {{ $order->id }} auswählen" class="relative z-10 mt-1 size-4 shrink-0 rounded border-zinc-300 accent-[var(--color-accent)]" />
                            <div class="min-w-0">
                                <h2 class="min-w-0 text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                                    <a href="{{ $showUrl }}" class="line-clamp-2 after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $title }}</a>
                                </h2>
                                @if ($contact !== '')
                                    <p class="mt-0.5 truncate text-sm text-zinc-500">{{ $contact }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="relative z-10 -me-1.5 -mt-1 shrink-0">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen für Bestellung {{ $order->id }}" />

                                <flux:menu>
                                    <flux:menu.item icon="eye" :href="$showUrl">Ansehen</flux:menu.item>
                                    @if ($canApprove)
                                        @if ($order->isConfirmed())
                                            <flux:menu.item icon="check-circle" wire:click="approve({{ $order->id }})" wire:confirm="Travel Alert freischalten? Der Kunde erhält eine E-Mail, dass sein Zugang bereitsteht.">Freischalten</flux:menu.item>
                                        @else
                                            <flux:menu.item icon="check-circle" disabled title="Der Kunde hat die Bestellung noch nicht bestätigt.">Freischalten</flux:menu.item>
                                        @endif
                                    @endif
                                    @unless ($order->isRejected())
                                        <flux:menu.item icon="x-circle" variant="danger" wire:click="reject({{ $order->id }})" wire:confirm="Bestellung ablehnen? Der Zugang wird nicht freigeschaltet. Der Kunde wird nicht automatisch benachrichtigt.">Ablehnen</flux:menu.item>
                                    @endunless
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                        <flux:badge size="sm" inset="top bottom" :color="$statusColors[$order->status]">{{ $order->status_label }}</flux:badge>
                        <flux:badge size="sm" inset="top bottom" :color="$order->existing_billing === 'ja' ? 'green' : 'blue'" title="Abrechnung">{{ $order->existing_billing === 'ja' ? 'Bestehend' : 'Neu' }}</flux:badge>
                    </div>

                    <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                        <x-adminv2.master-data.card-row icon="envelope" label="E-Mail">
                            <span class="break-all">{{ $order->email ?: '–' }}</span>
                        </x-adminv2.master-data.card-row>
                        @if ($order->phone)
                            <x-adminv2.master-data.card-row icon="phone" label="Telefon">{{ $order->phone }}</x-adminv2.master-data.card-row>
                        @endif
                        @if ($order->city || $order->country)
                            <x-adminv2.master-data.card-row icon="map-pin" label="Ort">
                                {{ $order->city ?: '–' }}
                                @if ($order->country) <span class="text-zinc-400">·</span> {{ $order->country }} @endif
                            </x-adminv2.master-data.card-row>
                        @endif
                        @if ($order->trial_expires_at)
                            <x-adminv2.master-data.card-row icon="clock" label="Testversion">
                                <span @class(['tabular-nums', 'font-medium text-red-600 dark:text-red-400' => $trialExpired])>
                                    Test {{ $trialExpired ? 'abgelaufen am' : 'läuft ab am' }} {{ $order->trial_expires_at->format('d.m.Y') }}
                                </span>
                            </x-adminv2.master-data.card-row>
                        @endif
                    </dl>

                    <div class="mt-auto flex flex-wrap items-center justify-between gap-x-4 gap-y-1 pt-4 text-xs text-zinc-500">
                        <span class="tabular-nums">eingegangen {{ $order->created_at?->format('d.m.Y H:i') ?? '–' }}</span>
                        <span class="tabular-nums">Nr. {{ $order->id }}</span>
                    </div>
                </article>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif
</div>
