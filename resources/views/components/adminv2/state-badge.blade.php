@props(['state', 'size' => 'sm'])

<flux:tooltip :content="$state->description()">
    <flux:badge :color="$state->color()" :size="$size" inset="top bottom">{{ $state->label() }}</flux:badge>
</flux:tooltip>
