{{--
    Datum mit Uhrzeit ueber einen grossen Kalender (flatpickr) statt des
    kleinen Browser-Kalenders. Der Wert liegt in der Livewire-Eigenschaft im
    Format "Y-m-d\TH:i" – wie bei einem datetime-local-Feld – und wird erst
    mit der naechsten Anfrage uebertragen (kein Live-Binding).

    Braucht window.adminv2DateTimePicker (Ereignis-Editor, @assets).
--}}
@props([
    'property',
    'label',
    'description' => null,
])

<div x-data="adminv2DateTimePicker(@js($property))">
    <flux:field>
        <flux:label>{{ $label }}</flux:label>
        <flux:input
            x-ref="input"
            type="text"
            icon="calendar"
            placeholder="TT.MM.JJJJ HH:MM"
            autocomplete="off"
            :invalid="$errors->has($property)"
            {{ $attributes }}
        />
        @if ($description)
            <flux:description>{{ $description }}</flux:description>
        @endif
        <flux:error :name="$property" />
    </flux:field>
</div>
