@php
    use App\Livewire\AdminV2\System\Templates\Index;
    use App\Models\NotificationTemplate;

    $template = $this->template;
    [$sourceLabel, $sourceColor] = Index::sourceLabel($template->source);
@endphp

<form wire:submit="save" class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('adminv2.system.templates.index') }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                <flux:icon.arrow-left variant="micro" /> Vorlagen
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <flux:heading size="xl" level="1" class="truncate">{{ $template->name }}</flux:heading>
                <flux:badge size="sm" :color="$sourceColor" inset="top bottom">{{ $sourceLabel }}</flux:badge>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" :href="route('adminv2.system.templates.index')">Abbrechen</flux:button>
            <flux:button type="submit" variant="primary" icon="check">Speichern</flux:button>
        </div>
    </div>

    @if ($errors->any())
        <flux:callout variant="danger" icon="exclamation-triangle" heading="Bitte die markierten Angaben prüfen." />
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <x-adminv2.card heading="Vorlage bearbeiten" description="Änderungen wirken sich auf alle Kunden aus, die die Standardvorlage verwenden.">
            <div class="flex flex-col gap-5">
                <div class="grid items-start gap-5 sm:grid-cols-2">
                    <flux:input wire:model="name" label="Name" maxlength="255" />
                    <flux:field>
                        <flux:label>Quelle</flux:label>
                        <flux:input :value="$sourceLabel" disabled />
                        <flux:description>Die Quelle einer Vorlage lässt sich nicht ändern.</flux:description>
                    </flux:field>
                </div>

                <flux:input wire:model="subject" label="Betreff" maxlength="255" description="Platzhalter wie {event_title} oder {country_name} können verwendet werden." />

                <flux:textarea wire:model="bodyHtml" label="E-Mail-Inhalt (HTML)" rows="25" class="font-mono text-xs" description="HTML-Code mit Platzhaltern." />
            </div>
        </x-adminv2.card>

        <x-adminv2.card heading="Verfügbare Platzhalter" description="Ein Klick kopiert den Platzhalter." collapsible collapse-key="template-placeholders">
            <dl class="flex flex-col gap-2.5 text-sm">
                @foreach (NotificationTemplate::PLACEHOLDERS as $placeholder => $meaning)
                    <div wire:key="placeholder-{{ trim($placeholder, '{}') }}" class="flex flex-col gap-0.5">
                        <dt><x-adminv2.placeholder :name="trim($placeholder, '{}')" :label="$meaning" /></dt>
                        <dd class="text-zinc-600 dark:text-zinc-400">{{ $meaning }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-adminv2.card>
    </div>
</form>
