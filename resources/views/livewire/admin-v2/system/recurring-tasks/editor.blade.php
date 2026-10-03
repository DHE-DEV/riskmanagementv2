@php
    use App\Models\AdminTaskRecurrence;

    $recurrence = $this->recurrence;
    $preview = $this->preview;
    $unit = [
        AdminTaskRecurrence::FREQUENCY_DAILY => ['Tag', 'Tage'],
        AdminTaskRecurrence::FREQUENCY_WEEKLY => ['Woche', 'Wochen'],
        AdminTaskRecurrence::FREQUENCY_MONTHLY => ['Monat', 'Monate'],
        AdminTaskRecurrence::FREQUENCY_YEARLY => ['Jahr', 'Jahre'],
    ][$frequency] ?? ['Tag', 'Tage'];
    $isMonthlyOrYearly = in_array($frequency, [AdminTaskRecurrence::FREQUENCY_MONTHLY, AdminTaskRecurrence::FREQUENCY_YEARLY], true);

    $chip = 'inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-700 transition select-none hover:border-zinc-300 '
        .'has-[:checked]:border-[var(--color-accent)] has-[:checked]:bg-[var(--color-accent)] has-[:checked]:text-[var(--color-accent-foreground)] '
        .'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[var(--color-accent)]/40 '
        .'dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-600';
    $inline = 'flex flex-wrap items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300';
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('adminv2.system.recurring-tasks.index') }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Wiederkehrende Aufgaben
            </a>
            <flux:heading size="xl" level="1" class="mt-1">{{ $recurrence ? $recurrence->title : 'Neue wiederkehrende Aufgabe' }}</flux:heading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" :href="route('adminv2.system.recurring-tasks.index')">Abbrechen</flux:button>
            <flux:button type="submit" variant="primary" icon="check">Speichern</flux:button>
        </div>
    </div>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            {{-- Vorlage der Aufgabe --}}
            <x-adminv2.card heading="Aufgabe" description="So wird die Aufgabe jedes Mal angelegt.">
                <div class="flex flex-col gap-5">
                    <div>
                        <flux:input wire:model.live.debounce.400ms="title" label="Titel" placeholder="z. B. Quellen der aktiven Ereignisse prüfen – KW {kw}" maxlength="255" />
                        <div class="mt-2 text-xs text-zinc-500">
                            Platzhalter für den Termin:
                            @foreach (AdminTaskRecurrence::placeholders() as $placeholder => $meaning)
                                <span class="whitespace-nowrap"><x-adminv2.placeholder :name="trim($placeholder, '{}')" :label="$meaning" /> {{ $meaning }}</span>@if (! $loop->last), @endif
                            @endforeach
                        </div>
                    </div>

                    <flux:textarea wire:model="description" label="Beschreibung" rows="3" placeholder="Was genau ist zu tun? Links, Hinweise …" />

                    <x-adminv2.choice-boxes
                        label="Rubrik"
                        model="categoryId"
                        :options="$this->categories->map(fn ($category) => ['value' => $category->id, 'label' => $category->name])->all()"
                    />

                    <x-adminv2.choice-boxes
                        label="Priorität"
                        model="priority"
                        :options="collect(\App\Models\AdminTask::priorityOptions())->map(fn ($label, $value) => ['value' => $value, 'label' => $label, 'dot' => \App\Models\AdminTask::priorityDots()[$value]])->values()->all()"
                    />

                    <div class="grid items-start gap-5 sm:grid-cols-2">
                        <x-adminv2.assignee-select model="responsibleId" label="Verantwortlich" :users="$this->users" :teams="$this->teams" />
                        <x-adminv2.assignee-select model="nextAssigneeId" label="Nächster Bearbeiter" :users="$this->users" :teams="$this->teams" empty-label="Wie verantwortlich" clearable />
                    </div>
                </div>
            </x-adminv2.card>

            {{-- Rhythmus --}}
            <x-adminv2.card heading="Rhythmus" description="Wann die Aufgabe angelegt wird.">
                <div class="flex flex-col gap-5">
                    <div class="flex flex-wrap gap-2">
                        @foreach (AdminTaskRecurrence::frequencyOptions() as $value => $label)
                            <label wire:key="frequency-{{ $value }}" class="{{ $chip }}">
                                <input type="radio" wire:model.live="frequency" value="{{ $value }}" class="sr-only" />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>

                    <div class="{{ $inline }}">
                        <span>Alle</span>
                        <div class="w-20"><flux:input wire:model.live.debounce.300ms="interval" type="number" min="1" max="365" aria-label="Abstand" /></div>
                        <span>{{ (int) $interval === 1 ? $unit[0] : $unit[1] }}</span>
                    </div>
                    <flux:error name="interval" />

                    @if ($frequency === AdminTaskRecurrence::FREQUENCY_DAILY)
                        <flux:switch wire:model.live="workdaysOnly" label="Nur an Werktagen (Montag bis Freitag)" align="left" />
                    @endif

                    @if ($frequency === AdminTaskRecurrence::FREQUENCY_WEEKLY)
                        <div>
                            <div class="mb-2 text-sm font-medium text-zinc-800 dark:text-white">An diesen Wochentagen</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach (AdminTaskRecurrence::WEEKDAYS as $value => $label)
                                    <label wire:key="weekday-{{ $value }}" class="{{ $chip }}">
                                        <input type="checkbox" wire:model.live="weekdays" value="{{ $value }}" class="sr-only" />
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            <flux:error name="weekdays" class="mt-2" />
                        </div>
                    @endif

                    @if ($isMonthlyOrYearly)
                        <div class="flex flex-col gap-3">
                            @if ($frequency === AdminTaskRecurrence::FREQUENCY_YEARLY)
                                <div class="{{ $inline }}">
                                    <span>Im</span>
                                    <div class="w-44">
                                        <flux:select wire:model.live="month" aria-label="Monat">
                                            @foreach (AdminTaskRecurrence::MONTHS as $value => $label)
                                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>
                                </div>
                            @endif

                            <label class="{{ $inline }}">
                                <input type="radio" wire:model.live="monthlyMode" value="day" class="size-4 accent-[var(--color-accent)]" />
                                <span>Am</span>
                                <div class="w-44">
                                    <flux:select wire:model.live="dayOfMonth" aria-label="Tag des Monats" :disabled="$monthlyMode !== 'day'">
                                        @foreach (range(1, 31) as $day)
                                            <flux:select.option value="{{ $day }}">{{ $day }}.</flux:select.option>
                                        @endforeach
                                        <flux:select.option value="0">letzten Tag</flux:select.option>
                                    </flux:select>
                                </div>
                                <span>des Monats</span>
                            </label>

                            <label class="{{ $inline }}">
                                <input type="radio" wire:model.live="monthlyMode" value="weekday" class="size-4 accent-[var(--color-accent)]" />
                                <span>Am</span>
                                <div class="w-36">
                                    <flux:select wire:model.live="nth" aria-label="Wievielter" :disabled="$monthlyMode !== 'weekday'">
                                        @foreach (AdminTaskRecurrence::NTH as $value => $label)
                                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <div class="w-40">
                                    <flux:select wire:model.live="nthWeekday" aria-label="Wochentag" :disabled="$monthlyMode !== 'weekday'">
                                        @foreach (AdminTaskRecurrence::WEEKDAYS as $value => $label)
                                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <span>des Monats</span>
                            </label>

                            @if ($monthlyMode === 'day' && (int) $dayOfMonth >= 29)
                                <p class="text-xs text-zinc-500">Hat ein Monat keinen {{ (int) $dayOfMonth }}., gilt sein letzter Tag.</p>
                            @endif
                        </div>

                        <div class="{{ $inline }}">
                            <span>Fällt der Termin auf ein Wochenende:</span>
                            <div class="w-56">
                                <flux:select wire:model.live="weekendMode" aria-label="Termin am Wochenende">
                                    <flux:select.option value="keep">trotzdem anlegen</flux:select.option>
                                    <flux:select.option value="before">am Freitag davor</flux:select.option>
                                    <flux:select.option value="after">am Montag danach</flux:select.option>
                                    <flux:select.option value="skip">auslassen</flux:select.option>
                                </flux:select>
                            </div>
                        </div>
                    @endif

                    <div class="{{ $inline }}">
                        <span>Anlegen um</span>
                        <div class="w-32"><flux:input wire:model.live="createTime" type="time" aria-label="Uhrzeit" /></div>
                        <span>Uhr</span>
                    </div>
                    <flux:error name="createTime" />
                </div>
            </x-adminv2.card>

            {{-- Zeitraum --}}
            <x-adminv2.card heading="Zeitraum" description="Ab wann und wie lange.">
                <div class="flex flex-col gap-5">
                    <div class="w-48">
                        <flux:input wire:model.live="startsOn" type="date" label="Beginnt am" />
                    </div>

                    <div>
                        <div class="mb-2 text-sm font-medium text-zinc-800 dark:text-white">Endet</div>
                        <div class="flex flex-col gap-3">
                            <label class="{{ $inline }}">
                                <input type="radio" wire:model.live="endMode" value="never" class="size-4 accent-[var(--color-accent)]" />
                                <span>nie</span>
                            </label>
                            <label class="{{ $inline }}">
                                <input type="radio" wire:model.live="endMode" value="date" class="size-4 accent-[var(--color-accent)]" />
                                <span>am</span>
                                <div class="w-44"><flux:input wire:model.live="endsOn" type="date" aria-label="Enddatum" :disabled="$endMode !== 'date'" /></div>
                            </label>
                            <label class="{{ $inline }}">
                                <input type="radio" wire:model.live="endMode" value="count" class="size-4 accent-[var(--color-accent)]" />
                                <span>nach</span>
                                <div class="w-24"><flux:input wire:model.live.debounce.300ms="maxOccurrences" type="number" min="1" aria-label="Anzahl" :disabled="$endMode !== 'count'" /></div>
                                <span>angelegten Aufgaben</span>
                                @if ($recurrence && $recurrence->occurrences_count > 0)
                                    <span class="text-zinc-500">(bisher {{ $recurrence->occurrences_count }})</span>
                                @endif
                            </label>
                        </div>
                        <flux:error name="endsOn" class="mt-2" />
                        <flux:error name="maxOccurrences" class="mt-2" />
                    </div>
                </div>
            </x-adminv2.card>

            {{-- Faelligkeit und Erinnerung --}}
            <x-adminv2.card heading="Fälligkeit und Erinnerung" description="Gilt für jede angelegte Aufgabe, gerechnet ab ihrem Termin.">
                <div class="flex flex-col gap-4">
                    <label class="{{ $inline }}">
                        <input type="checkbox" wire:model.live="hasDue" class="size-4 rounded accent-[var(--color-accent)]" />
                        <span>Fällig</span>
                        <div class="w-20"><flux:input wire:model.live.debounce.300ms="dueInDays" type="number" min="0" aria-label="Tage bis zur Fälligkeit" :disabled="! $hasDue" /></div>
                        <span>{{ (int) $dueInDays === 1 ? 'Tag' : 'Tage' }} nach dem Anlegen</span>
                        <span class="text-zinc-500">(0 = am selben Tag)</span>
                    </label>
                    <flux:error name="dueInDays" />

                    <label class="{{ $inline }}">
                        <input type="checkbox" wire:model.live="hasReminder" class="size-4 rounded accent-[var(--color-accent)]" @disabled(! $hasDue) />
                        <span>Erinnerung</span>
                        <div class="w-20"><flux:input wire:model="remindDaysBefore" type="number" min="0" aria-label="Tage vor der Fälligkeit" :disabled="! $hasDue || ! $hasReminder" /></div>
                        <span>{{ (int) $remindDaysBefore === 1 ? 'Tag' : 'Tage' }} vor der Fälligkeit um</span>
                        <div class="w-32"><flux:input wire:model="remindTime" type="time" aria-label="Uhrzeit der Erinnerung" :disabled="! $hasDue || ! $hasReminder" /></div>
                        <span>Uhr</span>
                    </label>
                    <flux:error name="remindDaysBefore" />

                    <p class="text-xs text-zinc-500">
                        Wer die Aufgabe bekommt, erfährt per E-Mail davon; ist sie am Fälligkeitstag nicht erledigt, geht eine weitere Mail raus.
                    </p>
                </div>
            </x-adminv2.card>

            {{-- Verhalten --}}
            <x-adminv2.card heading="Verhalten">
                <div class="flex flex-col gap-4">
                    <flux:switch wire:model.live="skipIfOpen" label="Keine neue Aufgabe anlegen, solange die vorige noch offen ist" description="Der Termin entfällt dann – so stapeln sich keine unerledigten Aufgaben." align="left" />
                    <flux:switch wire:model.live="isActive" label="Aktiv" description="Ausgeschaltet wird nichts angelegt. Beim Wiedereinschalten zählt der nächste Termin ab dann; verpasste Termine werden nicht nachgeholt." align="left" />
                </div>
            </x-adminv2.card>
        </div>

        {{-- Vorschau --}}
        <aside class="flex flex-col gap-6 xl:sticky xl:top-6">
            <x-adminv2.card heading="Vorschau" description="So wird es ablaufen.">
                <div class="flex flex-col gap-4">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['summary'] }}</p>

                    @if (! $isActive)
                        <p class="rounded-md bg-amber-50 px-2.5 py-1.5 text-sm text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">Pausiert – es wird nichts angelegt.</p>
                    @elseif ($preview['ended'])
                        <p class="rounded-md bg-amber-50 px-2.5 py-1.5 text-sm text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">Mit diesen Angaben gibt es keinen weiteren Termin.</p>
                    @else
                        <div>
                            <div class="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500">Nächste Termine</div>
                            <ol class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($preview['dates'] as $date)
                                    <li wire:key="preview-{{ $date['date'] }}" class="py-2 first:pt-0 last:pb-0">
                                        <div class="flex items-baseline gap-2 text-sm tabular-nums">
                                            <span class="w-6 text-zinc-500">{{ $date['weekday'] }}</span>
                                            <span class="font-medium text-zinc-900 dark:text-white">{{ $date['date'] }}</span>
                                        </div>
                                        <div class="ms-8 mt-0.5 text-xs text-zinc-500">
                                            {{ ($date['title'] !== '' ? $date['title'] : 'Ohne Titel').($date['due'] ? ' · fällig '.$date['due'] : '') }}
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif

                    <p class="text-xs text-zinc-500">
                        Der Zeitplaner prüft alle fünf Minuten; die Aufgabe erscheint also spätestens fünf Minuten nach der Uhrzeit. Feiertage werden nicht berücksichtigt.
                    </p>
                </div>
            </x-adminv2.card>

            @if ($recurrence)
                <x-adminv2.card heading="Zuletzt angelegt" :description="$recurrence->occurrences_count.' '.($recurrence->occurrences_count === 1 ? 'Aufgabe' : 'Aufgaben').' aus dieser Serie'">
                    @if ($this->recentTasks->isEmpty())
                        <p class="text-sm text-zinc-500">Noch keine Aufgabe angelegt.</p>
                    @else
                        <ul class="-my-1 flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($this->recentTasks as $task)
                                <li wire:key="recent-{{ $task->id }}" class="py-2">
                                    <a href="{{ route('adminv2.tasks.show', $task) }}" target="_blank" @class(['text-sm font-medium hover:underline', 'text-zinc-900 dark:text-white' => ! $task->isDone(), 'text-zinc-400 line-through' => $task->isDone()])>{{ $task->title }}</a>
                                    <div class="mt-0.5 text-xs text-zinc-500 tabular-nums">{{ 'angelegt '.$task->created_at?->format('d.m.Y H:i').($task->due_date ? ' · fällig '.$task->due_date->format('d.m.Y') : '') }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-adminv2.card>
            @endif
        </aside>
    </div>
</form>
