<?php

use App\Jobs\FillRegionInfoJob;
use App\Livewire\AdminV2\MasterData\Regions\Editor as RegionEditor;
use App\Livewire\AdminV2\MasterData\Regions\Index as RegionIndex;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Models\User;
use App\Services\RegionInfoGenerator;
use App\Support\AdminV2\RegionInfo;
use App\Support\AdminV2\RegionInfoFillRun;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Regionsinfos: Beschreibung, Reiseinfos und Fakten je Region (regions.info),
 * KI-Vorbefuellung im Editor und fuer viele Regionen, Filter in der Liste.
 */
function regionInfoCountry(string $iso = 'IT', array $attributes = []): Country
{
    $continent = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);

    return Country::create(array_merge([
        'name_translations' => ['de' => 'Italien', 'en' => 'Italy'],
        'iso_code' => $iso,
        'iso3_code' => $iso.'X',
        'continent_id' => $continent->id,
        'timezone' => 'Europe/Rome',
    ], $attributes));
}

function regionInfoRegion(Country $country, string $name = 'Venetien', array $attributes = []): Region
{
    return Region::create(array_merge([
        'name_translations' => ['de' => $name, 'en' => $name],
        'code' => 'IT-'.substr(md5($name), 0, 3),
        'country_id' => $country->id,
    ], $attributes));
}

/** Antwort der KI im Format, das der Prompt verlangt. */
function regionInfoAnswer(string $short = 'Venetien reicht von den Dolomiten bis zur Adria.', array $extra = []): array
{
    return array_replace_recursive([
        'texts' => [
            'short_description' => ['de' => $short, 'en' => 'Veneto stretches from the Dolomites to the Adriatic.', 'nl' => 'Veneto loopt van de Dolomieten tot de Adriatische Zee.'],
            'known_for' => ['de' => ['Lagune von Venedig', 'Dolomiten'], 'en' => ['Venetian Lagoon', 'Dolomites'], 'nl' => ['Lagune van Venetië']],
            'cuisine' => ['de' => 'Risotto, Polenta und Cicchetti.', 'en' => '', 'nl' => ''],
        ],
        'best_months' => [9, 5, 6, 13],
        'population' => 4851851,
        'area_km2' => 18345,
        'timezone' => 'Europe/Rome',
    ], $extra);
}

function fakeRegionAi(mixed $answer): void
{
    config(['services.openai.key' => 'test-key']);

    Http::fake(['api.openai.com/*' => function ($request) use ($answer) {
        $data = $answer instanceof Closure ? $answer((string) ($request->data()['messages'][0]['content'] ?? '')) : $answer;

        return Http::response([
            'model' => 'gpt-test',
            'choices' => [['message' => ['content' => is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE)]]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
        ]);
    }]);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true, 'name' => 'Anna Admin']));
});

// ── Speichern ────────────────────────────────────────────────────────────

it('speichert Regionsinfos ohne Leeres und ohne die Zeitzone des Landes zu doppeln', function () {
    $italy = regionInfoCountry();
    $veneto = regionInfoRegion($italy);

    Livewire::test(RegionEditor::class, ['region' => $veneto->id])
        ->set('info.texts.short_description.de', "  Venetien im Nordosten.\r\n\r\n\r\n\r\nMit Venedig.  ")
        ->set('info.texts.known_for.de', 'Lagune, Dolomiten, , Lagune')
        ->set('info.texts.getting_around.en', 'Vaporetti in Venice.')
        ->call('toggleBestMonth', 9)
        ->call('toggleBestMonth', 5)
        ->set('info.population', '4.851.851')
        ->set('info.timezone', 'Europe/Rome')
        ->call('save')
        ->assertHasNoErrors();

    $info = $veneto->refresh()->info;

    expect($info['texts']['short_description'])->toBe(['de' => "Venetien im Nordosten.\n\nMit Venedig."])
        ->and($info['texts']['known_for']['de'])->toBe(['Lagune', 'Dolomiten'])
        ->and($info['texts']['getting_around'])->toBe(['en' => 'Vaporetti in Venice.'])
        ->and($info['best_months'])->toBe([5, 9])
        ->and($info['population'])->toBe(4851851)
        ->and($info)->not->toHaveKey('timezone')
        ->and($info)->not->toHaveKey('area_km2')
        ->and(RegionInfo::status($info))->toBe(RegionInfo::STATUS_MANUAL);
});

it('speichert eine abweichende Zeitzone und lehnt unbekannte ab', function () {
    $usa = regionInfoCountry('US', ['name_translations' => ['de' => 'USA', 'en' => 'USA'], 'timezone' => 'America/New_York']);
    $california = regionInfoRegion($usa, 'Kalifornien');

    Livewire::test(RegionEditor::class, ['region' => $california->id])
        ->set('info.timezone', 'Mars/Olympus')
        ->call('save')
        ->assertHasErrors(['info.timezone' => 'in'])
        ->set('info.timezone', 'America/Los_Angeles')
        ->call('save')
        ->assertHasNoErrors();

    expect($california->refresh()->info['timezone'])->toBe('America/Los_Angeles');
});

it('loescht die Infos ganz, wenn alle Felder geleert werden', function () {
    $veneto = regionInfoRegion(regionInfoCountry(), 'Venetien', ['info' => ['texts' => ['cuisine' => ['de' => 'Risotto']], 'meta' => ['ai_generated_at' => now()->toIso8601String()]]]);

    Livewire::test(RegionEditor::class, ['region' => $veneto->id])
        ->assertSet('info.texts.cuisine.de', 'Risotto')
        ->set('info.texts.cuisine.de', '')
        ->call('save');

    expect($veneto->refresh()->info)->toBeNull();
});

it('zeigt die Abschnitte der Regionsinfos im Editor', function () {
    $veneto = regionInfoRegion(regionInfoCountry());

    $this->get(route('adminv2.master-data.regions.edit', $veneto))
        ->assertOk()
        ->assertSee('Regionsbeschreibung')
        ->assertSee('Reiseinfos der Region')
        ->assertSee('Fakten zur Region')
        ->assertSee('Beste Reisemonate')
        ->assertSee('Mit KI vorbefüllen')
        ->assertSee('Wie das Land (Europe/Rome)')
        ->assertSee('Ohne Infos');
});

it('zeigt bei den Koordinaten eine OpenStreetMap-Karte mit den Grenzen des Landes', function () {
    $italy = regionInfoCountry();
    $veneto = regionInfoRegion($italy, 'Venetien', ['lat' => 45.6, 'lng' => 11.9]);

    $this->get(route('adminv2.master-data.regions.edit', $veneto))
        ->assertOk()
        ->assertSee('boundaries\\/country\\/'.$italy->id, false)
        ->assertSee('Umriss: Grenzen von Italien.')
        ->assertSee('https://www.openstreetmap.org/?mlat=45.6', false)
        ->assertSee('In OpenStreetMap ansehen');
});

// ── KI im Editor ────────────────────────────────────────────────────────

it('uebernimmt einen KI-Vorschlag ins Formular, fuellt nur Leeres und speichert erst mit Speichern', function () {
    fakeRegionAi(regionInfoAnswer());
    $veneto = regionInfoRegion(regionInfoCountry(), 'Venetien', ['info' => ['texts' => ['short_description' => ['de' => 'Eigener Text.']]]]);

    $component = Livewire::test(RegionEditor::class, ['region' => $veneto->id])
        ->call('startSuggestion')
        ->assertSet('suggestRunId', null)
        ->assertSet('info.texts.short_description.de', 'Eigener Text.')
        ->assertSet('info.texts.short_description.en', 'Veneto stretches from the Dolomites to the Adriatic.')
        ->assertSet('info.texts.known_for.de', 'Lagune von Venedig, Dolomiten')
        ->assertSet('info.best_months', [5, 6, 9])
        ->assertSet('info.timezone', '');

    // Noch nicht gespeichert
    expect($veneto->refresh()->info['texts'])->not->toHaveKey('cuisine');

    $component->call('save');

    $info = $veneto->refresh()->info;
    expect($info['texts']['cuisine']['de'])->toBe('Risotto, Polenta und Cicchetti.')
        ->and($info['area_km2'])->toBe(18345)
        ->and(RegionInfo::status($info))->toBe(RegionInfo::STATUS_AI);

    Http::assertSent(fn ($request) => str_contains((string) ($request->data()['messages'][0]['content'] ?? ''), 'Region: Venetien')
        && str_contains((string) ($request->data()['messages'][0]['content'] ?? ''), 'NICHT wiederholt'));
});

it('fragt einen laufenden Vorschlag ab und uebernimmt ihn, sobald der Job fertig ist', function () {
    \Illuminate\Support\Facades\Queue::fake();
    fakeRegionAi(regionInfoAnswer());
    $veneto = regionInfoRegion(regionInfoCountry());

    $component = Livewire::test(RegionEditor::class, ['region' => $veneto->id])
        ->call('startSuggestion')
        ->assertSee('Die KI schreibt die Regionsinfos')
        ->call('checkSuggestion')
        ->assertSet('info.texts.short_description.de', '');

    $runId = $component->get('suggestRunId');
    expect($runId)->not->toBeNull();
    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SuggestRegionInfoJob::class, fn ($job) => $job->runId === $runId);

    (new \App\Jobs\SuggestRegionInfoJob($runId))->handle(app(RegionInfoGenerator::class));

    $component->call('checkSuggestion')
        ->assertSet('suggestRunId', null)
        ->assertSet('info.texts.short_description.de', 'Venetien reicht von den Dolomiten bis zur Adria.')
        ->assertDontSee('Die KI schreibt die Regionsinfos');
});

it('ueberschreibt vorhandene Texte nur auf Wunsch', function () {
    fakeRegionAi(regionInfoAnswer('Neuer Text der KI.'));
    $veneto = regionInfoRegion(regionInfoCountry(), 'Venetien', ['info' => ['texts' => ['short_description' => ['de' => 'Eigener Text.']]]]);

    Livewire::test(RegionEditor::class, ['region' => $veneto->id])
        ->set('suggestOverwrite', true)
        ->call('startSuggestion')
        ->assertSet('info.texts.short_description.de', 'Neuer Text der KI.');
});

it('meldet einen Fehler der KI und laesst das Formular unveraendert', function () {
    fakeRegionAi('Tut mir leid, dazu weiß ich nichts.');
    $veneto = regionInfoRegion(regionInfoCountry());

    Livewire::test(RegionEditor::class, ['region' => $veneto->id])
        ->call('startSuggestion')
        ->assertSet('suggestRunId', null)
        ->assertSet('info.texts.short_description.de', '')
        ->assertDispatched('adminv2-toast', message: 'KI-Vorschlag fehlgeschlagen: Die KI hat kein gültiges JSON geliefert.', variant: 'danger');

    // Der erledigte Vorschlag bleibt nicht liegen.
    expect(RegionInfoRun::count())->toBe(0);
});

it('markiert einen KI-Entwurf als geprueft', function () {
    $veneto = regionInfoRegion(regionInfoCountry(), 'Venetien', ['info' => ['texts' => ['cuisine' => ['de' => 'Risotto']], 'meta' => ['ai_generated_at' => '2026-10-10T08:00:00+00:00', 'ai_model' => 'gpt-test']]]);

    Livewire::test(RegionEditor::class, ['region' => $veneto->id])
        ->assertSee('KI-Entwurf, ungeprüft')
        ->call('markReviewed')
        ->call('save');

    $meta = $veneto->refresh()->info['meta'];
    expect($meta['ai_model'])->toBe('gpt-test')
        ->and($meta['reviewed_by'])->toBe('Anna Admin')
        ->and(RegionInfo::status($veneto->info))->toBe(RegionInfo::STATUS_REVIEWED);
});

// ── Liste und Stapel ────────────────────────────────────────────────────

it('filtert die Liste nach dem Stand der Regionsinfos', function () {
    $italy = regionInfoCountry();
    regionInfoRegion($italy, 'Leerland');
    regionInfoRegion($italy, 'Kientwurf', ['info' => ['texts' => ['cuisine' => ['de' => 'x']], 'meta' => ['ai_generated_at' => '2026-10-10T08:00:00+00:00']]]);
    regionInfoRegion($italy, 'Geprueft', ['info' => ['texts' => ['cuisine' => ['de' => 'x']], 'meta' => ['ai_generated_at' => '2026-10-10T08:00:00+00:00', 'reviewed_at' => '2026-10-10T09:00:00+00:00']]]);
    regionInfoRegion($italy, 'Handarbeit', ['info' => ['texts' => ['cuisine' => ['de' => 'x']]]]);

    $names = fn (string $status) => Livewire::test(RegionIndex::class)->set('info', $status)->instance()->rows->map(fn ($region) => $region->getName('de'))->all();

    expect($names(RegionInfo::STATUS_EMPTY))->toBe(['Leerland'])
        ->and($names(RegionInfo::STATUS_AI))->toBe(['Kientwurf'])
        ->and($names(RegionInfo::STATUS_REVIEWED))->toBe(['Geprueft'])
        ->and($names(RegionInfo::STATUS_MANUAL))->toBe(['Handarbeit']);
});

it('fuellt die gefilterten Regionen ohne Infos im Hintergrund und zeigt den Fortschritt', function () {
    $prompts = [];
    fakeRegionAi(function (string $prompt) use (&$prompts) {
        $prompts[] = $prompt;

        return str_contains($prompt, 'Region: Kaputt') ? 'kein json' : regionInfoAnswer();
    });

    $italy = regionInfoCountry();
    $spain = regionInfoCountry('ES', ['name_translations' => ['de' => 'Spanien', 'en' => 'Spain'], 'timezone' => 'Europe/Madrid']);
    $regions = collect(['Venetien', 'Toskana', 'Kaputt', 'Ligurien', 'Umbrien'])->map(fn ($name) => regionInfoRegion($italy, $name));
    $done = regionInfoRegion($italy, 'Fertig', ['info' => ['texts' => ['cuisine' => ['de' => 'Eigenes']]]]);
    $other = regionInfoRegion($spain, 'Katalonien');

    Livewire::test(RegionIndex::class)
        ->set('countryIds', [(string) $italy->id])
        ->assertSet('fillCount', 5)
        ->call('startFill')
        ->assertSee('KI-Vorbefüllung abgeschlossen')
        ->assertSee('1 fehlgeschlagen');

    $run = RegionInfoFillRun::current();
    expect($run->total)->toBe(5)
        ->and($run->done)->toBe(4)
        ->and($run->status)->toBe(RegionInfoRun::STATUS_DONE)
        ->and($run->failed)->toHaveKey((string) $regions[2]->id)
        ->and($run->pending)->toBe([])
        ->and($run->finished_at)->not->toBeNull()
        ->and(count($prompts))->toBeGreaterThanOrEqual(5);

    expect(RegionInfo::status($regions[0]->refresh()->info))->toBe(RegionInfo::STATUS_AI)
        ->and($regions[2]->refresh()->info)->toBeNull()
        ->and($done->refresh()->info['texts']['cuisine']['de'])->toBe('Eigenes')
        ->and($other->refresh()->info)->toBeNull();
});

it('startet keinen zweiten Lauf und laesst einen Lauf anhalten', function () {
    $italy = regionInfoCountry();
    $veneto = regionInfoRegion($italy);
    config(['services.openai.key' => 'test-key']);

    $run = RegionInfoFillRun::start([$veneto->id], false, null);

    Livewire::test(RegionIndex::class)
        ->call('startFill')
        ->assertDispatched('adminv2-toast', message: 'Es läuft bereits eine KI-Vorbefüllung.', variant: 'danger')
        ->call('cancelFill');

    // Der angehaltene Lauf tut nichts mehr.
    Http::fake();
    (new FillRegionInfoJob($run->id))->handle(app(RegionInfoGenerator::class));

    Http::assertNothingSent();
    expect(RegionInfoFillRun::current()->status)->toBe(RegionInfoRun::STATUS_CANCELLED)
        ->and($veneto->refresh()->info)->toBeNull();

    Livewire::test(RegionIndex::class)->assertSee('KI-Vorbefüllung angehalten')->call('dismissFill')->assertDontSee('KI-Vorbefüllung angehalten');
    expect(RegionInfoFillRun::current())->toBeNull();
});

it('fuellt Regionen per Befehl', function () {
    fakeRegionAi(regionInfoAnswer());
    $italy = regionInfoCountry();
    $spain = regionInfoCountry('ES', ['name_translations' => ['de' => 'Spanien', 'en' => 'Spain'], 'timezone' => 'Europe/Madrid']);
    $veneto = regionInfoRegion($italy);
    $catalonia = regionInfoRegion($spain, 'Katalonien');

    $this->artisan('regions:fill-info', ['--country' => ['it'], '--dry-run' => true])->expectsOutputToContain('1 Regionen (nur gezählt)')->assertSuccessful();
    expect($veneto->refresh()->info)->toBeNull();

    $this->artisan('regions:fill-info', ['--country' => ['IT']])->expectsOutputToContain('1 vorbefüllt, 0 fehlgeschlagen')->assertSuccessful();

    expect($veneto->refresh()->info['texts']['short_description']['de'])->toBe('Venetien reicht von den Dolomiten bis zur Adria.')
        ->and($catalonia->refresh()->info)->toBeNull();
});

// ── API ──────────────────────────────────────────────────────────────────

it('liefert die Kurzbeschreibung der Regionsinfos in der API je Sprache', function () {
    $veneto = regionInfoRegion(regionInfoCountry(), 'Venetien', [
        'description' => 'Alt',
        'info' => ['texts' => ['short_description' => ['de' => 'Kurz auf Deutsch', 'en' => 'Short in English']]],
    ]);

    expect(\App\Http\Resources\Api\V1\PlaceResource::region($veneto, 'en')['description'])->toBe('Short in English')
        ->and(\App\Http\Resources\Api\V1\PlaceResource::region($veneto, 'nl')['description'])->toBe('Kurz auf Deutsch')
        ->and(\App\Http\Resources\Api\V1\PlaceResource::region($veneto, null)['description'])->toBe('Kurz auf Deutsch');

    $veneto->update(['info' => null]);
    expect(\App\Http\Resources\Api\V1\PlaceResource::region($veneto->refresh(), 'de')['description'])->toBe('Alt');
});
