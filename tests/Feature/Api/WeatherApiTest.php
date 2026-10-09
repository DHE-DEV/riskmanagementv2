<?php

use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function weatherToken(): string
{
    return Customer::factory()->create(['gtm_api_enabled' => true])->createToken('Test', ['gtm:read'])->plainTextToken;
}

function fakeOpenWeather(): void
{
    $hours = [];
    foreach (range(0, 15) as $i) {
        $dt = 1791568800 + $i * 10800; // ab 9.10.2026 18:00 Ortszeit (+02:00)
        $hours[] = [
            'dt' => $dt,
            'main' => ['temp' => 10 + $i, 'feels_like' => 8 + $i],
            'weather' => [['main' => $i % 2 ? 'Clouds' : 'Rain', 'description' => $i % 2 ? 'Bedeckt' : 'Leichter Regen', 'icon' => $i % 2 ? '04d' : '10d']],
            'wind' => ['speed' => 5.0],
            'pop' => $i % 2 ? 0.1 : 0.9,
        ];
    }

    Http::fake([
        'api.openweathermap.org/data/2.5/weather*' => Http::response([
            'name' => 'Mitte',
            'dt' => 1791568305,
            'timezone' => 7200,
            'main' => ['temp' => 9.45, 'feels_like' => 6.2, 'humidity' => 93, 'pressure' => 1008],
            'weather' => [['main' => 'Clouds', 'description' => 'Bedeckt', 'icon' => '04n']],
            'wind' => ['speed' => 8.3, 'deg' => 220],
            'visibility' => 6900,
            'clouds' => ['all' => 100],
            'sys' => ['sunrise' => 1791523241, 'sunset' => 1791563186, 'country' => 'DE'],
        ]),
        'api.openweathermap.org/data/2.5/forecast*' => Http::response([
            'cnt' => count($hours),
            'list' => $hours,
            'city' => ['name' => 'Berlin', 'timezone' => 7200],
        ]),
    ]);
}

beforeEach(fn () => Cache::flush());

it('liefert aktuelles Wetter, Stunden und Tage fuer die Hauptstadt eines Landes', function () {
    fakeOpenWeather();
    $europe = Continent::create(['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'code' => 'EU', 'sort_order' => 1]);
    $country = Country::create(['name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'], 'iso_code' => 'DE', 'iso3_code' => 'DEU', 'continent_id' => $europe->id, 'lat' => 51.1657, 'lng' => 10.4515]);
    City::create(['country_id' => $country->id, 'name_translations' => ['de' => 'Berlin', 'en' => 'Berlin'], 'lat' => 52.52, 'lng' => 13.405, 'is_capital' => true]);

    $response = $this->withToken(weatherToken())->getJson('/api/v1/countries/DE/weather?lang=de')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.location.name', 'Berlin')
        ->assertJsonPath('data.location.lat', 52.52)
        ->assertJsonPath('data.current.temperature', 10) // 9,45 → Dienst rundet auf 9,5 → 10
        ->assertJsonPath('data.current.feels_like', 6)
        ->assertJsonPath('data.current.wind_speed', 30)
        ->assertJsonPath('data.current.humidity', 93)
        ->assertJsonPath('data.current.icon', '04n')
        ->assertJsonPath('data.current.description', 'Bedeckt')
        ->assertJsonPath('data.current.sunrise', '2026-10-09T07:20:41+02:00')
        ->assertJsonPath('data.hourly.0.time', '2026-10-09T20:00:00+02:00')
        ->assertJsonPath('data.hourly.0.precipitation_probability', 90)
        ->assertJsonPath('data.daily.0.date', '2026-10-09')
        ->assertJsonPath('data.daily.1.date', '2026-10-10')
        ->assertJsonPath('data.daily.1.temp_min', 12)
        ->assertJsonPath('data.daily.1.temp_max', 19)
        ->assertJsonPath('data.source.name', 'OpenWeatherMap');

    expect($response->json('data.hourly'))->toHaveCount(8)
        ->and($response->json('data.daily'))->toHaveCount(3)
        // Tagessymbol vom Mittag, immer als Tagvariante.
        ->and($response->json('data.daily.1.icon'))->toEndWith('d');

    // Die Hauptstadt-Koordinaten gehen an OpenWeatherMap.
    Http::assertSent(fn ($request) => str_contains($request->url(), 'lat=52.52') && str_contains($request->url(), 'lang=de'));

    $this->withToken(weatherToken())->getJson('/api/v1/countries/XX/weather')->assertNotFound();
});

it('liefert das Wetter fuer Koordinaten und meldet Ausfaelle der Quelle', function () {
    fakeOpenWeather();

    $this->withToken(weatherToken())->getJson('/api/v1/weather?lat=52.52&lng=13.405&lang=en')
        ->assertOk()
        ->assertJsonPath('data.location.name', 'Mitte')
        ->assertJsonPath('data.current.condition', 'Bewölkt');

    $this->withToken(weatherToken())->getJson('/api/v1/weather?lat=200&lng=13')->assertStatus(422);
});

it('meldet 503, wenn die Wetterquelle nicht antwortet', function () {
    Http::fake(['api.openweathermap.org/*' => Http::response('', 500)]);

    $this->withToken(weatherToken())->getJson('/api/v1/weather?lat=52.52&lng=13.405')
        ->assertStatus(503)
        ->assertJsonPath('message', 'Weather service unavailable.');
});
