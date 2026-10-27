{{-- Tab „Mitarbeiter“: Zugaenge, die der Kunde selbst angelegt hat (nur Ansicht) --}}
@php
    $employees = $this->employees;
@endphp

<div class="flex flex-col gap-4">
    <x-adminv2.card heading="Mitarbeiter" :description="$this->employeeCount.' '.($this->employeeCount === 1 ? 'Zugang' : 'Zugänge').', die der Kunde unter Einstellungen › Benutzerverwaltung angelegt hat.'" flush>
        <div class="flex flex-wrap items-center gap-3 px-5 py-4">
            <div class="min-w-56 flex-1">
                <flux:input wire:model.live.debounce.300ms="employeeSearch" size="sm" icon="magnifying-glass" placeholder="Name, E-Mail, Position oder Personalnummer …" aria-label="Mitarbeiter durchsuchen" clearable />
            </div>
            <flux:select wire:model.live="employeeStatus" size="sm" class="w-40" aria-label="Nach Status filtern">
                <flux:select.option value="all">Alle</flux:select.option>
                <flux:select.option value="active">Aktiv</flux:select.option>
                <flux:select.option value="inactive">Inaktiv</flux:select.option>
            </flux:select>
        </div>

        @if ($employees->isEmpty())
            @include('livewire.admin-v2.customer-management.customers.partials.empty', [
                'icon' => 'users',
                'heading' => 'Keine Mitarbeiter',
                'text' => $employeeSearch !== '' || $employeeStatus !== 'all' ? 'Zu Suche und Filter passt kein Zugang.' : 'Der Kunde hat noch keine Mitarbeiter-Zugänge angelegt.',
            ])
        @endif
    </x-adminv2.card>

    @if ($employees->isNotEmpty())
        <div class="@container" wire:loading.class="opacity-60" wire:target="employeeSearch, employeeStatus, nextPage, previousPage">
            <div class="grid gap-4 @2xl:grid-cols-2 @5xl:grid-cols-3">
                @foreach ($employees as $employee)
                    @php
                        $active = $employee->isCurrentlyActive();
                        $name = trim($employee->first_name.' '.$employee->last_name) ?: $employee->email;
                    @endphp
                    <article wire:key="employee-{{ $employee->id }}" @class([
                        'flex min-w-0 flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950',
                        'opacity-70' => ! $active,
                    ])>
                        <h3 class="min-w-0 text-base font-semibold leading-snug text-zinc-900 dark:text-white">{{ trim(($employee->title ? $employee->title.' ' : '').$name) }}</h3>
                        @if ($employee->position || $employee->department)
                            <p class="text-sm text-zinc-500">{{ collect([$employee->position, $employee->department])->filter()->implode(' · ') }}</p>
                        @endif

                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <flux:badge size="sm" :color="$active ? 'green' : 'zinc'">{{ $active ? 'Aktiv' : 'Inaktiv' }}</flux:badge>
                            @if ($employee->provider)
                                <flux:badge size="sm" color="sky">Login {{ ucfirst($employee->provider) }}</flux:badge>
                            @endif
                            @if ($employee->branch)
                                <flux:badge size="sm" color="zinc">{{ $employee->branch->name }}</flux:badge>
                            @endif
                        </div>

                        <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                            <x-adminv2.master-data.card-row icon="envelope" label="E-Mail">
                                <a href="mailto:{{ $employee->email }}" class="block truncate underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900">{{ $employee->email ?: '–' }}</a>
                            </x-adminv2.master-data.card-row>
                            @if ($employee->phone || $employee->mobile)
                                <x-adminv2.master-data.card-row icon="phone" label="Telefon">
                                    {{ collect([$employee->phone, $employee->mobile])->filter()->implode(' · ') }}
                                </x-adminv2.master-data.card-row>
                            @endif
                            @if ($employee->personnel_number)
                                <x-adminv2.master-data.card-row icon="identification" label="Personalnummer">
                                    Personalnr. {{ $employee->personnel_number }}
                                </x-adminv2.master-data.card-row>
                            @endif
                            @if ($employee->active_from || $employee->active_until)
                                <x-adminv2.master-data.card-row icon="calendar" label="Gültig">
                                    {{ $employee->active_from?->format('d.m.Y') ?? 'offen' }} – {{ $employee->active_until?->format('d.m.Y') ?? 'offen' }}
                                </x-adminv2.master-data.card-row>
                            @endif
                            <x-adminv2.master-data.card-row icon="clock" label="Angelegt">
                                Angelegt {{ $employee->created_at?->format('d.m.Y') ?? '–' }}
                            </x-adminv2.master-data.card-row>
                        </dl>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950 [&>div]:border-t-0">
            @include('livewire.admin-v2.customer-management.customers.partials.pagination', ['paginator' => $employees, 'pageName' => 'employeesPage'])
        </div>
    @endif
</div>
