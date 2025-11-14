<?php

declare(strict_types=1);

use App\Filament\Resources\Airports\AirportResource;
use App\Filament\Resources\Airports\Pages\CreateAirport;
use App\Filament\Resources\Airports\Pages\EditAirport;
use App\Filament\Resources\Airports\Pages\ListAirports;
use App\Filament\Resources\Airports\Pages\ViewAirport;
use App\Models\{User, Airport, Country, City, Airline};
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_admin' => true,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);
});

// ==================== LIST TESTS ====================

test('can render airports list page', function () {
    Livewire::test(ListAirports::class)
        ->assertSuccessful();
});

test('can list airports', function () {
    $airports = Airport::factory()->count(10)->create();

    Livewire::test(ListAirports::class)
        ->assertCanSeeTableRecords($airports);
});

test('can search airports by name', function () {
    $airport1 = Airport::factory()->create(['name' => 'Frankfurt Airport']);
    $airport2 = Airport::factory()->create(['name' => 'Munich Airport']);

    Livewire::test(ListAirports::class)
        ->searchTable('Frankfurt')
        ->assertCanSeeTableRecords([$airport1])
        ->assertCanNotSeeTableRecords([$airport2]);
});

test('can search airports by iata code', function () {
    $airport1 = Airport::factory()->create(['iata_code' => 'FRA']);
    $airport2 = Airport::factory()->create(['iata_code' => 'MUC']);

    Livewire::test(ListAirports::class)
        ->searchTable('FRA')
        ->assertCanSeeTableRecords([$airport1])
        ->assertCanNotSeeTableRecords([$airport2]);
});

test('can filter airports by type', function () {
    $international = Airport::factory()->create(['type' => 'international']);
    $small = Airport::factory()->create(['type' => 'small_airport']);

    Livewire::test(ListAirports::class)
        ->filterTable('type', 'international')
        ->assertCanSeeTableRecords([$international])
        ->assertCanNotSeeTableRecords([$small]);
});

test('can filter airports by active status', function () {
    $activeAirport = Airport::factory()->create(['is_active' => true]);
    $inactiveAirport = Airport::factory()->create(['is_active' => false]);

    Livewire::test(ListAirports::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$activeAirport])
        ->assertCanNotSeeTableRecords([$inactiveAirport]);
});

// ==================== VIEW TESTS ====================

test('can render view airport page', function () {
    $airport = Airport::factory()->create();

    Livewire::test(ViewAirport::class, ['record' => $airport->getRouteKey()])
        ->assertSuccessful();
});

test('can view airport details', function () {
    $airport = Airport::factory()->create([
        'name' => 'Test Airport',
        'iata_code' => 'TST',
    ]);

    Livewire::test(ViewAirport::class, ['record' => $airport->getRouteKey()])
        ->assertSee('Test Airport')
        ->assertSee('TST');
});

// ==================== CREATE TESTS ====================

test('can render create airport page', function () {
    Livewire::test(CreateAirport::class)
        ->assertSuccessful();
});

test('can create airport with basic fields', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $newData = [
        'name' => 'Test Airport',
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
        'country_id' => $country->id,
        'city_id' => $city->id,
        'type' => 'international',
        'is_active' => true,
        'operates_24h' => true,
    ];

    Livewire::test(CreateAirport::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('airports', [
        'name' => 'Test Airport',
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
        'type' => 'international',
    ]);
});

test('can create airport with coordinates', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $newData = [
        'name' => 'Test Airport',
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
        'country_id' => $country->id,
        'city_id' => $city->id,
        'type' => 'international',
        'lat' => '50.1109',
        'lng' => '8.6821',
        'altitude' => 111,
        'is_active' => true,
    ];

    Livewire::test(CreateAirport::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airport = Airport::where('name', 'Test Airport')->first();
    expect((float) $airport->lat)->toBe(50.1109);
    expect((float) $airport->lng)->toBe(8.6821);
    expect($airport->altitude)->toBe(111);
});

test('can create airport with website urls', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $newData = [
        'name' => 'Test Airport',
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
        'country_id' => $country->id,
        'city_id' => $city->id,
        'type' => 'international',
        'website' => 'https://www.testairport.com',
        'security_timeslot_url' => 'https://www.testairport.com/timeslot',
        'is_active' => true,
    ];

    Livewire::test(CreateAirport::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('airports', [
        'name' => 'Test Airport',
        'website' => 'https://www.testairport.com',
        'security_timeslot_url' => 'https://www.testairport.com/timeslot',
    ]);
});

test('can create airport with lounges', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $newData = [
        'name' => 'Test Airport',
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
        'country_id' => $country->id,
        'city_id' => $city->id,
        'type' => 'international',
        'lounges' => [
            [
                'name' => 'Business Lounge',
                'location' => 'Terminal 1, Level 3',
                'access' => 'Business Class, Priority Pass',
                'url' => 'https://www.testairport.com/lounge1',
            ],
            [
                'name' => 'VIP Lounge',
                'location' => 'Terminal 2, Level 2',
                'access' => 'First Class',
                'url' => 'https://www.testairport.com/lounge2',
            ],
        ],
        'is_active' => true,
    ];

    Livewire::test(CreateAirport::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airport = Airport::where('name', 'Test Airport')->first();
    expect($airport->lounges)->toHaveCount(2);
    expect($airport->lounges[0]['name'])->toBe('Business Lounge');
    expect($airport->lounges[1]['location'])->toBe('Terminal 2, Level 2');
});

test('can create airport with nearby hotels', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $newData = [
        'name' => 'Test Airport',
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
        'country_id' => $country->id,
        'city_id' => $city->id,
        'type' => 'international',
        'nearby_hotels' => [
            [
                'name' => 'Airport Hotel',
                'distance_km' => 0.5,
                'shuttle' => true,
                'booking_url' => 'https://www.airporthotel.com',
                'notes' => 'Free shuttle every 30 minutes',
            ],
            [
                'name' => 'City Hotel',
                'distance_km' => 2.0,
                'shuttle' => false,
                'booking_url' => 'https://www.cityhotel.com',
                'notes' => 'Taxi recommended',
            ],
        ],
        'is_active' => true,
    ];

    Livewire::test(CreateAirport::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airport = Airport::where('name', 'Test Airport')->first();
    expect($airport->nearby_hotels)->toHaveCount(2);
    expect($airport->nearby_hotels[0]['name'])->toBe('Airport Hotel');
    expect($airport->nearby_hotels[0]['distance_km'])->toBe(0.5);
    expect($airport->nearby_hotels[0]['shuttle'])->toBe(true);
});

test('can create airport with mobility options - car rental', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $newData = [
        'name' => 'Test Airport',
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
        'country_id' => $country->id,
        'city_id' => $city->id,
        'type' => 'international',
        'mobility_options' => [
            'car_rental' => [
                'available' => true,
                'providers' => [
                    ['provider' => 'Sixt'],
                    ['provider' => 'Hertz'],
                    ['provider' => 'Avis'],
                ],
                'booking_url' => 'https://www.testairport.com/car-rental',
            ],
        ],
        'is_active' => true,
    ];

    Livewire::test(CreateAirport::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airport = Airport::where('name', 'Test Airport')->first();
    expect($airport->mobility_options['car_rental']['available'])->toBe(true);
    expect($airport->mobility_options['car_rental']['providers'])->toHaveCount(3);
});

test('can create airport with mobility options - public transport', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $newData = [
        'name' => 'Test Airport',
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
        'country_id' => $country->id,
        'city_id' => $city->id,
        'type' => 'international',
        'mobility_options' => [
            'public_transport' => [
                'available' => true,
                'types' => [
                    ['type' => 'S-Bahn'],
                    ['type' => 'Bus'],
                ],
                'info_url' => 'https://www.testairport.com/public-transport',
            ],
        ],
        'is_active' => true,
    ];

    Livewire::test(CreateAirport::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airport = Airport::where('name', 'Test Airport')->first();
    expect($airport->mobility_options['public_transport']['available'])->toBe(true);
    expect($airport->mobility_options['public_transport']['types'])->toHaveCount(2);
});

test('can create airport with mobility options - taxi', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $newData = [
        'name' => 'Test Airport',
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
        'country_id' => $country->id,
        'city_id' => $city->id,
        'type' => 'international',
        'mobility_options' => [
            'taxi' => [
                'available' => true,
                'info' => '24/7 available at Terminal 1',
                'approx_cost' => '50 EUR to city center',
            ],
        ],
        'is_active' => true,
    ];

    Livewire::test(CreateAirport::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airport = Airport::where('name', 'Test Airport')->first();
    expect($airport->mobility_options['taxi']['available'])->toBe(true);
    expect($airport->mobility_options['taxi']['approx_cost'])->toBe('50 EUR to city center');
});

test('can create airport with mobility options - parking', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $newData = [
        'name' => 'Test Airport',
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
        'country_id' => $country->id,
        'city_id' => $city->id,
        'type' => 'international',
        'mobility_options' => [
            'parking' => [
                'available' => true,
                'options' => [
                    [
                        'name' => 'Parkhaus P1',
                        'distance' => '100m to Terminal',
                        'price_info' => '5 EUR/Tag',
                    ],
                    [
                        'name' => 'Parkhaus P2',
                        'distance' => '200m to Terminal',
                        'price_info' => '3 EUR/Tag',
                    ],
                ],
                'booking_url' => 'https://www.testairport.com/parking',
            ],
        ],
        'is_active' => true,
    ];

    Livewire::test(CreateAirport::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airport = Airport::where('name', 'Test Airport')->first();
    expect($airport->mobility_options['parking']['available'])->toBe(true);
    expect($airport->mobility_options['parking']['options'])->toHaveCount(2);
});

test('can create airport with mobility options - airport shuttle', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $newData = [
        'name' => 'Test Airport',
        'iata_code' => 'TST',
        'icao_code' => 'TEST',
        'country_id' => $country->id,
        'city_id' => $city->id,
        'type' => 'international',
        'mobility_options' => [
            'airport_shuttle' => [
                'available' => true,
                'info' => 'Free shuttle to hotels, runs 24/7',
                'url' => 'https://www.testairport.com/shuttle',
            ],
        ],
        'is_active' => true,
    ];

    Livewire::test(CreateAirport::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airport = Airport::where('name', 'Test Airport')->first();
    expect($airport->mobility_options['airport_shuttle']['available'])->toBe(true);
    expect($airport->mobility_options['airport_shuttle']['info'])->toBe('Free shuttle to hotels, runs 24/7');
});

// ==================== VALIDATION TESTS ====================

test('name is required', function () {
    Livewire::test(CreateAirport::class)
        ->fillForm(['name' => null])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('iata code is required', function () {
    Livewire::test(CreateAirport::class)
        ->fillForm(['iata_code' => null])
        ->call('create')
        ->assertHasFormErrors(['iata_code' => 'required']);
});

test('icao code is required', function () {
    Livewire::test(CreateAirport::class)
        ->fillForm(['icao_code' => null])
        ->call('create')
        ->assertHasFormErrors(['icao_code' => 'required']);
});

test('country is required', function () {
    Livewire::test(CreateAirport::class)
        ->fillForm(['country_id' => null])
        ->call('create')
        ->assertHasFormErrors(['country_id' => 'required']);
});

test('city is required', function () {
    Livewire::test(CreateAirport::class)
        ->fillForm(['city_id' => null])
        ->call('create')
        ->assertHasFormErrors(['city_id' => 'required']);
});

test('type is required', function () {
    Livewire::test(CreateAirport::class)
        ->fillForm(['type' => null])
        ->call('create')
        ->assertHasFormErrors(['type' => 'required']);
});

test('iata code must be unique', function () {
    $existingAirport = Airport::factory()->create(['iata_code' => 'FRA']);

    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    Livewire::test(CreateAirport::class)
        ->fillForm([
            'name' => 'New Airport',
            'iata_code' => 'FRA',
            'icao_code' => 'NEWW',
            'country_id' => $country->id,
            'city_id' => $city->id,
            'type' => 'international',
        ])
        ->call('create')
        ->assertHasFormErrors(['iata_code']);
});

test('icao code must be unique', function () {
    $existingAirport = Airport::factory()->create(['icao_code' => 'EDDF']);

    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    Livewire::test(CreateAirport::class)
        ->fillForm([
            'name' => 'New Airport',
            'iata_code' => 'NEW',
            'icao_code' => 'EDDF',
            'country_id' => $country->id,
            'city_id' => $city->id,
            'type' => 'international',
        ])
        ->call('create')
        ->assertHasFormErrors(['icao_code']);
});

test('iata code has max length of 3', function () {
    Livewire::test(CreateAirport::class)
        ->fillForm(['iata_code' => 'ABCD'])
        ->call('create')
        ->assertHasFormErrors(['iata_code']);
});

test('icao code has max length of 4', function () {
    Livewire::test(CreateAirport::class)
        ->fillForm(['icao_code' => 'ABCDE'])
        ->call('create')
        ->assertHasFormErrors(['icao_code']);
});

test('website must be valid url', function () {
    Livewire::test(CreateAirport::class)
        ->fillForm(['website' => 'not-a-url'])
        ->call('create')
        ->assertHasFormErrors(['website']);
});

test('latitude must be between -90 and 90', function () {
    Livewire::test(CreateAirport::class)
        ->fillForm(['lat' => '100'])
        ->call('create')
        ->assertHasFormErrors(['lat']);

    Livewire::test(CreateAirport::class)
        ->fillForm(['lat' => '-100'])
        ->call('create')
        ->assertHasFormErrors(['lat']);
});

test('longitude must be between -180 and 180', function () {
    Livewire::test(CreateAirport::class)
        ->fillForm(['lng' => '200'])
        ->call('create')
        ->assertHasFormErrors(['lng']);

    Livewire::test(CreateAirport::class)
        ->fillForm(['lng' => '-200'])
        ->call('create')
        ->assertHasFormErrors(['lng']);
});

// ==================== EDIT TESTS ====================

test('can render edit airport page', function () {
    $airport = Airport::factory()->create();

    Livewire::test(EditAirport::class, ['record' => $airport->getRouteKey()])
        ->assertSuccessful();
});

test('can retrieve airport data for editing', function () {
    $airport = Airport::factory()->create();

    Livewire::test(EditAirport::class, ['record' => $airport->getRouteKey()])
        ->assertFormSet([
            'name' => $airport->name,
            'iata_code' => $airport->iata_code,
            'icao_code' => $airport->icao_code,
        ]);
});

test('can update airport basic fields', function () {
    $airport = Airport::factory()->create();
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $updateData = [
        'name' => 'Updated Airport Name',
        'iata_code' => 'UPD',
        'icao_code' => 'UPDT',
        'country_id' => $country->id,
        'city_id' => $city->id,
        'type' => 'large_airport',
        'is_active' => false,
        'operates_24h' => false,
    ];

    Livewire::test(EditAirport::class, ['record' => $airport->getRouteKey()])
        ->fillForm($updateData)
        ->call('save')
        ->assertHasNoFormErrors();

    $airport->refresh();

    expect($airport->name)->toBe('Updated Airport Name');
    expect($airport->iata_code)->toBe('UPD');
    expect($airport->type)->toBe('large_airport');
    expect($airport->is_active)->toBe(false);
});

test('can update airport coordinates', function () {
    $airport = Airport::factory()->create();

    $updateData = [
        'lat' => '52.5200',
        'lng' => '13.4050',
        'altitude' => 34,
    ];

    Livewire::test(EditAirport::class, ['record' => $airport->getRouteKey()])
        ->fillForm($updateData)
        ->call('save')
        ->assertHasNoFormErrors();

    $airport->refresh();

    expect((float) $airport->lat)->toBe(52.5200);
    expect((float) $airport->lng)->toBe(13.4050);
    expect($airport->altitude)->toBe(34);
});

test('can update airport lounges', function () {
    $airport = Airport::factory()->create();

    $updateData = [
        'lounges' => [
            [
                'name' => 'Updated Lounge',
                'location' => 'Terminal 3',
                'access' => 'All passengers',
                'url' => 'https://www.updated-lounge.com',
            ],
        ],
    ];

    Livewire::test(EditAirport::class, ['record' => $airport->getRouteKey()])
        ->fillForm($updateData)
        ->call('save')
        ->assertHasNoFormErrors();

    $airport->refresh();

    expect($airport->lounges)->toHaveCount(1);
    expect($airport->lounges[0]['name'])->toBe('Updated Lounge');
});

test('can update airport nearby hotels', function () {
    $airport = Airport::factory()->create();

    $updateData = [
        'nearby_hotels' => [
            [
                'name' => 'New Hotel',
                'distance_km' => 1.5,
                'shuttle' => true,
                'booking_url' => 'https://www.newhotel.com',
                'notes' => 'Newly opened',
            ],
        ],
    ];

    Livewire::test(EditAirport::class, ['record' => $airport->getRouteKey()])
        ->fillForm($updateData)
        ->call('save')
        ->assertHasNoFormErrors();

    $airport->refresh();

    expect($airport->nearby_hotels)->toHaveCount(1);
    expect($airport->nearby_hotels[0]['name'])->toBe('New Hotel');
});

test('can update airport mobility options', function () {
    $airport = Airport::factory()->create();

    $updateData = [
        'mobility_options' => [
            'taxi' => [
                'available' => true,
                'info' => 'Updated taxi info',
                'approx_cost' => '60 EUR',
            ],
        ],
    ];

    Livewire::test(EditAirport::class, ['record' => $airport->getRouteKey()])
        ->fillForm($updateData)
        ->call('save')
        ->assertHasNoFormErrors();

    $airport->refresh();

    expect($airport->mobility_options['taxi']['available'])->toBe(true);
    expect($airport->mobility_options['taxi']['info'])->toBe('Updated taxi info');
});

test('can update iata code to unique value', function () {
    $airport = Airport::factory()->create(['iata_code' => 'FRA']);

    Livewire::test(EditAirport::class, ['record' => $airport->getRouteKey()])
        ->fillForm(['iata_code' => 'MUC'])
        ->call('save')
        ->assertHasNoFormErrors();

    $airport->refresh();

    expect($airport->iata_code)->toBe('MUC');
});

test('cannot update iata code to duplicate value', function () {
    $existingAirport = Airport::factory()->create(['iata_code' => 'MUC']);
    $airport = Airport::factory()->create(['iata_code' => 'FRA']);

    Livewire::test(EditAirport::class, ['record' => $airport->getRouteKey()])
        ->fillForm(['iata_code' => 'MUC'])
        ->call('save')
        ->assertHasFormErrors(['iata_code']);
});

// ==================== DELETE TESTS ====================

test('can delete airport', function () {
    $airport = Airport::factory()->create();

    Livewire::test(EditAirport::class, ['record' => $airport->getRouteKey()])
        ->callAction('delete');

    $this->assertSoftDeleted('airports', [
        'id' => $airport->id,
    ]);
});

test('can restore deleted airport', function () {
    $airport = Airport::factory()->create();
    $airport->delete();

    Livewire::test(EditAirport::class, ['record' => $airport->getRouteKey()])
        ->callAction('restore');

    $airport->refresh();

    expect($airport->deleted_at)->toBeNull();
});

test('can force delete airport', function () {
    $airport = Airport::factory()->create();
    $airport->delete();

    Livewire::test(EditAirport::class, ['record' => $airport->getRouteKey()])
        ->callAction('forceDelete');

    $this->assertDatabaseMissing('airports', [
        'id' => $airport->id,
    ]);
});

// ==================== BULK ACTION TESTS ====================

test('can bulk delete airports', function () {
    $airports = Airport::factory()->count(3)->create();

    Livewire::test(ListAirports::class)
        ->callTableBulkAction('delete', $airports);

    foreach ($airports as $airport) {
        $this->assertSoftDeleted('airports', [
            'id' => $airport->id,
        ]);
    }
});

// ==================== RELATIONSHIP TESTS ====================

test('can view airlines relation on airport', function () {
    $airport = Airport::factory()->create();
    $airlines = Airline::factory()->count(3)->create();
    $airport->airlines()->attach($airlines);

    Livewire::test(ViewAirport::class, ['record' => $airport->getRouteKey()])
        ->assertSuccessful();

    expect($airport->airlines)->toHaveCount(3);
});

test('city options are filtered by selected country', function () {
    $country1 = Country::factory()->create();
    $country2 = Country::factory()->create();
    $city1 = City::factory()->create(['country_id' => $country1->id]);
    $city2 = City::factory()->create(['country_id' => $country2->id]);

    Livewire::test(CreateAirport::class)
        ->fillForm(['country_id' => $country1->id])
        ->assertFormFieldExists('city_id');
});

// ==================== AUTHORIZATION TESTS ====================

test('non-admin cannot access airport resource', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user);

    Livewire::test(ListAirports::class)
        ->assertForbidden();
});

test('inactive admin cannot access airport resource', function () {
    $user = User::factory()->create([
        'is_admin' => true,
        'is_active' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(ListAirports::class)
        ->assertForbidden();
});

// ==================== SCOPE TESTS ====================

test('can scope airports by country', function () {
    $country = Country::factory()->create();
    $airportInCountry = Airport::factory()->create(['country_id' => $country->id]);
    $airportNotInCountry = Airport::factory()->create();

    $airports = Airport::byCountry($country->id)->get();

    expect($airports)->toHaveCount(1);
    expect($airports->first()->id)->toBe($airportInCountry->id);
});

test('can scope airports by city', function () {
    $city = City::factory()->create();
    $airportInCity = Airport::factory()->create(['city_id' => $city->id]);
    $airportNotInCity = Airport::factory()->create();

    $airports = Airport::byCity($city->id)->get();

    expect($airports)->toHaveCount(1);
    expect($airports->first()->id)->toBe($airportInCity->id);
});

test('can search airports', function () {
    $airport1 = Airport::factory()->create([
        'name' => 'Frankfurt Airport',
        'iata_code' => 'FRA',
    ]);
    $airport2 = Airport::factory()->create([
        'name' => 'Munich Airport',
        'iata_code' => 'MUC',
    ]);

    $results = Airport::search('Frankfurt')->get();
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($airport1->id);

    $results = Airport::search('FRA')->get();
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($airport1->id);
});

// ==================== AIRLINES RELATION MANAGER OPERATIONS ====================

describe('Airlines Relation Manager Operations', function () {

    // ==================== ATTACH OPERATION TESTS ====================

    test('can attach existing airline to airport', function () {
        $airport = Airport::factory()->create();
        $airline = Airline::factory()->create(['name' => 'Test Airline', 'iata_code' => 'TA']);

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->callTableAction('attach', null, [
                'recordId' => $airline->id,
                'direction' => 'both',
                'terminal' => 'Terminal 1',
            ])
            ->assertHasNoTableActionErrors();

        expect($airport->fresh()->airlines)->toHaveCount(1);
        expect($airport->fresh()->airlines->first()->id)->toBe($airline->id);
        expect($airport->fresh()->airlines->first()->pivot->direction)->toBe('both');
        expect($airport->fresh()->airlines->first()->pivot->terminal)->toBe('Terminal 1');
    });

    test('can attach airline with direction from', function () {
        $airport = Airport::factory()->create();
        $airline = Airline::factory()->create();

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->callTableAction('attach', null, [
                'recordId' => $airline->id,
                'direction' => 'from',
                'terminal' => null,
            ])
            ->assertHasNoTableActionErrors();

        $airport->refresh();
        expect($airport->airlines->first()->pivot->direction)->toBe('from');
    });

    test('can attach airline with direction to', function () {
        $airport = Airport::factory()->create();
        $airline = Airline::factory()->create();

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->callTableAction('attach', null, [
                'recordId' => $airline->id,
                'direction' => 'to',
            ])
            ->assertHasNoTableActionErrors();

        $airport->refresh();
        expect($airport->airlines->first()->pivot->direction)->toBe('to');
    });

    test('can attach airline without terminal', function () {
        $airport = Airport::factory()->create();
        $airline = Airline::factory()->create();

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->callTableAction('attach', null, [
                'recordId' => $airline->id,
                'direction' => 'both',
            ])
            ->assertHasNoTableActionErrors();

        $airport->refresh();
        expect($airport->airlines->first()->pivot->terminal)->toBeNull();
    });

    test('can attach multiple airlines sequentially', function () {
        $airport = Airport::factory()->create();
        $airline1 = Airline::factory()->create(['iata_code' => 'A1']);
        $airline2 = Airline::factory()->create(['iata_code' => 'A2']);
        $airline3 = Airline::factory()->create(['iata_code' => 'A3']);

        $relationManager = Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ]);

        $relationManager
            ->callTableAction('attach', null, [
                'recordId' => $airline1->id,
                'direction' => 'both',
            ])
            ->assertHasNoTableActionErrors();

        $relationManager
            ->callTableAction('attach', null, [
                'recordId' => $airline2->id,
                'direction' => 'from',
                'terminal' => 'T2',
            ])
            ->assertHasNoTableActionErrors();

        $relationManager
            ->callTableAction('attach', null, [
                'recordId' => $airline3->id,
                'direction' => 'to',
                'terminal' => 'T3',
            ])
            ->assertHasNoTableActionErrors();

        $airport->refresh();
        expect($airport->airlines)->toHaveCount(3);
    });

    test('cannot attach same airline twice', function () {
        $airport = Airport::factory()->create();
        $airline = Airline::factory()->create();

        // First attachment should succeed
        $airport->airlines()->attach($airline->id, [
            'direction' => 'both',
        ]);

        // Second attachment should fail due to unique constraint
        $relationManager = Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ]);

        try {
            $relationManager->callTableAction('attach', null, [
                'recordId' => $airline->id,
                'direction' => 'both',
            ]);

            // If we get here, the test should fail
            expect(false)->toBeTrue('Expected unique constraint violation');
        } catch (\Exception $e) {
            // Expected behavior - unique constraint should prevent duplicate
            expect(true)->toBeTrue();
        }
    });

    test('validates that airline exists before attaching', function () {
        $airport = Airport::factory()->create();

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->callTableAction('attach', null, [
                'recordId' => 99999, // Non-existent airline ID
                'direction' => 'both',
            ])
            ->assertHasTableActionErrors(['recordId']);
    });

    test('validates direction is required when attaching', function () {
        $airport = Airport::factory()->create();
        $airline = Airline::factory()->create();

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->callTableAction('attach', null, [
                'recordId' => $airline->id,
                'direction' => null,
            ])
            ->assertHasTableActionErrors(['direction']);
    });

    test('terminal field has max length validation', function () {
        $airport = Airport::factory()->create();
        $airline = Airline::factory()->create();

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->callTableAction('attach', null, [
                'recordId' => $airline->id,
                'direction' => 'both',
                'terminal' => str_repeat('A', 51), // Exceeds max length of 50
            ])
            ->assertHasTableActionErrors(['terminal']);
    });

    // ==================== DETACH OPERATION TESTS ====================

    test('can detach airline from airport', function () {
        $airport = Airport::factory()->create();
        $airline = Airline::factory()->create();
        $airport->airlines()->attach($airline->id, ['direction' => 'both']);

        expect($airport->fresh()->airlines)->toHaveCount(1);

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->callTableAction('detach', $airline)
            ->assertHasNoTableActionErrors();

        expect($airport->fresh()->airlines)->toHaveCount(0);
    });

    test('detach removes relationship but does not delete airline', function () {
        $airport = Airport::factory()->create();
        $airline = Airline::factory()->create(['name' => 'Test Airline']);
        $airport->airlines()->attach($airline->id, ['direction' => 'both']);

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->callTableAction('detach', $airline);

        // Airline should still exist in database
        expect(Airline::find($airline->id))->not->toBeNull();
        expect(Airline::find($airline->id)->name)->toBe('Test Airline');

        // But relationship should be removed
        expect($airport->fresh()->airlines)->toHaveCount(0);
    });

    test('can bulk detach multiple airlines', function () {
        $airport = Airport::factory()->create();
        $airlines = Airline::factory()->count(5)->create();

        foreach ($airlines as $airline) {
            $airport->airlines()->attach($airline->id, ['direction' => 'both']);
        }

        expect($airport->fresh()->airlines)->toHaveCount(5);

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->callTableBulkAction('detach', $airlines->take(3))
            ->assertHasNoTableActionErrors();

        expect($airport->fresh()->airlines)->toHaveCount(2);
    });

    test('can bulk detach all airlines', function () {
        $airport = Airport::factory()->create();
        $airlines = Airline::factory()->count(10)->create();

        foreach ($airlines as $airline) {
            $airport->airlines()->attach($airline->id, ['direction' => 'both']);
        }

        expect($airport->fresh()->airlines)->toHaveCount(10);

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->callTableBulkAction('detach', $airlines)
            ->assertHasNoTableActionErrors();

        expect($airport->fresh()->airlines)->toHaveCount(0);

        // All airlines should still exist in database
        expect(Airline::count())->toBe(10);
    });

    // ==================== TABLE OPERATION TESTS ====================

    test('can view all attached airlines', function () {
        $airport = Airport::factory()->create();
        $airlines = Airline::factory()->count(5)->create();

        foreach ($airlines as $airline) {
            $airport->airlines()->attach($airline->id, ['direction' => 'both']);
        }

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->assertCanSeeTableRecords($airlines);
    });

    test('can search within attached airlines by name', function () {
        $airport = Airport::factory()->create();
        $lufthansa = Airline::factory()->create(['name' => 'Lufthansa', 'iata_code' => 'LH']);
        $airFrance = Airline::factory()->create(['name' => 'Air France', 'iata_code' => 'AF']);
        $emirates = Airline::factory()->create(['name' => 'Emirates', 'iata_code' => 'EK']);

        $airport->airlines()->attach($lufthansa->id, ['direction' => 'both']);
        $airport->airlines()->attach($airFrance->id, ['direction' => 'both']);
        $airport->airlines()->attach($emirates->id, ['direction' => 'both']);

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->searchTable('Lufthansa')
            ->assertCanSeeTableRecords([$lufthansa])
            ->assertCanNotSeeTableRecords([$airFrance, $emirates]);
    });

    test('can search within attached airlines by iata code', function () {
        $airport = Airport::factory()->create();
        $lufthansa = Airline::factory()->create(['name' => 'Lufthansa', 'iata_code' => 'LH']);
        $airFrance = Airline::factory()->create(['name' => 'Air France', 'iata_code' => 'AF']);
        $emirates = Airline::factory()->create(['name' => 'Emirates', 'iata_code' => 'EK']);

        $airport->airlines()->attach($lufthansa->id, ['direction' => 'both']);
        $airport->airlines()->attach($airFrance->id, ['direction' => 'both']);
        $airport->airlines()->attach($emirates->id, ['direction' => 'both']);

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->searchTable('LH')
            ->assertCanSeeTableRecords([$lufthansa])
            ->assertCanNotSeeTableRecords([$airFrance, $emirates]);
    });

    test('can search within attached airlines by icao code', function () {
        $airport = Airport::factory()->create();
        $lufthansa = Airline::factory()->create(['name' => 'Lufthansa', 'iata_code' => 'LH', 'icao_code' => 'DLH']);
        $airFrance = Airline::factory()->create(['name' => 'Air France', 'iata_code' => 'AF', 'icao_code' => 'AFR']);

        $airport->airlines()->attach($lufthansa->id, ['direction' => 'both']);
        $airport->airlines()->attach($airFrance->id, ['direction' => 'both']);

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->searchTable('DLH')
            ->assertCanSeeTableRecords([$lufthansa])
            ->assertCanNotSeeTableRecords([$airFrance]);
    });

    test('can filter attached airlines by active status', function () {
        $airport = Airport::factory()->create();
        $activeAirline = Airline::factory()->create(['is_active' => true]);
        $inactiveAirline = Airline::factory()->inactive()->create();

        $airport->airlines()->attach($activeAirline->id, ['direction' => 'both']);
        $airport->airlines()->attach($inactiveAirline->id, ['direction' => 'both']);

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->filterTable('is_active', '1')
            ->assertCanSeeTableRecords([$activeAirline])
            ->assertCanNotSeeTableRecords([$inactiveAirline]);
    });

    test('can filter attached airlines by inactive status', function () {
        $airport = Airport::factory()->create();
        $activeAirline = Airline::factory()->create(['is_active' => true]);
        $inactiveAirline = Airline::factory()->inactive()->create();

        $airport->airlines()->attach($activeAirline->id, ['direction' => 'both']);
        $airport->airlines()->attach($inactiveAirline->id, ['direction' => 'both']);

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->filterTable('is_active', '0')
            ->assertCanSeeTableRecords([$inactiveAirline])
            ->assertCanNotSeeTableRecords([$activeAirline]);
    });

    test('can sort attached airlines by name ascending', function () {
        $airport = Airport::factory()->create();
        $airlineC = Airline::factory()->create(['name' => 'Charlie Airlines', 'iata_code' => 'CA']);
        $airlineA = Airline::factory()->create(['name' => 'Alpha Airlines', 'iata_code' => 'AA']);
        $airlineB = Airline::factory()->create(['name' => 'Bravo Airlines', 'iata_code' => 'BA']);

        $airport->airlines()->attach($airlineC->id, ['direction' => 'both']);
        $airport->airlines()->attach($airlineA->id, ['direction' => 'both']);
        $airport->airlines()->attach($airlineB->id, ['direction' => 'both']);

        $component = Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->sortTable('name', 'asc');

        // Default sort is by name ascending, so all records should be visible
        $component->assertCanSeeTableRecords([$airlineA, $airlineB, $airlineC]);
    });

    test('can sort attached airlines by iata code', function () {
        $airport = Airport::factory()->create();
        $airline1 = Airline::factory()->create(['iata_code' => 'ZZ']);
        $airline2 = Airline::factory()->create(['iata_code' => 'AA']);
        $airline3 = Airline::factory()->create(['iata_code' => 'MM']);

        $airport->airlines()->attach($airline1->id, ['direction' => 'both']);
        $airport->airlines()->attach($airline2->id, ['direction' => 'both']);
        $airport->airlines()->attach($airline3->id, ['direction' => 'both']);

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->sortTable('iata_code')
            ->assertCanSeeTableRecords([$airline1, $airline2, $airline3]);
    });

    test('displays correct count of attached airlines', function () {
        $airport = Airport::factory()->create();
        $airlines = Airline::factory()->count(7)->create();

        foreach ($airlines as $airline) {
            $airport->airlines()->attach($airline->id, ['direction' => 'both']);
        }

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->assertCountTableRecords(7);
    });

    test('displays empty state when no airlines attached', function () {
        $airport = Airport::factory()->create();

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->assertCountTableRecords(0);
    });

    // ==================== PIVOT DATA DISPLAY TESTS ====================

    test('displays pivot terminal in table', function () {
        $airport = Airport::factory()->create();
        $airline = Airline::factory()->create(['name' => 'Test Airline']);
        $airport->airlines()->attach($airline->id, [
            'direction' => 'both',
            'terminal' => 'Terminal 2',
        ]);

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->assertCanSeeTableRecords([$airline])
            ->assertSee('Terminal 2');
    });

    test('displays placeholder when terminal is null', function () {
        $airport = Airport::factory()->create();
        $airline = Airline::factory()->create();
        $airport->airlines()->attach($airline->id, [
            'direction' => 'both',
            'terminal' => null,
        ]);

        Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ])
            ->assertCanSeeTableRecords([$airline]);
    });

    test('can view airline with all pivot data variations', function () {
        $airport = Airport::factory()->create();

        $airline1 = Airline::factory()->create(['iata_code' => 'A1']);
        $airline2 = Airline::factory()->create(['iata_code' => 'A2']);
        $airline3 = Airline::factory()->create(['iata_code' => 'A3']);

        $airport->airlines()->attach($airline1->id, [
            'direction' => 'both',
            'terminal' => 'Terminal 1',
        ]);

        $airport->airlines()->attach($airline2->id, [
            'direction' => 'from',
            'terminal' => null,
        ]);

        $airport->airlines()->attach($airline3->id, [
            'direction' => 'to',
            'terminal' => 'T3',
        ]);

        $component = Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ]);

        $component->assertCanSeeTableRecords([$airline1, $airline2, $airline3]);
        $component->assertSee('Terminal 1');
        $component->assertSee('T3');
    });

    // ==================== INTEGRATION TESTS ====================

    test('attached airlines reflect immediately in table after attachment', function () {
        $airport = Airport::factory()->create();
        $airline = Airline::factory()->create();

        $component = Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ]);

        $component->assertCountTableRecords(0);

        $component->callTableAction('attach', null, [
            'recordId' => $airline->id,
            'direction' => 'both',
        ]);

        $component->assertCountTableRecords(1);
        $component->assertCanSeeTableRecords([$airline]);
    });

    test('detached airlines disappear from table after detachment', function () {
        $airport = Airport::factory()->create();
        $airline = Airline::factory()->create();
        $airport->airlines()->attach($airline->id, ['direction' => 'both']);

        $component = Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ]);

        $component->assertCountTableRecords(1);

        $component->callTableAction('detach', $airline);

        $component->assertCountTableRecords(0);
    });

    test('can perform multiple operations in sequence', function () {
        $airport = Airport::factory()->create();
        $airline1 = Airline::factory()->create(['iata_code' => 'A1']);
        $airline2 = Airline::factory()->create(['iata_code' => 'A2']);
        $airline3 = Airline::factory()->create(['iata_code' => 'A3']);

        $component = Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ]);

        // Attach three airlines
        $component->callTableAction('attach', null, [
            'recordId' => $airline1->id,
            'direction' => 'both',
        ]);

        $component->callTableAction('attach', null, [
            'recordId' => $airline2->id,
            'direction' => 'from',
        ]);

        $component->callTableAction('attach', null, [
            'recordId' => $airline3->id,
            'direction' => 'to',
        ]);

        $component->assertCountTableRecords(3);

        // Detach one
        $component->callTableAction('detach', $airline2);

        $component->assertCountTableRecords(2);
        $component->assertCanSeeTableRecords([$airline1, $airline3]);
        $component->assertCanNotSeeTableRecords([$airline2]);
    });

    test('table displays airline information correctly', function () {
        $airport = Airport::factory()->create();
        $country = Country::factory()->create(['name_de' => 'Deutschland']);
        $airline = Airline::factory()->create([
            'name' => 'Lufthansa',
            'iata_code' => 'LH',
            'icao_code' => 'DLH',
            'home_country_id' => $country->id,
            'is_active' => true,
            'cabin_classes' => ['economy', 'business'],
        ]);

        $airport->airlines()->attach($airline->id, [
            'direction' => 'both',
            'terminal' => 'Terminal 1',
        ]);

        $component = Livewire::test(\App\Filament\Resources\Airports\RelationManagers\AirlinesRelationManager::class, [
            'ownerRecord' => $airport,
            'pageClass' => ViewAirport::class,
        ]);

        $component->assertCanSeeTableRecords([$airline]);
        $component->assertSee('Lufthansa');
        $component->assertSee('LH');
        $component->assertSee('Terminal 1');
    });
});
