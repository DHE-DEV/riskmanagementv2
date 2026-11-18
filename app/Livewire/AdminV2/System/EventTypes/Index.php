<?php

namespace App\Livewire\AdminV2\System\EventTypes;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\CustomEvent;
use App\Models\EventType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * System > Event-Typen: die Kategorien der Ereignisse mit ihren Namen je Sprache.
 * Gelöscht wird nicht – Ereignisse verweisen auf die Typen; stattdessen deaktivieren.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Event-Typen')]
class Index extends Component
{
    use AuthorizesAdminV2;

    #[Computed]
    public function types(): Collection
    {
        return EventType::query()->withCount('events')->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * Sprachen ohne Namen – außer der Ausgangssprache, die in der Spalte name steht.
     *
     * @return array<int, string>
     */
    public function missingLocales(EventType $type): array
    {
        return array_values(array_filter(
            CustomEvent::translationLocales(),
            fn (string $locale) => $locale !== CustomEvent::sourceLocale() && blank($type->name_translations[$locale] ?? null),
        ));
    }

    public function toggleActive(int $typeId): void
    {
        $type = EventType::findOrFail($typeId);
        $type->update(['is_active' => ! $type->is_active]);
        Cache::forget('gtm_all_events');

        unset($this->types);
        $this->dispatch('adminv2-toast', message: $type->is_active ? '„'.$type->name.'“ ist aktiv.' : '„'.$type->name.'“ ist inaktiv und lässt sich bei neuen Ereignissen nicht mehr wählen.');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.event-types.index');
    }
}
