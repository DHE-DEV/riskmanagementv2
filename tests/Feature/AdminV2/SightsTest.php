<?php

use App\Livewire\AdminV2\MasterData\Regions\Editor as RegionEditor;
use App\Livewire\AdminV2\MasterData\Regions\Index as RegionIndex;
use App\Livewire\AdminV2\MasterData\Sights\Editor as SightEditor;
use App\Livewire\AdminV2\MasterData\Sights\Index as SightIndex;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Models\Sight;
use App\Models\User;
use App\Support\AdminV2\MasterData;
use App\Support\AdminV2\RegionInfoFillRun;
use App\Support\AdminV2\SightInfo;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Sehenswuerdigkeiten: Pflege in AdminV2, KI-Vorschlaege je Region (mit
 * OSM-Abgleich und ohne Dubletten), Befehl und Kunden-API.
 */
function sightCountry(string $iso = 'IT', array $attributes = []): Country
{
    $continent = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);

    return Country::create(array_merge([
        'name_translations' => ['de' => 'Italien', 'en' => 'Italy'],
        'iso_code' => $iso,
        'iso3_code' => $iso.'X',
        'continent_id' => $continent->id,
    ], $attributes));
}

function sightRegion(Country $country, string $name = 'Ligurien', string $english = 'Liguria'): Region
{
    return Region::create(['name_translations' => ['de' => $name, 'en' => $english], 'code' => 'IT-'.substr(md5($name), 0, 2), 'country_id' => $country->id]);
}

function sightCity(Country $country, ?Region $region, string $name, ?string $english = null): City
{
    return City::create(['name_translations' => ['de' => $name, 'en' => $english ?? $name], 'country_id' => $country->id, 'region_id' => $region?->id]);
}

function sight(Country $country, string $name, array $attributes = []): Sight
{
    return Sight::create(array_merge(['name_translations' => ['de' => $name, 'en' => $name], 'country_id' => $country->id, 'category' => 'landmark'], $attributes));
}

/** Eine Antwort der KI mit Sehenswuerdigkeiten. */
function sightAnswer(): array
{
    $text = fn (string $de) => ['de' => $de, 'en' => $de.' (en)', 'nl' => $de.' (nl)'];

    return ['sights' => [
        ['name' => ['de' => 'Aquarium Genua', 'en' => 'Aquarium of Genoa', 'nl' => 'Aquarium van Genua'], 'category' => 'theme_park', 'is_highlight' => true, 'city' => 'Genoa', 'address' => 'Porto Antico', 'lat' => 44.4101, 'lng' => 8.9263, 'website' => 'https://www.acquariodigenova.it/', 'visit_minutes' => 180,
            'texts' => ['short_description' => $text('Großes Aquarium im alten Hafen.'), 'description' => $text('Eröffnet 1992.')]],
        ['name' => ['de' => 'Nationalpark Cinque Terre', 'en' => 'Cinque Terre National Park', 'nl' => 'Nationaal Park Cinque Terre'], 'category' => 'national_park', 'is_highlight' => true, 'city' => null, 'lat' => 44.12, 'lng' => 9.73, 'website' => 'keine', 'visit_minutes' => 480,
            'texts' => ['short_description' => $text('Fünf Dörfer an der Steilküste.'), 'description' => $text('UNESCO-Welterbe.')]],
        ['name' => ['de' => 'Laterne von Genua', 'en' => 'Lanterna of Genoa', 'nl' => 'Lanterna'], 'category' => 'erfunden', 'is_highlight' => false, 'city' => 'Genua', 'lat' => 44.404, 'lng' => 8.904,
            'texts' => ['short_description' => $text('Leuchtturm.'), 'description' => $text('Wahrzeichen der Stadt.')]],
        ['name' => 'nur ein Text ohne Sprachen', 'category' => 'other'],
        ['name' => ['en' => 'Kein deutscher Name'], 'category' => 'other'],
    ]];
}

/**
 * OpenAI und Nominatim faken. Nominatim findet das Aquarium nahe am KI-Punkt,
 * den Nationalpark weit weg (der Punkt der KI bleibt) und die Laterne gar nicht.
 */
function fakeSightServices(mixed $answer = null): void
{
    config(['services.openai.key' => 'test-key', 'services.nominatim.enabled' => true, 'services.nominatim.throttle' => 0]);

    Http::fake([
        'api.openai.com/*' => function ($request) use ($answer) {
            $data = $answer instanceof Closure ? $answer((string) ($request->data()['messages'][0]['content'] ?? '')) : ($answer ?? sightAnswer());

            return Http::response(['model' => 'gpt-test', 'choices' => [['message' => ['content' => is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE)]]], 'usage' => ['total_tokens' => 50]]);
        },
        'nominatim.openstreetmap.org/*' => function ($request) {
            $q = (string) ($request->data()['q'] ?? '');

            return Http::response(match (true) {
                str_contains($q, 'Aquarium') => [['lat' => '44.410145', 'lon' => '8.926291']],
                str_contains($q, 'Cinque Terre') => [['lat' => '41.9', 'lon' => '12.5']],
                default => [],
            });
        },
    ]);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true, 'name' => 'Anna Admin']));
});

// ── Pflege ───────────────────────────────────────────────────────────────

it('zeigt den Bereich in den Stammdaten und die Liste mit Filtern', function () {
    $italy = sightCountry();
    $liguria = sightRegion($italy);
    $genoa = sightCity($italy, $liguria, 'Genua', 'Genoa');
    sight($italy, 'Aquarium Genua', ['region_id' => $liguria->id, 'city_id' => $genoa->id, 'category' => 'theme_park', 'is_highlight' => true, 'lat' => 44.41, 'lng' => 8.93, 'info' => ['meta' => ['ai_generated_at' => '2026-10-10T10:00:00+00:00']]]);
    sight($italy, 'Cinque Terre', ['region_id' => $liguria->id, 'category' => 'national_park', 'info' => ['meta' => ['ai_generated_at' => '2026-10-10T10:00:00+00:00', 'reviewed_at' => '2026-10-10T11:00:00+00:00']]]);
    sight($italy, 'Kolosseum', ['category' => 'archaeological']);

    expect(MasterData::sections())->toHaveKey('sights');

    $this->get('/adminv2/master-data/sights')->assertOk()->assertSee('Aquarium Genua')->assertSee('Freizeitpark &amp; Zoo', false)->assertSee('Genua · Ligurien · Italien');

    $names = fn (array $set) => collect(tap(Livewire::test(SightIndex::class), fn ($component) => collect($set)->each(fn ($value, $key) => $component->set($key, $value)))->instance()->rows->items())->map(fn ($sight) => $sight->getName('de'))->sort()->values()->all();

    expect($names(['category' => 'national_park']))->toBe(['Cinque Terre'])
        ->and($names(['highlight' => 'yes']))->toBe(['Aquarium Genua'])
        ->and($names(['status' => SightInfo::STATUS_AI]))->toBe(['Aquarium Genua'])
        ->and($names(['status' => SightInfo::STATUS_REVIEWED]))->toBe(['Cinque Terre'])
        ->and($names(['status' => SightInfo::STATUS_MANUAL]))->toBe(['Kolosseum'])
        ->and($names(['coordinates' => 'missing']))->toBe(['Cinque Terre', 'Kolosseum'])
        ->and($names(['countryIds' => [(string) $italy->id], 'region' => (string) $liguria->id]))->toBe(['Aquarium Genua', 'Cinque Terre'])
        ->and($names(['countryIds' => [(string) $italy->id], 'region' => 'none']))->toBe(['Kolosseum'])
        ->and($names(['search' => 'kolos']))->toBe(['Kolosseum']);
});

it('legt eine Sehenswuerdigkeit an und uebernimmt die Region der Stadt', function () {
    $italy = sightCountry();
    $liguria = sightRegion($italy);
    $genoa = sightCity($italy, $liguria, 'Genua');

    Livewire::test(SightEditor::class)
        ->set('names.de', 'Laterne')
        ->set('names.en', 'Lanterna')
        ->set('countryId', (string) $italy->id)
        ->set('cityId', (string) $genoa->id)
        ->assertSet('regionId', (string) $liguria->id)
        ->set('category', 'landmark')
        ->set('isHighlight', true)
        ->set('websiteUrl', 'https://www.lanternadigenova.it')
        ->set('info.texts.short_description.de', 'Leuchtturm von Genua.')
        ->set('info.visit_minutes', '60')
        ->set('lat', '44.404')
        ->set('lng', '8.904')
        ->call('save')
        ->assertHasNoErrors();

    $sight = Sight::firstWhere('country_id', $italy->id);
    expect($sight->name_translations)->toBe(['de' => 'Laterne', 'en' => 'Lanterna'])
        ->and($sight->region_id)->toBe($liguria->id)
        ->and($sight->city_id)->toBe($genoa->id)
        ->and($sight->is_highlight)->toBeTrue()
        ->and($sight->info)->toBe(['texts' => ['short_description' => ['de' => 'Leuchtturm von Genua.']], 'visit_minutes' => 60])
        ->and((float) $sight->lat)->toBe(44.404)
        ->and(SightInfo::status($sight->info))->toBe(SightInfo::STATUS_MANUAL);

    $this->get(route('adminv2.master-data.sights.edit', $sight))->assertOk()->assertSee('Laterne')->assertSee('Von Hand gepflegt')->assertSee('In OpenStreetMap ansehen');
});

it('prueft Pflichtfelder, Stadt zum Land und Adressen', function () {
    $italy = sightCountry();
    $spain = sightCountry('ES', ['name_translations' => ['de' => 'Spanien', 'en' => 'Spain']]);
    $madrid = sightCity($spain, null, 'Madrid');

    Livewire::test(SightEditor::class)
        ->set('countryId', (string) $italy->id)
        ->set('cityId', (string) $madrid->id)
        ->set('websiteUrl', 'keine Adresse')
        ->call('save')
        ->assertHasErrors(['names.de' => 'required', 'cityId' => 'in', 'websiteUrl' => 'url']);

    expect(Sight::count())->toBe(0);
});

it('markiert einen KI-Entwurf als geprueft', function () {
    $italy = sightCountry();
    $sight = sight($italy, 'Aquarium', ['info' => ['texts' => ['tips' => ['de' => 'Früh kommen.']], 'meta' => ['ai_generated_at' => '2026-10-10T10:00:00+00:00', 'ai_model' => 'gpt-test', 'geocoded' => 'ai']]]);

    Livewire::test(SightEditor::class, ['sight' => $sight->id])
        ->assertSee('KI-Entwurf, ungeprüft')
        ->assertSee('nur von der KI – bitte prüfen')
        ->call('markReviewed')
        ->call('save');

    $meta = $sight->refresh()->info['meta'];
    expect($meta['reviewed_by'])->toBe('Anna Admin')
        ->and($meta['geocoded'])->toBe('ai')
        ->and(SightInfo::status($sight->info))->toBe(SightInfo::STATUS_REVIEWED);
});

it('verhindert das endgueltige Loeschen einer Region mit Sehenswuerdigkeiten', function () {
    $italy = sightCountry();
    $liguria = sightRegion($italy);
    sight($italy, 'Aquarium', ['region_id' => $liguria->id]);

    expect(MasterData::dependents($liguria))->toBe(['Sehenswürdigkeiten' => 1]);
});

// ── KI ───────────────────────────────────────────────────────────────────

it('legt aus dem Regionen-Editor Sehenswuerdigkeiten per KI an – mit OSM-Abgleich und ohne Dubletten', function () {
    fakeSightServices();
    $italy = sightCountry();
    $liguria = sightRegion($italy);
    $genoa = sightCity($italy, $liguria, 'Genua', 'Genoa');
    sight($italy, 'Laterne von Genua', ['region_id' => $liguria->id]);

    Livewire::test(RegionEditor::class, ['region' => $liguria->id])
        ->assertSee('Sehenswürdigkeiten mit KI vorschlagen')
        ->call('startSightsSuggestion')
        ->assertSet('sightsRunId', null)
        ->assertDispatched('adminv2-toast', message: '3 Sehenswürdigkeiten als KI-Entwurf angelegt.')
        ->assertSee('Aquarium Genua');

    $aquarium = Sight::get()->first(fn ($sight) => $sight->getName('de') === 'Aquarium Genua');
    $park = Sight::get()->first(fn ($sight) => $sight->getName('de') === 'Nationalpark Cinque Terre');

    expect(Sight::count())->toBe(4)
        ->and(Sight::get()->contains(fn ($sight) => $sight->name_translations === ['de' => 'nur ein Text ohne Sprachen'] && $sight->category === 'other'))->toBeTrue()
        ->and($aquarium->city_id)->toBe($genoa->id)
        ->and($aquarium->is_highlight)->toBeTrue()
        ->and((float) $aquarium->lat)->toBe(44.410145)
        ->and($aquarium->info['meta']['geocoded'])->toBe('osm')
        ->and($aquarium->info['visit_minutes'])->toBe(180)
        ->and($aquarium->info['texts']['short_description']['nl'])->toBe('Großes Aquarium im alten Hafen. (nl)')
        ->and($aquarium->website_url)->toBe('https://www.acquariodigenova.it/')
        ->and($park->city_id)->toBeNull()
        ->and($park->region_id)->toBe($liguria->id)
        ->and((float) $park->lat)->toBe(44.12)
        ->and($park->info['meta']['geocoded'])->toBe('ai')
        ->and($park->website_url)->toBeNull()
        ->and(SightInfo::status($park->info))->toBe(SightInfo::STATUS_AI)
        ->and(RegionInfoRun::count())->toBe(0);

    Http::assertSent(fn ($request) => str_contains((string) ($request->data()['messages'][0]['content'] ?? ''), 'Bereits erfasst (nicht noch einmal vorschlagen): Laterne von Genua'));
});

it('fuellt die gefilterten Regionen ohne Sehenswuerdigkeiten aus der Liste', function () {
    fakeSightServices(fn (string $prompt) => str_contains($prompt, 'Region: Kaputt') ? 'kein json' : sightAnswer());
    $italy = sightCountry();
    $liguria = sightRegion($italy);
    $broken = sightRegion($italy, 'Kaputt', 'Broken');
    $done = sightRegion($italy, 'Fertig', 'Done');
    sight($italy, 'Schon da', ['region_id' => $done->id]);

    Livewire::test(RegionIndex::class)
        ->set('countryIds', [(string) $italy->id])
        ->set('fillKind', 'sights')
        ->assertSet('fillCount', 2)
        ->call('startFill')
        ->assertSee('KI-Sehenswürdigkeiten abgeschlossen')
        ->assertSee('4 Sehenswürdigkeiten angelegt')
        ->assertSee('1 fehlgeschlagen');

    $run = RegionInfoFillRun::current(RegionInfoRun::KIND_SIGHTS);
    expect($run->done)->toBe(1)
        ->and($run->failed)->toHaveKey((string) $broken->id)
        ->and(Sight::where('region_id', $liguria->id)->count())->toBe(4)
        ->and(Sight::where('region_id', $done->id)->count())->toBe(1)
        ->and(RegionInfoFillRun::current())->toBeNull();
});

it('legt Sehenswuerdigkeiten per Befehl an', function () {
    fakeSightServices();
    $italy = sightCountry();
    $spain = sightCountry('ES', ['name_translations' => ['de' => 'Spanien', 'en' => 'Spain']]);
    $liguria = sightRegion($italy);
    sightRegion($spain, 'Katalonien', 'Catalonia');

    $this->artisan('sights:fill', ['--country' => ['it'], '--dry-run' => true])->expectsOutputToContain('1 Regionen (nur gezählt)')->assertSuccessful();
    $this->artisan('sights:fill', ['--country' => ['IT']])->expectsOutputToContain('1 Regionen bearbeitet, 4 Sehenswürdigkeiten angelegt, 0 fehlgeschlagen')->assertSuccessful();

    expect(Sight::where('region_id', $liguria->id)->count())->toBe(4)
        ->and(Sight::where('country_id', $spain->id)->count())->toBe(0);

    // Ein zweiter Lauf laesst Regionen mit Sehenswuerdigkeiten aus.
    $this->artisan('sights:fill', ['--country' => ['IT']])->expectsOutputToContain('0 Regionen')->assertSuccessful();
});

// ── API ──────────────────────────────────────────────────────────────────

it('liefert die Sehenswuerdigkeiten eines Landes und einzeln ueber die API', function () {
    $italy = sightCountry();
    $liguria = sightRegion($italy);
    $genoa = sightCity($italy, $liguria, 'Genua', 'Genoa');
    $aquarium = sight($italy, 'Aquarium Genua', ['name_translations' => ['de' => 'Aquarium Genua', 'en' => 'Aquarium of Genoa'], 'region_id' => $liguria->id, 'city_id' => $genoa->id, 'category' => 'theme_park', 'is_highlight' => true, 'lat' => 44.41, 'lng' => 8.93, 'info' => ['texts' => ['short_description' => ['de' => 'Im Hafen.', 'en' => 'In the harbour.']], 'visit_minutes' => 180, 'meta' => ['ai_generated_at' => '2026-10-10T10:00:00+00:00']]]);
    sight($italy, 'Altstadt', ['region_id' => $liguria->id, 'category' => 'old_town']);
    sight($italy, 'Kolosseum', ['category' => 'archaeological']);

    $controller = app(\App\Http\Controllers\Api\V1\CountryPlacesController::class);
    $list = fn (array $query) => $controller->sights(\Illuminate\Http\Request::create('/', 'GET', $query), 'IT')->getData(true);

    $all = $list(['lang' => 'en']);
    expect($all['meta']['total'])->toBe(3)
        ->and(array_column($all['data'], 'name'))->toBe(['Aquarium of Genoa', 'Altstadt', 'Kolosseum'])
        ->and($all['data'][0])->toMatchArray([
            'category' => 'theme_park',
            'category_name' => 'Theme park & zoo',
            'is_highlight' => true,
            'city' => ['id' => $genoa->id, 'name' => 'Genoa'],
            'coordinates' => ['lat' => 44.41, 'lng' => 8.93],
            'visit_minutes' => 180,
            'short_description' => 'In the harbour.',
            'opening_hours' => null,
            'is_reviewed' => false,
        ]);

    expect(array_column($list(['region' => $liguria->id, 'lang' => 'de'])['data'], 'name'))->toBe(['Aquarium Genua', 'Altstadt'])
        ->and($list(['category' => 'archaeological'])['meta']['total'])->toBe(1)
        ->and($list(['highlight' => '1'])['meta']['total'])->toBe(1)
        ->and($list([])['data'][0]['short_description'])->toBe(['de' => 'Im Hafen.', 'en' => 'In the harbour.']);

    $one = $controller->sight(\Illuminate\Http\Request::create('/', 'GET', ['lang' => 'de']), $aquarium->id)->getData(true);
    expect($one['data']['country'])->toBe(['code' => 'IT', 'name' => 'Italien'])
        ->and($controller->sight(\Illuminate\Http\Request::create('/', 'GET'), 999999)->getStatusCode())->toBe(404)
        ->and($controller->sights(\Illuminate\Http\Request::create('/', 'GET'), 'XX')->getStatusCode())->toBe(404);
});

// ── Export und Import (Migration) ─────────────────────────────────────────

it('gibt Sehenswuerdigkeiten als Daten aus und legt sie auf einem anderen System wieder an', function () {
    $italy = sightCountry();
    $liguria = sightRegion($italy);
    $genoa = sightCity($italy, $liguria, 'Genua', 'Genoa');
    sight($italy, 'Aquarium Genua', ['region_id' => $liguria->id, 'city_id' => $genoa->id, 'category' => 'theme_park', 'is_highlight' => true, 'sort_order' => 1, 'lat' => 44.41, 'lng' => 8.93, 'info' => ['texts' => ['short_description' => ['de' => 'Im Hafen.']], 'meta' => ['ai_generated_at' => '2026-10-10T10:00:00+00:00']]]);
    sight($italy, 'Cinque Terre', ['region_id' => $liguria->id, 'category' => 'national_park', 'sort_order' => 2]);

    $data = \App\Support\AdminV2\SightTransfer::export(['it']);
    expect($data['IT']['Ligurien'])->toHaveCount(2)
        ->and($data['IT']['Ligurien'][0]['city'])->toBe('Genua');

    // "Anderes System": gleiche Namen, andere IDs; ein Eintrag existiert schon, eine Region fehlt.
    Sight::query()->forceDelete();
    $liguria->delete();
    $otherLiguria = sightRegion($italy);
    $otherGenoa = sightCity($italy, $otherLiguria, 'Genua', 'Genoa');
    sight($italy, 'Cinque Terre', ['region_id' => $otherLiguria->id]);
    $data['IT']['Atlantis'] = [['name_translations' => ['de' => 'Versunkene Stadt'], 'category' => 'other']];

    $result = \App\Support\AdminV2\SightTransfer::import($data);

    expect($result)->toBe(['created' => 1, 'skipped' => 2, 'missing_regions' => ['IT: Atlantis']]);

    $aquarium = Sight::get()->first(fn ($sight) => $sight->getName('de') === 'Aquarium Genua');
    expect($aquarium->region_id)->toBe($otherLiguria->id)
        ->and($aquarium->city_id)->toBe($otherGenoa->id)
        ->and($aquarium->is_highlight)->toBeTrue()
        ->and(SightInfo::status($aquarium->info))->toBe(SightInfo::STATUS_AI);

    // Ein zweiter Import legt nichts doppelt an.
    expect(\App\Support\AdminV2\SightTransfer::import($data)['created'])->toBe(0);
});

it('loest gemerkte Orte zu Name, Land und Lage auf', function () {
    $italy = sightCountry();
    $liguria = sightRegion($italy);
    $genoa = sightCity($italy, $liguria, 'Genua', 'Genoa');
    $genoa->update(['lat' => 44.4, 'lng' => 8.9]);
    $aquarium = sight($italy, 'Aquarium Genua', ['name_translations' => ['de' => 'Aquarium Genua', 'en' => 'Aquarium of Genoa'], 'region_id' => $liguria->id, 'city_id' => $genoa->id, 'category' => 'theme_park', 'lat' => 44.41, 'lng' => 8.93]);

    $controller = app(\App\Http\Controllers\Api\V1\CountryPlacesController::class);
    $data = $controller->places(\Illuminate\Http\Request::create('/', 'GET', ['keys' => "region:{$liguria->id}, city:{$genoa->id},sight:{$aquarium->id},sight:999999,quatsch,country:IT", 'lang' => 'en']))->getData(true);

    expect($data['meta']['total'])->toBe(3)
        ->and(collect($data['data'])->pluck('key')->sort()->values()->all())->toBe(["city:{$genoa->id}", "region:{$liguria->id}", "sight:{$aquarium->id}"]);

    $sight = collect($data['data'])->firstWhere('kind', 'sight');
    expect($sight)->toMatchArray([
        'name' => 'Aquarium of Genoa',
        'country' => ['code' => 'IT', 'name' => 'Italy'],
        'region' => ['id' => $liguria->id, 'name' => 'Liguria'],
        'city' => ['id' => $genoa->id, 'name' => 'Genoa'],
        'coordinates' => ['lat' => 44.41, 'lng' => 8.93],
        'category' => 'theme_park',
        'category_name' => 'Theme park & zoo',
    ]);
});
