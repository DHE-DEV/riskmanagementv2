{{--
    Fenster der KI-Pruefungen eines Abschnitts (Trait RunsAiChecks).

    - area / section: Bereich und offener Abschnitt
    - checks: die hinterlegten Pruefungen; data: Bezeichnung => Wert, was an die KI geht
    - checkId / result / error: Zustand
    - title: Bezeichnung des Eintrags, z. B. "Deutschland"
    - noteKeys: Felder mit Notiz – die Begruendung der KI laesst sich dorthin uebernehmen
    - promptDraft: Prompt der gewaehlten Pruefung fuer diesen Lauf ($aiPromptDraft) – anpassbar,
      ohne die hinterlegte Pruefung zu aendern
--}}
@props([
    'area',
    'section' => '',
    'checks',
    'data' => [],
    'checkId' => '',
    'result' => null,
    'error' => null,
    'title' => null,
    'models' => [],
    'saveAsCheck' => false,
    'review' => null,
    'noteKeys' => [],
    'promptDraft' => '',
])

@php
    use App\Support\AdminV2\AiAreas;
    use App\Support\AiSettings;

    $chosen = $checkId === 'custom' ? null : $checks->firstWhere('id', (int) $checkId);
    $promptEdited = $chosen && trim((string) $promptDraft) !== trim((string) $chosen->prompt);
    $custom = $checkId === 'custom';
    $reviewMode = $checkId === 'review';
    $canReview = $section !== '' && $section !== AiAreas::GENERAL;
    $reviewFields = $review && ($review['section'] ?? null) === $section ? $review['fields'] : [];
    $openSuggestions = collect($reviewFields)->filter(fn ($field) => $field['status'] === 'change' && ! ($field['applied'] ?? false))->count();
    $reviewCounts = array_count_values(array_column($reviewFields, 'status'));
    $openNotes = collect($reviewFields)->filter(fn ($field, $key) => in_array($key, $noteKeys, true) && $field['note'] && ! ($field['note_applied'] ?? false))->count();

    // Bezeichnungen wie "Sicherheit › Kriminalitätsniveau" werden nach ihrem Bereich gruppiert.
    $reviewGroups = [];
    foreach ($reviewFields as $key => $field) {
        [$group, $label] = array_pad(explode(' › ', $data[$key]['label'] ?? $key, 2), -2, '');
        $reviewGroups[$group][$key] = $field + ['label' => $label];
    }
    $placeholders = $section !== '' ? AiAreas::placeholders($area, $section) : [];
    $sectionLabel = $section !== '' ? AiAreas::sectionLabel($area, $section) : '';
    $manageUrl = route('adminv2.system.ai', ['tab' => $area]);
@endphp

<flux:modal name="ai-check" class="max-w-3xl md:w-[48rem]">
    {{-- "busy" zeigt waehrend des Laufs deutlich, dass die KI arbeitet, und sperrt die Schaltflaechen gegen Doppelklicks. --}}
    <div class="flex flex-col gap-5" x-data="{ busy: false, run(method) { if (this.busy) return; this.busy = true; $wire[method]().finally(() => this.busy = false) } }">
        <div>
            <flux:heading size="lg">KI-Prüfung · {{ $sectionLabel }}</flux:heading>
            @if ($title)
                <flux:subheading>{{ $title }}</flux:subheading>
            @endif
        </div>

        {{-- Pruefung waehlen – oder einen eigenen Prompt schreiben --}}
        <div class="flex flex-col gap-2" role="radiogroup" aria-label="Prüfung">
            @foreach ($checks as $check)
                <label wire:key="ai-check-{{ $check->id }}" class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 px-3 py-2.5 transition hover:border-zinc-300 has-[:checked]:border-[var(--color-accent)] has-[:checked]:bg-[var(--color-accent)]/5 dark:border-zinc-700 dark:hover:border-zinc-600">
                    <input type="radio" wire:model.live="aiCheckId" value="{{ $check->id }}" x-bind:disabled="busy" class="mt-1 size-4 shrink-0 accent-[var(--color-accent)]" />
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $check->name }}</span>
                            <span class="font-mono text-xs text-zinc-500">{{ $check->model ?: AiSettings::model().' (Standard)' }}</span>
                            @if ($check->createsTasks())
                                <flux:badge size="sm" color="sky" icon="clipboard-document-check" inset="top bottom" title="Wenn: {{ $check->task_condition }}">legt Aufgaben an</flux:badge>
                            @endif
                            @if ($section === AiAreas::GENERAL && $check->section !== null && $check->section !== AiAreas::GENERAL)
                                <flux:badge size="sm" color="zinc" inset="top bottom">{{ AiAreas::sectionLabel($area, $check->section) }}</flux:badge>
                            @elseif ($check->section === null)
                                <flux:badge size="sm" color="zinc" inset="top bottom">alle Abschnitte</flux:badge>
                            @elseif ($check->section === AiAreas::GENERAL)
                                <flux:badge size="sm" color="zinc" inset="top bottom">gesamter Eintrag</flux:badge>
                            @endif
                        </span>
                        @if ($check->description)
                            <span class="mt-0.5 block text-sm text-zinc-600 dark:text-zinc-400">{{ $check->description }}</span>
                        @endif
                    </span>
                </label>
            @endforeach

            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-dashed border-zinc-300 px-3 py-2.5 transition hover:border-zinc-400 has-[:checked]:border-solid has-[:checked]:border-[var(--color-accent)] has-[:checked]:bg-[var(--color-accent)]/5 dark:border-zinc-600">
                <input type="radio" wire:model.live="aiCheckId" value="custom" x-bind:disabled="busy" class="mt-1 size-4 shrink-0 accent-[var(--color-accent)]" />
                <span class="min-w-0 flex-1">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">Eigener Prompt</span>
                    <span class="mt-0.5 block text-sm text-zinc-600 dark:text-zinc-400">Einmalig eine eigene Frage stellen – die Daten des Abschnitts gehen mit.</span>
                </span>
            </label>

            @if ($canReview)
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-dashed border-zinc-300 px-3 py-2.5 transition hover:border-zinc-400 has-[:checked]:border-solid has-[:checked]:border-[var(--color-accent)] has-[:checked]:bg-[var(--color-accent)]/5 dark:border-zinc-600">
                    <input type="radio" wire:model.live="aiCheckId" value="review" x-bind:disabled="busy" class="mt-1 size-4 shrink-0 accent-[var(--color-accent)]" />
                    <span class="min-w-0 flex-1">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">Felder automatisch prüfen</span>
                        <span class="mt-0.5 block text-sm text-zinc-600 dark:text-zinc-400">Die KI bewertet jedes Feld dieses Abschnitts: korrekt, Vorschlag oder nicht prüfbar. Vorschläge lassen sich einzeln oder alle übernehmen.</span>
                    </span>
                </label>
            @endif
        </div>

        @if ($reviewMode)
            <div class="flex flex-col gap-4 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                <flux:field>
                    <flux:label>Modell</flux:label>
                    <flux:select wire:model="aiCustomModel">
                        <flux:select.option value="">Standardmodell ({{ AiSettings::model() }})</flux:select.option>
                        @foreach ($models as $modelId)
                            <flux:select.option value="{{ $modelId }}">{{ $modelId }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>
        @endif

        @if ($checks->isEmpty())
            <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                Für diesen Abschnitt ist noch keine KI-Prüfung hinterlegt –
                <a href="{{ $manageUrl }}" target="_blank" class="text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">unter System › KI anlegen</a>
                oder unten direkt einen eigenen Prompt schreiben.
            </p>
        @endif

        @if ($custom)
            <div class="flex flex-col gap-4 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                <flux:field>
                    <flux:label>Prompt</flux:label>
                    <flux:description>
                        Platzhalter (Klick kopiert):
                        @foreach ($placeholders as $key => $label)
                            <x-adminv2.placeholder :name="$key" :label="$label" class="!bg-white" />
                        @endforeach
                        <x-adminv2.placeholder name="daten" label="Alle Angaben des Abschnitts als Liste" class="!bg-white" />
                        – ohne Platzhalter werden die Angaben des Abschnitts automatisch angehängt.
                    </flux:description>
                    <flux:textarea wire:model="aiCustomPrompt" rows="6" placeholder="z. B. Prüfe diese Angaben auf Plausibilität und nenne Abweichungen mit Quelle." />
                    <flux:error name="aiCustomPrompt" />
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Modell</flux:label>
                        <flux:select wire:model="aiCustomModel">
                            <flux:select.option value="">Standardmodell ({{ AiSettings::model() }})</flux:select.option>
                            @foreach ($models as $modelId)
                                <flux:select.option value="{{ $modelId }}">{{ $modelId }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Als Prüfung speichern</flux:label>
                        <flux:switch wire:model.live="aiSaveAsCheck" label="Für diesen Abschnitt hinterlegen" align="left" />
                        @if ($saveAsCheck)
                            <flux:input wire:model="aiCheckName" placeholder="Name der Prüfung" maxlength="100" class="mt-2" />
                        @endif
                        <flux:error name="aiCheckName" />
                    </flux:field>
                </div>
            </div>
        @endif

        @if ($chosen)
            {{-- Der Prompt laesst sich fuer diesen einen Lauf anpassen; aufgeklappt bleibt er auch ueber Aktualisierungen hinweg. --}}
            <div wire:key="ai-prompt-{{ $chosen->id }}" class="text-sm" x-data="{ open: @js($promptEdited || $errors->has('aiPromptDraft')) }">
                <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" class="inline-flex flex-wrap items-center gap-1.5 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                    <flux:icon.chevron-right variant="micro" class="transition-transform" x-bind:class="open && 'rotate-90'" />
                    KI Prompt ansehen und anpassen
                    @if ($promptEdited)
                        <flux:badge size="sm" color="amber" inset="top bottom">für diesen Lauf angepasst</flux:badge>
                    @endif
                </button>

                <div x-show="open" x-cloak class="mt-2 flex flex-col gap-2">
                    <flux:textarea wire:model.blur="aiPromptDraft" rows="8" aria-label="KI Prompt" x-bind:disabled="busy" class="font-mono text-xs" />
                    <flux:error name="aiPromptDraft" />
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500">
                        <span>
                            Änderungen gelten nur für diesen Lauf – die hinterlegte Prüfung „{{ $chosen->name }}“ bleibt unverändert.
                            @if ($promptEdited && $chosen->createsTasks()) Mit angepasstem Prompt entsteht keine Aufgabe. @endif
                        </span>
                        @if ($promptEdited)
                            <flux:button size="xs" variant="ghost" icon="arrow-uturn-left" wire:click="resetAiPrompt">Hinterlegten Prompt wiederherstellen</flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @endif

            {{-- Was an die KI geht --}}
            <details class="text-sm">
                <summary class="cursor-pointer text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Daten, die übergeben werden ({{ count($data) }})</summary>
                <dl class="mt-2 grid gap-x-4 gap-y-1 rounded-xl bg-zinc-50 p-3 text-xs sm:grid-cols-[12rem_minmax(0,1fr)] dark:bg-zinc-900">
                    @foreach ($data as $key => $item)
                        <dt class="text-zinc-500">{{ $item['label'] }} <x-adminv2.placeholder :name="$key" :label="$item['label']" /></dt>
                        <dd class="whitespace-pre-wrap break-words text-zinc-800 dark:text-zinc-200">{{ $item['value'] }}</dd>
                    @endforeach
                </dl>
            </details>

            <div class="flex flex-wrap items-center gap-3">
                @if ($reviewMode)
                    <flux:button variant="primary" icon="sparkles" x-on:click="run('reviewAiFields')" x-bind:disabled="busy">
                        <span x-show="! busy">Felder prüfen</span><span x-show="busy" x-cloak>Die KI prüft …</span>
                    </flux:button>
                @else
                    <flux:button variant="primary" icon="sparkles" x-on:click="run('runAiCheck')" x-bind:disabled="busy || {{ (! $chosen && ! $custom) ? 'true' : 'false' }}">
                        <span x-show="! busy">KI ausführen</span><span x-show="busy" x-cloak>Die KI arbeitet …</span>
                    </flux:button>
                @endif
                <flux:spacer />
                <a href="{{ $manageUrl }}" target="_blank" class="text-xs text-zinc-500 underline decoration-zinc-300 underline-offset-2 hover:text-zinc-900 dark:hover:text-white">Prüfungen verwalten</a>
            </div>

            {{-- Deutlich sichtbarer Fortschritt waehrend des Laufs --}}
            <div x-show="busy" x-cloak class="rounded-xl border border-[var(--color-accent)]/30 bg-[var(--color-accent)]/5 px-4 py-3">
                <div class="flex items-center gap-3 text-sm text-zinc-800 dark:text-zinc-200">
                    <flux:icon.arrow-path variant="mini" class="shrink-0 animate-spin text-[var(--color-accent)]" />
                    <span>Die KI arbeitet – das dauert meist 5 bis 30 Sekunden, bei vielen Feldern auch länger. Das Fenster bitte geöffnet lassen.</span>
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-[var(--color-accent)]/15">
                    <div class="h-full w-1/3 animate-pulse rounded-full bg-[var(--color-accent)]"></div>
                </div>
            </div>

            @if ($error)
                <p class="rounded-xl bg-red-50 px-3 py-2.5 text-sm text-red-800 dark:bg-red-500/10 dark:text-red-300">{{ $error }}</p>
            @endif

            @if ($reviewMode && $reviewFields !== [])
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">Ergebnis der Feldprüfung</p>
                            @if ($review['summary'] ?? null)
                                <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">{{ $review['summary'] }}</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($openSuggestions > 0)
                                <flux:button size="sm" variant="primary" icon="arrow-down-tray" wire:click="applyAllAiSuggestions">Alle {{ $openSuggestions }} Vorschläge übernehmen</flux:button>
                            @endif
                            @if ($openNotes > 0)
                                <flux:button size="sm" icon="pencil-square" wire:click="applyAllAiNotes">{{ $openNotes }} {{ $openNotes === 1 ? 'Text' : 'Texte' }} in Notizen übernehmen</flux:button>
                            @endif
                            <flux:button size="sm" variant="ghost" wire:click="dismissAiReview">Hinweise verwerfen</flux:button>
                        </div>
                    </div>

                    <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-500">
                        <span class="inline-flex items-center gap-1 text-green-700 dark:text-green-400"><flux:icon.check-circle variant="micro" /> {{ $reviewCounts['ok'] ?? 0 }} korrekt</span>
                        <span class="inline-flex items-center gap-1 text-amber-700 dark:text-amber-400"><flux:icon.light-bulb variant="micro" /> {{ $reviewCounts['change'] ?? 0 }} {{ ($reviewCounts['change'] ?? 0) === 1 ? 'Vorschlag' : 'Vorschläge' }}</span>
                        <span class="inline-flex items-center gap-1"><flux:icon.question-mark-circle variant="micro" /> {{ $reviewCounts['unknown'] ?? 0 }} nicht prüfbar</span>
                    </p>

                    <div class="mt-3 flex flex-col gap-3 text-sm">
                        @foreach ($reviewGroups as $group => $groupFields)
                            <div wire:key="review-group-{{ $loop->index }}" class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800">
                                @if ($group !== '')
                                    <p class="bg-zinc-50 px-3 py-1.5 text-xs font-semibold tracking-wide text-zinc-600 uppercase dark:bg-zinc-900 dark:text-zinc-400">{{ $group }}</p>
                                @endif
                                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach ($groupFields as $key => $field)
                                        <li wire:key="review-{{ $key }}" class="grid items-start gap-x-3 gap-y-1 px-3 py-2 sm:grid-cols-[12rem_minmax(0,1fr)_auto]">
                                            <span class="text-zinc-600 dark:text-zinc-400">{{ $field['label'] }}</span>
                                            <span class="min-w-0">
                                                @if ($field['status'] === 'ok')
                                                    <span class="inline-flex items-center gap-1 text-green-700 dark:text-green-400"><flux:icon.check-circle variant="micro" /> korrekt</span>
                                                @elseif ($field['status'] === 'change')
                                                    <x-adminv2.ai-value :text="$field['value']" @class(['text-zinc-900 dark:text-white', 'font-medium' => mb_strlen($field['value']) <= 80]) />
                                                    @if (! in_array($data[$key]['value'] ?? '–', ['', '–'], true))
                                                        <x-adminv2.ai-value :text="$data[$key]['value']" prefix="Bisher:" class="mt-1 text-xs text-zinc-500" />
                                                    @endif
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-zinc-500"><flux:icon.question-mark-circle variant="micro" /> nicht prüfbar</span>
                                                @endif
                                                @if ($field['note'])
                                                    <span class="mt-1 block text-xs break-words whitespace-pre-line text-zinc-500">{{ $field['note'] }}</span>
                                                @endif
                                            </span>
                                            <span class="flex flex-col items-end gap-1">
                                                @if ($field['status'] === 'change')
                                                    @if ($field['applied'] ?? false)
                                                        <flux:badge size="sm" color="green">übernommen</flux:badge>
                                                    @else
                                                        <flux:button size="xs" icon="arrow-down-tray" wire:click="applyAiSuggestion('{{ $key }}')">Übernehmen</flux:button>
                                                    @endif
                                                @endif
                                                @if (in_array($key, $noteKeys, true) && $field['note'])
                                                    @if ($field['note_applied'] ?? false)
                                                        <flux:badge size="sm" color="green">Text in Notiz</flux:badge>
                                                    @else
                                                        <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="applyAiNote('{{ $key }}')">Text in Notiz</flux:button>
                                                    @endif
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>

                    @if ($review['usage'] ?? null)
                        <p class="mt-3 border-t border-zinc-100 pt-3 text-xs text-zinc-500 dark:border-zinc-800">
                            Verbrauch: {{ number_format($review['usage']['total_tokens'], 0, ',', '.') }} Token
                            ({{ number_format($review['usage']['input_tokens'], 0, ',', '.') }} Eingabe, {{ number_format($review['usage']['output_tokens'], 0, ',', '.') }} Ausgabe)
                            · @if ($review['usage']['cost'] !== null) Kosten ca. {{ number_format($review['usage']['cost'], 4, ',', '.') }} $ @else Kosten: kein Preis für dieses Modell hinterlegt (System › KI) @endif
                            · Modell {{ $review['usage']['model'] }}
                        </p>
                    @endif
                    <p class="mt-2 text-xs text-zinc-500">Die Hinweise stehen auch unter den Feldern. Übernommene Werte sind erst nach „Speichern“ wirksam.</p>
                </div>
            @endif

            @if ($result)
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $result['title'] }}</p>
                    {{-- Die Antwort kommt als Markdown bzw. HTML: Ueberschriften, Listen und Tabellen bekommen hier ihre Form. --}}
                    <div class="mt-2 max-h-[32rem] overflow-y-auto text-sm leading-relaxed break-words text-zinc-700 dark:text-zinc-300 [&_a]:underline [&_blockquote]:mt-2 [&_blockquote]:border-s-2 [&_blockquote]:border-zinc-200 [&_blockquote]:ps-3 [&_code]:rounded [&_code]:bg-zinc-100 [&_code]:px-1 [&_code]:font-mono [&_code]:text-xs dark:[&_code]:bg-zinc-800 [&_h1]:mt-4 [&_h1]:font-semibold [&_h1]:text-zinc-900 dark:[&_h1]:text-white [&_h2]:mt-4 [&_h2]:font-semibold [&_h2]:text-zinc-900 dark:[&_h2]:text-white [&_h3]:mt-3 [&_h3]:font-semibold [&_h3]:text-zinc-900 dark:[&_h3]:text-white [&_h4]:mt-3 [&_h4]:font-semibold [&_hr]:my-3 [&_hr]:border-zinc-200 dark:[&_hr]:border-zinc-800 [&_li]:ms-5 [&_li]:mt-0.5 [&_ol]:mt-1 [&_ol]:list-decimal [&_p]:mt-2 [&_strong]:font-semibold [&_strong]:text-zinc-900 dark:[&_strong]:text-white [&_table]:mt-2 [&_table]:w-full [&_td]:border-b [&_td]:border-zinc-100 [&_td]:py-1.5 [&_td]:pe-3 [&_td]:align-top dark:[&_td]:border-zinc-800 [&_th]:border-b [&_th]:border-zinc-200 [&_th]:py-1.5 [&_th]:pe-3 [&_th]:text-start [&_th]:font-semibold dark:[&_th]:border-zinc-700 [&_ul]:mt-1 [&_ul]:list-disc [&>*:first-child]:mt-0">{!! $result['html'] !!}</div>

                    {{-- Die Pruefung legt Aufgaben an: was aus ihrer Bedingung fuer diesen Eintrag wurde. --}}
                    @if ($taskOutcome = $result['task'] ?? null)
                        <div @class([
                            'mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 rounded-lg px-3 py-2 text-sm',
                            'bg-sky-50 text-sky-900 dark:bg-sky-500/10 dark:text-sky-200' => $taskOutcome['met'],
                            'bg-zinc-50 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400' => ! $taskOutcome['met'],
                        ])>
                            <flux:icon.clipboard-document-check variant="mini" class="shrink-0" />
                            @if (! $taskOutcome['parsed'])
                                <span>Die Bedingung ließ sich aus der Antwort nicht auswerten – es wurde keine Aufgabe angelegt.</span>
                            @elseif (! $taskOutcome['met'])
                                <span>Die Bedingung trifft nicht zu – keine Aufgabe.</span>
                            @else
                                <span>{{ $taskOutcome['created'] ? 'Die Bedingung trifft zu – Unteraufgabe angelegt:' : 'Die Bedingung trifft zu – es gibt bereits eine offene Unteraufgabe, das Ergebnis steht dort als Notiz:' }}</span>
                                <a href="{{ $taskOutcome['url'] }}" target="_blank" class="font-medium underline decoration-sky-300 underline-offset-2 hover:decoration-sky-700">{{ $taskOutcome['title'] }}</a>
                            @endif
                        </div>
                    @endif

                    @if ($result['usage'])
                        <p class="mt-3 border-t border-zinc-100 pt-3 text-xs text-zinc-500 dark:border-zinc-800">
                            Verbrauch: {{ number_format($result['usage']['total_tokens'], 0, ',', '.') }} Token
                            ({{ number_format($result['usage']['input_tokens'], 0, ',', '.') }} Eingabe, {{ number_format($result['usage']['output_tokens'], 0, ',', '.') }} Ausgabe)
                            · @if ($result['usage']['cost'] !== null) Kosten ca. {{ number_format($result['usage']['cost'], 4, ',', '.') }} $ @else Kosten: kein Preis für dieses Modell hinterlegt (System › KI) @endif
                            · Modell {{ $result['usage']['model'] }}
                        </p>
                    @endif
                </div>
            @endif

        <div class="flex justify-end">
            <flux:modal.close><flux:button variant="ghost">Schließen</flux:button></flux:modal.close>
        </div>
    </div>
</flux:modal>
