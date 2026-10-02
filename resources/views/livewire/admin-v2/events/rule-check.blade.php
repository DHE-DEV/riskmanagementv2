@php
    use App\Support\AdminV2\EventState;

    $event = $this->event;
    $state = $this->state;
    $customers = $this->customers;
    $delivered = in_array($state, [EventState::Live, EventState::Scheduled], true);
    $countryNames = $event->countries->map->getName('de')->unique()->values();
@endphp

<div class="flex flex-col gap-6" wire:init="evaluate">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('adminv2.events.edit', $event) }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Zurück zum Ereignis
            </a>
            <flux:heading size="xl" level="1" class="mt-1">Regeln der Kunden</flux:heading>
            <flux:subheading>Welche Benachrichtigungsregeln würden bei diesem Ereignis greifen? Reine Vorschau – es wird nichts versendet.</flux:subheading>
        </div>
    </div>

    {{-- Das Ereignis, um das es geht --}}
    <x-adminv2.card>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ $event->getTitle('de') ?: 'Ohne Titel' }}</h2>
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5">
                    <x-adminv2.state-badge :state="$state" />
                    <x-adminv2.priority-badge :priority="$event->priority" />
                    @foreach ($event->eventTypes as $eventType)
                        <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $eventType->name }}</span>
                    @endforeach
                </div>
                <dl class="mt-3 flex flex-col gap-1 text-sm text-zinc-600 dark:text-zinc-400">
                    <div class="flex items-center gap-2">
                        <dt class="shrink-0"><flux:icon.calendar variant="mini" class="text-zinc-400" /><span class="sr-only">Zeitraum</span></dt>
                        <dd class="tabular-nums">{{ $event->start_date?->format('d.m.Y') ?? '–' }} – {{ $event->end_date?->format('d.m.Y') ?? 'offen' }}</dd>
                    </div>
                    <div class="flex items-start gap-2">
                        <dt class="mt-0.5 shrink-0"><flux:icon.map-pin variant="mini" class="text-zinc-400" /><span class="sr-only">Länder</span></dt>
                        <dd>{{ $countryNames->isEmpty() ? 'Kein Standort' : $countryNames->implode(', ') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Alle Kunden oder ein bestimmter --}}
            <form wire:submit="applyCustomer" class="flex flex-col gap-2">
                <div class="flex items-end gap-2">
                    <div class="w-48">
                        <flux:input wire:model="customerInput" label="Kundennummer" placeholder="alle Kunden" inputmode="numeric" />
                    </div>
                    <flux:button type="submit">Prüfen</flux:button>
                    @if ($customerNumber !== '')
                        <flux:button variant="ghost" wire:click="showAllCustomers">Alle Kunden</flux:button>
                    @endif
                </div>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    @if ($customerNumber === '')
                        Gezeigt werden die Regeln <span class="font-medium text-zinc-900 dark:text-white">aller Kunden</span>.
                    @else
                        Gezeigt wird Kunde <span class="font-medium text-zinc-900 dark:text-white">{{ $customerNumber }}</span>@if ($customers->isNotEmpty()):
                            {{ $customers->map(fn ($customer) => trim((string) ($customer->company_name ?: $customer->name)) ?: $customer->email)->unique()->implode(', ') }}@endif.
                    @endif
                </p>
            </form>
        </div>
    </x-adminv2.card>

    @unless ($delivered)
        <flux:callout variant="warning" icon="exclamation-triangle" heading="Dieses Ereignis wird derzeit nicht ausgeliefert ({{ $state->label() }}).">
            <flux:callout.text>
                Benachrichtigungen gehen erst raus, wenn es veröffentlicht ist. Die Liste zeigt, welche Regeln dann zuträfen.
                @if ($customerNumber !== '') Von Hand senden lässt sich deshalb ebenfalls erst danach. @endif
            </flux:callout.text>
        </flux:callout>
    @endunless

    @if (! $loaded)
        {{-- Ladeanzeige: fuer Travel Alert werden die Reisen der Kunden abgefragt --}}
        <x-adminv2.card>
            <div class="flex items-center gap-3" role="status">
                <flux:icon.arrow-path variant="mini" class="shrink-0 animate-spin text-[var(--color-accent)]" />
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Die Regeln werden ausgewertet …</p>
                    <p class="text-xs text-zinc-500">Für Travel-Alert-Regeln werden die Reisen der Kunden abgefragt. Das kann einen Moment dauern.</p>
                </div>
            </div>
        </x-adminv2.card>
    @else
        @if ($pdsFailed)
            <flux:callout variant="danger" icon="exclamation-triangle" heading="Mindestens ein Abruf der Reisen ist fehlgeschlagen.">
                <flux:callout.text>„Keine betroffenen Reisen“ ist für die betroffenen Kunden deshalb nicht aussagekräftig.</flux:callout.text>
            </flux:callout>
        @endif

        @if ($portsMissing > 0)
            <flux:callout variant="warning" icon="exclamation-triangle" heading="PDS konnte zu {{ $portsMissing }} {{ $portsMissing === 1 ? 'Kreuzfahrt' : 'Kreuzfahrten' }} im Zeitraum keine Häfen liefern.">
                <flux:callout.text>Diese Reisen gelten nicht als betroffen, weil ihre Länder unbekannt sind. Alle übrigen Reisen wurden geprüft.</flux:callout.text>
            </flux:callout>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                @foreach (['matching' => 'Nur zutreffende', 'all' => 'Alle Regeln'] as $value => $label)
                    <button
                        type="button"
                        wire:click="$set('show', '{{ $value }}')"
                        @class([
                            'rounded-md px-3 py-1.5 text-sm font-medium transition',
                            'bg-white text-zinc-900 shadow-xs dark:bg-zinc-950 dark:text-white' => $show === $value,
                            'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' => $show !== $value,
                        ])
                    >{{ $label }}</button>
                @endforeach
            </div>

            <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="evaluate">Neu auswerten</flux:button>
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-2" wire:loading.class="opacity-60" wire:target="evaluate, applyCustomer, showAllCustomers, show">
            @foreach ($this->groups as $source => $group)
                <x-adminv2.card :heading="$group['label']" :description="$group['matching'].' von '.$group['total'].' '.($group['total'] === 1 ? 'Regel trifft' : 'Regeln treffen').' zu'" flush collapsible>
                    @forelse ($group['rows'] as $row)
                        <div wire:key="rule-{{ $row['rule_id'] }}" class="border-b border-zinc-100 px-5 py-4 last:border-0 dark:border-zinc-800">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-baseline gap-x-2">
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $row['customer_name'] }}</span>
                                        @if ($row['customer_number'])
                                            <span class="text-xs text-zinc-500 tabular-nums">Kundennummer {{ $row['customer_number'] }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">
                                        Regel
                                        <a href="{{ route('adminv2.rules.show', $row['rule_id']) }}" target="_blank" class="underline decoration-zinc-300 underline-offset-2 hover:text-zinc-900 hover:decoration-zinc-900 dark:hover:text-white">„{{ $row['rule_name'] }}“</a>
                                    </div>
                                </div>

                                @if ($row['would_notify'])
                                    <flux:badge color="green" inset="top bottom" icon="check">Trifft zu</flux:badge>
                                @else
                                    <flux:badge color="zinc" inset="top bottom">Trifft nicht zu</flux:badge>
                                @endif
                            </div>

                            <dl class="mt-3 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-[auto_1fr]">
                                <dt class="text-zinc-500">Prioritäten</dt>
                                <dd class="text-zinc-800 dark:text-zinc-200">{{ $row['risk_levels'] === [] ? 'alle' : implode(', ', $row['risk_levels']) }}</dd>

                                <dt class="text-zinc-500">Event-Typen</dt>
                                <dd class="text-zinc-800 dark:text-zinc-200">{{ $row['categories'] === [] ? 'alle' : implode(', ', $row['categories']) }}</dd>

                                @if ($source === \App\Models\NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR)
                                    <dt class="text-zinc-500">Länder</dt>
                                    <dd class="text-zinc-800 dark:text-zinc-200">{{ $row['countries'] === [] ? 'alle' : implode(', ', $row['countries']) }}</dd>
                                @else
                                    <dt class="text-zinc-500">Länder</dt>
                                    <dd class="text-zinc-800 dark:text-zinc-200">über die Reisen des Kunden</dd>
                                @endif

                                <dt class="text-zinc-500">Empfänger</dt>
                                <dd class="break-all text-zinc-800 dark:text-zinc-200">{{ $row['recipient'] ?? '–' }}</dd>
                            </dl>

                            @if ($row['reasons'] !== [])
                                <ul class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach ($row['reasons'] as $reason)
                                        <li class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            @if (! empty($row['trips']))
                                <div class="mt-3 rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-900">
                                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                        {{ count($row['trips']) }} {{ count($row['trips']) === 1 ? 'betroffene Reise' : 'betroffene Reisen' }}
                                    </p>
                                    <ul class="mt-1 flex flex-col gap-0.5 text-sm text-zinc-700 dark:text-zinc-300">
                                        @foreach (array_slice($row['trips'], 0, 8) as $trip)
                                            <li class="flex flex-wrap gap-x-3">
                                                <span class="font-medium">{{ $trip['name'] }}</span>
                                                <span class="tabular-nums text-zinc-500">{{ $trip['period'] }}</span>
                                                <span class="text-zinc-500">{{ $trip['countries'] }}</span>
                                            </li>
                                        @endforeach
                                        @if (count($row['trips']) > 8)
                                            <li class="text-zinc-500">… und {{ count($row['trips']) - 8 }} weitere</li>
                                        @endif
                                    </ul>
                                </div>
                            @endif

                            @if ($row['already_sent_at'])
                                <p class="mt-3 text-xs text-zinc-500">
                                    Für dieses Ereignis bereits benachrichtigt am {{ $row['already_sent_at'] }}.
                                    @if ($source === \App\Models\NotificationRule::SOURCE_TRAVEL_ALERT)
                                        Von selbst geht eine weitere Mail nur raus, wenn mehr Reisen betroffen sind als damals.
                                    @else
                                        Von selbst geht keine weitere Mail raus.
                                    @endif
                                </p>
                            @endif

                            {{-- Von Hand senden: nur bei einem einzelnen Kunden und einer zutreffenden Regel --}}
                            @if ($this->canSend && $row['would_notify'])
                                <div class="mt-3">
                                    <flux:button size="sm" icon="paper-airplane" wire:click="confirmSend({{ $row['rule_id'] }})">
                                        {{ $row['already_sent_at'] ? 'Benachrichtigung erneut senden' : 'Benachrichtigung jetzt senden' }}
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="px-5 py-8 text-sm text-zinc-500">
                            @if ($group['total'] === 0)
                                {{ $customerNumber !== '' ? 'Dieser Kunde hat hier keine Regel.' : 'Es gibt hier keine Regeln.' }}
                            @else
                                Keine der {{ $group['total'] }} Regeln trifft zu. Über „Alle Regeln“ siehst du, woran es jeweils liegt.
                            @endif
                        </p>
                    @endforelse
                </x-adminv2.card>
            @endforeach
        </div>

        {{-- Versandverlauf: wann welche Mail zu diesem Ereignis rausging --}}
        @php
            $history = $this->history;
            $control = 'h-9 rounded-lg border border-zinc-200 bg-white px-2.5 text-sm text-zinc-800 shadow-xs outline-none focus:border-[var(--color-accent)] dark:border-white/10 dark:bg-white/10 dark:text-white';
        @endphp
        <x-adminv2.card
            heading="Versandverlauf"
            :description="$history['total'] === 0
                ? 'Zu diesem Ereignis ist '.($customerNumber !== '' ? 'für diesen Kunden ' : '').'noch keine Mail rausgegangen.'
                : $history['total'].' '.($history['total'] === 1 ? 'Mail' : 'Mails').' zu diesem Ereignis'.($customerNumber !== '' ? ' an diesen Kunden' : '')"
            flush
            collapsible
            collapsed
        >
            @if ($history['total'] > 0)
                {{-- Filter --}}
                <div class="flex flex-wrap items-center gap-2 border-b border-zinc-100 px-5 py-3 dark:border-zinc-800">
                    <input type="search" wire:model.live.debounce.300ms="historySearch" placeholder="Empfänger, Betreff{{ $customerNumber === '' ? ', Kunde' : '' }} …" aria-label="Versandverlauf durchsuchen" class="{{ $control }} w-64 max-w-full" />

                    <select wire:model.live="historyStatus" aria-label="Status" class="{{ $control }}">
                        <option value="">Alle Status</option>
                        <option value="sent">Versendet</option>
                        <option value="failed">Fehlgeschlagen</option>
                    </select>

                    <select wire:model.live="historySource" aria-label="Bereich" class="{{ $control }}">
                        <option value="">Beide Bereiche</option>
                        <option value="{{ \App\Models\NotificationRule::SOURCE_GLOBAL_TRAVEL_MONITOR }}">Global Travel Monitor</option>
                        <option value="{{ \App\Models\NotificationRule::SOURCE_TRAVEL_ALERT }}">Travel Alert</option>
                    </select>

                    @if ($this->historyRules->count() > 1)
                        <select wire:model.live="historyRule" aria-label="Regel" class="{{ $control }} max-w-56">
                            <option value="">Alle Regeln</option>
                            @foreach ($this->historyRules as $historyRuleOption)
                                <option value="{{ $historyRuleOption->id }}">{{ $historyRuleOption->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    <span class="ms-1 text-sm text-zinc-500">Gesendet</span>
                    <input type="date" wire:model.live="historyFrom" aria-label="Gesendet ab" class="{{ $control }}" />
                    <span class="text-sm text-zinc-500">bis</span>
                    <input type="date" wire:model.live="historyTo" aria-label="Gesendet bis" class="{{ $control }}" />

                    @if ($this->historyHasFilters)
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetHistoryFilters">Zurücksetzen</flux:button>
                    @endif

                    <flux:spacer />

                    <flux:button
                        variant="ghost"
                        size="sm"
                        :icon="$historyDirection === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'"
                        wire:click="toggleHistoryDirection"
                    >{{ $historyDirection === 'asc' ? 'Älteste zuerst' : 'Neueste zuerst' }}</flux:button>
                </div>

                <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="historySearch, historyStatus, historySource, historyRule, historyFrom, historyTo, historyGoTo, toggleHistoryDirection, resetHistoryFilters">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead class="border-b border-zinc-100 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:border-zinc-800">
                            <tr>
                                <th class="px-3 py-3 ps-5 font-medium">Gesendet</th>
                                @if ($customerNumber === '')
                                    <th class="px-3 py-3 font-medium">Kunde</th>
                                @endif
                                <th class="px-3 py-3 font-medium">Angewendete Regel</th>
                                <th class="px-3 py-3 font-medium">Empfänger</th>
                                <th class="px-3 py-3 font-medium">Reisen in der Mail</th>
                                <th class="px-3 py-3 pe-5 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @if ($history['rows'] === [])
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-sm text-zinc-500">Für diese Filter gibt es keine Mail.</td>
                                </tr>
                            @endif
                            @foreach ($history['rows'] as $mail)
                                <tr wire:key="mail-{{ $mail['id'] }}-{{ $mail['at'] }}" class="align-top">
                                    <td class="px-3 py-3 ps-5 whitespace-nowrap tabular-nums text-zinc-900 dark:text-white">
                                        {{ $mail['at'] }}
                                        @if ($mail['version'])
                                            <div class="mt-0.5 text-xs text-zinc-500">Version {{ $mail['version'] }}</div>
                                        @endif
                                    </td>
                                    @if ($customerNumber === '')
                                        <td class="px-3 py-3 text-zinc-700 dark:text-zinc-300">{{ $mail['customer_name'] }}</td>
                                    @endif
                                    <td class="px-3 py-3">
                                        @if ($mail['rule_id'])
                                            <a href="{{ route('adminv2.rules.show', $mail['rule_id']) }}" target="_blank" class="inline-flex items-center gap-1 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">
                                                {{ $mail['rule_name'] }}
                                                <flux:icon.arrow-top-right-on-square variant="micro" class="shrink-0 text-zinc-400" />
                                            </a>
                                            @if ($mail['rule_deleted'])
                                                <span class="text-xs text-zinc-500">(gelöscht)</span>
                                            @endif
                                        @else
                                            <div class="text-zinc-900 dark:text-white">{{ $mail['rule_name'] }}</div>
                                        @endif
                                        @if ($mail['source'])
                                            <div class="mt-0.5 text-xs text-zinc-500">{{ $mail['source'] === \App\Models\NotificationRule::SOURCE_TRAVEL_ALERT ? 'Travel Alert' : 'Global Travel Monitor' }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="break-all text-zinc-700 dark:text-zinc-300">{{ $mail['recipient'] }}</div>
                                        <div class="mt-0.5 text-xs text-zinc-500">{{ $mail['subject'] }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-zinc-700 dark:text-zinc-300">
                                        @if (! empty($mail['trips']))
                                            <ul class="flex flex-col gap-0.5">
                                                @foreach (array_slice($mail['trips'], 0, 6) as $trip)
                                                    <li>{{ $trip['name'] ?: $trip['key'] }}</li>
                                                @endforeach
                                                @if (count($mail['trips']) > 6)
                                                    <li class="text-zinc-500">… und {{ count($mail['trips']) - 6 }} weitere</li>
                                                @endif
                                            </ul>
                                        @elseif ((int) $mail['trips_count'] > 0)
                                            {{ $mail['trips_count'] }} {{ (int) $mail['trips_count'] === 1 ? 'Reise' : 'Reisen' }}
                                            <div class="mt-0.5 text-xs text-zinc-500">Welche, wurde damals noch nicht gespeichert.</div>
                                        @else
                                            <span class="text-zinc-400">–</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 pe-5">
                                        @if ($mail['status'] === 'sent')
                                            <flux:badge color="green" size="sm" inset="top bottom">Versendet</flux:badge>
                                        @else
                                            <flux:badge color="red" size="sm" inset="top bottom">Fehlgeschlagen</flux:badge>
                                            @if ($mail['error'])
                                                <div class="mt-1 max-w-xs text-xs text-red-700 dark:text-red-400">{{ \Illuminate\Support\Str::limit($mail['error'], 160) }}</div>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Seiten: hoechstens zehn Mails auf einmal --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 px-5 py-3 text-sm text-zinc-500 dark:border-zinc-800">
                    <span class="tabular-nums">
                        @if ($history['filtered'] === 0)
                            0 von {{ $history['total'] }}
                        @else
                            {{ ($history['page'] - 1) * 10 + 1 }}–{{ min($history['page'] * 10, $history['filtered']) }} von {{ $history['filtered'] }}
                            @if ($history['filtered'] !== $history['total']) (gefiltert aus {{ $history['total'] }}) @endif
                        @endif
                    </span>

                    @if ($history['pages'] > 1)
                        <div class="flex items-center gap-2">
                            <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="historyGoTo({{ $history['page'] - 1 }})" :disabled="$history['page'] <= 1">Zurück</flux:button>
                            <span class="tabular-nums">Seite {{ $history['page'] }} von {{ $history['pages'] }}</span>
                            <flux:button size="sm" variant="ghost" icon-trailing="chevron-right" wire:click="historyGoTo({{ $history['page'] + 1 }})" :disabled="$history['page'] >= $history['pages']">Weiter</flux:button>
                        </div>
                    @endif
                </div>
            @endif
        </x-adminv2.card>

        {{-- Zukuenftige Reisen (Travel-Detail-Links) – nur bei einem einzelnen Kunden --}}
        @if ($customerNumber !== '')
            @php
                $affectedTrips = count(array_filter($trips, fn ($trip) => $trip['affected']));
                $eventHasCountries = $event->countries->isNotEmpty();
            @endphp

            <x-adminv2.card
                heading="Zukünftige Reisen des Kunden"
                :description="$tripsLoaded
                    ? $affectedTrips.' von '.count($trips).' '.(count($trips) === 1 ? 'Reise ist' : 'Reisen sind').' von diesem Ereignis betroffen'
                    : 'Travel-Detail-Links, die heute oder später enden – mit der Prüfung, ob dieses Ereignis sie betrifft.'"
                flush
                collapsible
                collapsed
            >
                @if (! $tripsLoaded)
                    <div class="flex flex-wrap items-center gap-3 px-5 py-5">
                        <flux:button icon="arrow-down-tray" wire:click="loadTrips" wire:loading.attr="disabled" wire:target="loadTrips">Zukünftige Reisen abrufen</flux:button>
                        <span class="flex items-center gap-2 text-sm text-zinc-500" wire:loading.flex wire:target="loadTrips" role="status">
                            <flux:icon.arrow-path variant="mini" class="animate-spin text-[var(--color-accent)]" />
                            Die Reisen werden abgerufen …
                        </span>
                        <span class="text-sm text-zinc-500" wire:loading.remove wire:target="loadTrips">
                            Betroffen ist eine Reise, wenn ihr Zeitraum den des Ereignisses überschneidet und sie in eines seiner Länder führt.
                        </span>
                    </div>
                @else
                    {{-- Filter --}}
                    <div class="flex flex-wrap items-center gap-2 border-b border-zinc-100 px-5 py-3 dark:border-zinc-800">
                        <input type="search" wire:model.live.debounce.300ms="tripsSearch" placeholder="Reise, Referenz oder Kennung …" aria-label="Reisen durchsuchen" class="{{ $control }} w-64 max-w-full" />

                        <select wire:model.live="tripsShow" aria-label="Betroffenheit" class="{{ $control }}">
                            <option value="all">Alle Reisen</option>
                            <option value="affected">Nur betroffene</option>
                            <option value="unaffected">Nur nicht betroffene</option>
                        </select>

                        <select wire:model.live="tripsCountry" aria-label="Land" class="{{ $control }} max-w-56">
                            <option value="">Alle Länder</option>
                            @foreach ($this->tripCountries as $tripCountry)
                                <option value="{{ $tripCountry }}">{{ $tripCountry }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="tripsReported" aria-label="Gemeldet" class="{{ $control }}">
                            <option value="">Gemeldet und nicht gemeldet</option>
                            <option value="reported">Schon per Mail gemeldet</option>
                            <option value="unreported">Noch nicht gemeldet</option>
                        </select>

                        <span class="ms-1 text-sm text-zinc-500">Unterwegs</span>
                        <input type="date" wire:model.live="tripsFrom" aria-label="Unterwegs ab" class="{{ $control }}" />
                        <span class="text-sm text-zinc-500">bis</span>
                        <input type="date" wire:model.live="tripsTo" aria-label="Unterwegs bis" class="{{ $control }}" />

                        @if ($this->tripsHasFilters)
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetTripFilters">Zurücksetzen</flux:button>
                        @endif

                        <flux:spacer />

                        <span class="text-sm tabular-nums text-zinc-500">{{ count($this->visibleTrips) }} von {{ count($trips) }}</span>
                        <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="loadTrips">Neu abrufen</flux:button>
                    </div>

                    @if ($this->mailsWithoutTripList > 0)
                        <div class="border-b border-zinc-100 px-5 py-3 text-sm text-zinc-500 dark:border-zinc-800">
                            Bei {{ $this->mailsWithoutTripList }} {{ $this->mailsWithoutTripList === 1 ? 'älteren Mail' : 'älteren Mails' }} wurde noch nicht gespeichert, welche Reisen sie nannten – sie stehen im Versandverlauf, aber nicht an der einzelnen Reise.
                        </div>
                    @endif

                    @if ($tripsFailed)
                        <div class="border-b border-zinc-100 px-5 py-3 text-sm text-red-700 dark:border-zinc-800 dark:text-red-400">
                            Der Abruf der Travel-Detail-Links ist fehlgeschlagen. Die Liste ist deshalb unvollständig.
                        </div>
                    @endif

                    <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="loadTrips, tripsShow, tripsSearch, tripsCountry, tripsReported, tripsFrom, tripsTo, resetTripFilters">
                        <table class="w-full min-w-[860px] text-left text-sm">
                            <thead class="border-b border-zinc-100 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:border-zinc-800">
                                <tr>
                                    <th class="px-3 py-3 ps-5 font-medium">Reise</th>
                                    <th class="px-3 py-3 font-medium">Zeitraum</th>
                                    <th class="px-3 py-3 font-medium">Länder</th>
                                    <th class="px-3 py-3 font-medium">Dieses Ereignis</th>
                                    <th class="px-3 py-3 pe-5 font-medium"><span class="sr-only">Link</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @forelse ($this->visibleTrips as $trip)
                                    <tr wire:key="trip-{{ $trip['key'] }}" @class(['align-top', 'bg-green-50/60 dark:bg-green-400/5' => $trip['affected']])>
                                        <td class="px-3 py-3 ps-5">
                                            <div class="font-medium text-zinc-900 dark:text-white">{{ $trip['name'] }}</div>
                                            <div class="mt-0.5 text-xs text-zinc-500">
                                                @if ($trip['reference']) {{ $trip['reference'] }} · @endif
                                                <span class="font-mono">{{ $trip['tid'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap tabular-nums">
                                            <span @class(['font-medium text-zinc-900 dark:text-white' => $trip['in_period'], 'text-zinc-600 dark:text-zinc-400' => ! $trip['in_period']])>{{ $trip['period'] }}</span>
                                        </td>
                                        <td class="px-3 py-3">
                                            @forelse ($trip['countries'] as $country)
                                                <span @class(['font-medium text-zinc-900 dark:text-white' => $country['match'], 'text-zinc-600 dark:text-zinc-400' => ! $country['match']])>{{ $country['name'] }}</span>@if (! $loop->last)<span class="text-zinc-400">, </span>@endif
                                            @empty
                                                <span class="text-zinc-400">–</span>
                                            @endforelse
                                            @if ($trip['ports_missing'])
                                                <div class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">Kreuzfahrt: Die Häfen konnten nicht geladen werden.</div>
                                            @endif
                                        </td>
                                        <td class="min-w-60 px-3 py-3">
                                            @php $mailsForTrip = $this->tripMails[$trip['key']] ?? []; @endphp
                                            @if ($trip['affected'])
                                                <flux:badge color="green" size="sm" inset="top bottom" icon="check">Betroffen</flux:badge>
                                                @unless ($trip['counted'])
                                                    <div class="mt-1 text-xs text-amber-700 dark:text-amber-400">Der Versand berücksichtigt diese Reise derzeit nicht.</div>
                                                @endunless
                                            @else
                                                <flux:badge color="zinc" size="sm" inset="top bottom">Nicht betroffen</flux:badge>
                                                <div class="mt-1 text-xs text-zinc-500">
                                                    @if (! $eventHasCountries)
                                                        Ereignis hat keine Länder
                                                    @elseif (! $trip['in_period'] && ! $trip['country_match'])
                                                        Zeitraum und Land passen nicht
                                                    @elseif (! $trip['in_period'])
                                                        Zeitraum passt nicht
                                                    @else
                                                        Land passt nicht
                                                    @endif
                                                </div>
                                            @endif
                                            @if ($mailsForTrip !== [])
                                                <div class="mt-1 flex items-start gap-1 text-xs text-zinc-600 dark:text-zinc-400">
                                                    <flux:icon.envelope variant="micro" class="mt-px shrink-0" />
                                                    <span>Gemeldet am {{ implode(', ', $mailsForTrip) }}</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 pe-5 text-right whitespace-nowrap">
                                            @if ($trip['url'])
                                                <flux:button size="xs" icon-trailing="arrow-top-right-on-square" href="{{ $trip['url'] }}" target="_blank" rel="noopener noreferrer">Link öffnen</flux:button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-8 text-sm text-zinc-500">
                                            @if ($trips === [])
                                                Für diesen Kunden gibt es keine zukünftigen Reisen.
                                            @else
                                                Für diese Filter gibt es unter den {{ count($trips) }} zukünftigen Reisen keinen Treffer.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-adminv2.card>
        @endif
    @endif
    {{-- Rueckfrage vor dem Senden von Hand --}}
    <flux:modal name="send-rule" class="md:w-[32rem]">
        @if ($sendRow = $this->sendRow)
            <div class="flex flex-col gap-5">
                <div>
                    <flux:heading size="lg">{{ $sendRow['already_sent_at'] ? 'Benachrichtigung erneut senden?' : 'Benachrichtigung jetzt senden?' }}</flux:heading>
                    <flux:text class="mt-2">
                        Es geht genau eine E-Mail raus – nur für diese Regel und dieses Ereignis. Andere Regeln und Kunden bleiben unberührt.
                    </flux:text>
                </div>

                <dl class="grid gap-x-6 gap-y-1.5 text-sm sm:grid-cols-[auto_1fr]">
                    <dt class="text-zinc-500">Kunde</dt>
                    <dd class="text-zinc-900 dark:text-white">{{ $sendRow['customer_name'] }}</dd>
                    <dt class="text-zinc-500">Regel</dt>
                    <dd class="text-zinc-900 dark:text-white">
                        „{{ $sendRow['rule_name'] }}“
                        ({{ $sendRow['source'] === \App\Models\NotificationRule::SOURCE_TRAVEL_ALERT ? 'Travel Alert' : 'Global Travel Monitor' }})
                    </dd>
                    <dt class="text-zinc-500">Empfänger</dt>
                    <dd class="break-all font-medium text-zinc-900 dark:text-white">{{ $sendRow['recipient'] }}</dd>
                    @if (! empty($sendRow['trips']))
                        <dt class="text-zinc-500">Betroffene Reisen</dt>
                        <dd class="text-zinc-900 dark:text-white">{{ count($sendRow['trips']) }}</dd>
                    @endif
                    @if ($sendRow['already_sent_at'])
                        <dt class="text-zinc-500">Zuletzt gesendet</dt>
                        <dd class="text-zinc-900 dark:text-white">{{ $sendRow['already_sent_at'] }}</dd>
                    @endif
                </dl>

                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                    <flux:button variant="primary" icon="paper-airplane" wire:click="sendRule" wire:loading.attr="disabled" wire:target="sendRule">Jetzt senden</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
