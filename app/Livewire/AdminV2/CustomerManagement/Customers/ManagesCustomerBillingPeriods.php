<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use App\Models\Cooperation;
use App\Models\CustomerBillingPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/**
 * Tab „Abrechnung“: Zeitraeume, in denen der Kunde selbststaendig oder ueber
 * eine Kooperation abgerechnet wird (das Intervall steht in
 * ManagesCustomerBillingIntervals). Die Zeitraeume duerfen sich nicht
 * ueberschneiden; ein offener Zeitraum laesst sich beim Wechsel automatisch
 * zum Vortag beenden.
 */
trait ManagesCustomerBillingPeriods
{
    public bool $showBillingForm = false;

    /** @var array{id: ?int, cooperation_id: string, billing_mode: string, starts_on: string, ends_on: string, notes: string, close_previous: bool} */
    public array $billingForm = [];

    #[Computed]
    public function billingPeriods(): Collection
    {
        return $this->customer->billingPeriods()->with('cooperation')->get();
    }

    #[Computed]
    public function cooperationOptions(): Collection
    {
        return Cooperation::query()->ordered()->get(['id', 'name', 'member_billing', 'fixed_price_per_office', 'fixed_price_mode', 'is_active']);
    }

    /** Laufender offener Zeitraum, der beim Wechsel beendet werden kann. */
    #[Computed]
    public function openBillingPeriod(): ?CustomerBillingPeriod
    {
        return $this->billingPeriods->first(fn ($period) => $period->ends_on === null && $period->id !== ($this->billingForm['id'] ?? null));
    }

    public function newBillingPeriod(): void
    {
        $this->resetErrorBag();
        $this->billingForm = [
            'id' => null,
            'cooperation_id' => '',
            'billing_mode' => 'self_payer',
            'starts_on' => today()->startOfMonth()->addMonth()->toDateString(),
            'ends_on' => '',
            'notes' => '',
            'close_previous' => true,
        ];
        $this->showBillingForm = true;
    }

    public function editBillingPeriod(int $periodId): void
    {
        $period = $this->customer->billingPeriods()->findOrFail($periodId);

        $this->resetErrorBag();
        $this->billingForm = [
            'id' => $period->id,
            'cooperation_id' => (string) ($period->cooperation_id ?? ''),
            'billing_mode' => $period->billing_mode,
            'starts_on' => $period->starts_on->toDateString(),
            'ends_on' => $period->ends_on?->toDateString() ?? '',
            'notes' => (string) $period->notes,
            'close_previous' => false,
        ];
        $this->showBillingForm = true;
    }

    /** Neue Kooperation gewaehlt: Abrechnung so, wie bei ihr eingestellt. */
    public function updatedBillingFormCooperationId(string $value): void
    {
        $cooperation = $value !== '' ? Cooperation::find((int) $value) : null;

        $this->billingForm['billing_mode'] = $cooperation?->member_billing ?? 'self_payer';
    }

    public function saveBillingPeriod(): void
    {
        $this->validate([
            'billingForm.cooperation_id' => ['nullable', Rule::exists('cooperations', 'id')],
            'billingForm.billing_mode' => ['required', Rule::in(array_keys(CustomerBillingPeriod::BILLING_MODES))],
            'billingForm.starts_on' => ['required', 'date'],
            'billingForm.ends_on' => ['nullable', 'date', 'after_or_equal:billingForm.starts_on'],
            'billingForm.notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'billingForm.starts_on.required' => 'Bitte ein Startdatum angeben.',
            'billingForm.ends_on.after_or_equal' => 'Das Enddatum liegt vor dem Startdatum.',
        ]);

        $form = $this->billingForm;
        $startsOn = Carbon::parse($form['starts_on'])->toDateString();
        $endsOn = $form['ends_on'] !== '' ? Carbon::parse($form['ends_on'])->toDateString() : null;
        $cooperationId = $form['cooperation_id'] !== '' ? (int) $form['cooperation_id'] : null;

        // Wechsel: den laufenden offenen Zeitraum zum Vortag beenden, wenn er vorher begann.
        $open = $this->openBillingPeriod;
        $closePrevious = ! $form['id'] && ($form['close_previous'] ?? false) && $open && $open->starts_on->toDateString() < $startsOn;
        $ignore = $closePrevious ? [$open->id] : [];

        $conflict = CustomerBillingPeriod::overlaps($this->customer->id, $startsOn, $endsOn, $form['id']);
        if ($conflict && ! in_array($conflict->id, $ignore, true)) {
            $this->addError('billingForm.starts_on', 'Überschneidet sich mit dem Zeitraum '.$conflict->rangeLabel().' ('.$conflict->label().').');

            return;
        }
        if ($closePrevious && ($second = CustomerBillingPeriod::query()->where('customer_id', $this->customer->id)->whereKeyNot($open->id)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $startsOn))
            ->when($endsOn, fn ($q) => $q->whereDate('starts_on', '<=', $endsOn))->first())) {
            $this->addError('billingForm.starts_on', 'Überschneidet sich mit dem Zeitraum '.$second->rangeLabel().' ('.$second->label().').');

            return;
        }

        if ($closePrevious) {
            $open->update(['ends_on' => Carbon::parse($startsOn)->subDay()->toDateString()]);
        }

        $period = $form['id'] ? $this->customer->billingPeriods()->findOrFail($form['id']) : new CustomerBillingPeriod(['customer_id' => $this->customer->id]);
        $period->fill([
            'cooperation_id' => $cooperationId,
            // Ohne Kooperation immer Selbstzahler
            'billing_mode' => $cooperationId ? $form['billing_mode'] : 'self_payer',
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'notes' => trim((string) $form['notes']) ?: null,
        ])->save();

        $this->showBillingForm = false;
        unset($this->billingPeriods, $this->openBillingPeriod);
        $this->dispatch('adminv2-toast', message: 'Abrechnungszeitraum '.$period->rangeLabel().' gespeichert.'.($closePrevious ? ' Der vorherige Zeitraum endet am '.$open->ends_on->format('d.m.Y').'.' : ''));
    }

    public function deleteBillingPeriod(int $periodId): void
    {
        $period = $this->customer->billingPeriods()->findOrFail($periodId);
        $period->delete();

        unset($this->billingPeriods, $this->openBillingPeriod);
        $this->dispatch('adminv2-toast', message: 'Abrechnungszeitraum '.$period->rangeLabel().' gelöscht.');
    }
}
