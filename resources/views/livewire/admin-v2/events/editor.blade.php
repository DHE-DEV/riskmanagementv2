@php
    use App\Models\CustomEvent;
    use App\Support\AdminV2\EventState;

    $event = $this->event;
    $state = $this->state;
    $locales = CustomEvent::translationLocales();
    $sourceLocale = CustomEvent::sourceLocale();

    // Ausgeliefert oder ausgeliefert gewesen: Aenderungen wirken sofort und ohne neue Version.
    $isPublished = in_array($state, [EventState::Live, EventState::Scheduled, EventState::Expired], true);
    $isTrashed = (bool) $event?->trashed();
    $activeOther = $event ? $this->versions->first(fn ($version) => $version->is_active && $version->id !== $event->id) : null;
    $typeLabels = ['country' => 'Land', 'region' => 'Region', 'city' => 'Stadt'];
@endphp

@assets
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    {{-- Leaflet wie im uebrigen Projekt (Karten-Vorschau der Standorte) --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    {{-- Kalender fuer Beginn und Ende – der Kalender des Browsers ist zu klein. --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/de.js"></script>
    <style>
        /* Doppelt so gross wie der Standard-Kalender; die Position rechnet
           adminv2DateTimePicker selbst, damit der vergroesserte Kalender im
           Fenster bleibt. */
        .flatpickr-calendar.adminv2-calendar {
            --adminv2-calendar-scale: 2;
            transform: scale(var(--adminv2-calendar-scale));
            border-radius: 0.75rem;
            box-shadow: 0 0 0 1px rgb(228 228 231), 0 10px 25px rgb(0 0 0 / 0.15);
        }
        .flatpickr-calendar.adminv2-calendar:before,
        .flatpickr-calendar.adminv2-calendar:after { display: none; }
        .flatpickr-calendar.adminv2-calendar .flatpickr-day.selected,
        .flatpickr-calendar.adminv2-calendar .flatpickr-day.selected:hover {
            background: #171717; border-color: #171717; color: #fff;
        }
        .flatpickr-calendar.adminv2-calendar .flatpickr-day.today { border-color: #a3a3a3; }
        .dark .flatpickr-calendar.adminv2-calendar {
            background: #171717; color: #f5f5f5;
            box-shadow: 0 0 0 1px rgb(63 63 70), 0 10px 25px rgb(0 0 0 / 0.5);
        }
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-months .flatpickr-month,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-current-month,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-current-month input.cur-year,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-current-month .flatpickr-monthDropdown-months,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-weekday,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-day,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-time input,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-time .flatpickr-time-separator,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-months .flatpickr-prev-month svg,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-months .flatpickr-next-month svg { color: #f5f5f5; fill: #f5f5f5; background: transparent; }
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-day.prevMonthDay,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-day.nextMonthDay,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-day.flatpickr-disabled { color: #525252; }
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-day:hover,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-day.today:hover,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-time input:hover,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-time .numInputWrapper:hover { background: #262626; border-color: #262626; }
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-day.selected,
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-day.selected:hover { background: #fff; border-color: #fff; color: #171717; }
        .dark .flatpickr-calendar.adminv2-calendar .flatpickr-time,
        .dark .flatpickr-calendar.adminv2-calendar.hasTime .flatpickr-time { border-top-color: #3f3f46; }
    </style>
    <script>
        // Datum mit Uhrzeit: Livewire haelt "Y-m-d\TH:i" (wie datetime-local),
        // angezeigt wird "TT.MM.JJJJ HH:MM". Aenderungen gehen als verzoegerte
        // Aktualisierung mit der naechsten Anfrage mit; setzt der Server den
        // Wert (z. B. aus einem KI-Vorschlag), folgt der Kalender.
        window.adminv2DateTimePicker = (property) => {
            const pad = (n) => String(n).padStart(2, '0');
            const toIso = (date) => date
                ? `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
                : '';
            const toDate = (iso) => {
                const date = iso ? new Date(iso) : null;

                return date && ! Number.isNaN(date.getTime()) ? date : null;
            };
            let picker = null;

            return {
                init() {
                    picker = flatpickr(this.$refs.input, {
                        enableTime: true,
                        time_24hr: true,
                        allowInput: true,
                        disableMobile: true,
                        locale: 'de',
                        dateFormat: 'd.m.Y H:i',
                        defaultDate: toDate(this.$wire.get(property)),
                        position: (self) => this.position(self),
                        onReady: (dates, value, self) => self.calendarContainer.classList.add('adminv2-calendar'),
                        onChange: (dates) => {
                            const iso = toIso(dates[0]);

                            if (iso !== (this.$wire.get(property) || '')) {
                                this.$wire.set(property, iso, false);
                            }
                        },
                    });

                    this.$wire.$watch(property, (value) => {
                        if (toIso(picker.selectedDates[0]) !== (value || '')) {
                            picker.setDate(toDate(value), false);
                        }
                    });
                },

                // Der Kalender ist per transform vergroessert; flatpickr rechnet mit
                // der unvergroesserten Box. Deshalb hier: unter das Feld, sonst
                // darueber; linksbuendig, sonst rechtsbuendig – so bleibt er im Fenster.
                position(self) {
                    const calendar = self.calendarContainer;
                    const scale = parseFloat(getComputedStyle(calendar).getPropertyValue('--adminv2-calendar-scale')) || 1;
                    const field = self._input.getBoundingClientRect();
                    const width = calendar.offsetWidth;
                    const height = calendar.offsetHeight;
                    const fitsRight = field.left + width * scale <= window.innerWidth - 8;
                    const fitsBelow = field.bottom + 4 + height * scale <= window.innerHeight - 8 || field.top - 4 - height * scale < 8;

                    calendar.style.transformOrigin = `${fitsBelow ? 'top' : 'bottom'} ${fitsRight ? 'left' : 'right'}`;
                    calendar.style.left = `${window.scrollX + (fitsRight ? field.left : field.right - width)}px`;
                    calendar.style.top = `${window.scrollY + (fitsBelow ? field.bottom + 4 : field.top - 4 - height)}px`;
                    calendar.classList.toggle('arrowTop', fitsBelow);
                    calendar.classList.toggle('arrowBottom', ! fitsBelow);
                },

                destroy() {
                    picker?.destroy();
                    picker = null;
                },
            };
        };
    </script>
    <script>
        // Kleine Karten-Vorschau mit Pin. Die Karte selbst liegt ausserhalb der
        // Alpine-Daten; aendern sich die Koordinaten, baut Livewire das Element neu auf.
        window.adminv2LocationMap = (lat, lng, zoom) => {
            let map = null;

            return {
                init() {
                    map = L.map(this.$refs.map, {
                        center: [lat, lng],
                        zoom,
                        // Das Mausrad soll die Seite scrollen, nicht die Karte zoomen.
                        scrollWheelZoom: false,
                        attributionControl: true,
                    });

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 18,
                        attribution: '&copy; OpenStreetMap',
                    }).addTo(map);

                    L.marker([lat, lng]).addTo(map);

                    setTimeout(() => map && map.invalidateSize(), 50);
                },
                destroy() {
                    map?.remove();
                    map = null;
                },
            };
        };
    </script>
    <script>
        // Texteditor je Sprache. Geschrieben wird nur, was von Hand geaendert
        // wurde – ein unberuehrter Text bleibt unveraendert gespeichert.
        window.adminv2Editor = (value, placeholder, locale) => {
            // Bewusst ausserhalb der Alpine-Daten: als reaktives Proxy-Objekt
            // verliert Quill den Bezug zu seiner eigenen Auswahl.
            let quill = null;

            return {
                value,
                init() {
                    // Die Editoren der anderen Sprachen sind zunaechst ausgeblendet.
                    // Quill wird erst gestartet, wenn der Editor sichtbar ist –
                    // in einem versteckten Element kann es keine Auswahl bestimmen.
                    if (this.$el.offsetParent !== null) {
                        this.boot();

                        return;
                    }

                    const observer = new IntersectionObserver((entries) => {
                        if (entries.some((entry) => entry.isIntersecting)) {
                            observer.disconnect();
                            this.boot();
                        }
                    });
                    observer.observe(this.$el);
                },
                // Inhalt von aussen setzen, z. B. wenn ein KI-Vorschlag uebernommen wird.
                setContent(detail) {
                    if (detail.locale !== locale) return;

                    this.value = detail.html;
                    quill?.setContents(quill.clipboard.convert({ html: detail.html }), 'silent');
                },
                boot() {
                    quill = new Quill(this.$refs.editor, {
                        theme: 'snow',
                        placeholder,
                        modules: {
                            toolbar: [
                                [{ header: [2, 3, false] }],
                                ['bold', 'italic', 'underline', 'strike'],
                                ['link', 'blockquote'],
                                [{ list: 'ordered' }, { list: 'bullet' }],
                                ['clean'],
                            ],
                        },
                    });

                    if (this.value) {
                        quill.setContents(quill.clipboard.convert({ html: this.value }), 'silent');
                    }

                    quill.on('text-change', (delta, previous, source) => {
                        if (source !== 'user') return;

                        this.value = quill.getText().trim() === ''
                            ? ''
                            : quill.getSemanticHTML().replaceAll('&nbsp;', ' ');
                    });
                },
            };
        };
    </script>
    <style>
        /* Laufender Balken der KI-Ladeanzeige */
        .adminv2-ai-bar > span { display: block; height: 100%; width: 40%; border-radius: 9999px; background: var(--color-accent); animation: adminv2-ai-slide 1.4s ease-in-out infinite; }
        @keyframes adminv2-ai-slide { 0% { transform: translateX(-100%); } 100% { transform: translateX(250%); } }
        @media (prefers-reduced-motion: reduce) { .adminv2-ai-bar > span { animation-duration: 4s; } }

        .adminv2-editor .ql-toolbar.ql-snow { border-color: var(--color-zinc-200); border-radius: 0.75rem 0.75rem 0 0; background: var(--color-zinc-50); }
        .adminv2-editor .ql-container.ql-snow { height: auto; border-color: var(--color-zinc-200); border-radius: 0 0 0.75rem 0.75rem; font-family: inherit; font-size: 0.9375rem; }
        .adminv2-editor .ql-editor { min-height: 16rem; line-height: 1.6; }
        .adminv2-editor .ql-editor.ql-blank::before { color: var(--color-zinc-400); font-style: normal; }
        .dark .adminv2-editor .ql-toolbar.ql-snow { border-color: var(--color-zinc-700); background: var(--color-zinc-900); }
        .dark .adminv2-editor .ql-container.ql-snow { border-color: var(--color-zinc-700); color: var(--color-zinc-100); }
        .dark .adminv2-editor .ql-snow .ql-stroke { stroke: var(--color-zinc-300); }
        .dark .adminv2-editor .ql-snow .ql-fill { fill: var(--color-zinc-300); }
        .dark .adminv2-editor .ql-snow .ql-picker { color: var(--color-zinc-300); }
        .dark .adminv2-editor .ql-snow .ql-picker-options { background: var(--color-zinc-800); }
    </style>
@endassets

<div class="flex flex-col gap-6">
    {{-- Kopf --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('adminv2.events.index') }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Passolution Ereignisse
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <flux:heading size="xl" level="1" class="truncate">
                    {{ $event ? ($event->getTitle('de') ?: 'Ohne Titel') : 'Neues Ereignis' }}
                </flux:heading>
                @if ($state)
                    <x-adminv2.state-badge :state="$state" />
                @endif
                @if ($event && ($event->version ?? 1) > 1)
                    <flux:badge color="zinc" size="sm" inset="top bottom">Version {{ $event->version }}</flux:badge>
                @endif
            </div>
        </div>

        @unless ($isTrashed)
            <div class="flex flex-wrap items-center gap-2">
                @if ($isPublished)
                    <flux:modal.trigger name="new-version">
                        <flux:button icon="document-duplicate">Neue Version</flux:button>
                    </flux:modal.trigger>
                    <flux:button variant="primary" wire:click="save" icon="check">Änderungen speichern</flux:button>
                @else
                    <flux:button wire:click="save">{{ $event ? 'Speichern' : 'Als Entwurf speichern' }}</flux:button>
                    <flux:modal.trigger name="publish">
                        <flux:button variant="primary" icon="rocket-launch">
                            {{ $state === EventState::PendingReview ? 'Freigeben & veröffentlichen' : 'Veröffentlichen' }}
                        </flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        @endunless
    </div>

    @if ($errors->any())
        <flux:callout variant="danger" icon="exclamation-triangle" heading="Bitte die markierten Angaben prüfen.">
            <flux:callout.text>
                <ul class="list-disc ps-5">
                    @foreach (collect($errors->all())->unique() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </flux:callout.text>
        </flux:callout>
    @endif

    {{-- Hinweise zum Zustand --}}
    @if ($isTrashed)
        <flux:callout variant="warning" icon="trash" heading="Dieses Ereignis liegt im Papierkorb.">
            <x-slot name="actions">
                <flux:button size="sm" wire:click="restore">Wiederherstellen</flux:button>
            </x-slot>
        </flux:callout>
    @elseif ($state === EventState::Superseded)
        <flux:callout variant="secondary" icon="clock" heading="Diese Version wurde abgelöst.">
            <flux:callout.text>
                Sie bleibt als Historie erhalten. Aktuell gilt Version {{ $event->supersededBy?->version ?? '?' }}.
            </flux:callout.text>
            @if ($event->supersededBy)
                <x-slot name="actions">
                    <flux:button size="sm" :href="route('adminv2.events.edit', $event->supersededBy)">Zur aktuellen Version</flux:button>
                </x-slot>
            @endif
        </flux:callout>
    @elseif ($isPublished)
        <flux:callout variant="secondary" icon="information-circle" heading="Dieses Ereignis ist veröffentlicht.">
            <flux:callout.text>
                „Änderungen speichern“ wirkt sofort und still – ohne neue Version und ohne erneute Benachrichtigung. Das passt für Korrekturen.
                Bei inhaltlichen Änderungen besser eine neue Version anlegen: der bisherige Stand bleibt nachvollziehbar, und Kunden werden über die Aktualisierung informiert.
            </flux:callout.text>
        </flux:callout>
    @elseif (! $event)
        <flux:callout variant="secondary" icon="information-circle">
            <flux:callout.text>
                Ein neues Ereignis entsteht als Entwurf. Ausgeliefert wird es erst nach „Veröffentlichen“ – dafür braucht es mindestens einen Standort.
            </flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-3 min-[1900px]:grid-cols-4">
        {{-- Hauptspalte: alle Abschnitte untereinander --}}
        <div class="flex flex-col gap-6 xl:col-span-2 min-[1900px]:col-span-3">
            {{-- Inhalt --}}
            <x-adminv2.card heading="Inhalt" description="Titel und Beschreibung je Sprache. Ausgangssprache ist {{ CustomEvent::localeLabel($sourceLocale, false) }}.">
                <x-slot:actions>
                    <flux:modal.trigger name="translate">
                        <flux:button size="sm" icon="language">Übersetzen</flux:button>
                    </flux:modal.trigger>
                </x-slot:actions>

                <div x-data="{ locale: @js($sourceLocale) }">
                    <div class="mb-4 inline-flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                        @foreach ($locales as $locale)
                            <button
                                type="button"
                                x-on:click="locale = @js($locale)"
                                class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition"
                                :class="locale === @js($locale)
                                    ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-950 dark:text-white'
                                    : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'"
                            >
                                {{ CustomEvent::localeLabel($locale) }}
                                @if (filled($titles[$locale] ?? null))
                                    <span class="size-1.5 rounded-full bg-green-500" title="Titel vorhanden"></span>
                                @else
                                    <span class="size-1.5 rounded-full bg-zinc-300 dark:bg-zinc-600" title="Noch kein Titel"></span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    @foreach ($locales as $locale)
                        <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif class="flex flex-col gap-5" wire:key="content-{{ $locale }}">
                            <flux:input
                                wire:model.blur="titles.{{ $locale }}"
                                :label="'Titel'.($locale === $sourceLocale ? '' : ' ('.strtoupper($locale).')')"
                                placeholder="z. B. Streik im öffentlichen Nahverkehr in Rom"
                                maxlength="255"
                            />

                            <flux:field>
                                <flux:label>Beschreibung</flux:label>
                                <div
                                    wire:ignore
                                    class="adminv2-editor"
                                    x-data="adminv2Editor($wire.entangle('contents.{{ $locale }}'), 'Was ist passiert, was bedeutet es für Reisende?', @js($locale))"
                                    x-on:adminv2-editor-set.window="setContent($event.detail)"
                                >
                                    <div x-ref="editor"></div>
                                </div>
                                <flux:error name="contents.{{ $locale }}" />
                            </flux:field>
                        </div>
                    @endforeach
                </div>
            </x-adminv2.card>

            {{-- Einordnung --}}
            <x-adminv2.card heading="Einordnung" description="Typ und Priorität bestimmen Icon, Filter und welche Benachrichtigungsregeln greifen.">
                <div class="flex flex-col gap-6">
                    <flux:field>
                        <flux:label>Event-Typen</flux:label>
                        {{-- Jede Box ist so breit, dass der laengste Typname samt Symbol und Haken in
                             eine Zeile passt (ch = Zeichenbreite); es stehen so viele nebeneinander,
                             wie dann Platz haben. Der Platz fuer den Haken ist immer reserviert. --}}
                        @php $typeMinWidth = 'calc('.max(8, (int) $this->eventTypes->max(fn ($type) => mb_strlen($type->name))).'ch + 5.75rem)'; @endphp
                        <div class="grid gap-2 text-sm" style="grid-template-columns: repeat(auto-fill, minmax(min({{ $typeMinWidth }}, 100%), 1fr));">
                            @foreach ($this->eventTypes as $eventType)
                                {{-- Die Checkbox bleibt fuer Tastatur und Screenreader erhalten,
                                     sichtbar ist nur die Markierung der gewaehlten Box. --}}
                                <label
                                    wire:key="type-{{ $eventType->id }}"
                                    class="flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 px-3 py-2.5 text-sm text-zinc-700 transition hover:border-zinc-300 has-[:checked]:border-[var(--color-accent)] has-[:checked]:bg-[var(--color-accent)]/10 has-[:checked]:text-[var(--color-accent)] has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[var(--color-accent)]/40 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600"
                                >
                                    <input type="checkbox" wire:model.live="eventTypeIds" value="{{ $eventType->id }}" class="peer sr-only" />
                                    <i class="fas {{ $eventType->icon ?: 'fa-map-marker' }} w-5 shrink-0 text-center opacity-70 peer-checked:opacity-100" aria-hidden="true"></i>
                                    <span class="min-w-0 flex-1 font-medium break-words hyphens-auto" lang="de">{{ $eventType->name }}</span>
                                    <flux:icon.check variant="mini" class="invisible shrink-0 peer-checked:visible" />
                                </label>
                            @endforeach
                        </div>
                        <flux:error name="eventTypeIds" />
                    </flux:field>

                    @if ($this->allowsDisplayTypeSelection && count($eventTypeIds) > 1)
                        <div class="max-w-sm">
                            <flux:select wire:model="displayTypeId" label="Icon auf der Karte" description:trailing="Bei mehreren Typen: welches Icon der Marker zeigt.">
                                <flux:select.option value="">Automatisch (erster Typ)</flux:select.option>
                                @foreach ($this->eventTypes->whereIn('id', $eventTypeIds) as $eventType)
                                    <flux:select.option value="{{ $eventType->id }}">{{ $eventType->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endif

                    <flux:radio.group wire:model="priority" label="Priorität" variant="segmented" class="max-w-xl">
                        @foreach (CustomEvent::getPriorityOptions() as $value => $label)
                            <flux:radio value="{{ $value }}" label="{{ $label }}" />
                        @endforeach
                    </flux:radio.group>
                </div>
            </x-adminv2.card>

            {{-- Zeitraum --}}
            <x-adminv2.card heading="Zeitraum" description="Reisen gelten als betroffen, wenn sich ihr Reisezeitraum mit diesem Zeitraum überschneidet.">
                <div class="grid items-start gap-5 sm:grid-cols-2">
                    <x-adminv2.datetime-picker property="startDate" label="Beginn" />
                    <x-adminv2.datetime-picker property="endDate" label="Ende" description="Leer lassen für ein Ereignis ohne absehbares Ende." />
                </div>
            </x-adminv2.card>

            {{-- Standorte --}}
            <x-adminv2.card heading="Standorte" description="Mindestens ein Land. Region oder Stadt verfeinern die Position auf der Karte; pro Land sind mehrere Standorte möglich. Je Standort lassen sich die Standard-Koordinaten ausschalten und eigene Koordinaten eintragen.">
                <div class="flex flex-col gap-5">
                    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
                        <flux:input
                            wire:model.live.debounce.300ms="locationSearch"
                            icon="magnifying-glass"
                            placeholder="Stadt, Region oder Land suchen …"
                            x-on:focus="open = true"
                            x-on:input="open = true"
                            autocomplete="off"
                        />

                        @if (mb_strlen(trim($locationSearch)) >= 2)
                            <div
                                x-show="open"
                                x-cloak
                                class="absolute z-20 mt-1 max-h-[32rem] w-full overflow-y-auto rounded-xl border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                            >
                                @forelse ($this->locationResults as $result)
                                    @php
                                        $isCity = $result['type'] === 'city';
                                        $expanded = ($result['type'] === 'country' && $browseCountryId === $result['id'])
                                            || ($result['type'] === 'region' && $browseRegionId === $result['id']);
                                    @endphp
                                    <div wire:key="result-{{ $result['type'] }}-{{ $result['id'] }}" @class(['rounded-lg', 'bg-zinc-50 dark:bg-zinc-800/60' => $expanded])>
                                        <div class="flex items-center gap-2 pe-2">
                                            {{-- Stadt: anklicken ordnet zu. Land/Region: anklicken klappt die
                                                 Regionen bzw. Staedte auf, zugeordnet wird ueber den Knopf rechts. --}}
                                            <button
                                                type="button"
                                                wire:click="{{ $isCity ? "addLocation('city', {$result['id']})" : ($result['type'] === 'country' ? "browseCountry({$result['id']})" : "browseRegion({$result['id']})") }}"
                                                class="flex min-w-0 flex-1 items-center gap-2 rounded-lg px-3 py-2 text-start text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
                                                @if (! $isCity) aria-expanded="{{ $expanded ? 'true' : 'false' }}" @endif
                                            >
                                                @unless ($isCity)
                                                    <flux:icon.chevron-right variant="micro" class="shrink-0 text-zinc-400 transition-transform {{ $expanded ? 'rotate-90' : '' }}" />
                                                @endunless
                                                <span class="min-w-0 truncate">
                                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $result['name'] }}</span>
                                                    @if ($result['context'] !== '')
                                                        <span class="text-zinc-500"> – {{ $result['context'] }}</span>
                                                    @endif
                                                </span>
                                                <span class="ms-auto shrink-0 rounded bg-zinc-100 px-1.5 py-0.5 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $typeLabels[$result['type']] }}</span>
                                            </button>
                                            @unless ($isCity)
                                                <flux:button size="xs" variant="subtle" icon="plus" wire:click="addLocation('{{ $result['type'] }}', {{ $result['id'] }})">Zuordnen</flux:button>
                                            @endunless
                                        </div>

                                        @if ($expanded)
                                            <div class="flex flex-col gap-2 px-3 pb-3 pt-1">
                                                @if ($result['type'] === 'country')
                                                    <x-adminv2.location-tags
                                                        heading="Regionen"
                                                        empty="Zu diesem Land sind keine Regionen hinterlegt."
                                                        :items="$this->browseRegions"
                                                        :active="$browseRegionId"
                                                        click="browseRegion"
                                                    />
                                                @endif

                                                @if ($browseRegionId && ($result['type'] === 'region' || collect($this->browseRegions)->contains('id', $browseRegionId)))
                                                    @php $browsedRegion = $result['type'] === 'region' ? $result['name'] : collect($this->browseRegions)->firstWhere('id', $browseRegionId)['name']; @endphp
                                                    <x-adminv2.location-tags
                                                        wire:key="cities-{{ $browseRegionId }}"
                                                        heading="Städte in {{ $browsedRegion }}"
                                                        empty="Zu dieser Region sind keine Städte hinterlegt."
                                                        :items="$this->browseCities"
                                                        click="addLocation('city', :id)"
                                                        :nested="$result['type'] === 'country'"
                                                    >
                                                        @if ($result['type'] === 'country')
                                                            <flux:button size="xs" variant="subtle" icon="plus" wire:click="addLocation('region', {{ $browseRegionId }})">Nur die Region zuordnen</flux:button>
                                                        @endif
                                                    </x-adminv2.location-tags>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <p class="px-3 py-2 text-sm text-zinc-500">Kein Ort gefunden für „{{ $locationSearch }}“.</p>
                                @endforelse
                            </div>
                        @endif
                    </div>

                    @error('locations')
                        <flux:callout variant="danger" icon="exclamation-triangle">
                            <flux:callout.text>{{ $message }}</flux:callout.text>
                        </flux:callout>
                    @enderror

                    @if ($locations === [])
                        <div class="rounded-xl border border-dashed border-zinc-300 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700">
                            Noch kein Standort zugeordnet. Über die Suche oben einen Ort hinzufügen – danach lassen sich für ihn auch eigene Koordinaten eintragen.
                        </div>
                    @else
                        <ul class="flex flex-col gap-3">
                            @foreach ($locations as $index => $location)
                                <li
                                    wire:key="location-{{ $index }}-{{ $location['country_id'] }}-{{ $location['region_id'] ?? 0 }}-{{ $location['city_id'] ?? 0 }}"
                                    class="rounded-xl border border-zinc-200 bg-zinc-100/70 p-4 dark:border-zinc-700 dark:bg-zinc-900"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex min-w-0 items-center gap-2.5">
                                            <span class="shrink-0 rounded bg-white px-1.5 py-0.5 font-mono text-xs font-medium text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700">{{ $location['iso_code'] ?? '–' }}</span>
                                            <span class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $location['label'] }}</span>
                                        </div>
                                        <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="removeLocation({{ $index }})" aria-label="Standort entfernen" />
                                    </div>

                                    @php
                                        // Position fuer die Vorschau; je genauer der Ort, desto naeher der Ausschnitt.
                                        $mapPosition = app(\App\Services\CustomEventLocationService::class)->parseCoordinates($location['coordinates'] ?? '');
                                        $mapZoom = ! empty($location['city_id']) ? 10 : (! empty($location['region_id']) ? 7 : 5);
                                        $googleMapsUrl = $mapPosition
                                            ? 'https://www.google.com/maps/search/?api=1&query='.$mapPosition[0].','.$mapPosition[1]
                                            : null;
                                    @endphp

                                    {{-- Untereinander: Schalter, Karte ueber die ganze Breite, Koordinaten, Notiz --}}
                                    <div class="mt-3 flex flex-col gap-4">
                                        <flux:switch
                                            wire:model.live="locations.{{ $index }}.use_default_coordinates"
                                            label="Standard-Koordinaten verwenden"
                                            description="Ausschalten, um eigene Koordinaten einzutragen. Standard ist die Stadt, bei einer Region ihre Hauptstadt, bei einem Land die Landeshauptstadt."
                                            align="left"
                                        />

                                        <div class="flex flex-col gap-2">
                                            @if ($mapPosition)
                                                <div
                                                    wire:key="map-{{ $index }}-{{ md5(implode(',', $mapPosition).'-'.$mapZoom) }}"
                                                    wire:ignore
                                                    x-data="adminv2LocationMap({{ $mapPosition[0] }}, {{ $mapPosition[1] }}, {{ $mapZoom }})"
                                                    class="relative z-0 h-56 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700"
                                                >
                                                    <div x-ref="map" class="size-full" role="img" aria-label="Kartenvorschau für {{ $location['label'] }}"></div>
                                                </div>
                                            @else
                                                <div class="flex h-32 items-center justify-center rounded-lg border border-dashed border-zinc-300 px-4 text-center text-sm text-zinc-500 dark:border-zinc-700">
                                                    Keine Kartenvorschau – für diesen Standort fehlen lesbare Koordinaten.
                                                </div>
                                            @endif

                                            @if (empty($location['use_default_coordinates']))
                                                <div class="max-w-md">
                                                    <flux:input
                                                        wire:model.blur="locations.{{ $index }}.coordinates"
                                                        label="Koordinaten"
                                                        placeholder="50.1109, 8.6821"
                                                        description:trailing="Breite, Länge – z. B. aus Google Maps kopiert."
                                                    />
                                                    @if ($googleMapsUrl)
                                                        <a href="{{ $googleMapsUrl }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center gap-1 text-sm text-zinc-700 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-zinc-300">
                                                            In Google Maps öffnen <flux:icon.arrow-top-right-on-square variant="micro" />
                                                        </a>
                                                    @endif
                                                </div>
                                            @elseif (($location['coordinates'] ?? '') !== '')
                                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                                    <span class="font-medium text-zinc-800 dark:text-white">Koordinaten</span>
                                                    @if ($googleMapsUrl)
                                                        <a href="{{ $googleMapsUrl }}" target="_blank" rel="noopener noreferrer" title="In Google Maps öffnen" class="inline-flex items-center gap-1 tabular-nums underline decoration-zinc-300 underline-offset-2 hover:text-zinc-900 hover:decoration-zinc-900 dark:hover:text-white">
                                                            {{ $location['coordinates'] }} <flux:icon.arrow-top-right-on-square variant="micro" />
                                                        </a>
                                                    @else
                                                        <span class="tabular-nums">{{ $location['coordinates'] }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-sm text-red-600 dark:text-red-400">{{ $location['coordinate_issue'] ?? 'Für diesen Ort sind keine Koordinaten hinterlegt.' }}</div>
                                            @endif

                                            @if (! empty($location['use_default_coordinates']))
                                                <flux:error name="locations.{{ $index }}.coordinates" />
                                            @endif
                                        </div>

                                        <flux:textarea
                                            wire:model.blur="locations.{{ $index }}.location_note"
                                            label="Notiz"
                                            rows="4"
                                            placeholder="z. B. Flughafen, Altstadt"
                                        />
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <flux:switch
                        wire:model="isNationwide"
                        label="Landesweit"
                        description="Gilt im gesamten Land: Bei Suchen nach Koordinaten oder Flughafen-Code wird das Ereignis unabhängig von der Entfernung gefunden."
                        align="left"
                    />
                </div>
            </x-adminv2.card>

            {{-- Quellen. "Alle pruefen" stoesst die Pruefungen nacheinander an, damit jede
                 Quelle ihre eigene Ladeanzeige und ihr eigenes Ergebnis bekommt. --}}
            @php
                $checkableSources = collect($sources)
                    ->filter(fn ($source) => filled($source['link_url'] ?? null))
                    ->keys()
                    ->all();
            @endphp
            <div
                x-data="{
                    checkingAll: false,
                    async checkAll(indices) {
                        this.checkingAll = true;
                        try {
                            for (const index of indices) {
                                await $wire.checkSource(index);
                            }
                        } finally {
                            this.checkingAll = false;
                        }
                    },
                }"
            >
                <x-adminv2.card heading="Quellen" description="Beliebig viele Quellenangaben mit Link. Die KI-Prüfung vergleicht Titel, Beschreibung, Typen, Priorität, Zeitraum und Standorte mit dem heutigen Inhalt der Quelle und macht Vorschläge.">
                    <x-slot:actions>
                        <flux:button size="sm" icon="plus" wire:click="addSource">Quelle hinzufügen</flux:button>
                        @if (count($checkableSources) > 0)
                            <flux:button
                                size="sm"
                                icon="sparkles"
                                x-on:click="checkAll({{ \Illuminate\Support\Js::from($checkableSources) }})"
                                x-bind:disabled="checkingAll"
                            >
                                <span x-show="! checkingAll">Alle Quellen mit KI prüfen</span>
                                <span x-show="checkingAll" x-cloak>Prüfung läuft …</span>
                            </flux:button>
                        @endif
                    </x-slot:actions>

                    @if ($sources === [])
                        <p class="text-sm text-zinc-500">Noch keine Quelle angegeben.</p>
                    @else
                        <ul class="flex flex-col gap-3">
                            @foreach ($sources as $index => $source)
                                @php
                                    $sourceUrl = trim((string) ($source['link_url'] ?? ''));
                                    $isLink = \Illuminate\Support\Str::startsWith($sourceUrl, ['http://', 'https://']);
                                    $check = $sourceUrl !== '' ? ($sourceChecks[\App\Models\CustomEventSourceCheck::hashFor($sourceUrl)] ?? null) : null;
                                    $checkStyles = [
                                        'unchanged' => ['Keine Änderung erkannt', 'check-circle', 'border-green-200 bg-green-50 text-green-900 dark:border-green-400/20 dark:bg-green-400/10 dark:text-green-100', 'text-green-600 dark:text-green-400'],
                                        'changed' => ['Die Situation hat sich möglicherweise geändert', 'exclamation-triangle', 'border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-100', 'text-amber-600 dark:text-amber-400'],
                                        'unclear' => ['Aus der Quelle nicht eindeutig zu beurteilen', 'information-circle', 'border-zinc-200 bg-zinc-50 text-zinc-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200', 'text-zinc-500'],
                                        'error' => ['Prüfung nicht möglich', 'x-circle', 'border-red-200 bg-red-50 text-red-900 dark:border-red-400/20 dark:bg-red-400/10 dark:text-red-100', 'text-red-600 dark:text-red-400'],
                                    ];
                                @endphp
                                <li wire:key="source-{{ $index }}" class="rounded-xl border border-zinc-200 bg-zinc-100/70 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                    <div class="grid gap-3 sm:grid-cols-[1fr_1.5fr] sm:items-start">
                                        <flux:input wire:model.blur="sources.{{ $index }}.link_text" placeholder="Link-Text, z. B. Auswärtiges Amt" aria-label="Link-Text" />
                                        <div>
                                            <x-adminv2.url-input wire:model.blur="sources.{{ $index }}.link_url" type="url" aria-label="Link-Adresse" />
                                            <flux:error name="sources.{{ $index }}.link_url" />
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <flux:checkbox wire:model="sources.{{ $index }}.show_frontend" label="Im Frontend anzeigen" />

                                        <flux:spacer />

                                        @if ($isLink)
                                            <flux:button size="sm" icon="arrow-top-right-on-square" href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer">
                                                Link öffnen
                                            </flux:button>
                                            <flux:button
                                                size="sm"
                                                icon="sparkles"
                                                wire:click="checkSource({{ $index }})"
                                                wire:loading.attr="disabled"
                                                wire:target="checkSource({{ $index }})"
                                                x-bind:disabled="checkingAll"
                                            >
                                                Mit KI prüfen
                                            </flux:button>
                                        @endif
                                        <flux:modal.trigger name="delete-source-{{ $index }}">
                                            <flux:button size="sm" icon="trash">Quelle löschen</flux:button>
                                        </flux:modal.trigger>
                                    </div>

                                    {{-- Rueckfrage vor dem Loeschen --}}
                                    <flux:modal name="delete-source-{{ $index }}" class="md:w-[32rem]">
                                        <div class="flex flex-col gap-5">
                                            <div>
                                                <flux:heading size="lg">Quelle löschen?</flux:heading>
                                                <flux:text class="mt-2">
                                                    @if (filled($source['link_text'] ?? null) || $sourceUrl !== '')
                                                        Die Quelle „{{ ($source['link_text'] ?? '') ?: $sourceUrl }}“ wird aus diesem Ereignis entfernt.
                                                    @else
                                                        Diese noch leere Quelle wird entfernt.
                                                    @endif
                                                    Endgültig gelöscht ist sie erst, wenn das Ereignis gespeichert wird.
                                                </flux:text>
                                                @if ($sourceUrl !== '' && filled($source['link_text'] ?? null))
                                                    <flux:text class="mt-2 break-all font-mono text-xs">{{ $sourceUrl }}</flux:text>
                                                @endif
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                                                <flux:modal.close>
                                                    <flux:button variant="danger" icon="trash" wire:click="removeSource({{ $index }})">Quelle löschen</flux:button>
                                                </flux:modal.close>
                                            </div>
                                        </div>
                                    </flux:modal>

                                    {{-- Ladeanzeige waehrend der Pruefung --}}
                                    <div
                                        wire:loading.flex
                                        wire:target="checkSource({{ $index }})"
                                        class="adminv2-ai-working mt-3 hidden items-center gap-3 overflow-hidden rounded-lg border border-[var(--color-accent)]/20 bg-[var(--color-accent)]/5 px-4 py-3"
                                        role="status"
                                    >
                                        <flux:icon.sparkles variant="mini" class="shrink-0 animate-pulse text-[var(--color-accent)]" />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">Die KI prüft diese Quelle …</p>
                                            <p class="text-xs text-zinc-500">Seite wird abgerufen und mit dem erfassten Stand verglichen. Das dauert meist 10 bis 30 Sekunden.</p>
                                            <div class="adminv2-ai-bar mt-2 h-1 overflow-hidden rounded-full bg-[var(--color-accent)]/15"><span></span></div>
                                        </div>
                                    </div>

                                    {{-- Ergebnis der letzten Pruefung --}}
                                    @if ($check)
                                        @php [$checkTitle, $checkIcon, $checkBox, $checkIconColor] = $checkStyles[$check['status']] ?? $checkStyles['unclear']; @endphp
                                        <div
                                            wire:loading.remove
                                            wire:target="checkSource({{ $index }})"
                                            class="mt-3 flex gap-3 rounded-lg border px-4 py-3 {{ $checkBox }}"
                                        >
                                            <flux:icon :icon="$checkIcon" variant="mini" class="mt-0.5 shrink-0 {{ $checkIconColor }}" />
                                            <div class="min-w-0 flex-1 text-sm">
                                                <p class="font-semibold">{{ $checkTitle }}</p>
                                                @if ($check['summary'] !== '')
                                                    <p class="mt-1 break-words">{{ $check['summary'] }}</p>
                                                @endif
                                                @if (! empty($check['changes']))
                                                    <ul class="mt-2 list-disc space-y-0.5 ps-5">
                                                        @foreach ($check['changes'] as $change)
                                                            <li class="break-words">{{ $change }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                                @if (! empty($check['suggestion']))
                                                    <p class="mt-2 break-words"><span class="font-medium">Empfehlung:</span> {{ $check['suggestion'] }}</p>
                                                @endif
                                                @if (! empty($check['proposals']))
                                                    @php $checkHash = \App\Models\CustomEventSourceCheck::hashFor($sourceUrl); @endphp
                                                    <div class="mt-3 rounded-lg border border-black/10 bg-white/70 p-3 text-zinc-900 dark:border-white/10 dark:bg-black/20 dark:text-zinc-100">
                                                        <p class="font-semibold">Vorschläge der KI</p>
                                                        <ul class="mt-2 flex flex-col divide-y divide-black/5 dark:divide-white/10">
                                                            @foreach ($check['proposals'] as $proposalIndex => $proposal)
                                                                <li wire:key="proposal-{{ $index }}-{{ $proposalIndex }}" class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2 py-2.5 first:pt-0 last:pb-0">
                                                                    <div class="min-w-0 flex-1 basis-64">
                                                                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ $proposal['label'] }}</p>
                                                                        <p class="mt-0.5 break-words whitespace-pre-line">{{ $proposal['display'] }}</p>
                                                                        @if (! empty($proposal['reason']))
                                                                            <p class="mt-1 text-xs break-words text-zinc-600 dark:text-zinc-400">{{ $proposal['reason'] }}</p>
                                                                        @endif
                                                                    </div>

                                                                    @if (! empty($proposal['applied']))
                                                                        <div class="flex shrink-0 items-center gap-2">
                                                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 dark:text-green-400">
                                                                                <flux:icon.check variant="micro" /> Übernommen
                                                                            </span>
                                                                            <flux:button size="xs" variant="ghost" icon="arrow-uturn-left" wire:click="undoProposal('{{ $checkHash }}', {{ $proposalIndex }})">
                                                                                Rückgängig
                                                                            </flux:button>
                                                                        </div>
                                                                    @elseif ($proposal['field'] === 'locations')
                                                                        <span class="shrink-0 text-xs text-zinc-500">Bitte oben über die Suche anpassen</span>
                                                                    @else
                                                                        <flux:button size="xs" wire:click="applyProposal('{{ $checkHash }}', {{ $proposalIndex }})">Übernehmen</flux:button>
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif

                                                <p class="mt-2 text-xs opacity-70">
                                                    KI-Prüfung vom {{ $check['checked_at'] }}@if ($check['checked_by']) durch {{ $check['checked_by'] }}@endif.
                                                    @if ($check['status'] !== 'error') Bitte die Einschätzung an der Quelle gegenprüfen. @endif
                                                </p>
                                                @if (! empty($check['usage']))
                                                    {{-- Verbrauch und Kosten der Anfrage --}}
                                                    <p class="mt-1 text-xs tabular-nums opacity-70">
                                                        Verbrauch: {{ number_format($check['usage']['total_tokens'], 0, ',', '.') }} Token
                                                        ({{ number_format($check['usage']['input_tokens'], 0, ',', '.') }} Eingabe, {{ number_format($check['usage']['output_tokens'], 0, ',', '.') }} Ausgabe)
                                                        · Modell {{ $check['usage']['model'] }}
                                                        · @if ($check['usage']['cost'] !== null) Kosten ca. {{ number_format($check['usage']['cost'], 4, ',', '.') }} $ @else Kosten: kein Preis für dieses Modell hinterlegt (System › KI) @endif
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-adminv2.card>
            </div>
        </div>

        {{-- Seitenspalte --}}
        <aside class="flex flex-col gap-6 xl:sticky xl:top-6">
            {{-- Zugeklappt zeigt die Kopfzeile den Zustand; aufgeklappt kommen Daten und Aktionen dazu. --}}
            <x-adminv2.card
                heading="Status"
                :description="$state ? $state->label().' – '.$state->description() : 'Neu – noch nicht gespeichert.'"
                collapsible
                collapsed
            >
                <div class="flex flex-col gap-4">
                    <div>
                        @if ($state)
                            <x-adminv2.state-badge :state="$state" size="base" />
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $state->description() }}</p>
                        @else
                            <flux:badge color="indigo" inset="top bottom">Neu</flux:badge>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Noch nicht gespeichert.</p>
                        @endif
                    </div>

                    @if ($event)
                        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-sm">
                            @if ($event->activated_at)
                                <dt class="text-zinc-500">Veröffentlicht</dt>
                                <dd class="text-zinc-900 tabular-nums dark:text-white">{{ $event->activated_at->format('d.m.Y H:i') }}</dd>
                            @endif
                            <dt class="text-zinc-500">Angelegt</dt>
                            <dd class="text-zinc-900 tabular-nums dark:text-white">{{ $event->created_at?->format('d.m.Y H:i') }}</dd>
                            <dt class="text-zinc-500">Geändert</dt>
                            <dd class="text-zinc-900 tabular-nums dark:text-white">{{ $event->updated_at?->format('d.m.Y H:i') }}</dd>
                            @if ($event->apiClient)
                                <dt class="text-zinc-500">Herkunft</dt>
                                <dd class="text-zinc-900 dark:text-white">API: {{ $event->apiClient->name }}</dd>
                            @endif
                            <dt class="text-zinc-500">Klicks</dt>
                            <dd class="text-zinc-900 tabular-nums dark:text-white">
                                {{ number_format($this->clickStats['total'], 0, ',', '.') }}
                                <span class="text-zinc-500">({{ $this->clickStats['today'] }} heute)</span>
                            </dd>
                        </dl>
                    @endif

                    @if ($event && ! $isTrashed)
                        <flux:separator />

                        <div class="flex flex-col gap-2">
                            @if ($state === EventState::PendingReview)
                                <flux:button size="sm" icon="x-circle" wire:click="reject" wire:confirm="Dieses Ereignis ablehnen?">Ablehnen</flux:button>
                            @endif

                            <flux:modal.trigger name="rule-check">
                                <flux:button size="sm" icon="clipboard-document-check" class="w-full">Regeln der Kunden prüfen</flux:button>
                            </flux:modal.trigger>

                            @if ($event->is_active)
                                <flux:modal.trigger name="notify">
                                    <flux:button size="sm" icon="bell-alert" class="w-full">Benachrichtigungen auslösen</flux:button>
                                </flux:modal.trigger>
                                <flux:modal.trigger name="deactivate">
                                    <flux:button size="sm" icon="no-symbol" class="w-full">Deaktivieren</flux:button>
                                </flux:modal.trigger>
                            @endif

                            <flux:button size="sm" icon="archive-box" wire:click="toggleArchive">
                                {{ $event->archived ? 'Archivierung aufheben' : 'Archivieren' }}
                            </flux:button>

                            <flux:modal.trigger name="delete">
                                <flux:button size="sm" variant="ghost" icon="trash" class="w-full !text-red-600 dark:!text-red-400">Löschen</flux:button>
                            </flux:modal.trigger>
                        </div>
                    @endif
                </div>
            </x-adminv2.card>

            @unless ($isTrashed)
                <livewire:admin-v2.tasks.panel :event-id="$this->eventId" :token="$taskToken" :key="'tasks-'.($this->eventId ?? $taskToken)" />
            @endunless

            @if ($event)
                <x-adminv2.card heading="Versionen" description="Veröffentlichte Stände bleiben unverändert erhalten.">
                    <div class="flex flex-col gap-4">
                        <flux:textarea
                            wire:model="versionNote"
                            label="Änderungsnotiz dieser Version"
                            rows="2"
                            placeholder="Was hat sich gegenüber der Vorversion geändert?"
                            description="Erscheint in der Versionshistorie – auch für Kunden."
                        />

                        <flux:textarea
                            wire:model="versionInternalNote"
                            label="Interne Notiz dieser Version"
                            rows="3"
                            placeholder="Hintergründe, Absprachen, offene Punkte …"
                            description="Nur im Admin-Bereich sichtbar – Kunden sehen sie nicht."
                        />

                        <ul class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($this->versions as $version)
                                @php $versionState = EventState::of($version); @endphp
                                <li wire:key="version-{{ $version->id }}" class="py-2.5 first:pt-0 last:pb-0">
                                    <div class="flex items-center justify-between gap-3">
                                        @if ($version->id === $event->id)
                                            <span class="text-sm font-semibold text-zinc-900 dark:text-white">Version {{ $version->version ?? 1 }} · diese</span>
                                        @else
                                            <a href="{{ route('adminv2.events.edit', $version) }}" class="text-sm font-medium text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">
                                                Version {{ $version->version ?? 1 }}
                                            </a>
                                        @endif
                                        <x-adminv2.state-badge :state="$versionState" />
                                    </div>
                                    <div class="mt-0.5 text-xs text-zinc-500">
                                        {{ $version->activated_at ? 'veröffentlicht '.$version->activated_at->format('d.m.Y H:i') : 'angelegt '.$version->created_at?->format('d.m.Y H:i') }}
                                    </div>
                                    @if ($version->version_note)
                                        <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">{{ $version->version_note }}</p>
                                    @endif
                                    @if ($version->version_internal_note)
                                        <p class="mt-1 flex items-start gap-1 rounded-md bg-amber-50 px-2 py-1 text-xs whitespace-pre-line text-amber-900 dark:bg-amber-400/10 dark:text-amber-200">
                                            <flux:icon.lock-closed variant="micro" class="mt-px shrink-0" />
                                            <span><span class="font-medium">Intern:</span> {{ $version->version_internal_note }}</span>
                                        </p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </x-adminv2.card>
            @endif
        </aside>
    </div>


    {{-- Dialoge --}}
    <flux:modal name="publish" class="md:w-[32rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Ereignis veröffentlichen?</flux:heading>
                <flux:text class="mt-2">
                    Das Ereignis wird gespeichert und ab sofort auf Karte, in Feeds und über die API ausgeliefert.
                    @if ($activeOther)
                        Version {{ $activeOther->version }} wird dabei abgelöst und bleibt als Historie erhalten.
                    @endif
                </flux:text>
                <flux:text class="mt-2">
                    Der nächste Benachrichtigungslauf informiert Kunden mit passenden Regeln und betroffenen Reisen.
                </flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="publish" x-on:click="$dispatch('modal-close', { name: 'publish' })">Veröffentlichen</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="translate" class="md:w-[32rem]">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">Per DeepL übersetzen</flux:heading>
                <flux:text class="mt-2">
                    Speichert das Ereignis und übersetzt Titel und Beschreibung aus {{ CustomEvent::localeLabel($sourceLocale, false) }} in die übrigen Sprachen.
                </flux:text>
            </div>
            <flux:checkbox wire:model="overwriteTranslations" label="Bereits ausgefüllte Übersetzungen überschreiben" description="Ohne Haken werden nur leere Felder gefüllt." />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="translate" icon="language">Speichern & übersetzen</flux:button>
            </div>
        </div>
    </flux:modal>

    @if ($event)
        <flux:modal name="new-version" class="md:w-[32rem]">
            <div class="flex flex-col gap-5">
                <div>
                    <flux:heading size="lg">Neue Version anlegen</flux:heading>
                    <flux:text class="mt-2">
                        Es entsteht eine vollständige Kopie als Entwurf. Die aktuelle Version bleibt live, bis die neue veröffentlicht wird.
                        Nicht gespeicherte Änderungen auf dieser Seite werden nicht übernommen.
                    </flux:text>
                </div>
                <flux:textarea
                    wire:model="newVersionNote"
                    label="Änderungsnotiz"
                    rows="3"
                    placeholder="Was ändert sich gegenüber dieser Version?"
                    description="Erscheint in der Versionshistorie – auch für Kunden."
                />
                <flux:textarea
                    wire:model="newVersionInternalNote"
                    label="Interne Notiz"
                    rows="3"
                    placeholder="Hintergründe, Absprachen, offene Punkte …"
                    description="Nur im Admin-Bereich sichtbar – Kunden sehen sie nicht."
                />
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                    <flux:button variant="primary" wire:click="createVersion">Version anlegen</flux:button>
                </div>
            </div>
        </flux:modal>

        <flux:modal name="rule-check" class="md:w-[32rem]">
            <form wire:submit="openRuleCheck" class="flex flex-col gap-5">
                <div>
                    <flux:heading size="lg">Regeln der Kunden prüfen</flux:heading>
                    <flux:text class="mt-2">
                        Zeigt, welche Benachrichtigungsregeln für Global Travel Monitor und Travel Alert bei diesem Ereignis greifen würden.
                        Geprüft wird der gespeicherte Stand; versendet wird dabei nichts.
                    </flux:text>
                </div>

                <flux:radio.group wire:model.live="ruleCheckScope" label="Für wen?">
                    <flux:radio value="all" label="Alle Kunden" />
                    <flux:radio value="one" label="Ein bestimmter Kunde" />
                </flux:radio.group>

                @if ($ruleCheckScope === 'one')
                    <flux:input wire:model="ruleCheckCustomer" label="Kundennummer" placeholder="z. B. 21565" inputmode="numeric" autofocus />
                @endif

                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                    <flux:button type="submit" variant="primary">Regeln anzeigen</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="notify" class="md:w-[32rem]">
            <div class="flex flex-col gap-5">
                <div>
                    <flux:heading size="lg">Benachrichtigungen auslösen?</flux:heading>
                    <flux:text class="mt-2">
                        Travel-Alert- und GTM-Benachrichtigungen für dieses Ereignis werden erneut versendet – auch an Empfänger, die bereits benachrichtigt wurden.
                    </flux:text>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                    <flux:button variant="primary" wire:click="triggerNotifications">Jetzt senden</flux:button>
                </div>
            </div>
        </flux:modal>

        <flux:modal name="deactivate" class="md:w-[32rem]">
            <div class="flex flex-col gap-5">
                <div>
                    <flux:heading size="lg">Ereignis deaktivieren?</flux:heading>
                    <flux:text class="mt-2">Es wird sofort nicht mehr ausgeliefert. Der Inhalt bleibt erhalten und kann später wieder veröffentlicht werden.</flux:text>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                    <flux:button variant="danger" wire:click="deactivate">Deaktivieren</flux:button>
                </div>
            </div>
        </flux:modal>

        <flux:modal name="delete" class="md:w-[32rem]">
            <div class="flex flex-col gap-5">
                <div>
                    <flux:heading size="lg">Ereignis löschen?</flux:heading>
                    <flux:text class="mt-2">Das Ereignis wandert in den Papierkorb und wird nicht mehr ausgeliefert. Es lässt sich wiederherstellen.</flux:text>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                    <flux:button variant="danger" wire:click="delete">Löschen</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
