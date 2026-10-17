<?php

namespace App\Livewire\AdminV2\Docs;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Support\AdminV2\Docs;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Doku: eine Anleitung mit Inhaltsverzeichnis.
 */
#[Layout('components.layouts.adminv2.app')]
class Show extends Component
{
    use AuthorizesAdminV2;

    public string $doc;

    public function mount(string $doc): void
    {
        abort_unless(Docs::find($doc), 404);
        $this->doc = $doc;
    }

    public function render()
    {
        $meta = Docs::find($this->doc);

        return view('livewire.admin-v2.docs.show', [
            'meta' => $meta,
            'rendered' => app(Docs::class)->renderDoc($this->doc),
        ])->title($meta['title'].' · Doku');
    }
}
