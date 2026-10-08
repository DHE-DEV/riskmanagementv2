@php
    $stats = $this->stats;
    $workerRunning = $this->workerRunning;
    $pendingJobs = $this->pendingJobs;
    $failedJobs = $this->failedJobs;
    $pendingSelected = count($selectedPending);
    $failedSelected = count($selectedFailed);

    $th = 'px-4 py-2.5 text-start text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400';
    $td = 'px-4 py-2.5 align-top text-sm text-zinc-700 dark:text-zinc-300';
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Automator</flux:heading>
            <flux:subheading>System · Die Warteschlange der Hintergrund-Jobs: was wartet, was fehlgeschlagen ist, und ob der Worker läuft.</flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button icon="arrow-path" wire:click="refresh">Aktualisieren</flux:button>
            <flux:button variant="primary" icon="chart-bar" :href="route('adminv2.system.automator.monitor')">Automator Monitor</flux:button>
        </div>
    </div>

    {{-- Worker --}}
    <div @class([
        'flex flex-wrap items-center justify-between gap-3 rounded-2xl border px-5 py-4',
        'border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950/40' => $workerRunning,
        'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/40' => ! $workerRunning,
    ])>
        <div class="flex items-center gap-3 text-sm">
            <span @class(['size-3 rounded-full', 'bg-green-500 animate-pulse' => $workerRunning, 'bg-amber-500' => ! $workerRunning])></span>
            @if ($workerRunning)
                <span class="font-semibold text-green-800 dark:text-green-300">Worker aktiv</span>
                @if ($stats['processing'] > 0)
                    <span class="text-green-700 dark:text-green-400">{{ $stats['processing'] }} {{ $stats['processing'] === 1 ? 'Job wird' : 'Jobs werden' }} gerade verarbeitet</span>
                @endif
            @else
                <span class="font-semibold text-amber-800 dark:text-amber-300">Worker inaktiv</span>
                <span class="text-amber-700 dark:text-amber-400">Jobs werden nicht automatisch verarbeitet.</span>
            @endif
        </div>

        @if ($stats['pending'] > 0)
            <div class="flex flex-wrap items-center gap-2">
                <flux:button size="sm" icon="play" wire:click="processNext">Nächsten Job verarbeiten</flux:button>
                <flux:button size="sm" variant="primary" icon="forward" wire:click="processAll" wire:confirm="Alle {{ $stats['pending'] }} wartenden Jobs jetzt verarbeiten? Das kann einige Zeit dauern.">Alle verarbeiten</flux:button>
            </div>
        @endif
    </div>

    {{-- Zahlen --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <x-adminv2.card>
            <p class="text-sm text-zinc-500">Wartende Jobs</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ number_format($stats['pending'], 0, ',', '.') }}</p>
        </x-adminv2.card>
        <x-adminv2.card>
            <p class="text-sm text-zinc-500">Fehlgeschlagen</p>
            <p @class(['mt-1 text-3xl font-semibold tabular-nums', 'text-red-600 dark:text-red-400' => $stats['failed'] > 0, 'text-zinc-900 dark:text-white' => $stats['failed'] === 0])>{{ number_format($stats['failed'], 0, ',', '.') }}</p>
        </x-adminv2.card>
        <x-adminv2.card>
            <p class="text-sm text-zinc-500">Warteschlangen</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ count($stats['queues']) }}</p>
            @if ($stats['queues'] !== [])
                <p class="mt-1 text-xs text-zinc-500">
                    @foreach ($stats['queues'] as $queue => $count)
                        <span class="whitespace-nowrap">{{ $queue }} ({{ $count }})</span>@if (! $loop->last), @endif
                    @endforeach
                </p>
            @endif
        </x-adminv2.card>
    </div>

    {{-- Reiter --}}
    <div class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1">
        @foreach ($this->tabs() as $key => $label)
            @php $count = $stats[$key]; @endphp
            <button
                type="button"
                wire:click="$set('tab', '{{ $key }}')"
                wire:key="tab-{{ $key }}"
                @class([
                    'inline-flex shrink-0 items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-medium transition',
                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $tab === $key,
                    'text-zinc-600 hover:bg-zinc-200/60 dark:text-zinc-400 dark:hover:bg-zinc-800' => $tab !== $key,
                ])
            >
                {{ $label }}
                <span @class([
                    'rounded-full px-1.5 text-xs tabular-nums',
                    'bg-white/20 dark:bg-zinc-900/10' => $tab === $key,
                    'bg-zinc-200 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' => $tab !== $key && ! ($key === 'failed' && $count > 0),
                    'bg-red-100 text-red-700 dark:bg-red-400/20 dark:text-red-300' => $tab !== $key && $key === 'failed' && $count > 0,
                ])>{{ number_format($count, 0, ',', '.') }}</span>
            </button>
        @endforeach
    </div>

    @if ($tab === 'pending')
        <x-adminv2.card flush>
            @if ($pendingJobs === [])
                <div class="flex flex-col items-center gap-2 px-4 py-14 text-center">
                    <flux:icon.check-circle class="size-8 text-green-500" />
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine wartenden Jobs</p>
                    <p class="text-sm text-zinc-500">Alle Aufgaben wurden abgearbeitet.</p>
                </div>
            @else
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-4 py-3 text-sm dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <flux:checkbox wire:click="togglePendingPage" :checked="$pendingSelected > 0 && $pendingSelected === count($pendingJobs)" label="Alle auswählen" />
                        @if ($pendingSelected > 0)
                            <span class="text-zinc-500 tabular-nums">{{ $pendingSelected }} ausgewählt</span>
                        @endif
                    </div>
                    @if ($pendingSelected > 0)
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="deleteSelectedPending" wire:confirm="{{ $pendingSelected }} {{ $pendingSelected === 1 ? 'Job' : 'Jobs' }} aus der Warteschlange entfernen?">Ausgewählte entfernen</flux:button>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b border-zinc-100 dark:border-zinc-800">
                            <tr>
                                <th class="{{ $th }} w-10"></th>
                                <th class="{{ $th }}">ID</th>
                                <th class="{{ $th }}">Job</th>
                                <th class="{{ $th }}">Warteschlange</th>
                                <th class="{{ $th }}">Versuche</th>
                                <th class="{{ $th }}">Erstellt</th>
                                <th class="{{ $th }}">Verfügbar ab</th>
                                <th class="{{ $th }}"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($pendingJobs as $job)
                                <tr wire:key="pending-{{ $job['id'] }}" @class(['bg-sky-50 dark:bg-sky-950/30' => in_array((string) $job['id'], $selectedPending, true)])>
                                    <td class="{{ $td }}"><flux:checkbox wire:model.live="selectedPending" value="{{ $job['id'] }}" aria-label="Job {{ $job['id'] }} auswählen" /></td>
                                    <td class="{{ $td }} font-mono text-xs tabular-nums">{{ $job['id'] }}</td>
                                    <td class="{{ $td }} font-medium text-zinc-900 dark:text-white">
                                        {{ $job['name'] }}
                                        @if ($job['reserved'])
                                            <flux:badge size="sm" color="green" inset="top bottom" class="ms-1">in Arbeit</flux:badge>
                                        @endif
                                    </td>
                                    <td class="{{ $td }}"><flux:badge size="sm" color="sky" inset="top bottom">{{ $job['queue'] }}</flux:badge></td>
                                    <td class="{{ $td }} tabular-nums">{{ $job['attempts'] }}</td>
                                    <td class="{{ $td }} whitespace-nowrap text-xs tabular-nums">{{ $job['created_at'] }}</td>
                                    <td class="{{ $td }} whitespace-nowrap text-xs tabular-nums">{{ $job['available_at'] }}</td>
                                    <td class="{{ $td }} text-end">
                                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="deletePending({{ $job['id'] }})" wire:confirm="Job {{ $job['id'] }} ({{ $job['name'] }}) aus der Warteschlange entfernen?" aria-label="Job entfernen" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-adminv2.card>
    @else
        @if ($failedJobs !== [])
            <div class="flex flex-wrap items-center justify-end gap-2">
                <flux:modal.trigger name="automator-retry-all">
                    <flux:button size="sm" icon="arrow-path">Alle erneut versuchen</flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="automator-flush">
                    <flux:button size="sm" variant="ghost" icon="trash" class="!text-red-600">Alle fehlgeschlagenen löschen …</flux:button>
                </flux:modal.trigger>
            </div>
        @endif

        <x-adminv2.card flush>
            @if ($failedJobs === [])
                <div class="flex flex-col items-center gap-2 px-4 py-14 text-center">
                    <flux:icon.check-circle class="size-8 text-green-500" />
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine fehlgeschlagenen Jobs</p>
                </div>
            @else
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-4 py-3 text-sm dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <flux:checkbox wire:click="toggleFailedPage" :checked="$failedSelected > 0 && $failedSelected === count($failedJobs)" label="Alle auswählen" />
                        @if ($failedSelected > 0)
                            <span class="text-zinc-500 tabular-nums">{{ $failedSelected }} ausgewählt</span>
                        @endif
                    </div>
                    @if ($failedSelected > 0)
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button size="sm" icon="arrow-path" wire:click="retrySelectedFailed" wire:confirm="{{ $failedSelected }} {{ $failedSelected === 1 ? 'Job' : 'Jobs' }} erneut versuchen?">Ausgewählte erneut versuchen</flux:button>
                            <flux:button size="sm" variant="danger" icon="trash" wire:click="deleteSelectedFailed" wire:confirm="{{ $failedSelected }} fehlgeschlagene {{ $failedSelected === 1 ? 'Job' : 'Jobs' }} löschen?">Ausgewählte löschen</flux:button>
                        </div>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b border-zinc-100 dark:border-zinc-800">
                            <tr>
                                <th class="{{ $th }} w-10"></th>
                                <th class="{{ $th }}">Job</th>
                                <th class="{{ $th }}">Warteschlange</th>
                                <th class="{{ $th }}">Fehlgeschlagen</th>
                                <th class="{{ $th }}">Fehler</th>
                                <th class="{{ $th }}"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($failedJobs as $job)
                                <tr wire:key="failed-{{ $job['uuid'] }}" @class(['bg-red-50 dark:bg-red-950/30' => in_array($job['uuid'], $selectedFailed, true)])>
                                    <td class="{{ $td }}"><flux:checkbox wire:model.live="selectedFailed" value="{{ $job['uuid'] }}" aria-label="Job {{ $job['name'] }} auswählen" /></td>
                                    <td class="{{ $td }}">
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $job['name'] }}</span>
                                        <span class="block font-mono text-xs text-zinc-500">{{ \Illuminate\Support\Str::limit($job['uuid'], 8, '') }}</span>
                                    </td>
                                    <td class="{{ $td }}"><flux:badge size="sm" color="red" inset="top bottom">{{ $job['queue'] }}</flux:badge></td>
                                    <td class="{{ $td }} whitespace-nowrap text-xs tabular-nums">{{ $job['failed_at'] }}</td>
                                    <td class="{{ $td }} max-w-md break-words text-xs text-red-600 dark:text-red-400">{{ $job['exception'] }}</td>
                                    <td class="{{ $td }} whitespace-nowrap text-end">
                                        <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="retry('{{ $job['uuid'] }}')" aria-label="Erneut versuchen" />
                                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteFailed('{{ $job['uuid'] }}')" wire:confirm="Fehlgeschlagenen Job {{ $job['name'] }} löschen?" aria-label="Löschen" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-adminv2.card>
    @endif

    <flux:modal name="automator-retry-all" class="md:w-[30rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Alle fehlgeschlagenen Jobs erneut versuchen</flux:heading>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Alle {{ $stats['failed'] }} fehlgeschlagenen Jobs werden wieder in die Warteschlange gestellt.</p>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" icon="arrow-path" wire:click="retryAll">Erneut versuchen</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="automator-flush" class="md:w-[30rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Alle fehlgeschlagenen Jobs löschen?</flux:heading>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Alle {{ $stats['failed'] }} fehlgeschlagenen Jobs werden endgültig entfernt.</p>
                <p class="mt-2 text-sm font-medium text-red-600 dark:text-red-400">Das lässt sich nicht rückgängig machen.</p>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="danger" icon="trash" wire:click="flushFailed">Alle löschen</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
