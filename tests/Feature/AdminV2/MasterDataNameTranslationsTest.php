<?php

use App\Livewire\AdminV2\MasterData\Cities\Editor as CityEditor;
use App\Livewire\AdminV2\MasterData\Countries\Editor as CountryEditor;
use App\Livewire\AdminV2\MasterData\Regions\Editor as RegionEditor;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Stammdaten: Namen in allen Sprachen aus EVENT_LANGUAGES pflegen und fehlende ergänzen.
 */
function namesCountry(array $names = ['de' => 'Frankreich', 'en' => 'France']): Country
{
    $continent = Continent::create(['code' => 'EU', 'name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);

    return Country::create(['iso_code' => 'FR', 'iso3_code' => 'FRA', 'continent_id' => $continent->id, 'name_translations' => $names]);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));
});

it('zeigt die niederländische Fassung eines Regionsnamens und speichert Änderungen daran', function () {
    $region = Region::create(['country_id' => namesCountry()->id, 'code' => 'BRE', 'name_translations' => ['de' => 'Bretagne', 'en' => 'Brittany', 'nl' => 'Bretagne (alt)']]);

    Livewire::test(RegionEditor::class, ['region' => $region->id])
        ->assertSet('otherNames.nl', 'Bretagne (alt)')
        ->set('otherNames.nl', 'Bretagne')
        ->call('save')
        ->assertHasNoErrors();

    expect($region->refresh()->name_translations)->toBe(['de' => 'Bretagne', 'en' => 'Brittany', 'nl' => 'Bretagne']);
});

it('entfernt eine Sprache, wenn ihr Feld geleert wird', function () {
    $city = City::create(['country_id' => namesCountry()->id, 'name_translations' => ['de' => 'Nizza', 'en' => 'Nice', 'nl' => 'Nice']]);

    Livewire::test(CityEditor::class, ['city' => $city->id])
        ->set('otherNames.nl', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($city->refresh()->name_translations)->toBe(['de' => 'Nizza', 'en' => 'Nice']);
});

it('übersetzt leere Sprachen per DeepL aus dem englischen Namen mit dem Land als Kontext', function () {
    config(['services.deepl.api_key' => 'test-key', 'services.deepl.api_url' => 'https://api.deepl.test/v2/translate']);
    Http::fake(['api.deepl.test/*' => fn (Request $request) => Http::response(['translations' => [['text' => $request['target_lang'].':'.$request['text'][0]]]])]);
    $city = City::create(['country_id' => namesCountry()->id, 'name_translations' => ['de' => 'Nizza', 'en' => 'Nice']]);

    Livewire::test(CityEditor::class, ['city' => $city->id])
        ->call('translateNames')
        ->assertSet('otherNames.nl', 'NL:Nice');

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'deepl') && ($request->data()['source_lang'] ?? null) === 'EN' && str_contains($request->data()['context'] ?? '', 'in France'));
});

it('ergänzt beim Land fehlende Sprachen aus der intl-Länderliste', function () {
    $country = namesCountry();

    Livewire::test(CountryEditor::class, ['country' => $country->id])
        ->call('completeNames')
        ->assertSet('extraNames', [['code' => 'nl', 'name' => 'Frankrijk']]);
});
