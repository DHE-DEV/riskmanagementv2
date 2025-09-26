<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6 flex gap-3">
            <x-filament::button type="submit" color="primary">
                Speichern
            </x-filament::button>

            <x-filament::button
                color="gray"
                tag="a"
                :href="\App\Filament\Resources\CustomEvents\CustomEventResource::getUrl('edit', ['record' => $record])">
                Abbrechen
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>