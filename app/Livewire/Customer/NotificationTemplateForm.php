<?php

namespace App\Livewire\Customer;

use App\Mail\RiskEventMail;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class NotificationTemplateForm extends Component
{
    public ?int $templateId = null;
    public ?string $testMailStatus = null;

    public string $name = '';
    public string $subject = '';
    public string $bodyHtml = '';

    public function mount(?int $templateId = null): void
    {
        $this->templateId = $templateId;

        if ($templateId) {
            $customer = auth('customer')->user();
            $template = NotificationTemplate::forCustomer($customer->id)->findOrFail($templateId);

            if ($template->is_system) {
                abort(403, 'System-Vorlagen können nicht bearbeitet werden.');
            }

            $this->name = $template->name;
            $this->subject = $template->subject;
            $this->bodyHtml = $template->body_html;
        }
    }

    #[\Livewire\Attributes\On('load-template')]
    public function loadTemplate(?int $id = null): void
    {
        $this->templateId = $id;
        $this->testMailStatus = null;

        if ($id) {
            $customer = auth('customer')->user();
            $template = \App\Models\NotificationTemplate::forCustomer($customer->id)->findOrFail($id);
            $this->name = $template->name;
            $this->subject = $template->subject;
            $this->bodyHtml = $template->body_html;
        } else {
            $this->name = '';
            $this->subject = '';
            $this->bodyHtml = '';
        }

        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'bodyHtml' => 'required|string',
        ], [
            'name.required' => 'Bitte geben Sie einen Vorlagennamen ein.',
            'subject.required' => 'Bitte geben Sie einen Betreff ein.',
            'bodyHtml.required' => 'Bitte geben Sie den E-Mail-Inhalt ein.',
        ]);

        $customer = auth('customer')->user();

        $data = [
            'customer_id' => $customer->id,
            'name' => $this->name,
            'subject' => $this->subject,
            'body_html' => $this->bodyHtml,
            'is_system' => false,
        ];

        if ($this->templateId) {
            $template = $customer->notificationTemplates()->findOrFail($this->templateId);
            $template->update($data);
        } else {
            NotificationTemplate::create($data);
        }

        $this->js('window.dispatchEvent(new CustomEvent("template-saved"))');
    }

    public function sendTestMail(): void
    {
        if (! $this->templateId) {
            return;
        }

        $customer = auth('customer')->user();
        $template = NotificationTemplate::forCustomer($customer->id)->findOrFail($this->templateId);

        $placeholders = [
            '{event_title}' => 'Test-Ereignis',
            '{country_name}' => 'Deutschland',
            '{risk_level}' => 'Hoch',
            '{category}' => 'Allgemein',
            '{description}' => 'Dies ist eine Test-Benachrichtigung um den E-Mail-Versand zu prüfen.',
            '{event_date}' => now()->format('d.m.Y'),
            '{unsubscribe_url}' => '#',
        ];

        // Create a temporary rule with an empty recipients collection for the Mailable
        $tempRule = new NotificationRule();
        $tempRule->setRelation('recipients', collect());

        try {
            Mail::to($customer->email)->send(new RiskEventMail($template, $placeholders, $tempRule));
            $this->testMailStatus = 'success:Test-Mail wurde erfolgreich an ' . $customer->email . ' gesendet.';
        } catch (\Throwable $e) {
            $this->testMailStatus = 'error:Fehler beim Senden: ' . $e->getMessage();
        }
    }

    public function deleteTemplate(): void
    {
        if (! $this->templateId) {
            return;
        }

        $customer = auth('customer')->user();
        $template = $customer->notificationTemplates()->findOrFail($this->templateId);
        $template->delete();

        $this->js('window.dispatchEvent(new CustomEvent("template-deleted"))');
    }

    public function render()
    {
        return view('livewire.customer.notification-template-form', [
            'placeholders' => NotificationTemplate::PLACEHOLDERS,
        ]);
    }
}
