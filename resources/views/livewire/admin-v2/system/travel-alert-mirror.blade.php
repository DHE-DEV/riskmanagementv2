@php
    use App\Services\TravelDetail\TravelAlertTripMirror;

    $summary = $this->summary;
    $delta = $this->delta;
    $customers = $this->customers;
    $stored = $this->storedTrips;
@endphp

<div class="flex flex-col gap-6" wire:poll.30s>
    <div>
        <flux:heading size="xl" level="1">Travel-Alert-Spiegel</flux:heading>
        <flux:subheading>System · pds-Konten, deren Reisen für den Travel Alert lokal gespiegelt werden. Ein Konto kommt beim ersten Öffnen von Travel Alert dazu und wird nach {{ TravelAlertTripMirror::INACTIVE_AFTER_DAYS }} Tagen ohne Aufruf entfernt.</flux:subheading>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500">Gespiegelte Konten</div>
            <div class="mt-1 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ number_format($summary['synced'], 0, ',', '.') }}</div>
            @if ($summary['waiting'] > 0)
                <div class="mt-1 text-xs text-amber-600">{{ $summary['waiting'] }} warten auf den ersten Vollabruf</div>
            @endif
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500">Gespiegelte Reisen</div>
            <div class="mt-1 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ number_format($summary['trips'], 0, ',', '.') }}</div>
            <div class="mt-1 text-xs text-zinc-500">Reisen, die heute oder später enden</div>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500">Delta-Feed (jede Minute)</div>
            <div class="mt-1 text-2xl font-semibold tabular-nums {{ $this->deltaIsStale() ? 'text-red-600' : 'text-zinc-900 dark:text-white' }}">
                {{ $delta->last_run_at ? $delta->last_run_at->diffForHumans() : '–' }}
            </div>
            <div class="mt-1 text-xs text-zinc-500">
                @if ($this->deltaIsStale())
                    <span class="text-red-600">Läuft nicht – Scheduler prüfen.</span>
                @else
                    Stand pds: {{ $delta->cursor_last_change_at?->format('d.m.Y H:i') ?? '–' }} UTC
                @endif
            </div>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500">Vollabgleich</div>
            <div class="mt-1 text-2xl font-semibold tabular-nums {{ $summary['stale'] > 0 ? 'text-red-600' : 'text-zinc-900 dark:text-white' }}">
                {{ $summary['stale'] > 0 ? $summary['stale'].' veraltet' : 'aktuell' }}
            </div>
            <div class="mt-1 text-xs text-zinc-500">nachts um 03:40, entfernt auch gelöschte Links</div>
        </div>
    </div>

    <section class="flex flex-col gap-3">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <flux:heading size="lg" level="2">Konten</flux:heading>
            <div class="w-full sm:w-72">
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Konto-ID, Kunde oder E-Mail" clearable />
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3">pds-Konto</th>
                        <th class="px-4 py-3">Kunden</th>
                        <th class="px-4 py-3 text-right">Reisen</th>
                        <th class="px-4 py-3">Zuletzt geöffnet</th>
                        <th class="px-4 py-3">Vollabruf</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->accounts as $account)
                        @php
                            $accountCustomers = $customers->get($account->pds_account_id) ?? collect();
                            $status = $this->status($account);
                        @endphp
                        <tr wire:key="mirror-{{ $account->pds_account_id }}" class="align-top">
                            <td class="whitespace-nowrap px-4 py-3 font-mono tabular-nums text-zinc-900 dark:text-white">{{ $account->pds_account_id }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-0.5">
                                    @forelse ($accountCustomers->take(3) as $customer)
                                        <a href="{{ route('adminv2.customer-management.customers.edit', $customer) }}" wire:navigate
                                            class="text-zinc-800 hover:underline dark:text-zinc-200">
                                            {{ $this->customerLabel($customer) }}
                                            <span class="text-xs text-zinc-400">#{{ $customer->id }}{{ $customer->assign_to ? ' · Unterkonto' : '' }}</span>
                                        </a>
                                    @empty
                                        <span class="text-zinc-400">kein Kunde mit diesem Konto</span>
                                    @endforelse
                                    @if ($accountCustomers->count() > 3)
                                        <span class="text-xs text-zinc-500">+ {{ $accountCustomers->count() - 3 }} weitere</span>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-zinc-700 dark:text-zinc-300">{{ number_format($stored[$account->pds_account_id] ?? 0, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums text-zinc-600 dark:text-zinc-400">
                                {{ $account->last_requested_at?->format('d.m.Y H:i') }}
                                <div class="text-xs text-zinc-400">entfällt ab {{ $account->last_requested_at?->copy()->addDays(TravelAlertTripMirror::INACTIVE_AFTER_DAYS)->format('d.m.Y') }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums text-zinc-600 dark:text-zinc-400">
                                {{ $account->full_synced_at?->format('d.m.Y H:i') ?? '–' }}
                                @if ($account->synced_from)
                                    <div class="text-xs text-zinc-400">Reisen ab {{ $account->synced_from->format('d.m.Y') }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @switch($status)
                                    @case('waiting')
                                        <flux:badge size="sm" color="amber">Wartet auf Vollabruf</flux:badge>
                                        @break
                                    @case('stale')
                                        <flux:badge size="sm" color="red">Veraltet</flux:badge>
                                        @break
                                    @default
                                        <flux:badge size="sm" color="emerald">Aktiv</flux:badge>
                                @endswitch
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-zinc-500">
                                {{ $search !== '' ? 'Kein Konto passt zur Suche.' : 'Noch keine Konten gespiegelt. Ein Konto kommt dazu, sobald ein Kunde Travel Alert öffnet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <flux:text class="text-xs">Ein Konto vorab spiegeln: <code>php artisan travel-alert:sync-trips --account=&lt;pds-Konto&gt;</code></flux:text>
    </section>
</div>
