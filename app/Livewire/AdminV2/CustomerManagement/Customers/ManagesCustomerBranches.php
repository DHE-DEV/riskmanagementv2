<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Filialen eines Kunden auf der Kundenseite: Liste mit Suche, Filter und
 * Sortierung, Anlegen und Bearbeiten im Dialog, Loeschen einzeln und gesammelt.
 */
trait ManagesCustomerBranches
{
    public string $branchSearch = '';

    /** '' = alle Filialen, 'yes' = nur Hauptsitz, 'no' = keine Hauptsitze */
    public string $branchHeadquarters = '';

    public string $branchSort = 'name';

    public string $branchDirection = 'asc';

    /** @var array<int, string> IDs der angehakten Filialen */
    public array $selectedBranches = [];

    /** Filiale im Dialog; null = neue Filiale. */
    #[Locked]
    public ?int $editingBranchId = null;

    #[Locked]
    public string $branchAppCode = '';

    /** @var array<string, mixed> */
    public array $branchForm = [];

    /**
     * @return array<string, string>
     */
    public function branchSortOptions(): array
    {
        return [
            'app_code' => 'App-Code',
            'name' => 'Filialname',
            'postal_code' => 'PLZ',
            'city' => 'Stadt',
            'is_headquarters' => 'Hauptsitz',
            'created_at' => 'Erstellt am',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyBranchForm(): array
    {
        return [
            'name' => '',
            'additional' => '',
            'is_headquarters' => false,
            'street' => '',
            'house_number' => '',
            'postal_code' => '',
            'city' => '',
            'country' => 'Deutschland',
            'latitude' => '',
            'longitude' => '',
        ];
    }

    public function updatedBranchSearch(): void
    {
        $this->resetPage('branchesPage');
    }

    public function updatedBranchHeadquarters(): void
    {
        $this->resetPage('branchesPage');
    }

    public function sortBranches(string $column): void
    {
        if (! array_key_exists($column, $this->branchSortOptions())) {
            return;
        }

        $this->branchDirection = $this->branchSort === $column && $this->branchDirection === 'asc' ? 'desc' : 'asc';
        $this->branchSort = $column;
        $this->resetPage('branchesPage');
    }

    #[Computed]
    public function branches(): LengthAwarePaginator
    {
        $query = $this->customer->branches();

        if (($term = trim($this->branchSearch)) !== '') {
            $this->whereEveryWord($query, $term, ['app_code', 'name', 'street', 'house_number', 'postal_code', 'city']);
        }

        match ($this->branchHeadquarters) {
            'yes' => $query->where('is_headquarters', true),
            'no' => $query->where('is_headquarters', false),
            default => null,
        };

        $sort = array_key_exists($this->branchSort, $this->branchSortOptions()) ? $this->branchSort : 'name';

        $query
            ->orderBy($sort, $this->branchDirection === 'desc' ? 'desc' : 'asc')
            ->orderBy('id');

        return $this->paginateWithinRange($query, self::RELATION_PER_PAGE, 'branchesPage');
    }

    public function createBranch(): void
    {
        $this->editingBranchId = null;
        $this->branchAppCode = '';
        $this->branchForm = $this->emptyBranchForm();
        $this->resetErrorBag();

        $this->modal('customer-branch')->show();
    }

    public function editBranch(int $branchId): void
    {
        $branch = $this->customer->branches()->findOrFail($branchId);

        $this->editingBranchId = $branch->id;
        $this->branchAppCode = (string) $branch->app_code;
        $this->branchForm = [
            'name' => (string) $branch->name,
            'additional' => (string) $branch->additional,
            'is_headquarters' => (bool) $branch->is_headquarters,
            'street' => (string) $branch->street,
            'house_number' => (string) $branch->house_number,
            'postal_code' => (string) $branch->postal_code,
            'city' => (string) $branch->city,
            'country' => (string) $branch->country,
            // Ohne die angehaengten Nullen der Dezimalspalte.
            'latitude' => $branch->latitude === null ? '' : (string) (float) $branch->latitude,
            'longitude' => $branch->longitude === null ? '' : (string) (float) $branch->longitude,
        ];
        $this->resetErrorBag();

        $this->modal('customer-branch')->show();
    }

    public function saveBranch(): void
    {
        $this->validate([
            'branchForm.name' => ['required', 'string', 'max:255'],
            'branchForm.additional' => ['nullable', 'string', 'max:255'],
            'branchForm.is_headquarters' => ['boolean'],
            'branchForm.street' => ['required', 'string', 'max:255'],
            'branchForm.house_number' => ['required', 'string', 'max:20'],
            'branchForm.postal_code' => ['required', 'string', 'max:20'],
            'branchForm.city' => ['required', 'string', 'max:255'],
            'branchForm.country' => ['required', 'string', 'max:255'],
            'branchForm.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'branchForm.longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'branchForm.name.required' => 'Bitte einen Filialnamen eingeben.',
            'branchForm.street.required' => 'Bitte die Straße eingeben.',
            'branchForm.house_number.required' => 'Bitte die Hausnummer eingeben.',
            'branchForm.postal_code.required' => 'Bitte die PLZ eingeben.',
            'branchForm.city.required' => 'Bitte die Stadt eingeben.',
            'branchForm.country.required' => 'Bitte das Land eingeben.',
            'branchForm.latitude.numeric' => 'Breitengrad als Zahl angeben, z. B. 50.9375.',
            'branchForm.latitude.between' => 'Der Breitengrad liegt zwischen -90 und 90.',
            'branchForm.longitude.numeric' => 'Längengrad als Zahl angeben, z. B. 6.9603.',
            'branchForm.longitude.between' => 'Der Längengrad liegt zwischen -180 und 180.',
        ]);

        $text = fn ($value) => trim((string) $value) === '' ? null : trim((string) $value);
        $number = fn ($value) => is_numeric($value) ? (float) $value : null;

        $branch = $this->editingBranchId
            ? $this->customer->branches()->findOrFail($this->editingBranchId)
            : new Branch(['customer_id' => $this->customer->id]);
        $created = ! $branch->exists;

        // Der App-Code wird beim Anlegen automatisch vergeben.
        $branch->fill([
            'name' => trim($this->branchForm['name']),
            'additional' => $text($this->branchForm['additional']),
            'is_headquarters' => (bool) $this->branchForm['is_headquarters'],
            'street' => $text($this->branchForm['street']),
            'house_number' => $text($this->branchForm['house_number']),
            'postal_code' => $text($this->branchForm['postal_code']),
            'city' => $text($this->branchForm['city']),
            'country' => $text($this->branchForm['country']),
            'latitude' => $number($this->branchForm['latitude']),
            'longitude' => $number($this->branchForm['longitude']),
        ])->save();

        $this->modal('customer-branch')->close();
        $this->editingBranchId = null;
        unset($this->branches);

        $this->dispatch('adminv2-toast', message: $created ? 'Filiale „'.$branch->name.'“ angelegt.' : 'Filiale „'.$branch->name.'“ gespeichert.');
    }

    public function deleteBranch(int $branchId): void
    {
        $branch = $this->customer->branches()->findOrFail($branchId);
        $branch->delete();

        $this->selectedBranches = array_values(array_diff($this->selectedBranches, [(string) $branchId]));
        unset($this->branches);

        $this->dispatch('adminv2-toast', message: 'Filiale „'.$branch->name.'“ gelöscht.');
    }

    /**
     * Die angehakten Filialen loeschen – einzeln, damit der BranchObserver laeuft.
     */
    public function deleteSelectedBranches(): void
    {
        $branches = $this->customer->branches()->whereKey(array_map('intval', $this->selectedBranches))->get();
        $branches->each->delete();

        $this->selectedBranches = [];
        unset($this->branches);

        $this->dispatch('adminv2-toast', message: $branches->count().' '.($branches->count() === 1 ? 'Filiale' : 'Filialen').' gelöscht.');
    }
}
