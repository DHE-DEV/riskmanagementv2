@php
    use App\Livewire\AdminV2\System\Automator\Monitor;

    $stats = $this->stats;
    $logs = $this->logs;
    $statusLabels = ['completed' => ['Abgeschlossen', 'green'], 'failed' => ['Fehlgeschlagen', 'red'], 'running' => ['Läuft …', 'sky']];

    $th = 'px-4 py-2.5 text-start text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400';
    $td = 'px-4 py-2.5 align-top text-sm text-zinc-700 dark:text-zinc-300';
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('adminv2.system.automator.index') }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Automator
            </a>
            <flux:heading size="xl" level="1" class="mt-1">Automator Monitor</flux:heading>
            <flux:subheading>Durchläufe der Benachrichtigungs-Warteschlangen und des Travel-Link-Syncs.</flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button icon="arrow-path" wire:click="refresh">Aktualisieren</flux:button>
            <flux:modal.trigger name="monitor-run-gtm">
                <flux:button icon="play">GTM jetzt ausführen</flux:button>
            </flux:modal.trigger>
            <flux:modal.trigger name="monitor-run-travel-alert">
                <flux:button icon="play">Travel Alert jetzt ausführen</flux:button>
            </flux:modal.trigger>
            <flux:modal.trigger name="monitor-sync-travel-links">
                <flux:button icon="link">Travel Links Sync</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    {{-- Zahlen des Tages je Warteschlange --}}
    <div class="grid gap-4 lg:grid-cols-3">
        @foreach (Monitor::QUEUES as $queue => [$label, $color])
            @php
                $stat = $stats[$queue];
                $lastRun = $stat['last_run'];
                $isSync = $queue === 'travel-link-sync';
            @endphp
            <x-adminv2.card wire:key="stat-{{ $queue }}">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span @class(['size-2.5 rounded-full', 'bg-green-500 animate-pulse' => $lastRun?->status === 'running', 'bg-zinc-400' => $lastRun?->status !== 'running'])></span>
                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $label }}</span>
                    </div>
                    <span class="text-xs text-zinc-500">alle {{ $stat['interval'] }} Min.</span>
                </div>

                <dl class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-zinc-50 px-2 py-2 dark:bg-zinc-900">
                        <dd class="text-xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $isSync ? $stat['processed'] : $stat['runs'] }}</dd>
                        <dt class="text-[0.65rem] text-zinc-500">{{ $isSync ? 'Kunden heute' : 'Durchläufe heute' }}</dt>
                    </div>
                    <div class="rounded-lg bg-green-50 px-2 py-2 dark:bg-green-950/40">
                        <dd class="text-xl font-semibold tabular-nums text-green-800 dark:text-green-300">{{ $stat['sent'] }}</dd>
                        <dt class="text-[0.65rem] text-zinc-500">{{ $isSync ? 'Reisen sync. heute' : 'Gesendet heute' }}</dt>
                    </div>
                    <div @class(['rounded-lg px-2 py-2', 'bg-red-50 dark:bg-red-950/40' => $stat['errors'] > 0, 'bg-zinc-50 dark:bg-zinc-900' => $stat['errors'] === 0])>
                        <dd @class(['text-xl font-semibold tabular-nums', 'text-red-600 dark:text-red-400' => $stat['errors'] > 0, 'text-zinc-500' => $stat['errors'] === 0])>{{ $stat['errors'] }}</dd>
                        <dt class="text-[0.65rem] text-zinc-500">Fehler heute</dt>
                    </div>
                </dl>

                <p class="mt-3 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                    @if ($lastRun)
                        <span>Letzter Lauf {{ $lastRun->started_at->format('d.m.Y H:i:s') }}@if ($lastRun->duration) ({{ $lastRun->duration }})@endif</span>
                        @php [$statusLabel, $statusColor] = $statusLabels[$lastRun->status] ?? [$lastRun->status, 'zinc']; @endphp
                        <flux:badge size="sm" :color="$statusColor" inset="top bottom">{{ $statusLabel }}</flux:badge>
                    @else
                        <span>Noch kein Durchlauf</span>
                    @endif
                </p>
            </x-adminv2.card>
        @endforeach
    </div>

    {{-- Reiter --}}
    <div class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1">
        @foreach ($this->tabs() as $key => $label)
            <button
                type="button"
                wire:click="$set('tab', '{{ $key }}')"
                wire:key="tab-{{ $key }}"
                @class([
                    'inline-flex shrink-0 items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-medium transition',
                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $tab === $key,
                    'text-zinc-600 hover:bg-zinc-200/60 dark:text-zinc-400 dark:hover:bg-zinc-800' => $tab !== $key,
                ])
            >{{ $label }}</button>
        @endforeach
    </div>

    <x-adminv2.card flush>
        @if ($logs->isEmpty())
            <div class="flex flex-col items-center gap-2 px-4 py-14 text-center">
                <flux:icon.clock class="size-8 text-zinc-300 dark:text-zinc-600" />
                <p class="text-sm font-medium text-zinc-900 dark:text-white">Noch keine Durchläufe protokolliert</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-zinc-100 dark:border-zinc-800">
                        <tr>
                            <th class="{{ $th }}">Warteschlange</th>
                            <th class="{{ $th }}">Gestartet</th>
                            <th class="{{ $th }}">Abgeschlossen</th>
                            <th class="{{ $th }}">Dauer</th>
                            <th class="{{ $th }} text-end">{{ $tab === 'travel-link-sync' ? 'Kunden' : 'Ereignisse' }}</th>
                            <th class="{{ $th }} text-end">{{ $tab === 'travel-link-sync' ? 'Reisen sync.' : 'Gesendet' }}</th>
                            <th class="{{ $th }} text-end">Fehler</th>
                            <th class="{{ $th }}">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($logs as $log)
                            @php
                                [$queueLabel, $queueColor] = Monitor::QUEUES[$log->queue_name] ?? [$log->queue_name, 'zinc'];
                                [$statusLabel, $statusColor] = $statusLabels[$log->status] ?? [$log->status, 'zinc'];
                            @endphp
                            <tr wire:key="log-{{ $log->id }}" @class(['bg-red-50 dark:bg-red-950/30' => $log->status === 'failed'])>
                                <td class="{{ $td }}"><flux:badge size="sm" :color="$queueColor" inset="top bottom">{{ $queueLabel }}</flux:badge></td>
                                <td class="{{ $td }} whitespace-nowrap text-xs tabular-nums">{{ $log->started_at?->format('d.m.Y H:i:s') ?? '–' }}</td>
                                <td class="{{ $td }} whitespace-nowrap text-xs tabular-nums">{{ $log->completed_at?->format('d.m.Y H:i:s') ?? '–' }}</td>
                                <td class="{{ $td }} tabular-nums">{{ $log->duration ?? '–' }}</td>
                                <td class="{{ $td }} text-end tabular-nums">{{ $log->events_processed }}</td>
                                <td @class([$td, 'text-end tabular-nums', 'font-semibold text-green-700 dark:text-green-400' => $log->notifications_sent > 0])>{{ $log->notifications_sent }}</td>
                                <td @class([$td, 'text-end tabular-nums', 'font-semibold text-red-600 dark:text-red-400' => $log->errors > 0])>{{ $log->errors }}</td>
                                <td class="{{ $td }}"><flux:badge size="sm" :color="$statusColor" inset="top bottom">{{ $statusLabel }}</flux:badge></td>
                            </tr>
                            @if ($log->error_message)
                                <tr wire:key="log-error-{{ $log->id }}" class="bg-red-50 dark:bg-red-950/30">
                                    <td colspan="8" class="px-4 pb-2.5 text-xs text-red-600 dark:text-red-400"><span class="font-semibold">Fehler:</span> {{ \Illuminate\Support\Str::limit($log->error_message, 300) }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
                <x-adminv2.pagination :paginator="$logs" />
            </div>
        @endif
    </x-adminv2.card>

    <p class="text-xs text-zinc-500">
        <span class="font-semibold">Konfiguration (.env):</span>
        GTM alle {{ config('notifications.gtm_interval') }} Min. · Travel Alert alle {{ config('notifications.travel_alert_interval') }} Min. · Travel Link Sync alle {{ config('notifications.travel_links_sync_interval') }} Min. · Rückblick {{ config('notifications.lookback_hours') }} h
    </p>

    <flux:modal name="monitor-run-gtm" class="md:w-[30rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">GTM jetzt ausführen</flux:heading>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Die Benachrichtigungen des Global Travel Monitor werden außer der Reihe sofort verarbeitet.</p>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" icon="play" wire:click="runGtm">Jetzt ausführen</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="monitor-run-travel-alert" class="md:w-[30rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Travel Alert jetzt ausführen</flux:heading>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Die Benachrichtigungen des Travel Alert werden außer der Reihe sofort verarbeitet.</p>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" icon="play" wire:click="runTravelAlert">Jetzt ausführen</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="monitor-sync-travel-links" class="md:w-[30rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Travel Links synchronisieren</flux:heading>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Für alle Kunden mit aktivierten Travel Links wird ein Sync-Job in die Warteschlange gestellt.</p>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" icon="link" wire:click="syncTravelLinks">Sync starten</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
