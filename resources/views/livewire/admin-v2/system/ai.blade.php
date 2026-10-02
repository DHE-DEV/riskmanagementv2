@php
    use App\Support\AiSettings;

    $keySource = AiSettings::apiKeySource();
    $activeModel = AiSettings::model();
    $modelSource = AiSettings::modelSource();
    $models = $this->filteredModels;
    $knownIds = array_column($this->models, 'id');
@endphp

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">KI</flux:heading>
        <flux:subheading>System · Schlüssel und Modell für den KI-Assistenten und die Quellen-Prüfung.</flux:subheading>
    </div>

    <div class="grid items-start gap-6 xl:grid-cols-2">
        {{-- API-Schluessel --}}
        <x-adminv2.card heading="API-Schlüssel" description="Zugang zu OpenAI. Der Schlüssel wird verschlüsselt gespeichert und nicht wieder angezeigt.">
            <div class="flex flex-col gap-5">
                <div class="flex flex-wrap items-center gap-3">
                    @if ($keySource)
                        <flux:badge color="green" inset="top bottom">Hinterlegt</flux:badge>
                        <span class="font-mono text-sm text-zinc-800 dark:text-zinc-200">{{ AiSettings::maskedApiKey() }}</span>
                        <span class="text-sm text-zinc-500">
                            {{ $keySource === 'admin' ? 'im Admin-Bereich gespeichert' : 'aus der .env (RISK_CHARGPT_KEY)' }}
                        </span>
                    @else
                        <flux:badge color="amber" inset="top bottom">Fehlt</flux:badge>
                        <span class="text-sm text-zinc-500">Ohne Schlüssel stehen KI-Assistent und Quellen-Prüfung nicht zur Verfügung.</span>
                    @endif
                </div>

                <form wire:submit="saveApiKey" class="flex flex-col gap-3">
                    <flux:input
                        wire:model="newApiKey"
                        type="password"
                        label="{{ $keySource === 'admin' ? 'Schlüssel ersetzen' : 'Schlüssel hinterlegen' }}"
                        placeholder="sk-…"
                        autocomplete="off"
                        viewable
                    />
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:button type="submit" variant="primary" icon="key">Schlüssel speichern</flux:button>
                        @if ($keySource === 'admin')
                            <flux:button variant="ghost" wire:click="removeApiKey" wire:confirm="Den hinterlegten Schlüssel entfernen? Danach gilt wieder der Schlüssel aus der .env, falls dort einer steht.">
                                Hinterlegten Schlüssel entfernen
                            </flux:button>
                        @endif
                    </div>
                </form>

                <flux:separator />

                <div class="flex flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <flux:button icon="signal" wire:click="testConnection" :disabled="! $keySource">Verbindung testen</flux:button>
                        <span class="text-sm text-zinc-500">Schickt eine kurze Anfrage an „{{ $model ?: $activeModel }}“.</span>
                    </div>

                    <div wire:loading.flex wire:target="testConnection" class="hidden items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400" role="status">
                        <flux:icon.arrow-path variant="micro" class="animate-spin" /> Die KI wird gefragt …
                    </div>

                    @if ($testResult)
                        <div
                            wire:loading.remove
                            wire:target="testConnection"
                            @class([
                                'flex gap-2 rounded-lg border px-3 py-2 text-sm',
                                'border-green-200 bg-green-50 text-green-900 dark:border-green-400/20 dark:bg-green-400/10 dark:text-green-100' => $testResult['ok'],
                                'border-red-200 bg-red-50 text-red-900 dark:border-red-400/20 dark:bg-red-400/10 dark:text-red-100' => ! $testResult['ok'],
                            ])
                        >
                            <flux:icon :icon="$testResult['ok'] ? 'check-circle' : 'x-circle'" variant="mini" class="mt-0.5 shrink-0" />
                            <span class="min-w-0 break-words">{{ $testResult['ok'] ? 'Verbindung in Ordnung. ' : 'Verbindung fehlgeschlagen: ' }}{{ $testResult['message'] }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </x-adminv2.card>

        {{-- Modell --}}
        <x-adminv2.card heading="Modell" description="Gilt für alle KI-Funktionen. Die Liste zeigt die Modelle, die mit dem hinterlegten Schlüssel verfügbar sind.">
            <x-slot:actions>
                <flux:button size="sm" icon="arrow-path" wire:click="refreshModels" :disabled="! $keySource">Liste aktualisieren</flux:button>
            </x-slot:actions>

            <form wire:submit="saveModel" class="flex flex-col gap-4">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                    <span class="text-zinc-500">Aktuell verwendet:</span>
                    <span class="font-mono font-medium text-zinc-900 dark:text-white">{{ $activeModel }}</span>
                    <span class="text-zinc-500">
                        ({{ ['admin' => 'im Admin-Bereich gewählt', 'env' => 'aus der .env', 'default' => 'Standard'][$modelSource] }})
                    </span>
                </div>

                @if ($modelsError)
                    <flux:callout variant="danger" icon="exclamation-triangle" heading="Die Modell-Liste konnte nicht geladen werden.">
                        <flux:callout.text>{{ $modelsError }}</flux:callout.text>
                    </flux:callout>
                @endif

                @if ($this->models !== [])
                    <flux:input wire:model.live.debounce.200ms="modelSearch" icon="magnifying-glass" placeholder="Modell suchen …" clearable />

                    <div class="max-h-[28rem] overflow-y-auto rounded-xl border border-zinc-200 dark:border-zinc-700" wire:loading.class="opacity-60" wire:target="refreshModels, modelSearch">
                        @forelse ($models as $option)
                            <label
                                wire:key="model-{{ $option['id'] }}"
                                class="flex cursor-pointer items-center gap-3 border-b border-zinc-100 px-3 py-2.5 text-sm last:border-0 hover:bg-zinc-50 has-[:checked]:bg-[var(--color-accent)]/10 dark:border-zinc-800 dark:hover:bg-zinc-900"
                            >
                                <input type="radio" wire:model="model" value="{{ $option['id'] }}" class="size-4 shrink-0 accent-[var(--color-accent)]" />
                                <span class="min-w-0 flex-1 truncate font-mono text-zinc-900 dark:text-white">{{ $option['id'] }}</span>
                                @if ($option['id'] === $activeModel)
                                    <flux:badge color="green" size="sm" inset="top bottom">aktiv</flux:badge>
                                @endif
                                {{-- Preis je 1 Mio. Token: Eingabe / Ausgabe --}}
                                @if ($optionPrices = AiSettings::prices($option['id']))
                                    <span class="shrink-0 text-xs text-zinc-600 tabular-nums dark:text-zinc-400" title="US-Dollar je 1 Mio. Token: Eingabe / Ausgabe{{ AiSettings::priceSource($option['id']) === 'admin' ? ' (von Hand hinterlegt)' : '' }}">
                                        {{ rtrim(rtrim(number_format($optionPrices['input'], 2, ',', '.'), '0'), ',') }} $ / {{ rtrim(rtrim(number_format($optionPrices['output'], 2, ',', '.'), '0'), ',') }} $
                                    </span>
                                @else
                                    <span class="shrink-0 text-xs text-zinc-400" title="Für dieses Modell ist kein Preis bekannt">kein Preis</span>
                                @endif
                                @if ($option['created'])
                                    <span class="shrink-0 text-xs text-zinc-500 tabular-nums">seit {{ $option['created'] }}</span>
                                @endif
                            </label>
                        @empty
                            <p class="px-3 py-6 text-center text-sm text-zinc-500">Kein Modell passt zu „{{ $modelSearch }}“.</p>
                        @endforelse
                    </div>

                    @if (! in_array($activeModel, $knownIds, true))
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            <flux:callout.text>Das aktuell verwendete Modell „{{ $activeModel }}“ steht nicht in der Liste dieses Schlüssels.</flux:callout.text>
                        </flux:callout>
                    @endif
                @else
                    {{-- Ohne Liste (kein Schluessel oder Fehler) laesst sich der Name von Hand eintragen. --}}
                    <flux:input wire:model="model" label="Modellname" placeholder="z. B. gpt-4o-mini" description:trailing="Die Liste erscheint, sobald ein gültiger Schlüssel hinterlegt ist." />
                @endif

                <flux:error name="model" />

                <div class="flex flex-wrap items-center gap-3">
                    <flux:button type="submit" variant="primary">Modell speichern</flux:button>
                    @if ($model !== '' && $model !== $activeModel)
                        <span class="text-sm text-zinc-500">Ausgewählt: <span class="font-mono text-zinc-800 dark:text-zinc-200">{{ $model }}</span> – noch nicht gespeichert</span>
                    @endif
                </div>
            </form>

            <flux:separator class="my-5" />

            {{-- Preise fuer die Kostenanzeige --}}
            <form wire:submit="savePrices" class="flex flex-col gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Preise für „{{ $activeModel }}“</h3>
                    @php
                        $listPrices = AiSettings::listPrices($activeModel);
                        $priceText = fn (float $value) => rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
                    @endphp
                    <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                        In US-Dollar je 1 Million Token. Damit rechnet die Plattform die Kosten jeder KI-Abfrage aus.
                        @if ($listPrices)
                            Laut <a href="{{ config('ai_prices.source') }}" target="_blank" rel="noopener noreferrer" class="underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900">Preisliste von OpenAI</a>
                            (Stand {{ \Illuminate\Support\Carbon::parse(config('ai_prices.as_of'))->format('d.m.Y') }}):
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $priceText($listPrices['input']) }} $ Eingabe, {{ $priceText($listPrices['output']) }} $ Ausgabe</span>.
                            Nur ausfüllen, wenn ein anderer Preis gelten soll.
                        @else
                            Für dieses Modell steht kein Preis in der hinterlegten
                            <a href="{{ config('ai_prices.source') }}" target="_blank" rel="noopener noreferrer" class="underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900">Preisliste von OpenAI</a> – bitte hier eintragen.
                        @endif
                    </p>
                </div>

                <div class="grid items-start gap-4 sm:grid-cols-2">
                    <flux:input wire:model="priceInput" label="Eingabe ($ je 1 Mio. Token)" placeholder="{{ $listPrices ? 'Preisliste: '.$priceText($listPrices['input']) : 'z. B. 2,50' }}" inputmode="decimal" />
                    <flux:input wire:model="priceOutput" label="Ausgabe ($ je 1 Mio. Token)" placeholder="{{ $listPrices ? 'Preisliste: '.$priceText($listPrices['output']) : 'z. B. 10' }}" inputmode="decimal" />
                </div>

                <div>
                    <flux:button type="submit">Preise speichern</flux:button>
                </div>
            </form>
        </x-adminv2.card>
    </div>

    {{-- KI-Suche nach aktuellen Ereignissen --}}
    @php
        $latestSearch = $this->latestAiSearch;
        $openSuggestions = \App\Models\AiEventSuggestion::query()->open()->count();
    @endphp
    <x-adminv2.card heading="Aktuelle Ereignisse suchen" description="Die KI durchsucht das Internet nach Themen für neue Ereignisse und schlägt Priorität, Zeitraum, Event-Typen und Länder vor.">
        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <form wire:submit="saveEventSearchSettings" class="flex flex-col gap-5">
                <flux:textarea
                    wire:model="eventSearchPrompt"
                    label="Auftrag an die KI"
                    description="Wonach gesucht werden soll. Datum, Kategorien, Antwortformat und die Liste des bereits Erfassten ergänzt die Plattform selbst."
                    rows="14"
                />

                <flux:switch
                    wire:model="eventSearchExcludeExisting"
                    label="Bereits erfasste Ereignisse ausschließen – nur neue suchen"
                    description="Die KI bekommt die ausgelieferten und kürzlich angelegten Ereignisse mitgeteilt und lässt sie weg. Schon Vorgeschlagenes kommt in jedem Fall nicht noch einmal."
                    align="left"
                />

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button type="submit">Einstellungen speichern</flux:button>
                    <flux:button variant="ghost" wire:click="resetEventSearchPrompt">Standard-Auftrag wiederherstellen</flux:button>
                </div>
            </form>

            <div class="flex flex-col gap-4">
                <div>
                    <flux:button
                        variant="primary"
                        icon="sparkles"
                        wire:click="searchEventsNow"
                        wire:loading.attr="disabled"
                        wire:target="searchEventsNow"
                        :disabled="$latestSearch?->isRunning() || ! $keySource"
                    >Jetzt nach Ereignissen suchen</flux:button>
                    @unless ($keySource)
                        <p class="mt-2 text-sm text-amber-700 dark:text-amber-400">Dafür muss oben ein API-Schlüssel hinterlegt sein.</p>
                    @endunless
                </div>

                <x-adminv2.ai-search-status :search="$latestSearch" />

                <div class="rounded-xl border border-zinc-200 px-4 py-3 text-sm dark:border-zinc-800">
                    <p class="text-zinc-700 dark:text-zinc-300">
                        <span class="font-medium text-zinc-900 dark:text-white">{{ $openSuggestions }} {{ $openSuggestions === 1 ? 'offener Vorschlag' : 'offene Vorschläge' }}</span>
                        – sie stehen in der Ereignisliste im Reiter „Heute angelegt“. Dort lässt sich aus jedem Vorschlag ein Ereignis als Entwurf anlegen.
                    </p>
                    <flux:button size="sm" icon-trailing="arrow-right" :href="route('adminv2.events.index', ['tab' => 'today'])" class="mt-3">Vorschläge ansehen</flux:button>
                </div>

                @if ($this->recentAiSearches->count() > 1)
                    <div>
                        <div class="mb-1 text-xs font-medium uppercase tracking-wide text-zinc-500">Letzte Suchen</div>
                        <ul class="flex flex-col divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                            @foreach ($this->recentAiSearches as $search)
                                <li wire:key="search-{{ $search->id }}" class="flex flex-wrap items-baseline justify-between gap-x-3 py-1.5 tabular-nums">
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ $search->created_at?->format('d.m.Y H:i') }}</span>
                                    <span class="text-zinc-500">
                                        @if ($search->status === \App\Models\AiEventSearch::STATUS_DONE)
                                            {{ $search->new_count }} neu von {{ $search->found_count }}{{ $search->cost !== null ? ' · '.number_format($search->cost, 4, ',', '.').' $' : '' }}
                                        @elseif ($search->isRunning())
                                            läuft …
                                        @else
                                            {{ $search->isStale() ? 'abgebrochen' : 'fehlgeschlagen' }}
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </x-adminv2.card>
</div>
