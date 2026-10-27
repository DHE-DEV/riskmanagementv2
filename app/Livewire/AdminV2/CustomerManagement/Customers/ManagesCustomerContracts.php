<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use App\Models\CustomerContract;
use App\Models\CustomerContractPrice;
use App\Models\Product;
use App\Models\ProductVersionPrice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

/**
 * Tab „Abo“: Vertraege des Kunden mit Laufzeit, Bedingungen, vereinbarter
 * jaehrlicher Preiserhoehung (nur Erinnerung) und einer Preismatrix ueber alle
 * Produkte und Auspraegungen. Leere Felder bedeuten
 * Standardpreis; ein Preis kann an eine Anzahl PAX gebunden sein. Vertraege
 * ueberschneiden sich nicht.
 */
trait ManagesCustomerContracts
{
    public bool $showContractForm = false;

    /**
     * @var array{id: ?int, name: string, starts_on: string, ends_on: string, minimum_term_months: string, notice_period: string, auto_renews: bool, conditions: list<string>, notes: string, prices: array<int|string, array<string, mixed>>}
     */
    public array $contractForm = [];

    #[Computed]
    public function contracts(): Collection
    {
        return $this->customer->contracts()->with('prices.version.product')->get();
    }

    /** Produkte mit Auspraegungen und Standardpreis fuer die Matrix */
    #[Computed]
    public function contractCatalog(): Collection
    {
        return Product::query()->with(['versions' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'), 'versions.prices'])
            ->where('is_active', true)->ordered()->get();
    }

    public function newContract(): void
    {
        $this->resetErrorBag();
        $this->contractForm = [
            'id' => null,
            'name' => 'Rahmenvertrag '.today()->year,
            'starts_on' => today()->startOfMonth()->addMonth()->toDateString(),
            'ends_on' => '',
            'minimum_term_months' => '12',
            'notice_period' => '3 Monate zum Laufzeitende',
            'auto_renews' => true,
            'annual_increase_percent' => '',
            'conditions' => [''],
            'notes' => '',
            'prices' => [],
        ];
        $this->showContractForm = true;
    }

    public function editContract(int $contractId): void
    {
        $contract = $this->customer->contracts()->with('prices')->findOrFail($contractId);

        $prices = [];
        foreach ($contract->prices as $price) {
            $prices[$price->product_version_id] = [
                'is_free' => $price->is_free,
                'pax' => $price->pax !== null ? (string) $price->pax : '',
                ...array_map(
                    fn ($column) => $price->{$column} !== null ? str_replace('.', ',', (string) $price->{$column}) : '',
                    array_combine(array_keys(ProductVersionPrice::AMOUNTS), array_keys(ProductVersionPrice::AMOUNTS)),
                ),
            ];
        }

        $this->resetErrorBag();
        $this->contractForm = [
            'id' => $contract->id,
            'name' => $contract->name,
            'starts_on' => $contract->starts_on->toDateString(),
            'ends_on' => $contract->ends_on?->toDateString() ?? '',
            'minimum_term_months' => (string) ($contract->minimum_term_months ?? ''),
            'notice_period' => (string) $contract->notice_period,
            'auto_renews' => $contract->auto_renews,
            'annual_increase_percent' => $contract->annual_increase_percent !== null ? rtrim(rtrim(str_replace('.', ',', (string) $contract->annual_increase_percent), '0'), ',') : '',
            'conditions' => $contract->conditions ?: [''],
            'notes' => (string) $contract->notes,
            'prices' => $prices,
        ];
        $this->showContractForm = true;
    }

    public function addContractCondition(): void
    {
        $this->contractForm['conditions'][] = '';
    }

    public function removeContractCondition(int $index): void
    {
        unset($this->contractForm['conditions'][$index]);
        $this->contractForm['conditions'] = array_values($this->contractForm['conditions']) ?: [''];
    }

    public function saveContract(): void
    {
        $amountRules = [];
        foreach (array_keys(ProductVersionPrice::AMOUNTS) as $column) {
            // 99000 · 99000,50 · 99.000,50 · 9.90
            $amountRules['contractForm.prices.*.'.$column] = ['nullable', 'regex:/^(?:(?:\d{1,3}(?:\.\d{3})+|\d{1,9})(?:,\d{1,2})?|\d{1,9}\.\d{1,2})$/'];
        }

        $this->validate([
            'contractForm.name' => ['required', 'string', 'max:255'],
            'contractForm.starts_on' => ['required', 'date'],
            'contractForm.ends_on' => ['nullable', 'date', 'after_or_equal:contractForm.starts_on'],
            'contractForm.minimum_term_months' => ['nullable', 'integer', 'min:1', 'max:240'],
            'contractForm.notice_period' => ['nullable', 'string', 'max:255'],
            'contractForm.annual_increase_percent' => ['nullable', 'regex:/^\d{1,2}([.,]\d{1,2})?$/'],
            'contractForm.conditions.*' => ['nullable', 'string', 'max:1000'],
            'contractForm.notes' => ['nullable', 'string', 'max:5000'],
            'contractForm.prices.*.pax' => ['nullable', 'regex:/^\d{1,3}(\.?\d{3})*$/'],
            ...$amountRules,
        ], [
            'contractForm.name.required' => 'Bitte den Vertrag benennen.',
            'contractForm.starts_on.required' => 'Bitte den Vertragsbeginn angeben.',
            'contractForm.ends_on.after_or_equal' => 'Das Vertragsende liegt vor dem Beginn.',
            'contractForm.annual_increase_percent.regex' => 'Prozent mit höchstens zwei Nachkommastellen, z. B. 3 oder 2,5.',
            'contractForm.prices.*.pax.regex' => 'PAX als ganze Zahl, z. B. 10.000.000.',
            'contractForm.prices.*.*.regex' => 'Betrag mit höchstens zwei Nachkommastellen, z. B. 8,50 oder 99.000,00.',
        ]);

        $form = $this->contractForm;
        $startsOn = Carbon::parse($form['starts_on'])->toDateString();
        $endsOn = $form['ends_on'] !== '' ? Carbon::parse($form['ends_on'])->toDateString() : null;

        if ($conflict = CustomerContract::overlaps($this->customer->id, $startsOn, $endsOn, $form['id'])) {
            $this->addError('contractForm.starts_on', 'Überschneidet sich mit „'.$conflict->name.'“ ('.$conflict->rangeLabel().').');

            return;
        }

        // Mit Komma: deutsches Format (Punkte = Tausender); sonst Punkt als Dezimaltrenner.
        $amount = function ($value) {
            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }

            return str_contains($value, ',') ? str_replace(['.', ','], ['', '.'], $value) : $value;
        };

        $contract = DB::transaction(function () use ($form, $startsOn, $endsOn, $amount) {
            $contract = $form['id'] ? $this->customer->contracts()->findOrFail($form['id']) : new CustomerContract(['customer_id' => $this->customer->id]);
            $contract->fill([
                'name' => trim($form['name']),
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'minimum_term_months' => $form['minimum_term_months'] !== '' ? (int) $form['minimum_term_months'] : null,
                'notice_period' => trim((string) $form['notice_period']) ?: null,
                'auto_renews' => (bool) $form['auto_renews'],
                'annual_increase_percent' => trim((string) $form['annual_increase_percent']) !== '' ? str_replace(',', '.', trim((string) $form['annual_increase_percent'])) : null,
                'conditions' => array_values(array_filter(array_map('trim', $form['conditions']), fn ($line) => $line !== '')) ?: null,
                'notes' => trim((string) $form['notes']) ?: null,
            ])->save();

            // Nur Zeilen mit eigenem Wert speichern – der Rest ist Standardpreis.
            $keep = [];
            foreach ($form['prices'] as $versionId => $data) {
                $values = array_map($amount, array_intersect_key($data, ProductVersionPrice::AMOUNTS));
                $pax = trim((string) ($data['pax'] ?? '')) !== '' ? (int) str_replace('.', '', $data['pax']) : null;
                if (! ($data['is_free'] ?? false) && array_filter($values, fn ($v) => $v !== null) === [] && $pax === null) {
                    continue;
                }

                CustomerContractPrice::updateOrCreate(
                    ['customer_contract_id' => $contract->id, 'product_version_id' => (int) $versionId],
                    [...$values, 'is_free' => (bool) ($data['is_free'] ?? false), 'pax' => $pax],
                );
                $keep[] = (int) $versionId;
            }
            $contract->prices()->whereNotIn('product_version_id', $keep)->delete();

            return $contract;
        });

        $this->showContractForm = false;
        unset($this->contracts);
        $this->dispatch('adminv2-toast', message: 'Vertrag „'.$contract->name.'“ gespeichert.');
    }

    public function deleteContract(int $contractId): void
    {
        $contract = $this->customer->contracts()->findOrFail($contractId);
        $contract->delete();

        unset($this->contracts);
        $this->dispatch('adminv2-toast', message: 'Vertrag „'.$contract->name.'“ gelöscht.');
    }
}
