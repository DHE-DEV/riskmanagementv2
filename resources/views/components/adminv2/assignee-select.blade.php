{{--
    Auswahl "Person oder Team" fuer Verantwortlich / Naechster Bearbeiter –
    als Flux-Dropdown mit den Gruppen "Personen" und "Teams".

    Der Wert ist die ID der Person bzw. "team:ID" (siehe AdminTask::assigneeValue()).
    Er wird erst mit der naechsten Anfrage (z. B. Speichern) an den Server gemeldet.

    - clearable: zusaetzlicher Eintrag ohne Auswahl (emptyLabel)
--}}
@props([
    'model',
    'label',
    'users' => [],
    'teams' => [],
    'emptyLabel' => 'Bitte wählen …',
    'clearable' => false,
    'description' => null,
])

@php
    $labels = ['' => $emptyLabel];

    foreach ($users as $user) {
        $labels[(string) $user->id] = trim($user->name);
    }
    foreach ($teams as $team) {
        $labels['team:'.$team->id] = 'Team '.$team->name;
    }

    $check = 'ms-auto flex shrink-0 ps-3 text-[var(--color-accent)]';
@endphp

<flux:field {{ $attributes }}>
    <flux:label>{{ $label }}</flux:label>

    <div x-data="{ value: $wire.entangle('{{ $model }}'), labels: @js($labels) }">
        <flux:dropdown class="block w-full">
            <flux:button icon:trailing="chevron-down" class="w-full !justify-between" aria-label="{{ $label }}">
                <span class="truncate font-normal" x-text="labels[value] ?? labels['']" :class="(value ?? '') === '' && 'text-zinc-500'"></span>
            </flux:button>

            <flux:menu class="max-h-80 min-w-64 overflow-y-auto">
                @if ($clearable)
                    <flux:menu.item x-on:click="value = ''">
                        {{ $emptyLabel }}
                        <span class="{{ $check }}" x-show="(value ?? '') === ''" x-cloak><flux:icon.check variant="micro" /></span>
                    </flux:menu.item>
                @endif

                <flux:menu.group heading="Personen">
                    @foreach ($users as $user)
                        <flux:menu.item wire:key="{{ $model }}-user-{{ $user->id }}" x-on:click="value = '{{ $user->id }}'">
                            {{ trim($user->name) }}
                            <span class="{{ $check }}" x-show="value === '{{ $user->id }}'" x-cloak><flux:icon.check variant="micro" /></span>
                        </flux:menu.item>
                    @endforeach
                </flux:menu.group>

                @if (count($teams) > 0)
                    <flux:menu.group heading="Teams">
                        @foreach ($teams as $team)
                            <flux:menu.item wire:key="{{ $model }}-team-{{ $team->id }}" icon="user-group" x-on:click="value = 'team:{{ $team->id }}'">
                                {{ $team->name }}
                                <span class="{{ $check }}" x-show="value === 'team:{{ $team->id }}'" x-cloak><flux:icon.check variant="micro" /></span>
                            </flux:menu.item>
                        @endforeach
                    </flux:menu.group>
                @endif
            </flux:menu>
        </flux:dropdown>
    </div>

    @if ($description)
        <flux:description>{{ $description }}</flux:description>
    @endif

    <flux:error name="{{ $model }}" />
</flux:field>
