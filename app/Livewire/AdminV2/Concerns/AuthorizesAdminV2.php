<?php

namespace App\Livewire\AdminV2\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Prueft den Admin-Zugang bei JEDEM Aufruf der Komponente.
 *
 * Die Routen-Middleware greift nur beim Seitenaufruf – Livewire-Aktionen
 * laufen ueber /livewire/update und muessen den Zugang selbst absichern.
 */
trait AuthorizesAdminV2
{
    public function bootAuthorizesAdminV2(): void
    {
        $user = Auth::guard('web')->user();

        abort_unless($user && $user->is_admin && $user->is_active, 403);

        app()->setLocale(config('app.locale'));
    }
}
