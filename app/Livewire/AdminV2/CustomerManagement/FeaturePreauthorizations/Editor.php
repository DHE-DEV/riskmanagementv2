<?php

namespace App\Livewire\AdminV2\CustomerManagement\FeaturePreauthorizations;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\CustomerFeaturePreauthorization;
use App\Services\CustomerFeaturePreauthorizationService;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Formular einer einzelnen Feature-Vormerkung: Account-ID, Feature,
 * Freischalten oder Sperren, Notiz. Zeigt direkt, ob es zu der Account-ID
 * schon Kundenkonten gibt – sonst ist nicht erkennbar, ob die Vormerkung
 * sofort oder erst beim naechsten Login greift.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2;

    #[Locked]
    public ?int $preauthorizationId = null;

    public string $pdsAccountId = '';

    public string $featureKey = Index::DEFAULT_FEATURE;

    public bool $enabled = true;

    public string $note = '';

    public function mount(?int $preauthorization = null): void
    {
        if ($preauthorization === null) {
            return;
        }

        $model = CustomerFeaturePreauthorization::findOrFail($preauthorization);

        $this->preauthorizationId = $model->id;
        $this->pdsAccountId = (string) $model->pds_account_id;
        $this->featureKey = $model->feature_key;
        $this->enabled = $model->enabled;
        $this->note = (string) $model->note;
    }

    #[Computed]
    public function preauthorization(): ?CustomerFeaturePreauthorization
    {
        return $this->preauthorizationId ? CustomerFeaturePreauthorization::find($this->preauthorizationId) : null;
    }

    /**
     * @return array<string, string> Feature-Key => Bezeichnung
     */
    public function featureLabels(): array
    {
        $labels = CustomerFeatureOverride::getFeatureLabels();

        // Ein inzwischen unbekannter Key bleibt an seiner Vormerkung sichtbar.
        if ($this->preauthorization && ! isset($labels[$this->preauthorization->feature_key])) {
            $labels[$this->preauthorization->feature_key] = $this->preauthorization->feature_key;
        }

        return $labels;
    }

    /**
     * Kundenkonten mit der eingegebenen Account-ID.
     */
    #[Computed]
    public function accounts(): Collection
    {
        $accountId = ctype_digit($this->pdsAccountId) ? (int) $this->pdsAccountId : 0;

        return $accountId > 0
            ? Customer::query()->where('pds_account_id', $accountId)->orderBy('id')->get(['id', 'pds_account_id', 'name', 'company_name', 'email'])
            : collect();
    }

    /**
     * Hinweis neben der Account-ID; null, solange keine gueltige ID eingegeben ist.
     */
    public function accountHint(): ?string
    {
        if (! ctype_digit($this->pdsAccountId) || (int) $this->pdsAccountId < 1) {
            return null;
        }

        $count = $this->accounts->count();

        return match (true) {
            $count === 0 => 'Noch kein Konto – greift beim ersten Login',
            $count === 1 => '1 Konto vorhanden',
            default => "{$count} Konten vorhanden",
        };
    }

    public function updatedPdsAccountId(): void
    {
        unset($this->accounts);
    }

    protected function rules(): array
    {
        return [
            'pdsAccountId' => [
                'required', 'integer', 'min:1',
                // Je Account-ID und Feature gibt es nur eine Vormerkung (eindeutiger Index).
                Rule::unique('customer_feature_preauthorizations', 'pds_account_id')
                    ->where('feature_key', $this->featureKey)
                    ->ignore($this->preauthorizationId),
            ],
            'featureKey' => ['required', Rule::in(array_keys($this->featureLabels()))],
            'enabled' => ['boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'pdsAccountId.required' => 'Bitte die PDS Account-ID eingeben.',
            'pdsAccountId.integer' => 'Die Account-ID muss eine Zahl sein.',
            'pdsAccountId.min' => 'Die Account-ID muss mindestens 1 sein.',
            'pdsAccountId.unique' => 'Für diese Account-ID ist das Feature bereits vorgemerkt.',
            'featureKey.required' => 'Bitte ein Feature wählen.',
            'featureKey.in' => 'Bitte ein Feature wählen.',
            'note.max' => 'Die Notiz darf höchstens 255 Zeichen lang sein.',
        ];
    }

    public function save()
    {
        $this->validate();

        $model = $this->preauthorization ?? new CustomerFeaturePreauthorization;
        $creating = ! $model->exists;

        $model->fill([
            'pds_account_id' => (int) $this->pdsAccountId,
            'feature_key' => $this->featureKey,
            'enabled' => $this->enabled,
            'note' => filled($this->note) ? trim($this->note) : null,
        ])->save();

        $message = 'Gespeichert.';

        // Gibt es zu der Account-ID schon Konten, soll eine neue Vormerkung
        // nicht bis zum naechsten Login warten.
        if ($creating) {
            $result = app(CustomerFeaturePreauthorizationService::class)
                ->applyToExistingCustomers($model->feature_key, [$model->pds_account_id]);

            $message = $result['customers'] > 0
                ? "Sofort angewendet: {$result['customers']} bestehende Konten angepasst."
                : 'Vorgemerkt. Greift beim ersten Login dieses Accounts.';
        }

        session()->flash('adminv2-toast', $message);

        return $this->redirectRoute('adminv2.customer-management.feature-preauthorizations.index');
    }

    /**
     * Loescht nur die Vormerkung – eine bereits erteilte Freischaltung bleibt
     * beim Kunden bestehen.
     */
    public function delete()
    {
        $this->preauthorization?->delete();

        session()->flash('adminv2-toast', 'Vormerkung gelöscht. Eine bereits erteilte Freischaltung bleibt beim Kunden bestehen.');

        return $this->redirectRoute('adminv2.customer-management.feature-preauthorizations.index');
    }

    public function render()
    {
        return view('livewire.admin-v2.customer-management.feature-preauthorizations.editor')
            ->title($this->preauthorization ? 'Feature-Vormerkung bearbeiten' : 'Feature einzeln vormerken');
    }
}
