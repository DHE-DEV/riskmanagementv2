<?php

namespace App\Livewire\AdminV2\System\Products;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\Product;
use App\Models\ProductFeature;
use App\Models\ProductFeatureValue;
use App\Models\ProductVersion;
use App\Models\ProductVersionPrice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Ein Produkt bzw. Feature anlegen oder bearbeiten: Stammdaten, Auspraegungen
 * (Allgemein oder z. B. Standard/Premium) mit Preisen ab Stichtag oder
 * kostenlos, und der Leistungsumfang je Auspraegung fuer die Gegenueberstellung.
 *
 * Auspraegungen und Leistungspunkte tragen eine "uid", damit neue Eintraege
 * schon vor dem Speichern in der Matrix angesprochen werden koennen.
 */
#[Layout('components.layouts.adminv2.app')]
class Editor extends Component
{
    use AuthorizesAdminV2;

    protected const AMOUNT_RULE = ['nullable', 'regex:/^\d{1,8}([.,]\d{1,2})?$/'];

    #[Locked]
    public ?int $productId = null;

    public string $name = '';

    public string $key = '';

    public string $subtitle = '';

    public string $description = '';

    public string $requiresProductId = '';

    /** Ausprägung des vorausgesetzten Produkts (leer = beliebige) */
    public string $requiresVersionId = '';

    public string $priceBasis = 'per_location';

    public bool $isActive = true;

    public string $sortOrder = '0';

    /** @var list<array{uid: string, id: ?int, name: string, is_active: bool, prices: list<array<string, mixed>>}> */
    public array $versions = [];

    /** @var list<array{uid: string, id: ?int, name: string, description: string, values: array<string, array{included: bool, note: string}>}> */
    public array $features = [];

    public function mount($product = null): void
    {
        if ($product === null) {
            $this->versions = [$this->blankVersion('Allgemein')];

            return;
        }

        $model = Product::with(['versions.prices', 'features.values'])->findOrFail((int) $product);

        $this->productId = $model->id;
        $this->name = $model->name;
        $this->key = (string) $model->key;
        $this->subtitle = (string) $model->subtitle;
        $this->description = (string) $model->description;
        $this->requiresProductId = (string) ($model->requires_product_id ?? '');
        $this->requiresVersionId = (string) ($model->requires_product_version_id ?? '');
        $this->priceBasis = $model->price_basis ?: 'per_location';
        $this->isActive = (bool) $model->is_active;
        $this->sortOrder = (string) $model->sort_order;

        $uidById = [];
        foreach ($model->versions as $version) {
            $uidById[$version->id] = $uid = 'v'.$version->id;
            $this->versions[] = [
                'uid' => $uid,
                'id' => $version->id,
                'name' => $version->name,
                'is_active' => (bool) $version->is_active,
                'prices' => $version->prices->map(fn (ProductVersionPrice $price) => [
                    'id' => $price->id,
                    'valid_from' => $price->valid_from->toDateString(),
                    'is_free' => $price->is_free,
                    ...array_map(fn ($column) => $price->{$column} !== null ? str_replace('.', ',', (string) $price->{$column}) : '', array_combine(array_keys(ProductVersionPrice::AMOUNTS), array_keys(ProductVersionPrice::AMOUNTS))),
                ])->values()->all(),
            ];
        }

        if ($this->versions === []) {
            $this->versions = [$this->blankVersion('Allgemein')];
        }

        foreach ($model->features as $feature) {
            $values = [];
            foreach ($this->versions as $version) {
                $value = $feature->values->firstWhere('product_version_id', $version['id']);
                $values[$version['uid']] = ['included' => (bool) $value?->included, 'note' => (string) $value?->note];
            }

            $this->features[] = [
                'uid' => 'f'.$feature->id,
                'id' => $feature->id,
                'name' => $feature->name,
                'description' => (string) $feature->description,
                'values' => $values,
            ];
        }
    }

    #[Computed]
    public function product(): ?Product
    {
        return $this->productId ? Product::find($this->productId) : null;
    }

    /** Andere Produkte als moegliche Voraussetzung */
    #[Computed]
    public function requirementOptions(): Collection
    {
        return Product::query()->when($this->productId, fn ($q) => $q->whereKeyNot($this->productId))->ordered()->get(['id', 'name']);
    }

    /** Auspraegungen des vorausgesetzten Produkts */
    #[Computed]
    public function requiredVersionOptions(): Collection
    {
        return $this->requiresProductId !== ''
            ? ProductVersion::where('product_id', (int) $this->requiresProductId)->orderBy('sort_order')->orderBy('id')->get(['id', 'name'])
            : collect();
    }

    /** Anderes vorausgesetztes Produkt: Auspraegung neu waehlen. */
    public function updatedRequiresProductId(): void
    {
        $this->requiresVersionId = '';
        unset($this->requiredVersionOptions);
    }

    public function addVersion(): void
    {
        $names = array_column($this->versions, 'name');
        $suggestion = ! in_array('Standard', $names, true) ? 'Standard' : (! in_array('Premium', $names, true) ? 'Premium' : '');

        // Aus „Allgemein“ wird beim Aufteilen „Standard“
        if (count($this->versions) === 1 && $this->versions[0]['name'] === 'Allgemein') {
            $this->versions[0]['name'] = 'Standard';
            $suggestion = 'Premium';
        }

        $version = $this->blankVersion($suggestion);
        $this->versions[] = $version;

        foreach ($this->features as $index => $feature) {
            $this->features[$index]['values'][$version['uid']] = ['included' => false, 'note' => ''];
        }
    }

    public function removeVersion(string $uid): void
    {
        if (count($this->versions) <= 1) {
            return;
        }

        $this->versions = array_values(array_filter($this->versions, fn ($version) => $version['uid'] !== $uid));

        foreach ($this->features as $index => $feature) {
            unset($this->features[$index]['values'][$uid]);
        }
    }

    public function addPrice(int $versionIndex): void
    {
        $this->versions[$versionIndex]['prices'] ??= [];
        array_unshift($this->versions[$versionIndex]['prices'], $this->blankPrice());
    }

    public function removePrice(int $versionIndex, int $priceIndex): void
    {
        unset($this->versions[$versionIndex]['prices'][$priceIndex]);
        $this->versions[$versionIndex]['prices'] = array_values($this->versions[$versionIndex]['prices']);
    }

    public function addFeature(): void
    {
        $values = [];
        foreach ($this->versions as $version) {
            $values[$version['uid']] = ['included' => true, 'note' => ''];
        }

        $this->features[] = ['uid' => 'f'.Str::random(8), 'id' => null, 'name' => '', 'description' => '', 'values' => $values];
    }

    public function removeFeature(string $uid): void
    {
        $this->features = array_values(array_filter($this->features, fn ($feature) => $feature['uid'] !== $uid));
    }

    public function moveFeature(int $index, int $direction): void
    {
        $target = $index + $direction;
        if (! isset($this->features[$index], $this->features[$target])) {
            return;
        }

        [$this->features[$index], $this->features[$target]] = [$this->features[$target], $this->features[$index]];
    }

    public function save()
    {
        $amountRules = [];
        foreach (array_keys(ProductVersionPrice::AMOUNTS) as $column) {
            $amountRules['versions.*.prices.*.'.$column] = self::AMOUNT_RULE;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'alpha_dash', 'max:50', Rule::unique('products', 'key')->ignore($this->productId)],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'requiresProductId' => ['nullable', Rule::exists('products', 'id')],
            'requiresVersionId' => ['nullable', Rule::exists('product_versions', 'id')->where('product_id', (int) $this->requiresProductId)],
            'priceBasis' => ['required', Rule::in(array_keys(Product::PRICE_BASES))],
            'sortOrder' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'versions' => ['required', 'array', 'min:1'],
            'versions.*.name' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'versions.*.prices.*.valid_from' => ['required', 'date'],
            ...$amountRules,
            'features.*.name' => ['required', 'string', 'max:255'],
            'features.*.description' => ['nullable', 'string', 'max:2000'],
            'features.*.values.*.note' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Bitte den Namen des Produkts eingeben.',
            'key.alpha_dash' => 'Das Kürzel besteht aus Buchstaben, Ziffern, - und _ (z. B. travel_alert).',
            'key.unique' => 'Dieses Kürzel hat schon ein anderes Produkt.',
            'versions.*.name.required' => 'Bitte die Ausprägung benennen (z. B. Standard).',
            'versions.*.name.distinct' => 'Zwei Ausprägungen heißen gleich.',
            'versions.*.prices.*.valid_from.required' => 'Ab wann gilt der Preis?',
            'versions.*.prices.*.*.regex' => 'Betrag mit höchstens zwei Nachkommastellen, z. B. 9,90.',
            'features.*.name.required' => 'Bitte den Leistungspunkt benennen.',
        ]);

        // Je Auspraegung nur ein Preisstand pro Stichtag
        foreach ($this->versions as $versionIndex => $version) {
            $dates = array_column($version['prices'] ?? [], 'valid_from');
            foreach (array_diff_assoc($dates, array_unique($dates)) as $priceIndex => $date) {
                $this->addError("versions.$versionIndex.prices.$priceIndex.valid_from", 'Für diesen Tag gibt es schon einen Preis.');

                return null;
            }
        }

        $amount = fn ($value) => trim((string) $value) === '' ? null : str_replace(',', '.', trim((string) $value));

        $product = DB::transaction(function () use ($amount) {
            $product = $this->product ?? new Product(['currency' => 'EUR']);
            $product->fill([
                'name' => trim($this->name),
                'key' => trim($this->key) ?: null,
                'subtitle' => trim($this->subtitle) ?: null,
                'description' => trim($this->description) ?: null,
                'requires_product_id' => $this->requiresProductId !== '' ? (int) $this->requiresProductId : null,
                'requires_product_version_id' => $this->requiresProductId !== '' && $this->requiresVersionId !== '' ? (int) $this->requiresVersionId : null,
                'price_basis' => $this->priceBasis,
                'is_active' => $this->isActive,
                'sort_order' => (int) ($this->sortOrder ?: 0),
            ])->save();

            // Auspraegungen mit Preisen
            $versionIds = [];
            foreach ($this->versions as $sort => $data) {
                $version = $data['id'] ? $product->versions()->findOrFail($data['id']) : new ProductVersion(['product_id' => $product->id, 'currency' => 'EUR']);
                $version->fill(['name' => trim($data['name']), 'is_active' => (bool) $data['is_active'], 'sort_order' => $sort])->save();
                $versionIds[$data['uid']] = $version->id;

                $priceIds = [];
                foreach ($data['prices'] ?? [] as $priceData) {
                    $price = $priceData['id'] ? $version->prices()->findOrFail($priceData['id']) : new ProductVersionPrice(['product_version_id' => $version->id]);
                    $price->fill([
                        'valid_from' => $priceData['valid_from'],
                        'is_free' => (bool) $priceData['is_free'],
                        ...array_map($amount, array_intersect_key($priceData, ProductVersionPrice::AMOUNTS)),
                    ])->save();
                    $priceIds[] = $price->id;
                }
                $version->prices()->whereNotIn('id', $priceIds)->delete();
            }
            $product->versions()->whereNotIn('id', $versionIds)->delete();

            // Leistungsumfang je Auspraegung
            $featureIds = [];
            foreach ($this->features as $sort => $data) {
                $feature = $data['id'] ? $product->features()->findOrFail($data['id']) : new ProductFeature(['product_id' => $product->id]);
                $feature->fill(['name' => trim($data['name']), 'description' => trim((string) $data['description']) ?: null, 'sort_order' => $sort])->save();
                $featureIds[] = $feature->id;

                foreach ($versionIds as $uid => $versionId) {
                    $value = $data['values'][$uid] ?? ['included' => false, 'note' => ''];
                    ProductFeatureValue::updateOrCreate(
                        ['product_feature_id' => $feature->id, 'product_version_id' => $versionId],
                        ['included' => (bool) $value['included'], 'note' => trim((string) $value['note']) ?: null],
                    );
                }
            }
            $product->features()->whereNotIn('id', $featureIds)->delete();

            return $product;
        });

        session()->flash('adminv2-toast', '„'.$product->name.'“ gespeichert.');

        return $this->redirectRoute('adminv2.system.products.edit', $product);
    }

    public function render()
    {
        return view('livewire.admin-v2.system.products.editor')
            ->title($this->product ? $this->product->name : 'Neues Produkt');
    }

    /** @return array{uid: string, id: null, name: string, is_active: bool, prices: list<array<string, mixed>>} */
    protected function blankVersion(string $name): array
    {
        return ['uid' => 'v'.Str::random(8), 'id' => null, 'name' => $name, 'is_active' => true, 'prices' => []];
    }

    /** @return array<string, mixed> */
    protected function blankPrice(): array
    {
        return [
            'id' => null,
            'valid_from' => today()->startOfMonth()->addMonth()->toDateString(),
            'is_free' => false,
            ...array_fill_keys(array_keys(ProductVersionPrice::AMOUNTS), ''),
        ];
    }
}
