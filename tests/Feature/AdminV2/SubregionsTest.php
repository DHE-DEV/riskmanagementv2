<?php

use App\Http\Controllers\Api\V1\CountryPlacesController;
use App\Jobs\SuggestRegionInfoJob;
use App\Livewire\AdminV2\MasterData\Cities\Editor as CityEditor;
use App\Livewire\AdminV2\MasterData\Sights\Editor as SightEditor;
use App\Livewire\AdminV2\MasterData\Sights\Index as SightIndex;
use App\Livewire\AdminV2\MasterData\Subregions\Editor as SubregionEditor;
use App\Livewire\AdminV2\MasterData\Subregions\Index as SubregionIndex;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Models\Sight;
use App\Models\Subregion;
use App\Models\User;
use App\Services\RegionInfoGenerator;
use App\Support\AdminV2\MasterData;
use App\Support\AdminV2\SightTransfer;
use App\Support\AdminV2\SubregionTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * Teilgebiete (Inseln, Inselgruppen, Reisegebiete): Pflege in AdminV2,
 * Zuordnung von Staedten und Sehenswuerdigkeiten, KI-Prompt, API und
 * Uebertragung per Migration.
 */
function canaries(): array
{
    $continent = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);
    $spain = Country::create(['name_translations' => ['de' => 'Spanien', 'en' => 'Spain'], 'iso_code' => 'ES', 'iso3_code' => 'ESP', 'continent_id' => $continent->id, 'timezone' => 'Europe/Madrid']);
    $region = Region::create(['name_translations' => ['de' => 'Kanarische Inseln', 'en' => 'Canary Islands'], 'code' => 'ES-CN', 'country_id' => $spain->id,
        'info' => ['texts' => ['short_description' => ['de' => 'Inseln im Atlantik.']]]]);

    return [$spain, $region];
}

function subregion(Region $region, string $de, string $en, string $kind = 'island', array $attributes = []): Subregion
{
    return Subregion::create(array_merge([
        'region_id' => $region->id,
        'country_id' => $region->country_id,
        'kind' => $kind,
        'name_translations' => ['de' => $de, 'en' => $en],
    ], $attributes));
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true, 'name' => 'Anna Admin']));
});

// ── Pflege ───────────────────────────────────────────────────────────────

it('zeigt den Bereich in den Stammdaten, die Liste mit Filtern und die Teilgebiete am Regionen-Editor', function () {
    [$spain, $region] = canaries();
    $tenerife = subregion($region, 'Teneriffa', 'Tenerife', 'island', ['sort_order' => 1, 'info' => ['texts' => ['short_description' => ['de' => 'Vulkaninsel.']], 'meta' => ['ai_generated_at' => '2026-10-11T10:00:00+00:00']]]);
    subregion($region, 'Costa Adeje', 'Costa Adeje', 'area', ['sort_order' => 2]);

    expect(MasterData::sections())->toHaveKey('subregions');

    $this->get('/adminv2/master-data/subregions')->assertOk()->assertSee('Teneriffa')->assertSee('Kanarische Inseln · Spanien');

    $names = fn (array $set) => collect(tap(Livewire::test(SubregionIndex::class), fn ($component) => collect($set)->each(fn ($value, $key) => $component->set($key, $value)))->instance()->rows->items())->map(fn ($subregion) => $subregion->getName('de'))->values()->all();

    expect($names([]))->toBe(['Teneriffa', 'Costa Adeje'])
        ->and($names(['kind' => 'area']))->toBe(['Costa Adeje'])
        ->and($names(['status' => 'ai']))->toBe(['Teneriffa'])
        ->and($names(['status' => 'empty']))->toBe(['Costa Adeje'])
        ->and($names(['search' => 'tene']))->toBe(['Teneriffa']);

    $this->get('/adminv2/master-data/regions/'.$region->id)->assertOk()->assertSee('Teilgebiete')->assertSee('Teneriffa')->assertSee('Neues Teilgebiet');
    $this->get('/adminv2/master-data/subregions/'.$tenerife->id)->assertOk()->assertSee('Insel in Kanarische Inseln, Spanien')->assertSee('Vulkaninsel.');
});

it('legt ein Teilgebiet an und speichert Infos und Reihenfolge', function () {
    [$spain, $region] = canaries();
    subregion($region, 'Teneriffa', 'Tenerife', 'island', ['sort_order' => 4]);

    Livewire::withQueryParams(['region' => $region->id])->test(SubregionEditor::class)
        ->assertSet('regionId', (string) $region->id)
        ->set('names.de', ' La Gomera ')
        ->set('names.en', 'La Gomera')
        ->set('kind', 'island')
        ->set('info.texts.short_description.de', 'Grüne Wanderinsel.')
        ->set('info.population', '22.000')
        ->set('info.timezone', 'Europe/Madrid')
        ->set('lat', '28.1')
        ->set('lng', '-17.2')
        ->call('save')
        ->assertHasNoErrors();

    $gomera = Subregion::where('name_translations->de', 'La Gomera')->firstOrFail();
    expect($gomera->country_id)->toBe($spain->id)
        ->and($gomera->sort_order)->toBe(5)
        ->and($gomera->info['texts']['short_description']['de'])->toBe('Grüne Wanderinsel.')
        ->and($gomera->info['population'])->toBe(22000)
        // gleiche Zeitzone wie das Land wird nicht gespeichert
        ->and($gomera->info)->not->toHaveKey('timezone');

    Livewire::test(SubregionEditor::class)->set('regionId', '')->set('names.de', '')->call('save')->assertHasErrors(['names.de', 'regionId']);
});

it('ordnet Staedte und Sehenswuerdigkeiten einem Teilgebiet zu und filtert danach', function () {
    [$spain, $region] = canaries();
    $tenerife = subregion($region, 'Teneriffa', 'Tenerife');
    $laguna = City::create(['name_translations' => ['de' => 'La Laguna'], 'country_id' => $spain->id, 'region_id' => $region->id]);

    Livewire::test(CityEditor::class, ['city' => $laguna->id])
        ->assertSet('subregionId', '')
        ->set('subregionId', (string) $tenerife->id)
        ->call('save')->assertHasNoErrors();
    expect($laguna->fresh()->subregion_id)->toBe($tenerife->id);

    // Die Stadt bringt ihr Teilgebiet mit.
    Livewire::test(SightEditor::class)
        ->set('names.de', 'Altstadt von La Laguna')
        ->set('countryId', (string) $spain->id)
        ->set('cityId', (string) $laguna->id)
        ->assertSet('regionId', (string) $region->id)
        ->assertSet('subregionId', (string) $tenerife->id)
        ->set('category', 'old_town')
        ->call('save')->assertHasNoErrors();

    $sight = Sight::where('name_translations->de', 'Altstadt von La Laguna')->firstOrFail();
    expect($sight->subregion_id)->toBe($tenerife->id);
    Sight::create(['name_translations' => ['de' => 'Strand'], 'country_id' => $spain->id, 'region_id' => $region->id, 'category' => 'beach']);

    $names = fn (string $subregion) => collect(Livewire::test(SightIndex::class)->set('countryIds', [(string) $spain->id])->set('region', (string) $region->id)->set('subregion', $subregion)->instance()->rows->items())->map(fn ($s) => $s->getName('de'))->all();
    expect($names((string) $tenerife->id))->toBe(['Altstadt von La Laguna'])
        ->and($names('none'))->toBe(['Strand']);

    $this->get('/adminv2/master-data/subregions/'.$tenerife->id)->assertOk()->assertSee('Altstadt von La Laguna')->assertSee('La Laguna');
});

it('fragt den KI-Vorschlag fuer ein Teilgebiet mit eigenem Prompt im Hintergrund an', function () {
    Queue::fake();
    [$spain, $region] = canaries();
    $tenerife = subregion($region, 'Teneriffa', 'Tenerife');
    subregion($region, 'Gran Canaria', 'Gran Canaria');
    subregion($region, 'Südküste', 'South coast', 'area');

    Livewire::test(SubregionEditor::class, ['subregion' => $tenerife->id])->call('startSuggestion');
    $run = RegionInfoRun::firstOrFail();
    expect($run->subregion_id)->toBe($tenerife->id)->and($run->region_id)->toBe($region->id);
    Queue::assertPushed(SuggestRegionInfoJob::class);

    $prompt = app(RegionInfoGenerator::class)->prompt($tenerife->fresh());
    expect($prompt)->toContain('Teilgebiet (Insel)')
        ->toContain('Insel: Teneriffa (englisch: Tenerife)')
        ->toContain('Gehört zur Region: Kanarische Inseln')
        ->toContain('Weitere Teilgebiete der Region: Gran Canaria, Südküste')
        ->toContain('population: Einwohnerzahl');
    expect(app(RegionInfoGenerator::class)->prompt(Subregion::where('kind', 'area')->first()))->toContain('bei einem Reisegebiet ohne feste Grenzen immer null');

    // Der Job fragt die KI zum Teilgebiet und legt das Ergebnis am Lauf ab.
    config(['services.openai.key' => 'test-key']);
    Http::fake(['api.openai.com/*' => Http::response(['model' => 'gpt-test', 'choices' => [['message' => ['content' => json_encode(['texts' => ['short_description' => ['de' => 'Insel mit dem Teide.']], 'population' => 931646])]]]])]);
    (new SuggestRegionInfoJob($run->id))->handle(app(RegionInfoGenerator::class));
    expect($run->fresh()->status)->toBe(RegionInfoRun::STATUS_DONE)
        ->and($run->fresh()->result['texts']['short_description']['de'])->toBe('Insel mit dem Teide.');
    Http::assertSent(fn ($request) => str_contains((string) ($request->data()['messages'][0]['content'] ?? ''), 'Teneriffa'));
});

// ── API ──────────────────────────────────────────────────────────────────

it('liefert Region mit Infos und Teilgebieten, Teilgebiete einzeln und die Zuordnung an Staedten und Sehenswuerdigkeiten', function () {
    [$spain, $region] = canaries();
    $region->update(['info' => ['texts' => ['short_description' => ['de' => 'Inseln im Atlantik.', 'en' => 'Islands in the Atlantic.'], 'known_for' => ['de' => ['Vulkane', 'Strände'], 'en' => ['Volcanoes']]], 'best_months' => [3, 4], 'population' => 2200000, 'timezone' => 'Atlantic/Canary']]);
    $tenerife = subregion($region, 'Teneriffa', 'Tenerife', 'island', ['sort_order' => 1, 'lat' => 28.29, 'lng' => -16.62, 'info' => ['texts' => ['short_description' => ['de' => 'Die größte Insel.'], 'description' => ['de' => "Absatz eins.\n\nAbsatz zwei."]], 'area_km2' => 2034]]);
    subregion($region, 'Südküste', 'South coast', 'area', ['sort_order' => 2]);
    $city = City::create(['name_translations' => ['de' => 'Puerto de la Cruz'], 'country_id' => $spain->id, 'region_id' => $region->id, 'subregion_id' => $tenerife->id]);
    $teide = Sight::create(['name_translations' => ['de' => 'Teide'], 'country_id' => $spain->id, 'region_id' => $region->id, 'subregion_id' => $tenerife->id, 'category' => 'national_park']);
    Sight::create(['name_translations' => ['de' => 'Strand'], 'country_id' => $spain->id, 'region_id' => $region->id, 'category' => 'beach']);

    $controller = app(CountryPlacesController::class);
    $get = fn (string $method, array $query, ...$args) => $controller->{$method}(Request::create('/', 'GET', $query), ...$args)->getData(true);

    expect($get('regions', ['lang' => 'de'], 'ES')['data'][0]['subregions_count'])->toBe(2);

    $detail = $get('region', ['lang' => 'en'], $region->id)['data'];
    expect($detail['country'])->toBe(['code' => 'ES', 'name' => 'Spain'])
        ->and($detail['info'])->toMatchArray(['short_description' => 'Islands in the Atlantic.', 'known_for' => ['Volcanoes'], 'best_months' => [3, 4], 'population' => 2200000, 'timezone' => 'Atlantic/Canary', 'climate' => null])
        ->and(array_column($detail['subregions'], 'name'))->toBe(['Tenerife', 'South coast'])
        ->and($detail['subregions'][0])->toMatchArray(['kind' => 'island', 'kind_name' => 'Island', 'group_name' => 'Islands', 'cities_count' => 1, 'sights_count' => 1, 'coordinates' => ['lat' => 28.29, 'lng' => -16.62]])
        ->and($detail['subregions'][1]['group_name'])->toBe('Travel areas');

    $one = $get('subregion', ['lang' => 'de'], $tenerife->id)['data'];
    expect($one)->toMatchArray(['name' => 'Teneriffa', 'kind_name' => 'Insel', 'group_name' => 'Inseln', 'description' => 'Die größte Insel.', 'region' => ['id' => $region->id, 'code' => 'ES-CN', 'name' => 'Kanarische Inseln'], 'country' => ['code' => 'ES', 'name' => 'Spanien']])
        ->and($one['info'])->toMatchArray(['description' => "Absatz eins.\n\nAbsatz zwei.", 'area_km2' => 2034, 'population' => null]);

    expect(array_column($get('subregions', ['region' => $region->id], 'ES')['data'], 'id'))->toHaveCount(2);

    $sights = $get('sights', ['lang' => 'de', 'subregion' => $tenerife->id], 'ES');
    expect(array_column($sights['data'], 'name'))->toBe(['Teide'])
        ->and($sights['data'][0]['subregion'])->toBe(['id' => $tenerife->id, 'name' => 'Teneriffa', 'kind' => 'island']);

    expect($get('cities', ['lang' => 'de', 'subregion' => $tenerife->id], 'ES')['data'][0]['subregion'])->toBe(['id' => $tenerife->id, 'name' => 'Teneriffa']);

    $places = $get('places', ['keys' => 'subregion:'.$tenerife->id.',sight:'.$teide->id.',subregion:999', 'lang' => 'de']);
    expect($places['meta']['total'])->toBe(2)
        ->and($places['data'][0])->toMatchArray(['key' => 'subregion:'.$tenerife->id, 'kind' => 'subregion', 'name' => 'Teneriffa', 'region' => ['id' => $region->id, 'name' => 'Kanarische Inseln'], 'coordinates' => ['lat' => 28.29, 'lng' => -16.62]]);

    expect($controller->region(Request::create('/'), 999999)->getStatusCode())->toBe(404)
        ->and($controller->subregion(Request::create('/'), 999999)->getStatusCode())->toBe(404);
});

// ── Uebertragung per Migration ───────────────────────────────────────────

it('gibt Teilgebiete samt Zuordnungen aus und legt sie auf einem anderen System an', function () {
    [$spain, $region] = canaries();
    $tenerife = subregion($region, 'Teneriffa', 'Tenerife', 'island', ['sort_order' => 1, 'info' => ['texts' => ['short_description' => ['de' => 'Die größte Insel.']]]]);
    City::create(['name_translations' => ['de' => 'La Laguna'], 'country_id' => $spain->id, 'region_id' => $region->id, 'subregion_id' => $tenerife->id]);
    Sight::create(['name_translations' => ['de' => 'Teide', 'en' => 'Mount Teide'], 'country_id' => $spain->id, 'region_id' => $region->id, 'subregion_id' => $tenerife->id, 'category' => 'national_park']);
    Sight::create(['name_translations' => ['de' => 'Anaga'], 'country_id' => $spain->id, 'region_id' => $region->id, 'subregion_id' => $tenerife->id, 'category' => 'nature', 'created_at' => now()->addMinute()]);

    $data = SubregionTransfer::export(['es']);
    $newSights = SightTransfer::export(['es'], fn (Sight $sight) => $sight->getName('de') === 'Anaga');
    expect($data['ES']['Kanarische Inseln'][0])->toMatchArray(['kind' => 'island', 'cities' => ['La Laguna'], 'sights' => [['de' => 'Anaga'], ['de' => 'Teide', 'en' => 'Mount Teide']]])
        ->and($newSights['ES']['Kanarische Inseln'][0]['subregion'])->toBe('Teneriffa');

    // "Anderes System": keine Teilgebiete, Anaga fehlt noch, der Teide heisst dort auf Deutsch anders.
    Sight::query()->forceDelete();
    Subregion::query()->forceDelete();
    City::query()->update(['subregion_id' => null]);
    $teide = Sight::create(['name_translations' => ['de' => 'Pico del Teide', 'en' => 'Mount Teide'], 'country_id' => $spain->id, 'region_id' => $region->id, 'category' => 'national_park']);

    expect(SubregionTransfer::import($data + ['XX' => ['Irgendwo' => []]]))->toMatchArray(['created' => 1, 'cities' => 1, 'sights' => 1, 'missing' => ['XX']])
        ->and(SightTransfer::import($newSights)['created'])->toBe(1);

    $tenerife = Subregion::firstOrFail();
    expect($teide->fresh()->subregion_id)->toBe($tenerife->id)
        ->and(Sight::where('name_translations->de', 'Anaga')->first()->subregion_id)->toBe($tenerife->id)
        ->and(City::first()->subregion_id)->toBe($tenerife->id)
        // ein zweiter Lauf legt nichts doppelt an
        ->and(SubregionTransfer::import($data)['created'])->toBe(0);
});
