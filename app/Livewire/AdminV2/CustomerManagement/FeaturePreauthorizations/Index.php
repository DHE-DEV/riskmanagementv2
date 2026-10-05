<?php

namespace App\Livewire\AdminV2\CustomerManagement\FeaturePreauthorizations;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\QueriesLists;
use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use App\Models\CustomerFeaturePreauthorization;
use App\Services\CustomerFeaturePreauthorizationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kundenverwaltung > Feature-Vormerkungen: vorgemerkte Freischaltungen je
 * pds_account_id. Gedacht fuer Accounts, die sich noch nie eingeloggt haben –
 * beim ersten Login wird die Vormerkung in ein Feature-Override uebersetzt.
 *
 * Liste mit Suche, Filtern und Sortierung, dazu Import ganzer ID-Listen,
 * Anwenden auf bestehende Konten und Loeschen der Auswahl.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Feature-Vormerkungen')]
class Index extends Component
{
    use AuthorizesAdminV2, QueriesLists, WithPagination;

    public const PER_PAGE = 25;

    /** Vorgabe im Import und beim einzelnen Vormerken. */
    public const DEFAULT_FEATURE = 'navigation_risk_overview_enabled';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'created_at')]
    public string $sort = 'created_at';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    #[Url(except: '')]
    public string $feature = '';

    /** '' = alle, 'yes' = nur Freischaltungen, 'no' = nur Sperren */
    #[Url(except: '')]
    public string $enabled = '';

    /**
     * 'open' = noch nicht eingeloest (Vorgabe), 'applied' = eingeloest, 'all' = beides.
     * Eine Suche gilt immer fuer alle Vormerkungen.
     */
    #[Url(except: 'open')]
    public string $status = 'open';

    /** Nur Vormerkungen, zu deren Account-ID es noch kein Kundenkonto gibt. */
    #[Url(as: 'without-account', except: false)]
    public bool $withoutAccount = false;

    /** @var array<int, string> IDs der angehakten Vormerkungen */
    public array $selected = [];

    // Import einer ID-Liste
    public string $importIds = '';

    public string $importFeatureKey = self::DEFAULT_FEATURE;

    public bool $importEnabled = true;

    public string $importNote = '';

    public bool $importApplyNow = true;

    /**
     * Sortierungen: Spalte => Bezeichnung; die erste ist die Vorgabe.
     *
     * @return array<string, string>
     */
    public function sortOptions(): array
    {
        return [
            'created_at' => 'Angelegt',
            'pds_account_id' => 'PDS Account-ID',
            'feature_key' => 'Feature',
            'customers_count' => 'Konten',
            'applied_at' => 'Eingelöst',
        ];
    }

    /**
     * @return array<string, string> Feature-Key => Bezeichnung
     */
    public function featureLabels(): array
    {
        return CustomerFeatureOverride::getFeatureLabels();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'sort', 'feature', 'enabled', 'status', 'withoutAccount'], true)) {
            $this->resetPage();
            // Die Auswahl gilt fuer das, was zu sehen ist.
            $this->selected = [];
        }
    }

    public function toggleDirection(): void
    {
        $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'feature', 'enabled', 'status', 'withoutAccount', 'selected']);
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->feature !== '' || $this->enabled !== '' || $this->status !== 'open' || $this->withoutAccount;
    }

    /**
     * Abfrage mit Suche und Filtern – auch Grundlage fuer "alle auf der Seite auswaehlen".
     */
    protected function filteredQuery(): Builder
    {
        $query = CustomerFeaturePreauthorization::query();

        if (($term = trim($this->search)) !== '') {
            $query->where('pds_account_id', 'like', '%'.addcslashes($term, '%_\\').'%');
        } else {
            // Wer eine Account-ID sucht, soll sie finden – auch wenn sie schon eingeloest ist.
            match ($this->status) {
                'applied' => $query->whereNotNull('applied_at'),
                'all' => null,
                default => $query->whereNull('applied_at'),
            };
        }

        if ($this->feature !== '') {
            $query->where('feature_key', $this->feature);
        }

        match ($this->enabled) {
            'yes' => $query->where('enabled', true),
            'no' => $query->where('enabled', false),
            default => null,
        };

        if ($this->withoutAccount) {
            $query->whereDoesntHave('customers');
        }

        return $query;
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';
        $sort = array_key_exists($this->sort, $this->sortOptions()) ? $this->sort : 'created_at';

        // withCount statt Unterabfrage je Zeile: "Konten" zeigt, ob die
        // Vormerkung schon jemanden betrifft. Die Konten selbst nur fuer die Links.
        $query = $this->filteredQuery()
            ->withCount('customers')
            ->with(['customers' => fn ($query) => $query->select(['id', 'pds_account_id', 'name', 'company_name'])->orderBy('id')]);

        if ($sort === 'applied_at') {
            // Offene (ohne Datum) stehen immer am Ende.
            $query->orderByRaw('applied_at is null asc');
        }

        $query
            ->orderBy($sort, $direction)
            ->orderBy('customer_feature_preauthorizations.id', $direction);

        return $this->paginateWithinRange($query, self::PER_PAGE);
    }

    /**
     * Wie viele Vormerkungen noch auf ihren ersten Login warten (im bisherigen
     * Admin das Abzeichen am Menuepunkt).
     */
    #[Computed]
    public function openCount(): int
    {
        return CustomerFeaturePreauthorization::query()->whereNull('applied_at')->count();
    }

    /**
     * Wie viele Vormerkungen bereits eingeloest sind – sie stehen beim Oeffnen nicht in der Liste.
     */
    #[Computed]
    public function appliedCount(): int
    {
        return CustomerFeaturePreauthorization::query()->whereNotNull('applied_at')->count();
    }

    /**
     * Alle Vormerkungen der aktuellen Seite an- bzw. abwaehlen.
     */
    public function togglePage(): void
    {
        $pageIds = $this->rows->getCollection()->map(fn ($row) => (string) $row->id)->all();
        $allSelected = $pageIds !== [] && array_diff($pageIds, $this->selected) === [];

        $this->selected = $allSelected
            ? array_values(array_diff($this->selected, $pageIds))
            : array_values(array_unique([...$this->selected, ...$pageIds]));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /**
     * @return array<int, int>
     */
    protected function selectedIds(): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $this->selected))));
    }

    /**
     * Eine Liste von Account-IDs auf einmal vormerken. Bereits vorgemerkte IDs
     * werden aktualisiert, nicht doppelt angelegt.
     */
    public function import(): void
    {
        $data = $this->validate([
            'importIds' => ['required', 'string'],
            'importFeatureKey' => ['required', Rule::in(CustomerFeatureOverride::getFeatureKeys())],
            'importEnabled' => ['boolean'],
            'importNote' => ['nullable', 'string', 'max:255'],
            'importApplyNow' => ['boolean'],
        ], [
            'importIds.required' => 'Bitte mindestens eine Account-ID eingeben.',
            'importFeatureKey.required' => 'Bitte ein Feature wählen.',
            'importFeatureKey.in' => 'Bitte ein Feature wählen.',
            'importNote.max' => 'Die Notiz darf höchstens 255 Zeichen lang sein.',
        ]);

        $ids = self::parseIds($data['importIds']);

        if ($ids === []) {
            $this->addError('importIds', 'Keine gültigen IDs erkannt.');

            return;
        }

        $service = app(CustomerFeaturePreauthorizationService::class);
        $recorded = $service->record(
            $data['importFeatureKey'],
            $ids,
            $data['importEnabled'],
            filled($data['importNote']) ? trim($data['importNote']) : null,
        );

        $withAccount = Customer::query()->whereIn('pds_account_id', $ids)->count();
        $message = "Import abgeschlossen: {$recorded} Account-IDs vorgemerkt, davon {$withAccount} mit bestehendem Konto.";

        if ($data['importApplyNow']) {
            $result = $service->applyToExistingCustomers($data['importFeatureKey'], $ids);
            $message .= " {$result['customers']} Konten sofort angepasst.";
        }

        $this->reset(['importIds', 'importFeatureKey', 'importEnabled', 'importNote', 'importApplyNow']);
        $this->resetPage();
        unset($this->rows, $this->openCount);

        $this->modal('preauthorization-import')->close();
        $this->dispatch('adminv2-toast', message: $message);
    }

    /**
     * Alle Vormerkungen auf Konten anwenden, die es bereits gibt.
     */
    public function applyPending(): void
    {
        $result = app(CustomerFeaturePreauthorizationService::class)->applyToExistingCustomers();

        unset($this->rows, $this->openCount);

        $this->modal('preauthorization-apply-pending')->close();
        $this->dispatch(
            'adminv2-toast',
            message: ($result['customers'] > 0 ? 'Angewendet: ' : 'Nichts zu tun: ')."{$result['customers']} Konten angepasst ({$result['applied']} Freischaltungen).",
        );
    }

    /**
     * Eine Vormerkung sofort auf die bestehenden Konten ihrer Account-ID anwenden.
     */
    public function apply(int $preauthorizationId): void
    {
        $record = CustomerFeaturePreauthorization::findOrFail($preauthorizationId);

        $result = app(CustomerFeaturePreauthorizationService::class)
            ->applyToExistingCustomers($record->feature_key, [$record->pds_account_id]);

        unset($this->rows, $this->openCount);

        $this->notifyResult($result);
    }

    /**
     * Die ausgewaehlten Vormerkungen auf bestehende Konten anwenden.
     */
    public function applySelected(): void
    {
        $records = CustomerFeaturePreauthorization::query()->whereIn('id', $this->selectedIds())->get();
        $service = app(CustomerFeaturePreauthorizationService::class);
        $total = ['customers' => 0, 'applied' => 0];

        // Nach Feature gruppieren, weil der Service je Aufruf genau einen
        // Feature-Key filtert.
        foreach ($records->groupBy('feature_key') as $featureKey => $group) {
            $result = $service->applyToExistingCustomers($featureKey, $group->pluck('pds_account_id')->all());

            $total['customers'] += $result['customers'];
            $total['applied'] += $result['applied'];
        }

        $this->selected = [];
        unset($this->rows, $this->openCount);

        $this->modal('preauthorization-apply-selected')->close();
        $this->notifyResult($total);
    }

    /**
     * Loescht nur die Vormerkungen – bereits erteilte Freischaltungen bleiben bestehen.
     */
    public function deleteSelected(): void
    {
        $deleted = CustomerFeaturePreauthorization::query()->whereIn('id', $this->selectedIds())->delete();

        $this->selected = [];
        unset($this->rows, $this->openCount);

        $this->modal('preauthorization-delete-selected')->close();
        $this->dispatch(
            'adminv2-toast',
            message: $deleted === 1
                ? '1 Vormerkung gelöscht. Bereits erteilte Freischaltungen bleiben bestehen.'
                : "{$deleted} Vormerkungen gelöscht. Bereits erteilte Freischaltungen bleiben bestehen.",
        );
    }

    /**
     * @param  array{customers: int, applied: int}  $result
     */
    protected function notifyResult(array $result): void
    {
        $this->dispatch(
            'adminv2-toast',
            message: $result['customers'] === 0
                ? 'Nichts zu tun: Alle betroffenen Konten haben bereits einen gesetzten Wert oder es gibt noch keine Konten.'
                : "Angewendet: {$result['customers']} Konten angepasst ({$result['applied']} Freischaltungen).",
        );
    }

    /**
     * Eine ID pro Zeile oder durch Komma/Semikolon getrennt; alles, was keine
     * positive Zahl ist, wird ignoriert.
     *
     * @return array<int, int>
     */
    public static function parseIds(string $raw): array
    {
        return collect(preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->filter(fn (string $value) => ctype_digit($value))
            ->map(fn (string $value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.admin-v2.customer-management.feature-preauthorizations.index');
    }
}
