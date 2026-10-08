@php
    use App\Models\CustomEvent;

    $app = $this->app;
    $locales = CustomEvent::translationLocales();
    $sourceLocale = CustomEvent::sourceLocale();
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('adminv2.system.taxi-apps.index') }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Taxi Apps
            </a>
            <flux:heading size="xl" level="1" class="mt-1">{{ $app ? $app->name : 'Neue Taxi App' }}</flux:heading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" :href="route('adminv2.system.taxi-apps.index')">Abbrechen</flux:button>
            <flux:button type="submit" variant="primary" icon="check">Speichern</flux:button>
        </div>
    </div>

    @if ($errors->any())
        <flux:callout variant="danger" icon="exclamation-triangle" heading="Bitte die markierten Angaben prüfen." />
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="flex flex-col gap-6">
            <x-adminv2.card heading="Anbieter" description="Name und Beschreibung je Sprache – so erscheint die App bei den Ländern und in Apps.">
                <div x-data="{ locale: @js($sourceLocale) }" class="flex flex-col gap-5">
                    <div class="grid items-start gap-5 sm:grid-cols-[minmax(0,1fr)_8rem]">
                        <flux:input wire:model="name" label="Name" placeholder="z. B. Uber" maxlength="255" />
                        <flux:input wire:model="sortOrder" label="Sortierung" inputmode="numeric" description="Kleine Zahl zuerst." />
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Sprache der Beschreibung</span>
                        <div class="inline-flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                            @foreach ($locales as $locale)
                                <button type="button" x-on:click="locale = @js($locale)" class="rounded-md px-3 py-1 text-sm font-medium transition" :class="locale === @js($locale) ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-950 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'">
                                    {{ CustomEvent::localeLabel($locale) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @foreach ($locales as $locale)
                        <div x-show="locale === @js($locale)" @if ($locale !== $sourceLocale) x-cloak @endif wire:key="description-{{ $locale }}">
                            <flux:textarea wire:model="descriptions.{{ $locale }}" label="Beschreibung ({{ strtoupper($locale) }})" rows="4" placeholder="Wofür die App gut ist, Besonderheiten, Zahlungsarten …" />
                            <flux:error name="descriptions.{{ $locale }}" />
                        </div>
                    @endforeach

                    <flux:switch wire:model="isActive" label="Aktiv" description="Nur aktive Apps lassen sich bei Ländern auswählen." align="left" />
                </div>
            </x-adminv2.card>

            <x-adminv2.card heading="Logo und Links" description="Das Logo wird verlinkt, nicht hochgeladen – am besten ein quadratisches PNG oder SVG.">
                <div class="flex flex-col gap-5">
                    <div class="flex items-start gap-4">
                        <x-adminv2.taxi-app-logo :url="$logoUrl" :name="$name" class="size-16 shrink-0" />
                        <div class="min-w-0 flex-1">
                            <flux:input wire:model.live.debounce.500ms="logoUrl" label="Logo (URL)" placeholder="https://…/logo.png" type="url" />
                            <flux:error name="logoUrl" />
                        </div>
                    </div>
                    <flux:input wire:model="websiteUrl" label="Website" placeholder="https://www.uber.com" type="url" />
                    <flux:error name="websiteUrl" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <flux:input wire:model="appStoreUrl" label="App Store (iOS)" placeholder="https://apps.apple.com/…" type="url" />
                            <flux:error name="appStoreUrl" />
                        </div>
                        <div>
                            <flux:input wire:model="playStoreUrl" label="Google Play (Android)" placeholder="https://play.google.com/store/apps/…" type="url" />
                            <flux:error name="playStoreUrl" />
                        </div>
                    </div>
                </div>
            </x-adminv2.card>
        </div>

        <div class="flex flex-col gap-6">
            @if ($app)
                <x-adminv2.card heading="Länder" :description="$app->countries()->count().' '.($app->countries()->count() === 1 ? 'Land nutzt' : 'Länder nutzen').' diese App.'">
                    @php $countries = $app->countries()->orderByRaw(\App\Support\AdminV2\MasterData::nameSql('countries'))->get(); @endphp
                    @if ($countries->isEmpty())
                        <p class="text-sm text-zinc-500">Noch keinem Land zugeordnet. Die Zuordnung geschieht im Länder-Editor unter „Taxi-Apps“.</p>
                    @else
                        <ul class="flex flex-col divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                            @foreach ($countries as $country)
                                <li class="flex items-center justify-between gap-3 py-1.5">
                                    <a href="{{ route('adminv2.master-data.countries.edit', $country) }}" class="truncate text-zinc-900 hover:underline dark:text-white">{{ $country->getName('de') }}</a>
                                    <span class="shrink-0 font-mono text-xs text-zinc-400">{{ $country->iso_code }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-adminv2.card>
            @else
                <x-adminv2.card heading="Nach dem Speichern">
                    <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Sobald die App angelegt ist, lässt sie sich im Länder-Editor unter „Taxi-Apps“ den Ländern zuordnen.</p>
                </x-adminv2.card>
            @endif
        </div>
    </div>
</form>
