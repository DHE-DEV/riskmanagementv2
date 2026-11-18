<?php

use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\EventType;
use App\Models\Region;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * names:translate ergänzt fehlende Namen in allen Sprachen aus EVENT_LANGUAGES:
 * Länder aus der intl-Länderliste, alles andere per DeepL mit Kontext; vorhandene Namen bleiben.
 */
function fakeNameDeepL(): void
{
    config(['services.deepl.api_key' => 'test-key', 'services.deepl.api_url' => 'https://api.deepl.test/v2/translate']);

    Http::fake([
        'api.deepl.test/*' => fn (Request $request) => Http::response([
            'translations' => array_map(fn (string $text) => ['text' => $request['target_lang'].':'.$text], $request['text']),
        ]),
    ]);
}

function germany(): Country
{
    $continent = Continent::create(['code' => 'EU', 'name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);

    return Country::create(['iso_code' => 'DE', 'iso3_code' => 'DEU', 'continent_id' => $continent->id, 'name_translations' => ['de' => 'Deutschland', 'en' => 'Germany']]);
}

it('ergänzt fehlende Namen in allen konfigurierten Sprachen und lässt vorhandene stehen', function () {
    config(['app.event_languages' => 'de,en,nl']);
    $country = germany();
    $region = Region::create(['country_id' => $country->id, 'name_translations' => ['de' => 'Bayern', 'en' => 'Bavaria']]);
    $city = City::create(['country_id' => $country->id, 'name_translations' => ['de' => 'München', 'en' => 'Munich', 'nl' => 'München (gepflegt)']]);
    $type = EventType::create(['code' => 'strike', 'name' => 'Streik', 'is_active' => true, 'sort_order' => 1]);
    fakeNameDeepL();

    $this->artisan('names:translate')->assertSuccessful();

    expect($country->refresh()->name_translations['nl'])->toBe('Duitsland')
        ->and($country->continent->refresh()->name_translations['nl'])->toBe('NL:Europe')
        ->and($region->refresh()->name_translations['nl'])->toBe('NL:Bavaria')
        ->and($city->refresh()->name_translations['nl'])->toBe('München (gepflegt)')
        ->and($type->refresh()->name_translations)->toBe(['en' => 'EN-GB:Streik', 'nl' => 'NL:Streik']);
    Http::assertSent(fn (Request $request) => in_array('Bavaria', $request['text'], true)
        && $request['source_lang'] === 'EN'
        && str_contains($request['context'], 'in Germany'));
});

it('übersetzt eine neu konfigurierte Sprache ohne Codeänderung', function () {
    config(['app.event_languages' => 'de,en,fr']);
    $country = germany();
    fakeNameDeepL();

    $this->artisan('names:translate --type=countries --type=continents')->assertSuccessful();

    expect($country->refresh()->name_translations['fr'])->toBe('Allemagne')
        ->and($country->continent->refresh()->name_translations['fr'])->toBe('FR:Europe');
});

it('schickt im Probelauf nichts an DeepL und speichert nichts', function () {
    $country = germany();
    Region::create(['country_id' => $country->id, 'name_translations' => ['de' => 'Bayern', 'en' => 'Bavaria']]);
    fakeNameDeepL();

    $this->artisan('names:translate --dry-run')
        ->expectsOutputToContain('regions      nl: 1 per DeepL (Probelauf)')
        ->assertSuccessful();

    Http::assertNothingSent();
    expect($country->refresh()->name_translations)->not->toHaveKey('nl');
});
