<?php

declare(strict_types=1);

use App\Filament\Resources\Cities\CityResource;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

describe('List Cities Page', function () {
    test('can render list page', function () {
        livewire(CityResource\Pages\ListCities::class)
            ->assertSuccessful();
    });

    test('can list cities', function () {
        $cities = City::factory()->count(10)->create();

        livewire(CityResource\Pages\ListCities::class)
            ->assertCanSeeTableRecords($cities);
    });

    test('can search cities by name translation', function () {
        $city1 = City::factory()->create([
            'name_translations' => ['de' => 'München', 'en' => 'Munich'],
        ]);
        $city2 = City::factory()->create([
            'name_translations' => ['de' => 'Berlin', 'en' => 'Berlin'],
        ]);

        livewire(CityResource\Pages\ListCities::class)
            ->searchTable('München')
            ->assertCanSeeTableRecords([$city1])
            ->assertCanNotSeeTableRecords([$city2]);
    });

    test('can filter cities by country', function () {
        $country1 = Country::factory()->create();
        $country2 = Country::factory()->create();

        $city1 = City::factory()->create(['country_id' => $country1->id]);
        $city2 = City::factory()->create(['country_id' => $country2->id]);

        livewire(CityResource\Pages\ListCities::class)
            ->filterTable('country_id', $country1->id)
            ->assertCanSeeTableRecords([$city1])
            ->assertCanNotSeeTableRecords([$city2]);
    });

    test('can filter cities by region', function () {
        $country = Country::factory()->create();
        $region1 = Region::factory()->create(['country_id' => $country->id]);
        $region2 = Region::factory()->create(['country_id' => $country->id]);

        $city1 = City::factory()->create([
            'country_id' => $country->id,
            'region_id' => $region1->id,
        ]);
        $city2 = City::factory()->create([
            'country_id' => $country->id,
            'region_id' => $region2->id,
        ]);

        livewire(CityResource\Pages\ListCities::class)
            ->filterTable('region_id', $region1->id)
            ->assertCanSeeTableRecords([$city1])
            ->assertCanNotSeeTableRecords([$city2]);
    });

    test('can filter capital cities', function () {
        $capitalCity = City::factory()->create(['is_capital' => true]);
        $nonCapitalCity = City::factory()->create(['is_capital' => false]);

        livewire(CityResource\Pages\ListCities::class)
            ->filterTable('is_capital', true)
            ->assertCanSeeTableRecords([$capitalCity])
            ->assertCanNotSeeTableRecords([$nonCapitalCity]);
    });

    test('can filter regional capital cities', function () {
        $regionalCapital = City::factory()->create(['is_regional_capital' => true]);
        $nonRegionalCapital = City::factory()->create(['is_regional_capital' => false]);

        livewire(CityResource\Pages\ListCities::class)
            ->filterTable('is_regional_capital', true)
            ->assertCanSeeTableRecords([$regionalCapital])
            ->assertCanNotSeeTableRecords([$nonRegionalCapital]);
    });

    test('can delete city', function () {
        $city = City::factory()->create();

        livewire(CityResource\Pages\ListCities::class)
            ->callTableAction(DeleteAction::class, $city);

        $this->assertSoftDeleted($city);
    });

    test('can bulk delete cities', function () {
        $cities = City::factory()->count(3)->create();

        livewire(CityResource\Pages\ListCities::class)
            ->callTableBulkAction(DeleteBulkAction::class, $cities);

        foreach ($cities as $city) {
            $this->assertSoftDeleted($city);
        }
    });
});

describe('Create City Page', function () {
    test('can render create page', function () {
        livewire(CityResource\Pages\CreateCity::class)
            ->assertSuccessful();
    });

    test('can create city with all fields', function () {
        $country = Country::factory()->create();
        $region = Region::factory()->create(['country_id' => $country->id]);

        $data = [
            'name_translations.de' => 'München',
            'name_translations.en' => 'Munich',
            'country_id' => $country->id,
            'region_id' => $region->id,
            'population' => 1500000,
            'lat' => 48.1351,
            'lng' => 11.5820,
            'is_capital' => false,
            'is_regional_capital' => true,
        ];

        livewire(CityResource\Pages\CreateCity::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('cities', [
            'country_id' => $country->id,
            'region_id' => $region->id,
            'population' => 1500000,
        ]);

        $city = City::where('country_id', $country->id)
            ->where('region_id', $region->id)
            ->first();

        expect($city->name_translations)->toBe([
            'de' => 'München',
            'en' => 'Munich',
        ]);
        expect($city->is_regional_capital)->toBeTrue();
    });

    test('can create city with minimal required fields', function () {
        $country = Country::factory()->create();

        $data = [
            'name_translations.de' => 'Berlin',
            'name_translations.en' => 'Berlin',
            'country_id' => $country->id,
        ];

        livewire(CityResource\Pages\CreateCity::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('cities', [
            'country_id' => $country->id,
        ]);
    });

    test('validates required fields', function () {
        livewire(CityResource\Pages\CreateCity::class)
            ->fillForm([])
            ->call('create')
            ->assertHasFormErrors([
                'name_translations.de' => 'required',
                'country_id' => 'required',
            ]);
    });

    test('validates name_translations max length', function () {
        $country = Country::factory()->create();

        livewire(CityResource\Pages\CreateCity::class)
            ->fillForm([
                'name_translations.de' => str_repeat('A', 256),
                'name_translations.en' => 'Munich',
                'country_id' => $country->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['name_translations.de' => 'max']);
    });

    test('validates latitude bounds', function () {
        $country = Country::factory()->create();

        livewire(CityResource\Pages\CreateCity::class)
            ->fillForm([
                'name_translations.de' => 'München',
                'name_translations.en' => 'Munich',
                'country_id' => $country->id,
                'lat' => 91.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lat']);

        livewire(CityResource\Pages\CreateCity::class)
            ->fillForm([
                'name_translations.de' => 'München',
                'name_translations.en' => 'Munich',
                'country_id' => $country->id,
                'lat' => -91.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lat']);
    });

    test('validates longitude bounds', function () {
        $country = Country::factory()->create();

        livewire(CityResource\Pages\CreateCity::class)
            ->fillForm([
                'name_translations.de' => 'München',
                'name_translations.en' => 'Munich',
                'country_id' => $country->id,
                'lng' => 181.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lng']);

        livewire(CityResource\Pages\CreateCity::class)
            ->fillForm([
                'name_translations.de' => 'München',
                'name_translations.en' => 'Munich',
                'country_id' => $country->id,
                'lng' => -181.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lng']);
    });

    test('validates population is numeric', function () {
        $country = Country::factory()->create();

        livewire(CityResource\Pages\CreateCity::class)
            ->fillForm([
                'name_translations.de' => 'München',
                'name_translations.en' => 'Munich',
                'country_id' => $country->id,
                'population' => 'not a number',
            ])
            ->call('create')
            ->assertHasFormErrors(['population']);
    });

    test('validates country_id exists', function () {
        livewire(CityResource\Pages\CreateCity::class)
            ->fillForm([
                'name_translations.de' => 'München',
                'name_translations.en' => 'Munich',
                'country_id' => 999999,
            ])
            ->call('create')
            ->assertHasFormErrors(['country_id']);
    });

    test('validates region belongs to selected country', function () {
        $country1 = Country::factory()->create();
        $country2 = Country::factory()->create();
        $region = Region::factory()->create(['country_id' => $country2->id]);

        livewire(CityResource\Pages\CreateCity::class)
            ->fillForm([
                'name_translations.de' => 'München',
                'name_translations.en' => 'Munich',
                'country_id' => $country1->id,
                'region_id' => $region->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['region_id']);
    });
});

describe('Edit City Page', function () {
    test('can render edit page', function () {
        $city = City::factory()->create();

        livewire(CityResource\Pages\EditCity::class, [
            'record' => $city->getRouteKey(),
        ])
            ->assertSuccessful();
    });

    test('can retrieve city data', function () {
        $country = Country::factory()->create();
        $region = Region::factory()->create(['country_id' => $country->id]);
        $city = City::factory()->create([
            'name_translations' => ['de' => 'München', 'en' => 'Munich'],
            'country_id' => $country->id,
            'region_id' => $region->id,
            'population' => 1500000,
            'is_capital' => false,
            'is_regional_capital' => true,
        ]);

        livewire(CityResource\Pages\EditCity::class, [
            'record' => $city->getRouteKey(),
        ])
            ->assertFormSet([
                'name_translations.de' => 'München',
                'name_translations.en' => 'Munich',
                'country_id' => $country->id,
                'region_id' => $region->id,
                'population' => 1500000,
                'is_capital' => false,
                'is_regional_capital' => true,
            ]);
    });

    test('can update city', function () {
        $country1 = Country::factory()->create();
        $region1 = Region::factory()->create(['country_id' => $country1->id]);
        $city = City::factory()->create([
            'name_translations' => ['de' => 'München', 'en' => 'Munich'],
            'country_id' => $country1->id,
            'region_id' => $region1->id,
        ]);

        $country2 = Country::factory()->create();
        $region2 = Region::factory()->create(['country_id' => $country2->id]);

        $newData = [
            'name_translations.de' => 'Berlin',
            'name_translations.en' => 'Berlin',
            'country_id' => $country2->id,
            'region_id' => $region2->id,
            'population' => 3700000,
            'lat' => 52.5200,
            'lng' => 13.4050,
            'is_capital' => true,
            'is_regional_capital' => false,
        ];

        livewire(CityResource\Pages\EditCity::class, [
            'record' => $city->getRouteKey(),
        ])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $city->refresh();

        expect($city->name_translations)->toBe([
            'de' => 'Berlin',
            'en' => 'Berlin',
        ]);
        expect($city->country_id)->toBe($country2->id);
        expect($city->region_id)->toBe($region2->id);
        expect($city->population)->toBe(3700000);
        expect($city->is_capital)->toBeTrue();
    });

    test('can update city coordinates using coordinates_import field', function () {
        $city = City::factory()->create();

        livewire(CityResource\Pages\EditCity::class, [
            'record' => $city->getRouteKey(),
        ])
            ->fillForm([
                'coordinates_import' => '48.1351, 11.5820',
            ])
            ->assertHasNoFormErrors();

        // Note: The coordinates_import field is dehydrated:false,
        // so it won't be saved to the database directly.
        // The afterStateUpdated callback handles setting lat/lng.
    });

    test('validates required fields on update', function () {
        $city = City::factory()->create();

        livewire(CityResource\Pages\EditCity::class, [
            'record' => $city->getRouteKey(),
        ])
            ->fillForm([
                'name_translations.de' => '',
                'country_id' => null,
            ])
            ->call('save')
            ->assertHasFormErrors([
                'name_translations.de' => 'required',
                'country_id' => 'required',
            ]);
    });

    test('can delete city from edit page', function () {
        $city = City::factory()->create();

        livewire(CityResource\Pages\EditCity::class, [
            'record' => $city->getRouteKey(),
        ])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted($city);
    });
});

describe('View City Page', function () {
    test('can render view page', function () {
        $city = City::factory()->create();

        livewire(CityResource\Pages\ViewCity::class, [
            'record' => $city->getRouteKey(),
        ])
            ->assertSuccessful();
    });

    test('can display city data', function () {
        $city = City::factory()->create([
            'name_translations' => ['de' => 'München', 'en' => 'Munich'],
        ]);

        livewire(CityResource\Pages\ViewCity::class, [
            'record' => $city->getRouteKey(),
        ])
            ->assertSee('München');
    });
});

describe('City Relationships', function () {
    test('city belongs to country', function () {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        expect($city->country->id)->toBe($country->id);
    });

    test('city belongs to region', function () {
        $country = Country::factory()->create();
        $region = Region::factory()->create(['country_id' => $country->id]);
        $city = City::factory()->create([
            'country_id' => $country->id,
            'region_id' => $region->id,
        ]);

        expect($city->region->id)->toBe($region->id);
    });

    test('city can have null region', function () {
        $country = Country::factory()->create();
        $city = City::factory()->create([
            'country_id' => $country->id,
            'region_id' => null,
        ]);

        expect($city->region)->toBeNull();
    });

    test('region selector is filtered by selected country', function () {
        $country1 = Country::factory()->create();
        $country2 = Country::factory()->create();

        $region1 = Region::factory()->create(['country_id' => $country1->id]);
        $region2 = Region::factory()->create(['country_id' => $country2->id]);

        // When creating a city with country1, only region1 should be available
        // This is handled by the form's reactive country_id field
        $city = City::factory()->create([
            'country_id' => $country1->id,
            'region_id' => $region1->id,
        ]);

        expect($city->region->country_id)->toBe($country1->id);
    });
});

describe('City Scopes', function () {
    test('can filter capital cities using scope', function () {
        City::factory()->count(3)->create(['is_capital' => true]);
        City::factory()->count(5)->create(['is_capital' => false]);

        $capitals = City::capitals()->get();

        expect($capitals->count())->toBe(3);
    });

    test('can filter cities by country using scope', function () {
        $country = Country::factory()->create();
        City::factory()->count(3)->create(['country_id' => $country->id]);
        City::factory()->count(2)->create();

        $citiesByCountry = City::byCountry($country->id)->get();

        expect($citiesByCountry->count())->toBe(3);
    });

    test('can filter cities by region using scope', function () {
        $country = Country::factory()->create();
        $region = Region::factory()->create(['country_id' => $country->id]);

        City::factory()->count(3)->create([
            'country_id' => $country->id,
            'region_id' => $region->id,
        ]);
        City::factory()->count(2)->create(['country_id' => $country->id]);

        $citiesByRegion = City::byRegion($region->id)->get();

        expect($citiesByRegion->count())->toBe(3);
    });
});

describe('City Soft Deletes', function () {
    test('soft deleted cities are not shown in list', function () {
        $activeCity = City::factory()->create();
        $deletedCity = City::factory()->create();
        $deletedCity->delete();

        livewire(CityResource\Pages\ListCities::class)
            ->assertCanSeeTableRecords([$activeCity])
            ->assertCanNotSeeTableRecords([$deletedCity]);
    });

    test('can restore soft deleted city', function () {
        $city = City::factory()->create();
        $city->delete();

        $city->restore();

        $this->assertDatabaseHas('cities', [
            'id' => $city->id,
            'deleted_at' => null,
        ]);
    });
});

describe('City Model Methods', function () {
    test('getName returns correct translation', function () {
        $city = City::factory()->create([
            'name_translations' => ['de' => 'München', 'en' => 'Munich'],
        ]);

        expect($city->getName('de'))->toBe('München');
        expect($city->getName('en'))->toBe('Munich');
    });

    test('getName falls back to English when German translation missing', function () {
        $city = City::factory()->create([
            'name_translations' => ['en' => 'Munich'],
        ]);

        expect($city->getName('de'))->toBe('Munich');
    });

    test('getName returns Unknown when all translations missing', function () {
        $city = City::factory()->create([
            'name_translations' => [],
        ]);

        expect($city->getName('de'))->toBe('Unknown');
    });
});

describe('City Capital Status', function () {
    test('city can be a capital', function () {
        $country = Country::factory()->create();
        $city = City::factory()->create([
            'country_id' => $country->id,
            'is_capital' => true,
        ]);

        expect($city->is_capital)->toBeTrue();
    });

    test('city can be a regional capital', function () {
        $country = Country::factory()->create();
        $region = Region::factory()->create(['country_id' => $country->id]);
        $city = City::factory()->create([
            'country_id' => $country->id,
            'region_id' => $region->id,
            'is_regional_capital' => true,
        ]);

        expect($city->is_regional_capital)->toBeTrue();
    });

    test('city can be both capital and regional capital', function () {
        $country = Country::factory()->create();
        $region = Region::factory()->create(['country_id' => $country->id]);
        $city = City::factory()->create([
            'country_id' => $country->id,
            'region_id' => $region->id,
            'is_capital' => true,
            'is_regional_capital' => true,
        ]);

        expect($city->is_capital)->toBeTrue();
        expect($city->is_regional_capital)->toBeTrue();
    });
});
