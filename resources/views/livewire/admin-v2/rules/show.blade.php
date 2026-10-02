@php
    use App\Models\NotificationRule;

    $rule = $this->rule;
    $customer = $rule->customer;
    $template = $this->template;
    $isTravelAlert = ($rule->source ?? NotificationRule::SOURCE_TRAVEL_ALERT) === NotificationRule::SOURCE_TRAVEL_ALERT;
    $customerName = $customer ? (trim((string) ($customer->company_name ?: $customer->name)) ?: $customer->email) : 'Gelöschter Kunde';
    $recipients = $rule->recipients->groupBy('recipient_type');
@endphp

<div class="flex flex-col gap-6">
    <div>
        <div class="flex flex-wrap items-center gap-3">
            <flux:heading size="xl" level="1">Regel „{{ $rule->name }}“</flux:heading>
            <flux:badge size="sm">{{ $isTravelAlert ? 'Travel Alert' : 'Global Travel Monitor' }}</flux:badge>
            @if ($rule->trashed())
                <flux:badge size="sm" color="red">Gelöscht</flux:badge>
            @elseif ($rule->is_active)
                <flux:badge size="sm" color="green">Aktiv</flux:badge>
            @else
                <flux:badge size="sm" color="amber">Deaktiviert</flux:badge>
            @endif
        </div>
        <flux:subheading>
            Benachrichtigungsregel von {{ $customerName }}@if ($customer?->pds_account_id) · Kundennummer {{ $customer->pds_account_id }}@endif.
            Die Regel pflegt der Kunde in seinem Bereich – hier nur zum Nachsehen.
        </flux:subheading>
    </div>

    @if ($customer && ! $customer->notifications_enabled)
        <flux:callout variant="warning" icon="exclamation-triangle" heading="Die Benachrichtigungen dieses Kunden sind ausgeschaltet.">
            <flux:callout.text>Solange das so ist, verschickt diese Regel nichts.</flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-2">
        <x-adminv2.card heading="Wann die Regel greift">
            <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                <dt class="text-zinc-500">Prioritäten</dt>
                <dd class="text-zinc-900 dark:text-white">
                    {{ empty($rule->risk_levels) ? 'alle' : collect($rule->risk_levels)->map(fn ($level) => NotificationRule::RISK_LEVELS[$level] ?? $level)->implode(', ') }}
                </dd>

                <dt class="text-zinc-500">Event-Typen</dt>
                <dd class="text-zinc-900 dark:text-white">{{ $this->categoryNames === [] ? 'alle' : implode(', ', $this->categoryNames) }}</dd>

                <dt class="text-zinc-500">Länder</dt>
                <dd class="text-zinc-900 dark:text-white">
                    @if ($isTravelAlert)
                        über die Reisen des Kunden – eine Mail geht nur raus, wenn eine Reise betroffen ist
                    @else
                        {{ $this->countryNames === [] ? 'alle' : implode(', ', $this->countryNames) }}
                    @endif
                </dd>

                <dt class="text-zinc-500">Angelegt</dt>
                <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $rule->created_at?->format('d.m.Y H:i') ?? '–' }}</dd>

                <dt class="text-zinc-500">Zuletzt geändert</dt>
                <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $rule->updated_at?->format('d.m.Y H:i') ?? '–' }}</dd>
            </dl>
        </x-adminv2.card>

        <x-adminv2.card heading="Empfänger und Vorlage">
            <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                @foreach (['to' => 'An', 'cc' => 'Kopie (CC)', 'bcc' => 'Blindkopie (BCC)'] as $type => $label)
                    @if ($recipients->has($type))
                        <dt class="text-zinc-500">{{ $label }}</dt>
                        <dd class="break-all text-zinc-900 dark:text-white">{{ $recipients[$type]->pluck('email')->implode(', ') }}</dd>
                    @endif
                @endforeach

                @if ($rule->recipients->isEmpty())
                    <dt class="text-zinc-500">An</dt>
                    <dd class="font-medium text-amber-600 dark:text-amber-400">Kein Empfänger hinterlegt</dd>
                @endif

                <dt class="text-zinc-500">Vorlage</dt>
                <dd class="text-zinc-900 dark:text-white">
                    @if ($template)
                        {{ $template->name }}
                        <span class="text-zinc-500">({{ $template->is_system ? 'Standard-Vorlage' : 'eigene Vorlage des Kunden' }})</span>
                    @else
                        <span class="font-medium text-amber-600 dark:text-amber-400">Keine Vorlage gefunden</span>
                    @endif
                </dd>

                @if ($template)
                    <dt class="text-zinc-500">Betreff</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $template->subject }}</dd>
                @endif
            </dl>
        </x-adminv2.card>
    </div>

    @php $logs = $this->logs; @endphp
    <x-adminv2.card
        heading="Versand dieser Regel"
        :description="$logs->total() === 0 ? 'Mit dieser Regel wurde noch nichts verschickt.' : $logs->total().' '.($logs->total() === 1 ? 'Mail' : 'Mails').' insgesamt, neueste zuerst'"
        flush
    >
        @if ($logs->total() > 0)
            <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="gotoPage, nextPage, previousPage">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="border-b border-zinc-100 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:border-zinc-800">
                        <tr>
                            <th class="px-3 py-3 ps-5 font-medium">Gesendet</th>
                            <th class="px-3 py-3 font-medium">Ereignis</th>
                            <th class="px-3 py-3 font-medium">Empfänger</th>
                            <th class="px-3 py-3 font-medium">Reisen</th>
                            <th class="px-3 py-3 pe-5 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($logs as $log)
                            <tr wire:key="log-{{ $log['id'] }}-{{ $log['at'] }}" class="align-top">
                                <td class="px-3 py-3 ps-5 whitespace-nowrap tabular-nums text-zinc-900 dark:text-white">{{ $log['at'] }}</td>
                                <td class="px-3 py-3">
                                    @if ($log['event_id'])
                                        <a href="{{ route('adminv2.events.edit', $log['event_id']) }}" target="_blank" class="text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $log['event_title'] }}</a>
                                    @else
                                        <span class="text-zinc-700 dark:text-zinc-300">{{ $log['event_title'] }}</span>
                                    @endif
                                    <div class="mt-0.5 text-xs text-zinc-500">{{ $log['subject'] }}</div>
                                </td>
                                <td class="px-3 py-3 break-all text-zinc-700 dark:text-zinc-300">{{ $log['recipient'] }}</td>
                                <td class="px-3 py-3 tabular-nums text-zinc-700 dark:text-zinc-300">{{ (int) $log['trips_count'] > 0 ? $log['trips_count'] : '–' }}</td>
                                <td class="px-3 py-3 pe-5">
                                    @if ($log['status'] === 'sent')
                                        <flux:badge color="green" size="sm" inset="top bottom">Versendet</flux:badge>
                                    @else
                                        <flux:badge color="red" size="sm" inset="top bottom">Fehlgeschlagen</flux:badge>
                                        @if ($log['error'])
                                            <div class="mt-1 max-w-xs text-xs text-red-700 dark:text-red-400">{{ \Illuminate\Support\Str::limit($log['error'], 160) }}</div>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 px-5 py-3 text-sm text-zinc-500 dark:border-zinc-800">
                <span class="tabular-nums">{{ $logs->firstItem() }}–{{ $logs->lastItem() }} von {{ $logs->total() }}</span>

                @if ($logs->hasPages())
                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="previousPage" :disabled="$logs->onFirstPage()">Zurück</flux:button>
                        <span class="tabular-nums">Seite {{ $logs->currentPage() }} von {{ $logs->lastPage() }}</span>
                        <flux:button size="sm" variant="ghost" icon-trailing="chevron-right" wire:click="nextPage" :disabled="! $logs->hasMorePages()">Weiter</flux:button>
                    </div>
                @endif
            </div>
        @endif
    </x-adminv2.card>
</div>
