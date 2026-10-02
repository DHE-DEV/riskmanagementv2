{{-- Papierkorb-Filter der Stammdaten-Listen (Eigenschaft "trashed"). --}}
<flux:select wire:model.live="trashed" aria-label="Papierkorb" {{ $attributes }}>
    <flux:select.option value="">Ohne Papierkorb</flux:select.option>
    <flux:select.option value="with">Mit Papierkorb</flux:select.option>
    <flux:select.option value="only">Nur Papierkorb</flux:select.option>
</flux:select>
