<?php

namespace App\Livewire\AdminV2\System\Templates;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\NotificationTemplate;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * System > Vorlagen: die Standard-Vorlagen der Benachrichtigungs-E-Mails je
 * Quelle (Travel Alert, Global Travel Monitor). Sie gelten fuer alle Kunden,
 * die keine eigene Vorlage hinterlegt haben.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Vorlagen')]
class Index extends Component
{
    use AuthorizesAdminV2;

    /** Quelle => [Bezeichnung, Farbe der Markierung] */
    public const SOURCES = [
        'travel-alert' => ['Travel Alert', 'amber'],
        'global-travel-monitor' => ['Global Travel Monitor', 'sky'],
    ];

    /**
     * @return array{0: string, 1: string}
     */
    public static function sourceLabel(?string $source): array
    {
        return self::SOURCES[$source] ?? [$source ?: 'Ohne Quelle', 'zinc'];
    }

    #[Computed]
    public function templates(): Collection
    {
        return NotificationTemplate::query()
            ->where('is_system', true)
            ->withCount('notificationRules')
            ->orderBy('source')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.admin-v2.system.templates.index');
    }
}
