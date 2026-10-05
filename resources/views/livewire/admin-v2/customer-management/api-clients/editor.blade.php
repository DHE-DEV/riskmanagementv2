@php
    use App\Livewire\AdminV2\CustomerManagement\ApiClients\Editor;
    use App\Livewire\AdminV2\CustomerManagement\ApiClients\Index;

    $record = $this->record;
    $indexUrl = route('adminv2.customer-management.api-clients.index');

    // Vorschau des neu gewaehlten Logos – nicht jede Datei laesst sich vorab anzeigen.
    $logoPreview = null;
    if ($logo && ! $errors->has('logo')) {
        try {
            $logoPreview = $logo->temporaryUrl();
        } catch (\Throwable) {
            $logoPreview = null;
        }
    }
    $savedLogo = $removeLogo ? null : $record?->getLogoUrl();

    $sortIcon = fn (string $column) => $eventSort === $column ? ($eventDirection === 'asc' ? 'chevron-up' : 'chevron-down') : 'chevron-up-down';
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ $indexUrl }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> API-Kunden
            </a>
            <flux:heading size="xl" level="1" class="mt-1">{{ $record ? $record->name : 'Neuer API-Kunde' }}</flux:heading>
            @if ($record)
                <flux:subheading>{{ $record->company_name }}</flux:subheading>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="ghost" :href="$indexUrl">{{ $record ? 'Zur Liste' : 'Abbrechen' }}</flux:button>
            @if ($record)
                <flux:button variant="ghost" icon="trash" wire:click="delete" wire:confirm="„{{ $record->name }}“ löschen? Die API-Tokens dieses Kunden funktionieren danach nicht mehr.">Löschen</flux:button>
            @endif
            <flux:button variant="primary" icon="check" wire:click="save" wire:loading.attr="disabled" wire:target="save, logo">Speichern</flux:button>
        </div>
    </div>

    {{-- Gerade erzeugter Token: nur jetzt im Klartext sichtbar --}}
    @if ($newToken)
        <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-900 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-200" x-data="{ copied: false }">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold">API-Token erstellt</p>
                    <p class="mt-0.5">Bitte kopieren Sie den Token jetzt. Er wird nicht erneut angezeigt.</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <flux:button size="sm" icon="clipboard-document" x-on:click="navigator.clipboard.writeText(@js($newToken)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
                        <span x-text="copied ? 'Kopiert' : 'Kopieren'">Kopieren</span>
                    </flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="dismissToken">Ausblenden</flux:button>
                </div>
            </div>
            <code class="mt-3 block rounded-lg bg-white px-3 py-2 font-mono text-sm break-all text-zinc-900 select-all dark:bg-zinc-950 dark:text-white">{{ $newToken }}</code>
        </div>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <form wire:submit="save" class="flex flex-col gap-6">
            <x-adminv2.card heading="Kundeninformationen">
                <div class="flex flex-col gap-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:input wire:model="name" label="Name" maxlength="255" />
                        <flux:input wire:model="companyName" label="Firma" maxlength="255" />
                    </div>
                    <flux:input wire:model="contactEmail" type="email" label="E-Mail" maxlength="255" />
                    <flux:textarea wire:model="description" label="Beschreibung" rows="3" maxlength="1000" />
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Logo" description="Max. 2 MB. PNG, JPG oder SVG.">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex h-20 min-w-20 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 px-3 dark:border-zinc-800 dark:bg-zinc-900">
                            @if ($logoPreview)
                                <img src="{{ $logoPreview }}" alt="Neues Logo" class="max-h-16 max-w-48 object-contain" />
                            @elseif ($savedLogo)
                                <img src="{{ $savedLogo }}" alt="Logo von {{ $record->name }}" class="max-h-16 max-w-48 object-contain" />
                            @else
                                {{-- Ohne eigenes Logo gilt das Passolution-Logo. --}}
                                <img src="{{ url('/Passolution-Logo-klein.png') }}" alt="Passolution-Logo" class="max-h-16 max-w-48 object-contain opacity-60" />
                            @endif
                        </div>

                        <div class="min-w-0 text-sm text-zinc-600 dark:text-zinc-400">
                            @if ($logo && ! $errors->has('logo'))
                                <p class="font-medium text-zinc-900 dark:text-white">Neues Logo: {{ $logo->getClientOriginalName() }}</p>
                                <p>Wird mit dem Speichern übernommen.</p>
                                <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="discardLogo" class="mt-1">Verwerfen</flux:button>
                            @elseif ($savedLogo)
                                <p>Eigenes Logo hinterlegt.</p>
                                <flux:button size="xs" variant="ghost" icon="trash" wire:click="$set('removeLogo', true)" class="mt-1">Logo entfernen</flux:button>
                            @elseif ($removeLogo)
                                <p>Das Logo wird mit dem Speichern entfernt.</p>
                                <flux:button size="xs" variant="ghost" icon="arrow-uturn-left" wire:click="$set('removeLogo', false)" class="mt-1">Doch behalten</flux:button>
                            @else
                                <p>Kein eigenes Logo – es wird das Passolution-Logo verwendet.</p>
                            @endif
                        </div>
                    </div>

                    <flux:field>
                        <flux:label>{{ $savedLogo ? 'Logo ersetzen' : 'Logo hochladen' }}</flux:label>
                        <flux:input type="file" wire:model="logo" accept="image/png,image/jpeg,image/svg+xml" />
                        <p class="text-xs text-zinc-500" wire:loading wire:target="logo">Datei wird hochgeladen …</p>
                        <flux:error name="logo" />
                    </flux:field>
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Einstellungen">
                <div class="flex flex-col gap-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:select wire:model="status" label="Status">
                            @foreach (Index::STATUSES as $value => [$label])
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="rateLimit" type="number" min="1" max="1000" label="Rate Limit (Requests/Minute)" />
                    </div>

                    <flux:switch wire:model="canCreateEvents" label="Event-Erstellung erlauben" description="Wenn aktiviert, kann dieser Kunde eigene Events per API erstellen, bearbeiten und löschen." align="left" />
                    <flux:switch wire:model="autoApproveEvents" label="Events automatisch freigeben" description="Wenn aktiviert, werden Events dieses Kunden sofort veröffentlicht ohne Review." align="left" />
                </div>
            </x-adminv2.card>

            {{-- Damit die Eingabetaste in einem Feld speichert. --}}
            <button type="submit" class="hidden" tabindex="-1" aria-hidden="true"></button>
        </form>

        <div class="flex flex-col gap-6">
            @if ($record)
                @php
                    $stats = $this->stats;
                    [$statusLabel, $statusColor] = Index::STATUSES[$record->status] ?? [$record->status, 'zinc'];
                @endphp

                <x-adminv2.card heading="API-Tokens" description="Ein Token ist ein Jahr gültig und darf Events schreiben.">
                    <div class="flex flex-col gap-4">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            <span class="font-medium text-zinc-900 tabular-nums dark:text-white">{{ $stats['tokens'] }}</span>
                            {{ $stats['tokens'] === 1 ? 'Token vorhanden' : 'Tokens vorhanden' }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <flux:button size="sm" icon="key" wire:click="generateToken" wire:confirm="Es wird ein neuer API-Token erstellt. Der Token wird nur einmal angezeigt – bitte kopieren Sie ihn sofort.">API-Token generieren</flux:button>
                            <flux:button size="sm" variant="danger" icon="shield-exclamation" wire:click="revokeTokens" wire:confirm="Alle aktiven API-Tokens für diesen Kunden werden sofort ungültig. Der Kunde kann keine API-Aufrufe mehr durchführen.">Alle Tokens widerrufen</flux:button>
                        </div>
                    </div>
                </x-adminv2.card>

                <x-adminv2.card heading="Statistiken">
                    <dl class="flex flex-col gap-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-zinc-600 dark:text-zinc-400">Status</dt>
                            <dd><flux:badge size="sm" :color="$statusColor" inset="top bottom">{{ $statusLabel }}</flux:badge></dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-zinc-600 dark:text-zinc-400">Anzahl Events</dt>
                            <dd class="font-medium text-zinc-900 tabular-nums dark:text-white">{{ number_format($stats['events'], 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-zinc-600 dark:text-zinc-400">API-Requests (30 Tage)</dt>
                            <dd class="font-medium text-zinc-900 tabular-nums dark:text-white">{{ number_format($stats['requests'], 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-zinc-600 dark:text-zinc-400">Rate Limit</dt>
                            <dd class="font-medium text-zinc-900 tabular-nums dark:text-white">{{ $record->rate_limit }} Req/Min</dd>
                        </div>
                    </dl>
                </x-adminv2.card>

                <x-adminv2.record-tasks :record="$record" />

                <x-adminv2.card heading="Zeitstempel">
                    <dl class="flex flex-col gap-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-zinc-600 dark:text-zinc-400">Erstellt</dt>
                            <dd class="text-zinc-900 tabular-nums dark:text-white">{{ $record->created_at?->format('d.m.Y H:i') ?? '–' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-zinc-600 dark:text-zinc-400">Aktualisiert</dt>
                            <dd class="text-zinc-900 tabular-nums dark:text-white">{{ $record->updated_at?->format('d.m.Y H:i') ?? '–' }}</dd>
                        </div>
                    </dl>
                </x-adminv2.card>
            @else
                <x-adminv2.card heading="API-Tokens und Events">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        API-Tokens lassen sich erzeugen, sobald der API-Kunde gespeichert ist. Dort erscheinen auch seine Events und Kennzahlen.
                    </p>
                </x-adminv2.card>
            @endif
        </div>
    </div>

    {{-- Per API angelegte Events mit Freigabe --}}
    @if ($record)
        @php $events = $this->events; @endphp

        <x-adminv2.card heading="Events" description="Die von diesem Kunden per API angelegten Events." flush>
            <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <div class="min-w-64 flex-1">
                    <flux:input wire:model.live.debounce.300ms="eventSearch" icon="magnifying-glass" placeholder="Titel suchen …" aria-label="Events durchsuchen" clearable />
                </div>
                <div class="w-52">
                    <flux:select wire:model.live="eventReviewStatus" aria-label="Review-Status">
                        <flux:select.option value="">Alle Review-Status</flux:select.option>
                        @foreach (Editor::REVIEW_STATUSES as $value => [$label])
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            @if ($events->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-zinc-500">
                    {{ $eventSearch !== '' || $eventReviewStatus !== '' ? 'Zu Suche und Filter passt kein Event.' : 'Dieser Kunde hat noch keine Events angelegt.' }}
                </p>
            @else
                <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="eventSearch, eventReviewStatus, sortEvents, approveEvent, rejectEvent, gotoPage, nextPage, previousPage">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead class="border-b border-zinc-100 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:border-zinc-800">
                            <tr>
                                <th class="px-3 py-3 ps-5 font-medium">
                                    <button type="button" wire:click="sortEvents('title')" class="inline-flex items-center gap-1 uppercase hover:text-zinc-900 dark:hover:text-white">Titel <flux:icon :name="$sortIcon('title')" variant="micro" /></button>
                                </th>
                                <th class="px-3 py-3 font-medium">Priorität</th>
                                <th class="px-3 py-3 font-medium">Review</th>
                                <th class="px-3 py-3 font-medium">Aktiv</th>
                                <th class="px-3 py-3 font-medium">
                                    <button type="button" wire:click="sortEvents('start_date')" class="inline-flex items-center gap-1 uppercase hover:text-zinc-900 dark:hover:text-white">Start <flux:icon :name="$sortIcon('start_date')" variant="micro" /></button>
                                </th>
                                <th class="px-3 py-3 font-medium">
                                    <button type="button" wire:click="sortEvents('created_at')" class="inline-flex items-center gap-1 uppercase hover:text-zinc-900 dark:hover:text-white">Erstellt <flux:icon :name="$sortIcon('created_at')" variant="micro" /></button>
                                </th>
                                <th class="px-3 py-3 pe-5 font-medium"><span class="sr-only">Aktionen</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($events as $event)
                                @php [$reviewLabel, $reviewColor] = Editor::REVIEW_STATUSES[$event->review_status] ?? [$event->review_status, 'zinc']; @endphp
                                <tr wire:key="event-{{ $event->id }}" class="align-middle">
                                    <td class="px-3 py-3 ps-5">
                                        <a href="{{ route('adminv2.events.edit', $event->id) }}" class="font-medium text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white" title="{{ $event->title }}">{{ \Illuminate\Support\Str::limit((string) $event->title, 50) }}</a>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap"><x-adminv2.priority-badge :priority="$event->priority" /></td>
                                    <td class="px-3 py-3"><flux:badge size="sm" :color="$reviewColor" inset="top bottom">{{ $reviewLabel }}</flux:badge></td>
                                    <td class="px-3 py-3"><flux:badge size="sm" :color="$event->is_active ? 'green' : 'zinc'" inset="top bottom">{{ $event->is_active ? 'Ja' : 'Nein' }}</flux:badge></td>
                                    <td class="px-3 py-3 whitespace-nowrap tabular-nums">{{ $event->start_date?->format('d.m.Y H:i') ?? '–' }}</td>
                                    <td class="px-3 py-3 whitespace-nowrap tabular-nums">{{ $event->created_at?->format('d.m.Y H:i') ?? '–' }}</td>
                                    <td class="px-3 py-3 pe-5">
                                        @if ($event->review_status === 'pending_review')
                                            <div class="flex justify-end gap-2">
                                                <flux:button size="xs" icon="check-circle" wire:click="approveEvent({{ $event->id }})" wire:confirm="Dieses Event freigeben? Es wird damit veröffentlicht.">Freigeben</flux:button>
                                                <flux:button size="xs" variant="danger" icon="x-circle" wire:click="rejectEvent({{ $event->id }})" wire:confirm="Dieses Event ablehnen?">Ablehnen</flux:button>
                                            </div>
                                        @endif
                                    </td>
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
