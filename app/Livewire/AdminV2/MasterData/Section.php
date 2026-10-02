<?php

namespace App\Livewire\AdminV2\MasterData;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Support\AdminV2\MasterData;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Seite eines Stammdaten-Bereichs. Die Bereiche sind noch nicht umgezogen –
 * bis dahin zeigt jede Seite einen Hinweis und den Weg in den bisherigen Admin.
 */
#[Layout('components.layouts.adminv2.app')]
class Section extends Component
{
    use AuthorizesAdminV2;

    #[Locked]
    public string $section;

    public function mount(string $section): void
    {
        abort_unless(in_array($section, MasterData::placeholderKeys(), true), 404);

        $this->section = $section;
    }

    public function render()
    {
        $definition = MasterData::sections()[$this->section];

        return view('livewire.admin-v2.master-data.section', ['definition' => $definition])
            ->title($definition['label']);
    }
}
