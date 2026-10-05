<?php

namespace App\Livewire\AdminV2\CustomerManagement\TravelAlertOrders\Concerns;

use App\Models\TravelAlertOrder;
use App\Services\TravelAlertOrderService;
use Illuminate\Support\Facades\Auth;

/**
 * Entscheidung ueber eine TravelAlert-Bestellung – gleich in Liste und
 * Einzelansicht. Die Bedingungen werden hier noch einmal geprueft: die
 * Schaltflaechen sind zwar ausgeblendet, der Aufruf kaeme aber trotzdem an.
 */
trait DecidesTravelAlertOrders
{
    /**
     * Zugang freischalten; der Kunde bekommt die Mail, dass er bereitsteht.
     */
    protected function approveOrder(TravelAlertOrder $order): bool
    {
        if ($order->isApproved() || $order->isRejected()) {
            $this->dispatch('adminv2-toast', message: 'Über diese Bestellung ist bereits entschieden.', variant: 'danger');

            return false;
        }

        if (! $order->isConfirmed()) {
            $this->dispatch('adminv2-toast', message: 'Der Kunde hat die Bestellung noch nicht bestätigt.', variant: 'danger');

            return false;
        }

        app(TravelAlertOrderService::class)->approve($order, Auth::id());

        // Ohne Kundenkonto (z. B. geloeschter Kunde) schaltet der Dienst nichts frei und verschickt keine Mail.
        if ($order->customer) {
            $this->dispatch('adminv2-toast', message: 'Travel Alert freigeschaltet. Der Kunde wurde per E-Mail informiert.');
        } else {
            $this->dispatch('adminv2-toast', message: 'Bestellung freigegeben – zu ihr gehört aber kein Kundenkonto mehr: Es wurde nichts freigeschaltet und keine E-Mail verschickt.', variant: 'danger');
        }

        return true;
    }

    /**
     * Ablehnen – auch nach einer Freischaltung moeglich. Der Kunde wird nicht benachrichtigt.
     */
    protected function rejectOrder(TravelAlertOrder $order): bool
    {
        if ($order->isRejected()) {
            $this->dispatch('adminv2-toast', message: 'Die Bestellung ist bereits abgelehnt.', variant: 'danger');

            return false;
        }

        app(TravelAlertOrderService::class)->reject($order, Auth::id());

        $this->dispatch('adminv2-toast', message: 'Bestellung abgelehnt. Der Kunde wurde nicht benachrichtigt.');

        return true;
    }
}
