{{--
    Auswahl "Suche ausfuehren": startet eine der hinterlegten KI-Suchen ueber
    runAiProfile() der umgebenden Livewire-Komponente.
--}}
@props(['profiles', 'running' => false, 'size' => 'base', 'variant' => 'primary'])

@if ($profiles->isEmpty())
    <flux:button :size="$size" :variant="$variant" icon="plus" :href="route('adminv2.system.ai.searches.create')">Suche hinterlegen</flux:button>
@else
    <flux:dropdown align="end">
        <flux:button :size="$size" :variant="$variant" icon="sparkles" icon:trailing="chevron-down" :disabled="$running">Suche ausführen</flux:button>

        <flux:menu class="max-h-80 min-w-64 overflow-y-auto">
            <flux:menu.group heading="Hinterlegte Suchen">
                @foreach ($profiles as $profile)
                    <flux:menu.item wire:key="run-profile-{{ $profile->id }}" icon="bolt" wire:click="runAiProfile({{ $profile->id }})">
                        {{ $profile->name }}{{ $profile->is_active ? '' : ' (pausiert)' }}
                    </flux:menu.item>
                @endforeach
            </flux:menu.group>
            <flux:menu.item icon="cog-6-tooth" :href="route('adminv2.system.ai')">Suchen verwalten</flux:menu.item>
        </flux:menu>
    </flux:dropdown>
@endif
