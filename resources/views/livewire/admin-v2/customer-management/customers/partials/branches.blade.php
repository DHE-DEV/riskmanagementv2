@php
    $branches = $this->branches;
    $branchFiltered = $branchSearch !== '' || $branchHeadquarters !== '';
@endphp

<div class="flex flex-col gap-4">
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
            <div class="flex items-center gap-1">
                <div class="w-40">
                    <flux:select wire:model.live="branchSort" size="sm" aria-label="Sortieren nach">
                        @foreach ($this->branchSortOptions() as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:button size="sm" variant="ghost" :icon="$branchDirection === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'" wire:click="sortBranches('{{ $branchSort }}')" :aria-label="$branchDirection === 'asc' ? 'Aufsteigend – absteigend sortieren' : 'Absteigend – aufsteigend sortieren'" />
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
        @endif
    </x-adminv2.card>

    @if ($branches->isNotEmpty())
        {{-- Karten unter dem Kasten: je nach verfuegbarer Breite 1, 2 oder hoechstens 3 nebeneinander --}}
        <div class="@container" wire:loading.class="opacity-60" wire:target="branchSearch, branchHeadquarters, branchSort, sortBranches, deleteBranch, deleteSelectedBranches, saveBranch, nextPage, previousPage">
            <div class="grid gap-4 @2xl:grid-cols-2 @5xl:grid-cols-3">
                @foreach ($branches as $branch)
                    <article wire:key="branch-{{ $branch->id }}" class="flex min-w-0 flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-start gap-2.5">
                                <input type="checkbox" wire:model.live="selectedBranches" value="{{ $branch->id }}" aria-label="{{ $branch->name }} auswählen" class="mt-1 size-4 shrink-0 rounded border-zinc-300 accent-[var(--color-accent)]" />
                                <div class="min-w-0">
                                    <h3 class="line-clamp-2 text-base font-semibold leading-snug text-zinc-900 dark:text-white">{{ $branch->name }}</h3>
                                    @if ($branch->additional)
                                        <p class="mt-0.5 truncate text-sm text-zinc-500">{{ $branch->additional }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="-me-1.5 -mt-1 flex shrink-0 gap-0.5">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editBranch({{ $branch->id }})" aria-label="Filiale {{ $branch->name }} bearbeiten" />
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteBranch({{ $branch->id }})" wire:confirm="Möchten Sie diese Filiale wirklich löschen?" aria-label="Filiale {{ $branch->name }} löschen" />
                            </div>
                        </div>

                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                            <flux:badge size="sm" color="blue" inset="top bottom" class="font-mono">{{ $branch->app_code }}</flux:badge>
                            @if ($branch->is_headquarters)
                                <flux:badge size="sm" color="green" inset="top bottom" icon="check-circle">Hauptsitz</flux:badge>
                            @endif
                        </div>

                        <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                            <x-adminv2.master-data.card-row icon="map-pin" label="Adresse">
                                {{ trim($branch->street.' '.$branch->house_number) ?: '–' }}<br>
                                {{ trim($branch->postal_code.' '.$branch->city) ?: '–' }}
                            </x-adminv2.master-data.card-row>
                            <x-adminv2.master-data.card-row icon="calendar" label="Erstellt am">
                                Erstellt {{ $branch->created_at?->format('d.m.Y H:i') ?? '–' }}
                            </x-adminv2.master-data.card-row>
                        </dl>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950 [&>div]:border-t-0">
            @include('livewire.admin-v2.customer-management.customers.partials.pagination', ['paginator' => $branches, 'pageName' => 'branchesPage'])
        </div>
    @endif
</div>
