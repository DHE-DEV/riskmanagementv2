<?php

use App\Models\Airline;
use App\Models\Airport;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function aviationToken(): string
{
    return Customer::factory()->create(['gtm_api_enabled' => true])->createToken('Test', ['gtm:read'])->plainTextToken;
}

/** Aegypten mit Kairo (Lounges, Hotels, Mobilitaet), Hurghada und zwei Airlines. */
function aviationData(): array
{
    $africa = Continent::create(['name_translations' => ['de' => 'Afrika', 'en' => 'Africa'], 'code' => 'AF', 'sort_order' => 1]);
    $egypt = Country::create(['name_translations' => ['de' => 'Ägypten', 'en' => 'Egypt'], 'iso_code' => 'EG', 'iso3_code' => 'EGY', 'continent_id' => $africa->id]);
    $cairoCity = City::create(['country_id' => $egypt->id, 'name_translations' => ['de' => 'Kairo', 'en' => 'Cairo'], 'is_capital' => true]);

    $cairo = Airport::create([
        'name' => 'Cairo International Airport', 'iata_code' => 'CAI', 'icao_code' => 'HECA', 'city_id' => $cairoCity->id, 'country_id' => $egypt->id,
        'type' => 'international', 'is_active' => true, 'operates_24h' => true, 'lat' => 30.1219, 'lng' => 31.4056, 'timezone' => 'Africa/Cairo',
        'website' => 'https://cairo-airport.com', 'security_timeslot_url' => null,
        'lounges' => [['name' => 'Ahlan Lounge', 'location' => 'Terminal 3', 'access' => 'Alle Passagiere', 'price_per_person' => 40, 'children_welcome' => true, 'url' => 'https://example.com/lounge']],
        'nearby_hotels' => [['name' => 'Le Méridien Cairo Airport', 'distance_km' => 0.2, 'shuttle' => true, 'booking_url' => 'https://example.com/hotel', 'notes' => 'Direkt am Terminal 3']],
        'mobility_options' => [
            'taxi' => ['available' => true, 'info' => 'Vor allen Terminals', 'approx_cost' => '300 EGP'],
            'parking' => ['available' => true, 'options' => [['name' => 'P1', 'url' => 'https://example.com/p1', 'distance' => '100m', 'price_info' => null]]],
            'car_rental' => ['available' => true, 'providers' => [['name' => 'Hertz', 'url' => 'https://hertz.com']]],
            'airport_shuttle' => ['available' => false],
            'public_transport' => ['available' => true, 'types' => [['name' => 'Bus', 'url' => null]]],
        ],
    ]);
    $hurghadaCity = City::create(['country_id' => $egypt->id, 'name_translations' => ['de' => 'Hurghada', 'en' => 'Hurghada']]);
    $hurghada = Airport::create(['name' => 'Hurghada International Airport', 'iata_code' => 'HRG', 'icao_code' => 'HEGN', 'city_id' => $hurghadaCity->id, 'country_id' => $egypt->id, 'type' => 'international', 'is_active' => true]);
    Airport::create(['name' => 'Alter Flugplatz', 'iata_code' => 'XXX', 'icao_code' => 'HEXX', 'city_id' => $hurghadaCity->id, 'country_id' => $egypt->id, 'type' => 'small_airport', 'is_active' => false]);

    $egyptair = Airline::create([
        'name' => "\tEgyptAir", 'iata_code' => 'MS', 'icao_code' => 'MSR', 'home_country_id' => $egypt->id, 'headquarters' => 'Kairo',
        'website' => 'https://egyptair.com', 'booking_url' => 'https://egyptair.com/book', 'is_active' => true,
        'contact_info' => ['hotline' => '+20 2 2696 6300', 'email' => null, 'chat_url' => null, 'help_url' => 'https://egyptair.com/help'],
        'cabin_classes' => ['economy', 'business'],
        'baggage_rules' => [
            'hand_baggage' => ['economy' => '1x8kg', 'business' => '2x8kg', 'first' => null, 'premium_economy' => null],
            'checked_baggage' => ['economy' => '23kg', 'business' => '2x32kg', 'first' => null, 'premium_economy' => null],
            'hand_baggage_dimensions' => ['economy' => ['length' => 55, 'width' => 40, 'height' => 20], 'business' => ['length' => null, 'width' => null, 'height' => null]],
            'hand_baggage_notes' => '', 'hand_baggage_info_url' => 'https://egyptair.com/baggage',
        ],
        'pet_policy' => [
            'allowed' => true, 'notes' => null, 'info_url' => 'https://egyptair.com/pets',
            'in_cabin' => ['allowed' => true, 'max_weight' => '8kg', 'carrier_length' => 55, 'carrier_width' => 40, 'carrier_height' => 26, 'weight_includes_bag' => true, 'advance_notice_required' => true, 'notes' => null],
            'in_hold' => ['allowed' => true, 'max_weight' => null, 'advance_notice_required' => true, 'notes' => 'Anmeldung 48 h vorher'],
            'restrictions' => ['breed_restrictions', 'service_animals_allowed'],
        ],
    ]);
    $inactive = Airline::create(['name' => 'Alte Linie', 'iata_code' => 'ZZ', 'home_country_id' => $egypt->id, 'is_active' => false]);
    $egyptair->airports()->attach([$cairo->id => ['direction' => 'both', 'terminal' => '3'], $hurghada->id => ['direction' => 'both', 'terminal' => null]]);
    $inactive->airports()->attach($cairo->id, ['direction' => 'both']);

    return compact('egypt', 'cairo', 'hurghada', 'egyptair');
}

it('listet Flughaefen eines Landes mit Kurzdaten', function () {
    aviationData();

    $response = $this->withToken(aviationToken())->getJson('/api/v1/airports?country=eg&lang=de')->assertOk();
    $data = $response->json('data');

    expect($response->json('meta.total'))->toBe(2)
        ->and(collect($data)->pluck('iata_code')->all())->toBe(['CAI', 'HRG'])
        ->and($data[0])->toMatchArray(['name' => 'Cairo International Airport', 'icao_code' => 'HECA', 'type_label' => 'Internationaler Flughafen', 'city' => 'Kairo', 'operates_24h' => true])
        ->and($data[0]['country'])->toBe(['iso_code' => 'EG', 'name' => 'Ägypten'])
        ->and($data[0]['counts'])->toBe(['lounges' => 1, 'nearby_hotels' => 1, 'airlines' => 1])
        ->and($data[0])->not->toHaveKey('lounges');

    $this->withToken(aviationToken())->getJson('/api/v1/airports?q=hurg')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.iata_code', 'HRG');
});

it('liefert einen Flughafen mit Lounges, Hotels, Mobilitaet und Airlines', function () {
    aviationData();

    $data = $this->withToken(aviationToken())->getJson('/api/v1/airports/heca?lang=en')->assertOk()->json('data');

    expect($data['iata_code'])->toBe('CAI')
        ->and($data['city'])->toBe('Cairo')
        ->and($data['lounges'])->toEqual([['name' => 'Ahlan Lounge', 'location' => 'Terminal 3', 'access' => 'Alle Passagiere', 'price_per_person' => 40.0, 'children_welcome' => true, 'url' => 'https://example.com/lounge']])
        ->and($data['nearby_hotels'])->toBe([['name' => 'Le Méridien Cairo Airport', 'distance_km' => 0.2, 'shuttle' => true, 'booking_url' => 'https://example.com/hotel', 'notes' => 'Direkt am Terminal 3']])
        ->and($data['mobility']['taxi'])->toBe(['available' => true, 'info' => 'Vor allen Terminals', 'approx_cost' => '300 EGP'])
        ->and($data['mobility']['parking']['options'])->toBe([['name' => 'P1', 'url' => 'https://example.com/p1', 'distance' => '100m']])
        ->and($data['mobility']['airport_shuttle'])->toBe(['available' => false, 'info' => null, 'url' => null])
        ->and($data['mobility']['public_transport']['types'])->toBe([['name' => 'Bus']])
        ->and($data['airlines'])->toBe([['iata_code' => 'MS', 'icao_code' => 'MSR', 'name' => 'EgyptAir', 'terminal' => '3', 'direction' => 'both', 'cabin_classes' => [['key' => 'economy', 'label' => 'Economy'], ['key' => 'business', 'label' => 'Business Class']]]]);
});

it('listet Airlines und liefert eine Airline mit Kontakt, Gepaeck, Tieren und Flughaefen', function () {
    aviationData();
    $token = aviationToken();

    $list = $this->withToken($token)->getJson('/api/v1/airlines?country=EG&lang=de')->assertOk();
    expect($list->json('meta.total'))->toBe(1)
        ->and($list->json('data.0'))->toMatchArray(['iata_code' => 'MS', 'name' => 'EgyptAir', 'headquarters' => 'Kairo'])
        ->and($list->json('data.0.home_country'))->toBe(['iso_code' => 'EG', 'name' => 'Ägypten'])
        ->and($list->json('data.0.counts.airports'))->toBe(2);

    $data = $this->withToken($token)->getJson('/api/v1/airlines/ms?lang=de&include=lounges,hotels')->assertOk()->json('data');

    expect($data['contact'])->toBe(['hotline' => '+20 2 2696 6300', 'email' => null, 'chat_url' => null, 'help_url' => 'https://egyptair.com/help'])
        ->and($data['baggage']['classes']['economy'])->toEqual(['hand' => ['allowance' => '1x8kg', 'dimensions_cm' => ['length' => 55.0, 'width' => 40.0, 'height' => 20.0]], 'checked' => ['allowance' => '23kg']])
        ->and($data['baggage']['classes']['first'])->toBe(['hand' => ['allowance' => null, 'dimensions_cm' => null], 'checked' => ['allowance' => null]])
        ->and($data['baggage']['notes'])->toBeNull()
        ->and($data['baggage']['info_url'])->toBe('https://egyptair.com/baggage')
        ->and($data['pet_policy']['allowed'])->toBeTrue()
        ->and($data['pet_policy']['in_cabin'])->toBe(['allowed' => true, 'max_weight' => '8kg', 'carrier_dimensions_cm' => ['length' => 55, 'width' => 40, 'height' => 26], 'weight_includes_bag' => true, 'advance_notice_required' => true])
        ->and($data['pet_policy']['in_hold'])->toBe(['allowed' => true, 'advance_notice_required' => true, 'notes' => 'Anmeldung 48 h vorher'])
        ->and($data['pet_policy']['restrictions'])->toBe([['key' => 'breed_restrictions', 'label' => 'Rasseeinschränkungen'], ['key' => 'service_animals_allowed', 'label' => 'Assistenztiere erlaubt']])
        ->and(collect($data['airports'])->pluck('iata_code')->all())->toBe(['CAI', 'HRG'])
        ->and($data['airports'][0])->toMatchArray(['city' => 'Kairo', 'country' => 'EG', 'terminal' => '3'])
        ->and($data['airports'][0]['lounges'][0]['name'])->toBe('Ahlan Lounge')
        ->and($data['airports'][0]['nearby_hotels'][0]['name'])->toBe('Le Méridien Cairo Airport')
        ->and($data['airports'][1]['lounges'])->toBe([]);

    // Ohne include bleiben die Flughaefen schlank.
    $slim = $this->withToken($token)->getJson('/api/v1/airlines/MSR')->assertOk()->json('data.airports.0');
    expect($slim)->not->toHaveKey('lounges')->and($slim)->not->toHaveKey('nearby_hotels');
});

it('weist unbekannte Codes, inaktive Eintraege und fehlende Token ab', function () {
    aviationData();
    $token = aviationToken();

    $this->withToken($token)->getJson('/api/v1/airports/XXX')->assertNotFound();
    $this->withToken($token)->getJson('/api/v1/airports/Q')->assertNotFound();
    $this->withToken($token)->getJson('/api/v1/airlines/ZZ')->assertNotFound();
    app('auth')->forgetGuards();
    $this->withoutToken()->getJson('/api/v1/airlines')->assertUnauthorized();
});
