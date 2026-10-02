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
        {{-- Linke Spalte: Schluessel, darunter die KI-Vorlagen --}}
        <div class="flex flex-col gap-6">
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

            {{-- KI-Vorlagen: die Auftraege fuer die Suche nach Ereignissen --}}
            <x-adminv2.card heading="KI Vorlagen" description="Die Aufträge (Prompts) für die KI-Suche nach Ereignissen. Jede hinterlegte Suche wählt eine Vorlage; ohne Auswahl gilt die Standard-Vorlage.">
                <x-slot:actions>
                    <flux:button size="sm" variant="primary" icon="plus" wire:click="createPrompt">Neue Vorlage</flux:button>
                </x-slot:actions>

                <ul class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800" wire:loading.class="opacity-60" wire:target="makeDefaultPrompt, deletePrompt, savePrompt">
                    @foreach ($this->prompts as $prompt)
                        <li wire:key="prompt-{{ $prompt->id }}" class="flex flex-wrap items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $prompt->name }}</span>
                                    @if ($prompt->is_default)
                                        <flux:badge size="sm" color="green" inset="top bottom">Standard</flux:badge>
                                    @endif
                                    <span class="text-xs text-zinc-500">
                                        {{ $prompt->is_default ? 'gilt für alle Suchen ohne eigene Vorlage' : ($prompt->profiles_count === 0 ? 'von keiner Suche gewählt' : 'gewählt von '.$prompt->profiles_count.' '.($prompt->profiles_count === 1 ? 'Suche' : 'Suchen')) }}
                                    </span>
                                </div>
                                <p class="mt-1 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $prompt->prompt }}</p>
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                @unless ($prompt->is_default)
                                    <flux:button size="sm" variant="ghost" wire:click="makeDefaultPrompt({{ $prompt->id }})">Als Standard</flux:button>
                                @endunless
                                <flux:button size="sm" icon="pencil-square" wire:click="editPrompt({{ $prompt->id }})">Bearbeiten</flux:button>
                                @unless ($prompt->is_default)
                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="deletePrompt({{ $prompt->id }})" wire:confirm="Die Vorlage „{{ $prompt->name }}“ löschen? Suchen, die sie nutzen, laufen danach mit der Standard-Vorlage." aria-label="Vorlage löschen" />
                                @endunless
                            </div>
                        </li>
                    @endforeach
                </ul>

                <p class="mt-4 text-xs text-zinc-500">
                    Die Vorlage beschreibt, wonach gesucht wird. Datum, Kategorien, Filter der Suche, die Liste des bereits Erfassten und das Antwortformat ergänzt die Plattform selbst.
                </p>
            </x-adminv2.card>
        </div>

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

    @php $latestSearch = $this->latestAiSearch; @endphp

    @if ($latestSearch?->isRunning())
        <x-adminv2.ai-search-status :search="$latestSearch" />
    @endif

    {{-- Hinterlegte Suchen: KI-Vorlage, Filter und Zeitplan – jede mit eigener Seite --}}
    <x-adminv2.card heading="Hinterlegte Suchen" description="Gesucht wird ausschließlich über diese Suchen: KI-Vorlage, Filter und Zeitplan. Der Zeitplaner führt sie zu den angegebenen Zeiten aus; die Ergebnisse stehen unter „KI Suchergebnisse“.">
        <x-slot:actions>
            <flux:button size="sm" variant="primary" icon="plus" :href="route('adminv2.system.ai.searches.create')">Neue Suche</flux:button>
        </x-slot:actions>

        @if ($this->profiles->isEmpty())
            <p class="text-sm text-zinc-500">
                Noch keine Suche hinterlegt. Lege zum Beispiel „Streiks in Europa – werktags um 7 und 13 Uhr“ an; sie läuft dann von selbst.
            </p>
        @else
            <div class="grid gap-4 lg:grid-cols-2" wire:loading.class="opacity-60" wire:target="toggleProfile, deleteProfile, runAiProfile">
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
                                <h3 class="text-base font-semibold leading-snug text-zinc-900 dark:text-white">
                                    <a href="{{ route('adminv2.system.ai.searches.edit', $profile) }}" class="hover:underline">{{ $profile->name }}</a>
                                </h3>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    @if (! $profile->is_active)
                                        <flux:badge size="sm" color="amber" inset="top bottom">Pausiert</flux:badge>
                                    @elseif ($profile->next_run_at)
                                        <flux:badge size="sm" color="green" inset="top bottom">Automatisch</flux:badge>
                                    @else
                                        <flux:badge size="sm" inset="top bottom">Nur von Hand</flux:badge>
                                    @endif
                                    <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">Vorlage: {{ $profile->promptTemplate?->name ?? 'Standard' }}</span>
                                    @if ($profile->max_results)
                                        <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">höchstens {{ $profile->max_results }} {{ $profile->max_results === 1 ? 'Ergebnis' : 'Ergebnisse' }}</span>
                                    @endif
                                    @unless ($profile->exclude_existing)
                                        <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">auch bereits Erfasstes</span>
                                    @endunless
                                </div>
                            </div>

                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" aria-label="Aktionen" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" :href="route('adminv2.system.ai.searches.edit', $profile)">Bearbeiten und Ergebnisse</flux:menu.item>
                                    <flux:menu.item icon="bolt" wire:click="runAiProfile({{ $profile->id }})">Jetzt ausführen</flux:menu.item>
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
                            <div class="flex items-start gap-2">
                                <dt class="mt-0.5 shrink-0"><flux:icon.envelope variant="mini" class="text-zinc-400" /><span class="sr-only">Benachrichtigung</span></dt>
                                <dd>
                                    @if ($recipients = $profile->recipientSummary())
                                        E-Mail an {{ $recipients }}{{ $profile->notify_when_empty ? ' – nach jedem Lauf' : ' – bei neuen Vorschlägen' }}
                                    @else
                                        Keine E-Mail-Benachrichtigung
                                    @endif
                                </dd>
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


    {{-- KI-Vorlage anlegen / bearbeiten --}}
    <flux:modal name="ai-prompt" class="md:w-[44rem]">
        <form wire:submit="savePrompt" class="flex flex-col gap-5">
            <flux:heading size="lg">{{ $promptId ? 'Vorlage bearbeiten' : 'Neue Vorlage' }}</flux:heading>

            <flux:input wire:model="promptName" label="Name" placeholder="z. B. Streiks und Verkehr" maxlength="100" />

            <flux:field>
                <flux:label>Auftrag an die KI</flux:label>
                <flux:description>Wonach die KI im Internet suchen soll – in eigenen Worten.</flux:description>
                <flux:textarea wire:model="promptText" rows="14" />
                <div class="mt-1">
                    <flux:button size="xs" variant="ghost" wire:click="fillPromptWithBuiltIn">Mitgelieferten Auftrag als Ausgangspunkt einfügen</flux:button>
                </div>
                <flux:error name="promptText" />
            </flux:field>

            <flux:switch wire:model="promptIsDefault" label="Als Standard verwenden" description="Die Standard-Vorlage gilt für jede hinterlegte Suche, die keine eigene Vorlage gewählt hat. Es gibt immer genau einen Standard." align="left" />

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ $promptId ? 'Speichern' : 'Vorlage anlegen' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
