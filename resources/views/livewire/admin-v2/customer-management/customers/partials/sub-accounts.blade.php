@php
    $subAccounts = $this->subAccounts;
@endphp

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

    <div class="flex flex-wrap items-center gap-3 px-5 py-4">
        <div class="min-w-56 flex-1">
            <flux:input wire:model.live.debounce.300ms="subAccountSearch" size="sm" icon="magnifying-glass" placeholder="Code, Firma, E-Mail, Stadt oder PDS-ID suchen …" aria-label="Unteraccounts durchsuchen" clearable />
        </div>
    </div>

    @if ($subAccounts->isEmpty())
        @include('livewire.admin-v2.customer-management.customers.partials.empty', [
            'icon' => 'building-storefront',
            'heading' => 'Keine Unteraccounts',
            'text' => $subAccountSearch !== '' ? 'Zur Suche passt kein Unteraccount.' : 'Diesem Kunden ist noch kein anderer Kunde zugeordnet.',
        ])
    @else
        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="subAccountSearch, nextPage, previousPage">
            <table class="w-full text-sm">
                <thead class="border-y border-zinc-100 bg-zinc-50 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Code</th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">PDS-ID</th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Firma</th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Stadt</th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">E-Mail</th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Angelegt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($subAccounts as $account)
                        <tr wire:key="sub-account-{{ $account->id }}" class="text-zinc-700 dark:text-zinc-300">
                            <td class="px-4 py-2.5"><flux:badge size="sm" color="sky" inset="top bottom" class="font-mono">{{ $account->app_code }}</flux:badge></td>
                            <td class="px-4 py-2.5 tabular-nums">{{ $account->pds_account_id ?? '–' }}</td>
                            <td class="px-4 py-2.5 font-medium text-zinc-900 dark:text-white">
                                <a href="{{ route('adminv2.customer-management.customers.edit', $account->id) }}" class="underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900">{{ $account->company_name ?: $account->name }}</a>
                                @if ($account->trashed())
                                    <flux:badge size="sm" color="amber" inset="top bottom" class="ms-1">Papierkorb</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-2.5">{{ $account->company_city ?: '–' }}</td>
                            <td class="px-4 py-2.5">{{ $account->email }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">{{ $account->created_at?->format('d.m.Y') ?? '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('livewire.admin-v2.customer-management.customers.partials.pagination', ['paginator' => $subAccounts, 'pageName' => 'subAccountsPage'])
    @endif
</x-adminv2.card>
