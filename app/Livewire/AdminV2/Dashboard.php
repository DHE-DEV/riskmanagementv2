<?php

namespace App\Livewire\AdminV2;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Startseite des Admin-Bereichs: Begruessung und Einstieg in die Bereiche.
 * Die Kennzahlen der Ereignisse liegen unter Ereignisse > Uebersicht.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Willkommen')]
class Dashboard extends Component
{
    use AuthorizesAdminV2;

    public function render()
    {
        return view('livewire.admin-v2.dashboard');
    }
}
