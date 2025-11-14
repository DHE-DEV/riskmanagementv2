<?php

declare(strict_types=1);

use App\Filament\Resources\Countries\CountryResource;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Region;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

describe('List Countries Page', function () {
    test('can render list page', function () {
        livewire(CountryResource\Pages\ListCountries::class)
            ->assertSuccessful();
    });

    test('can list countries', function () {
        $countries = Country::factory()->count(10)->create();

        livewire(CountryResource\Pages\ListCountries::class)
            ->assertCanSeeTableRecords($countries);
    });

    test('can search countries by iso code', function () {
        $country1 = Country::factory()->create(['iso_code' => 'DE']);
        $country2 = Country::factory()->create(['iso_code' => 'FR']);

        livewire(CountryResource\Pages\ListCountries::class)
            ->searchTable('DE')
            ->assertCanSeeTableRecords([$country1])
            ->assertCanNotSeeTableRecords([$country2]);
    });

    test('can search countries by iso3 code', function () {
        $country1 = Country::factory()->create(['iso3_code' => 'DEU']);
        $country2 = Country::factory()->create(['iso3_code' => 'FRA']);

        livewire(CountryResource\Pages\ListCountries::class)
            ->searchTable('DEU')
            ->assertCanSeeTableRecords([$country1])
            ->assertCanNotSeeTableRecords([$country2]);
    });

    test('can search countries by name translation', function () {
        $country1 = Country::factory()->create([
            'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
        ]);
        $country2 = Country::factory()->create([
            'name_translations' => ['de' => 'Frankreich', 'en' => 'France'],
        ]);

        livewire(CountryResource\Pages\ListCountries::class)
            ->searchTable('Deutschland')
            ->assertCanSeeTableRecords([$country1])
            ->assertCanNotSeeTableRecords([$country2]);
    });

    test('can filter countries by continent', function () {
        $continentEU = Continent::factory()->create(['code' => 'EU']);
        $continentAS = Continent::factory()->create(['code' => 'AS']);

        $countryEU = Country::factory()->create(['continent_id' => $continentEU->id]);
        $countryAS = Country::factory()->create(['continent_id' => $continentAS->id]);

        livewire(CountryResource\Pages\ListCountries::class)
            ->filterTable('continent_id', $continentEU->id)
            ->assertCanSeeTableRecords([$countryEU])
            ->assertCanNotSeeTableRecords([$countryAS]);
    });

    test('can filter EU member countries', function () {
        $euCountry = Country::factory()->create(['is_eu_member' => true]);
        $nonEuCountry = Country::factory()->create(['is_eu_member' => false]);

        livewire(CountryResource\Pages\ListCountries::class)
            ->filterTable('is_eu_member', true)
            ->assertCanSeeTableRecords([$euCountry])
            ->assertCanNotSeeTableRecords([$nonEuCountry]);
    });

    test('can filter Schengen member countries', function () {
        $schengenCountry = Country::factory()->create(['is_schengen_member' => true]);
        $nonSchengenCountry = Country::factory()->create(['is_schengen_member' => false]);

        livewire(CountryResource\Pages\ListCountries::class)
            ->filterTable('is_schengen_member', true)
            ->assertCanSeeTableRecords([$schengenCountry])
            ->assertCanNotSeeTableRecords([$nonSchengenCountry]);
    });

    test('can delete country', function () {
        $country = Country::factory()->create();

        livewire(CountryResource\Pages\ListCountries::class)
            ->callTableAction(DeleteAction::class, $country);

        $this->assertSoftDeleted($country);
    });

    test('can bulk delete countries', function () {
        $countries = Country::factory()->count(3)->create();

        livewire(CountryResource\Pages\ListCountries::class)
            ->callTableBulkAction(DeleteBulkAction::class, $countries);

        foreach ($countries as $country) {
            $this->assertSoftDeleted($country);
        }
    });
});

describe('Create Country Page', function () {
    test('can render create page', function () {
        livewire(CountryResource\Pages\CreateCountry::class)
            ->assertSuccessful();
    });

    test('can create country with all fields', function () {
        $continent = Continent::factory()->create();

        $data = [
            'iso_code' => 'DE',
            'iso3_code' => 'DEU',
            'name_translations' => [
                'de' => 'Deutschland',
                'en' => 'Germany',
            ],
            'continent_id' => $continent->id,
            'is_eu_member' => true,
            'is_schengen_member' => true,
            'currency_code' => 'EUR',
            'currency_name' => 'Euro',
            'currency_symbol' => '€',
            'phone_prefix' => '+49',
            'timezone' => 'Europe/Berlin',
            'population' => 83000000,
            'area_km2' => 357022.00,
            'lat' => 51.1657,
            'lng' => 10.4515,
        ];

        livewire(CountryResource\Pages\CreateCountry::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('countries', [
            'iso_code' => 'DE',
            'iso3_code' => 'DEU',
            'currency_code' => 'EUR',
            'phone_prefix' => '+49',
        ]);

        $country = Country::where('iso_code', 'DE')->first();
        expect($country->name_translations)->toBe([
            'de' => 'Deutschland',
            'en' => 'Germany',
        ]);
        expect($country->is_eu_member)->toBeTrue();
        expect($country->is_schengen_member)->toBeTrue();
    });

    test('can create country with minimal required fields', function () {
        $continent = Continent::factory()->create();

        $data = [
            'iso_code' => 'US',
            'iso3_code' => 'USA',
            'name_translations' => [
                'de' => 'Vereinigte Staaten',
                'en' => 'United States',
            ],
            'continent_id' => $continent->id,
        ];

        livewire(CountryResource\Pages\CreateCountry::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('countries', [
            'iso_code' => 'US',
            'iso3_code' => 'USA',
        ]);
    });

    test('validates required fields', function () {
        livewire(CountryResource\Pages\CreateCountry::class)
            ->fillForm([])
            ->call('create')
            ->assertHasFormErrors([
                'iso_code' => 'required',
                'iso3_code' => 'required',
                'name_translations' => 'required',
                'continent_id' => 'required',
            ]);
    });

    test('validates iso_code is unique', function () {
        Country::factory()->create(['iso_code' => 'DE']);

        $continent = Continent::factory()->create();

        livewire(CountryResource\Pages\CreateCountry::class)
            ->fillForm([
                'iso_code' => 'DE',
                'iso3_code' => 'DEU',
                'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
                'continent_id' => $continent->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['iso_code' => 'unique']);
    });

    test('validates iso3_code is unique', function () {
        Country::factory()->create(['iso3_code' => 'DEU']);

        $continent = Continent::factory()->create();

        livewire(CountryResource\Pages\CreateCountry::class)
            ->fillForm([
                'iso_code' => 'DE',
                'iso3_code' => 'DEU',
                'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
                'continent_id' => $continent->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['iso3_code' => 'unique']);
    });

    test('validates iso_code max length', function () {
        livewire(CountryResource\Pages\CreateCountry::class)
            ->fillForm([
                'iso_code' => 'DEU',
                'iso3_code' => 'DEU',
                'name_translations' => ['de' => 'Test', 'en' => 'Test'],
            ])
            ->call('create')
            ->assertHasFormErrors(['iso_code' => 'max']);
    });

    test('validates iso3_code max length', function () {
        livewire(CountryResource\Pages\CreateCountry::class)
            ->fillForm([
                'iso_code' => 'DE',
                'iso3_code' => 'DEUR',
                'name_translations' => ['de' => 'Test', 'en' => 'Test'],
            ])
            ->call('create')
            ->assertHasFormErrors(['iso3_code' => 'max']);
    });

    test('validates latitude bounds', function () {
        $continent = Continent::factory()->create();

        livewire(CountryResource\Pages\CreateCountry::class)
            ->fillForm([
                'iso_code' => 'DE',
                'iso3_code' => 'DEU',
                'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
                'continent_id' => $continent->id,
                'lat' => 91.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lat']);
    });

    test('validates longitude bounds', function () {
        $continent = Continent::factory()->create();

        livewire(CountryResource\Pages\CreateCountry::class)
            ->fillForm([
                'iso_code' => 'DE',
                'iso3_code' => 'DEU',
                'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
                'continent_id' => $continent->id,
                'lng' => 181.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['lng']);
    });

    test('validates currency_code max length', function () {
        $continent = Continent::factory()->create();

        livewire(CountryResource\Pages\CreateCountry::class)
            ->fillForm([
                'iso_code' => 'DE',
                'iso3_code' => 'DEU',
                'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
                'continent_id' => $continent->id,
                'currency_code' => 'EURO',
            ])
            ->call('create')
            ->assertHasFormErrors(['currency_code' => 'max']);
    });

    test('validates population is numeric', function () {
        $continent = Continent::factory()->create();

        livewire(CountryResource\Pages\CreateCountry::class)
            ->fillForm([
                'iso_code' => 'DE',
                'iso3_code' => 'DEU',
                'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
                'continent_id' => $continent->id,
                'population' => 'not a number',
            ])
            ->call('create')
            ->assertHasFormErrors(['population']);
    });
});

describe('Edit Country Page', function () {
    test('can render edit page', function () {
        $country = Country::factory()->create();

        livewire(CountryResource\Pages\EditCountry::class, [
            'record' => $country->getRouteKey(),
        ])
            ->assertSuccessful();
    });

    test('can retrieve country data', function () {
        $continent = Continent::factory()->create();
        $country = Country::factory()->create([
            'iso_code' => 'DE',
            'iso3_code' => 'DEU',
            'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
            'continent_id' => $continent->id,
            'is_eu_member' => true,
            'is_schengen_member' => true,
            'currency_code' => 'EUR',
        ]);

        livewire(CountryResource\Pages\EditCountry::class, [
            'record' => $country->getRouteKey(),
        ])
            ->assertFormSet([
                'iso_code' => 'DE',
                'iso3_code' => 'DEU',
                'continent_id' => $continent->id,
                'is_eu_member' => true,
                'is_schengen_member' => true,
                'currency_code' => 'EUR',
            ]);
    });

    test('can update country', function () {
        $continent = Continent::factory()->create();
        $country = Country::factory()->create([
            'iso_code' => 'DE',
            'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
        ]);

        $newContinent = Continent::factory()->create();

        livewire(CountryResource\Pages\EditCountry::class, [
            'record' => $country->getRouteKey(),
        ])
            ->fillForm([
                'iso_code' => 'AT',
                'iso3_code' => 'AUT',
                'name_translations' => [
                    'de' => 'Österreich',
                    'en' => 'Austria',
                ],
                'continent_id' => $newContinent->id,
                'is_eu_member' => true,
                'currency_code' => 'EUR',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $country->refresh();

        expect($country->iso_code)->toBe('AT');
        expect($country->iso3_code)->toBe('AUT');
        expect($country->name_translations['de'])->toBe('Österreich');
        expect($country->continent_id)->toBe($newContinent->id);
    });

    test('can update country with KeyValue translations', function () {
        $country = Country::factory()->create([
            'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
        ]);

        livewire(CountryResource\Pages\EditCountry::class, [
            'record' => $country->getRouteKey(),
        ])
            ->fillForm([
                'name_translations' => [
                    'de' => 'Deutschland Updated',
                    'en' => 'Germany Updated',
                    'fr' => 'Allemagne',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $country->refresh();

        expect($country->name_translations)->toBe([
            'de' => 'Deutschland Updated',
            'en' => 'Germany Updated',
            'fr' => 'Allemagne',
        ]);
    });

    test('validates unique iso_code on update ignoring current record', function () {
        $country1 = Country::factory()->create(['iso_code' => 'DE']);
        $country2 = Country::factory()->create(['iso_code' => 'FR']);

        livewire(CountryResource\Pages\EditCountry::class, [
            'record' => $country2->getRouteKey(),
        ])
            ->fillForm([
                'iso_code' => 'DE',
            ])
            ->call('save')
            ->assertHasFormErrors(['iso_code' => 'unique']);
    });

    test('can delete country from edit page', function () {
        $country = Country::factory()->create();

        livewire(CountryResource\Pages\EditCountry::class, [
            'record' => $country->getRouteKey(),
        ])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted($country);
    });
});

describe('View Country Page', function () {
    test('can render view page', function () {
        $country = Country::factory()->create();

        livewire(CountryResource\Pages\ViewCountry::class, [
            'record' => $country->getRouteKey(),
        ])
            ->assertSuccessful();
    });

    test('can display country data', function () {
        $country = Country::factory()->create([
            'iso_code' => 'DE',
            'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'],
        ]);

        livewire(CountryResource\Pages\ViewCountry::class, [
            'record' => $country->getRouteKey(),
        ])
            ->assertSee('DE')
            ->assertSee('Deutschland');
    });
});

describe('Country Relationships', function () {
    test('country belongs to continent', function () {
        $continent = Continent::factory()->create();
        $country = Country::factory()->create(['continent_id' => $continent->id]);

        expect($country->continent->id)->toBe($continent->id);
    });

    test('can view regions relation manager', function () {
        $country = Country::factory()
            ->has(Region::factory()->count(3))
            ->create();

        livewire(CountryResource\RelationManagers\RegionsRelationManager::class, [
            'ownerRecord' => $country,
            'pageClass' => CountryResource\Pages\EditCountry::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($country->regions);
    });

    test('can view cities relation manager', function () {
        $country = Country::factory()
            ->has(City::factory()->count(3))
            ->create();

        livewire(CountryResource\RelationManagers\CitiesRelationManager::class, [
            'ownerRecord' => $country,
            'pageClass' => CountryResource\Pages\EditCountry::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($country->cities);
    });

    test('country with regions displays relationship count', function () {
        $country = Country::factory()
            ->has(Region::factory()->count(5))
            ->create();

        expect($country->regions()->count())->toBe(5);
    });

    test('country with cities displays relationship count', function () {
        $country = Country::factory()
            ->has(City::factory()->count(10))
            ->create();

        expect($country->cities()->count())->toBe(10);
    });
});

describe('Country Scopes', function () {
    test('can filter EU member countries using scope', function () {
        Country::factory()->count(3)->create(['is_eu_member' => true]);
        Country::factory()->count(2)->create(['is_eu_member' => false]);

        $euCountries = Country::euMembers()->get();

        expect($euCountries->count())->toBe(3);
    });

    test('can filter countries by continent using scope', function () {
        $continent = Continent::factory()->create();
        Country::factory()->count(3)->create(['continent_id' => $continent->id]);
        Country::factory()->count(2)->create();

        $countriesByContinent = Country::byContinent($continent->id)->get();

        expect($countriesByContinent->count())->toBe(3);
    });
});

describe('Country Soft Deletes', function () {
    test('soft deleted countries are not shown in list', function () {
        $activeCountry = Country::factory()->create();
        $deletedCountry = Country::factory()->create();
        $deletedCountry->delete();

        livewire(CountryResource\Pages\ListCountries::class)
            ->assertCanSeeTableRecords([$activeCountry])
            ->assertCanNotSeeTableRecords([$deletedCountry]);
    });

    test('can restore soft deleted country', function () {
        $country = Country::factory()->create();
        $country->delete();

        $country->restore();

        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
            'deleted_at' => null,
        ]);
    });
});

describe('Relation Manager CRUD Operations', function () {
    describe('Regions Relation Manager', function () {
        test('can render regions relation manager', function () {
            $country = Country::factory()->create();

            livewire(CountryResource\RelationManagers\RegionsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\ViewCountry::class,
            ])
                ->assertSuccessful();
        });

        test('can create region through relation manager', function () {
            $country = Country::factory()->create();

            $data = [
                'code' => 'BY',
                'name_translations.de' => 'Bayern',
                'name_translations.en' => 'Bavaria',
                'description' => 'The largest German state',
                'lat' => 48.7904,
                'lng' => 11.4979,
            ];

            livewire(CountryResource\RelationManagers\RegionsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('create', data: $data)
                ->assertHasNoTableActionErrors();

            $this->assertDatabaseHas('regions', [
                'code' => 'BY',
                'country_id' => $country->id,
                'description' => 'The largest German state',
            ]);
        });

        test('can create region with minimal required fields', function () {
            $country = Country::factory()->create();

            $data = [
                'code' => 'HE',
                'name_translations.de' => 'Hessen',
                'name_translations.en' => 'Hesse',
            ];

            livewire(CountryResource\RelationManagers\RegionsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('create', data: $data)
                ->assertHasNoTableActionErrors();

            $this->assertDatabaseHas('regions', [
                'code' => 'HE',
                'country_id' => $country->id,
            ]);
        });

        test('validates required fields when creating region', function () {
            $country = Country::factory()->create();

            livewire(CountryResource\RelationManagers\RegionsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('create', data: [])
                ->assertHasTableActionErrors([
                    'code' => 'required',
                    'name_translations.de' => 'required',
                ]);
        });

        test('can edit region through relation manager', function () {
            $country = Country::factory()->create();
            $region = \App\Models\Region::factory()->create([
                'country_id' => $country->id,
                'code' => 'BY',
                'name_translations' => ['de' => 'Bayern', 'en' => 'Bavaria'],
            ]);

            livewire(CountryResource\RelationManagers\RegionsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('edit', $region, data: [
                    'code' => 'BAV',
                    'name_translations.de' => 'Bayern Updated',
                    'name_translations.en' => 'Bavaria Updated',
                    'description' => 'Updated description',
                ])
                ->assertHasNoTableActionErrors();

            $region->refresh();
            expect($region->code)->toBe('BAV');
            expect($region->name_translations['de'])->toBe('Bayern Updated');
        });

        test('can delete region through relation manager', function () {
            $country = Country::factory()->create();
            $region = \App\Models\Region::factory()->create(['country_id' => $country->id]);

            livewire(CountryResource\RelationManagers\RegionsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('delete', $region);

            $this->assertSoftDeleted($region);
        });

        test('can search regions by name', function () {
            $country = Country::factory()->create();
            $region1 = \App\Models\Region::factory()->create([
                'country_id' => $country->id,
                'name_translations' => ['de' => 'Bayern', 'en' => 'Bavaria'],
            ]);
            $region2 = \App\Models\Region::factory()->create([
                'country_id' => $country->id,
                'name_translations' => ['de' => 'Hessen', 'en' => 'Hesse'],
            ]);

            livewire(CountryResource\RelationManagers\RegionsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->searchTable('Bayern')
                ->assertCanSeeTableRecords([$region1])
                ->assertCanNotSeeTableRecords([$region2]);
        });
    });

    describe('Cities Relation Manager (from Country)', function () {
        test('can render cities relation manager', function () {
            $country = Country::factory()->create();

            livewire(CountryResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\ViewCountry::class,
            ])
                ->assertSuccessful();
        });

        test('can create city through relation manager', function () {
            $country = Country::factory()->create();
            $region = \App\Models\Region::factory()->create(['country_id' => $country->id]);

            $data = [
                'name_translations.de' => 'München',
                'name_translations.en' => 'Munich',
                'region_id' => $region->id,
                'is_capital' => false,
                'is_regional_capital' => true,
                'population' => 1500000,
                'lat' => 48.1351,
                'lng' => 11.5820,
            ];

            livewire(CountryResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('create', data: $data)
                ->assertHasNoTableActionErrors();

            $this->assertDatabaseHas('cities', [
                'country_id' => $country->id,
                'region_id' => $region->id,
                'is_regional_capital' => true,
            ]);
        });

        test('can create city with minimal required fields', function () {
            $country = Country::factory()->create();

            $data = [
                'name_translations.de' => 'Berlin',
                'name_translations.en' => 'Berlin',
            ];

            livewire(CountryResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('create', data: $data)
                ->assertHasNoTableActionErrors();

            $this->assertDatabaseHas('cities', [
                'country_id' => $country->id,
            ]);
        });

        test('validates required fields when creating city', function () {
            $country = Country::factory()->create();

            livewire(CountryResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('create', data: [])
                ->assertHasTableActionErrors([
                    'name_translations.de' => 'required',
                ]);
        });

        test('can edit city through relation manager', function () {
            $country = Country::factory()->create();
            $city = City::factory()->create([
                'country_id' => $country->id,
                'name_translations' => ['de' => 'München', 'en' => 'Munich'],
            ]);

            livewire(CountryResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('edit', $city, data: [
                    'name_translations.de' => 'München Updated',
                    'name_translations.en' => 'Munich Updated',
                    'population' => 1600000,
                ])
                ->assertHasNoTableActionErrors();

            $city->refresh();
            expect($city->name_translations['de'])->toBe('München Updated');
            expect($city->population)->toBe(1600000);
        });

        test('can delete city through relation manager', function () {
            $country = Country::factory()->create();
            $city = City::factory()->create(['country_id' => $country->id]);

            livewire(CountryResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('delete', $city);

            $this->assertSoftDeleted($city);
        });

        test('can filter cities by capital status', function () {
            $country = Country::factory()->create();
            $capital = City::factory()->create([
                'country_id' => $country->id,
                'is_capital' => true,
            ]);
            $regularCity = City::factory()->create([
                'country_id' => $country->id,
                'is_capital' => false,
            ]);

            livewire(CountryResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->filterTable('is_capital')
                ->assertCanSeeTableRecords([$capital])
                ->assertCanNotSeeTableRecords([$regularCity]);
        });

        test('can search cities by name', function () {
            $country = Country::factory()->create();
            $city1 = City::factory()->create([
                'country_id' => $country->id,
                'name_translations' => ['de' => 'München', 'en' => 'Munich'],
            ]);
            $city2 = City::factory()->create([
                'country_id' => $country->id,
                'name_translations' => ['de' => 'Hamburg', 'en' => 'Hamburg'],
            ]);

            livewire(CountryResource\RelationManagers\CitiesRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->searchTable('München')
                ->assertCanSeeTableRecords([$city1])
                ->assertCanNotSeeTableRecords([$city2]);
        });
    });

    describe('Airports Relation Manager', function () {
        test('can render airports relation manager', function () {
            $country = Country::factory()->create();

            livewire(CountryResource\RelationManagers\AirportsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\ViewCountry::class,
            ])
                ->assertSuccessful();
        });

        test('can create airport through relation manager', function () {
            $country = Country::factory()->create();
            $city = City::factory()->create(['country_id' => $country->id]);

            $data = [
                'name' => 'Munich Airport',
                'iata_code' => 'MUC',
                'icao_code' => 'EDDM',
                'city_id' => $city->id,
                'type' => 'international',
                'altitude' => 453,
                'timezone' => 'Europe/Berlin',
                'lat' => 48.3537,
                'lng' => 11.7751,
            ];

            livewire(CountryResource\RelationManagers\AirportsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('create', data: $data)
                ->assertHasNoTableActionErrors();

            $this->assertDatabaseHas('airports', [
                'name' => 'Munich Airport',
                'iata_code' => 'MUC',
                'country_id' => $country->id,
            ]);
        });

        test('can create airport with minimal required fields', function () {
            $country = Country::factory()->create();

            $data = [
                'name' => 'Test Airport',
            ];

            livewire(CountryResource\RelationManagers\AirportsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('create', data: $data)
                ->assertHasNoTableActionErrors();

            $this->assertDatabaseHas('airports', [
                'name' => 'Test Airport',
                'country_id' => $country->id,
            ]);
        });

        test('validates required fields when creating airport', function () {
            $country = Country::factory()->create();

            livewire(CountryResource\RelationManagers\AirportsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('create', data: [])
                ->assertHasTableActionErrors([
                    'name' => 'required',
                ]);
        });

        test('validates IATA code max length', function () {
            $country = Country::factory()->create();

            livewire(CountryResource\RelationManagers\AirportsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('create', data: [
                    'name' => 'Test Airport',
                    'iata_code' => 'MUCC',
                ])
                ->assertHasTableActionErrors(['iata_code' => 'max']);
        });

        test('validates ICAO code max length', function () {
            $country = Country::factory()->create();

            livewire(CountryResource\RelationManagers\AirportsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('create', data: [
                    'name' => 'Test Airport',
                    'icao_code' => 'EDDMM',
                ])
                ->assertHasTableActionErrors(['icao_code' => 'max']);
        });

        test('can edit airport through relation manager', function () {
            $country = Country::factory()->create();
            $airport = \App\Models\Airport::factory()->create([
                'country_id' => $country->id,
                'name' => 'Munich Airport',
                'iata_code' => 'MUC',
            ]);

            livewire(CountryResource\RelationManagers\AirportsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('edit', $airport, data: [
                    'name' => 'Munich International Airport',
                    'type' => 'large_airport',
                ])
                ->assertHasNoTableActionErrors();

            $airport->refresh();
            expect($airport->name)->toBe('Munich International Airport');
            expect($airport->type)->toBe('large_airport');
        });

        test('can delete airport through relation manager', function () {
            $country = Country::factory()->create();
            $airport = \App\Models\Airport::factory()->create(['country_id' => $country->id]);

            livewire(CountryResource\RelationManagers\AirportsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->callTableAction('delete', $airport);

            $this->assertSoftDeleted($airport);
        });

        test('can filter airports by type', function () {
            $country = Country::factory()->create();
            $international = \App\Models\Airport::factory()->create([
                'country_id' => $country->id,
                'type' => 'international',
            ]);
            $small = \App\Models\Airport::factory()->create([
                'country_id' => $country->id,
                'type' => 'small_airport',
            ]);

            livewire(CountryResource\RelationManagers\AirportsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->filterTable('type', ['international'])
                ->assertCanSeeTableRecords([$international])
                ->assertCanNotSeeTableRecords([$small]);
        });

        test('can filter airports with IATA code', function () {
            $country = Country::factory()->create();
            $withIata = \App\Models\Airport::factory()->create([
                'country_id' => $country->id,
                'iata_code' => 'MUC',
            ]);
            $withoutIata = \App\Models\Airport::factory()->create([
                'country_id' => $country->id,
                'iata_code' => null,
            ]);

            livewire(CountryResource\RelationManagers\AirportsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->filterTable('has_iata')
                ->assertCanSeeTableRecords([$withIata])
                ->assertCanNotSeeTableRecords([$withoutIata]);
        });

        test('can search airports by name', function () {
            $country = Country::factory()->create();
            $airport1 = \App\Models\Airport::factory()->create([
                'country_id' => $country->id,
                'name' => 'Munich Airport',
            ]);
            $airport2 = \App\Models\Airport::factory()->create([
                'country_id' => $country->id,
                'name' => 'Frankfurt Airport',
            ]);

            livewire(CountryResource\RelationManagers\AirportsRelationManager::class, [
                'ownerRecord' => $country,
                'pageClass' => CountryResource\Pages\EditCountry::class,
            ])
                ->searchTable('Munich')
                ->assertCanSeeTableRecords([$airport1])
                ->assertCanNotSeeTableRecords([$airport2]);
        });
    });
});
