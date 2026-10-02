<?php

use App\Livewire\AdminV2\MasterData\Airlines\Editor as AirlineEditor;
use App\Livewire\AdminV2\MasterData\Airlines\Index as AirlineIndex;
use App\Livewire\AdminV2\MasterData\AirportCodes\Editor as AirportCodeEditor;
use App\Livewire\AdminV2\MasterData\AirportCodes\Index as AirportCodeIndex;
use App\Livewire\AdminV2\MasterData\Airports\Editor as AirportEditor;
use App\Livewire\AdminV2\MasterData\Airports\Index as AirportIndex;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\AirportCode;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\User;
use App\Support\AdminV2\AirportExtras;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * Stammdaten: Flughaefen, Flughafen-Codes und Airlines – Listen, Anlegen,
 * Bearbeiten, Lounges/Mobilitaet/Hotels und die Verknuepfungen Airline <-> Flughafen.
 */
function aviationCountry(string $german, string $iso): Country
{
    $continent = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);

    return Country::create([
        'name_translations' => ['de' => $german, 'en' => $german],
        'iso_code' => $iso,
        'iso3_code' => $iso.'X',
        'continent_id' => $continent->id,
    ]);
}

function aviationCity(string $german, Country $country): City
{
    return City::create(['name_translations' => ['de' => $german, 'en' => $german], 'country_id' => $country->id]);
}

function airport(string $name, string $iata, City $city, array $attributes = []): Airport
{
    return Airport::create(array_merge([
        'name' => $name,
        'iata_code' => $iata,
        'icao_code' => 'E'.$iata,
        'city_id' => $city->id,
        'country_id' => $city->country_id,
        'type' => 'international',
        'is_active' => true,
        'operates_24h' => false,
    ], $attributes));
}

function airline(string $name, ?string $iata = null, array $attributes = []): Airline
{
    return Airline::create(array_merge(['name' => $name, 'iata_code' => $iata, 'is_active' => true], $attributes));
}

function airportCode(string $name, string $ident, array $attributes = []): AirportCode
{
    return AirportCode::create(array_merge([
        'name' => $name,
        'ident' => $ident,
        'type' => 'medium_airport',
        'scheduled_service' => 'no',
        'is_active' => true,
        'operates_24h' => false,
    ], $attributes));
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));
});

// ── Seiten ───────────────────────────────────────────────────────────────

it('zeigt Flughaefen, Flughafen-Codes und Airlines als Listen statt als Hinweis', function () {
    $germany = aviationCountry('Deutschland', 'DE');
    $munich = aviationCity('München', $germany);
    $muc = airport('Flughafen München', 'MUC', $munich);
    $lh = airline('Lufthansa', 'LH', ['home_country_id' => $germany->id, 'cabin_classes' => ['economy', 'business']]);
    $code = airportCode('Munich Airport', 'EDDM', ['iata_code' => 'MUC', 'icao_code' => 'EDDM', 'municipality' => 'Munich', 'iso_country' => 'DE', 'continent' => 'EU']);

    $this->get('/adminv2/master-data/airports')->assertOk()->assertSee('Flughafen München')->assertSee('MUC · EMUC')->assertDontSee('An dieser Seite wird aktuell gearbeitet');
    $this->get('/adminv2/master-data/airport-codes')->assertOk()->assertSee('Munich Airport')->assertSee('EDDM');
    $this->get('/adminv2/master-data/airlines')->assertOk()->assertSee('Lufthansa')->assertSee('Business Class');

    foreach (['airports', 'airport-codes', 'airlines'] as $key) {
        $this->get('/adminv2/master-data/'.$key.'/create')->assertOk();
    }

    $this->get('/adminv2/master-data/airports/'.$muc->id)->assertOk()->assertSee('Lounges')->assertSee('Mobilitätsangebote')->assertSee('Hotels in der Nähe')->assertSee('Airlines (0)');
    $this->get('/adminv2/master-data/airport-codes/'.$code->id)->assertOk()->assertSee('Codes und Einstufung');
    $this->get('/adminv2/master-data/airlines/'.$lh->id)->assertOk()->assertSee('Haustiermitnahme')->assertSee('Flughäfen (0)');

    // Nur die Laenderinformationen zeigen noch den Hinweis.
    $this->get('/adminv2/master-data/country-information')->assertOk()->assertSee('An dieser Seite wird aktuell gearbeitet');
    $this->get('/adminv2/master-data/airports/999')->assertNotFound();
});

// ── Flughaefen ───────────────────────────────────────────────────────────

it('sucht und filtert Flughaefen', function () {
    $germany = aviationCountry('Deutschland', 'DE');
    $france = aviationCountry('Frankreich', 'FR');
    $munich = aviationCity('München', $germany);
    $paris = aviationCity('Paris', $france);
    airport('Flughafen München', 'MUC', $munich, ['lat' => 48.35, 'lng' => 11.78]);
    airport('Charles de Gaulle', 'CDG', $paris, ['type' => 'large_airport', 'is_active' => false]);

    Livewire::test(AirportIndex::class)
        ->set('search', 'cdg')
        ->assertSee('Charles de Gaulle')->assertDontSee('Flughafen München')
        ->set('search', 'münch')
        ->assertSee('Flughafen München')->assertDontSee('Charles de Gaulle')
        ->set('search', '')
        ->set('countryIds', [(string) $france->id])
        ->assertSee('Charles de Gaulle')->assertDontSee('Flughafen München')
        ->set('countryIds', [])
        ->set('type', 'international')
        ->assertSee('Flughafen München')->assertDontSee('Charles de Gaulle')
        ->set('type', '')
        ->set('active', 'inactive')
        ->assertSee('Charles de Gaulle')->assertDontSee('Flughafen München')
        ->set('active', '')
        ->set('coordinates', 'missing')
        ->assertSee('Charles de Gaulle')->assertDontSee('Flughafen München');
});

it('legt einen Flughafen mit Lounges, Mobilitaet und Hotels an', function () {
    $germany = aviationCountry('Deutschland', 'DE');
    $france = aviationCountry('Frankreich', 'FR');
    $munich = aviationCity('München', $germany);
    aviationCity('Paris', $france);

    $component = Livewire::withQueryParams(['city' => $munich->id])
        ->test(AirportEditor::class)
        ->assertSet('countryId', (string) $germany->id)
        ->assertSet('cityId', (string) $munich->id)
        ->call('save')
        ->assertHasErrors(['name', 'iataCode', 'icaoCode'])
        ->set('name', 'Flughafen München')
        ->set('iataCode', 'muc')
        ->set('icaoCode', 'eddm')
        ->set('website', 'https://www.munich-airport.de')
        ->set('operates24h', true)
        ->set('altitude', '453')
        ->set('coordinatesImport', '48.3538, 11.7861')
        ->call('addLounge')
        ->set('lounges.0.name', 'Airport Lounge World')
        ->set('lounges.0.location', 'Terminal 1')
        ->set('lounges.0.price_per_person', '45,50')
        ->set('lounges.0.children_welcome', true)
        ->call('addLounge')
        ->set('mobility.car_rental.available', true)
        ->call('addMobilityRow', 'car_rental')
        ->set('mobility.car_rental.providers.0.name', 'Sixt')
        ->set('mobility.car_rental.providers.0.url', 'https://www.sixt.de')
        ->set('mobility.taxi.available', true)
        ->set('mobility.taxi.approx_cost', '70 €')
        ->call('addHotel')
        ->set('hotels.0.name', 'Hilton Munich Airport')
        ->set('hotels.0.distance_km', '0,2')
        ->set('hotels.0.shuttle', true)
        ->call('save')
        ->assertHasNoErrors();

    $airport = Airport::where('iata_code', 'MUC')->first();
    $component->assertRedirect(route('adminv2.master-data.airports.edit', $airport));

    expect($airport->icao_code)->toBe('EDDM')
        ->and($airport->city_id)->toBe($munich->id)
        ->and($airport->operates_24h)->toBeTrue()
        ->and($airport->altitude)->toBe(453)
        ->and(round((float) $airport->lat, 4))->toBe(48.3538)
        ->and($airport->source)->toBe('manual')
        // Die leere zweite Lounge faellt weg.
        ->and($airport->lounges)->toEqual([['name' => 'Airport Lounge World', 'location' => 'Terminal 1', 'access' => null, 'children_welcome' => true, 'price_per_person' => 45.5, 'url' => null]])
        ->and($airport->mobility_options['car_rental'])->toEqual(['available' => true, 'providers' => [['name' => 'Sixt', 'url' => 'https://www.sixt.de']]])
        ->and($airport->mobility_options['taxi'])->toEqual(['available' => true, 'info' => null, 'approx_cost' => '70 €'])
        ->and($airport->mobility_options['parking'])->toEqual(['available' => false, 'options' => []])
        ->and($airport->nearby_hotels)->toEqual([['name' => 'Hilton Munich Airport', 'distance_km' => 0.2, 'shuttle' => true, 'booking_url' => null, 'notes' => null]]);

    // Beim erneuten Oeffnen stehen die Angaben im Formular.
    Livewire::test(AirportEditor::class, ['airport' => $airport->id])
        ->assertSet('lounges.0.price_per_person', '45.5')
        ->assertSet('mobility.car_rental.providers.0.name', 'Sixt')
        ->assertSet('hotels.0.shuttle', true);
});

it('prueft beim Flughafen Codes und die Stadt zum Land', function () {
    $germany = aviationCountry('Deutschland', 'DE');
    $france = aviationCountry('Frankreich', 'FR');
    $munich = aviationCity('München', $germany);
    $paris = aviationCity('Paris', $france);
    airport('Flughafen München', 'MUC', $munich);

    Livewire::test(AirportEditor::class)
        ->set('name', 'Test')
        ->set('countryId', (string) $france->id)
        ->set('cityId', (string) $munich->id)
        ->set('iataCode', 'MUC')
        ->set('icaoCode', 'EDDM')
        ->call('save')
        ->assertHasErrors(['cityId' => 'in', 'iataCode' => 'unique'])
        ->set('iataCode', 'MU')
        ->set('icaoCode', 'EMUC')
        ->call('save')
        ->assertHasErrors(['iataCode' => 'size', 'icaoCode' => 'unique']);

    // Mit dem Land wechselt die Stadt weg, wenn sie nicht mehr passt.
    Livewire::test(AirportEditor::class)
        ->set('countryId', (string) $germany->id)
        ->set('cityId', (string) $munich->id)
        ->set('countryId', (string) $france->id)
        ->assertSet('cityId', '');
});

it('verknuepft Airlines mit einem Flughafen samt Richtung und Terminal', function () {
    $germany = aviationCountry('Deutschland', 'DE');
    $munich = aviationCity('München', $germany);
    $muc = airport('Flughafen München', 'MUC', $munich);
    $lh = airline('Lufthansa', 'LH');
    $ba = airline('British Airways', 'BA');

    $component = Livewire::test(AirportEditor::class, ['airport' => $muc->id])
        ->call('addLink')
        ->assertHasErrors(['linkId'])
        ->set('linkId', (string) $lh->id)
        ->set('linkDirection', 'from')
        ->set('linkTerminal', 'T2')
        ->call('addLink')
        ->assertHasNoErrors()
        ->assertDispatched('adminv2-toast')
        ->assertSet('linkId', '')
        ->assertSee('Airlines (1)')
        ->assertSee('Terminal T2');

    expect($muc->airlines()->first()->pivot->direction)->toBe('from');
    // Die verknuepfte Airline steht nicht mehr zur Auswahl.
    expect(collect($component->get('availableLinkOptions'))->pluck('value')->all())->toBe([$ba->id]);

    $component->call('editLink', $lh->id)
        ->assertSet('linkDirection', 'from')
        ->set('linkDirection', 'both')
        ->set('linkTerminal', '')
        ->call('saveLink')
        ->assertSet('editingLinkId', null);

    expect($muc->airlines()->first()->pivot->direction)->toBe('both')
        ->and($muc->airlines()->first()->pivot->terminal)->toBeNull();

    $component->call('removeLink', $lh->id)->assertSee('Airlines (0)');

    expect($muc->airlines()->count())->toBe(0)
        ->and(Airline::find($lh->id))->not->toBeNull();
});

// ── Flughafen-Codes ──────────────────────────────────────────────────────

it('sucht Flughafen-Codes – kurze Begriffe als Code, laengere im Namen – und filtert', function () {
    airportCode('Munich Airport', 'EDDM', ['iata_code' => 'MUC', 'icao_code' => 'EDDM', 'municipality' => 'Munich', 'iso_country' => 'DE', 'continent' => 'EU', 'type' => 'large_airport', 'scheduled_service' => 'yes']);
    airportCode('Mucuri Airport', 'SNMU', ['iata_code' => 'MVS', 'icao_code' => 'SNMU', 'municipality' => 'Mucuri', 'iso_country' => 'BR', 'continent' => 'SA', 'type' => 'small_airport']);
    airportCode('Hospital Heliport', 'DE-0001', ['iso_country' => 'DE', 'continent' => 'EU', 'type' => 'heliport', 'is_active' => false]);

    Livewire::test(AirportCodeIndex::class)
        ->set('search', 'MUC')
        ->assertSee('Munich Airport')->assertDontSee('Mucuri Airport')
        ->set('search', 'mucuri')
        ->assertSee('Mucuri Airport')->assertDontSee('Munich Airport')
        ->set('search', '')
        ->set('isoCountry', 'de')
        ->assertSee('Munich Airport')->assertSee('Hospital Heliport')->assertDontSee('Mucuri Airport')
        ->set('codes', 'none')
        ->assertSee('Hospital Heliport')->assertDontSee('Munich Airport')
        ->set('codes', 'iata')
        ->assertSee('Munich Airport')->assertDontSee('Hospital Heliport')
        ->set('codes', '')
        ->set('scheduled', 'yes')
        ->assertSee('Munich Airport')->assertDontSee('Hospital Heliport')
        ->set('scheduled', '')
        ->set('type', 'heliport')
        ->assertSee('Hospital Heliport')->assertDontSee('Munich Airport')
        ->set('type', '')
        ->set('isoCountry', '')
        ->set('continent', 'SA')
        ->assertSee('Mucuri Airport')->assertDontSee('Munich Airport')
        ->set('continent', '')
        ->set('active', 'inactive')
        ->assertSee('Hospital Heliport')->assertDontSee('Munich Airport')
        ->call('resetFilters')
        ->assertSee('Munich Airport')->assertSee('Mucuri Airport')->assertSee('Hospital Heliport');
});

it('legt einen Flughafen-Code an und uebernimmt den ISO-Code vom verknuepften Land', function () {
    $germany = aviationCountry('Deutschland', 'DE');
    $munich = aviationCity('München', $germany);

    Livewire::test(AirportCodeEditor::class)
        ->call('save')
        ->assertHasErrors(['name', 'ident'])
        ->set('name', 'Munich Airport')
        ->set('countryId', (string) $germany->id)
        ->assertSet('isoCountry', 'DE')
        ->set('cityId', (string) $munich->id)
        ->set('ident', 'eddm')
        ->set('iataCode', 'muc')
        ->set('icaoCode', 'eddm')
        ->set('type', 'large_airport')
        ->set('continent', 'EU')
        ->set('scheduledService', 'yes')
        ->set('elevationFt', '1487')
        ->set('lat', '48,3538')
        ->set('lng', '11,7861')
        ->set('keywords', 'Franz Josef Strauß')
        ->call('save')
        ->assertHasNoErrors();

    $code = AirportCode::where('ident', 'EDDM')->first();

    expect($code)->not->toBeNull()
        ->and($code->iata_code)->toBe('MUC')
        ->and($code->country_id)->toBe($germany->id)
        ->and($code->city_id)->toBe($munich->id)
        ->and($code->iso_country)->toBe('DE')
        ->and($code->scheduled_service)->toBe('yes')
        ->and($code->elevation_ft)->toBe(1487)
        ->and(round((float) $code->latitude_deg, 4))->toBe(48.3538)
        ->and($code->source)->toBe('manual')
        ->and($code->mobility_options['parking']['available'])->toBeFalse();

    Livewire::test(AirportCodeEditor::class, ['airportCode' => $code->id])
        ->assertSet('lat', '48.3538')
        ->assertSet('scheduledService', 'yes')
        ->set('website', 'kein link')
        ->call('save')
        ->assertHasErrors(['website' => 'url']);
});

it('laesst einen Flughafen-Code nicht endgueltig loeschen, solange Flugsegmente darauf verweisen', function () {
    $code = airportCode('Munich Airport', 'EDDM');
    $code->delete();

    // Nur die Verweise zaehlen – die uebrigen Pflichtspalten des Segments sind hier ohne Belang.
    DB::statement('set foreign_key_checks = 0');
    DB::table('folder_flight_segments')->insert(
        collect(DB::getSchemaBuilder()->getColumns('folder_flight_segments'))
            ->reject(fn ($column) => $column['nullable'] || $column['auto_increment'] || $column['default'] !== null)
            ->mapWithKeys(fn ($column) => [$column['name'] => str_contains($column['type'], 'int') ? 1 : (str_contains($column['type'], 'date') || str_contains($column['type'], 'time') ? now() : 'x')])
            ->merge(['departure_airport_id' => $code->id, 'created_at' => now(), 'updated_at' => now()])
            ->all()
    );
    DB::statement('set foreign_key_checks = 1');

    $component = Livewire::test(AirportCodeIndex::class)
        ->set('trashed', 'only')
        ->call('confirmDelete', $code->id, true);

    expect($component->get('pendingDelete')['dependents'])->toEqual(['Flugsegmente in Reisen' => 1]);

    $component->call('deleteConfirmed');

    expect(AirportCode::withTrashed()->find($code->id))->not->toBeNull();
});

// ── Airlines ─────────────────────────────────────────────────────────────

it('filtert Airlines nach Heimatland, Kabinenklasse, Haustieren und Status', function () {
    $germany = aviationCountry('Deutschland', 'DE');
    $uk = aviationCountry('Vereinigtes Königreich', 'GB');
    airline('Lufthansa', 'LH', ['home_country_id' => $germany->id, 'cabin_classes' => ['economy', 'business', 'first'], 'pet_policy' => ['allowed' => true]]);
    airline('British Airways', 'BA', ['home_country_id' => $uk->id, 'cabin_classes' => ['economy'], 'is_active' => false]);

    Livewire::test(AirlineIndex::class)
        ->set('countryIds', [(string) $uk->id])
        ->assertSee('British Airways')->assertDontSee('Lufthansa')
        ->set('countryIds', [])
        ->set('cabinClass', 'first')
        ->assertSee('Lufthansa')->assertDontSee('British Airways')
        ->set('cabinClass', '')
        ->set('pets', 'yes')
        ->assertSee('Lufthansa')->assertDontSee('British Airways')
        ->set('pets', '')
        ->set('active', 'inactive')
        ->assertSee('British Airways')->assertDontSee('Lufthansa')
        ->set('active', '')
        ->set('search', 'ba')
        ->assertSee('British Airways')->assertDontSee('Lufthansa');
});

it('legt eine Airline mit Kontakt, Gepaeckregeln und Haustiermitnahme an', function () {
    $germany = aviationCountry('Deutschland', 'DE');

    $component = Livewire::test(AirlineEditor::class)
        ->call('save')
        ->assertHasErrors(['name'])
        ->set('name', 'Lufthansa')
        ->set('iataCode', 'lh')
        ->set('icaoCode', 'dlh')
        ->set('homeCountryId', (string) $germany->id)
        ->set('headquarters', 'Köln')
        ->set('website', 'https://www.lufthansa.com')
        ->set('contact.hotline', '+49 69 86 799 799')
        ->set('contact.email', 'service@lufthansa.com')
        ->set('cabinClasses', ['economy', 'business'])
        ->set('checkedBaggage.economy', '23 kg')
        ->set('handBaggage.economy', '8 kg')
        ->set('handDimensions.economy.length', '55')
        ->set('handDimensions.economy.width', '40')
        ->set('handDimensions.economy.height', '23')
        ->set('handBaggageInfoUrl', 'https://www.lufthansa.com/gepaeck')
        ->set('petsAllowed', true)
        ->set('petCabin.allowed', true)
        ->set('petCabin.max_weight', '8 kg')
        ->set('petCabin.carrier_length', '55')
        ->set('petCabin.advance_notice_required', true)
        ->set('petRestrictions', ['breed_restrictions', 'service_animals_allowed'])
        ->set('petNotes', 'Hunde und Katzen.')
        ->call('save')
        ->assertHasNoErrors();

    $airline = Airline::where('iata_code', 'LH')->first();
    $component->assertRedirect(route('adminv2.master-data.airlines.edit', $airline));

    expect($airline->icao_code)->toBe('DLH')
        ->and($airline->home_country_id)->toBe($germany->id)
        ->and($airline->contact_info)->toEqual(['hotline' => '+49 69 86 799 799', 'email' => 'service@lufthansa.com', 'chat_url' => null, 'help_url' => null])
        ->and($airline->cabin_classes)->toBe(['economy', 'business'])
        ->and($airline->baggage_rules['checked_baggage']['economy'])->toBe('23 kg')
        ->and($airline->baggage_rules['hand_baggage_dimensions']['economy'])->toEqual(['length' => 55.0, 'width' => 40.0, 'height' => 23.0])
        ->and($airline->baggage_rules['hand_baggage_dimensions']['first'])->toEqual(['length' => null, 'width' => null, 'height' => null])
        ->and($airline->pet_policy['allowed'])->toBeTrue()
        ->and($airline->pet_policy['in_cabin']['max_weight'])->toBe('8 kg')
        ->and($airline->pet_policy['in_cabin']['carrier_length'])->toEqual(55)
        ->and($airline->pet_policy['in_hold']['allowed'])->toBeFalse()
        ->and($airline->pet_policy['restrictions'])->toBe(['breed_restrictions', 'service_animals_allowed']);

    // Ohne Haustiermitnahme bleibt nur "allowed: false" stehen.
    Livewire::test(AirlineEditor::class, ['airline' => $airline->id])
        ->assertSet('petCabin.max_weight', '8 kg')
        ->assertSet('handDimensions.economy.length', '55')
        ->set('petsAllowed', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($airline->fresh()->pet_policy)->toEqual(['allowed' => false]);
});

it('prueft bei der Airline die Codes auf Laenge und Eindeutigkeit', function () {
    airline('Lufthansa', 'LH', ['icao_code' => 'DLH']);

    Livewire::test(AirlineEditor::class)
        ->set('name', 'Testair')
        ->set('iataCode', 'LH')
        ->set('icaoCode', 'DL')
        ->set('contact.email', 'keine-adresse')
        ->call('save')
        ->assertHasErrors(['iataCode' => 'unique', 'icaoCode' => 'size', 'contact.email' => 'email'])
        ->set('iataCode', '')
        ->set('icaoCode', 'DLH')
        ->call('save')
        ->assertHasErrors(['icaoCode' => 'unique']);
});

it('verknuepft Flughaefen mit einer Airline', function () {
    $germany = aviationCountry('Deutschland', 'DE');
    $munich = aviationCity('München', $germany);
    $muc = airport('Flughafen München', 'MUC', $munich);
    $lh = airline('Lufthansa', 'LH');

    Livewire::test(AirlineEditor::class, ['airline' => $lh->id])
        ->set('linkId', (string) $muc->id)
        ->call('addLink')
        ->assertHasNoErrors()
        ->assertSee('Flughäfen (1)')
        ->assertSee('Flughafen München');

    expect($lh->airports()->first()->pivot->direction)->toBe('both');
});

// ── Support ──────────────────────────────────────────────────────────────

it('bringt Lounges, Mobilitaet und Hotels ins Formular und zurueck', function () {
    $stored = ['taxi' => ['available' => true, 'info' => 'Taxistand vor Terminal 1', 'approx_cost' => null], 'parking' => ['available' => true, 'options' => [['name' => 'P1', 'distance' => '2 min', 'price_info' => null, 'url' => null]]]];
    $form = AirportExtras::mobilityToForm($stored);

    expect($form['taxi'])->toEqual(['available' => true, 'info' => 'Taxistand vor Terminal 1', 'approx_cost' => ''])
        ->and($form['parking']['options'])->toEqual([['name' => 'P1', 'distance' => '2 min', 'price_info' => '', 'url' => '']])
        ->and($form['car_rental'])->toEqual(['available' => false, 'providers' => []])
        ->and(AirportExtras::mobilityFromForm($form)['taxi'])->toEqual($stored['taxi'])
        ->and(AirportExtras::mobilityFromForm($form)['parking'])->toEqual($stored['parking'])
        ->and(AirportExtras::loungesFromForm([['name' => '  ']]))->toBe([])
        ->and(AirportExtras::hotelsToForm(null))->toBe([]);
});
