<?php

declare(strict_types=1);

use App\Filament\Resources\Continents\ContinentResource;
use App\Models\Continent;
use App\Models\Country;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

describe('List Continents Page', function () {
    test('can render list page', function () {
        livewire(ContinentResource\Pages\ListContinents::class)
            ->assertSuccessful();
    });

    test('can list continents', function () {
        $continents = Continent::factory()->count(10)->create();

        livewire(ContinentResource\Pages\ListContinents::class)
            ->assertCanSeeTableRecords($continents);
    });

    test('can search continents by code', function () {
        $continent1 = Continent::factory()->create(['code' => 'EU']);
        $continent2 = Continent::factory()->create(['code' => 'AS']);

        livewire(ContinentResource\Pages\ListContinents::class)
            ->searchTable('EU')
            ->assertCanSeeTableRecords([$continent1])
            ->assertCanNotSeeTableRecords([$continent2]);
    });

    test('can search continents by name translation', function () {
        $continent1 = Continent::factory()->create([
            'name_translations' => ['de' => 'Europa', 'en' => 'Europe'],
        ]);
        $continent2 = Continent::factory()->create([
            'name_translations' => ['de' => 'Asien', 'en' => 'Asia'],
        ]);

        livewire(ContinentResource\Pages\ListContinents::class)
            ->searchTable('Europa')
            ->assertCanSeeTableRecords([$continent1])
            ->assertCanNotSeeTableRecords([$continent2]);
    });

    test('can sort continents by sort_order', function () {
        $continents = Continent::factory()->count(3)->create([
            'sort_order' => fn () => rand(1, 100),
        ]);

        livewire(ContinentResource\Pages\ListContinents::class)
            ->sortTable('sort_order')
            ->assertCanSeeTableRecords($continents->sortBy('sort_order'), inOrder: true);
    });

    test('can delete continent', function () {
        $continent = Continent::factory()->create();

        livewire(ContinentResource\Pages\ListContinents::class)
            ->callTableAction(DeleteAction::class, $continent);

        $this->assertSoftDeleted($continent);
    });

    test('can bulk delete continents', function () {
        $continents = Continent::factory()->count(3)->create();

        livewire(ContinentResource\Pages\ListContinents::class)
            ->callTableBulkAction(DeleteBulkAction::class, $continents);

        foreach ($continents as $continent) {
            $this->assertSoftDeleted($continent);
        }
    });
});

describe('Create Continent Page', function () {
    test('can render create page', function () {
        livewire(ContinentResource\Pages\CreateContinent::class)
            ->assertSuccessful();
    });

    test('can create continent with all fields', function () {
        $data = [
            'code' => 'EU',
            'name_translations.de' => 'Europa',
            'name_translations.en' => 'Europe',
            'sort_order' => 1,
            'description' => 'Test description for Europe',
            'lat' => 54.5260,
            'lng' => 15.2551,
        ];

        livewire(ContinentResource\Pages\CreateContinent::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('continents', [
            'code' => 'EU',
            'sort_order' => 1,
            'description' => 'Test description for Europe',
        ]);

        $continent = Continent::where('code', 'EU')->first();
        expect($continent->name_translations)->toBe([
            'de' => 'Europa',
            'en' => 'Europe',
        ]);
    });

    test('can create continent with minimal required fields', function () {
        $data = [
            'code' => 'AS',
            'name_translations.de' => 'Asien',
            'name_translations.en' => 'Asia',
            'sort_order' => 0,
        ];

        livewire(ContinentResource\Pages\CreateContinent::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('continents', [
            'code' => 'AS',
        ]);
    });

    test('validates required fields', function () {
        livewire(ContinentResource\Pages\CreateContinent::class)
            ->fillForm([])
            ->call('create')
            ->assertHasFormErrors([
                'code' => 'required',
                'name_translations.de' => 'required',
                'name_translations.en' => 'required',
            ]);
    });

    test('validates code max length', function () {
        livewire(ContinentResource\Pages\CreateContinent::class)
            ->fillForm([
                'code' => str_repeat('A', 11),
                'name_translations.de' => 'Test',
                'name_translations.en' => 'Test',
            ])
            ->call('create')
            ->assertHasFormErrors(['code' => 'max']);
    });

    test('validates name_translations max length', function () {
        livewire(ContinentResource\Pages\CreateContinent::class)
            ->fillForm([
                'code' => 'EU',
                'name_translations.de' => str_repeat('A', 256),
                'name_translations.en' => 'Europe',
            ])
            ->call('create')
            ->assertHasFormErrors(['name_translations.de' => 'max']);
    });

    test('validates description max length', function () {
        livewire(ContinentResource\Pages\CreateContinent::class)
            ->fillForm([
                'code' => 'EU',
                'name_translations.de' => 'Europa',
                'name_translations.en' => 'Europe',
                'description' => str_repeat('A', 1001),
            ])
            ->call('create')
            ->assertHasFormErrors(['description' => 'max']);
    });

    test('validates latitude bounds', function () {
        livewire(ContinentResource\Pages\CreateContinent::class)
            ->fillForm([
                'code' => 'EU',
                'name_translations.de' => 'Europa',
                'name_translations.en' => 'Europe',
                'lat' => 91.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lat']);

        livewire(ContinentResource\Pages\CreateContinent::class)
            ->fillForm([
                'code' => 'EU',
                'name_translations.de' => 'Europa',
                'name_translations.en' => 'Europe',
                'lat' => -91.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lat']);
    });

    test('validates longitude bounds', function () {
        livewire(ContinentResource\Pages\CreateContinent::class)
            ->fillForm([
                'code' => 'EU',
                'name_translations.de' => 'Europa',
                'name_translations.en' => 'Europe',
                'lng' => 181.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lng']);

        livewire(ContinentResource\Pages\CreateContinent::class)
            ->fillForm([
                'code' => 'EU',
                'name_translations.de' => 'Europa',
                'name_translations.en' => 'Europe',
                'lng' => -181.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lng']);
    });

    test('validates sort_order is numeric', function () {
        livewire(ContinentResource\Pages\CreateContinent::class)
            ->fillForm([
                'code' => 'EU',
                'name_translations.de' => 'Europa',
                'name_translations.en' => 'Europe',
                'sort_order' => 'not a number',
            ])
            ->call('create')
            ->assertHasFormErrors(['sort_order']);
    });
});

describe('Edit Continent Page', function () {
    test('can render edit page', function () {
        $continent = Continent::factory()->create();

        livewire(ContinentResource\Pages\EditContinent::class, [
            'record' => $continent->getRouteKey(),
        ])
            ->assertSuccessful();
    });

    test('can retrieve continent data', function () {
        $continent = Continent::factory()->create([
            'code' => 'EU',
            'name_translations' => ['de' => 'Europa', 'en' => 'Europe'],
            'sort_order' => 1,
            'description' => 'European continent',
        ]);

        livewire(ContinentResource\Pages\EditContinent::class, [
            'record' => $continent->getRouteKey(),
        ])
            ->assertFormSet([
                'code' => 'EU',
                'name_translations.de' => 'Europa',
                'name_translations.en' => 'Europe',
                'sort_order' => 1,
                'description' => 'European continent',
            ]);
    });

    test('can update continent', function () {
        $continent = Continent::factory()->create([
            'code' => 'EU',
            'name_translations' => ['de' => 'Europa', 'en' => 'Europe'],
        ]);

        $newData = [
            'code' => 'EUR',
            'name_translations.de' => 'Europa Updated',
            'name_translations.en' => 'Europe Updated',
            'sort_order' => 10,
            'description' => 'Updated description',
            'lat' => 50.0,
            'lng' => 10.0,
        ];

        livewire(ContinentResource\Pages\EditContinent::class, [
            'record' => $continent->getRouteKey(),
        ])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $continent->refresh();

        expect($continent->code)->toBe('EUR');
        expect($continent->name_translations)->toBe([
            'de' => 'Europa Updated',
            'en' => 'Europe Updated',
        ]);
        expect($continent->sort_order)->toBe(10);
        expect($continent->description)->toBe('Updated description');
    });

    test('validates required fields on update', function () {
        $continent = Continent::factory()->create();

        livewire(ContinentResource\Pages\EditContinent::class, [
            'record' => $continent->getRouteKey(),
        ])
            ->fillForm([
                'code' => '',
                'name_translations.de' => '',
            ])
            ->call('save')
            ->assertHasFormErrors([
                'code' => 'required',
                'name_translations.de' => 'required',
            ]);
    });

    test('can delete continent from edit page', function () {
        $continent = Continent::factory()->create();

        livewire(ContinentResource\Pages\EditContinent::class, [
            'record' => $continent->getRouteKey(),
        ])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted($continent);
    });
});

describe('View Continent Page', function () {
    test('can render view page', function () {
        $continent = Continent::factory()->create();

        livewire(ContinentResource\Pages\ViewContinent::class, [
            'record' => $continent->getRouteKey(),
        ])
            ->assertSuccessful();
    });

    test('can display continent data', function () {
        $continent = Continent::factory()->create([
            'code' => 'EU',
            'name_translations' => ['de' => 'Europa', 'en' => 'Europe'],
            'sort_order' => 1,
            'description' => 'European continent',
        ]);

        livewire(ContinentResource\Pages\ViewContinent::class, [
            'record' => $continent->getRouteKey(),
        ])
            ->assertSee('EU')
            ->assertSee('Europa');
    });
});

describe('Continent Relationships', function () {
    test('can view countries relation manager', function () {
        $continent = Continent::factory()
            ->has(Country::factory()->count(3))
            ->create();

        livewire(ContinentResource\RelationManagers\CountriesRelationManager::class, [
            'ownerRecord' => $continent,
            'pageClass' => ContinentResource\Pages\EditContinent::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($continent->countries);
    });

    test('continent with countries displays relationship count', function () {
        $continent = Continent::factory()
            ->has(Country::factory()->count(5))
            ->create();

        expect($continent->countries()->count())->toBe(5);
    });
});

describe('Continent Soft Deletes', function () {
    test('soft deleted continents are not shown in list', function () {
        $activeContinent = Continent::factory()->create();
        $deletedContinent = Continent::factory()->create();
        $deletedContinent->delete();

        livewire(ContinentResource\Pages\ListContinents::class)
            ->assertCanSeeTableRecords([$activeContinent])
            ->assertCanNotSeeTableRecords([$deletedContinent]);
    });

    test('can restore soft deleted continent', function () {
        $continent = Continent::factory()->create();
        $continent->delete();

        $continent->restore();

        $this->assertDatabaseHas('continents', [
            'id' => $continent->id,
            'deleted_at' => null,
        ]);
    });
});

describe('Relation Manager CRUD Operations', function () {
    describe('Countries Relation Manager - Table Operations', function () {
        test('can render countries relation manager', function () {
            $continent = Continent::factory()->create();

            livewire(ContinentResource\RelationManagers\CountriesRelationManager::class, [
                'ownerRecord' => $continent,
                'pageClass' => ContinentResource\Pages\ViewContinent::class,
            ])
                ->assertSuccessful();
        });

        test('displays all countries for continent', function () {
            $continent = Continent::factory()->create();
            $countries = Country::factory()->count(5)->create(['continent_id' => $continent->id]);
            $otherCountries = Country::factory()->count(3)->create();

            livewire(ContinentResource\RelationManagers\CountriesRelationManager::class, [
                'ownerRecord' => $continent,
                'pageClass' => ContinentResource\Pages\ViewContinent::class,
            ])
                ->assertCanSeeTableRecords($countries)
                ->assertCanNotSeeTableRecords($otherCountries);
        });

        test('can search countries by german name in relation manager', function () {
            $continent = Continent::factory()->create();
            $country1 = Country::factory()->create([
                'continent_id' => $continent->id,
                'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
            ]);
            $country2 = Country::factory()->create([
                'continent_id' => $continent->id,
                'name_translations' => ['de' => 'Frankreich', 'en' => 'France'],
            ]);

            livewire(ContinentResource\RelationManagers\CountriesRelationManager::class, [
                'ownerRecord' => $continent,
                'pageClass' => ContinentResource\Pages\ViewContinent::class,
            ])
                ->searchTable('Deutschland')
                ->assertCanSeeTableRecords([$country1])
                ->assertCanNotSeeTableRecords([$country2]);
        });

        test('can search countries by ISO code in relation manager', function () {
            $continent = Continent::factory()->create();
            $country1 = Country::factory()->create([
                'continent_id' => $continent->id,
                'iso_code' => 'DE',
            ]);
            $country2 = Country::factory()->create([
                'continent_id' => $continent->id,
                'iso_code' => 'FR',
            ]);

            livewire(ContinentResource\RelationManagers\CountriesRelationManager::class, [
                'ownerRecord' => $continent,
                'pageClass' => ContinentResource\Pages\ViewContinent::class,
            ])
                ->searchTable('DE')
                ->assertCanSeeTableRecords([$country1])
                ->assertCanNotSeeTableRecords([$country2]);
        });

        test('can search countries by ISO3 code in relation manager', function () {
            $continent = Continent::factory()->create();
            $country1 = Country::factory()->create([
                'continent_id' => $continent->id,
                'iso3_code' => 'DEU',
            ]);
            $country2 = Country::factory()->create([
                'continent_id' => $continent->id,
                'iso3_code' => 'FRA',
            ]);

            livewire(ContinentResource\RelationManagers\CountriesRelationManager::class, [
                'ownerRecord' => $continent,
                'pageClass' => ContinentResource\Pages\ViewContinent::class,
            ])
                ->searchTable('DEU')
                ->assertCanSeeTableRecords([$country1])
                ->assertCanNotSeeTableRecords([$country2]);
        });

        test('can filter EU member countries in relation manager', function () {
            $continent = Continent::factory()->create();
            $euCountry = Country::factory()->create([
                'continent_id' => $continent->id,
                'is_eu_member' => true,
            ]);
            $nonEuCountry = Country::factory()->create([
                'continent_id' => $continent->id,
                'is_eu_member' => false,
            ]);

            livewire(ContinentResource\RelationManagers\CountriesRelationManager::class, [
                'ownerRecord' => $continent,
                'pageClass' => ContinentResource\Pages\ViewContinent::class,
            ])
                ->filterTable('is_eu_member')
                ->assertCanSeeTableRecords([$euCountry])
                ->assertCanNotSeeTableRecords([$nonEuCountry]);
        });

        test('can filter Schengen member countries in relation manager', function () {
            $continent = Continent::factory()->create();
            $schengenCountry = Country::factory()->create([
                'continent_id' => $continent->id,
                'is_schengen_member' => true,
            ]);
            $nonSchengenCountry = Country::factory()->create([
                'continent_id' => $continent->id,
                'is_schengen_member' => false,
            ]);

            livewire(ContinentResource\RelationManagers\CountriesRelationManager::class, [
                'ownerRecord' => $continent,
                'pageClass' => ContinentResource\Pages\ViewContinent::class,
            ])
                ->filterTable('is_schengen_member')
                ->assertCanSeeTableRecords([$schengenCountry])
                ->assertCanNotSeeTableRecords([$nonSchengenCountry]);
        });

        test('pagination works in countries relation manager', function () {
            $continent = Continent::factory()->create();
            Country::factory()->count(30)->create(['continent_id' => $continent->id]);

            $component = livewire(ContinentResource\RelationManagers\CountriesRelationManager::class, [
                'ownerRecord' => $continent,
                'pageClass' => ContinentResource\Pages\ViewContinent::class,
            ]);

            $component->assertSuccessful();
            expect($component->get('tableRecords')->count())->toBeLessThanOrEqual(25);
        });

        test('can sort countries by german name', function () {
            $continent = Continent::factory()->create();
            Country::factory()->create([
                'continent_id' => $continent->id,
                'name_translations' => ['de' => 'Zypern', 'en' => 'Cyprus'],
            ]);
            Country::factory()->create([
                'continent_id' => $continent->id,
                'name_translations' => ['de' => 'Belgien', 'en' => 'Belgium'],
            ]);

            livewire(ContinentResource\RelationManagers\CountriesRelationManager::class, [
                'ownerRecord' => $continent,
                'pageClass' => ContinentResource\Pages\ViewContinent::class,
            ])
                ->sortTable('german_name')
                ->assertSuccessful();
        });

        test('displays correct country count', function () {
            $continent = Continent::factory()->create();
            Country::factory()->count(7)->create(['continent_id' => $continent->id]);

            expect($continent->countries()->count())->toBe(7);
        });
    });
});
