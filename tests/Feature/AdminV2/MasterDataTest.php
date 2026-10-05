<?php

use App\Livewire\AdminV2\MasterData\Cities\Editor as CityEditor;
use App\Livewire\AdminV2\MasterData\Cities\Index as CityIndex;
use App\Livewire\AdminV2\MasterData\Continents\Editor as ContinentEditor;
use App\Livewire\AdminV2\MasterData\Continents\Index as ContinentIndex;
use App\Livewire\AdminV2\MasterData\Countries\Editor as CountryEditor;
use App\Livewire\AdminV2\MasterData\Countries\Index as CountryIndex;
use App\Livewire\AdminV2\MasterData\Regions\Editor as RegionEditor;
use App\Livewire\AdminV2\MasterData\Regions\Index as RegionIndex;
use App\Models\AiCheck;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Region;
use App\Models\User;
use App\Support\AdminV2\Coordinates;
use App\Support\AdminV2\CountryRiskProfile;
use App\Support\AdminV2\MasterData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Stammdaten: Kontinente, Laender, Regionen und Staedte – Listen mit Suche,
 * Filtern und Papierkorb; Anlegen, Bearbeiten, Loeschen und Wiederherstellen.
 */
function continent(string $german, string $code, int $sortOrder = 0): Continent
{
    return Continent::create([
        'name_translations' => ['de' => $german, 'en' => $german],
        'code' => $code,
        'sort_order' => $sortOrder,
    ]);
}

function country(string $german, string $iso, Continent $continent, array $attributes = []): Country
{
    return Country::create(array_merge([
        'name_translations' => ['de' => $german, 'en' => $german],
        'iso_code' => $iso,
        'iso3_code' => $iso.'X',
        'continent_id' => $continent->id,
    ], $attributes));
}

function region(string $german, string $code, Country $country, array $attributes = []): Region
{
    return Region::create(array_merge([
        'name_translations' => ['de' => $german, 'en' => $german],
        'code' => $code,
        'country_id' => $country->id,
    ], $attributes));
}

function city(string $german, Country $country, array $attributes = []): City
{
    return City::create(array_merge([
        'name_translations' => ['de' => $german, 'en' => $german],
        'country_id' => $country->id,
    ], $attributes));
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));
});

// ── Seiten ───────────────────────────────────────────────────────────────

it('zeigt die vier umgezogenen Bereiche als Listen und die uebrigen weiter als Hinweis', function () {
    $europe = continent('Europa', 'EU', 1);
    $germany = country('Deutschland', 'DE', $europe);
    region('Bayern', 'BY', $germany);
    city('München', $germany, ['population' => 1500000]);

    $this->get('/adminv2/master-data/continents')->assertOk()->assertSee('Kontinente')->assertSee('Europa')->assertDontSee('An dieser Seite wird aktuell gearbeitet');
    $this->get('/adminv2/master-data/countries')->assertOk()->assertSee('Deutschland')->assertSee('DEX');
    $this->get('/adminv2/master-data/regions')->assertOk()->assertSee('Bayern');
    $this->get('/adminv2/master-data/cities')->assertOk()->assertSee('München')->assertSee('1.500.000');

    foreach (['continents', 'countries', 'regions', 'cities'] as $key) {
        $this->get('/adminv2/master-data/'.$key.'/create')->assertOk();
    }

    $this->get('/adminv2/master-data/continents/'.$europe->id)->assertOk()->assertSee('Europa');
    $this->get('/adminv2/master-data/countries/'.$germany->id)->assertOk()->assertSee('Risikoprofil')->assertSee('Ländergrenze');

    foreach (MasterData::placeholderKeys() as $key) {
        $this->get('/adminv2/master-data/'.$key)->assertOk()->assertSee('An dieser Seite wird aktuell gearbeitet');
    }

    $this->get('/adminv2/master-data/continents/999')->assertNotFound();
});

it('verlangt einen aktiven Administrator', function () {
    auth()->logout();

    $this->get('/adminv2/master-data/countries')->assertRedirect(route('adminv2.login'));

    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->get('/adminv2/master-data/countries')
        ->assertForbidden();
});

// ── Listen ───────────────────────────────────────────────────────────────

it('sucht Laender nach Name und ISO-Code, unabhaengig von Gross- und Kleinschreibung', function () {
    $europe = continent('Europa', 'EU');
    country('Deutschland', 'DE', $europe);
    country('Österreich', 'AT', $europe);
    country('Dänemark', 'DK', $europe);

    Livewire::test(CountryIndex::class)
        ->set('search', 'öster')
        ->assertSee('Österreich')
        ->assertDontSee('Deutschland')
        ->set('search', 'dk')
        ->assertSee('Dänemark')
        ->assertDontSee('Österreich');
});

it('filtert Laender nach Kontinent, Mitgliedschaft, Risikostufe und fehlenden Koordinaten', function () {
    $europe = continent('Europa', 'EU');
    $asia = continent('Asien', 'AS');
    country('Deutschland', 'DE', $europe, ['is_eu_member' => true, 'is_schengen_member' => true, 'lat' => 51.1, 'lng' => 10.4]);
    country('Schweiz', 'CH', $europe, ['is_schengen_member' => true, 'lat' => 46.8, 'lng' => 8.2]);
    country('Japan', 'JP', $asia, ['risk_profile' => ['security' => ['overall_risk_level' => 2], 'natural_hazards' => ['natural_hazard_level' => 4]]]);

    Livewire::test(CountryIndex::class)
        ->set('continent', (string) $asia->id)
        ->assertSee('Japan')->assertDontSee('Deutschland')
        ->set('continent', '')
        ->set('membership', 'eu')
        ->assertSee('Deutschland')->assertDontSee('Schweiz')
        ->set('membership', 'schengen')
        ->assertSee('Schweiz')->assertSee('Deutschland')->assertDontSee('Japan')
        ->set('membership', 'both')
        ->assertSee('Deutschland')->assertDontSee('Schweiz')
        ->set('membership', 'none')
        ->assertSee('Japan')->assertDontSee('Schweiz')->assertDontSee('Deutschland')
        ->set('membership', '')
        ->set('risk', '4')
        ->assertSee('Japan')->assertDontSee('Deutschland')
        ->set('risk', 'none')
        ->assertSee('Deutschland')->assertDontSee('Japan')
        ->set('risk', '')
        ->set('coordinates', 'missing')
        ->assertSee('Japan')->assertDontSee('Schweiz')
        ->call('resetFilters')
        ->assertSee('Japan')->assertSee('Schweiz')->assertSee('Deutschland');
});

it('sortiert nach dem deutschen Namen mit Umlauten an der richtigen Stelle', function () {
    $europe = continent('Europa', 'EU');
    country('Ungarn', 'HU', $europe);
    country('Österreich', 'AT', $europe);
    country('Norwegen', 'NO', $europe);
    country('Polen', 'PL', $europe);

    // Nach einem Aufruf liegt die Antwort als JSON vor (Umlaute kodiert) – deshalb ueber das HTML pruefen.
    $order = fn ($component) => collect(['Norwegen', 'Österreich', 'Polen', 'Ungarn'])
        ->sortBy(fn (string $name) => mb_strpos($component->html(), $name))
        ->values()->all();

    $component = Livewire::test(CountryIndex::class);
    expect($order($component))->toBe(['Norwegen', 'Österreich', 'Polen', 'Ungarn']);

    $component->call('sortBy', 'name')->assertSet('direction', 'desc');
    expect($order($component))->toBe(['Ungarn', 'Polen', 'Österreich', 'Norwegen']);

    $component->call('sortBy', 'unbekannt')->assertSet('sort', 'name')->assertSet('direction', 'desc');
});

it('zeigt den Papierkorb nur auf Wunsch und stellt daraus wieder her', function () {
    $europe = continent('Europa', 'EU');
    $germany = country('Deutschland', 'DE', $europe);
    $old = country('Jugoslawien', 'YU', $europe);
    $old->delete();

    Livewire::test(CountryIndex::class)
        ->assertSee('Deutschland')->assertDontSee('Jugoslawien')
        ->set('trashed', 'only')
        ->assertSee('Jugoslawien')->assertDontSee('Deutschland')
        ->set('trashed', 'with')
        ->assertSee('Jugoslawien')->assertSee('Deutschland')->assertSee('Papierkorb')
        ->call('restore', $old->id)
        ->assertDispatched('adminv2-toast');

    expect($old->fresh()->trashed())->toBeFalse();
});

it('filtert Staedte nach Land, Region, Hauptstadt und fehlenden Koordinaten', function () {
    $europe = continent('Europa', 'EU');
    $germany = country('Deutschland', 'DE', $europe);
    $france = country('Frankreich', 'FR', $europe);
    $bavaria = region('Bayern', 'BY', $germany);
    city('Berlin', $germany, ['is_capital' => true, 'lat' => 52.5, 'lng' => 13.4]);
    city('München', $germany, ['region_id' => $bavaria->id, 'is_regional_capital' => true]);
    city('Paris', $france, ['is_capital' => true, 'lat' => 48.9, 'lng' => 2.3]);

    Livewire::test(CityIndex::class)
        ->set('countryIds', [(string) $germany->id])
        ->assertSee('Berlin')->assertSee('München')->assertDontSee('Paris')
        ->assertSee('Alle Regionen')
        ->set('region', (string) $bavaria->id)
        ->assertSee('München')->assertDontSee('Berlin')
        ->set('region', 'none')
        ->assertSee('Berlin')->assertDontSee('München')
        // Mit einem weiteren Land faellt der Regionsfilter weg.
        ->set('countryIds', [(string) $germany->id, (string) $france->id])
        ->assertSet('region', '')
        ->assertSee('Paris')
        ->set('capital', 'capital')
        ->assertSee('Berlin')->assertSee('Paris')->assertDontSee('München')
        ->set('capital', 'regional')
        ->assertSee('München')->assertDontSee('Berlin')
        ->set('capital', '')
        ->set('coordinates', 'missing')
        ->assertSee('München')->assertDontSee('Berlin');
});

it('filtert Regionen nach Land und verlinkt Land und Staedte', function () {
    $europe = continent('Europa', 'EU');
    $germany = country('Deutschland', 'DE', $europe);
    $austria = country('Österreich', 'AT', $europe);
    $bavaria = region('Bayern', 'BY', $germany);
    region('Tirol', 'T', $austria);
    city('München', $germany, ['region_id' => $bavaria->id]);

    Livewire::withQueryParams(['country' => [$austria->id]])
        ->test(RegionIndex::class)
        ->assertSee('Tirol')->assertDontSee('Bayern');

    Livewire::withQueryParams([])
        ->test(RegionIndex::class)
        ->assertSee('Bayern')
        ->assertSee(route('adminv2.master-data.countries.edit', $germany->id))
        ->assertSee(route('adminv2.master-data.cities.index', ['country' => [$germany->id], 'region' => $bavaria->id]));
});

it('blaettert in Seiten zu 25 Eintraegen', function () {
    $germany = country('Deutschland', 'DE', continent('Europa', 'EU'));
    for ($i = 1; $i <= 30; $i++) {
        city(sprintf('Stadt %02d', $i), $germany);
    }

    Livewire::test(CityIndex::class)
        ->assertSee('Stadt 01')->assertSee('Stadt 25')->assertDontSee('Stadt 26')
        ->assertSee('Seite 1 von 2')
        ->call('nextPage')
        ->assertSee('Stadt 26')->assertDontSee('Stadt 01')
        // Eine neue Suche beginnt wieder auf Seite 1.
        ->set('search', 'Stadt')
        ->assertSee('Stadt 01');
});

// ── Kontinente ───────────────────────────────────────────────────────────

it('legt einen Kontinent an und uebernimmt Koordinaten aus einem Google-Maps-Link', function () {
    continent('Europa', 'EU', 1);

    Livewire::test(ContinentEditor::class)
        ->assertSet('sortOrder', '2')
        ->set('nameDe', 'Asien')
        ->set('nameEn', 'Asia')
        ->set('code', 'AS')
        ->set('keywords', 'Asien, Asia, Fernost')
        ->set('coordinatesImport', 'https://www.google.com/maps/place/Asien/@34.0479,100.6197,3z/data=!3m1!4b1')
        ->assertSet('lat', '34.0479')
        ->assertSet('lng', '100.6197')
        ->assertSet('coordinatesImport', '')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('adminv2.master-data.continents.edit', Continent::where('code', 'AS')->first()));

    $asia = Continent::where('code', 'AS')->first();

    expect($asia->getName('de'))->toBe('Asien')
        ->and($asia->sort_order)->toBe(2)
        ->and($asia->keywords)->toBe(['Asien', 'Asia', 'Fernost'])
        ->and((float) $asia->lat)->toBe(34.0479)
        ->and((float) $asia->lng)->toBe(100.6197);
});

it('prueft beim Kontinent Pflichtfelder, Codelaenge und doppelte Codes – nur bei geaendertem Code', function () {
    continent('Europa', 'EU', 1);
    $duplicate = continent('Südamerika', 'SA', 2);
    continent('Südasien', 'sa', 3);

    Livewire::test(ContinentEditor::class)
        ->set('code', 'TOOLONG')
        ->set('lat', '91')
        ->call('save')
        ->assertHasErrors(['nameDe', 'nameEn', 'code', 'lat'])
        ->set('nameDe', 'Afrika')->set('nameEn', 'Africa')->set('lat', '')->set('lng', '20')
        ->set('code', 'eu')
        ->call('save')
        ->assertHasErrors(['code' => 'unique', 'lat' => 'required_with']);

    // Der Altbestand mit doppeltem Code laesst sich weiter speichern …
    Livewire::test(ContinentEditor::class, ['continent' => $duplicate->id])
        ->set('description', 'Der Kontinent südlich von Panama.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('adminv2-toast');

    // … ein geaenderter Code muss aber frei sein.
    Livewire::test(ContinentEditor::class, ['continent' => $duplicate->id])
        ->set('code', 'EU')
        ->call('save')
        ->assertHasErrors(['code' => 'unique']);

    expect($duplicate->fresh()->description)->toBe('Der Kontinent südlich von Panama.');
});

it('legt einen Kontinent in den Papierkorb, zeigt dabei seine Laender und loescht ihn erst ohne Laender endgueltig', function () {
    $europe = continent('Europa', 'EU');
    $germany = country('Deutschland', 'DE', $europe);

    $component = Livewire::test(ContinentIndex::class)
        ->call('confirmDelete', $europe->id);

    expect($component->get('pendingDelete'))->toMatchArray(['label' => 'Europa', 'force' => false, 'dependents' => ['Länder' => 1]]);

    $component->call('deleteConfirmed')->assertDispatched('adminv2-toast');

    expect($europe->fresh()->trashed())->toBeTrue();

    // Endgueltig loeschen scheitert, solange das Land daran haengt.
    $component = Livewire::test(ContinentIndex::class)
        ->set('trashed', 'only')
        ->call('confirmDelete', $europe->id, true);

    expect($component->get('pendingDelete'))->toMatchArray(['force' => true, 'dependents' => ['Länder' => 1]]);

    $component->call('deleteConfirmed');

    expect(Continent::withTrashed()->find($europe->id))->not->toBeNull();

    $germany->forceDelete();

    Livewire::test(ContinentIndex::class)
        ->set('trashed', 'only')
        ->call('confirmDelete', $europe->id, true)
        ->call('deleteConfirmed');

    expect(Continent::withTrashed()->find($europe->id))->toBeNull();
});

it('loescht aus der Bearbeitungsseite heraus und kehrt zur Liste zurueck', function () {
    $europe = continent('Europa', 'EU');

    Livewire::test(ContinentEditor::class, ['continent' => $europe->id])
        ->call('confirmDelete', $europe->id)
        ->call('deleteConfirmed')
        ->assertRedirect(route('adminv2.master-data.continents.index'));

    expect($europe->fresh()->trashed())->toBeTrue();

    // Aus dem Papierkorb heraus zeigt die Seite den Hinweis und stellt wieder her.
    Livewire::test(ContinentEditor::class, ['continent' => $europe->id])
        ->assertSee('liegt seit dem')
        ->call('restore', $europe->id)
        ->assertDispatched('adminv2-toast')
        ->assertDontSee('liegt seit dem');

    expect($europe->fresh()->trashed())->toBeFalse();
});

// ── Laender ──────────────────────────────────────────────────────────────

it('legt ein Land mit weiteren Sprachen, Zahlen in deutscher Schreibweise und Risikoprofil an', function () {
    $europe = continent('Europa', 'EU');

    $component = Livewire::withQueryParams(['continent' => $europe->id])
        ->test(CountryEditor::class)
        ->assertSet('continentId', (string) $europe->id)
        ->set('nameDe', 'Deutschland')
        ->set('nameEn', 'Germany')
        ->call('addName')
        ->set('extraNames.0.code', 'FR')
        ->set('extraNames.0.name', 'Allemagne')
        ->set('isoCode', 'de')
        ->set('iso3Code', 'deu')
        ->set('isEuMember', true)
        ->set('isSchengenMember', true)
        ->set('currencyCode', 'eur')
        ->set('currencySymbol', '€')
        ->set('phonePrefix', '+49')
        ->set('population', '83.200.000')
        ->set('areaKm2', '357588,5')
        ->set('lat', '51,1657')
        ->set('lng', '10,4515')
        ->set('riskProfile.security.overall_risk_level', '2')
        ->set('riskProfile.health.health_risk_level', '1')
        ->set('riskProfile.health.drinking_water_safe', true)
        ->set('riskProfile.health.recommended_vaccinations', 'Hepatitis A, FSME, ')
        ->set('riskProfile.entry.passport_validity_months', '6')
        ->set('riskProfile.climate.climate_zone', 'gemäßigt')
        ->call('save')
        ->assertHasNoErrors();

    $germany = Country::where('iso_code', 'DE')->first();
    $component->assertRedirect(route('adminv2.master-data.countries.edit', $germany));

    expect($germany->name_translations)->toEqual(['de' => 'Deutschland', 'en' => 'Germany', 'fr' => 'Allemagne'])
        ->and($germany->iso3_code)->toBe('DEU')
        ->and($germany->currency_code)->toBe('EUR')
        ->and($germany->is_eu_member)->toBeTrue()
        ->and($germany->population)->toBe(83200000)
        ->and((float) $germany->area_km2)->toBe(357588.5)
        ->and((float) $germany->lat)->toBe(51.1657)
        ->and($germany->overall_risk_level)->toBe(2)
        ->and($germany->risk_profile['health'])->toEqual(['health_risk_level' => 1, 'malaria_risk' => false, 'drinking_water_safe' => true, 'required_vaccinations' => [], 'recommended_vaccinations' => ['Hepatitis A', 'FSME']])
        ->and($germany->risk_profile['entry']['passport_validity_months'])->toBe(6)
        ->and($germany->risk_profile['climate']['climate_zone'])->toBe('gemäßigt')
        ->and($germany->risk_profile)->not->toHaveKey('infrastructure');
});

it('laesst ein Land ohne jede Bewertung als nicht bewertet und raeumt Altdaten in den Uebersetzungen auf', function () {
    $europe = continent('Europa', 'EU');
    $country = country('Südgeorgien', 'GS', $europe, [
        'name_translations' => ['en' => 'South Georgia', 'de ' => 'Südgeorgien'],
        'risk_profile' => ['security' => ['overall_risk_level' => 3, 'source' => 'Import 2024']],
    ]);

    $component = Livewire::test(CountryEditor::class, ['country' => $country->id])
        ->assertSet('nameDe', 'Südgeorgien')
        ->assertSet('extraNames', [])
        ->assertSet('riskProfile.security.overall_risk_level', '3');

    // Unbekannte Angaben im Profil bleiben beim Speichern erhalten …
    $component->set('riskProfile.security.crime_level', '1')->call('save')->assertHasNoErrors();

    expect($country->fresh()->risk_profile['security'])->toEqual(['overall_risk_level' => 3, 'source' => 'Import 2024', 'crime_level' => 1])
        ->and($country->fresh()->name_translations)->toEqual(['de' => 'Südgeorgien', 'en' => 'South Georgia']);

    // … ohne jede Angabe wird das Profil zu null.
    $country->forceFill(['risk_profile' => null])->save();

    Livewire::test(CountryEditor::class, ['country' => $country->id])
        ->set('riskProfile.health.malaria_risk', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($country->fresh()->risk_profile)->toBeNull()
        ->and($country->fresh()->has_risk_profile)->toBeFalse();
});

it('prueft beim Land ISO-Codes, Kontinent und weitere Sprachen', function () {
    $europe = continent('Europa', 'EU');
    country('Deutschland', 'DE', $europe, ['iso3_code' => 'DEU']);

    Livewire::test(CountryEditor::class)
        ->set('nameDe', 'Testland')->set('nameEn', 'Testland')
        ->set('isoCode', 'D')
        ->set('iso3Code', 'DEU')
        ->call('addName')
        ->set('extraNames.0.code', 'de')
        ->set('extraNames.0.name', 'Noch einmal')
        ->call('save')
        ->assertHasErrors(['isoCode' => 'size', 'iso3Code' => 'unique', 'continentId' => 'required', 'extraNames.0.code' => 'not_in'])
        ->set('isoCode', 'DE')
        ->call('save')
        ->assertHasErrors(['isoCode' => 'unique']);
});

it('zeigt zum Land Regionen, Staedte und Flughaefen und legt daraus Neues vorbelegt an', function () {
    $europe = continent('Europa', 'EU');
    $germany = country('Deutschland', 'DE', $europe);
    $bavaria = region('Bayern', 'BY', $germany);
    city('Berlin', $germany, ['is_capital' => true]);
    city('München', $germany, ['region_id' => $bavaria->id, 'population' => 1500000]);

    Livewire::test(CountryEditor::class, ['country' => $germany->id])
        ->assertSee('Regionen (1)')
        ->assertSee('Städte (2)')
        ->assertSee('Flughäfen (0)')
        ->assertSeeInOrder(['Berlin', 'München'])
        ->assertSee(route('adminv2.master-data.regions.create', ['country' => $germany->id]), false)
        ->assertSee(route('adminv2.master-data.cities.create', ['country' => $germany->id]), false);

    Livewire::withQueryParams(['country' => $germany->id])
        ->test(RegionEditor::class)
        ->assertSet('countryId', (string) $germany->id);

    Livewire::withQueryParams(['region' => $bavaria->id])
        ->test(CityEditor::class)
        ->assertSet('countryId', (string) $germany->id)
        ->assertSet('regionId', (string) $bavaria->id);

    Livewire::withQueryParams([]);
});

// ── Regionen ─────────────────────────────────────────────────────────────

it('legt eine Region an und bietet "Speichern & weitere anlegen" mit vorbelegtem Land', function () {
    $europe = continent('Europa', 'EU');
    $germany = country('Deutschland', 'DE', $europe);

    Livewire::test(RegionEditor::class)
        ->call('save')
        ->assertHasErrors(['nameDe', 'code', 'countryId'])
        ->set('nameDe', 'Bayern')
        ->set('nameEn', 'Bavaria')
        ->set('code', 'BY')
        ->set('countryId', (string) $germany->id)
        ->set('coordinatesImport', '48.7904, 11.4979')
        ->assertSet('lat', '48.7904')
        ->call('save', true)
        ->assertHasNoErrors()
        ->assertRedirect(route('adminv2.master-data.regions.create', ['country' => $germany->id]));

    $bavaria = Region::first();

    expect($bavaria->getName('en'))->toBe('Bavaria')
        ->and($bavaria->country_id)->toBe($germany->id)
        ->and((float) $bavaria->lng)->toBe(11.4979);

    // Ohne englischen Namen faellt der Schluessel weg.
    Livewire::test(RegionEditor::class, ['region' => $bavaria->id])
        ->set('nameEn', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($bavaria->fresh()->name_translations)->toEqual(['de' => 'Bayern']);
});

// ── Staedte ──────────────────────────────────────────────────────────────

it('legt eine Stadt an, haelt die Region zum Land passend und weist auf eine vorhandene Hauptstadt hin', function () {
    $europe = continent('Europa', 'EU');
    $germany = country('Deutschland', 'DE', $europe);
    $france = country('Frankreich', 'FR', $europe);
    $bavaria = region('Bayern', 'BY', $germany);
    city('Berlin', $germany, ['is_capital' => true]);

    Livewire::test(CityEditor::class)
        ->set('nameDe', 'München')
        ->set('countryId', (string) $germany->id)
        ->set('regionId', (string) $bavaria->id)
        ->set('isCapital', true)
        ->assertSee('hat bereits eine Hauptstadt')
        ->assertSee('Berlin')
        ->set('isCapital', false)
        // Mit einem anderen Land passt die Region nicht mehr.
        ->set('countryId', (string) $france->id)
        ->assertSet('regionId', '')
        ->set('countryId', (string) $germany->id)
        ->set('regionId', (string) $bavaria->id)
        ->set('isRegionalCapital', true)
        ->set('population', '1.500.000')
        ->call('save')
        ->assertHasNoErrors();

    $munich = City::where('name_translations->de', 'München')->first();

    expect($munich->region_id)->toBe($bavaria->id)
        ->and($munich->is_regional_capital)->toBeTrue()
        ->and($munich->population)->toBe(1500000);
});

it('weist eine Region eines anderen Landes zurueck', function () {
    $europe = continent('Europa', 'EU');
    $germany = country('Deutschland', 'DE', $europe);
    $france = country('Frankreich', 'FR', $europe);
    $bavaria = region('Bayern', 'BY', $germany);
    $paris = city('Paris', $france);

    Livewire::test(CityEditor::class, ['city' => $paris->id])
        ->set('regionId', (string) $bavaria->id)
        ->call('save')
        ->assertHasErrors(['regionId']);
});

// ── KI-Pruefungen ────────────────────────────────────────────────────────

it('fuehrt eine KI-Pruefung mit den Daten des Abschnitts aus und zeigt Verbrauch und Kosten', function () {
    config(['services.openai.key' => 'test-key']);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'model' => 'gpt-4o-2024-08-06',
            'choices' => [['message' => ['content' => "Die Währung ist der **Euro**.\n\n<script>alert(1)</script>Alles plausibel."]]],
            'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 30, 'total_tokens' => 150],
        ]),
    ]);

    $europe = continent('Europa', 'EU');
    $germany = country('Deutschland', 'DE', $europe, ['currency_code' => 'EUR', 'population' => 83000000]);

    // Eine Pruefung fuer "Weitere Informationen", eine bereichsweite, eine fuer einen anderen Abschnitt, eine ausgeschaltete.
    $currency = AiCheck::create(['name' => 'Währung prüfen', 'area' => 'countries', 'section' => 'details', 'prompt' => 'Stimmt die Währung {currency_code} für {name}? Weitere Angaben: {daten}', 'model' => 'gpt-4o-mini']);
    AiCheck::create(['name' => 'Allgemeine Plausibilität', 'area' => 'countries', 'section' => null, 'prompt' => 'Prüfe die Angaben auf Plausibilität.']);
    AiCheck::create(['name' => 'Koordinaten prüfen', 'area' => 'countries', 'section' => 'coordinates', 'prompt' => 'Liegt {lat}, {lng} in {name}?']);
    AiCheck::create(['name' => 'Alt', 'area' => 'countries', 'section' => 'details', 'prompt' => 'Alter Prompt.', 'is_active' => false]);
    AiCheck::create(['name' => 'Stadt', 'area' => 'cities', 'section' => null, 'prompt' => 'Städte-Prompt.']);

    $component = Livewire::test(CountryEditor::class, ['country' => $germany->id])
        ->assertSee('KI-Prüfung')
        ->call('openAiCheck', 'details')
        ->assertSet('aiSection', 'details')
        ->assertSee('Währung prüfen')
        ->assertSee('Allgemeine Plausibilität')
        ->assertDontSee('Koordinaten prüfen')
        ->assertDontSee('Städte-Prompt')
        ->assertDontSee('Alter Prompt');

    // Die Daten des Abschnitts – und nur die – gehen mit.
    expect(array_keys($component->get('aiData')))->toBe(['currency_code', 'currency_name', 'currency_symbol', 'phone_prefix', 'timezone', 'population', 'area_km2'])
        ->and($component->get('aiData')['currency_code']['value'])->toBe('EUR')
        ->and($component->get('aiData')['currency_name']['value'])->toBe('–');

    $component
        ->call('runAiCheck')
        ->assertSet('aiError', 'Bitte zuerst eine Prüfung auswählen oder einen eigenen Prompt eingeben.')
        // Noch nicht gespeicherte Eingaben zaehlen bereits.
        ->set('phonePrefix', '+49')
        ->set('aiCheckId', (string) $currency->id)
        ->call('runAiCheck')
        ->assertSet('aiError', null)
        ->assertSee('150 Token')
        ->assertSee('Die Währung ist');

    expect($component->get('aiResult.html'))->not->toContain('<script>')
        ->and($component->get('aiResult.usage.model'))->toBe('gpt-4o-2024-08-06');

    // Platzhalter ersetzt, {daten} mit allen Angaben des Abschnitts, das Modell der Pruefung verwendet.
    Http::assertSent(function ($request) {
        $content = $request->data()['messages'][0]['content'] ?? '';

        return ($request->data()['model'] ?? null) === 'gpt-4o-mini'
            && str_contains($content, 'Stimmt die Währung EUR für Deutschland?')
            && str_contains($content, 'Telefonvorwahl: +49')
            && str_contains($content, 'Bevölkerung: 83000000')
            && ! str_contains($content, 'ISO-Code');
    });

    // Im Kopf des Eintrags stehen alle Pruefungen des Bereichs, mit allen Daten.
    $component->call('openAiCheck', 'general')
        ->assertSee('Koordinaten prüfen')
        ->assertSee('Währung prüfen');

    expect(array_keys($component->get('aiData')))->toContain('iso_code', 'currency_code', 'lat', 'risk_profile');
});

it('haengt die Abschnittsdaten an, wenn der Prompt keine Platzhalter nutzt, und faengt Fehler ab', function () {
    config(['services.openai.key' => 'test-key']);
    Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'Rate limit']], 429)]);

    $europe = continent('Europa', 'EU');
    $check = AiCheck::create(['name' => 'Übersicht', 'area' => 'continents', 'section' => 'basics', 'prompt' => 'Beschreibe den Kontinent in zwei Sätzen.']);

    $service = app(\App\Services\AiCheckService::class);
    $prompt = $service->buildPrompt($check->prompt, ['name' => 'Europa', 'code' => 'EU', 'keywords' => ['Europa', 'EU'], 'is_x' => true], ['name' => 'Name', 'code' => 'Code', 'keywords' => 'Schlagwörter', 'is_x' => 'X']);

    expect($prompt)->toBe("Beschreibe den Kontinent in zwei Sätzen.\n\nDaten des Eintrags:\nName: Europa\nCode: EU\nSchlagwörter: Europa, EU\nX: Ja");

    $component = Livewire::test(ContinentEditor::class, ['continent' => $europe->id])
        ->call('openAiCheck', 'basics')
        ->assertSet('aiCheckId', (string) $check->id)
        ->call('runAiCheck')
        ->assertSet('aiResult', null);

    expect($component->get('aiError'))->toContain('ChatGPT');

    // Ohne Pruefung: Hinweis mit Weg zur Verwaltung – und der eigene Prompt ist vorgewaehlt.
    Livewire::test(ContinentEditor::class, ['continent' => $europe->id])
        ->call('openAiCheck', 'coordinates')
        ->assertSee('ist noch keine KI-Prüfung hinterlegt')
        ->assertSee(route('adminv2.system.ai', ['tab' => 'continents']))
        ->assertSet('aiCheckId', 'custom')
        ->assertSee('Eigener Prompt');
});

it('laesst den Prompt einer hinterlegten Pruefung fuer einen Lauf anpassen, ohne sie zu aendern', function () {
    config(['services.openai.key' => 'test-key']);
    Http::fake(['api.openai.com/*' => Http::response([
        'model' => 'gpt-4o-mini',
        'choices' => [['message' => ['content' => 'Passt.']]],
        'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 2, 'total_tokens' => 12],
    ])]);

    $europe = continent('Europa', 'EU');
    $check = AiCheck::create(['name' => 'Übersicht', 'area' => 'continents', 'section' => 'basics', 'prompt' => 'Beschreibe den Kontinent {name} in zwei Sätzen.']);
    $second = AiCheck::create(['name' => 'Code', 'area' => 'continents', 'section' => 'basics', 'prompt' => 'Ist der Code {code} richtig?']);

    $component = Livewire::test(ContinentEditor::class, ['continent' => $europe->id])
        ->call('openAiCheck', 'basics')
        ->assertSet('aiPromptDraft', '')
        // Mit der Wahl einer Pruefung steht ihr Prompt zum Anpassen bereit.
        ->set('aiCheckId', (string) $check->id)
        ->assertSet('aiPromptDraft', 'Beschreibe den Kontinent {name} in zwei Sätzen.')
        ->assertSee('KI Prompt ansehen und anpassen')
        ->assertDontSee('für diesen Lauf angepasst')
        ->set('aiPromptDraft', 'Zu kurz')
        ->assertSee('für diesen Lauf angepasst')
        ->call('runAiCheck')
        ->assertHasErrors(['aiPromptDraft'])
        ->set('aiPromptDraft', 'Nenne drei Nachbarkontinente von {name}.')
        ->call('runAiCheck')
        ->assertHasNoErrors()
        ->assertSet('aiError', null)
        ->assertSee('Passt.');

    expect($component->get('aiResult.title'))->toBe('Übersicht (Prompt angepasst)')
        ->and($component->get('aiResult.prompt'))->toBe('Nenne drei Nachbarkontinente von Europa.')
        // Die hinterlegte Pruefung bleibt, wie sie ist – und es entsteht keine weitere.
        ->and($check->fresh()->prompt)->toBe('Beschreibe den Kontinent {name} in zwei Sätzen.')
        ->and($check->fresh()->name)->toBe('Übersicht')
        ->and(AiCheck::count())->toBe(2);

    Http::assertSent(fn ($request) => str_contains($request->body(), 'Nenne drei Nachbarkontinente von Europa.') && ! str_contains($request->body(), 'in zwei S'));

    // Zurueck zum hinterlegten Text – der Lauf nutzt ihn wieder unveraendert.
    $component->call('resetAiPrompt')
        ->assertSet('aiPromptDraft', 'Beschreibe den Kontinent {name} in zwei Sätzen.')
        ->assertDontSee('für diesen Lauf angepasst')
        ->call('runAiCheck');

    expect($component->get('aiResult.title'))->toBe('Übersicht')
        ->and($component->get('aiResult.prompt'))->toBe('Beschreibe den Kontinent Europa in zwei Sätzen.');

    // Eine andere Pruefung bringt ihren eigenen Prompt mit – der Entwurf der vorigen gilt nicht weiter.
    $component->set('aiPromptDraft', 'Etwas ganz anderes fragen.')
        ->set('aiCheckId', (string) $second->id)
        ->assertSet('aiPromptDraft', 'Ist der Code {code} richtig?');
});

it('stellt mit einem eigenen Prompt eine einmalige Frage und speichert ihn auf Wunsch als Pruefung', function () {
    config(['services.openai.key' => 'test-key']);
    Http::fake(['api.openai.com/*' => Http::response([
        'model' => 'gpt-4o-mini',
        'choices' => [['message' => ['content' => 'Passt.']]],
        'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 2, 'total_tokens' => 12],
    ])]);

    $europe = continent('Europa', 'EU');
    $germany = country('Deutschland', 'DE', $europe, ['currency_code' => 'EUR']);

    $component = Livewire::test(CountryEditor::class, ['country' => $germany->id])
        ->call('openAiCheck', 'details')
        ->set('aiCheckId', 'custom')
        ->call('runAiCheck')
        ->assertHasErrors(['aiCustomPrompt'])
        ->set('aiCustomPrompt', 'Ist {currency_code} die Währung von {name}?')
        ->set('aiCustomModel', 'gpt-4o-mini')
        ->call('runAiCheck')
        ->assertHasNoErrors()
        ->assertSet('aiError', null)
        ->assertSee('Passt.')
        ->assertSee('12 Token');

    expect($component->get('aiResult.title'))->toBe('Eigener Prompt')
        ->and(AiCheck::count())->toBe(0);

    // (Neben der Anfrage ruft die Seite auch die Modell-Liste ab.)
    Http::assertSent(fn ($request) => ($request->data()['model'] ?? null) === 'gpt-4o-mini'
        && str_contains($request->data()['messages'][0]['content'] ?? '', 'Ist EUR die Währung von Deutschland?'));

    // Auf Wunsch wird der Prompt zur Pruefung dieses Abschnitts.
    $component
        ->set('aiSaveAsCheck', true)
        ->call('runAiCheck')
        ->assertHasErrors(['aiCheckName'])
        ->set('aiCheckName', 'Währung plausibel?')
        ->call('runAiCheck')
        ->assertHasNoErrors()
        ->assertSet('aiSaveAsCheck', false)
        ->assertSee('Währung plausibel?');

    $saved = AiCheck::first();

    expect($saved->area)->toBe('countries')
        ->and($saved->section)->toBe('details')
        ->and($saved->model)->toBe('gpt-4o-mini')
        ->and($saved->prompt)->toBe('Ist {currency_code} die Währung von {name}?')
        ->and($saved->created_by)->toBe(auth()->id());
});

it('verwaltet die KI-Pruefungen je Bereich unter System > KI', function () {
    config(['services.openai.key' => 'test-key']);

    $page = Livewire::withQueryParams(['tab' => 'countries'])
        ->test(\App\Livewire\AdminV2\System\Ai::class)
        ->assertSee('KI-Prüfungen · Länder')
        ->assertSee('Stammdaten – Flughäfen')
        ->call('createCheck')
        ->assertSet('checkArea', 'countries')
        ->call('saveCheck')
        ->assertHasErrors(['checkName', 'checkPrompt'])
        ->set('checkName', 'Währung prüfen')
        ->set('checkSection', 'details')
        ->set('checkDescription', 'Prüft Währungscode und -name.')
        ->set('checkPrompt', 'Stimmt {currency_code} für {name}?')
        ->set('checkModel', 'gpt-4o-mini')
        ->call('saveCheck')
        ->assertHasNoErrors()
        ->assertSee('Währung prüfen')
        ->assertSee('Weitere Informationen')
        ->assertSee('gpt-4o-mini');
    Livewire::withQueryParams([]);

    $check = AiCheck::where('name', 'Währung prüfen')->first();

    expect($check->area)->toBe('countries')
        ->and($check->section)->toBe('details')
        ->and($check->model)->toBe('gpt-4o-mini')
        ->and($check->is_active)->toBeTrue()
        ->and($check->created_by)->toBe(auth()->id());

    // Ein Abschnitt eines anderen Bereichs wird abgelehnt; Bearbeiten, Ausschalten, Loeschen.
    $page->call('editCheck', $check->id)
        ->assertSet('checkName', 'Währung prüfen')
        ->set('checkSection', 'lounges')
        ->call('saveCheck')
        ->assertHasErrors(['checkSection'])
        ->set('checkSection', '')
        ->call('saveCheck')
        ->assertHasNoErrors()
        ->assertSee('Alle Abschnitte')
        ->call('toggleCheck', $check->id)
        ->assertSee('ausgeschaltet')
        ->call('deleteCheck', $check->id);

    expect(AiCheck::count())->toBe(0);

    // Die Reiter zaehlen die Pruefungen je Bereich.
    AiCheck::create(['name' => 'A', 'area' => 'cities', 'prompt' => 'Prompt A.']);
    expect(Livewire::test(\App\Livewire\AdminV2\System\Ai::class)->get('tabCounts'))->toBe(['cities' => 1]);
});

// ── Koordinaten ──────────────────────────────────────────────────────────

it('liest Koordinaten aus Zahlenpaaren und Google-Maps-Links', function () {
    expect(Coordinates::parse('48.1351, 11.5820'))->toEqual(['lat' => 48.1351, 'lng' => 11.582])
        ->and(Coordinates::parse('(48.1351; 11.5820)'))->toEqual(['lat' => 48.1351, 'lng' => 11.582])
        ->and(Coordinates::parse('-33.8688 151.2093'))->toEqual(['lat' => -33.8688, 'lng' => 151.2093])
        ->and(Coordinates::parse('https://www.google.com/maps/@48.1351,11.582,12z'))->toEqual(['lat' => 48.1351, 'lng' => 11.582])
        ->and(Coordinates::parse('https://www.google.com/maps/place/M%C3%BCnchen/@48.1,11.5,12z/data=!3m1!4b1!4m6!3m5!1s0x479e75f9a38c5fd9:0x10cb84a7db1987d!8m2!3d48.1351253!4d11.5819805'))->toEqual(['lat' => 48.1351253, 'lng' => 11.5819805])
        ->and(Coordinates::parse('https://www.google.com/maps?q=48.1351,11.5820'))->toEqual(['lat' => 48.1351, 'lng' => 11.582])
        ->and(Coordinates::parse('https://maps.google.com/?ll=48.1351%2C11.5820&z=12'))->toEqual(['lat' => 48.1351, 'lng' => 11.582])
        ->and(Coordinates::parse('95, 11'))->toBeNull()
        ->and(Coordinates::parse('München'))->toBeNull()
        ->and(Coordinates::parse(''))->toBeNull()
        ->and(Coordinates::format(11.58200000))->toBe('11.582')
        ->and(Coordinates::format('-0.00000000'))->toBe('0');
});

it('baut das Risikoprofil aus dem Formular und zurueck', function () {
    $form = CountryRiskProfile::toForm(['health' => ['required_vaccinations' => ['Gelbfieber'], 'malaria_risk' => true]]);

    expect($form['health']['required_vaccinations'])->toBe('Gelbfieber')
        ->and($form['health']['malaria_risk'])->toBeTrue()
        ->and($form['security']['overall_risk_level'])->toBe('')
        ->and(CountryRiskProfile::fromForm($form, null)['health'])->toEqual(['malaria_risk' => true, 'drinking_water_safe' => false, 'required_vaccinations' => ['Gelbfieber'], 'recommended_vaccinations' => []])
        ->and(CountryRiskProfile::fromForm(CountryRiskProfile::toForm(null), null))->toBeNull();
});

// ── Karte mit Laendergrenzen ─────────────────────────────────────────────

it('liefert die Laendergrenzen eines Kontinents und eines Landes als GeoJSON fuer die Karte', function () {
    $europe = continent('Europa', 'EU');
    $germany = country('Deutschland', 'DE', $europe, ['lat' => 51.1, 'lng' => 10.4]);
    country('Frankreich', 'FR', $europe);

    DB::statement(
        'insert into country_boundaries (country_id, iso_a2, iso_a3, name, source, source_features, boundary, created_at, updated_at)
         values (?, ?, ?, ?, ?, 1, ST_GeomFromGeoJSON(?, 1, 4326), now(), now())',
        [$germany->id, 'DE', 'DEU', 'Germany', 'test', json_encode([
            'type' => 'MultiPolygon',
            'coordinates' => [[[[6.0, 47.0], [15.0, 47.0], [15.0, 55.0], [6.0, 55.0], [6.0, 47.0]]]],
        ])],
    );

    $continentJson = $this->get(route('adminv2.master-data.boundaries.continent', $europe->id))
        ->assertOk()
        ->assertJsonPath('type', 'FeatureCollection')
        ->assertJsonCount(1, 'features')
        ->assertJsonPath('features.0.properties.name', 'Deutschland')
        ->assertJsonPath('features.0.properties.iso', 'DE')
        ->assertJsonPath('features.0.geometry.type', 'MultiPolygon')
        ->json();

    // Koordinaten als Laenge/Breite, wie GeoJSON es verlangt (die Reihenfolge der Punkte darf MySQL aendern).
    $ring = $continentJson['features'][0]['geometry']['coordinates'][0][0];
    expect(collect($ring)->pluck(0)->min())->toEqual(6)
        ->and(collect($ring)->pluck(0)->max())->toEqual(15)
        ->and(collect($ring)->pluck(1)->min())->toEqual(47)
        ->and(collect($ring)->pluck(1)->max())->toEqual(55);

    $this->get(route('adminv2.master-data.boundaries.country', $germany->id))
        ->assertOk()
        ->assertJsonCount(1, 'features');

    // Ein Land ohne Grenze liefert eine leere Sammlung.
    $this->get(route('adminv2.master-data.boundaries.country', Country::where('iso_code', 'FR')->first()->id))
        ->assertOk()
        ->assertJsonCount(0, 'features');

    // Die Bearbeitungsseiten binden die Karte mit der passenden Quelle ein (als JavaScript-Zeichenkette).
    $asJs = fn (string $url) => str_replace('/', '\\/', $url);
    $this->get('/adminv2/master-data/continents/'.$europe->id)
        ->assertOk()
        ->assertSee($asJs(route('adminv2.master-data.boundaries.continent', $europe->id)), false);
    $this->get('/adminv2/master-data/countries/'.$germany->id)
        ->assertOk()
        ->assertSee($asJs(route('adminv2.master-data.boundaries.country', $germany->id)), false);

    auth()->logout();
    $this->get(route('adminv2.master-data.boundaries.continent', $europe->id))->assertRedirect(route('adminv2.login'));
});

it('laesst die KI die Felder eines Abschnitts pruefen und uebernimmt Vorschlaege einzeln oder alle', function () {
    config(['services.openai.key' => 'test-key']);
    Http::fake(['api.openai.com/*' => Http::response([
        'model' => 'gpt-4o-2024-08-06',
        'choices' => [['message' => ['content' => "```json\n".json_encode([
            'summary' => 'Zwei Angaben weichen ab.',
            'fields' => [
                'currency_code' => ['status' => 'ok', 'note' => 'EUR ist richtig.'],
                'currency_name' => ['status' => 'change', 'value' => 'Euro', 'note' => 'Der Name fehlt.'],
                'currency_symbol' => ['status' => 'change', 'value' => '€'],
                'phone_prefix' => ['status' => 'unknown', 'note' => 'Nicht geprüft.'],
                'timezone' => ['status' => 'change', 'value' => 'Europe/Berlin'],
                'population' => ['status' => 'change', 'value' => '83.200.000', 'note' => 'Stand 2024.'],
                'area_km2' => ['status' => 'change'],
            ],
        ], JSON_UNESCAPED_UNICODE)."\n```"]]],
        'usage' => ['prompt_tokens' => 200, 'completion_tokens' => 80, 'total_tokens' => 280],
    ])]);

    $europe = continent('Europa', 'EU');
    $germany = country('Deutschland', 'DE', $europe, ['currency_code' => 'EUR']);

    $component = Livewire::test(CountryEditor::class, ['country' => $germany->id])
        ->call('openAiCheck', 'general')
        ->set('aiCheckId', 'review')
        ->call('reviewAiFields')
        ->assertSet('aiReview', null);
    expect($component->get('aiError'))->toContain('je Abschnitt');

    $component->call('openAiCheck', 'details')
        ->assertSee('Felder automatisch prüfen')
        ->set('aiCheckId', 'review')
        ->set('aiCustomModel', 'gpt-4o-mini')
        ->call('reviewAiFields')
        ->assertSet('aiError', null)
        ->assertSee('Zwei Angaben weichen ab.')
        ->assertSee('Alle 4 Vorschläge übernehmen')
        ->assertSee('280 Token')
        // Hinweise stehen unter den Feldern.
        ->assertSee('KI: korrekt')
        ->assertSee('KI-Vorschlag:');

    $review = $component->get('aiReview');

    expect($review['section'])->toBe('details')
        ->and($review['fields']['currency_code']['status'])->toBe('ok')
        ->and($review['fields']['currency_name'])->toMatchArray(['status' => 'change', 'value' => 'Euro', 'note' => 'Der Name fehlt.'])
        ->and($review['fields']['phone_prefix']['status'])->toBe('unknown')
        // Ein "change" ohne Wert ist kein Vorschlag.
        ->and($review['fields']['area_km2']['status'])->toBe('unknown');

    Http::assertSent(fn ($request) => ($request->data()['model'] ?? null) === 'gpt-4o-mini'
        && str_contains($request->data()['messages'][0]['content'] ?? '', 'currency_code („Währungscode“): EUR')
        && str_contains($request->data()['messages'][0]['content'] ?? '', 'Name: Deutschland')
        && str_contains($request->data()['messages'][0]['content'] ?? '', 'Antworte ausschließlich mit JSON'));

    // Einzeln uebernehmen – Zahlen ohne Tausenderpunkte.
    $component->call('applyAiSuggestion', 'population')
        ->assertSet('population', '83200000')
        ->assertSet('aiReview.fields.population.applied', true)
        ->call('applyAiSuggestion', 'currency_code')
        ->assertSet('currencyCode', 'EUR');

    // Alle uebrigen uebernehmen.
    $component->call('applyAllAiSuggestions')
        ->assertSet('currencyName', 'Euro')
        ->assertSet('currencySymbol', '€')
        ->assertSet('timezone', 'Europe/Berlin')
        ->assertDispatched('adminv2-toast')
        ->assertDontSee('Alle 4 Vorschläge übernehmen');

    // Noch nicht gespeichert – erst "Speichern" schreibt.
    expect($germany->fresh()->currency_name)->toBeNull();

    $component->call('save')->assertHasNoErrors();

    expect($germany->fresh()->currency_name)->toBe('Euro')
        ->and($germany->fresh()->population)->toBe(83200000);

    $component->call('dismissAiReview')->assertSet('aiReview', null);
});

it('bietet Pruefungen "nur gesamter Eintrag" ausschliesslich im Kopf des Eintrags an', function () {
    $europe = continent('Europa', 'EU');
    $germany = country('Deutschland', 'DE', $europe);

    AiCheck::create(['name' => 'Gesamtbild', 'area' => 'countries', 'section' => 'general', 'prompt' => 'Beurteile den ganzen Eintrag.']);
    AiCheck::create(['name' => 'Überall', 'area' => 'countries', 'section' => null, 'prompt' => 'Überall.']);
    AiCheck::create(['name' => 'Nur Details', 'area' => 'countries', 'section' => 'details', 'prompt' => 'Details.']);

    Livewire::test(CountryEditor::class, ['country' => $germany->id])
        ->call('openAiCheck', 'details')
        ->assertSee('Überall')->assertSee('Nur Details')->assertDontSee('Gesamtbild')
        ->call('openAiCheck', 'basics')
        ->assertSee('Überall')->assertDontSee('Nur Details')->assertDontSee('Gesamtbild')
        ->call('openAiCheck', 'general')
        ->assertSee('Gesamtbild')->assertSee('Überall')->assertSee('Nur Details');

    // Unter System > KI laesst sich das so hinterlegen.
    Livewire::withQueryParams(['tab' => 'countries'])
        ->test(\App\Livewire\AdminV2\System\Ai::class)
        ->assertSee('Nur gesamter Eintrag')
        ->call('createCheck')
        ->set('checkName', 'Nur oben')
        ->set('checkSection', 'general')
        ->set('checkPrompt', 'Prüfe alles zusammen.')
        ->call('saveCheck')
        ->assertHasNoErrors();
    Livewire::withQueryParams([]);

    expect(AiCheck::where('name', 'Nur oben')->value('section'))->toBe('general');
});

it('speichert Notizen zu den Punkten des Risikoprofils je Sprache und uebersetzt sie per DeepL', function () {
    config(['services.deepl.api_key' => 'test-key', 'app.event_languages' => 'de,en,nl', 'app.event_source_language' => 'de']);
    Http::fake(['api.deepl.com/*' => fn ($request) => Http::response(['translations' => [['text' => '['.$request['target_lang'].'] '.$request['text']]]])]);

    $europe = continent('Europa', 'EU');
    $country = country('Testland', 'TL', $europe, ['risk_profile' => ['security' => ['crime_level' => 2]]]);

    $component = Livewire::test(CountryEditor::class, ['country' => $country->id])
        ->assertSet('riskProfile.security.notes.crime_level.de', '')
        ->set('riskProfile.security.notes.crime_level.de', ' Taschendiebstahl in Großstädten. ')
        ->set('riskProfile.health.notes.malaria_risk.de', 'Kein Risiko.')
        ->set('riskProfile.health.notes.malaria_risk.en', 'No risk.')
        ->call('translateRiskNotes')
        ->assertDispatched('adminv2-toast')
        ->assertSet('riskProfile.security.notes.crime_level.en', '[EN-GB] Taschendiebstahl in Großstädten.')
        ->assertSet('riskProfile.security.notes.crime_level.nl', '[NL] Taschendiebstahl in Großstädten.')
        // Ausgefuellte Sprachen bleiben ohne "ueberschreiben" stehen.
        ->assertSet('riskProfile.health.notes.malaria_risk.en', 'No risk.');

    // Erst "Speichern" schreibt.
    expect($country->fresh()->risk_profile['security'])->not->toHaveKey('notes');

    $component->call('save')->assertHasNoErrors();

    expect($country->fresh()->risk_profile['security']['notes'])->toEqual(['crime_level' => [
        'de' => 'Taschendiebstahl in Großstädten.',
        'en' => '[EN-GB] Taschendiebstahl in Großstädten.',
        'nl' => '[NL] Taschendiebstahl in Großstädten.',
    ]])->and($country->fresh()->risk_profile['health']['notes']['malaria_risk']['en'])->toBe('No risk.');

    // Geleerte Notizen fallen wieder heraus.
    Livewire::test(CountryEditor::class, ['country' => $country->id])
        ->assertSet('riskProfile.security.notes.crime_level.nl', '[NL] Taschendiebstahl in Großstädten.')
        ->set('riskProfile.security.notes.crime_level', ['de' => '', 'en' => '', 'nl' => ''])
        ->call('save')->assertHasNoErrors();

    expect($country->fresh()->risk_profile['security'])->toEqual(['crime_level' => 2]);
});

it('prueft das Risikoprofil Feld fuer Feld und uebernimmt Stufen, Schalter und Listen', function () {
    config(['services.openai.key' => 'test-key']);
    Http::fake(['api.openai.com/*' => Http::response([
        'model' => 'gpt-4o-2024-08-06',
        'choices' => [['message' => ['content' => json_encode([
            'summary' => 'Weitgehend plausibel.',
            'fields' => [
                'risk_security_overall_risk_level' => ['status' => 'change', 'value' => '3 – Mittel'],
                'risk_security_crime_level' => ['status' => 'change', 'value' => 'Hoch'],
                'risk_health_malaria_risk' => ['status' => 'change', 'value' => 'Ja', 'note' => 'In Teilen des Landes.'],
                'risk_health_required_vaccinations' => ['status' => 'change', 'value' => 'Gelbfieber, Polio'],
                'risk_entry_passport_validity_months' => ['status' => 'change', 'value' => '6 Monate'],
                'risk_climate_climate_zone' => ['status' => 'ok'],
                'risk_natural_hazards_flood_risk' => ['status' => 'change', 'value' => 'extrem'],
            ],
        ], JSON_UNESCAPED_UNICODE)]]],
        'usage' => ['prompt_tokens' => 300, 'completion_tokens' => 120, 'total_tokens' => 420],
    ])]);

    $europe = continent('Europa', 'EU');
    $country = country('Testland', 'TL', $europe, ['risk_profile' => ['climate' => ['climate_zone' => 'tropisch']]]);

    $component = Livewire::test(CountryEditor::class, ['country' => $country->id])
        ->call('openAiCheck', 'risk_profile')
        ->set('aiCheckId', 'review')
        ->call('reviewAiFields')
        ->assertSet('aiError', null)
        ->assertSee('Sicherheit › Gesamt-Sicherheitsrisiko')
        ->assertSee('KI-Vorschlag:');

    // Die KI bekommt jedes Feld einzeln, lesbar beschriftet.
    Http::assertSent(fn ($request) => str_contains($request->data()['messages'][0]['content'] ?? '', 'risk_climate_climate_zone („Klima › Klimazone“): tropisch'));

    // Die Begruendung der KI laesst sich in die Notiz des Punktes uebernehmen.
    $component->assertSee('Text in Notiz übernehmen')
        ->set('riskProfile.health.notes.malaria_risk.de', 'Eigene Notiz.')
        ->call('applyAiNote', 'risk_health_malaria_risk')
        ->assertSet('riskProfile.health.notes.malaria_risk.de', "Eigene Notiz.\nIn Teilen des Landes.")
        ->assertSet('aiReview.fields.risk_health_malaria_risk.note_applied', true)
        ->call('applyAllAiNotes')
        ->assertSet('riskProfile.health.notes.malaria_risk.de', "Eigene Notiz.\nIn Teilen des Landes.");

    $component->call('applyAllAiSuggestions')
        ->assertSet('riskProfile.security.overall_risk_level', '3')
        ->assertSet('riskProfile.security.crime_level', '4')
        ->assertSet('riskProfile.health.malaria_risk', true)
        ->assertSet('riskProfile.health.required_vaccinations', 'Gelbfieber, Polio')
        ->assertSet('riskProfile.entry.passport_validity_months', '6')
        // "extrem" ist keine Stufe – bleibt offen.
        ->assertSet('riskProfile.natural_hazards.flood_risk', '')
        ->assertSet('aiReview.fields.risk_natural_hazards_flood_risk.applied', null)
        ->call('save')
        ->assertHasNoErrors();

    expect($country->fresh()->risk_profile['security']['crime_level'])->toBe(4)
        ->and($country->fresh()->risk_profile['health']['required_vaccinations'])->toBe(['Gelbfieber', 'Polio'])
        ->and($country->fresh()->overall_risk_level)->toBe(3);
});
