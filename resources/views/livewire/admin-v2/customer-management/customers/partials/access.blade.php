@php
    use App\Livewire\AdminV2\CustomerManagement\Customers\Editor;

    $accounts = $this->accessibleAccounts;
    $candidates = $this->accessCandidates;
@endphp

<x-adminv2.card heading="Zugriff auf andere Accounts" description="Accounts, mit denen dieser Kunde zusätzlich zu seinem eigenen arbeiten darf." flush>
    {{-- Account-Zugriff hinzufuegen: Suche ueber Firma, E-Mail und App-Code --}}
    <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
        <flux:field>
            <flux:label>Account-Zugriff hinzufügen</flux:label>
            <flux:input wire:model.live.debounce.300ms="accessCandidateSearch" size="sm" icon="plus" placeholder="Firma, E-Mail oder App-Code des Accounts (mind. 2 Zeichen) …" clearable />
        </flux:field>

        @if (mb_strlen(trim($accessCandidateSearch)) >= 2)
            <ul class="mt-2 flex flex-col divide-y divide-zinc-100 rounded-xl border border-zinc-200 text-sm dark:divide-zinc-800 dark:border-zinc-700">
                @forelse ($candidates as $candidate)
                    <li wire:key="access-candidate-{{ $candidate->id }}" class="flex items-center justify-between gap-3 px-3 py-2">
                        <span class="min-w-0 truncate">
                            <span class="font-mono text-xs text-zinc-500">{{ $candidate->app_code }}</span>
                            {{ Editor::accountLabel($candidate) }}
                        </span>
                        <flux:button size="sm" icon="plus" wire:click="attachAccess({{ $candidate->id }})">Hinzufügen</flux:button>
                    </li>
                @empty
                    <li class="px-3 py-2 text-zinc-500">Kein weiterer Account gefunden.</li>
                @endforelse
            </ul>
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-3 px-5 py-4">
        <div class="min-w-56 flex-1">
            <flux:input wire:model.live.debounce.300ms="accessSearch" size="sm" icon="magnifying-glass" placeholder="Code, Firma oder E-Mail suchen …" aria-label="Account-Zugriffe durchsuchen" clearable />
        </div>
        @if (count($selectedAccess) > 0)
            <flux:button size="sm" variant="danger" icon="x-mark" wire:click="detachSelectedAccess" wire:confirm="Die ausgewählten Account-Zugriffe entfernen?">
                Ausgewählte entfernen ({{ count($selectedAccess) }})
            </flux:button>
        @endif
    </div>

    @if ($accounts->isEmpty())
        @include('livewire.admin-v2.customer-management.customers.partials.empty', [
            'icon' => 'user-group',
            'heading' => 'Keine Account-Zugriffe',
            'text' => $accessSearch !== '' ? 'Zur Suche passt kein Account-Zugriff.' : 'Dieser Kunde hat nur Zugriff auf seinen eigenen Account.',
        ])
    @else
        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="accessSearch, attachAccess, detachAccess, detachSelectedAccess">
            <table class="w-full text-sm">
                <thead class="border-y border-zinc-100 bg-zinc-50 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900">
                    <tr>
                        <th scope="col" class="w-10 px-4 py-2.5"><span class="sr-only">Auswahl</span></th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Code</th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Firma</th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">E-Mail</th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Stadt</th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Berechtigt seit</th>
                        <th scope="col" class="px-4 py-2.5"><span class="sr-only">Aktionen</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($accounts as $account)
                        <tr wire:key="access-{{ $account->id }}" class="text-zinc-700 dark:text-zinc-300">
                            <td class="px-4 py-2.5">
                                <input type="checkbox" wire:model.live="selectedAccess" value="{{ $account->id }}" aria-label="{{ Editor::accountLabel($account) }} auswählen" class="size-4 rounded border-zinc-300 accent-[var(--color-accent)]" />
                            </td>
                            <td class="px-4 py-2.5"><flux:badge size="sm" color="sky" inset="top bottom" class="font-mono">{{ $account->app_code }}</flux:badge></td>
                            <td class="px-4 py-2.5 font-medium text-zinc-900 dark:text-white">
                                <a href="{{ route('adminv2.customer-management.customers.edit', $account->id) }}" class="underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900">{{ $account->company_name ?: '–' }}</a>
                            </td>
                            <td class="px-4 py-2.5">{{ $account->email }}</td>
                            <td class="px-4 py-2.5">{{ $account->company_city ?: '–' }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">{{ $account->pivot->created_at?->format('d.m.Y H:i') ?? '–' }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex justify-end">
                                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="detachAccess({{ $account->id }})" wire:confirm="Diesen Account-Zugriff entfernen?">Entfernen</flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-adminv2.card>
