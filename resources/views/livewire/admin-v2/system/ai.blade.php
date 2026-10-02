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
                                    <span class="text-zinc-700 dark:text-zinc-300">
                                        {{ $search->created_at?->format('d.m.Y H:i') }}
                                        <span class="text-zinc-500">· {{ $search->profile?->name ?? ($search->isTargeted() ? 'gezielt' : 'allgemein') }}{{ $search->profile_id && ! $search->started_by ? ' (automatisch)' : '' }}</span>
                                    </span>
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

    {{-- Hinterlegte Suchen: eigener Auftrag, Filter und Zeitplan --}}
    @php
        $priorityOptions = \App\Models\CustomEvent::getPriorityOptions();
        $priorityDots = ['high' => 'bg-red-500', 'medium' => 'bg-amber-500', 'low' => 'bg-sky-500', 'info' => 'bg-zinc-400'];
        $chip = 'inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-700 transition select-none hover:border-zinc-300 '
            .'has-[:checked]:border-[var(--color-accent)] has-[:checked]:bg-[var(--color-accent)] has-[:checked]:text-[var(--color-accent-foreground)] '
            .'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[var(--color-accent)]/40 '
            .'dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-600';
    @endphp
    <x-adminv2.card heading="Hinterlegte Suchen" description="Suchen mit eigenem Auftrag, Filtern und Zeitplan. Der Zeitplaner führt sie zu den angegebenen Zeiten automatisch aus; die Ergebnisse stehen bei den KI-Vorschlägen in der Ereignisliste.">
        <x-slot:actions>
            <flux:button size="sm" variant="primary" icon="plus" wire:click="createProfile">Neue Suche</flux:button>
        </x-slot:actions>

        @if ($this->profiles->isEmpty())
            <p class="text-sm text-zinc-500">
                Noch keine Suche hinterlegt. Lege zum Beispiel „Streiks in Europa – werktags um 7 und 13 Uhr“ an; sie läuft dann von selbst.
            </p>
        @else
            <div class="grid gap-4 lg:grid-cols-2" wire:loading.class="opacity-60" wire:target="toggleProfile, deleteProfile, runProfileNow, saveProfile">
                @foreach ($this->profiles as $profile)
                    @php $lastSearch = $profile->searches->first(); @endphp
                    <article
                        wire:key="profile-{{ $profile->id }}"
                        @class([
                            'flex flex-col rounded-2xl border border-s-4 border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950',
                            'border-s-green-500' => $profile->is_active && $profile->next_run_at,
                            'border-s-amber-500' => ! $profile->is_active,
                            'border-s-zinc-300 dark:border-s-zinc-600' => $profile->is_active && ! $profile->next_run_at,
                        ])
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold leading-snug text-zinc-900 dark:text-white">{{ $profile->name }}</h3>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    @if (! $profile->is_active)
                                        <flux:badge size="sm" color="amber" inset="top bottom">Pausiert</flux:badge>
                                    @elseif ($profile->next_run_at)
                                        <flux:badge size="sm" color="green" inset="top bottom">Automatisch</flux:badge>
                                    @else
                                        <flux:badge size="sm" inset="top bottom">Nur von Hand</flux:badge>
                                    @endif
                                    <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $profile->prompt ? 'eigener Auftrag' : 'Standard-Auftrag' }}</span>
                                    @unless ($profile->exclude_existing)
                                        <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">auch bereits Erfasstes</span>
                                    @endunless
                                </div>
                            </div>

                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" wire:click="editProfile({{ $profile->id }})">Bearbeiten</flux:menu.item>
                                    <flux:menu.item icon="bolt" wire:click="runProfileNow({{ $profile->id }})">Jetzt ausführen</flux:menu.item>
                                    <flux:menu.item :icon="$profile->is_active ? 'pause' : 'play'" wire:click="toggleProfile({{ $profile->id }})">{{ $profile->is_active ? 'Pausieren' : 'Fortsetzen' }}</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="deleteProfile({{ $profile->id }})" wire:confirm="Die Suche „{{ $profile->name }}“ löschen? Bereits gefundene Vorschläge bleiben erhalten.">Löschen</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                            <div class="flex items-start gap-2">
                                <dt class="mt-0.5 shrink-0"><flux:icon.clock variant="mini" class="text-zinc-400" /><span class="sr-only">Zeitplan</span></dt>
                                <dd>
                                    {{ $profile->scheduleSummary() }}
                                    @if ($profile->next_run_at)
                                        <span class="font-medium text-zinc-900 tabular-nums dark:text-white">· nächster Lauf {{ $profile->next_run_at->format('d.m.Y H:i') }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex items-start gap-2">
                                <dt class="mt-0.5 shrink-0"><flux:icon.funnel variant="mini" class="text-zinc-400" /><span class="sr-only">Filter</span></dt>
                                <dd>{{ $profile->filterSummary() ?? 'Keine Filter – sucht nach allem, was der Auftrag nennt' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-auto pt-4 text-xs tabular-nums text-zinc-500">
                            @if ($lastSearch?->isRunning())
                                <span class="inline-flex items-center gap-1 font-medium text-[var(--color-accent)]"><flux:icon.arrow-path variant="micro" class="animate-spin" /> läuft gerade …</span>
                            @elseif ($lastSearch)
                                Zuletzt {{ $lastSearch->created_at?->format('d.m.Y H:i') }}:
                                @if ($lastSearch->status === \App\Models\AiEventSearch::STATUS_DONE)
                                    {{ $lastSearch->new_count }} neu von {{ $lastSearch->found_count }}{{ $lastSearch->cost !== null ? ' · '.number_format($lastSearch->cost, 4, ',', '.').' $' : '' }}
                                @else
                                    <span class="font-medium text-red-700 dark:text-red-400">{{ $lastSearch->isStale() ? 'abgebrochen' : 'fehlgeschlagen' }}</span>
                                @endif
                            @else
                                Noch nicht gelaufen
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </x-adminv2.card>

    {{-- Hinterlegte Suche anlegen / bearbeiten --}}
    <flux:modal name="search-profile" variant="flyout" class="w-full md:w-[44rem]">
        <form wire:submit="saveProfile" class="flex flex-col gap-6">
            <div>
                <flux:heading size="lg">{{ $profileId ? 'Suche bearbeiten' : 'Neue Suche' }}</flux:heading>
                <flux:text class="mt-1">Auftrag, Filter und Zeitplan einer Suche, die die KI automatisch ausführt.</flux:text>
            </div>

            <flux:input wire:model="profileName" label="Name" placeholder="z. B. Streiks in Europa" maxlength="100" />

            <flux:field>
                <flux:label>Auftrag an die KI</flux:label>
                <flux:description>Leer lassen, um den Standard-Auftrag zu verwenden. Datum, Kategorien, Filter und Antwortformat ergänzt die Plattform selbst.</flux:description>
                <flux:textarea wire:model="profilePrompt" rows="6" placeholder="Leer = Standard-Auftrag" />
                <div class="mt-1">
                    <flux:button size="xs" variant="ghost" wire:click="fillProfilePromptWithDefault">Standard-Auftrag als Vorlage einfügen</flux:button>
                </div>
                <flux:error name="profilePrompt" />
            </flux:field>

            <flux:separator text="Filter" />

            <flux:field>
                <flux:label>Länder</flux:label>
                <flux:description>Keines gewählt = alle Länder. Um nur einzelne Länder auszunehmen: „Alle auswählen“ und die unerwünschten abwählen.</flux:description>
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <flux:button size="xs" icon="check" wire:click="selectAllProfileCountries">Alle auswählen</flux:button>
                    <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="clearProfileCountries">Alle abwählen</flux:button>
                    <span class="text-xs tabular-nums text-zinc-500">
                        @php $countryTotal = $this->countryOptions->count(); $countrySelected = count($profileCountries); @endphp
                        @if ($countrySelected === 0)
                            keine Eingrenzung
                        @elseif ($countrySelected >= $countryTotal)
                            alle {{ $countryTotal }} gewählt
                        @else
                            {{ $countrySelected }} von {{ $countryTotal }} gewählt{{ $countryTotal - $countrySelected <= 20 ? ' – '.($countryTotal - $countrySelected).' ausgenommen' : '' }}
                        @endif
                    </span>
                </div>
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700" x-data="{ query: '' }">
                    <div class="border-b border-zinc-100 p-1.5 dark:border-zinc-800">
                        <input type="search" x-model="query" placeholder="Land oder Code suchen" autocomplete="off" aria-label="Land suchen" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm outline-none placeholder:text-zinc-400 focus:border-[var(--color-accent)] dark:border-zinc-700 dark:bg-zinc-800 dark:text-white" />
                    </div>
                    <div class="flex max-h-44 flex-col overflow-y-auto p-1">
                        @foreach ($this->countryOptions as $country)
                            @php $iso = strtoupper((string) $country->iso_code); @endphp
                            <label
                                wire:key="profile-country-{{ $iso }}"
                                x-show="query.trim() === '' || @js(mb_strtolower($country->getName('de').' '.$iso)).includes(query.trim().toLowerCase())"
                                class="flex cursor-pointer items-center gap-2.5 rounded-md px-2.5 py-1.5 text-sm text-zinc-800 hover:bg-zinc-100 has-[:checked]:font-medium dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                <input type="checkbox" wire:model.live.debounce.400ms="profileCountries" value="{{ $iso }}" class="size-4 shrink-0 rounded border-zinc-300 accent-[var(--color-accent)]" />
                                <span class="min-w-0 flex-1 truncate">{{ $country->getName('de') }}</span>
                                <span class="shrink-0 font-mono text-xs text-zinc-400">{{ $iso }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <flux:error name="profileCountries" />
            </flux:field>

            <flux:field>
                <flux:label>Event-Typen</flux:label>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->eventTypeOptions as $eventType)
                        <label wire:key="profile-type-{{ $eventType->code }}" class="{{ $chip }}">
                            <input type="checkbox" wire:model="profileTypes" value="{{ $eventType->code }}" class="sr-only" />
                            <i class="fas {{ $eventType->icon ?: 'fa-map-marker' }} text-xs opacity-70" aria-hidden="true"></i>
                            {{ $eventType->name }}
                        </label>
                    @endforeach
                </div>
            </flux:field>

            <flux:field>
                <flux:label>Priorität</flux:label>
                <div class="flex flex-wrap gap-2">
                    @foreach ($priorityOptions as $value => $label)
                        <label wire:key="profile-priority-{{ $value }}" class="{{ $chip }}">
                            <input type="checkbox" wire:model="profilePriorities" value="{{ $value }}" class="sr-only" />
                            <span class="size-2 rounded-full {{ $priorityDots[$value] ?? 'bg-zinc-400' }}"></span>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </flux:field>

            <flux:field>
                <flux:label>Zeitraum der Ereignisse</flux:label>
                <flux:description>Wie weit in die Zukunft die KI schauen soll. Gerechnet wird jedes Mal ab dem Tag, an dem die Suche läuft.</flux:description>
                <flux:select wire:model.live="profileDaysAhead">
                    @foreach ($this->profilePeriodOptions() as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <p class="mt-2 text-xs text-zinc-500">
                    @if ($profileDaysAhead === '')
                        Die KI sucht ohne Vorgabe eines Zeitraums – maßgeblich ist, was im Auftrag steht.
                    @else
                        Beispiel: Läuft die Suche heute ({{ now()->format('d.m.Y') }}), findet die KI nur Ereignisse, die
                        {{ (int) $profileDaysAhead === 0 ? 'am '.now()->format('d.m.Y') : 'zwischen dem '.now()->format('d.m.Y').' und dem '.now()->addDays((int) $profileDaysAhead)->format('d.m.Y') }}
                        stattfinden oder sich dann auswirken – auch angekündigte.
                    @endif
                </p>
                <flux:error name="profileDaysAhead" />
            </flux:field>

            <flux:input wire:model="profileKeyword" label="Stichwort" placeholder="z. B. Bahn" maxlength="200" description:trailing="Optional: Die KI sucht dann nur nach Ereignissen zu diesem Begriff." />

            <flux:switch wire:model="profileExcludeExisting" label="Bereits erfasste Ereignisse ausschließen – nur neue suchen" align="left" />

            <flux:separator text="Zeitplan" />

            <flux:field>
                <flux:label>Wochentage</flux:label>
                <flux:description>Keiner gewählt = jeden Tag.</flux:description>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Models\AiEventSearchProfile::WEEKDAYS_SHORT as $value => $label)
                        <label wire:key="profile-weekday-{{ $value }}" class="{{ $chip }}">
                            <input type="checkbox" wire:model="profileWeekdays" value="{{ $value }}" class="sr-only" />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </flux:field>

            <flux:field>
                <flux:label>Uhrzeiten</flux:label>
                <flux:description>Zu jeder Uhrzeit läuft die Suche einmal. Ohne Uhrzeit läuft sie nur von Hand.</flux:description>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($profileTimes as $index => $time)
                        <div wire:key="profile-time-{{ $index }}" class="flex items-center gap-1">
                            <div class="w-32"><flux:input wire:model="profileTimes.{{ $index }}" type="time" aria-label="Uhrzeit {{ $index + 1 }}" /></div>
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeProfileTime({{ $index }})" aria-label="Uhrzeit entfernen" />
                        </div>
                    @endforeach
                    <flux:button size="sm" icon="plus" wire:click="addProfileTime">Uhrzeit</flux:button>
                </div>
                <flux:error name="profileTimes" />
                @foreach ($profileTimes as $index => $time)
                    <flux:error name="profileTimes.{{ $index }}" />
                @endforeach
            </flux:field>

            <flux:switch wire:model="profileActive" label="Aktiv" description="Ausgeschaltet läuft die Suche nicht automatisch." align="left" />

            <p class="text-xs text-zinc-500">
                Jeder Lauf kostet Token (siehe „Letzte Suchen“). Der Zeitplaner prüft alle fünf Minuten; eine Suche startet also spätestens fünf Minuten nach der Uhrzeit.
            </p>

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ $profileId ? 'Speichern' : 'Suche anlegen' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
