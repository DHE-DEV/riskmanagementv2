@php
    use App\Models\CustomEvent;

    $type = $this->type;
    $sourceLocale = CustomEvent::sourceLocale();
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('adminv2.system.event-types.index') }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Event-Typen
            </a>
            <flux:heading size="xl" level="1" class="mt-1">{{ $type ? $type->name : 'Neuer Event-Typ' }}</flux:heading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" :href="route('adminv2.system.event-types.index')">Abbrechen</flux:button>
            <flux:button type="submit" variant="primary" icon="check">Speichern</flux:button>
        </div>
    </div>

    @if ($errors->any())
        <flux:callout variant="danger" icon="exclamation-triangle" heading="Bitte die markierten Angaben prüfen." />
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Name je Sprache" description="So heißt der Typ in API, Feeds und Filtern. Fehlt eine Sprache, erscheint der deutsche Name.">
                <x-slot:actions>
                    <flux:button size="sm" variant="ghost" icon="language" wire:click="translateMissing" wire:loading.attr="disabled" wire:target="translateMissing">Fehlende per DeepL übersetzen</flux:button>
                </x-slot:actions>
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach ($this->names as $locale => $name)
                        <div wire:key="name-{{ $locale }}">
                            <flux:input
                                wire:model="names.{{ $locale }}"
                                :label="CustomEvent::localeLabel($locale).($locale === $sourceLocale ? ' (Ausgangssprache)' : '')"
                                maxlength="255"
                                :placeholder="$locale === $sourceLocale ? 'z. B. Streik' : 'leer = deutscher Name'"
                            />
                            <flux:error name="names.{{ $locale }}" />
                        </div>
                    @endforeach
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Typ">
                <div class="flex flex-col gap-5">
                    <div class="grid items-start gap-5 sm:grid-cols-[minmax(0,1fr)_8rem]">
                        <flux:field>
                            <flux:label>Code</flux:label>
                            <flux:description>Kennung für Filter und API, z. B. <span class="font-mono">strike</span>. Nach dem Anlegen möglichst nicht mehr ändern.</flux:description>
                            <flux:input wire:model="code" maxlength="50" class="font-mono" />
                            <flux:error name="code" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Sortierung</flux:label>
                            <flux:description>Kleine Zahl zuerst.</flux:description>
                            <flux:input wire:model="sortOrder" inputmode="numeric" />
                            <flux:error name="sortOrder" />
                        </flux:field>
                    </div>

                    <flux:textarea wire:model="description" label="Beschreibung (intern)" rows="3" maxlength="1000" placeholder="Welche Ereignisse unter diesen Typ fallen …" />

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Farbe</flux:label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model.live="color" class="h-9 w-12 cursor-pointer rounded-md border border-zinc-200 bg-white p-1 dark:border-zinc-700 dark:bg-zinc-900" aria-label="Farbe wählen" />
                                <flux:input wire:model.live="color" maxlength="7" class="font-mono" />
                            </div>
                            <flux:error name="color" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Icon</flux:label>
                            <flux:description>Font-Awesome-Klasse, z. B. <span class="font-mono">fa-shield-alt</span>.</flux:description>
                            <flux:input wire:model="icon" maxlength="100" class="font-mono" />
                            <flux:error name="icon" />
                        </flux:field>
                    </div>

                    <flux:switch wire:model="isActive" label="Aktiv" description="Nur aktive Typen lassen sich bei Ereignissen auswählen." align="left" />
                </div>
            </x-adminv2.card>
        </div>

        <div class="flex flex-col gap-6">
            @if ($type)
                <x-adminv2.card heading="Verwendung">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        {{ $type->events_count }} {{ $type->events_count === 1 ? 'Ereignis ist' : 'Ereignisse sind' }} diesem Typ zugeordnet.
                        Statt zu löschen lässt sich ein Typ deaktivieren.
                    </p>
                </x-adminv2.card>
            @endif
            <x-adminv2.card heading="Weitere Sprachen">
                <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    Die Sprachen kommen aus <span class="font-mono">EVENT_LANGUAGES</span>. Kommt eine hinzu, erscheint hier ein weiteres Feld;
                    <span class="font-mono">php artisan names:translate</span> füllt die fehlenden Namen aller Typen auf einmal.
                </p>
            </x-adminv2.card>
        </div>
    </div>
</form>
