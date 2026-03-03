<?php

namespace App\Livewire\Customer;

use App\Models\NotificationTemplate;
use Livewire\Component;

class NotificationTemplateForm extends Component
{
    public ?int $templateId = null;

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

        session()->flash('success', $this->templateId
            ? 'Vorlage erfolgreich aktualisiert.'
            : 'Vorlage erfolgreich erstellt.'
        );

        $this->redirect(route('customer.notification-settings.templates.index'));
    }

    public function deleteTemplate(): void
    {
        if (! $this->templateId) {
            return;
        }

        $customer = auth('customer')->user();
        $template = $customer->notificationTemplates()->findOrFail($this->templateId);
        $template->delete();

        session()->flash('success', 'Vorlage erfolgreich gelöscht.');
        $this->redirect(route('customer.notification-settings.templates.index'));
    }

    public function render()
    {
        return view('livewire.customer.notification-template-form', [
            'placeholders' => NotificationTemplate::PLACEHOLDERS,
        ]);
    }
}
