<?php

namespace App\Livewire\Customer;

use App\Mail\RiskEventMail;
use App\Models\Country;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class NotificationRuleForm extends Component
{
    public ?int $ruleId = null;
    public ?string $testMailStatus = null;

    public string $name = '';
    public bool $isActive = true;
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

    #[\Livewire\Attributes\On('load-rule')]
    public function loadRule(?int $id = null): void
    {
        $this->ruleId = $id;
        $this->testMailStatus = null;
        $this->resetValidation();

        if ($id) {
            $customer = auth('customer')->user();
            $rule = $customer->notificationRules()->with('recipients')->findOrFail($id);
            $this->name = $rule->name;
            $this->isActive = $rule->is_active;
            $this->riskLevels = $rule->risk_levels ?? [];
            $this->categories = $rule->categories ?? [];
            $this->notificationTemplateId = $rule->notification_template_id;
            if ($rule->country_ids) {
                $countries = Country::whereIn('id', $rule->country_ids)->get();
                $this->selectedCountries = $countries->map(fn ($c) => ['id' => $c->id, 'name' => $c->getName('de')])->toArray();
            } else {
                $this->selectedCountries = [];
            }
            $this->recipients = $rule->recipients->map(fn ($r) => ['email' => $r->email, 'type' => $r->recipient_type])->toArray();
        } else {
            $this->name = '';
            $this->isActive = true;
            $this->riskLevels = [];
            $this->categories = [];
            $this->selectedCountries = [];
            $this->notificationTemplateId = null;
            $this->recipients = [['email' => '', 'type' => 'to']];
        }
        $this->countrySearch = '';
        $this->countryResults = [];
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

        $this->js('window.dispatchEvent(new CustomEvent("rule-saved"))');
    }

    public function sendTestMail(): void
    {
        if (! $this->ruleId) {
            return;
        }

        $customer = auth('customer')->user();
        $rule = $customer->notificationRules()->with(['template', 'recipients'])->findOrFail($this->ruleId);

        $template = $rule->template ?? NotificationTemplate::where('is_system', true)->first();

        if (! $template) {
            $this->testMailStatus = 'error:Keine E-Mail-Vorlage gefunden.';
            return;
        }

        $placeholders = [
            '{event_title}' => 'Test-Ereignis',
            '{country_name}' => 'Deutschland',
            '{risk_level}' => 'Hoch',
            '{category}' => 'Allgemein',
            '{description}' => 'Dies ist eine Test-Benachrichtigung um den E-Mail-Versand zu prüfen.',
            '{event_date}' => now()->format('d.m.Y'),
            '{unsubscribe_url}' => '#',
        ];

        $toRecipient = $rule->recipients->where('recipient_type', 'to')->first();

        if (! $toRecipient) {
            $this->testMailStatus = 'error:Kein TO-Empfänger konfiguriert.';
            return;
        }

        try {
            Mail::to($toRecipient->email)->send(new RiskEventMail($template, $placeholders, $rule));
            $this->testMailStatus = 'success:Test-Mail wurde erfolgreich gesendet.';
        } catch (\Throwable $e) {
            $this->testMailStatus = 'error:Fehler beim Senden: ' . $e->getMessage();
        }
    }

    public function deleteRule(): void
    {
        if (! $this->ruleId) {
            return;
        }

        $customer = auth('customer')->user();
        $rule = $customer->notificationRules()->findOrFail($this->ruleId);
        $rule->delete();

        $this->js('window.dispatchEvent(new CustomEvent("rule-deleted"))');
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
