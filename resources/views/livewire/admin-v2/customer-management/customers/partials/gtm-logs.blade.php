@php
    use App\Livewire\AdminV2\CustomerManagement\Customers\Editor;
    use Illuminate\Support\Str;

    $logs = $this->gtmLogs;
    $sortHeader = 'livewire.admin-v2.customer-management.customers.partials.sort-header';
    $logSortState = ['method' => 'sortLogs', 'sort' => $logSort, 'direction' => $logDirection];
@endphp

<x-adminv2.card heading="GTM API Logs" description="Protokollierte Anfragen dieses Kunden an die Global Travel Monitor API." flush>
    <div class="flex flex-wrap items-center gap-3 px-5 py-4">
        <div class="min-w-56 flex-1">
            <flux:input wire:model.live.debounce.300ms="logSearch" size="sm" icon="magnifying-glass" placeholder="Endpunkt suchen …" aria-label="GTM API Logs durchsuchen" clearable />
        </div>
        <div class="w-56">
            <flux:select wire:model.live="logStatus" size="sm" aria-label="Status">
                <flux:select.option value="">Alle Status</flux:select.option>
                @foreach (Editor::LOG_STATUSES as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if ($logs->isEmpty())
        @include('livewire.admin-v2.customer-management.customers.partials.empty', [
            'icon' => 'document-magnifying-glass',
            'heading' => 'Keine API Requests',
            'text' => $logSearch !== '' || $logStatus !== '' ? 'Zu Suche und Filter passt keine Anfrage.' : 'Es wurden noch keine GTM API Anfragen protokolliert.',
        ])
    @else
        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="logSearch, logStatus, sortLogs">
            <table class="w-full text-sm">
                <thead class="border-y border-zinc-100 bg-zinc-50 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900">
                    <tr>
                        @include($sortHeader, $logSortState + ['column' => 'created_at', 'label' => 'Zeitpunkt'])
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Methode</th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Endpunkt</th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">Status</th>
                        @include($sortHeader, $logSortState + ['column' => 'response_time_ms', 'label' => 'Antwortzeit'])
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">IP-Adresse</th>
                        <th scope="col" class="px-4 py-2.5 text-start font-medium">User Agent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($logs as $log)
                        <tr wire:key="gtm-log-{{ $log->id }}" class="text-zinc-700 dark:text-zinc-300">
                            <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">{{ $log->created_at?->format('d.m.Y H:i:s') ?? '–' }}</td>
                            <td class="px-4 py-2.5"><flux:badge size="sm" color="sky" inset="top bottom" class="font-mono">{{ $log->method }}</flux:badge></td>
                            <td class="px-4 py-2.5 font-mono text-xs" title="{{ $log->endpoint }}">{{ Str::limit($log->endpoint, 50) }}</td>
                            <td class="px-4 py-2.5">
                                <flux:badge size="sm" inset="top bottom" :color="match (true) { $log->response_status < 300 => 'green', $log->response_status < 400 => 'amber', default => 'red' }">{{ $log->response_status }}</flux:badge>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">{{ $log->response_time_ms !== null ? $log->response_time_ms.' ms' : '–' }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs">{{ $log->ip_address ?: '–' }}</td>
                            <td class="px-4 py-2.5 text-xs" title="{{ $log->user_agent }}">{{ $log->user_agent ? Str::limit($log->user_agent, 30) : '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('livewire.admin-v2.customer-management.customers.partials.pagination', ['paginator' => $logs, 'pageName' => 'logsPage'])
    @endif
</x-adminv2.card>
