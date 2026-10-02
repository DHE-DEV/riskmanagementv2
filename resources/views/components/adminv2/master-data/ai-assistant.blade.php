{{--
    Karte "KI-Assistent" einer Stammdaten-Bearbeitungsseite (Trait RunsAiAssistant):
    eine hinterlegte Aufgabe mit den Daten des Eintrags ausfuehren.

    - prompts: die fuer diesen Bereich hinterlegten Aufgaben
    - promptId: gewaehlte Aufgabe
    - result / error: Ergebnis des letzten Laufs
    - noun: "dieses Land", "diese Stadt" …
--}}
@props([
    'prompts',
    'promptId' => '',
    'result' => null,
    'error' => null,
    'noun' => 'diesen Eintrag',
])

@php
    $chosen = $prompts->firstWhere('id', (int) $promptId);
@endphp

<x-adminv2.card heading="KI-Assistent" description="Eine hinterlegte Aufgabe mit den gespeicherten Daten ausführen." collapsible collapsed>
    @if ($prompts->isEmpty())
        <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
            Für {{ $noun }} ist noch keine KI-Aufgabe hinterlegt.
            <a href="{{ url('/admin/ai-prompts') }}" target="_blank" class="text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">KI-Prompts im bisherigen Admin</a>
        </p>
    @else
        <div class="flex flex-col gap-4">
            <flux:select wire:model.live="aiPromptId" label="Aufgabe">
                <flux:select.option value="">Aufgabe auswählen …</flux:select.option>
                @foreach ($prompts as $prompt)
                    <flux:select.option value="{{ $prompt->id }}">{{ $prompt->name }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($chosen)
                @if ($chosen->description)
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $chosen->description }}</p>
                @endif

                <details class="text-sm">
                    <summary class="cursor-pointer text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">KI Prompt ansehen</summary>
                    <pre class="mt-2 max-h-64 overflow-y-auto rounded-xl bg-zinc-50 p-3 font-mono text-xs leading-relaxed whitespace-pre-wrap text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ $chosen->prompt_template }}</pre>
                </details>
            @endif

            <div class="flex items-center gap-3">
                <flux:button icon="sparkles" wire:click="runAiAssistant" wire:loading.attr="disabled" wire:target="runAiAssistant" :disabled="! $chosen">KI ausführen</flux:button>
                <span wire:loading wire:target="runAiAssistant" class="text-sm text-zinc-500">Die KI arbeitet …</span>
            </div>

            @if ($error)
                <p class="rounded-xl bg-red-50 px-3 py-2.5 text-sm text-red-800 dark:bg-red-500/10 dark:text-red-300">{{ $error }}</p>
            @endif

            @if ($result)
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $result['title'] }}</p>
                    <div class="mt-2 text-sm leading-relaxed break-words text-zinc-700 dark:text-zinc-300 [&_a]:underline [&_h1]:mt-3 [&_h1]:font-semibold [&_h2]:mt-3 [&_h2]:font-semibold [&_h3]:mt-3 [&_h3]:font-semibold [&_li]:ms-5 [&_ol]:list-decimal [&_p]:mt-2 [&_ul]:list-disc">{!! $result['html'] !!}</div>

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
        </div>
    @endif
</x-adminv2.card>
