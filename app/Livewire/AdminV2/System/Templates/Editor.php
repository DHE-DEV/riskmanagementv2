<?php

namespace App\Livewire\AdminV2\System\Templates;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\NotificationTemplate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Eine Standard-Vorlage bearbeiten: Name, Betreff und E-Mail-Inhalt (HTML)
 * mit Platzhaltern. Die Quelle einer Vorlage steht fest.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Vorlage bearbeiten')]
class Editor extends Component
{
    use AuthorizesAdminV2;

    #[Locked]
    public int $templateId;

    public string $name = '';

    public string $subject = '';

    public string $bodyHtml = '';

    public function mount($template): void
    {
        $model = NotificationTemplate::query()->where('is_system', true)->find((int) $template);

        abort_unless($model, 404);

        $this->templateId = $model->id;
        $this->name = $model->name;
        $this->subject = $model->subject;
        $this->bodyHtml = (string) $model->body_html;
    }

    #[Computed]
    public function template(): NotificationTemplate
    {
        return NotificationTemplate::query()->where('is_system', true)->findOrFail($this->templateId);
    }

    public function save()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'bodyHtml' => ['required', 'string'],
        ], [
            'name.required' => 'Bitte einen Namen eingeben.',
            'subject.required' => 'Bitte einen Betreff eingeben.',
            'bodyHtml.required' => 'Bitte den E-Mail-Inhalt eingeben.',
        ]);

        $this->template->update([
            'name' => trim($this->name),
            'subject' => trim($this->subject),
            'body_html' => $this->bodyHtml,
        ]);

        session()->flash('adminv2-toast', 'Vorlage „'.trim($this->name).'“ gespeichert.');

        return $this->redirectRoute('adminv2.system.templates.index');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.templates.editor');
    }
}
