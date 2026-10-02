@php
    use App\Models\AiEventSearch;
    use App\Models\AiEventSearchPrompt;
    use App\Support\AiSettings;

    $profile = $this->profile;
    $latestSearch = $this->latestAiSearch;
    $defaultPrompt = AiEventSearchPrompt::default();
    $shownPrompt = $promptId !== '' ? $this->prompts->firstWhere('id', (int) $promptId) : $defaultPrompt;
    $typeNames = $this->eventTypeOptions->pluck('name', 'code');
    $typeIcons = $this->eventTypeOptions->pluck('icon', 'code');

    $priorityOptions = \App\Models\CustomEvent::getPriorityOptions();
    $priorityDots = ['high' => 'bg-red-500', 'medium' => 'bg-amber-500', 'low' => 'bg-sky-500', 'info' => 'bg-zinc-400'];
    $chip = 'inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-700 transition select-none hover:border-zinc-300 '
        .'has-[:checked]:border-[var(--color-accent)] has-[:checked]:bg-[var(--color-accent)] has-[:checked]:text-[var(--color-accent-foreground)] '
        .'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[var(--color-accent)]/40 '
        .'dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-600';
@endphp

<div class="flex flex-col gap-6">
    <form wire:submit="save" class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <a href="{{ route('adminv2.system.ai') }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                    <flux:icon.arrow-left variant="micro" /> System › KI
                </a>
                <flux:heading size="xl" level="1" class="mt-1">{{ $profile ? $profile->name : 'Neue Suche' }}</flux:heading>
                <flux:subheading>Hinterlegte KI-Suche nach Ereignissen: Vorlage, Filter, Zeitplan und wer das Ergebnis per E-Mail bekommt.</flux:subheading>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($profile)
                    <flux:button variant="ghost" icon="trash" wire:click="delete" wire:confirm="Diese Suche löschen? Bereits gefundene Vorschläge bleiben erhalten." class="!text-red-600 dark:!text-red-400">Löschen</flux:button>
                    <flux:button icon="bolt" wire:click="runNow" wire:loading.attr="disabled" wire:target="runNow" :disabled="(bool) $latestSearch?->isRunning()">Jetzt ausführen</flux:button>
                @endif
                <flux:button type="submit" variant="primary" icon="check">{{ $profile ? 'Speichern' : 'Suche anlegen' }}</flux:button>
            </div>
        </div>

        @if ($latestSearch?->isRunning())
            <x-adminv2.ai-search-status :search="$latestSearch" />
        @endif

        <div class="grid items-start gap-6 xl:grid-cols-2">
            <div class="flex flex-col gap-6">
                <x-adminv2.card heading="Suche" description="Name und der Auftrag an die KI.">
                    <div class="flex flex-col gap-5">
                        <flux:input wire:model="name" label="Name" placeholder="z. B. Streiks in Europa" maxlength="100" />

                        <flux:field>
                            <flux:label>KI-Vorlage</flux:label>
                            <flux:description>Der Auftrag, mit dem die KI sucht. Ohne Auswahl gilt die Standard-Vorlage – auch wenn später eine andere zum Standard wird.</flux:description>
                            <flux:select wire:model.live="promptId">
                                <flux:select.option value="">Standard-Vorlage{{ $defaultPrompt ? ' („'.$defaultPrompt->name.'“)' : '' }}</flux:select.option>
                                @foreach ($this->prompts as $prompt)
                                    <flux:select.option value="{{ $prompt->id }}">{{ $prompt->name }}{{ $prompt->is_default ? ' (derzeit Standard)' : '' }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            @if ($shownPrompt)
                                <details class="mt-2 text-sm">
                                    <summary class="cursor-pointer text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Auftrag der Vorlage „{{ $shownPrompt->name }}“ ansehen</summary>
                                    <p class="mt-2 rounded-lg bg-zinc-50 p-3 text-sm whitespace-pre-line text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ $shownPrompt->prompt }}</p>
                                </details>
                            @endif
                            <p class="mt-2 text-xs text-zinc-500">Vorlagen werden unter <a href="{{ route('adminv2.system.ai') }}" class="underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900">System › KI</a> gepflegt.</p>
                            <flux:error name="promptId" />
                        </flux:field>
                    </div>
                </x-adminv2.card>

                <x-adminv2.card heading="Filter" description="Grenzt ein, was die KI liefern soll – wie die Filter der Ereignisliste.">
                    <div class="flex flex-col gap-5">
                        <flux:field>
                            <flux:label>Länder</flux:label>
                            <flux:description>Keines gewählt = alle Länder. Um nur einzelne Länder auszunehmen: „Alle auswählen“ und die unerwünschten abwählen.</flux:description>
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <flux:button size="xs" icon="check" wire:click="selectAllCountries">Alle auswählen</flux:button>
                                <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="clearCountries">Alle abwählen</flux:button>
                                <span class="text-xs tabular-nums text-zinc-500">
                                    @php $countryTotal = $this->countryOptions->count(); $countrySelected = count($countries); @endphp
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
                                            <input type="checkbox" wire:model.live.debounce.400ms="countries" value="{{ $iso }}" class="size-4 shrink-0 rounded border-zinc-300 accent-[var(--color-accent)]" />
                                            <span class="min-w-0 flex-1 truncate">{{ $country->getName('de') }}</span>
                                            <span class="shrink-0 font-mono text-xs text-zinc-400">{{ $iso }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <flux:error name="countries" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Event-Typen</flux:label>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->eventTypeOptions as $eventType)
                                    <label wire:key="profile-type-{{ $eventType->code }}" class="{{ $chip }}">
                                        <input type="checkbox" wire:model="types" value="{{ $eventType->code }}" class="sr-only" />
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
                                        <input type="checkbox" wire:model="priorities" value="{{ $value }}" class="sr-only" />
                                        <span class="size-2 rounded-full {{ $priorityDots[$value] ?? 'bg-zinc-400' }}"></span>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </flux:field>

                        <flux:field>
                            <flux:label>Zeitraum der Ereignisse</flux:label>
                            <flux:description>Wie weit in die Zukunft die KI schauen soll. Gerechnet wird jedes Mal ab dem Tag, an dem die Suche läuft.</flux:description>
                            <flux:select wire:model.live="daysAhead">
                                @foreach ($this->periodOptions() as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <p class="mt-2 text-xs text-zinc-500">
                                @if ($daysAhead === '')
                                    Die KI sucht ohne Vorgabe eines Zeitraums – maßgeblich ist, was im Auftrag steht.
                                @else
                                    Beispiel: Läuft die Suche heute ({{ now()->format('d.m.Y') }}), findet die KI nur Ereignisse, die
                                    {{ (int) $daysAhead === 0 ? 'am '.now()->format('d.m.Y') : 'zwischen dem '.now()->format('d.m.Y').' und dem '.now()->addDays((int) $daysAhead)->format('d.m.Y') }}
                                    stattfinden oder sich dann auswirken – auch angekündigte.
                                @endif
                            </p>
                            <flux:error name="daysAhead" />
                        </flux:field>

                        <flux:input wire:model="keyword" label="Stichwort" placeholder="z. B. Bahn" maxlength="200" description:trailing="Optional: Die KI sucht dann nur nach Ereignissen zu diesem Begriff." />

                        <div class="max-w-sm">
                            <flux:input
                                wire:model="maxResults"
                                type="number"
                                min="1"
                                max="{{ AiSettings::EVENT_SEARCH_MAX_RESULTS_LIMIT }}"
                                label="Höchstens so viele Ergebnisse je Lauf"
                                placeholder="Standard: {{ AiSettings::eventSearchMaxResults() }}"
                                description:trailing="Leer = der Standard von oben. Die KI liefert die wichtigsten zuerst."
                            />
                        </div>

                        <flux:switch wire:model="excludeExisting" label="Bereits erfasste Ereignisse ausschließen – nur neue suchen" align="left" />

            
                    </div>
                </x-adminv2.card>
            </div>

            <div class="flex flex-col gap-6">
                <x-adminv2.card heading="Zeitplan" :description="$profile ? $profile->scheduleSummary().($profile->next_run_at ? ' · nächster Lauf '.$profile->next_run_at->format('d.m.Y H:i') : '') : 'Wann die Suche automatisch läuft.'">
                    <div class="flex flex-col gap-5">
                        <flux:field>
                            <flux:label>Wochentage</flux:label>
                            <flux:description>Keiner gewählt = jeden Tag.</flux:description>
                            <div class="flex flex-wrap gap-2">
                                @foreach (\App\Models\AiEventSearchProfile::WEEKDAYS_SHORT as $value => $label)
                                    <label wire:key="profile-weekday-{{ $value }}" class="{{ $chip }}">
                                        <input type="checkbox" wire:model="weekdays" value="{{ $value }}" class="sr-only" />
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </flux:field>

                        <flux:field>
                            <flux:label>Uhrzeiten</flux:label>
                            <flux:description>Zu jeder Uhrzeit läuft die Suche einmal. Ohne Uhrzeit läuft sie nur von Hand.</flux:description>
                            <div class="flex flex-wrap items-center gap-2">
                                @foreach ($times as $index => $time)
                                    <div wire:key="profile-time-{{ $index }}" class="flex items-center gap-1">
                                        <div class="w-32"><flux:input wire:model="times.{{ $index }}" type="time" aria-label="Uhrzeit {{ $index + 1 }}" /></div>
                                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeTime({{ $index }})" aria-label="Uhrzeit entfernen" />
                                    </div>
                                @endforeach
                                <flux:button size="sm" icon="plus" wire:click="addTime">Uhrzeit</flux:button>
                            </div>
                            <flux:error name="times" />
                            @foreach ($times as $index => $time)
                                <flux:error name="times.{{ $index }}" />
                            @endforeach
                        </flux:field>

                        <flux:switch wire:model="active" label="Aktiv" description="Ausgeschaltet läuft die Suche nicht automatisch." align="left" />

            
                    </div>
                </x-adminv2.card>

                <x-adminv2.card heading="Benachrichtigung per E-Mail" description="Wer nach einem Lauf das Ergebnis bekommt – mit Link direkt zum Suchergebnis.">
                    <div class="flex flex-col gap-5">
                        <flux:field>
                            <flux:label>Benutzer</flux:label>
                            <flux:description>Wer das Ergebnis jedes Laufs per E-Mail bekommt – mit Link direkt zum Suchergebnis.</flux:description>
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700" x-data="{ query: '' }">
                                <div class="border-b border-zinc-100 p-1.5 dark:border-zinc-800">
                                    <input type="search" x-model="query" placeholder="Name suchen" autocomplete="off" aria-label="Benutzer suchen" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm outline-none placeholder:text-zinc-400 focus:border-[var(--color-accent)] dark:border-zinc-700 dark:bg-zinc-800 dark:text-white" />
                                </div>
                                <div class="flex max-h-40 flex-col overflow-y-auto p-1">
                                    @foreach ($this->notifyUserOptions as $user)
                                        <label
                                            wire:key="profile-notify-user-{{ $user->id }}"
                                            x-show="query.trim() === '' || @js(mb_strtolower(trim($user->name))).includes(query.trim().toLowerCase())"
                                            class="flex cursor-pointer items-center gap-2.5 rounded-md px-2.5 py-1.5 text-sm text-zinc-800 hover:bg-zinc-100 has-[:checked]:font-medium dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        >
                                            <input type="checkbox" wire:model="notifyUsers" value="{{ $user->id }}" class="size-4 shrink-0 rounded border-zinc-300 accent-[var(--color-accent)]" />
                                            <span class="min-w-0 flex-1 truncate">{{ trim($user->name) }}</span>
                                            <span class="shrink-0 truncate text-xs text-zinc-400">{{ $user->email }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <flux:error name="notifyUsers" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Teams</flux:label>
                            @if ($this->notifyTeamOptions->isEmpty())
                                <flux:description>Es gibt noch keine Teams (System › Teams).</flux:description>
                            @else
                                <flux:description>Je nach Einstellung des Teams geht die Mail an dessen zentrale Adresse oder an jedes Mitglied.</flux:description>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($this->notifyTeamOptions as $team)
                                        <label wire:key="profile-notify-team-{{ $team->id }}" class="{{ $chip }}">
                                            <input type="checkbox" wire:model="notifyTeams" value="{{ $team->id }}" class="sr-only" />
                                            <flux:icon.user-group variant="micro" class="opacity-70" />
                                            {{ $team->name }}
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                            <flux:error name="notifyTeams" />
                        </flux:field>

                        <flux:switch wire:model="notifyWhenEmpty" label="Auch benachrichtigen, wenn nichts Neues gefunden wurde" description="Ohne diesen Schalter kommt eine Mail nur bei neuen Vorschlägen – und wenn die Suche fehlschlägt." align="left" />

                        <p class="text-xs text-zinc-500">
                            Jeder Lauf kostet Token (die Kosten stehen unten bei den Läufen). Der Zeitplaner prüft alle fünf Minuten; eine Suche startet also spätestens fünf Minuten nach der Uhrzeit.
                        </p>
                    </div>
                </x-adminv2.card>
            </div>
        </div>
    </form>

    {{-- Die Laeufe dieser Suche mit ihren Ergebnissen, der letzte zuerst --}}
    @if ($profile)
        <div>
            <flux:heading size="lg" level="2">Suchen und Ergebnisse</flux:heading>
            <flux:subheading>
                {{ $runs->total() === 0 ? 'Diese Suche ist noch nicht gelaufen.' : $runs->total().' '.($runs->total() === 1 ? 'Lauf' : 'Läufe').' – der letzte zuerst. Bei übernommenen Ergebnissen steht, welcher Text im Passolution Ereignis gelandet ist.' }}
            </flux:subheading>
        </div>

        <div class="flex flex-col gap-6" wire:loading.class="opacity-60" wire:target="createDraftFromSuggestion, dismissSuggestion, restoreSuggestion, gotoPage, nextPage, previousPage">
            @foreach ($runs as $run)
                <section wire:key="run-{{ $run->id }}" class="rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
                    <header class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold tabular-nums text-zinc-900 dark:text-white">
                                {{ $run->created_at?->format('d.m.Y H:i') }}
                                <span class="text-sm font-normal text-zinc-500">· {{ $run->starter ? 'von '.trim($run->starter->name) : 'automatisch' }}</span>
                            </h3>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">
                                Vorlage „{{ $run->prompt_name ?? 'Standard' }}“
                                · {{ $run->exclude_existing ? 'nur Neues' : 'auch bereits Erfasstes' }}
                                @if ($run->filterSummary()) · {{ $run->filterSummary() }} @endif
                            </p>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-3 text-sm">
                            @if ($run->isRunning())
                                <span class="inline-flex items-center gap-1 font-medium text-[var(--color-accent)]"><flux:icon.arrow-path variant="micro" class="animate-spin" /> läuft …</span>
                            @elseif ($run->status === AiEventSearch::STATUS_DONE)
                                <span class="tabular-nums text-zinc-900 dark:text-white">{{ $run->found_count }} gefunden, {{ $run->new_count }} neu</span>
                                @if ($run->cost !== null)
                                    <span class="tabular-nums text-zinc-500">{{ number_format($run->cost, 4, ',', '.') }} $</span>
                                @endif
                                @if ($run->notified_at)
                                    <span class="inline-flex items-center gap-1 text-zinc-500" title="Ergebnis-Mail verschickt am {{ $run->notified_at->format('d.m.Y H:i') }}"><flux:icon.envelope variant="micro" /> Mail verschickt</span>
                                @endif
                            @else
                                <flux:badge color="red" size="sm" inset="top bottom">{{ $run->isStale() ? 'Abgebrochen' : 'Fehlgeschlagen' }}</flux:badge>
                            @endif
                        </div>
                    </header>

                    <div class="p-5">
                        @if ($run->status === AiEventSearch::STATUS_FAILED && $run->error)
                            <p class="text-sm text-red-700 dark:text-red-400">{{ $run->error }}</p>
                        @elseif ($run->suggestions->isEmpty())
                            <p class="text-sm text-zinc-500">
                                {{ $run->isRunning() ? 'Die Ergebnisse erscheinen, sobald die Suche fertig ist.' : 'Dieser Lauf hat nichts Neues ergeben'.($run->found_count > 0 ? ' – die '.$run->found_count.' gefundenen Themen waren bereits erfasst oder vorgeschlagen.' : '.') }}
                            </p>
                        @else
                            <div class="grid gap-4 lg:grid-cols-2">
                                @foreach ($run->suggestions as $suggestion)
                                    <x-adminv2.ai-suggestion-card
                                        wire:key="suggestion-{{ $suggestion->id }}"
                                        :suggestion="$suggestion"
                                        :type-names="$typeNames"
                                        :type-icons="$typeIcons"
                                        :country-names="$countryNames"
                                    />
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>

        @if ($runs->hasPages())
            <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-500">
                <span class="tabular-nums">Lauf {{ $runs->firstItem() }}–{{ $runs->lastItem() }} von {{ $runs->total() }}</span>
                <div class="flex items-center gap-2">
                    <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="previousPage" :disabled="$runs->onFirstPage()">Zurück</flux:button>
                    <span class="tabular-nums">Seite {{ $runs->currentPage() }} von {{ $runs->lastPage() }}</span>
                    <flux:button size="sm" variant="ghost" icon-trailing="chevron-right" wire:click="nextPage" :disabled="! $runs->hasMorePages()">Weiter</flux:button>
                </div>
            </div>
        @endif
    @endif
</div>
