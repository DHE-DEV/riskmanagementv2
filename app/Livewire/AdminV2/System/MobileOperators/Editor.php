<?php

namespace App\Livewire\AdminV2\System\MobileOperators;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\CustomEvent;
use App\Models\MobileOperator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Einen Mobilfunkanbieter anlegen oder bearbeiten: Name, Logo (als Link),
 * Beschreibung je Sprache, Website, Prepaid-Seite und eSIM.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2;

    #[Locked]
    public ?int $operatorId = null;

    public string $name = '';

    /** @var array<string, string> Beschreibung je Sprache */
    public array $descriptions = [];

    public string $logoUrl = '';

    public string $websiteUrl = '';

    public string $prepaidUrl = '';

    public bool $offersEsim = false;

    public bool $isActive = true;

    public string $sortOrder = '0';

    public function mount($operator = null): void
    {
        foreach (CustomEvent::translationLocales() as $locale) {
            $this->descriptions[$locale] = '';
        }

        if ($operator === null) {
            return;
        }

        $model = MobileOperator::findOrFail((int) $operator);

        $this->operatorId = $model->id;
        $this->name = $model->name;
        $this->logoUrl = (string) $model->logo_url;
        $this->websiteUrl = (string) $model->website_url;
        $this->prepaidUrl = (string) $model->prepaid_url;
        $this->offersEsim = (bool) $model->offers_esim;
        $this->isActive = (bool) $model->is_active;
        $this->sortOrder = (string) $model->sort_order;

        foreach ($model->description_translations ?? [] as $locale => $text) {
            $this->descriptions[$locale] = (string) $text;
        }
    }

    #[Computed]
    public function operator(): ?MobileOperator
    {
        return $this->operatorId ? MobileOperator::find($this->operatorId) : null;
    }

    public function save()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'descriptions.*' => ['nullable', 'string', 'max:2000'],
            'logoUrl' => ['nullable', 'url', 'max:2048'],
            'websiteUrl' => ['nullable', 'url', 'max:2048'],
            'prepaidUrl' => ['nullable', 'url', 'max:2048'],
            'sortOrder' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'name.required' => 'Bitte den Namen des Anbieters eingeben.',
            'logoUrl.url' => 'Das Logo ist eine vollständige Adresse (https://…).',
            'websiteUrl.url' => 'Die Website ist eine vollständige Adresse (https://…).',
            'prepaidUrl.url' => 'Die Prepaid-Seite ist eine vollständige Adresse (https://…).',
            'sortOrder.integer' => 'Die Sortierung ist eine ganze Zahl.',
        ]);

        $descriptions = array_filter(array_map(fn ($text) => trim((string) $text), $this->descriptions), fn ($text) => $text !== '');

        $model = $this->operator ?? new MobileOperator;
        $created = ! $model->exists;

        $model->fill([
            'name' => trim($this->name),
            'description_translations' => $descriptions ?: null,
            'logo_url' => trim($this->logoUrl) ?: null,
            'website_url' => trim($this->websiteUrl) ?: null,
            'prepaid_url' => trim($this->prepaidUrl) ?: null,
            'offers_esim' => $this->offersEsim,
            'is_active' => $this->isActive,
            'sort_order' => (int) ($this->sortOrder ?: 0),
        ])->save();

        session()->flash('adminv2-toast', $created ? '„'.$model->name.'“ angelegt.' : '„'.$model->name.'“ gespeichert.');

        return $this->redirectRoute('adminv2.system.mobile-operators.index');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.mobile-operators.editor')
            ->title($this->operator ? $this->operator->name : 'Neuer Mobilfunkanbieter');
    }
}
