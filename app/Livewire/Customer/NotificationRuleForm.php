<?php

namespace App\Livewire\Customer;

use App\Models\Country;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use Livewire\Component;

class NotificationRuleForm extends Component
{
    public ?int $ruleId = null;

    public string $name = '';
    public bool $isActive = true;
    public string $logicOperator = 'and';
    public array $riskLevels = [];
    public array $categories = [];
    public array $selectedCountries = [];
    public ?int $notificationTemplateId = null;

    public array $recipients = [];

    public string $countrySearch = '';
    public array $countryResults = [];

    public function mount(?int $ruleId = null): void
    {
        $this->ruleId = $ruleId;

        if ($ruleId) {
            $customer = auth('customer')->user();
            $rule = $customer->notificationRules()->with('recipients')->findOrFail($ruleId);

            $this->name = $rule->name;
            $this->isActive = $rule->is_active;
            $this->logicOperator = $rule->logic_operator;
            $this->riskLevels = $rule->risk_levels ?? [];
            $this->categories = $rule->categories ?? [];
            $this->notificationTemplateId = $rule->notification_template_id;

            // Load countries
            if ($rule->country_ids) {
                $countries = Country::whereIn('id', $rule->country_ids)->get();
                $this->selectedCountries = $countries->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->getName('de'),
                ])->toArray();
            }

            // Load recipients
            $this->recipients = $rule->recipients->map(fn ($r) => [
                'email' => $r->email,
                'type' => $r->recipient_type,
            ])->toArray();
        }

        if (empty($this->recipients)) {
            $this->recipients = [['email' => '', 'type' => 'to']];
        }
    }

    public function updatedCountrySearch(): void
    {
        if (strlen($this->countrySearch) < 2) {
            $this->countryResults = [];
            return;
        }

        $selectedIds = collect($this->selectedCountries)->pluck('id')->toArray();

        $this->countryResults = Country::query()
            ->whereNotIn('id', $selectedIds)
            ->where(function ($q) {
                $search = '%' . $this->countrySearch . '%';
                $q->where('name_translations->de', 'like', $search)
                  ->orWhere('name_translations->en', 'like', $search)
                  ->orWhere('iso_code', 'like', $search);
            })
            ->limit(10)
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->getName('de')])
            ->toArray();
    }

    public function addCountry(int $id, string $name): void
    {
        if (! collect($this->selectedCountries)->contains('id', $id)) {
            $this->selectedCountries[] = ['id' => $id, 'name' => $name];
        }
        $this->countrySearch = '';
        $this->countryResults = [];
    }

    public function removeCountry(int $id): void
    {
        $this->selectedCountries = collect($this->selectedCountries)
            ->reject(fn ($c) => $c['id'] === $id)
            ->values()
            ->toArray();
    }

    public function addRecipient(): void
    {
        $this->recipients[] = ['email' => '', 'type' => 'to'];
    }

    public function removeRecipient(int $index): void
    {
        if (count($this->recipients) > 1) {
            unset($this->recipients[$index]);
            $this->recipients = array_values($this->recipients);
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'logicOperator' => 'required|in:and,or',
            'riskLevels' => 'array',
            'riskLevels.*' => 'in:' . implode(',', array_keys(NotificationRule::RISK_LEVELS)),
            'categories' => 'array',
            'categories.*' => 'in:' . implode(',', array_keys(NotificationRule::CATEGORIES)),
            'recipients' => 'required|array|min:1',
            'recipients.*.email' => 'required|email',
            'recipients.*.type' => 'required|in:to,cc,bcc',
            'notificationTemplateId' => 'nullable|exists:notification_templates,id',
        ], [
            'name.required' => 'Bitte geben Sie einen Namen für die Regel ein.',
            'recipients.required' => 'Mindestens ein Empfänger ist erforderlich.',
            'recipients.*.email.required' => 'Bitte geben Sie eine E-Mail-Adresse ein.',
            'recipients.*.email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
        ]);

        $customer = auth('customer')->user();

        $data = [
            'customer_id' => $customer->id,
            'name' => $this->name,
            'is_active' => $this->isActive,
            'logic_operator' => $this->logicOperator,
            'risk_levels' => ! empty($this->riskLevels) ? $this->riskLevels : null,
            'categories' => ! empty($this->categories) ? $this->categories : null,
            'country_ids' => ! empty($this->selectedCountries)
                ? collect($this->selectedCountries)->pluck('id')->toArray()
                : null,
            'notification_template_id' => $this->notificationTemplateId,
        ];

        if ($this->ruleId) {
            $rule = $customer->notificationRules()->findOrFail($this->ruleId);
            $rule->update($data);
        } else {
            $rule = NotificationRule::create($data);
        }

        // Sync recipients
        $rule->recipients()->delete();
        foreach ($this->recipients as $recipient) {
            if (! empty($recipient['email'])) {
                $rule->recipients()->create([
                    'email' => $recipient['email'],
                    'recipient_type' => $recipient['type'],
                ]);
            }
        }

        session()->flash('success', $this->ruleId
            ? 'Regel erfolgreich aktualisiert.'
            : 'Regel erfolgreich erstellt.'
        );

        $this->redirect(route('customer.notification-settings.index'));
    }

    public function deleteRule(): void
    {
        if (! $this->ruleId) {
            return;
        }

        $customer = auth('customer')->user();
        $rule = $customer->notificationRules()->findOrFail($this->ruleId);
        $rule->delete();

        session()->flash('success', 'Regel erfolgreich gelöscht.');
        $this->redirect(route('customer.notification-settings.index'));
    }

    public function render()
    {
        $customer = auth('customer')->user();
        $templates = NotificationTemplate::forCustomer($customer->id)->get();

        return view('livewire.customer.notification-rule-form', [
            'templates' => $templates,
            'availableRiskLevels' => NotificationRule::RISK_LEVELS,
            'availableCategories' => NotificationRule::CATEGORIES,
        ]);
    }
}
