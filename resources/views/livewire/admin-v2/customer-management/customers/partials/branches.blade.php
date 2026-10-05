@php
    $branches = $this->branches;
    $branchFiltered = $branchSearch !== '' || $branchHeadquarters !== '';
    $sortHeader = 'livewire.admin-v2.customer-management.customers.partials.sort-header';
    $branchSortState = ['method' => 'sortBranches', 'sort' => $branchSort, 'direction' => $branchDirection];
@endphp

<x-adminv2.card heading="Filialen" flush>
    <x-slot:actions>
        <flux:button size="sm" icon="plus" wire:click="createBranch">Filiale hinzufügen</flux:button>
    </x-slot:actions>

    <div class="flex flex-wrap items-center gap-3 px-5 py-4">
        <div class="min-w-56 flex-1">
            <flux:input wire:model.live.debounce.300ms="branchSearch" size="sm" icon="magnifying-glass" placeholder="App-Code, Name oder Adresse suchen …" aria-label="Filialen durchsuchen" clearable />
        </div>
        <div class="w-48">
            <flux:select wire:model.live="branchHeadquarters" size="sm" aria-label="Hauptsitz">
                <flux:select.option value="">Alle Filialen</flux:select.option>
                <flux:select.option value="yes">Nur Hauptsitz</flux:select.option>
                <flux:select.option value="no">Keine Hauptsitze</flux:select.option>
            </flux:select>
        </div>
        @if (count($selectedBranches) > 0)
            <flux:button size="sm" variant="danger" icon="trash" wire:click="deleteSelectedBranches" wire:confirm="Möchten Sie die ausgewählten Filialen wirklich löschen?">
                Ausgewählte löschen ({{ count($selectedBranches) }})
            </flux:button>
        @endif
    </div>

    @if ($branches->isEmpty())
        @include('livewire.admin-v2.customer-management.customers.partials.empty', [
            'icon' => 'building-office',
            'heading' => 'Keine Filialen',
            'text' => $branchFiltered ? 'Zu Suche und Filter passt keine Filiale.' : 'Fügen Sie Filialen für diesen Kunden hinzu.',
        ])
    @else
        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="branchSearch, branchHeadquarters, sortBranches, deleteBranch, deleteSelectedBranches, saveBranch">
            <table class="w-full text-sm">
                <thead class="border-y border-zinc-100 bg-zinc-50 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900">
                    <tr>
                        <th scope="col" class="w-10 px-4 py-2.5"><span class="sr-only">Auswahl</span></th>
                        @include($sortHeader, $branchSortState + ['column' => 'app_code', 'label' => 'App-Code'])
                        @include($sortHeader, $branchSortState + ['column' => 'name', 'label' => 'Filialname'])
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Straße</th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Nr.</th>
                        @include($sortHeader, $branchSortState + ['column' => 'postal_code', 'label' => 'PLZ'])
                        @include($sortHeader, $branchSortState + ['column' => 'city', 'label' => 'Stadt'])
                        @include($sortHeader, $branchSortState + ['column' => 'is_headquarters', 'label' => 'Hauptsitz'])
                        @include($sortHeader, $branchSortState + ['column' => 'created_at', 'label' => 'Erstellt am'])
                        <th scope="col" class="px-4 py-2.5"><span class="sr-only">Aktionen</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($branches as $branch)
                        <tr wire:key="branch-{{ $branch->id }}" class="text-zinc-700 dark:text-zinc-300">
                            <td class="px-4 py-2.5">
                                <input type="checkbox" wire:model.live="selectedBranches" value="{{ $branch->id }}" aria-label="{{ $branch->name }} auswählen" class="size-4 rounded border-zinc-300 accent-[var(--color-accent)]" />
                            </td>
                            <td class="px-4 py-2.5"><flux:badge size="sm" color="blue" inset="top bottom" class="font-mono">{{ $branch->app_code }}</flux:badge></td>
                            <td class="px-4 py-2.5 font-medium text-zinc-900 dark:text-white">
                                {{ $branch->name }}
                                @if ($branch->additional) <span class="block text-xs font-normal text-zinc-500">{{ $branch->additional }}</span> @endif
                            </td>
                            <td class="px-4 py-2.5">{{ $branch->street ?: '–' }}</td>
                            <td class="px-4 py-2.5">{{ $branch->house_number ?: '–' }}</td>
                            <td class="px-4 py-2.5 tabular-nums">{{ $branch->postal_code ?: '–' }}</td>
                            <td class="px-4 py-2.5">{{ $branch->city ?: '–' }}</td>
                            <td class="px-4 py-2.5">
                                @if ($branch->is_headquarters)
                                    <flux:icon.check-circle variant="mini" class="text-green-600" /><span class="sr-only">Hauptsitz</span>
                                @else
                                    <flux:icon.x-circle variant="mini" class="text-zinc-300 dark:text-zinc-600" /><span class="sr-only">kein Hauptsitz</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">{{ $branch->created_at?->format('d.m.Y H:i') ?? '–' }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex justify-end gap-1">
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editBranch({{ $branch->id }})">Bearbeiten</flux:button>
                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteBranch({{ $branch->id }})" wire:confirm="Möchten Sie diese Filiale wirklich löschen?" aria-label="Filiale {{ $branch->name }} löschen" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('livewire.admin-v2.customer-management.customers.partials.pagination', ['paginator' => $branches, 'pageName' => 'branchesPage'])
    @endif
</x-adminv2.card>
