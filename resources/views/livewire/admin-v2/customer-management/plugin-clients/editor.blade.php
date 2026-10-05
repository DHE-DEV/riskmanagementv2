@php
    use App\Livewire\AdminV2\CustomerManagement\PluginClients\Editor;
    use App\Livewire\AdminV2\CustomerManagement\PluginClients\Index;

    $client = $this->client;
    $index = route('adminv2.customer-management.plugin-clients.index');
    $number = fn (int $value) => number_format($value, 0, ',', '.');
    $copy = 'livewire.admin-v2.customer-management.plugin-clients.copy';
    $customerOptions = $this->customerOptions
        ->map(fn ($customer) => ['value' => $customer->id, 'label' => trim(($customer->name ?: 'Ohne Namen').' · '.$customer->email, ' ·')])
        ->all();

    $customer = $client?->customer;
    $key = $client?->activeKey;
    $businessTypes = collect((array) ($customer?->business_type ?? []))->filter()->map(fn ($type) => Editor::BUSINESS_TYPES[$type] ?? $type);
    $th = 'px-5 py-2.5 text-start text-xs font-medium uppercase tracking-wide text-zinc-500';
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ $index }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Plugin-Kunden
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <flux:heading size="xl" level="1">{{ $client ? $client->company_name : 'Neuer Plugin-Kunde' }}</flux:heading>
                @if ($client)
                    <flux:badge size="sm" :color="Index::STATUS_COLORS[$client->status] ?? 'zinc'">{{ Index::STATUSES[$client->status] ?? $client->status }}</flux:badge>
                @endif
            </div>
            @if ($client)
                <flux:subheading>{{ collect([$client->contact_name, $client->email])->filter()->implode(' · ') }}</flux:subheading>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($client)
                <flux:button
                    icon="arrow-path"
                    wire:click="regenerateKey"
                    wire:confirm="API-Key erneuern? Achtung: Bei Erneuerung des API-Keys muss die Einbindung auf ALLEN registrierten Domains aktualisiert werden! Der alte Key wird sofort ungültig und die Darstellung funktioniert nicht mehr, bis der neue Key eingebunden wurde."
                >
                    API-Key erneuern
                </flux:button>
                <flux:button
                    variant="danger"
                    icon="trash"
                    wire:click="delete"
                    wire:confirm="Sind Sie sicher, dass Sie diesen Plugin-Kunden löschen möchten? Alle zugehörigen Daten (Domains, API-Keys, Nutzungsstatistiken) werden ebenfalls gelöscht."
                >
                    Löschen
                </flux:button>
            @endif
            <flux:button variant="ghost" :href="$index">{{ $client ? 'Zur Liste' : 'Abbrechen' }}</flux:button>
            <flux:button variant="primary" icon="check" wire:click="save" wire:loading.attr="disabled" wire:target="save">Speichern</flux:button>
        </div>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-2">
        {{-- Linke Spalte: Formular --}}
        <form wire:submit="save" class="flex flex-col gap-6">
            <x-adminv2.card heading="Kundendaten">
                <div class="flex flex-col gap-5">
                    <flux:field>
                        <flux:label>Verknüpfter Kunde (optional)</flux:label>
                        <x-adminv2.search-select :options="$customerOptions" model="customerId" :selected="$customerId" placeholder="Kein Kunde verknüpft" search-placeholder="Name oder E-Mail …" label="Verknüpfter Kunde" clearable />
                        <flux:error name="customerId" />
                    </flux:field>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:input wire:model="companyName" label="Firma" maxlength="255" />
                        <flux:input wire:model="contactName" label="Ansprechpartner" maxlength="255" />
                    </div>

                    <flux:input wire:model="email" type="email" label="E-Mail" maxlength="255" />

                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            @foreach (Index::STATUSES as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>

                    <flux:switch wire:model="allowAppAccess" label="App-Zugang erlaubt" description="Ermöglicht Nutzung ohne Domain-Validierung (für WebView-Apps)" align="left" />
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Adresse">
                <div class="flex flex-col gap-5">
                    <div class="grid gap-5 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                        <flux:input wire:model="street" label="Straße" maxlength="255" />
                        <flux:input wire:model="houseNumber" label="Hausnummer" maxlength="20" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-3">
                        <flux:input wire:model="postalCode" label="PLZ" maxlength="20" />
                        <flux:input wire:model="city" label="Ort" maxlength="255" />
                        <flux:input wire:model="country" label="Land" maxlength="255" />
                    </div>
                </div>
            </x-adminv2.card>

            {{-- Enter in einem Feld speichert – dafuer braucht das Formular eine eigene Schaltflaeche. --}}
            <button type="submit" class="sr-only" tabindex="-1" aria-hidden="true">Speichern</button>
        </form>

        {{-- Rechte Spalte: Statistik, API-Zugang, verknuepfter Kunde --}}
        <div class="flex flex-col gap-6">
            @if ($client)
                <x-adminv2.card heading="Statistik">
                    <dl class="grid gap-4 sm:grid-cols-3">
                        @foreach (['total' => 'Gesamtaufrufe', 'days30' => 'Aufrufe (30 Tage)', 'today' => 'Aufrufe (heute)'] as $field => $label)
                            <div>
                                <dt class="text-sm text-zinc-500">{{ $label }}</dt>
                                <dd class="mt-1 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $number($this->usage[$field]) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-adminv2.card>

                <x-adminv2.card heading="API-Zugang">
                    @if ($key)
                        <dl class="flex flex-col gap-4 text-sm">
                            <div>
                                <dt class="text-zinc-500">Aktiver API-Key</dt>
                                <dd class="mt-0.5 flex items-start gap-1.5 text-zinc-900 dark:text-white">
                                    <span class="break-all font-mono">{{ $key->public_key }}</span>
                                    @include($copy, ['text' => $key->public_key, 'label' => 'API-Key kopieren'])
                                </dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500">Key erstellt am</dt>
                                <dd class="mt-0.5 tabular-nums text-zinc-900 dark:text-white">{{ $key->created_at?->format('d.m.Y H:i') ?? '–' }}</dd>
                            </div>
                            @foreach (Editor::EMBED_VIEWS as $view => $label)
                                @php $url = Editor::EMBED_BASE.$view.'?key='.$key->public_key; @endphp
                                <div>
                                    <dt class="text-zinc-500">{{ $label }}</dt>
                                    <dd class="mt-0.5 flex items-start gap-1.5 text-zinc-900 dark:text-white">
                                        <span class="break-all font-mono text-xs">{{ $url }}</span>
                                        @include($copy, ['text' => $url, 'label' => 'Link kopieren'])
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    @else
                        <p class="text-sm text-zinc-500">Kein API-Key vorhanden. Über „API-Key erneuern“ wird einer erzeugt.</p>
                    @endif
                </x-adminv2.card>

                @if ($customer)
                    <x-adminv2.card heading="Verknüpfter Kunde">
                        <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                            <dt class="text-zinc-500">Kunde</dt>
                            <dd>
                                <a href="{{ route('adminv2.customer-management.customers.edit', $customer->id) }}" target="_blank" class="inline-flex items-center gap-1 text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">
                                    {{ $customer->name ?: $customer->email }} <flux:icon.arrow-top-right-on-square variant="micro" />
                                </a>
                            </dd>

                            <dt class="text-zinc-500">Kunden-E-Mail</dt>
                            <dd class="flex items-start gap-1.5 text-zinc-900 dark:text-white">
                                @if (filled($customer->email))
                                    <span class="break-all">{{ $customer->email }}</span>
                                    @include($copy, ['text' => $customer->email, 'label' => 'E-Mail kopieren'])
                                @else
                                    –
                                @endif
                            </dd>

                            <dt class="text-zinc-500">Kundentyp</dt>
                            <dd>
                                @if ($customer->customer_type === 'business')
                                    <flux:badge size="sm" color="green">Firmenkunde</flux:badge>
                                @elseif ($customer->customer_type === 'private')
                                    <flux:badge size="sm" color="blue">Privatkunde</flux:badge>
                                @else
                                    –
                                @endif
                            </dd>

                            <dt class="text-zinc-500">Geschäftstyp</dt>
                            <dd class="flex flex-wrap gap-1.5">
                                @forelse ($businessTypes as $type)
                                    <flux:badge size="sm" color="zinc">{{ $type }}</flux:badge>
                                @empty
                                    –
                                @endforelse
                            </dd>
                        </dl>
                    </x-adminv2.card>
                @endif

                <x-adminv2.record-tasks :record="$client" />

                <x-adminv2.card heading="Zeitstempel" collapsible collapsed collapse-key="plugin-client-timestamps">
                    <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
                        <dt class="text-zinc-500">Registriert am</dt>
                        <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $client->created_at?->format('d.m.Y H:i') ?? '–' }}</dd>
                        <dt class="text-zinc-500">Aktualisiert am</dt>
                        <dd class="tabular-nums text-zinc-900 dark:text-white">{{ $client->updated_at?->format('d.m.Y H:i') ?? '–' }}</dd>
                    </dl>
                </x-adminv2.card>
            @else
                <x-adminv2.card heading="Nach dem Speichern">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Sobald der Plugin-Kunde angelegt ist, bekommt er automatisch einen API-Key. Danach lassen sich hier seine Domains pflegen und die Aufrufe des Plugins ansehen.</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>

    @if ($client)
        {{-- Registrierte Domains --}}
        <x-adminv2.card heading="Registrierte Domains" description="Nur auf diesen Domains darf das Plugin eingebunden werden." flush>
            <div class="flex flex-col gap-2 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="min-w-64 flex-1">
                        <flux:input wire:model="newDomain" wire:keydown.enter.prevent="addDomain" placeholder="beispiel.de" aria-label="Neue Domain" maxlength="255" />
                    </div>
                    <flux:switch wire:model="newDomainActive" label="Aktiv" align="left" />
                    <flux:button icon="plus" wire:click="addDomain" wire:loading.attr="disabled" wire:target="addDomain">Domain hinzufügen</flux:button>
                </div>
                <flux:error name="newDomain" />
                <p class="text-xs text-zinc-500">Ohne https:// oder http:// eingeben.</p>
            </div>

            @if ($this->domains->isEmpty())
                <div class="px-5 py-10 text-center">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine Domains</p>
                    <p class="mt-1 text-sm text-zinc-500">Für diesen Kunden sind keine Domains registriert.</p>
                </div>
            @else
                <ul class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800" wire:loading.class="opacity-60" wire:target="toggleDomain, deleteDomain, addDomain">
                    @foreach ($this->domains as $domain)
                        <li wire:key="domain-{{ $domain->id }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                            <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="inline-flex min-w-0 items-center gap-1.5 text-sm font-medium text-zinc-900 dark:text-white">
                                    <span class="break-all">{{ $domain->domain }}</span>
                                    @include($copy, ['text' => $domain->domain, 'label' => 'Domain kopieren'])
                                </span>
                                <flux:badge size="sm" :color="$domain->is_active ? 'green' : 'red'">{{ $domain->is_active ? 'Aktiv' : 'Deaktiviert' }}</flux:badge>
                                <span class="text-xs tabular-nums text-zinc-500">hinzugefügt {{ $domain->created_at?->format('d.m.Y H:i') ?? '–' }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <flux:button size="sm" variant="ghost" :icon="$domain->is_active ? 'pause' : 'play'" wire:click="toggleDomain({{ $domain->id }})">{{ $domain->is_active ? 'Deaktivieren' : 'Aktivieren' }}</flux:button>
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteDomain({{ $domain->id }})" wire:confirm="Sind Sie sicher, dass Sie diese Domain endgültig löschen möchten?">Löschen</flux:button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-adminv2.card>

        {{-- Aufrufe – aktualisiert sich alle 30 Sekunden --}}
        @php $events = $this->events; @endphp
        <x-adminv2.card heading="Aufrufe" description="Jeder Aufruf des Plugins auf einer Seite des Kunden. Die Liste aktualisiert sich alle 30 Sekunden." flush>
            <div wire:poll.30s class="flex flex-wrap items-center gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <div class="min-w-56 flex-1">
                    <flux:input wire:model.live.debounce.300ms="eventSearch" icon="magnifying-glass" placeholder="Domain oder Pfad suchen …" aria-label="Aufrufe durchsuchen" clearable />
                </div>
                <div class="w-44">
                    <flux:select wire:model.live="eventType" aria-label="Event-Typ">
                        <flux:select.option value="">Alle Event-Typen</flux:select.option>
                        @foreach ($this->eventTypeOptions as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="w-52">
                    <flux:select wire:model.live="eventDomain" aria-label="Domain">
                        <flux:select.option value="">Alle Domains</flux:select.option>
                        @foreach ($this->eventDomainOptions as $value)
                            <flux:select.option value="{{ $value }}">{{ $value }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="w-44">
                    <flux:select wire:model.live="eventPeriod" aria-label="Zeitraum">
                        <flux:select.option value="">Gesamter Zeitraum</flux:select.option>
                        <flux:select.option value="today">Heute</flux:select.option>
                        <flux:select.option value="week">Diese Woche</flux:select.option>
                        <flux:select.option value="month">Dieser Monat</flux:select.option>
                    </flux:select>
                </div>
                <div class="w-44">
                    <flux:select wire:model.live="eventSort" aria-label="Sortierung">
                        <flux:select.option value="created_at">Nach Zeitpunkt</flux:select.option>
                        <flux:select.option value="domain">Nach Domain</flux:select.option>
                    </flux:select>
                </div>
                <flux:button variant="ghost" size="sm" :icon="$eventDirection === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'" wire:click="toggleEventDirection">
                    {{ $eventDirection === 'asc' ? 'Aufsteigend' : 'Absteigend' }}
                </flux:button>
                @if ($this->hasEventFilters())
                    <flux:button variant="ghost" icon="x-mark" wire:click="resetEventFilters">Zurücksetzen</flux:button>
                @endif
            </div>

            @if ($events->isEmpty())
                <div class="px-5 py-10 text-center">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Keine Aufrufe</p>
                    <p class="mt-1 text-sm text-zinc-500">
                        {{ $this->hasEventFilters() ? 'Zu Suche und Filtern passt kein Aufruf.' : 'Es wurden noch keine Widget-Aufrufe für diesen Kunden registriert.' }}
                    </p>
                </div>
            @else
                <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="eventSearch, eventType, eventDomain, eventPeriod, eventSort, toggleEventDirection, resetEventFilters, gotoPage, nextPage, previousPage">
                    <table class="w-full text-sm">
                        <thead class="border-b border-zinc-100 dark:border-zinc-800">
                            <tr>
                                <th class="{{ $th }}">Zeitpunkt</th>
                                <th class="{{ $th }}">Domain</th>
                                <th class="{{ $th }}">Pfad</th>
                                <th class="{{ $th }}">Event-Typ</th>
                                <th class="{{ $th }}">Browser</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($events as $event)
                                <tr wire:key="event-{{ $event->id }}">
                                    <td class="whitespace-nowrap px-5 py-2.5 tabular-nums text-zinc-900 dark:text-white">{{ $event->created_at?->format('d.m.Y H:i:s') ?? '–' }}</td>
                                    <td class="px-5 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $event->domain }}</td>
                                    <td class="max-w-64 truncate px-5 py-2.5 text-zinc-600 dark:text-zinc-400" title="{{ $event->path }}">{{ filled($event->path) ? $event->path : '–' }}</td>
                                    <td class="px-5 py-2.5">
                                        <flux:badge size="sm" :color="match ($event->event_type) { 'page_load' => 'blue', 'click' => 'green', default => 'zinc' }">{{ $event->event_type }}</flux:badge>
                                    </td>
                                    <td class="max-w-64 truncate px-5 py-2.5 text-zinc-500" title="{{ $event->user_agent }}">{{ filled($event->user_agent) ? $event->user_agent : '–' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-zinc-100 px-5 py-3 dark:border-zinc-800">
                    <x-adminv2.pagination :paginator="$events" />
                </div>
            @endif
        </x-adminv2.card>
    @endif
</div>
