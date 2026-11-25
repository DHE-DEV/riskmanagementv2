{{-- Fenster "KI-Verlauf": jede Feldrecherche dieses Eintrags mit Prompt, Vorschlag, Quellen und was daraus wurde (Trait ResearchesAiFields). --}}
@php
    $researchFields = $this->aiResearchFields();
@endphp

<flux:modal name="ai-research-history" class="w-full md:w-[56rem]">
    <div class="flex flex-col gap-4">
        <div>
            <flux:heading size="lg">KI-Verlauf</flux:heading>
            <flux:subheading>
                {{ $aiResearchHistoryKey === '*' ? 'Alle Felder' : ($researchFields[$aiResearchHistoryKey]['label'] ?? '') }}{{ $title ? ' – '.$title : '' }} · was die KI vorgeschlagen hat, mit welchen Quellen, und was übernommen wurde.
            </flux:subheading>
        </div>

        @forelse ($this->aiResearchHistory as $run)
            <article wire:key="ai-history-{{ $run->id }}" class="rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                <header class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        @if ($aiResearchHistoryKey === '*')
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $researchFields[$run->field]['label'] ?? $run->field }}</p>
                        @endif
                        <p class="text-xs text-zinc-500">
                            {{ $run->created_at->format('d.m.Y H:i') }} · {{ $run->user?->name ?? 'gelöschter Benutzer' }}
                            · {{ \App\Support\AdminV2\AiModelOptions::providerLabel($run->provider) }} {{ $run->model }}{{ ($run->usage['model'] ?? null) && $run->usage['model'] !== $run->model ? ' (geantwortet: '.$run->usage['model'].')' : '' }}
                            · Vorlage: {{ $run->ai_field_prompt_id ? ($run->template?->name ?? 'gelöscht') : 'eigener Prompt' }}
                        </p>
                    </div>
                    @if ($run->isOpen())
                        <flux:badge size="sm" color="sky">läuft</flux:badge>
                    @elseif ($run->failureMessage())
                        <flux:badge size="sm" color="red">fehlgeschlagen</flux:badge>
                    @elseif ($run->isApplied())
                        <flux:badge size="sm" :color="$run->saved_at ? 'green' : 'amber'">{{ $run->saved_at ? 'übernommen & gespeichert' : 'übernommen, nicht gespeichert' }}</flux:badge>
                    @else
                        <flux:badge size="sm">nicht übernommen</flux:badge>
                    @endif
                </header>

                @if ($run->failureMessage())
                    <p class="mt-2 text-red-700 dark:text-red-400">{{ $run->failureMessage() }}</p>
                @elseif (! $run->isOpen())
                    <dl class="mt-3 grid gap-x-4 gap-y-1 sm:grid-cols-[9rem_minmax(0,1fr)]">
                        <dt class="text-zinc-500">Vorschlag</dt>
                        <dd class="text-zinc-900 dark:text-white">{{ $run->value ?? '–' }}</dd>
                        @if ($run->note)
                            <dt class="text-zinc-500">Begründung</dt>
                            <dd class="whitespace-pre-line text-zinc-800 dark:text-zinc-200">{{ $run->note }}</dd>
                        @endif
                        @if ($run->isApplied())
                            <dt class="text-zinc-500">Übernommen</dt>
                            <dd class="text-zinc-800 dark:text-zinc-200">
                                {{ $run->applied_at->format('d.m.Y H:i') }} von {{ $run->appliedBy?->name ?? 'gelöschter Benutzer' }}:
                                „{{ \Illuminate\Support\Str::limit($run->previous_value ?? '–', 120) }}“ → „{{ \Illuminate\Support\Str::limit($run->applied_value ?? '–', 120) }}“
                                {{ $run->saved_at ? '· gespeichert '.$run->saved_at->format('d.m.Y H:i') : '· noch nicht gespeichert' }}
                            </dd>
                        @endif
                        @if ($run->usage)
                            <dt class="text-zinc-500">Verbrauch</dt>
                            <dd class="text-zinc-800 dark:text-zinc-200">{{ number_format((int) ($run->usage['total_tokens'] ?? 0), 0, ',', '.') }} Token{{ $run->cost() !== null ? ' · ca. '.number_format($run->cost(), 4, ',', '.').' $' : '' }}</dd>
                        @endif
                    </dl>
                @endif

                <div class="mt-3 flex flex-col gap-1 text-xs text-zinc-500">
                    <details>
                        <summary class="cursor-pointer select-none">Gesendeter Prompt</summary>
                        <p class="mt-1 whitespace-pre-line rounded-lg bg-zinc-50 p-2 dark:bg-zinc-900">{{ $run->prompt ?? $run->question }}</p>
                    </details>
                    @if ($run->answer)
                        <details>
                            <summary class="cursor-pointer select-none">Antwort der KI (unverändert)</summary>
                            <p class="mt-1 whitespace-pre-line rounded-lg bg-zinc-50 p-2 dark:bg-zinc-900">{{ $run->answer }}</p>
                        </details>
                    @endif
                    @if (! empty($run->sources))
                        <details>
                            <summary class="cursor-pointer select-none">Quellen ({{ count($run->sources) }})</summary>
                            <ul class="mt-1 flex flex-col gap-1 ps-4">
                                @foreach ($run->sources as $source)
                                    <li class="truncate"><a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer" class="underline">{{ $source['title'] ?: $source['url'] }}</a></li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            </article>
        @empty
            <p class="text-sm text-zinc-500">Noch keine KI-Recherche zu {{ $aiResearchHistoryKey === '*' ? 'diesem Eintrag' : 'diesem Feld' }}.</p>
        @endforelse
    </div>
</flux:modal>
