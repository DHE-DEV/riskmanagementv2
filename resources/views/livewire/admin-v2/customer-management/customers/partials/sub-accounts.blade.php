@php
    $subAccounts = $this->subAccounts;
@endphp

<div class="flex flex-col gap-4">
    <x-adminv2.card heading="Unteraccounts" description="Kunden, die diesem Kunden als Agentur zugeordnet sind – z. B. die Unteraccounts aus pds-admin." flush>
        <x-slot:actions>
            <flux:button size="sm" icon="arrow-down-tray" :href="route('adminv2.customer-management.customers.import-children', $customerId)">Unteraccounts aus pds-admin importieren</flux:button>
        </x-slot:actions>

        {{-- Hinweis fuer alle, die hier oder beim Unteraccount nachsehen: was die Unteraccounts sind --}}
        <form wire:submit="saveSubAccountsNote" class="flex flex-col gap-2 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
            <flux:textarea
                wire:model="subAccountsNote"
                label="Hinweis zu den Unteraccounts"
                description="Wird auch bei jedem zugeordneten Kunden oben angezeigt."
                placeholder="z. B. Reisebürokette – die Unteraccounts sind eigenständige Büros mit eigenen Logins."
                rows="2"
                resize="vertical"
            />
            <flux:error name="subAccountsNote" />
            <div>
                <flux:button type="submit" size="sm" icon="check" wire:loading.attr="disabled" wire:target="saveSubAccountsNote">Hinweis speichern</flux:button>
            </div>
        </form>

        @if ($showSubAccountsAsBranches && $this->subAccountGeoStats['total'] > 0)
            @php
                $geo = $this->subAccountGeoStats;
            @endphp
            {{-- Karte "Filialen & Standorte": Stand der Koordinaten --}}
            <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 px-5 py-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-300" @if ($geo['pending'] > 0) wire:poll.15s @endif>
                <flux:icon.map-pin variant="mini" class="text-zinc-400" />
                <span>Karte: {{ $geo['located'] }} von {{ $geo['total'] }} mit Koordinaten</span>
                @if ($geo['pending'] > 0)
                    <flux:badge size="sm" color="sky" inset="top bottom">{{ $geo['pending'] }} werden ermittelt</flux:badge>
                    <flux:button size="xs" variant="ghost" icon="play" wire:click="geocodeSubAccounts">Anstoßen</flux:button>
                @endif
                @if ($geo['missing'] > 0)
                    <flux:badge size="sm" color="amber" inset="top bottom">{{ $geo['missing'] }} ohne Treffer bzw. ohne Adresse</flux:badge>
                    <flux:button size="xs" variant="ghost" icon="arrow-path" wire:click="geocodeSubAccounts(true)">Erneut versuchen</flux:button>
                @endif
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3 px-5 py-4">
            <div class="min-w-56 flex-1">
                <flux:input wire:model.live.debounce.300ms="subAccountSearch" size="sm" icon="magnifying-glass" placeholder="Code, Firma, E-Mail, PLZ, Stadt oder PDS-ID suchen …" aria-label="Unteraccounts durchsuchen" clearable />
            </div>
        </div>

        @if ($subAccounts->isEmpty())
            @include('livewire.admin-v2.customer-management.customers.partials.empty', [
                'icon' => 'building-storefront',
                'heading' => 'Keine Unteraccounts',
                'text' => $subAccountSearch !== '' ? 'Zur Suche passt kein Unteraccount.' : 'Diesem Kunden ist noch kein anderer Kunde zugeordnet.',
            ])
        @endif
    </x-adminv2.card>

    @if ($subAccounts->isNotEmpty())
        {{-- Karten unter dem Kasten: je nach verfuegbarer Breite 1, 2 oder hoechstens 3 nebeneinander --}}
        <div class="@container" wire:loading.class="opacity-60" wire:target="subAccountSearch, nextPage, previousPage">
            <div class="grid gap-4 @2xl:grid-cols-2 @5xl:grid-cols-3">
                @foreach ($subAccounts as $account)
                    @php
                        $editUrl = route('adminv2.customer-management.customers.edit', $account->id);
                    @endphp
                    <article wire:key="sub-account-{{ $account->id }}" @class([
                        'group relative flex min-w-0 flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700',
                        'opacity-70' => $account->trashed(),
                    ])>
                        <h3 class="min-w-0 text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                            <a href="{{ $editUrl }}" class="line-clamp-2 after:absolute after:inset-0 after:rounded-2xl group-hover:underline">{{ $account->company_name ?: $account->name }}</a>
                        </h3>

                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <flux:badge size="sm" color="sky" class="font-mono">{{ $account->app_code }}</flux:badge>
                            @if ($account->location_type === 'travel_advisor')
                                <flux:badge size="sm" color="teal">Reiseberater</flux:badge>
                            @endif
                            @php
                                $billing = $account->currentBillingPeriod;
                            @endphp
                            @if (! $billing)
                                <flux:badge size="sm" color="zinc">Abrechnung offen</flux:badge>
                            @elseif ($billing->billing_mode === 'cooperation')
                                <flux:badge size="sm" color="indigo" icon="building-office-2">Über {{ $billing->cooperation?->name ?? 'Kooperation' }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="violet" icon="banknotes">Selbstzahler</flux:badge>
                            @endif
                            @if ($account->trashed())
                                <flux:badge size="sm" color="amber">Papierkorb</flux:badge>
                            @endif
                        </div>

                        <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                            <x-adminv2.master-data.card-row icon="identification" label="PDS-ID">
                                PDS-ID <span class="font-mono text-xs">{{ $account->pds_account_id ?? '–' }}</span>
                            </x-adminv2.master-data.card-row>
                            <x-adminv2.master-data.card-row icon="map-pin" label="PLZ und Stadt">
                                {{ trim($account->company_postal_code.' '.$account->company_city) ?: '–' }}
                            </x-adminv2.master-data.card-row>
                            <x-adminv2.master-data.card-row icon="envelope" label="E-Mail">
                                <span class="block truncate">{{ $account->email ?: '–' }}</span>
                            </x-adminv2.master-data.card-row>
                            <x-adminv2.master-data.card-row icon="calendar" label="Angelegt">
                                Angelegt {{ $account->created_at?->format('d.m.Y') ?? '–' }}
                            </x-adminv2.master-data.card-row>
                        </dl>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950 [&>div]:border-t-0">
            @include('livewire.admin-v2.customer-management.customers.partials.pagination', ['paginator' => $subAccounts, 'pageName' => 'subAccountsPage'])
        </div>
    @endif
</div>
