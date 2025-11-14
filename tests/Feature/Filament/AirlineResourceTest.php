<?php

declare(strict_types=1);

use App\Filament\Resources\Airlines\AirlineResource;
use App\Filament\Resources\Airlines\Pages\CreateAirline;
use App\Filament\Resources\Airlines\Pages\EditAirline;
use App\Filament\Resources\Airlines\Pages\ListAirlines;
use App\Models\{User, Airline, Country, Airport};
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

test('can render airlines list page', function () {
    Livewire::test(ListAirlines::class)
        ->assertSuccessful();
});

test('can list airlines', function () {
    $airlines = Airline::factory()->count(10)->create();

    Livewire::test(ListAirlines::class)
        ->assertCanSeeTableRecords($airlines);
});

test('can search airlines by name', function () {
    $airline1 = Airline::factory()->create(['name' => 'Lufthansa']);
    $airline2 = Airline::factory()->create(['name' => 'Air France']);

    Livewire::test(ListAirlines::class)
        ->searchTable('Lufthansa')
        ->assertCanSeeTableRecords([$airline1])
        ->assertCanNotSeeTableRecords([$airline2]);
});

test('can search airlines by iata code', function () {
    $airline1 = Airline::factory()->create(['iata_code' => 'LH']);
    $airline2 = Airline::factory()->create(['iata_code' => 'AF']);

    Livewire::test(ListAirlines::class)
        ->searchTable('LH')
        ->assertCanSeeTableRecords([$airline1])
        ->assertCanNotSeeTableRecords([$airline2]);
});

test('can filter airlines by active status', function () {
    $activeAirline = Airline::factory()->create(['is_active' => true]);
    $inactiveAirline = Airline::factory()->inactive()->create();

    Livewire::test(ListAirlines::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$activeAirline])
        ->assertCanNotSeeTableRecords([$inactiveAirline]);
});

// ==================== CREATE TESTS ====================

test('can render create airline page', function () {
    Livewire::test(CreateAirline::class)
        ->assertSuccessful();
});

test('can create airline with basic fields', function () {
    $country = Country::factory()->create();

    $newData = [
        'name' => 'Test Airline',
        'iata_code' => 'TA',
        'icao_code' => 'TST',
        'home_country_id' => $country->id,
        'headquarters' => 'Frankfurt',
        'website' => 'https://www.testairline.com',
        'booking_url' => 'https://www.testairline.com/booking',
        'is_active' => true,
    ];

    Livewire::test(CreateAirline::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('airlines', [
        'name' => 'Test Airline',
        'iata_code' => 'TA',
        'icao_code' => 'TST',
        'headquarters' => 'Frankfurt',
    ]);
});

test('can create airline with contact info', function () {
    $country = Country::factory()->create();

    $newData = [
        'name' => 'Test Airline',
        'iata_code' => 'TA',
        'icao_code' => 'TST',
        'home_country_id' => $country->id,
        'contact_info' => [
            'hotline' => '+49 123 456789',
            'email' => 'contact@testairline.com',
            'chat_url' => 'https://www.testairline.com/chat',
            'help_url' => 'https://www.testairline.com/help',
        ],
        'is_active' => true,
    ];

    Livewire::test(CreateAirline::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airline = Airline::where('name', 'Test Airline')->first();
    expect($airline->contact_info)->toBe([
        'hotline' => '+49 123 456789',
        'email' => 'contact@testairline.com',
        'chat_url' => 'https://www.testairline.com/chat',
        'help_url' => 'https://www.testairline.com/help',
    ]);
});

test('can create airline with baggage rules', function () {
    $country = Country::factory()->create();

    $newData = [
        'name' => 'Test Airline',
        'iata_code' => 'TA',
        'icao_code' => 'TST',
        'home_country_id' => $country->id,
        'baggage_rules' => [
            'checked_baggage' => [
                'economy' => '1x23kg',
                'premium_economy' => '2x23kg',
                'business' => '2x32kg',
                'first' => '3x32kg',
            ],
            'hand_baggage' => [
                'economy' => '1x8kg',
                'premium_economy' => '2x8kg',
                'business' => '2x8kg',
                'first' => '2x8kg',
            ],
            'hand_baggage_dimensions' => [
                'economy' => [
                    'length' => 55,
                    'width' => 40,
                    'height' => 23,
                ],
                'business' => [
                    'length' => 55,
                    'width' => 40,
                    'height' => 23,
                ],
            ],
        ],
        'is_active' => true,
    ];

    Livewire::test(CreateAirline::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airline = Airline::where('name', 'Test Airline')->first();
    expect($airline->baggage_rules['checked_baggage']['economy'])->toBe('1x23kg');
    expect($airline->baggage_rules['hand_baggage_dimensions']['economy']['length'])->toBe(55);
});

test('can create airline with cabin classes', function () {
    $country = Country::factory()->create();

    $newData = [
        'name' => 'Test Airline',
        'iata_code' => 'TA',
        'icao_code' => 'TST',
        'home_country_id' => $country->id,
        'cabin_classes' => ['economy', 'business', 'first'],
        'is_active' => true,
    ];

    Livewire::test(CreateAirline::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airline = Airline::where('name', 'Test Airline')->first();
    expect($airline->cabin_classes)->toBe(['economy', 'business', 'first']);
});

test('can create airline with pet policy', function () {
    $country = Country::factory()->create();

    $newData = [
        'name' => 'Test Airline',
        'iata_code' => 'TA',
        'icao_code' => 'TST',
        'home_country_id' => $country->id,
        'pet_policy' => [
            'allowed' => true,
            'in_cabin' => [
                'max_weight' => '8kg',
                'carrier_size' => '55x40x23cm',
            ],
            'in_hold' => [
                'max_weight' => '75kg',
                'notes' => 'Only certain breeds allowed',
            ],
            'info_url' => 'https://www.testairline.com/pets',
            'notes' => 'Pets must be in approved carriers',
        ],
        'is_active' => true,
    ];

    Livewire::test(CreateAirline::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airline = Airline::where('name', 'Test Airline')->first();
    expect($airline->pet_policy['allowed'])->toBe(true);
    expect($airline->pet_policy['in_cabin']['max_weight'])->toBe('8kg');
});

test('can create airline with lounges', function () {
    $country = Country::factory()->create();

    $newData = [
        'name' => 'Test Airline',
        'iata_code' => 'TA',
        'icao_code' => 'TST',
        'home_country_id' => $country->id,
        'lounges' => [
            [
                'name' => 'Business Lounge',
                'location' => 'Frankfurt Terminal 1',
                'access' => 'Business Class',
                'url' => 'https://www.testairline.com/lounge1',
            ],
            [
                'name' => 'First Class Lounge',
                'location' => 'Munich Terminal 2',
                'access' => 'First Class',
                'url' => 'https://www.testairline.com/lounge2',
            ],
        ],
        'is_active' => true,
    ];

    Livewire::test(CreateAirline::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airline = Airline::where('name', 'Test Airline')->first();
    expect($airline->lounges)->toHaveCount(2);
    expect($airline->lounges[0]['name'])->toBe('Business Lounge');
});

test('can create airline with airport relationships', function () {
    $country = Country::factory()->create();
    $airports = Airport::factory()->count(3)->create();

    $newData = [
        'name' => 'Test Airline',
        'iata_code' => 'TA',
        'icao_code' => 'TST',
        'home_country_id' => $country->id,
        'airports' => $airports->pluck('id')->toArray(),
        'is_active' => true,
    ];

    Livewire::test(CreateAirline::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $airline = Airline::where('name', 'Test Airline')->first();
    expect($airline->airports)->toHaveCount(3);
});

// ==================== VALIDATION TESTS ====================

test('name is required', function () {
    Livewire::test(CreateAirline::class)
        ->fillForm(['name' => null])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('name has max length validation', function () {
    Livewire::test(CreateAirline::class)
        ->fillForm(['name' => Str::random(256)])
        ->call('create')
        ->assertHasFormErrors(['name']);
});

test('iata code must be unique', function () {
    $existingAirline = Airline::factory()->create(['iata_code' => 'LH']);

    $country = Country::factory()->create();

    Livewire::test(CreateAirline::class)
        ->fillForm([
            'name' => 'New Airline',
            'iata_code' => 'LH',
            'icao_code' => 'NEW',
            'home_country_id' => $country->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['iata_code']);
});

test('icao code must be unique', function () {
    $existingAirline = Airline::factory()->create(['icao_code' => 'DLH']);

    $country = Country::factory()->create();

    Livewire::test(CreateAirline::class)
        ->fillForm([
            'name' => 'New Airline',
            'iata_code' => 'NW',
            'icao_code' => 'DLH',
            'home_country_id' => $country->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['icao_code']);
});

test('iata code has max length of 2', function () {
    Livewire::test(CreateAirline::class)
        ->fillForm(['iata_code' => 'ABC'])
        ->call('create')
        ->assertHasFormErrors(['iata_code']);
});

test('icao code has max length of 3', function () {
    Livewire::test(CreateAirline::class)
        ->fillForm(['icao_code' => 'ABCD'])
        ->call('create')
        ->assertHasFormErrors(['icao_code']);
});

test('website must be valid url', function () {
    Livewire::test(CreateAirline::class)
        ->fillForm(['website' => 'not-a-url'])
        ->call('create')
        ->assertHasFormErrors(['website']);
});

test('booking url must be valid url', function () {
    Livewire::test(CreateAirline::class)
        ->fillForm(['booking_url' => 'not-a-url'])
        ->call('create')
        ->assertHasFormErrors(['booking_url']);
});

test('contact email must be valid email', function () {
    Livewire::test(CreateAirline::class)
        ->fillForm([
            'contact_info' => [
                'email' => 'not-an-email',
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['contact_info.email']);
});

// ==================== EDIT TESTS ====================

test('can render edit airline page', function () {
    $airline = Airline::factory()->create();

    Livewire::test(EditAirline::class, ['record' => $airline->getRouteKey()])
        ->assertSuccessful();
});

test('can retrieve airline data for editing', function () {
    $airline = Airline::factory()->create();

    Livewire::test(EditAirline::class, ['record' => $airline->getRouteKey()])
        ->assertFormSet([
            'name' => $airline->name,
            'iata_code' => $airline->iata_code,
            'icao_code' => $airline->icao_code,
        ]);
});

test('can update airline basic fields', function () {
    $airline = Airline::factory()->create();
    $country = Country::factory()->create();

    $updateData = [
        'name' => 'Updated Airline Name',
        'iata_code' => 'UP',
        'icao_code' => 'UPD',
        'home_country_id' => $country->id,
        'headquarters' => 'Updated City',
        'is_active' => false,
    ];

    Livewire::test(EditAirline::class, ['record' => $airline->getRouteKey()])
        ->fillForm($updateData)
        ->call('save')
        ->assertHasNoFormErrors();

    $airline->refresh();

    expect($airline->name)->toBe('Updated Airline Name');
    expect($airline->iata_code)->toBe('UP');
    expect($airline->headquarters)->toBe('Updated City');
    expect($airline->is_active)->toBe(false);
});

test('can update airline contact info', function () {
    $airline = Airline::factory()->create();

    $updateData = [
        'contact_info' => [
            'hotline' => '+49 999 888777',
            'email' => 'newemail@airline.com',
            'chat_url' => 'https://www.newchat.com',
            'help_url' => 'https://www.newhelp.com',
        ],
    ];

    Livewire::test(EditAirline::class, ['record' => $airline->getRouteKey()])
        ->fillForm($updateData)
        ->call('save')
        ->assertHasNoFormErrors();

    $airline->refresh();

    expect($airline->contact_info['email'])->toBe('newemail@airline.com');
    expect($airline->contact_info['hotline'])->toBe('+49 999 888777');
});

test('can update airline baggage rules', function () {
    $airline = Airline::factory()->create();

    $updateData = [
        'baggage_rules' => [
            'checked_baggage' => [
                'economy' => '2x23kg',
                'business' => '3x32kg',
            ],
            'hand_baggage' => [
                'economy' => '1x10kg',
            ],
        ],
    ];

    Livewire::test(EditAirline::class, ['record' => $airline->getRouteKey()])
        ->fillForm($updateData)
        ->call('save')
        ->assertHasNoFormErrors();

    $airline->refresh();

    expect($airline->baggage_rules['checked_baggage']['economy'])->toBe('2x23kg');
});

test('can update airline cabin classes', function () {
    $airline = Airline::factory()->create(['cabin_classes' => ['economy']]);

    $updateData = [
        'cabin_classes' => ['economy', 'premium_economy', 'business', 'first'],
    ];

    Livewire::test(EditAirline::class, ['record' => $airline->getRouteKey()])
        ->fillForm($updateData)
        ->call('save')
        ->assertHasNoFormErrors();

    $airline->refresh();

    expect($airline->cabin_classes)->toHaveCount(4);
    expect($airline->cabin_classes)->toContain('first');
});

test('can update airline lounges', function () {
    $airline = Airline::factory()->create();

    $updateData = [
        'lounges' => [
            [
                'name' => 'Premium Lounge',
                'location' => 'Berlin Terminal 1',
                'access' => 'Premium Economy',
                'url' => 'https://www.airline.com/premium-lounge',
            ],
        ],
    ];

    Livewire::test(EditAirline::class, ['record' => $airline->getRouteKey()])
        ->fillForm($updateData)
        ->call('save')
        ->assertHasNoFormErrors();

    $airline->refresh();

    expect($airline->lounges)->toHaveCount(1);
    expect($airline->lounges[0]['name'])->toBe('Premium Lounge');
});

test('can update airline airport relationships', function () {
    $airline = Airline::factory()->create();
    $newAirports = Airport::factory()->count(5)->create();

    $updateData = [
        'airports' => $newAirports->pluck('id')->toArray(),
    ];

    Livewire::test(EditAirline::class, ['record' => $airline->getRouteKey()])
        ->fillForm($updateData)
        ->call('save')
        ->assertHasNoFormErrors();

    $airline->refresh();

    expect($airline->airports)->toHaveCount(5);
});

test('can update iata code to unique value', function () {
    $airline = Airline::factory()->create(['iata_code' => 'LH']);

    Livewire::test(EditAirline::class, ['record' => $airline->getRouteKey()])
        ->fillForm(['iata_code' => 'UP'])
        ->call('save')
        ->assertHasNoFormErrors();

    $airline->refresh();

    expect($airline->iata_code)->toBe('UP');
});

test('cannot update iata code to duplicate value', function () {
    $existingAirline = Airline::factory()->create(['iata_code' => 'AF']);
    $airline = Airline::factory()->create(['iata_code' => 'LH']);

    Livewire::test(EditAirline::class, ['record' => $airline->getRouteKey()])
        ->fillForm(['iata_code' => 'AF'])
        ->call('save')
        ->assertHasFormErrors(['iata_code']);
});

// ==================== DELETE TESTS ====================

test('can delete airline', function () {
    $airline = Airline::factory()->create();

    Livewire::test(EditAirline::class, ['record' => $airline->getRouteKey()])
        ->callAction('delete');

    $this->assertSoftDeleted('airlines', [
        'id' => $airline->id,
    ]);
});

test('can restore deleted airline', function () {
    $airline = Airline::factory()->create();
    $airline->delete();

    Livewire::test(EditAirline::class, ['record' => $airline->getRouteKey()])
        ->callAction('restore');

    $airline->refresh();

    expect($airline->deleted_at)->toBeNull();
});

test('can force delete airline', function () {
    $airline = Airline::factory()->create();
    $airline->delete();

    Livewire::test(EditAirline::class, ['record' => $airline->getRouteKey()])
        ->callAction('forceDelete');

    $this->assertDatabaseMissing('airlines', [
        'id' => $airline->id,
    ]);
});

// ==================== BULK ACTION TESTS ====================

test('can bulk delete airlines', function () {
    $airlines = Airline::factory()->count(3)->create();

    Livewire::test(ListAirlines::class)
        ->callTableBulkAction('delete', $airlines);

    foreach ($airlines as $airline) {
        $this->assertSoftDeleted('airlines', [
            'id' => $airline->id,
        ]);
    }
});

// ==================== AUTHORIZATION TESTS ====================

test('non-admin cannot access airline resource', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user);

    Livewire::test(ListAirlines::class)
        ->assertForbidden();
});

test('inactive admin cannot access airline resource', function () {
    $user = User::factory()->create([
        'is_admin' => true,
        'is_active' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(ListAirlines::class)
        ->assertForbidden();
});
