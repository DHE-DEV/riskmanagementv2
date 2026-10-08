<?php

use App\Livewire\AdminV2\MasterData\Countries\Editor as CountryEditor;
use App\Models\Continent;
use App\Models\Country;
use App\Models\CountryHoliday;
use App\Models\Customer;
use App\Models\MobileOperator;
use App\Models\Region;
use App\Models\TaxiApp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function apiCustomerToken(array $attributes = [], array $abilities = ['gtm:read']): string
{
    $customer = Customer::factory()->create(array_merge(['gtm_api_enabled' => true], $attributes));

    return $customer->createToken('Test', $abilities)->plainTextToken;
}

function apiCountry(): Country
{
    $europe = Continent::create(['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'code' => 'EU', 'sort_order' => 1]);
    $france = Country::create(['name_translations' => ['de' => 'Frankreich', 'en' => 'France'], 'iso_code' => 'FR', 'iso3_code' => 'FRA', 'continent_id' => $europe->id]);

    $country = Country::create([
        'name_translations' => ['de' => 'Martinique', 'en' => 'Martinique', 'nl' => 'Martinique'],
        'iso_code' => 'MQ',
        'iso3_code' => 'MTQ',
        'continent_id' => $europe->id,
        'territory_type' => 'dependent',
        'parent_country_id' => $france->id,
        'driving_side' => 'right',
        'is_eu_member' => true,
        'currency_code' => 'EUR',
        'currency_name' => 'Euro',
        'currency_symbol' => '€',
        'phone_prefix' => '+596',
        'timezone' => 'America/Martinique',
        'population' => 360000,
        'lat' => 14.6415,
        'lng' => -61.0242,
        'travel_info' => [
            'short_description' => ['de' => 'Die Blumeninsel.', 'en' => 'The island of flowers.'],
            'description' => ['de' => "Absatz eins.\n\nAbsatz zwei."],
            'known_for' => ['de' => ['Rum', 'Strände'], 'en' => ['Rum', 'Beaches']],
            'intro' => ['de' => 'Karibik mit französischem Flair.'],
            'emergency' => ['general' => '112', 'police' => '17'],
            'religions' => ['christianity'],
            'national_day' => ['date' => '1789-07-14', 'name' => ['de' => 'Nationalfeiertag', 'en' => 'Bastille Day']],
            'plug_types' => ['C', 'E'],
            'voltage' => 220,
            'frequency' => 50,
            'power_notes' => ['de' => 'Wie in Frankreich.'],
            'tipping' => [
                'restaurants' => ['from' => 5, 'to' => 10, 'unit' => 'percent', 'description' => ['de' => 'Service meist enthalten.']],
                'taxi' => ['mode' => 'fixed', 'from' => 2, 'unit' => 'amount', 'currency' => 'EUR'],
            ],
        ],
        'risk_profile' => [
            'security' => ['overall_risk_level' => 2, 'crime_level' => 2, 'notes' => ['crime_level' => ['de' => 'Taschendiebstahl in Fort-de-France.']]],
            'health' => ['health_risk_level' => 1, 'malaria_risk' => false, 'required_vaccinations' => ['Gelbfieber']],
            'natural_hazards' => ['natural_hazard_level' => 4, 'hurricane_risk' => 4],
        ],
    ]);

    $region = Region::create(['country_id' => $country->id, 'name_translations' => ['de' => 'Nord', 'en' => 'North'], 'code' => 'MQ-N']);
    CountryHoliday::create(['country_id' => $country->id, 'date' => '2026-07-14', 'name_translations' => ['de' => 'Nationalfeiertag', 'en' => 'Bastille Day'], 'is_national' => true, 'source' => 'manual']);
    $regional = CountryHoliday::create(['country_id' => $country->id, 'date' => '2026-05-22', 'name_translations' => ['de' => 'Abschaffung der Sklaverei'], 'is_national' => false, 'source' => 'manual']);
    $regional->regions()->attach($region->id);
    CountryHoliday::create(['country_id' => $country->id, 'date' => '2027-07-14', 'name_translations' => ['de' => 'Nationalfeiertag'], 'is_national' => true, 'source' => 'manual']);

    $country->taxiApps()->attach(TaxiApp::create(['name' => 'Uber', 'description_translations' => ['de' => 'Fahrdienst', 'en' => 'Ride hailing'], 'website_url' => 'https://uber.com', 'is_active' => true, 'sort_order' => 1])->id);
    $country->mobileOperators()->attach(MobileOperator::create(['name' => 'Orange', 'website_url' => 'https://orange.fr', 'offers_esim' => true, 'is_active' => true, 'sort_order' => 1])->id);

    return $country;
}

it('liefert alle Angaben eines Landes strukturiert als JSON', function () {
    $country = apiCountry();
    $token = apiCustomerToken();

    $response = $this->withToken($token)->getJson('/api/v1/countries/mq?year=2026')->assertOk();

    $data = $response->json('data');

    expect($response->json('success'))->toBeTrue()
        ->and($data['iso_code'])->toBe('MQ')
        ->and($data['iso3_code'])->toBe('MTQ')
        ->and($data['name'])->toBe(['de' => 'Martinique', 'en' => 'Martinique', 'nl' => 'Martinique'])
        ->and($data['continent'])->toBe(['code' => 'EU', 'name' => ['de' => 'Europa', 'en' => 'Europe']])
        ->and($data['territory']['type'])->toBe('dependent')
        ->and($data['territory']['parent_country']['iso_code'])->toBe('FR')
        ->and($data['membership'])->toBe(['eu' => true, 'schengen' => false])
        ->and($data['currency']['code'])->toBe('EUR')
        ->and($data['coordinates'])->toBe(['lat' => 14.6415, 'lng' => -61.0242])
        ->and($data['flag']['svg_url'])->toContain('mq.svg')
        ->and($data['description']['short'])->toBe(['de' => 'Die Blumeninsel.', 'en' => 'The island of flowers.'])
        ->and($data['description']['long']['de'])->toBe("Absatz eins.\n\nAbsatz zwei.")
        ->and($data['description']['known_for']['en'])->toBe(['Rum', 'Beaches'])
        ->and($data['travel_info']['emergency'])->toBe(['general' => '112', 'police' => '17', 'ambulance' => null, 'fire' => null])
        ->and($data['travel_info']['religions'][0]['key'])->toBe('christianity')
        ->and($data['travel_info']['national_day']['day_month'])->toBe('07-14')
        ->and($data['travel_info']['driving_side_label'])->toBe('Rechtsverkehr')
        ->and($data['power']['voltage'])->toBe(220)
        ->and(array_column($data['power']['plug_types'], 'type'))->toBe(['C', 'E'])
        ->and($data['power']['plug_types'][0]['image'])->toContain('plug-types/c.svg')
        ->and($data['tipping']['restaurants'])->toMatchArray(['mode' => 'range', 'from' => 5.0, 'to' => 10.0, 'unit' => 'percent'])
        ->and($data['tipping']['taxi'])->toMatchArray(['mode' => 'fixed', 'from' => 2.0, 'to' => null, 'currency' => 'EUR'])
        ->and($data['tipping']['hotels'])->toBeNull()
        ->and($data['taxi_apps'][0])->toMatchArray(['name' => 'Uber', 'website_url' => 'https://uber.com'])
        ->and($data['mobile_operators'][0])->toMatchArray(['name' => 'Orange', 'offers_esim' => true])
        ->and($data['holidays']['year'])->toBe(2026)
        ->and(array_column($data['holidays']['items'], 'date'))->toBe(['2026-05-22', '2026-07-14'])
        ->and($data['holidays']['items'][0]['is_national'])->toBeFalse()
        ->and($data['holidays']['items'][0]['regions'][0]['code'])->toBe('MQ-N')
        ->and($data['holidays']['items'][1]['weekday'])->toBe(2)
        ->and($data['images']['hero'])->toBeNull()
        ->and($data['images']['gallery'])->toBe([])
        ->and($data['risk_profile']['overall'])->toBe(['level' => 4, 'label' => 'Hoch'])
        ->and($data['risk_profile']['categories']['security']['fields']['crime_level'])->toBe(['value' => 2, 'label' => 'Niedrig', 'note' => ['de' => 'Taschendiebstahl in Fort-de-France.']])
        ->and($data['risk_profile']['categories']['health']['fields']['malaria_risk']['value'])->toBeFalse()
        ->and($data['risk_profile']['categories']['health']['fields']['required_vaccinations']['value'])->toBe(['Gelbfieber'])
        ->and($data['risk_profile']['categories']['climate']['fields']['climate_zone']['value'])->toBeNull();

    // Ohne Jahr: das laufende Jahr.
    expect($this->withToken($token)->getJson('/api/v1/countries/MTQ')->assertOk()->json('data.holidays.year'))->toBe(now()->year);
});

it('liefert in der Laenderliste Flagge und Titelbild fuer Apps', function () {
    apiCountry();

    $rows = $this->withToken(apiCustomerToken())->getJson('/api/v1/countries')->assertOk()->json('data');
    $martinique = collect($rows)->firstWhere('iso_code', 'MQ');

    expect($martinique)->toMatchArray(['name_de' => 'Martinique', 'name_nl' => 'Martinique'])
        ->toHaveKey('hero_image_url')
        ->and($martinique['flag_url'])->toContain('mq.svg');
});

it('reduziert mit ?lang auf eine Sprache mit Rueckfall auf Deutsch', function () {
    apiCountry();
    $token = apiCustomerToken();

    $data = $this->withToken($token)->getJson('/api/v1/countries/MQ?lang=en&year=2026')->assertOk()->json('data');

    expect($data['name'])->toBe('Martinique')
        ->and($data['description']['short'])->toBe('The island of flowers.')
        // Keine englische Fassung – Deutsch als Rueckfall.
        ->and($data['description']['long'])->toBe("Absatz eins.\n\nAbsatz zwei.")
        ->and($data['description']['known_for'])->toBe(['Rum', 'Beaches'])
        ->and($data['travel_info']['religions'][0]['name'])->toBe('Christianity')
        ->and($data['holidays']['items'][1]['name'])->toBe('Bastille Day')
        ->and($data['holidays']['items'][0]['regions'][0]['name'])->toBe('North')
        ->and($data['risk_profile']['categories']['security']['fields']['crime_level']['note'])->toBe('Taschendiebstahl in Fort-de-France.')
        ->and($data['taxi_apps'][0]['description'])->toBe('Ride hailing');

    // Unbekannte Sprache: alle Sprachen.
    expect($this->withToken($token)->getJson('/api/v1/countries/MQ?lang=xx')->assertOk()->json('data.name'))->toBeArray();
});

it('weist unbekannte Laender, fehlende Token und fehlende Freigabe ab', function () {
    apiCountry();

    // Zwischen den Abrufen den Guard leeren – sonst bleibt der zuletzt erkannte Kunde haengen.
    $fresh = fn () => app('auth')->forgetGuards();

    $this->getJson('/api/v1/countries/MQ')->assertStatus(401);
    $fresh();
    $this->withToken(apiCustomerToken(['gtm_api_enabled' => false]))->getJson('/api/v1/countries/MQ')->assertStatus(403);
    $fresh();
    $this->withToken(apiCustomerToken([], ['folder:read']))->getJson('/api/v1/countries/MQ')->assertStatus(403);
    $fresh();

    $token = apiCustomerToken();
    $this->withToken($token)->getJson('/api/v1/countries/ZZ')->assertStatus(404)->assertJson(['success' => false]);
    $this->withToken($token)->getJson('/api/v1/countries/M')->assertStatus(404);
    $this->withToken($token)->getJson('/api/v1/countries/MQ?year=1800')->assertStatus(422);
});

it('zeigt im Laender-Editor per API-Test die Antwort des Endpunkts', function () {
    $country = apiCountry();
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));

    $component = Livewire::test(CountryEditor::class, ['country' => $country->id])
        ->assertSee('API-Test')
        ->assertSet('apiPreview', null)
        ->call('loadApiPreview')
        ->assertSet('apiPreviewError', null);

    $json = json_decode((string) $component->get('apiPreview'), true);

    expect($json['success'])->toBeTrue()
        ->and($json['data']['iso_code'])->toBe('MQ')
        ->and($json['data']['description']['short']['de'])->toBe('Die Blumeninsel.')
        ->and($component->instance()->apiPreviewUrl())->toEndWith('/v1/countries/MQ');

    // Sprache und Jahr gehen in Abruf und Adresse ein.
    $component->set('apiPreviewLang', 'en')->set('apiPreviewYear', '2027')->call('loadApiPreview');
    $json = json_decode((string) $component->get('apiPreview'), true);

    expect($json['data']['description']['short'])->toBe('The island of flowers.')
        ->and($json['data']['holidays']['year'])->toBe(2027)
        ->and(array_column($json['data']['holidays']['items'], 'date'))->toBe(['2027-07-14'])
        ->and($component->instance()->apiPreviewUrl())->toEndWith('/v1/countries/MQ?lang=en&year=2027');

    // Ungueltiges Jahr: Fehlermeldung statt Antwort.
    $component->set('apiPreviewYear', '1800')->call('loadApiPreview')->assertSet('apiPreview', null);
    expect($component->get('apiPreviewError'))->not->toBeNull();
});
