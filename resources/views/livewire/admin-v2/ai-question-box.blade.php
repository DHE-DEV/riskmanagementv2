{{-- Freie Frage an die KI zu einem Abschnitt – solange Antworten ausstehen, wird alle 4 Sekunden nachgesehen. --}}
<div
    class="mt-6 border-t border-dashed border-zinc-200 pt-5 dark:border-zinc-700"
    @if ($openIds !== []) wire:poll.4s="refreshQuestions" @endif
>
    <div class="mb-3 flex items-center gap-2">
        <flux:icon.chat-bubble-left-right variant="mini" class="text-zinc-400" />
        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Frage an die KI zu „{{ $sectionLabel }}“</span>
    </div>

    @if ($this->providers === [])
        <p class="text-sm text-zinc-500">Für keine KI ist ein API-Schlüssel hinterlegt – bitte unter System &gt; KI eintragen.</p>
    @else
        <form wire:submit="ask" class="flex flex-col gap-3">
            <flux:field>
                <flux:textarea wire:model="question" rows="3" resize="vertical" placeholder="Beliebige Frage oder Anweisung – die KI kennt den Eintrag und diesen Abschnitt." />
                <flux:error name="question" />
            </flux:field>

            <div class="flex flex-wrap items-end gap-3">
                <flux:field class="w-36">
                    <flux:label>KI</flux:label>
                    <flux:select wire:model.live="provider" size="sm">
                        @foreach ($this->providers as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="provider" />
                </flux:field>

                <flux:field class="min-w-56 flex-1">
                    <flux:label>Modell</flux:label>
                    <flux:select wire:model="model" size="sm" wire:key="ai-question-models-{{ $section }}-{{ $provider }}">
                        @forelse ($this->modelOptions as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @empty
                            <flux:select.option value="">Keine Modelle verfügbar</flux:select.option>
                        @endforelse
                    </flux:select>
                    <flux:error name="model" />
                </flux:field>

                <flux:button type="submit" size="sm" variant="primary" icon="paper-airplane">Senden</flux:button>
            </div>
        </form>
    @endif

    @if ($this->questions->isNotEmpty())
        <ul class="mt-5 flex flex-col gap-3">
            @foreach ($this->questions as $item)
                <li wire:key="ai-question-{{ $item->id }}" class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="whitespace-pre-line text-sm font-medium text-zinc-900 dark:text-white">{{ $item->question }}</p>
                            <p class="mt-1 text-xs text-zinc-500">
                                {{ \App\Livewire\AdminV2\AiQuestionBox::PROVIDER_LABELS[$item->provider] ?? $item->providerLabel() }} · {{ $item->model }} · {{ $item->created_at->format('d.m.Y H:i') }}
                            </p>
                        </div>
                        <flux:button size="xs" variant="ghost" icon="trash" wire:click="deleteQuestion({{ $item->id }})" aria-label="Frage entfernen" />
                    </div>

                    <div class="mt-3">
                        @if ($item->isOpen())
                            <div class="flex items-center gap-2 text-sm text-sky-700 dark:text-sky-400">
                                <flux:icon.arrow-path variant="mini" class="animate-spin" />
                                {{ $item->status === \App\Models\AiQuestion::STATUS_QUEUED ? 'Wartet auf Bearbeitung …' : 'Die KI arbeitet …' }}
                            </div>
                        @elseif ($item->failureMessage())
                            <flux:callout variant="danger" icon="exclamation-triangle" :text="$item->failureMessage()" />
                        @else
                            <div class="text-sm leading-relaxed text-zinc-800 dark:text-zinc-200 [&_a]:text-sky-700 [&_a]:underline [&_h1]:mb-2 [&_h1]:font-semibold [&_h2]:mb-2 [&_h2]:font-semibold [&_h3]:mb-1 [&_h3]:font-semibold [&_li]:mb-1 [&_ol]:mb-3 [&_ol]:list-decimal [&_ol]:ps-5 [&_p]:mb-3 [&_table]:mb-3 [&_td]:border [&_td]:px-2 [&_td]:py-1 [&_th]:border [&_th]:px-2 [&_th]:py-1 [&_ul]:mb-3 [&_ul]:list-disc [&_ul]:ps-5 dark:[&_a]:text-sky-400">
                                {!! $item->answerHtml() !!}
                            </div>

                            @if (! empty($item->sources))
                                <details class="mt-2 text-xs text-zinc-500">
                                    <summary class="cursor-pointer select-none">Quellen ({{ count($item->sources) }})</summary>
                                    <ul class="mt-1 flex flex-col gap-1 ps-4">
                                        @foreach ($item->sources as $source)
                                            <li class="truncate">
                                                <a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer" class="underline hover:text-zinc-800 dark:hover:text-zinc-200">{{ $source['title'] ?: $source['url'] }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
