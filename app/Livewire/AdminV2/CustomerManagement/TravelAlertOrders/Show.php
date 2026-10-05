<?php

namespace App\Livewire\AdminV2\CustomerManagement\TravelAlertOrders;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\CustomerManagement\TravelAlertOrders\Concerns\DecidesTravelAlertOrders;
use App\Models\TravelAlertOrder;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Eine TravelAlert-Bestellung: Angaben des Kunden zum Nachsehen, dazu
 * freischalten, ablehnen, Ablauf der Testversion setzen und loeschen.
 */
#[Layout('components.layouts.adminv2.app')]
class Show extends Component
{
    use AuthorizesAdminV2, DecidesTravelAlertOrders;

    #[Locked]
    public int $orderId;

    /** Ablaufdatum der Testversion im Dialog (Y-m-d, leer = keines). */
    public string $trialExpiresAt = '';

    public function mount(int $order): void
    {
        $this->orderId = TravelAlertOrder::findOrFail($order)->id;
    }

    #[Computed]
    public function order(): TravelAlertOrder
    {
        return TravelAlertOrder::query()->with('customer')->findOrFail($this->orderId);
    }

    /**
     * Die Mitarbeiter, die freigeschaltet bzw. abgelehnt haben (ID => Name).
     */
    #[Computed]
    public function deciders(): Collection
    {
        $ids = array_filter([$this->order->approved_by, $this->order->rejected_by]);

        return $ids === [] ? collect() : User::query()->whereIn('id', $ids)->pluck('name', 'id');
    }

    public function approve(): void
    {
        $this->approveOrder($this->order);

        unset($this->order, $this->deciders);
    }

    public function reject(): void
    {
        $this->rejectOrder($this->order);

        unset($this->order, $this->deciders);
    }

    public function openTrialExpiry(): void
    {
        $this->resetValidation();
        $this->trialExpiresAt = $this->order->trial_expires_at?->format('Y-m-d') ?? '';

        $this->modal('trial-expiry')->show();
    }

    /**
     * Ablaufdatum der Testversion speichern; ein leeres Feld entfernt es.
     */
    public function saveTrialExpiry(): void
    {
        $this->validate(
            ['trialExpiresAt' => ['nullable', 'date_format:Y-m-d']],
            ['trialExpiresAt.date_format' => 'Bitte ein gültiges Datum angeben.'],
        );

        $this->order->update(['trial_expires_at' => $this->trialExpiresAt !== '' ? $this->trialExpiresAt : null]);

        unset($this->order);

        $this->modal('trial-expiry')->close();
        $this->dispatch('adminv2-toast', message: $this->trialExpiresAt !== '' ? 'Ablaufdatum der Testversion gespeichert.' : 'Ablaufdatum der Testversion entfernt.');
    }

    public function delete(): void
    {
        $this->order->delete();

        session()->flash('adminv2-toast', 'Bestellung gelöscht.');

        $this->redirectRoute('adminv2.customer-management.travel-alert-orders.index');
    }

    public function render()
    {
        return view('livewire.admin-v2.customer-management.travel-alert-orders.show')
            ->title('Bestellung '.$this->order->id);
    }
}
