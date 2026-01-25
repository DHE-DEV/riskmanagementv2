<?php

namespace App\Livewire\Customer;

use Livewire\Component;

class TravelersAutoRefreshSettings extends Component
{
    public bool $autoRefresh = false;

    public int $refreshInterval = 30;

    public function mount(): void
    {
        $customer = auth('customer')->user();

        if ($customer) {
            $this->autoRefresh = (bool) $customer->auto_refresh_travelers;
            $this->refreshInterval = (int) $customer->travelers_refresh_interval;
        }
    }

    public function save(): void
    {
        $this->validate([
            'autoRefresh' => 'boolean',
            'refreshInterval' => 'required|integer|min:10|max:300',
        ]);

        $customer = auth('customer')->user();

        if ($customer) {
            $customer->update([
                'auto_refresh_travelers' => $this->autoRefresh,
                'travelers_refresh_interval' => $this->refreshInterval,
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Einstellungen erfolgreich gespeichert.',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.customer.travelers-auto-refresh-settings');
    }
}
