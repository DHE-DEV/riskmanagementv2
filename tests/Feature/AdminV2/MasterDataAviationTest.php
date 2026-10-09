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
use App\Models\MasterDataChange;
use App\Models\User;
use App\Services\AiCheckService;
use App\Services\AiFieldReviewService;
use App\Support\AdminV2\AirportExtras;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

    $this->get('/adminv2/master-data/airports')->assertOk()->assertSee('Flughafen München')->assertSee('MUC')->assertSee('EMUC')->assertDontSee('An dieser Seite wird aktuell gearbeitet')
        // Kennzahlen wie im bisherigen Admin
        ->assertSee('Diesen Monat angelegt')->assertSee('Datenqualität')->assertSee('Airlines verknüpft')->assertSee('Flughäfen angelegt pro Monat')->assertSee('Datenvollständigkeit');
    $this->get('/adminv2/master-data/airport-codes')->assertOk()->assertSee('Munich Airport')->assertSee('EDDM');
    $this->get('/adminv2/master-data/airlines')->assertOk()->assertSee('Lufthansa')->assertSee('Business Class');

    foreach (['airports', 'airport-codes', 'airlines'] as $key) {
        $this->get('/adminv2/master-data/'.$key.'/create')->assertOk();
    }

    $this->get('/adminv2/master-data/airports/'.$muc->id)->assertOk()->assertSee('Lounges')->assertSee('Mobilitätsangebote')->assertSee('Hotels in der Nähe')->assertSee('Airlines (0)');
    $this->get('/adminv2/master-data/airport-codes/'.$code->id)->assertOk()->assertSee('Codes und Einstufung');
    $this->get('/adminv2/master-data/airlines/'.$lh->id)->assertOk()->assertSee('Haustiermitnahme')->assertSee('Flughäfen (0)');

    // Die Laenderinformationen sind kein Stammdaten-Bereich mehr; es gibt keine Platzhalter-Seiten.
    $this->get('/adminv2/master-data/country-information')->assertNotFound();
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
        ->and($airport->created_by)->toBe(auth()->id())
        ->and($airport->updated_by)->toBe(auth()->id())
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

    // Aenderungsprotokoll: 1 angelegt, danach 2 Aenderungen (Feld + Airline-Verknuepfung);
    // ein unveraendertes Speichern zaehlt nicht.
    $lh = airline('Lufthansa', 'LH');
    Livewire::test(AirportEditor::class, ['airport' => $airport->id])
        ->call('save')
        ->set('website', 'https://www.munich-airport.com')
        ->call('save')
        ->set('linkId', (string) $lh->id)
        ->call('addLink');

    expect(MasterDataChange::where('model_id', $airport->id)->pluck('action')->all())->toBe(['created', 'updated', 'updated'])
        ->and(MasterDataChange::where('model_id', $airport->id)->where('action', 'updated')->pluck('changes')->all())->toEqual([['website'], ['airlines']]);

    expect(MasterDataChange::where('model_id', $airport->id)->first()->user_id)->toBe(auth()->id());
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
        ->assertSee('Flughafen München')
        // Liste mit Suchfeld; je Eintrag ein Menue statt einzelner Symbole.
        ->assertSee('Flughafen suchen …')
        ->assertSeeHtml('data-search="flughafen münchen muc"')
        ->assertSee('Aktionen für Flughafen München')
        ->assertSee('Flughafen öffnen')
        ->assertSee('Verknüpfung bearbeiten')
        ->assertSee('Verknüpfung entfernen')
        // Link-Felder (Website, Buchungslink, …) haben vorn das Symbol, das die Seite in einem neuen Tab oeffnet.
        ->assertSeeHtml('aria-label="Seite in neuem Tab öffnen"')
        ->assertSeeHtml('wire:model="website"')
        ->assertSeeHtml('wire:model="contact.help_url"');

    expect($lh->airports()->first()->pivot->direction)->toBe('both');
});

// ── KI ───────────────────────────────────────────────────────────────────

it('gliedert Ergebnisse der KI statt alles hintereinander zu schreiben', function () {
    $checks = app(AiCheckService::class);

    // Antworten in Markdown werden zu Ueberschriften, Listen und Tabellen; Zeilenumbrueche bleiben.
    $html = $checks->toHtml("## Freigepäck\n\n- **Economy:** 23 kg\n- **Business:** 2 × 32 kg\n\n| Klasse | Handgepäck |\n|---|---|\n| Economy | 8 kg |\n\nZeile eins\nZeile zwei <script>alert(1)</script>");

    expect($html)->toContain('<h2>Freigepäck</h2>')
        ->toContain('<strong>Economy:</strong> 23 kg')
        ->toContain('<table>')
        ->toContain('Zeile eins<br')
        ->not->toContain('<script>');

    // Listen, deren Eintraege selbst Kommas enthalten, stehen zeilenweise.
    expect($checks->format(['Economy: Freigepäck 23 kg, Handgepäck 8 kg', 'Business: Freigepäck 32 kg']))->toBe("Economy: Freigepäck 23 kg, Handgepäck 8 kg\nBusiness: Freigepäck 32 kg")
        ->and($checks->format(['Europa', 'EU']))->toBe('Europa, EU');

    // Eine strukturierte Antwort wird zu lesbaren Zeilen statt zu rohem JSON.
    $parsed = app(AiFieldReviewService::class)->parse(json_encode(['fields' => ['baggage' => ['status' => 'change', 'value' => [
        'economy' => ['checked_baggage' => '23 kg', 'hand_baggage' => '8 kg'],
        'hinweise' => ['Sperrgepäck anmelden'],
    ]]]], JSON_UNESCAPED_UNICODE), ['baggage']);

    expect($parsed['fields']['baggage']['value'])->toBe("Economy › Checked baggage: 23 kg\nEconomy › Hand baggage: 8 kg\nHinweise: Sperrgepäck anmelden");

    config(['services.openai.key' => 'test-key']);
    Http::fake(['api.openai.com/*' => Http::response([
        'model' => 'gpt-4o-mini',
        'choices' => [['message' => ['content' => json_encode(['summary' => 'Gepäckregeln fehlen.', 'fields' => [
            'baggage_checked_economy' => ['status' => 'change', 'value' => '1 × 23 kg', 'note' => 'Stand laut Website.'],
            'baggage_checked_business' => ['status' => 'change', 'value' => '2 × 32 kg'],
            'baggage_checked_first' => ['status' => 'ok', 'note' => 'Wird nicht angeboten.'],
            'baggage_hand_economy' => ['status' => 'change', 'value' => '8 kg'],
            'baggage_hand_economy_length' => ['status' => 'change', 'value' => '55 cm'],
            'baggage_hand_premium_economy_width' => ['status' => 'change', 'value' => '40'],
            'baggage_notes' => ['status' => 'change', 'value' => "Sperrgepäck: vorher anmelden\nSportgepäck: gegen Gebühr"],
            'baggage_info_url' => ['status' => 'change', 'value' => 'https://www.eurowings.com/gepaeck'],
        ]], JSON_UNESCAPED_UNICODE)]]],
        'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 40, 'total_tokens' => 140],
    ])]);

    $ew = airline('Eurowings', 'EW');

    $editor = Livewire::test(AirlineEditor::class, ['airline' => $ew->id])
        ->call('openAiCheck', 'baggage')
        ->set('aiCheckId', 'review')
        ->call('reviewAiFields')
        ->assertSet('aiError', null)
        // Im Fenster nach Freigepaeck und Handgepaeck gegliedert, je Klasse ein Feld.
        ->assertSee('Economy – Länge (cm)')
        ->assertSee('1 × 23 kg')
        // Mehrere Angaben in einem Feld: je Angabe eine Zeile, die Bezeichnung hervorgehoben.
        ->assertSeeHtml('<span class="font-semibold">Sperrgepäck:</span>')
        ->assertSeeHtml('<span class="font-semibold">Sportgepäck:</span>');

    // Eine Anfrage mit allen Feldern und dem Hinweis, wie sie zusammenhaengen.
    expect(Http::recorded(fn ($request) => str_contains($request->body(), 'baggage_hand_first_height') && str_contains($request->body(), 'Die Felder h'))->count())->toBe(1);

    // Jeder Vorschlag laesst sich in sein Feld uebernehmen.
    $editor->call('applyAllAiSuggestions')
        ->assertSet('checkedBaggage.economy', '1 × 23 kg')
        ->assertSet('checkedBaggage.business', '2 × 32 kg')
        ->assertSet('checkedBaggage.first', '')
        ->assertSet('handBaggage.economy', '8 kg')
        ->assertSet('handDimensions.economy.length', '55')
        ->assertSet('handDimensions.premium_economy.width', '40')
        ->assertSet('handBaggageNotes', "Sperrgepäck: vorher anmelden\nSportgepäck: gegen Gebühr")
        ->assertSet('handBaggageInfoUrl', 'https://www.eurowings.com/gepaeck')
        ->assertDispatched('adminv2-toast', message: '7 Vorschläge übernommen – noch nicht gespeichert.')
        ->call('save')
        ->assertHasNoErrors();

    expect($ew->fresh()->baggage_rules['checked_baggage']['economy'])->toBe('1 × 23 kg')
        ->and((float) $ew->fresh()->baggage_rules['hand_baggage_dimensions']['economy']['length'])->toBe(55.0);
});

it('laesst die KI jedes Feld der Haustiermitnahme pruefen und uebernimmt die Vorschlaege', function () {
    $change = fn (string $value) => ['status' => 'change', 'value' => $value, 'note' => 'Laut Website.'];

    config(['services.openai.key' => 'test-key']);
    Http::fake(['api.openai.com/*' => Http::response([
        'model' => 'gpt-4o-mini',
        'choices' => [['message' => ['content' => json_encode(['summary' => 'Haustiere dürfen mitreisen.', 'fields' => [
            'pets_allowed' => $change('Ja'),
            'pets_cabin_allowed' => $change('Ja'),
            'pets_cabin_max_weight' => $change('8 kg'),
            'pets_cabin_weight_includes_bag' => $change('Ja'),
            'pets_cabin_carrier_length' => $change('55 cm'),
            'pets_cabin_carrier_width' => $change('40'),
            'pets_cabin_carrier_height' => $change('23'),
            'pets_cabin_advance_notice_required' => $change('Ja'),
            'pets_cabin_notes' => $change('Nur Hunde und Katzen.'),
            'pets_hold_allowed' => ['status' => 'ok'],
            'pets_hold_max_weight' => ['status' => 'unknown'],
            'pets_hold_advance_notice_required' => ['status' => 'ok'],
            'pets_hold_notes' => ['status' => 'ok'],
            'pets_restrictions' => $change('Rasseeinschränkungen, Assistenztiere erlaubt'),
            'pets_info_url' => $change('https://www.eurowings.com/tiere'),
            'pets_notes' => $change('Anmeldung spätestens 48 Stunden vor Abflug.'),
        ]], JSON_UNESCAPED_UNICODE)]]],
        'usage' => ['prompt_tokens' => 300, 'completion_tokens' => 120, 'total_tokens' => 420],
    ])]);

    $ew = airline('Eurowings', 'EW');

    $editor = Livewire::test(AirlineEditor::class, ['airline' => $ew->id])
        ->assertSet('petsAllowed', false)
        ->call('openAiCheck', 'pets');

    // Alle Felder gehen an die KI – auch solange die Mitnahme nicht erlaubt ist.
    expect(array_keys($editor->get('aiData')))->toContain('pets_allowed', 'pets_cabin_max_weight', 'pets_cabin_carrier_length', 'pets_hold_allowed', 'pets_restrictions', 'pets_info_url', 'pets_notes');

    $editor->set('aiCheckId', 'review')
        ->call('reviewAiFields')
        ->assertSet('aiError', null)
        ->assertSee('Haustiere dürfen mitreisen.')
        // Im Fenster nach Bereich gegliedert.
        ->assertSee('In der Kabine')
        ->assertSee('Im Frachtraum')
        ->assertSee('Transportbox-Länge (cm)')
        ->assertSee('Alle 12 Vorschläge übernehmen');

    // Die Felder haengen zusammen: eine Anfrage mit allen Feldern und dem Hinweis dazu.
    $requests = Http::recorded(fn ($request) => str_contains($request->body(), 'pets_cabin_max_weight'));

    expect($requests->count())->toBe(1)
        ->and($requests->first()[0]->body())->toContain('pets_hold_notes', 'Die Felder h', 'Assistenztiere erlaubt')
        // Die Sammelangabe waere neben den Einzelfeldern doppelt; Gepaeckfelder gehoeren nicht dazu.
        ->not->toContain('- pets (')
        ->not->toContain('baggage_checked_economy');

    $editor->call('applyAllAiSuggestions')
        ->assertSet('petsAllowed', true)
        ->assertSet('petCabin.allowed', true)
        ->assertSet('petCabin.max_weight', '8 kg')
        ->assertSet('petCabin.weight_includes_bag', true)
        ->assertSet('petCabin.carrier_length', '55')
        ->assertSet('petCabin.carrier_width', '40')
        ->assertSet('petCabin.carrier_height', '23')
        ->assertSet('petCabin.advance_notice_required', true)
        ->assertSet('petCabin.notes', 'Nur Hunde und Katzen.')
        ->assertSet('petHold.allowed', false)
        ->assertSet('petRestrictions', ['breed_restrictions', 'service_animals_allowed'])
        ->assertSet('petInfoUrl', 'https://www.eurowings.com/tiere')
        ->assertSet('petNotes', 'Anmeldung spätestens 48 Stunden vor Abflug.')
        // Mit erlaubter Mitnahme stehen die Hinweise auch unter den Feldern.
        ->assertSee('KI: übernommen')
        ->call('save')
        ->assertHasNoErrors();

    $policy = $ew->fresh()->pet_policy;

    expect($policy['allowed'])->toBeTrue()
        ->and($policy['in_cabin']['max_weight'])->toBe('8 kg')
        ->and((float) $policy['in_cabin']['carrier_length'])->toBe(55.0)
        ->and($policy['restrictions'])->toBe(['breed_restrictions', 'service_animals_allowed'])
        ->and($policy['info_url'])->toBe('https://www.eurowings.com/tiere');

    // Eine Einschraenkung, die es nicht gibt, laesst sich nicht zuordnen.
    $editor->set('aiReview.fields.pets_restrictions', ['status' => 'change', 'value' => 'Nur montags', 'note' => null])
        ->call('applyAiSuggestion', 'pets_restrictions')
        ->assertSet('petRestrictions', ['breed_restrictions', 'service_animals_allowed'])
        ->assertDispatched('adminv2-toast', message: 'Dieser Vorschlag lässt sich nicht automatisch übernehmen – bitte von Hand eintragen.', variant: 'danger');
});

it('prueft Lounges und Hotels je Eintrag und Feld und zeigt die Hinweise unter den Feldern', function () {
    $ok = ['status' => 'ok'];

    config(['services.openai.key' => 'test-key']);
    Http::fake(['api.openai.com/*' => Http::response([
        'model' => 'gpt-4o-mini',
        'choices' => [['message' => ['content' => json_encode(['summary' => 'Eine Lounge fehlt.', 'fields' => [
            'lounge_0_name' => $ok,
            'lounge_0_location' => ['status' => 'change', 'value' => 'Terminal 2, Ebene 3', 'note' => 'Die Lounge ist umgezogen.'],
            'lounge_0_access' => $ok,
            'lounge_0_price_per_person' => ['status' => 'change', 'value' => '39,50 €'],
            'lounge_0_url' => $ok,
            'lounge_0_children_welcome' => ['status' => 'change', 'value' => 'Ja'],
            'lounge_1_name' => ['status' => 'unknown', 'note' => 'Die Lounge gibt es laut Website nicht mehr.'],
            'lounges_new' => ['status' => 'change', 'value' => "Airport Lounge World; Terminal 1; Priority Pass; 35; https://lounge.example\n- Business Lounge; Terminal 2\nSenator Lounge", 'note' => 'Zwei Lounges fehlen.'],
        ]], JSON_UNESCAPED_UNICODE)]]],
        'usage' => ['prompt_tokens' => 200, 'completion_tokens' => 80, 'total_tokens' => 280],
    ])]);

    $germany = aviationCountry('Deutschland', 'DE');
    $cgn = airport('Cologne Bonn Airport', 'CGN', aviationCity('Köln', $germany), ['lounges' => [
        ['name' => 'Business Lounge', 'location' => 'Terminal 1'],
        ['name' => 'Alte Lounge'],
    ]]);

    $editor = Livewire::test(AirportEditor::class, ['airport' => $cgn->id])->call('openAiCheck', 'lounges');

    // Jedes Feld jeder Lounge geht einzeln an die KI – dazu die Frage nach fehlenden Lounges.
    expect(array_keys($editor->get('aiData')))->toContain('lounge_0_name', 'lounge_0_location', 'lounge_1_children_welcome', 'lounges_new')
        ->and($editor->get('aiData')['lounge_0_location'])->toBe(['label' => 'Lounge 1 – Business Lounge › Standort', 'value' => 'Terminal 1']);

    $editor->set('aiCheckId', 'review')
        ->call('reviewAiFields')
        ->assertSet('aiError', null)
        // Im Fenster je Lounge gegliedert …
        ->assertSee('Lounge 1 – Business Lounge')
        ->assertSee('Fehlende Lounges')
        // … und unter den Feldern im Formular, auch nach dem Schliessen des Fensters.
        ->assertSee('Die Lounge ist umgezogen.')
        ->assertSee('Die Lounge gibt es laut Website nicht mehr.')
        ->assertSee('Fehlende Lounges laut KI')
        ->assertSeeHtml("applyAiSuggestion('lounge_0_location')")
        ->assertSeeHtml("applyAiSuggestion('lounges_new')");

    // Eine Anfrage: die Felder der Lounges haengen zusammen. Die Sammelangabe geht nicht noch einmal mit.
    $requests = Http::recorded(fn ($request) => str_contains($request->body(), 'lounge_0_location'));

    expect($requests->count())->toBe(1)
        ->and($requests->first()[0]->body())->toContain('lounges_new', 'Die Felder geh')
        ->not->toContain('- lounges (');

    // Einzelne Vorschlaege uebernehmen.
    $editor->call('applyAiSuggestion', 'lounge_0_location')
        ->assertSet('lounges.0.location', 'Terminal 2, Ebene 3')
        ->call('applyAiSuggestion', 'lounge_0_price_per_person')
        ->assertSet('lounges.0.price_per_person', '39.50')
        ->call('applyAiSuggestion', 'lounge_0_children_welcome')
        ->assertSet('lounges.0.children_welcome', true)
        // Fehlende Lounges als neue Eintraege – schon vorhandene werden nicht doppelt angelegt.
        ->call('applyAiSuggestion', 'lounges_new');

    expect($editor->get('lounges'))->toHaveCount(4)
        ->and($editor->get('lounges')[2])->toMatchArray(['name' => 'Airport Lounge World', 'location' => 'Terminal 1', 'access' => 'Priority Pass', 'price_per_person' => '35', 'url' => 'https://lounge.example', 'children_welcome' => false])
        ->and($editor->get('lounges')[3]['name'])->toBe('Senator Lounge')
        ->and($editor->get('aiReview.fields.lounges_new.applied'))->toBeTrue();

    $editor->call('save')->assertHasNoErrors();

    expect(collect($cgn->fresh()->lounges)->pluck('name')->all())->toBe(['Business Lounge', 'Alte Lounge', 'Airport Lounge World', 'Senator Lounge'])
        ->and($cgn->fresh()->lounges[0]['location'])->toBe('Terminal 2, Ebene 3');

    // Mit dem Entfernen einer Lounge verschieben sich die Eintraege – die Hinweise werden verworfen.
    $editor->call('removeLounge', 1)->assertSet('aiReview', null)->assertDontSee('Die Lounge ist umgezogen.');

    // Hotels: dieselbe Pruefung je Eintrag.
    $hotels = Livewire::test(AirportEditor::class, ['airport' => $cgn->id])
        ->call('addHotel')
        ->set('hotels.0.name', 'Airport Hotel')
        ->call('openAiCheck', 'hotels');

    expect(array_keys($hotels->get('aiData')))->toContain('hotel_0_name', 'hotel_0_distance_km', 'hotel_0_shuttle', 'hotels_new');

    $hotels->set('aiReview', ['section' => 'hotels', 'summary' => null, 'usage' => null, 'fields' => [
        'hotel_0_distance_km' => ['status' => 'change', 'value' => '0,4 km', 'note' => null],
        'hotels_new' => ['status' => 'change', 'value' => 'Stadthotel; 2,5; https://hotel.example; Shuttle auf Anfrage', 'note' => null],
    ]])
        ->call('applyAllAiSuggestions')
        ->assertSet('hotels.0.distance_km', '0.4')
        ->assertSet('hotels.1.name', 'Stadthotel')
        ->assertSet('hotels.1.distance_km', '2.5')
        ->assertSet('hotels.1.notes', 'Shuttle auf Anfrage');
});

it('prueft die Mobilitaetsangebote je Angebot und Feld', function () {
    $ok = ['status' => 'ok'];

    config(['services.openai.key' => 'test-key']);
    Http::fake(['api.openai.com/*' => Http::response([
        'model' => 'gpt-4o-mini',
        'choices' => [['message' => ['content' => json_encode(['summary' => 'Taxi und Parken fehlen.', 'fields' => [
            'mobility_car_rental_available' => $ok,
            'mobility_car_rental_0_url' => ['status' => 'change', 'value' => 'https://www.sixt.de/cgn', 'note' => 'Die Adresse fehlt.'],
            'mobility_car_rental_new' => ['status' => 'change', 'value' => "Europcar; https://www.europcar.de\nSixt; https://doppelt.example"],
            'mobility_taxi_available' => ['status' => 'change', 'value' => 'Ja', 'note' => 'Vor Terminal 1 gibt es einen Taxistand.'],
            'mobility_taxi_info' => ['status' => 'change', 'value' => 'Taxistand vor Terminal 1'],
            'mobility_taxi_approx_cost' => ['status' => 'change', 'value' => '30–40 € in die Innenstadt'],
            'mobility_parking_available' => ['status' => 'change', 'value' => 'Ja'],
            'mobility_parking_new' => ['status' => 'change', 'value' => 'P1; 2 Minuten; ab 5 € je Stunde; https://parken.example'],
            'mobility_public_transport_available' => ['status' => 'unknown', 'note' => 'Nicht geprüft.'],
        ]], JSON_UNESCAPED_UNICODE)]]],
        'usage' => ['prompt_tokens' => 200, 'completion_tokens' => 80, 'total_tokens' => 280],
    ])]);

    $germany = aviationCountry('Deutschland', 'DE');
    $cgn = airport('Cologne Bonn Airport', 'CGN', aviationCity('Köln', $germany), ['mobility_options' => [
        'car_rental' => ['available' => true, 'providers' => [['name' => 'Sixt', 'url' => null]]],
    ]]);

    $editor = Livewire::test(AirportEditor::class, ['airport' => $cgn->id])->call('openAiCheck', 'mobility');
    $data = $editor->get('aiData');

    // Je Angebot: verfuegbar, feste Felder, jede Zeile der Liste und was fehlt.
    expect(array_keys($data))->toContain('mobility_car_rental_available', 'mobility_car_rental_0_name', 'mobility_car_rental_0_url', 'mobility_car_rental_new', 'mobility_airport_shuttle_info', 'mobility_taxi_approx_cost', 'mobility_parking_new')
        ->and($data['mobility_car_rental_0_url']['label'])->toBe('Mietwagen › Zeile 1 (Sixt): Website/Buchungs-URL')
        ->and($data['mobility_taxi_available'])->toBe(['label' => 'Taxi › Verfügbar', 'value' => 'Nein']);

    $editor->set('aiCheckId', 'review')
        ->call('reviewAiFields')
        ->assertSet('aiError', null)
        // Unter den Feldern im Formular, auch nach dem Schliessen des Fensters.
        ->assertSee('Vor Terminal 1 gibt es einen Taxistand.')
        ->assertSee('Die Adresse fehlt.')
        ->assertSeeHtml("applyAiSuggestion('mobility_car_rental_0_url')")
        ->assertSeeHtml("applyAiSuggestion('mobility_taxi_available')");

    // Eine Anfrage mit dem Hinweis, wie die Felder zusammenhaengen und wie fehlende Eintraege zu nennen sind.
    $requests = Http::recorded(fn ($request) => str_contains($request->body(), 'mobility_taxi_available'));

    expect($requests->count())->toBe(1)
        ->and($requests->first()[0]->body())->toContain('mobility_parking_new', 'Name; Entfernung; Preisinformation; Website')
        ->not->toContain('- mobility (');

    $editor->call('applyAllAiSuggestions')
        ->assertSet('mobility.car_rental.providers.0.url', 'https://www.sixt.de/cgn')
        // Fehlende Anbieter kommen dazu – der schon eingetragene nicht noch einmal.
        ->assertSet('mobility.car_rental.providers.1', ['name' => 'Europcar', 'url' => 'https://www.europcar.de'])
        ->assertCount('mobility.car_rental.providers', 2)
        ->assertSet('mobility.taxi.available', true)
        ->assertSet('mobility.taxi.info', 'Taxistand vor Terminal 1')
        ->assertSet('mobility.taxi.approx_cost', '30–40 € in die Innenstadt')
        ->assertSet('mobility.parking.available', true)
        ->assertSet('mobility.parking.options.0', ['name' => 'P1', 'distance' => '2 Minuten', 'price_info' => 'ab 5 € je Stunde', 'url' => 'https://parken.example'])
        // Mit "verfuegbar" stehen die Hinweise auch unter den Feldern des Angebots.
        ->assertSeeHtml("mobility.taxi.info")
        ->call('save')
        ->assertHasNoErrors();

    $stored = $cgn->fresh()->mobility_options;

    expect($stored['taxi'])->toMatchArray(['available' => true, 'info' => 'Taxistand vor Terminal 1'])
        ->and(collect($stored['car_rental']['providers'])->pluck('name')->all())->toBe(['Sixt', 'Europcar'])
        ->and($stored['parking']['options'][0]['price_info'])->toBe('ab 5 € je Stunde');

    // Mit dem Entfernen einer Zeile verschieben sich die folgenden – die Hinweise werden verworfen.
    $editor->call('removeMobilityRow', 'car_rental', 0)->assertSet('aiReview', null);

    // Ein unbekanntes Feld laesst sich nicht uebernehmen.
    $editor->set('aiReview', ['section' => 'mobility', 'summary' => null, 'usage' => null, 'fields' => [
        'mobility_taxi_farbe' => ['status' => 'change', 'value' => 'gelb', 'note' => null],
    ]])
        ->call('applyAiSuggestion', 'mobility_taxi_farbe')
        ->assertDispatched('adminv2-toast', message: 'Dieser Vorschlag lässt sich nicht automatisch übernehmen – bitte von Hand eintragen.', variant: 'danger');
});

it('laesst das Ergebnis der Feldpruefung zu Listen im Abschnitt stehen', function () {
    $germany = aviationCountry('Deutschland', 'DE');
    $cgn = airport('Cologne Bonn Airport', 'CGN', aviationCity('Köln', $germany));

    // Sammelangaben lassen sich nicht in ein Feld uebernehmen – der Hinweis bleibt trotzdem im Abschnitt sichtbar.
    Livewire::test(AirportEditor::class, ['airport' => $cgn->id])
        ->set('aiReview', ['section' => 'airlines', 'summary' => null, 'usage' => null, 'fields' => [
            'airlines' => ['status' => 'change', 'value' => "Eurowings (EW)\nCondor (DE)", 'note' => 'Eurowings fehlt in der Liste.'],
        ]])
        ->assertSee('Eurowings fehlt in der Liste.')
        ->assertSee('Condor (DE)')
        ->assertDontSeeHtml("applyAiSuggestion('airlines')");

    $lh = airline('Lufthansa', 'LH');

    Livewire::test(AirlineEditor::class, ['airline' => $lh->id])
        ->set('aiReview', ['section' => 'cabin_classes', 'summary' => null, 'usage' => null, 'fields' => [
            'cabin_classes' => ['status' => 'change', 'value' => 'Economy, Business Class', 'note' => 'Die Kabinenklassen fehlen.'],
        ]])
        ->assertSee('Die Kabinenklassen fehlen.')
        ->assertDontSeeHtml("applyAiSuggestion('cabin_classes')");
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

it('zeigt die Prueflisten der Flughaefen und filtert danach', function () {
    $germany = aviationCountry('Deutschland', 'DE');
    $france = aviationCountry('Frankreich', 'FR');
    $munich = aviationCity('München', $germany);
    $paris = aviationCity('Paris', $france);

    // Im Verzeichnis: MUC mit korrekter Lage, CDG weit daneben; FRA fehlt dort, ist aber gross und mit Linienverkehr.
    airportCode('Munich Airport', 'EDDM', ['iata_code' => 'MUC', 'latitude_deg' => 48.3538, 'longitude_deg' => 11.7861, 'type' => 'large_airport', 'scheduled_service' => 'yes']);
    airportCode('Charles de Gaulle', 'LFPG', ['iata_code' => 'CDG', 'latitude_deg' => 49.0097, 'longitude_deg' => 2.5479, 'type' => 'large_airport', 'scheduled_service' => 'yes']);
    airportCode('Frankfurt Airport', 'EDDF', ['iata_code' => 'FRA', 'type' => 'large_airport', 'scheduled_service' => 'yes']);
    airportCode('Kleiner Platz', 'EDXX', ['iata_code' => 'XXA', 'type' => 'small_airport', 'scheduled_service' => 'no']);

    $muc = airport('Flughafen München', 'MUC', $munich, ['lat' => 48.3538, 'lng' => 11.7861]);
    $cdg = airport('Charles de Gaulle', 'CDG', $paris, ['lat' => 43.0, 'lng' => 2.5]);
    $zzz = airport('Phantasie-Flughafen', 'ZZZ', $munich);
    airline('Lufthansa', 'LH')->airports()->attach($muc->id, ['direction' => 'both']);
    // Lange nicht geaendert – direkt in der Tabelle, sonst setzt Eloquent updated_at neu.
    DB::table('airports')->where('id', $zzz->id)->update(['updated_at' => now()->subDays(400)]);

    $component = Livewire::test(AirportIndex::class);
    $stats = $component->get('stats');

    expect($stats['checks'])->toEqual(['no-airlines' => 2, 'unknown-iata' => 1, 'coordinates-off' => 1, 'stale' => 1])
        ->and($stats['unmanaged'])->toBe(1)
        ->and($stats['countriesWithAirport'])->toBe(2)
        ->and($stats['countries'])->toBe(2)
        ->and(collect($component->get('byContinent'))->pluck('count', 'label')->all())->toBe(['Europa' => 3])
        ->and(collect($component->get('topCountries'))->pluck('count', 'label')->all())->toBe(['Deutschland' => 2, 'Frankreich' => 1]);

    $component
        ->assertSee('Große Flughäfen, die fehlen')
        ->set('check', 'coordinates-off')
        ->assertSee('Charles de Gaulle')->assertDontSee('Flughafen München')->assertDontSee('Phantasie-Flughafen')
        ->set('check', 'unknown-iata')
        ->assertSee('Phantasie-Flughafen')->assertDontSee('Charles de Gaulle')
        ->set('check', 'no-airlines')
        ->assertSee('Charles de Gaulle')->assertSee('Phantasie-Flughafen')->assertDontSee('Flughafen München')
        ->set('check', 'stale')
        ->assertSee('Phantasie-Flughafen')->assertDontSee('Charles de Gaulle')
        ->set('check', '')
        ->set('continent', (string) $germany->continent_id)
        ->assertSee('Flughafen München')
        ->set('feature', 'lounges')
        ->assertDontSee('Flughafen München')
        // Die Kennzahlen verlinken in einen neuen Tab – mit genau einem Filter.
        ->assertSee(route('adminv2.master-data.airports.index', ['type' => 'international']))
        ->assertSee(route('adminv2.master-data.airports.index', ['feature' => 'hotels']))
        ->assertSee('Statistiken einblenden');

    // Die Kachel "Grosse Flughaefen, die fehlen" fuehrt in die Code-Liste – nur FRA.
    Livewire::test(AirportCodeIndex::class)
        ->set('managed', 'unmanaged')
        ->assertSee('Frankfurt Airport')->assertDontSee('Munich Airport')->assertDontSee('Kleiner Platz');

    // … und "Laender ohne Flughafen" in die Laenderliste.
    aviationCountry('Österreich', 'AT');
    Livewire::test(\App\Livewire\AdminV2\MasterData\Countries\Index::class)
        ->set('airports', 'none')
        ->assertSee('Österreich')->assertDontSee('Deutschland')
        ->set('airports', 'any')
        ->assertSee('Deutschland')->assertDontSee('Österreich');
});
