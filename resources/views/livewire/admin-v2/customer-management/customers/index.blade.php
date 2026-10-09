@php
    use App\Livewire\AdminV2\CustomerManagement\Customers\Index;

    $rows = $this->rows;
    $pending = $this->pending;
    $pageIds = $rows->pluck('id')->map(fn ($id) => (string) $id)->all();
    $pageSelected = $pageIds !== [] && array_diff($pageIds, $selected) === [];
    $selectedCount = count($selected);
@endphp

<div class="flex flex-col gap-6">
    {{-- Ohne "Neu"-Schaltflaeche: Kunden entstehen bei der Registrierung bzw. beim ersten Login. --}}
    <x-adminv2.customer-management.list-header section="customers" />

    <div class="flex flex-wrap items-center gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Name, E-Mail, Firma oder PDS Account-ID suchen …" aria-label="Suche" clearable />
        </div>
        <div class="w-44">
            <flux:select wire:model.live="customerType" aria-label="Kundentyp">
                <flux:select.option value="">Alle Kundentypen</flux:select.option>
                @foreach (Index::CUSTOMER_TYPES as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-52">
            <flux:select wire:model.live="emailVerified" aria-label="E-Mail Status">
                <flux:select.option value="">E-Mail Status: alle</flux:select.option>
                <flux:select.option value="verified">Verifiziert</flux:select.option>
                <flux:select.option value="unverified">Nicht verifiziert</flux:select.option>
            </flux:select>
        </div>
        <div class="w-48">
            <flux:select wire:model.live="gtmApi" aria-label="GTM API Zugang">
                <flux:select.option value="">GTM API: alle</flux:select.option>
                <flux:select.option value="enabled">GTM API aktiv</flux:select.option>
                <flux:select.option value="disabled">GTM API inaktiv</flux:select.option>
            </flux:select>
        </div>
        <div class="w-48">
            <flux:select wire:model.live="trashed" aria-label="Papierkorb">
                <flux:select.option value="">Ohne gelöschte Kunden</flux:select.option>
                <flux:select.option value="with">Mit gelöschten Kunden</flux:select.option>
                <flux:select.option value="only">Nur gelöschte Kunden</flux:select.option>
            </flux:select>
        </div>
        @if ($this->hasFilters())
            <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters">Zurücksetzen</flux:button>
        @endif
    </div>

    <x-adminv2.master-data.sort-bar :options="$this->sortOptions()" :sort="$sort" :direction="$direction" :total="$rows->total()" :noun="['Kunde', 'Kunden']" />

    @if ($rows->isEmpty())
        <x-adminv2.card flush>
            @if ($this->hasFilters())
                <x-adminv2.master-data.empty filtered noun="Kunden" />
            @else
                <div class="flex flex-col items-center gap-3 px-4 py-14 text-center">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
                        <flux:icon.users class="size-6" />
                    </span>
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine Kunden</p>
                        <p class="mt-1 text-sm text-zinc-500">Es wurden noch keine Kunden registriert.</p>
                    </div>
                </div>
            @endif
        </x-adminv2.card>
    @else
        {{-- Auswahl und Sammelaktionen --}}
        <div class="flex min-h-9 flex-wrap items-center gap-x-4 gap-y-2 text-sm text-zinc-600 dark:text-zinc-400">
            <label class="inline-flex cursor-pointer items-center gap-2">
                <input type="checkbox" wire:click="toggleSelectPage" @checked($pageSelected) class="size-4 rounded border-zinc-300 accent-[var(--color-accent)]" />
                Alle auf dieser Seite auswählen
            </label>

            @if ($selectedCount > 0)
                <span class="font-medium text-zinc-900 tabular-nums dark:text-white">{{ $selectedCount }} ausgewählt</span>
                <flux:dropdown>
                    <flux:button size="sm" icon-trailing="chevron-down">Sammelaktionen</flux:button>

                    <flux:menu>
                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmAction('bulk-delete')">Ausgewählte löschen</flux:menu.item>
                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmAction('bulk-force')">Ausgewählte endgültig löschen</flux:menu.item>
                        <flux:menu.item icon="arrow-uturn-left" wire:click="confirmAction('bulk-restore')">Ausgewählte wiederherstellen</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearSelection">Auswahl aufheben</flux:button>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60" wire:target="search, customerType, emailVerified, gtmApi, trashed, sort, toggleDirection, resetFilters, runPendingAction, gotoPage, nextPage, previousPage">
            @foreach ($rows as $customer)
                @php
                    $editUrl = route('adminv2.customer-management.customers.edit', $customer->id);
                    $title = Index::label($customer);
                    $isTrashed = $customer->trashed();
                @endphp
                <article
                    wire:key="customer-{{ $customer->id }}"
                    @class([
                        'group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
                        'opacity-70' => $isTrashed,
                    ])
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-2.5">
                            <input type="checkbox" wire:model.live="selected" value="{{ $customer->id }}" aria-label="{{ $title }} auswählen" class="relative z-10 mt-1 size-4 shrink-0 rounded border-zinc-300 accent-[var(--color-accent)]" />
                            <div class="min-w-0">
                                <h2 class="min-w-0 text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                                    {{-- Erst die Firma, darunter der Benutzer; ohne Firma steht der Benutzer oben. --}}
                                    <a href="{{ $editUrl }}" class="line-clamp-2 after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $customer->company_name ?: $title }}</a>
                                </h2>
                                @if ($customer->company_name)
                                    <p class="mt-0.5 truncate text-sm text-zinc-500">{{ $title }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="relative z-10 -me-1.5 -mt-1 shrink-0">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen für {{ $title }}" />

                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" :href="$editUrl">Bearbeiten</flux:menu.item>
                                    <flux:menu.item icon="arrow-top-right-on-square" :href="$editUrl" target="_blank">In neuem Tab öffnen</flux:menu.item>
                                    <flux:menu.separator />
                                    @if ($isTrashed)
                                        <flux:menu.item icon="arrow-uturn-left" wire:click="confirmAction('restore', {{ $customer->id }})">Wiederherstellen</flux:menu.item>
                                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmAction('force', {{ $customer->id }})">Endgültig löschen</flux:menu.item>
                                    @else
                                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmAction('delete', {{ $customer->id }})">Löschen</flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                        @if ($isTrashed)
                            <flux:badge size="sm" color="zinc" inset="top bottom">Gelöscht</flux:badge>
                        @endif
                        @if ($customer->customer_type)
                            <flux:badge size="sm" inset="top bottom" :color="match ($customer->customer_type) { 'private' => 'sky', 'business' => 'green', default => 'zinc' }">
                                {{ Index::CUSTOMER_TYPES[$customer->customer_type] ?? $customer->customer_type }}
                            </flux:badge>
                        @endif
                        @if ($customer->passolution_subscription_type)
                            <flux:badge size="sm" color="blue" inset="top bottom" title="Passolution Abo">{{ $customer->passolution_subscription_type }}</flux:badge>
                        @endif
                        <flux:badge size="sm" inset="top bottom" :color="$customer->provider ? 'amber' : 'zinc'" title="Login via">{{ $customer->provider ? ucfirst($customer->provider) : 'E-Mail' }}</flux:badge>
                        @if ($customer->gtm_api_enabled)
                            <flux:badge size="sm" color="green" inset="top bottom" icon="bolt" title="GTM API Zugang aktiv, {{ $customer->gtm_api_rate_limit ?? 60 }} Anfragen pro Minute">GTM API</flux:badge>
                        @endif
                    </div>

                    <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                        <x-adminv2.master-data.card-row icon="envelope" label="E-Mail">
                            <span class="flex min-w-0 items-center gap-1.5" x-data="{ copied: false }">
                                <span class="truncate">{{ $customer->email ?: '–' }}</span>
                                @if ($customer->email)
                                    <button
                                        type="button"
                                        class="relative z-10 shrink-0 text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                                        x-on:click="navigator.clipboard.writeText(@js($customer->email)); copied = true; setTimeout(() => copied = false, 1500)"
                                        aria-label="E-Mail-Adresse kopieren"
                                        title="Kopieren"
                                    >
                                        <flux:icon.clipboard variant="micro" x-show="! copied" />
                                        <flux:icon.check variant="micro" x-show="copied" x-cloak />
                                    </button>
                                @endif
                                @if ($customer->email_verified_at)
                                    <flux:icon.check-circle variant="mini" class="shrink-0 text-green-600" title="E-Mail verifiziert am {{ $customer->email_verified_at->format('d.m.Y H:i') }}" />
                                    <span class="sr-only">E-Mail verifiziert</span>
                                @else
                                    <flux:icon.x-circle variant="mini" class="shrink-0 text-red-500" title="E-Mail nicht verifiziert" />
                                    <span class="sr-only">E-Mail nicht verifiziert</span>
                                @endif
                            </span>
                        </x-adminv2.master-data.card-row>

                        @if (array_key_exists('pds_account_id', $this->sortOptions()))
                            <x-adminv2.master-data.card-row icon="identification" label="PDS Account-ID">
                                @if ($customer->pds_account_id)
                                    <span class="flex items-center gap-1.5" x-data="{ copied: false }">
                                        <span>PDS Account-ID <span class="font-mono text-xs">{{ $customer->pds_account_id }}</span></span>
                                        <button
                                            type="button"
                                            class="relative z-10 shrink-0 text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                                            x-on:click="navigator.clipboard.writeText(@js((string) $customer->pds_account_id)); copied = true; setTimeout(() => copied = false, 1500)"
                                            aria-label="PDS Account-ID kopieren"
                                            title="Kopieren"
                                        >
                                            <flux:icon.clipboard variant="micro" x-show="! copied" />
                                            <flux:icon.check variant="micro" x-show="copied" x-cloak />
                                        </button>
                                    </span>
                                @else
                                    <span class="text-zinc-400">PDS Account-ID: noch nie angemeldet</span>
                                @endif
                            </x-adminv2.master-data.card-row>
                        @endif

                        <x-adminv2.master-data.card-row icon="building-storefront" label="Filialen">
                            {{ $customer->branches_count }} {{ $customer->branches_count === 1 ? 'Filiale' : 'Filialen' }}
                            <span class="text-zinc-400">·</span> Filialen {{ $customer->branch_management_active ? 'aktiv' : 'inaktiv' }}
                        </x-adminv2.master-data.card-row>

                        <x-adminv2.master-data.card-row icon="key" label="API-Zugang">
                            @if ($customer->gtm_api_enabled)
                                <span class="text-green-700 dark:text-green-400">GTM API aktiv</span>
                                <span class="text-zinc-400">({{ $customer->gtm_api_rate_limit ?? 60 }}/min)</span>
                            @else
                                <span>GTM API inaktiv</span>
                            @endif
                            <span class="text-zinc-400">·</span>
                            <span @class(['font-medium text-zinc-900 dark:text-white' => $customer->tokens_count > 0])>{{ $customer->tokens_count }} {{ $customer->tokens_count === 1 ? 'API Token' : 'API Tokens' }}</span>
                            @if ($customer->tokens_count > 0 && ! $customer->gtm_api_enabled)
                                <flux:icon.exclamation-triangle variant="micro" class="inline shrink-0 text-amber-500" title="Tokens vorhanden, aber GTM API Zugang nicht aktiv" />
                            @endif
                        </x-adminv2.master-data.card-row>
                    </dl>

                    <div class="mt-auto flex flex-wrap items-center justify-between gap-x-4 gap-y-1 pt-4 text-xs text-zinc-500">
                        <span class="tabular-nums">registriert {{ $customer->created_at?->format('d.m.Y H:i') ?? '–' }}</span>
                        @if ($isTrashed)
                            <span class="tabular-nums">gelöscht {{ $customer->deleted_at->format('d.m.Y H:i') }}</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <x-adminv2.pagination :paginator="$rows" />
    @endif

    {{-- Rueckfrage vor dem Loeschen, Wiederherstellen und endgueltigen Loeschen --}}
    <flux:modal name="customer-confirm" class="md:w-[32rem]">
        @php
            $action = $pending['action'] ?? 'delete';
            $bulk = (bool) ($pending['bulk'] ?? false);
            $count = (int) ($pending['count'] ?? 0);
        @endphp
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">
                    @if ($action === 'force')
                        {{ $bulk ? 'Kunden endgültig löschen' : 'Kunde endgültig löschen' }}
                    @elseif ($action === 'restore')
                        {{ $bulk ? 'Kunden wiederherstellen' : 'Kunde wiederherstellen' }}
                    @else
                        {{ $bulk ? 'Kunden löschen' : 'Kunde löschen' }}
                    @endif
                </flux:heading>

                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    @if ($bulk)
                        @if ($action === 'force')
                            ACHTUNG: Dies löscht die {{ $count }} ausgewählten Kunden permanent. Sie können sich danach erneut registrieren. Diese Aktion kann nicht rückgängig gemacht werden!
                        @elseif ($action === 'restore')
                            Möchten Sie die gelöschten unter den {{ $count }} ausgewählten Kunden wiederherstellen?
                        @else
                            Möchten Sie die {{ $count }} ausgewählten Kunden wirklich löschen? Dies ist ein Soft Delete – die Kunden lassen sich wiederherstellen.
                        @endif
                    @elseif ($action === 'force')
                        ACHTUNG: Dies löscht „{{ $pending['label'] ?? '' }}“ permanent aus der Datenbank. Der Benutzer kann sich danach erneut registrieren. Diese Aktion kann nicht rückgängig gemacht werden!
                    @elseif ($action === 'restore')
                        Möchten Sie „{{ $pending['label'] ?? '' }}“ wiederherstellen?
                    @else
                        Möchten Sie „{{ $pending['label'] ?? '' }}“ wirklich löschen? Dies ist ein Soft Delete – der Kunde lässt sich wiederherstellen.
                    @endif
                </p>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                @if ($action === 'restore')
                    <flux:button variant="primary" icon="arrow-uturn-left" wire:click="runPendingAction">Wiederherstellen</flux:button>
                @else
                    <flux:button variant="danger" icon="trash" wire:click="runPendingAction">{{ $action === 'force' ? 'Endgültig löschen' : 'Löschen' }}</flux:button>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
