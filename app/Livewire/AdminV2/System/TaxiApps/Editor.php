<?php

namespace App\Livewire\AdminV2\System\TaxiApps;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\CustomEvent;
use App\Models\TaxiApp;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Eine Taxi-App anlegen oder bearbeiten: Name, Logo (als Link), Beschreibung
 * je Sprache, Website und App-Stores.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2;

    #[Locked]
    public ?int $appId = null;

    public string $name = '';

    /** @var array<string, string> Beschreibung je Sprache */
    public array $descriptions = [];

    public string $logoUrl = '';

    public string $websiteUrl = '';

    public string $appStoreUrl = '';

    public string $playStoreUrl = '';

    public bool $isActive = true;

    public string $sortOrder = '0';

    public function mount($app = null): void
    {
        foreach (CustomEvent::translationLocales() as $locale) {
            $this->descriptions[$locale] = '';
        }

        if ($app === null) {
            return;
        }

        $model = TaxiApp::findOrFail((int) $app);

        $this->appId = $model->id;
        $this->name = $model->name;
        $this->logoUrl = (string) $model->logo_url;
        $this->websiteUrl = (string) $model->website_url;
        $this->appStoreUrl = (string) $model->app_store_url;
        $this->playStoreUrl = (string) $model->play_store_url;
        $this->isActive = (bool) $model->is_active;
        $this->sortOrder = (string) $model->sort_order;

        foreach ($model->description_translations ?? [] as $locale => $text) {
            $this->descriptions[$locale] = (string) $text;
        }
    }

    #[Computed]
    public function app(): ?TaxiApp
    {
        return $this->appId ? TaxiApp::find($this->appId) : null;
    }

    public function save()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'descriptions.*' => ['nullable', 'string', 'max:2000'],
            'logoUrl' => ['nullable', 'url', 'max:2048'],
            'websiteUrl' => ['nullable', 'url', 'max:2048'],
            'appStoreUrl' => ['nullable', 'url', 'max:2048'],
            'playStoreUrl' => ['nullable', 'url', 'max:2048'],
            'sortOrder' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'name.required' => 'Bitte den Namen der App eingeben.',
            'logoUrl.url' => 'Das Logo ist eine vollständige Adresse (https://…).',
            'websiteUrl.url' => 'Die Website ist eine vollständige Adresse (https://…).',
            'appStoreUrl.url' => 'Der App-Store-Link ist eine vollständige Adresse (https://…).',
            'playStoreUrl.url' => 'Der Play-Store-Link ist eine vollständige Adresse (https://…).',
            'sortOrder.integer' => 'Die Sortierung ist eine ganze Zahl.',
        ]);

        $descriptions = array_filter(array_map(fn ($text) => trim((string) $text), $this->descriptions), fn ($text) => $text !== '');

        $model = $this->app ?? new TaxiApp;
        $created = ! $model->exists;

        $model->fill([
            'name' => trim($this->name),
            'description_translations' => $descriptions ?: null,
            'logo_url' => trim($this->logoUrl) ?: null,
            'website_url' => trim($this->websiteUrl) ?: null,
            'app_store_url' => trim($this->appStoreUrl) ?: null,
            'play_store_url' => trim($this->playStoreUrl) ?: null,
            'is_active' => $this->isActive,
            'sort_order' => (int) ($this->sortOrder ?: 0),
        ])->save();

        session()->flash('adminv2-toast', $created ? '„'.$model->name.'“ angelegt.' : '„'.$model->name.'“ gespeichert.');

        return $this->redirectRoute('adminv2.system.taxi-apps.index');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.taxi-apps.editor')
            ->title($this->app ? $this->app->name : 'Neue Taxi App');
    }
}
