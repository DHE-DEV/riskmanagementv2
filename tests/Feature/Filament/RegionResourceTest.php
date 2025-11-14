<?php

declare(strict_types=1);

use App\Filament\Resources\Regions\RegionResource;
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

describe('List Regions Page', function () {
    test('can render list page', function () {
        livewire(RegionResource\Pages\ListRegions::class)
            ->assertSuccessful();
    });

    test('can list regions', function () {
        $regions = Region::factory()->count(10)->create();

        livewire(RegionResource\Pages\ListRegions::class)
            ->assertCanSeeTableRecords($regions);
    });

    test('can search regions by code', function () {
        $region1 = Region::factory()->create(['code' => 'BY']);
        $region2 = Region::factory()->create(['code' => 'HE']);

        livewire(RegionResource\Pages\ListRegions::class)
            ->searchTable('BY')
            ->assertCanSeeTableRecords([$region1])
            ->assertCanNotSeeTableRecords([$region2]);
    });

    test('can search regions by name translation', function () {
        $region1 = Region::factory()->create([
            'name_translations' => ['de' => 'Bayern', 'en' => 'Bavaria'],
        ]);
        $region2 = Region::factory()->create([
            'name_translations' => ['de' => 'Hessen', 'en' => 'Hesse'],
        ]);

        livewire(RegionResource\Pages\ListRegions::class)
            ->searchTable('Bayern')
            ->assertCanSeeTableRecords([$region1])
            ->assertCanNotSeeTableRecords([$region2]);
    });

    test('can filter regions by country', function () {
        $country1 = Country::factory()->create();
        $country2 = Country::factory()->create();

        $region1 = Region::factory()->create(['country_id' => $country1->id]);
        $region2 = Region::factory()->create(['country_id' => $country2->id]);

        livewire(RegionResource\Pages\ListRegions::class)
            ->filterTable('country_id', $country1->id)
            ->assertCanSeeTableRecords([$region1])
            ->assertCanNotSeeTableRecords([$region2]);
    });

    test('can delete region', function () {
        $region = Region::factory()->create();

        livewire(RegionResource\Pages\ListRegions::class)
            ->callTableAction(DeleteAction::class, $region);

        $this->assertSoftDeleted($region);
    });

    test('can bulk delete regions', function () {
        $regions = Region::factory()->count(3)->create();

        livewire(RegionResource\Pages\ListRegions::class)
            ->callTableBulkAction(DeleteBulkAction::class, $regions);

        foreach ($regions as $region) {
            $this->assertSoftDeleted($region);
        }
    });
});

describe('Create Region Page', function () {
    test('can render create page', function () {
        livewire(RegionResource\Pages\CreateRegion::class)
            ->assertSuccessful();
    });

    test('can create region with all fields', function () {
        $country = Country::factory()->create();

        $data = [
            'code' => 'BY',
            'name_translations.de' => 'Bayern',
            'name_translations.en' => 'Bavaria',
            'country_id' => $country->id,
            'description' => 'The largest German state',
            'lat' => 48.7904,
            'lng' => 11.4979,
        ];

        livewire(RegionResource\Pages\CreateRegion::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('regions', [
            'code' => 'BY',
            'country_id' => $country->id,
            'description' => 'The largest German state',
        ]);

        $region = Region::where('code', 'BY')->first();
        expect($region->name_translations)->toBe([
            'de' => 'Bayern',
            'en' => 'Bavaria',
        ]);
    });

    test('can create region with minimal required fields', function () {
        $country = Country::factory()->create();

        $data = [
            'code' => 'HE',
            'name_translations.de' => 'Hessen',
            'name_translations.en' => 'Hesse',
            'country_id' => $country->id,
        ];

        livewire(RegionResource\Pages\CreateRegion::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('regions', [
            'code' => 'HE',
        ]);
    });

    test('validates required fields', function () {
        livewire(RegionResource\Pages\CreateRegion::class)
            ->fillForm([])
            ->call('create')
            ->assertHasFormErrors([
                'code' => 'required',
                'name_translations.de' => 'required',
                'country_id' => 'required',
            ]);
    });

    test('validates code max length', function () {
        livewire(RegionResource\Pages\CreateRegion::class)
            ->fillForm([
                'code' => str_repeat('A', 256),
                'name_translations.de' => 'Test',
                'name_translations.en' => 'Test',
            ])
            ->call('create')
            ->assertHasFormErrors(['code' => 'max']);
    });

    test('validates name_translations max length', function () {
        $country = Country::factory()->create();

        livewire(RegionResource\Pages\CreateRegion::class)
            ->fillForm([
                'code' => 'BY',
                'name_translations.de' => str_repeat('A', 256),
                'name_translations.en' => 'Bavaria',
                'country_id' => $country->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['name_translations.de' => 'max']);
    });

    test('validates description max length', function () {
        $country = Country::factory()->create();

        livewire(RegionResource\Pages\CreateRegion::class)
            ->fillForm([
                'code' => 'BY',
                'name_translations.de' => 'Bayern',
                'name_translations.en' => 'Bavaria',
                'country_id' => $country->id,
                'description' => str_repeat('A', 1001),
            ])
            ->call('create')
            ->assertHasFormErrors(['description' => 'max']);
    });

    test('validates latitude bounds', function () {
        $country = Country::factory()->create();

        livewire(RegionResource\Pages\CreateRegion::class)
            ->fillForm([
                'code' => 'BY',
                'name_translations.de' => 'Bayern',
                'name_translations.en' => 'Bavaria',
                'country_id' => $country->id,
                'lat' => 91.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lat']);

        livewire(RegionResource\Pages\CreateRegion::class)
            ->fillForm([
                'code' => 'BY',
                'name_translations.de' => 'Bayern',
                'name_translations.en' => 'Bavaria',
                'country_id' => $country->id,
                'lat' => -91.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lat']);
    });

    test('validates longitude bounds', function () {
        $country = Country::factory()->create();

        livewire(RegionResource\Pages\CreateRegion::class)
            ->fillForm([
                'code' => 'BY',
                'name_translations.de' => 'Bayern',
                'name_translations.en' => 'Bavaria',
                'country_id' => $country->id,
                'lng' => 181.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lng']);

        livewire(RegionResource\Pages\CreateRegion::class)
            ->fillForm([
                'code' => 'BY',
                'name_translations.de' => 'Bayern',
                'name_translations.en' => 'Bavaria',
                'country_id' => $country->id,
                'lng' => -181.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lng']);
    });

    test('validates country_id exists', function () {
        livewire(RegionResource\Pages\CreateRegion::class)
            ->fillForm([
                'code' => 'BY',
                'name_translations.de' => 'Bayern',
                'name_translations.en' => 'Bavaria',
                'country_id' => 999999,
            ])
            ->call('create')
            ->assertHasFormErrors(['country_id']);
    });
});

describe('Edit Region Page', function () {
    test('can render edit page', function () {
        $region = Region::factory()->create();

        livewire(RegionResource\Pages\EditRegion::class, [
            'record' => $region->getRouteKey(),
        ])
            ->assertSuccessful();
    });

    test('can retrieve region data', function () {
        $country = Country::factory()->create();
        $region = Region::factory()->create([
            'code' => 'BY',
            'name_translations' => ['de' => 'Bayern', 'en' => 'Bavaria'],
            'country_id' => $country->id,
            'description' => 'Test description',
        ]);

        livewire(RegionResource\Pages\EditRegion::class, [
            'record' => $region->getRouteKey(),
        ])
            ->assertFormSet([
                'code' => 'BY',
                'name_translations.de' => 'Bayern',
                'name_translations.en' => 'Bavaria',
                'country_id' => $country->id,
                'description' => 'Test description',
            ]);
    });

    test('can update region', function () {
        $country1 = Country::factory()->create();
        $region = Region::factory()->create([
            'code' => 'BY',
            'name_translations' => ['de' => 'Bayern', 'en' => 'Bavaria'],
            'country_id' => $country1->id,
        ]);

        $country2 = Country::factory()->create();

        $newData = [
            'code' => 'HE',
            'name_translations.de' => 'Hessen',
            'name_translations.en' => 'Hesse',
            'country_id' => $country2->id,
            'description' => 'Updated description',
            'lat' => 50.0,
            'lng' => 9.0,
        ];

        livewire(RegionResource\Pages\EditRegion::class, [
            'record' => $region->getRouteKey(),
        ])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $region->refresh();

        expect($region->code)->toBe('HE');
        expect($region->name_translations)->toBe([
            'de' => 'Hessen',
            'en' => 'Hesse',
        ]);
        expect($region->country_id)->toBe($country2->id);
        expect($region->description)->toBe('Updated description');
    });

    test('validates required fields on update', function () {
        $region = Region::factory()->create();

        livewire(RegionResource\Pages\EditRegion::class, [
            'record' => $region->getRouteKey(),
        ])
            ->fillForm([
                'code' => '',
                'name_translations.de' => '',
                'country_id' => null,
            ])
            ->call('save')
            ->assertHasFormErrors([
                'code' => 'required',
                'name_translations.de' => 'required',
                'country_id' => 'required',
            ]);
    });

    test('can delete region from edit page', function () {
        $region = Region::factory()->create();

        livewire(RegionResource\Pages\EditRegion::class, [
            'record' => $region->getRouteKey(),
        ])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted($region);
    });
});

describe('View Region Page', function () {
    test('can render view page', function () {
        $region = Region::factory()->create();

        livewire(RegionResource\Pages\ViewRegion::class, [
            'record' => $region->getRouteKey(),
        ])
            ->assertSuccessful();
    });

    test('can display region data', function () {
        $region = Region::factory()->create([
            'code' => 'BY',
            'name_translations' => ['de' => 'Bayern', 'en' => 'Bavaria'],
        ]);

        livewire(RegionResource\Pages\ViewRegion::class, [
            'record' => $region->getRouteKey(),
        ])
            ->assertSee('BY')
            ->assertSee('Bayern');
    });
});

describe('Region Relationships', function () {
    test('region belongs to country', function () {
        $country = Country::factory()->create();
        $region = Region::factory()->create(['country_id' => $country->id]);

        expect($region->country->id)->toBe($country->id);
    });

    test('can view cities relation manager', function () {
        $country = Country::factory()->create();
        $region = Region::factory()->create(['country_id' => $country->id]);

        $cities = City::factory()->count(3)->create([
            'country_id' => $country->id,
            'region_id' => $region->id,
        ]);

        livewire(RegionResource\RelationManagers\CitiesRelationManager::class, [
            'ownerRecord' => $region,
            'pageClass' => RegionResource\Pages\EditRegion::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($cities);
    });

    test('region with cities displays relationship count', function () {
        $country = Country::factory()->create();
        $region = Region::factory()->create(['country_id' => $country->id]);

        City::factory()->count(5)->create([
            'country_id' => $country->id,
            'region_id' => $region->id,
        ]);

        expect($region->cities()->count())->toBe(5);
    });
});

describe('Region Scopes', function () {
    test('can filter regions by country using scope', function () {
        $country = Country::factory()->create();
        Region::factory()->count(3)->create(['country_id' => $country->id]);
        Region::factory()->count(2)->create();

        $regionsByCountry = Region::byCountry($country->id)->get();

        expect($regionsByCountry->count())->toBe(3);
    });

    test('can search regions using scope', function () {
        Region::factory()->create([
            'code' => 'BY',
            'description' => 'Bavaria region',
        ]);
        Region::factory()->create([
            'code' => 'HE',
            'description' => 'Hesse region',
        ]);

        $searchResults = Region::search('BY')->get();

        expect($searchResults->count())->toBe(1);
        expect($searchResults->first()->code)->toBe('BY');
    });
});

describe('Region Soft Deletes', function () {
    test('soft deleted regions are not shown in list', function () {
        $activeRegion = Region::factory()->create();
        $deletedRegion = Region::factory()->create();
        $deletedRegion->delete();

        livewire(RegionResource\Pages\ListRegions::class)
            ->assertCanSeeTableRecords([$activeRegion])
            ->assertCanNotSeeTableRecords([$deletedRegion]);
    });

    test('can restore soft deleted region', function () {
        $region = Region::factory()->create();
        $region->delete();

        $region->restore();

        $this->assertDatabaseHas('regions', [
            'id' => $region->id,
            'deleted_at' => null,
        ]);
    });
});

describe('Region Model Methods', function () {
    test('getName returns correct translation', function () {
        $region = Region::factory()->create([
            'name_translations' => ['de' => 'Bayern', 'en' => 'Bavaria'],
        ]);

        expect($region->getName('de'))->toBe('Bayern');
        expect($region->getName('en'))->toBe('Bavaria');
    });

    test('getName falls back to code when translation missing', function () {
        $region = Region::factory()->create([
            'code' => 'BY',
            'name_translations' => [],
        ]);

        expect($region->getName('de'))->toBe('BY');
    });
});

describe('Relation Manager CRUD Operations', function () {
    describe('Cities Relation Manager (from Region)', function () {
        test('can render cities relation manager', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\ViewRegion::class,
            ])
                ->assertSuccessful();
        });

        test('can create city through relation manager', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);

            $data = [
                'name_translations.de' => 'München',
                'name_translations.en' => 'Munich',
                'is_capital' => false,
                'is_regional_capital' => true,
                'population' => 1500000,
                'lat' => 48.1351,
                'lng' => 11.5820,
            ];

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ])
                ->callTableAction('create', data: $data)
                ->assertHasNoTableActionErrors();

            $this->assertDatabaseHas('cities', [
                'country_id' => $country->id,
                'region_id' => $region->id,
                'is_regional_capital' => true,
                'population' => 1500000,
            ]);
        });

        test('can create city with minimal required fields', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);

            $data = [
                'name_translations.de' => 'Nürnberg',
                'name_translations.en' => 'Nuremberg',
            ];

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ])
                ->callTableAction('create', data: $data)
                ->assertHasNoTableActionErrors();

            $this->assertDatabaseHas('cities', [
                'country_id' => $country->id,
                'region_id' => $region->id,
            ]);
        });

        test('validates required fields when creating city', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ])
                ->callTableAction('create', data: [])
                ->assertHasTableActionErrors([
                    'name_translations.de' => 'required',
                ]);
        });

        test('automatically sets country_id from region when creating city', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);

            $data = [
                'name_translations.de' => 'Regensburg',
                'name_translations.en' => 'Regensburg',
            ];

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ])
                ->callTableAction('create', data: $data)
                ->assertHasNoTableActionErrors();

            $city = City::where('region_id', $region->id)->first();
            expect($city->country_id)->toBe($country->id);
            expect($city->region_id)->toBe($region->id);
        });

        test('can edit city through relation manager', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);
            $city = City::factory()->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
                'name_translations' => ['de' => 'München', 'en' => 'Munich'],
                'population' => 1500000,
            ]);

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ])
                ->callTableAction('edit', $city, data: [
                    'name_translations.de' => 'München Updated',
                    'name_translations.en' => 'Munich Updated',
                    'population' => 1600000,
                    'is_regional_capital' => true,
                ])
                ->assertHasNoTableActionErrors();

            $city->refresh();
            expect($city->name_translations['de'])->toBe('München Updated');
            expect($city->population)->toBe(1600000);
            expect($city->is_regional_capital)->toBeTrue();
        });

        test('can update all city fields through relation manager', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);
            $city = City::factory()->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
            ]);

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ])
                ->callTableAction('edit', $city, data: [
                    'name_translations.de' => 'New Name DE',
                    'name_translations.en' => 'New Name EN',
                    'is_capital' => true,
                    'is_regional_capital' => true,
                    'population' => 2000000,
                    'lat' => 50.0,
                    'lng' => 10.0,
                ])
                ->assertHasNoTableActionErrors();

            $city->refresh();
            expect($city->name_translations['de'])->toBe('New Name DE');
            expect($city->is_capital)->toBeTrue();
            expect($city->lat)->toBe(50.0);
        });

        test('validates fields when updating city', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);
            $city = City::factory()->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
            ]);

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ])
                ->callTableAction('edit', $city, data: [
                    'name_translations.de' => '',
                ])
                ->assertHasTableActionErrors([
                    'name_translations.de' => 'required',
                ]);
        });

        test('can delete city through relation manager', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);
            $city = City::factory()->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
            ]);

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ])
                ->callTableAction('delete', $city);

            $this->assertSoftDeleted($city);
        });

        test('displays only cities for the specific region', function () {
            $country = Country::factory()->create();
            $region1 = Region::factory()->create(['country_id' => $country->id]);
            $region2 = Region::factory()->create(['country_id' => $country->id]);

            $citiesInRegion1 = City::factory()->count(3)->create([
                'country_id' => $country->id,
                'region_id' => $region1->id,
            ]);
            $citiesInRegion2 = City::factory()->count(2)->create([
                'country_id' => $country->id,
                'region_id' => $region2->id,
            ]);

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region1,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ])
                ->assertCanSeeTableRecords($citiesInRegion1)
                ->assertCanNotSeeTableRecords($citiesInRegion2);
        });

        test('can search cities by name', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);
            $city1 = City::factory()->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
                'name_translations' => ['de' => 'München', 'en' => 'Munich'],
            ]);
            $city2 = City::factory()->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
                'name_translations' => ['de' => 'Nürnberg', 'en' => 'Nuremberg'],
            ]);

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ])
                ->searchTable('München')
                ->assertCanSeeTableRecords([$city1])
                ->assertCanNotSeeTableRecords([$city2]);
        });

        test('can filter cities by capital status', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);
            $capital = City::factory()->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
                'is_capital' => true,
            ]);
            $regularCity = City::factory()->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
                'is_capital' => false,
            ]);

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ])
                ->filterTable('is_capital')
                ->assertCanSeeTableRecords([$capital])
                ->assertCanNotSeeTableRecords([$regularCity]);
        });

        test('can filter cities by regional capital status', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);
            $regionalCapital = City::factory()->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
                'is_regional_capital' => true,
            ]);
            $regularCity = City::factory()->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
                'is_regional_capital' => false,
            ]);

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ])
                ->filterTable('is_regional_capital')
                ->assertCanSeeTableRecords([$regionalCapital])
                ->assertCanNotSeeTableRecords([$regularCity]);
        });

        test('can sort cities by name', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);
            City::factory()->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
                'name_translations' => ['de' => 'Würzburg', 'en' => 'Würzburg'],
            ]);
            City::factory()->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
                'name_translations' => ['de' => 'Augsburg', 'en' => 'Augsburg'],
            ]);

            livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ])
                ->sortTable('city_name')
                ->assertSuccessful();
        });

        test('pagination works in cities relation manager', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);
            City::factory()->count(30)->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
            ]);

            $component = livewire(\App\Filament\Resources\Regions\RegionResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $region,
                'pageClass' => \App\Filament\Resources\Regions\RegionResource\Pages\EditRegion::class,
            ]);

            $component->assertSuccessful();
            expect($component->get('tableRecords')->count())->toBeLessThanOrEqual(25);
        });

        test('displays correct city count for region', function () {
            $country = Country::factory()->create();
            $region = Region::factory()->create(['country_id' => $country->id]);
            City::factory()->count(8)->create([
                'country_id' => $country->id,
                'region_id' => $region->id,
            ]);

            expect($region->cities()->count())->toBe(8);
        });
    });
});
