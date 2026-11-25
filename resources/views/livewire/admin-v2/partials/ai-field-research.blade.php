{{--
    KI-Recherche an einem Feld (Trait ResearchesAiFields).

    - $researchKey: Schluessel des Feldes, z. B. risk_security_crime_level
    - $basicPlaceholders: Platzhalter, die als Schaltflaechen bereitstehen (Schluessel => Bezeichnung)
--}}
@php
    $researchState = $aiResearch[$researchKey] ?? null;
    $researchRuns = $this->aiResearchRuns->get($researchKey, collect());
    $researchTemplates = $this->aiResearchTemplates->get($researchKey, collect());
    $latestRuns = $researchRuns->take(3);
    $providers = \App\Support\AdminV2\AiModelOptions::providers();
@endphp

<div
    x-data="{
        open: false,
        saving: false,
        insert(token) {
            const el = $root.querySelector('textarea[data-ai-research-prompt]');
            if (! el) return;
            const start = el.selectionStart ?? el.value.length;
            el.setRangeText(token, start, el.selectionEnd ?? start, 'end');
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.focus();
        },
    }"
    class="flex flex-col gap-2"
>
    <div class="flex flex-wrap items-center gap-1">
        <flux:button size="xs" variant="ghost" icon="sparkles" x-on:click="open = ! open; if (open) $wire.openAiResearch(@js($researchKey))">
            <span x-text="open ? 'KI-Recherche schließen' : 'KI-Recherche'"></span>
        </flux:button>
        @if ($researchRuns->isNotEmpty())
            <flux:button size="xs" variant="ghost" icon="clock" wire:click="showAiResearchHistory(@js($researchKey))">Verlauf ({{ $researchRuns->count() }})</flux:button>
        @endif
    </div>

    <div x-show="open" x-cloak class="rounded-xl border border-violet-200 bg-violet-50/50 p-4 dark:border-violet-900 dark:bg-violet-950/20">
        @if ($providers === [])
            <p class="text-sm text-zinc-500">Für keine KI ist ein API-Schlüssel hinterlegt – bitte unter System &gt; KI eintragen.</p>
        @elseif ($researchState === null)
            <p class="flex items-center gap-2 text-sm text-zinc-500"><flux:icon.arrow-path variant="mini" class="animate-spin" /> Lädt …</p>
        @else
            <div class="flex flex-col gap-3">
                <flux:field>
                    <flux:label>Vorlage</flux:label>
                    <flux:select wire:model.live="aiResearch.{{ $researchKey }}.template" size="sm">
                        <flux:select.option value="">Eigener Prompt (nicht gespeichert)</flux:select.option>
                        @foreach ($researchTemplates as $template)
                            <flux:select.option :value="(string) $template->id">{{ $template->name }}{{ $template->is_default ? ' (Standard)' : '' }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Prompt</flux:label>
                    <flux:textarea wire:model.blur="aiResearch.{{ $researchKey }}.prompt" rows="4" resize="vertical" data-ai-research-prompt />
                    <flux:error name="aiResearch.{{ $researchKey }}.prompt" />
                    <div class="mt-1 flex flex-wrap items-center gap-1">
                        <span class="text-xs text-zinc-500">Platzhalter:</span>
                        @foreach ($basicPlaceholders as $placeholder => $label)
                            <button type="button" x-on:click="insert(@js('{'.$placeholder.'}'))" title="{{ $label }}" class="rounded-md bg-white px-1.5 py-0.5 font-mono text-xs text-violet-700 ring-1 ring-violet-200 hover:bg-violet-100 dark:bg-zinc-900 dark:text-violet-300 dark:ring-violet-800">{{ '{'.$placeholder.'}' }}</button>
                        @endforeach
                    </div>
                    @if ($unknown = $this->aiResearchUnknownPlaceholders($researchKey))
                        <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">Unbekannte Platzhalter bleiben stehen: {{ implode(', ', array_map(fn ($key) => '{'.$key.'}', $unknown)) }}</p>
                    @endif
                </flux:field>

                <details class="text-xs text-zinc-600 dark:text-zinc-400">
                    <summary class="cursor-pointer select-none">Vorschau mit den Werten dieses Eintrags</summary>
                    <p class="mt-1 whitespace-pre-line rounded-lg bg-white p-2 dark:bg-zinc-900">{{ $this->aiResearchPreview($researchKey) }}</p>
                    <p class="mt-1">Angehängt wird, welcher Wert in welchem Format gesucht ist – der vollständige Prompt steht später im Verlauf.</p>
                </details>

                <div class="flex flex-wrap items-end gap-3">
                    <flux:field class="w-32">
                        <flux:label>KI</flux:label>
                        <flux:select wire:model.live="aiResearch.{{ $researchKey }}.provider" size="sm">
                            @foreach ($providers as $value => $label)
                                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="aiResearch.{{ $researchKey }}.provider" />
                    </flux:field>
                    <flux:field class="min-w-48 flex-1">
                        <flux:label>Modell</flux:label>
                        <flux:select wire:model="aiResearch.{{ $researchKey }}.model" size="sm" wire:key="ai-research-models-{{ $researchKey }}-{{ $researchState['provider'] }}">
                            @foreach (\App\Support\AdminV2\AiModelOptions::models($researchState['provider']) as $value => $label)
                                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="aiResearch.{{ $researchKey }}.model" />
                    </flux:field>
                    <flux:button size="sm" variant="primary" icon="paper-airplane" wire:click="sendAiResearch(@js($researchKey))">Senden</flux:button>
                </div>

                {{-- Als Vorlage speichern: gilt danach fuer alle Eintraege an diesem Feld --}}
                <div class="flex flex-wrap items-center gap-2 border-t border-violet-200 pt-3 dark:border-violet-900">
                    @if ($researchState['template'] !== '')
                        <flux:button size="xs" icon="bookmark" wire:click="saveAiResearchTemplate(@js($researchKey))">In Vorlage speichern</flux:button>
                        <flux:button size="xs" variant="ghost" x-on:click="saving = ! saving">Als neue Vorlage …</flux:button>
                        <flux:button size="xs" variant="ghost" icon="trash" wire:click="deleteAiResearchTemplate(@js($researchKey))" wire:confirm="Diese Vorlage für alle Einträge löschen?">Vorlage löschen</flux:button>
                    @else
                        <flux:button size="xs" icon="bookmark" x-on:click="saving = ! saving">Als Vorlage speichern …</flux:button>
                    @endif
                    <flux:checkbox wire:model="aiResearch.{{ $researchKey }}.default" label="Standard für dieses Feld" />
                </div>
                <div x-show="saving" x-cloak class="flex flex-wrap items-start gap-2">
                    <flux:field class="min-w-56 flex-1">
                        <flux:input wire:model="aiResearch.{{ $researchKey }}.name" size="sm" placeholder="Name der Vorlage, z. B. „Kriminalität nach AA“" />
                        <flux:error name="aiResearch.{{ $researchKey }}.name" />
                    </flux:field>
                    <flux:button size="sm" variant="primary" wire:click="saveAiResearchTemplate(@js($researchKey), true)" x-on:click="saving = false">Speichern</flux:button>
                </div>
            </div>
        @endif
    </div>

    {{-- Die juengsten Ergebnisse stehen am Feld, auch wenn die Recherche zugeklappt ist. --}}
    @foreach ($latestRuns as $run)
        @php
            $display = $run->value !== null ? $this->aiResearchDisplay($researchKey, $run->value) : null;
        @endphp
        <div wire:key="ai-research-run-{{ $run->id }}" @class([
            'rounded-xl border p-3 text-sm',
            'border-violet-200 bg-white dark:border-violet-900 dark:bg-zinc-950' => ! $run->isApplied(),
            'border-zinc-200 bg-zinc-50 text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400' => $run->isApplied(),
        ])>
            @if ($run->isOpen())
                <p class="flex items-center gap-2 text-sky-700 dark:text-sky-400">
                    <flux:icon.arrow-path variant="mini" class="animate-spin" />
                    {{ $run->status === \App\Models\AiQuestion::STATUS_QUEUED ? 'Wartet auf Bearbeitung …' : 'Die KI recherchiert …' }}
                </p>
            @elseif ($run->failureMessage())
                <flux:callout variant="danger" icon="exclamation-triangle" :text="$run->failureMessage()" />
            @else
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        @if ($run->value !== null)
                            <p class="font-medium text-zinc-900 dark:text-white">Vorschlag: {{ $display ?? $run->value }}</p>
                            @if ($display === null)
                                <p class="text-xs text-amber-700 dark:text-amber-400">Passt nicht ins Feld – bitte von Hand eintragen.</p>
                            @endif
                        @elseif ($run->note === null)
                            <p class="text-zinc-600 dark:text-zinc-400">Die Antwort ließ sich nicht auswerten.</p>
                        @else
                            <p class="text-zinc-600 dark:text-zinc-400">Kein verlässlicher Wert gefunden.</p>
                        @endif
                        @if ($run->note)
                            <p class="mt-1 whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $run->note }}</p>
                        @endif
                    </div>
                    @if ($run->isApplied())
                        <flux:badge size="sm" color="{{ $run->saved_at ? 'green' : 'amber' }}" icon="check">{{ $run->saved_at ? 'übernommen & gespeichert' : 'übernommen, nicht gespeichert' }}</flux:badge>
                    @elseif (($run->value !== null && $display !== null) || ($run->value === null && $run->note !== null && ($this->aiResearchFields()[$researchKey]['note'] ?? false)))
                        <flux:button size="xs" variant="primary" icon="arrow-down-tray" wire:click="applyAiResearch({{ $run->id }})">Übernehmen</flux:button>
                    @endif
                </div>
                @if ($run->value === null && $run->note === null)
                    <details class="mt-1 text-xs text-zinc-500">
                        <summary class="cursor-pointer select-none">Antwort der KI</summary>
                        <p class="mt-1 whitespace-pre-line">{{ $run->answer }}</p>
                    </details>
                @endif
                @if (! empty($run->sources))
                    <details class="mt-1 text-xs text-zinc-500">
                        <summary class="cursor-pointer select-none">Quellen ({{ count($run->sources) }})</summary>
                        <ul class="mt-1 flex flex-col gap-1 ps-4">
                            @foreach ($run->sources as $source)
                                <li class="truncate"><a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer" class="underline">{{ $source['title'] ?: $source['url'] }}</a></li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            @endif
            <p class="mt-1 text-xs text-zinc-400">{{ \App\Support\AdminV2\AiModelOptions::providerLabel($run->provider) }} · {{ $run->model }} · {{ $run->created_at->format('d.m.Y H:i') }}</p>
        </div>
    @endforeach
</div>
