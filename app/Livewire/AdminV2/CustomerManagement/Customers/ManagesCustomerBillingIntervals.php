<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use App\Models\CustomerBillingInterval;
use App\Support\TravelInformationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/**
 * Tab „Abrechnung“: Abrechnungsintervall (monatlich, halbjaehrlich, jaehrlich)
 * je Zeitraum – fuer alle abonnierten Dienste oder einzelne Module. Zeitraeume
 * desselben Moduls ueberschneiden sich nicht; beim Umstellen laesst sich der
 * laufende offene Zeitraum zum Vortag beenden.
 */
trait ManagesCustomerBillingIntervals
{
    public bool $showIntervalForm = false;

    /** @var array{id: ?int, module: string, interval: string, starts_on: string, ends_on: string, notes: string, close_previous: bool} */
    public array $intervalForm = [];

    #[Computed]
    public function billingIntervals(): Collection
    {
        return $this->customer->billingIntervals()->get();
    }

    public function newBillingInterval(?string $module = null): void
    {
        $this->resetErrorBag();
        $this->intervalForm = [
            'id' => null,
            'module' => (string) $module,
            'interval' => 'yearly',
            'starts_on' => today()->startOfMonth()->addMonth()->toDateString(),
            'ends_on' => '',
            'notes' => '',
            'close_previous' => true,
        ];
        $this->showIntervalForm = true;
    }

    public function editBillingInterval(int $intervalId): void
    {
        $interval = $this->customer->billingIntervals()->findOrFail($intervalId);

        $this->resetErrorBag();
        $this->intervalForm = [
            'id' => $interval->id,
            'module' => (string) $interval->module,
            'interval' => $interval->interval,
            'starts_on' => $interval->starts_on->toDateString(),
            'ends_on' => $interval->ends_on?->toDateString() ?? '',
            'notes' => (string) $interval->notes,
            'close_previous' => false,
        ];
        $this->showIntervalForm = true;
    }

    public function saveBillingInterval(): void
    {
        $this->validate([
            'intervalForm.module' => ['nullable', Rule::in(['', ...array_keys(TravelInformationService::MODULES)])],
            'intervalForm.interval' => ['required', Rule::in(array_keys(CustomerBillingInterval::INTERVALS))],
            'intervalForm.starts_on' => ['required', 'date'],
            'intervalForm.ends_on' => ['nullable', 'date', 'after_or_equal:intervalForm.starts_on'],
            'intervalForm.notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'intervalForm.starts_on.required' => 'Bitte ein Startdatum angeben.',
            'intervalForm.ends_on.after_or_equal' => 'Das Enddatum liegt vor dem Startdatum.',
        ]);

        $form = $this->intervalForm;
        $module = $form['module'] !== '' ? $form['module'] : null;
        $startsOn = Carbon::parse($form['starts_on'])->toDateString();
        $endsOn = $form['ends_on'] !== '' ? Carbon::parse($form['ends_on'])->toDateString() : null;

        // Umstellen: laufenden offenen Zeitraum desselben Moduls zum Vortag beenden.
        $open = $form['id'] || ! ($form['close_previous'] ?? false) ? null : $this->customer->billingIntervals()
            ->when($module === null, fn ($q) => $q->whereNull('module'), fn ($q) => $q->where('module', $module))
            ->whereNull('ends_on')->whereDate('starts_on', '<', $startsOn)->first();

        $ignore = array_filter([$form['id'], $open?->id]);
        if ($conflict = CustomerBillingInterval::overlaps($this->customer->id, $module, $startsOn, $endsOn, $ignore)) {
            $this->addError('intervalForm.starts_on', 'Überschneidet sich mit '.$conflict->intervalLabel().' '.$conflict->rangeLabel().' ('.$conflict->moduleLabel().').');

            return;
        }

        if ($open) {
            $open->update(['ends_on' => Carbon::parse($startsOn)->subDay()->toDateString()]);
        }

        $interval = $form['id'] ? $this->customer->billingIntervals()->findOrFail($form['id']) : new CustomerBillingInterval(['customer_id' => $this->customer->id]);
        $interval->fill([
            'module' => $module,
            'interval' => $form['interval'],
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'notes' => trim((string) $form['notes']) ?: null,
        ])->save();

        $this->showIntervalForm = false;
        unset($this->billingIntervals);
        $this->dispatch('adminv2-toast', message: $interval->moduleLabel().': '.$interval->intervalLabel().' ab '.$interval->starts_on->format('d.m.Y').' gespeichert.'.($open ? ' Der vorherige Zeitraum endet am '.$open->ends_on->format('d.m.Y').'.' : ''));
    }

    public function deleteBillingInterval(int $intervalId): void
    {
        $interval = $this->customer->billingIntervals()->findOrFail($intervalId);
        $interval->delete();

        unset($this->billingIntervals);
        $this->dispatch('adminv2-toast', message: $interval->moduleLabel().': Zeitraum '.$interval->rangeLabel().' gelöscht.');
    }
}
