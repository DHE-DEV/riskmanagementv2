{{--
    Rueckfrage "per DeepL uebersetzen" fuer einen Abschnitt des Laender-Editors
    (Methode translateTexts, Eigenschaft overwriteNoteTranslations).

    - section: Schluessel des Abschnitts (details, tipping, images)
    - what: was uebersetzt wird, z. B. "die Beschreibungen"
    - saved: die Uebersetzungen werden sofort gespeichert (Bilder)
--}}
@props([
    'section',
    'what',
    'saved' => false,
])

@php $sourceLocale = \App\Models\CustomEvent::sourceLocale(); @endphp

<flux:modal name="translate-{{ $section }}" class="md:w-[32rem]">
    <div class="flex flex-col gap-5">
        <div>
            <flux:heading size="lg">Per DeepL übersetzen</flux:heading>
            <flux:text class="mt-2">
                Übersetzt {{ $what }} aus {{ \App\Models\CustomEvent::localeLabel($sourceLocale, false) }} in die übrigen Sprachen.
                {{ $saved ? 'Die Übersetzungen werden sofort am Bild gespeichert.' : 'Gespeichert wird erst mit „Speichern“.' }}
            </flux:text>
        </div>
        <flux:checkbox wire:model="overwriteNoteTranslations" label="Bereits ausgefüllte Übersetzungen überschreiben" description="Ohne Haken werden nur leere Sprachen gefüllt." />
        <div class="flex justify-end gap-2">
            <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
            <flux:button variant="primary" wire:click="translateTexts('{{ $section }}')" icon="language">Übersetzen</flux:button>
        </div>
    </div>
</flux:modal>
