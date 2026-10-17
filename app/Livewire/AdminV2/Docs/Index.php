<?php

namespace App\Livewire\AdminV2\Docs;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Support\AdminV2\Docs;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Doku: Uebersicht der internen Anleitungen, nach Bereich gruppiert.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Doku')]
class Index extends Component
{
    use AuthorizesAdminV2;

    public function render()
    {
        return view('livewire.admin-v2.docs.index', [
            'groups' => collect(Docs::DOCS)->map(fn (array $doc, string $key) => $doc + ['key' => $key])->groupBy('category'),
        ]);
    }
}
