{{-- "KI-Auftraege" im Menue: offene Auftraege werden alle 10 Sekunden abgefragt, sonst jede Minute. --}}
<div wire:poll.{{ $openCount > 0 ? '10s' : '60s' }}="refresh">
    <flux:dropdown position="top" align="start">
        <flux:button variant="ghost" size="sm" icon="sparkles" class="w-full !justify-start">
            KI-Aufträge
            @if ($openCount > 0)
                <flux:badge size="sm" color="sky" inset="top bottom" class="ms-1">
                    <flux:icon.arrow-path variant="micro" class="animate-spin" /> {{ $openCount }}
                </flux:badge>
            @endif
            @if ($unseenCount > 0)
                <flux:badge size="sm" color="green" inset="top bottom" class="ms-1">{{ $unseenCount }} fertig</flux:badge>
            @endif
        </flux:button>

        <flux:popover class="w-[22rem] max-w-[calc(100vw-2rem)] !p-0">
            <div class="flex items-center justify-between border-b border-zinc-200 px-3 py-2 dark:border-zinc-700">
                <span class="text-sm font-semibold text-zinc-900 dark:text-white">KI-Aufträge</span>
                @if ($unseenCount > 0)
                    <flux:button size="xs" variant="ghost" wire:click="markAllSeen">Alle als gesehen</flux:button>
                @endif
            </div>

            @if ($this->reviews->isEmpty())
                <p class="px-3 py-4 text-sm text-zinc-500">Keine Aufträge der letzten {{ \App\Livewire\AdminV2\AiReviewTray::DAYS }} Tage. Feldprüfungen startest du über „KI“ an einem Abschnitt – auch mehrere gleichzeitig.</p>
            @else
                <ul class="max-h-96 divide-y divide-zinc-100 overflow-y-auto dark:divide-zinc-800">
                    @foreach ($this->reviews as $review)
                        <li wire:key="tray-review-{{ $review->id }}">
                            <button type="button" wire:click="open({{ $review->id }})" @disabled($review->isOpen()) class="flex w-full items-start gap-2 px-3 py-2 text-start text-sm hover:bg-zinc-50 disabled:cursor-default disabled:hover:bg-transparent dark:hover:bg-zinc-800">
                                @if ($review->isOpen())
                                    <flux:icon.arrow-path variant="mini" @class(['mt-0.5 shrink-0 text-sky-600', 'animate-spin' => $review->status === 'running']) />
                                @elseif ($review->failureMessage())
                                    <flux:icon.exclamation-triangle variant="mini" class="mt-0.5 shrink-0 text-red-600" />
                                @else
                                    <flux:icon.check-circle variant="mini" class="mt-0.5 shrink-0 text-green-600" />
                                @endif
                                <span class="min-w-0 flex-1">
                                    <span @class(['block truncate text-zinc-900 dark:text-white', 'font-semibold' => $review->isFinished() && $review->seen_at === null])>
                                        {{ $review->title ? $review->title.' › ' : '' }}{{ $review->sectionLabel() }}
                                    </span>
                                    <span class="block text-xs text-zinc-500">
                                        @if ($review->status === 'queued')
                                            wartet auf Bearbeitung
                                        @elseif ($review->isOpen())
                                            recherchiert …
                                        @elseif ($review->failureMessage())
                                            fehlgeschlagen: {{ \Illuminate\Support\Str::limit($review->failureMessage(), 80) }}
                                        @else
                                            fertig {{ $review->finished_at?->diffForHumans() }}{{ $review->url ? '' : ' · neuer Eintrag, nicht mehr zu öffnen' }}
                                        @endif
                                    </span>
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </flux:popover>
    </flux:dropdown>
</div>
