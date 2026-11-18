<?php

namespace App\Livewire\AdminV2\System\EventTypes;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Services\DeepLTranslationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * System > Event-Typen: einen Typ anlegen oder bearbeiten – Code, Name je Sprache
 * (aus EVENT_LANGUAGES), Beschreibung, Farbe, Icon, Sortierung und Status.
 * Fehlende Sprachen lassen sich per DeepL aus dem deutschen Namen übersetzen.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2;

    #[Locked]
    public ?int $typeId = null;

    public string $code = '';

    /** @var array<string, string> Name je Sprache */
    public array $names = [];

    public string $description = '';

    public string $color = '#3B82F6';

    public string $icon = '';

    public bool $isActive = true;

    public string $sortOrder = '0';

    public function mount($type = null): void
    {
        foreach (CustomEvent::translationLocales() as $locale) {
            $this->names[$locale] = '';
        }

        if ($type === null) {
            $this->sortOrder = (string) ((int) EventType::max('sort_order') + 1);

            return;
        }

        $model = EventType::findOrFail((int) $type);

        $this->typeId = $model->id;
        $this->code = (string) $model->code;
        $this->names[CustomEvent::sourceLocale()] = (string) $model->name;
        $this->description = (string) $model->description;
        $this->color = (string) ($model->color ?: '#3B82F6');
        $this->icon = (string) $model->icon;
        $this->isActive = (bool) $model->is_active;
        $this->sortOrder = (string) $model->sort_order;

        foreach ($model->name_translations ?? [] as $locale => $name) {
            if ($locale !== CustomEvent::sourceLocale() && array_key_exists($locale, $this->names)) {
                $this->names[$locale] = (string) $name;
            }
        }
    }

    #[Computed]
    public function type(): ?EventType
    {
        return $this->typeId ? EventType::withCount('events')->find($this->typeId) : null;
    }

    /**
     * Leere Sprachen per DeepL aus dem Namen in der Ausgangssprache füllen (ohne zu speichern).
     */
    public function translateMissing(DeepLTranslationService $deepl): void
    {
        $source = CustomEvent::sourceLocale();
        $sourceName = trim($this->names[$source] ?? '');

        if ($sourceName === '') {
            $this->addError("names.{$source}", 'Bitte zuerst den deutschen Namen eintragen.');

            return;
        }

        if (! $deepl->isConfigured()) {
            $this->dispatch('adminv2-toast', message: 'DeepL ist nicht konfiguriert (DEEPL_KEY fehlt).', variant: 'danger');

            return;
        }

        $translated = 0;
        $errors = [];

        foreach (array_keys($this->names) as $locale) {
            if ($locale === $source || trim($this->names[$locale]) !== '') {
                continue;
            }

            try {
                $this->names[$locale] = $deepl->translateBatch(
                    [$sourceName],
                    $locale,
                    $source,
                    'Category of travel safety events shown to travellers, e.g. security, strike, health, entry requirements.',
                )[0];
                $translated++;
            } catch (\Throwable $e) {
                $errors[] = strtoupper($locale).': '.$e->getMessage();
            }
        }

        $this->dispatch('adminv2-toast', ...match (true) {
            $errors !== [] => ['message' => 'Übersetzung teilweise fehlgeschlagen – '.implode(' | ', $errors), 'variant' => 'danger'],
            $translated > 0 => ['message' => "{$translated} Sprache(n) übersetzt – bitte prüfen und speichern."],
            default => ['message' => 'Alle Sprachen sind bereits ausgefüllt.'],
        });
    }

    public function save()
    {
        $source = CustomEvent::sourceLocale();
        $this->code = mb_strtolower(trim($this->code));

        $this->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/', Rule::unique('event_types', 'code')->ignore($this->typeId)],
            "names.{$source}" => ['required', 'string', 'max:255'],
            'names.*' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sortOrder' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'code.required' => 'Bitte einen Code angeben, z. B. strike.',
            'code.regex' => 'Der Code besteht nur aus Kleinbuchstaben, Ziffern, Bindestrich und Unterstrich.',
            'code.unique' => 'Diesen Code trägt bereits ein anderer Event-Typ.',
            "names.{$source}.required" => 'Bitte den deutschen Namen angeben.',
            'color.regex' => 'Die Farbe ist ein Hex-Wert wie #3B82F6.',
            'sortOrder.integer' => 'Die Sortierung ist eine ganze Zahl.',
        ]);

        $translations = collect($this->names)
            ->except($source)
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->all();

        $model = $this->type ?? new EventType;
        $created = ! $model->exists;

        $model->fill([
            'code' => $this->code,
            'name' => trim($this->names[$source]),
            'name_translations' => $translations ?: null,
            'description' => trim($this->description) ?: null,
            'color' => $this->color ?: null,
            'icon' => trim($this->icon) ?: null,
            'is_active' => $this->isActive,
            'sort_order' => (int) ($this->sortOrder ?: 0),
        ])->save();

        // Zwischengespeicherte Events tragen die Typnamen mit.
        Cache::forget('gtm_all_events');

        session()->flash('adminv2-toast', $created ? '„'.$model->name.'“ angelegt.' : '„'.$model->name.'“ gespeichert.');

        return $this->redirectRoute('adminv2.system.event-types.index');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.event-types.editor')
            ->title($this->type ? $this->type->name : 'Neuer Event-Typ');
    }
}
