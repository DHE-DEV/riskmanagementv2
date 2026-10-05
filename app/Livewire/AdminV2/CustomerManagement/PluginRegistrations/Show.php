<?php

namespace App\Livewire\AdminV2\CustomerManagement\PluginRegistrations;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\PluginEmailVerification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Eine Plugin-Registrierung, deren E-Mail-Adresse noch bestaetigt werden
 * muss – zum Nachsehen. Aendern laesst sich nichts, nur loeschen.
 */
#[Layout('components.layouts.adminv2.app')]
class Show extends Component
{
    use AuthorizesAdminV2;

    #[Locked]
    public int $registrationId;

    public function mount(int $registration): void
    {
        $this->registrationId = PluginEmailVerification::findOrFail($registration)->id;
    }

    #[Computed]
    public function registration(): PluginEmailVerification
    {
        return PluginEmailVerification::findOrFail($this->registrationId);
    }

    public function delete()
    {
        $this->registration->delete();

        session()->flash('adminv2-toast', 'Eintrag wurde gelöscht.');

        return $this->redirectRoute('adminv2.customer-management.plugin-registrations.index');
    }

    public function render()
    {
        return view('livewire.admin-v2.customer-management.plugin-registrations.show')
            ->title('Registrierung '.$this->registration->email);
    }
}
