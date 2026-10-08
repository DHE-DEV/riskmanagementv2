<?php

namespace App\Livewire\AdminV2\System\TaxiApps;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\TaxiApp;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * System > Taxi Apps: die Anbieter, die sich Laendern zuordnen lassen – als
 * Karten mit Logo, Name, Beschreibung und Links.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Taxi Apps')]
class Index extends Component
{
    use AuthorizesAdminV2;

    #[Computed]
    public function apps(): Collection
    {
        return TaxiApp::query()->withCount('countries')->ordered()->get();
    }

    public function toggleActive(int $appId): void
    {
        $app = TaxiApp::findOrFail($appId);
        $app->update(['is_active' => ! $app->is_active]);

        unset($this->apps);
        $this->dispatch('adminv2-toast', message: $app->is_active ? '„'.$app->name.'“ ist aktiv.' : '„'.$app->name.'“ ist inaktiv und wird bei Ländern nicht mehr angeboten.');
    }

    public function delete(int $appId): void
    {
        $app = TaxiApp::findOrFail($appId);
        $app->delete();

        unset($this->apps);
        $this->dispatch('adminv2-toast', message: '„'.$app->name.'“ gelöscht.');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.taxi-apps.index');
    }
}
