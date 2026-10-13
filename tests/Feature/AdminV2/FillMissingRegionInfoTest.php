<?php

use App\Jobs\FillMissingRegionInfoJob;
use App\Livewire\AdminV2\MasterData\Regions\Editor as RegionEditor;
use App\Livewire\AdminV2\Tasks\RegionInfoRunPanel;
use App\Mail\FillMissingRegionInfoMail;
use App\Models\AdminTask;
use App\Models\AdminTaskActivity;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Models\RegionInfoSource;
use App\Models\Sight;
use App\Models\Subregion;
use App\Models\User;
use App\Services\RegionInfoGenerator;
use App\Support\AdminV2\RegionInfo;
use App\Support\AdminV2\RegionInfoTask;
use App\Support\AdminV2\RegionInfoTransfer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * regions:fill-missing – Luecken der Regionsinfos von Regionen und
 * Teilgebieten per KI fuellen, in der Queue, mit Mail am Ende.
 */
function gapCountry(string $iso = 'ES', string $name = 'Spanien'): Country
{
    $continent = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);

    return Country::create([
        'name_translations' => ['de' => $name, 'en' => $name],
        'iso_code' => $iso,
        'iso3_code' => $iso.'X',
        'continent_id' => $continent->id,
        'timezone' => 'Europe/Madrid',
    ]);
}

function gapRegion(Country $country, string $name, ?array $info = null): Region
{
    return Region::create([
        'name_translations' => ['de' => $name, 'en' => $name],
        'code' => $country->iso_code.'-'.substr(md5($name), 0, 3),
        'country_id' => $country->id,
        'info' => $info,
    ]);
}

function gapSubregion(Region $region, string $name, string $kind = 'island', ?array $info = null): Subregion
{
    return Subregion::create([
        'region_id' => $region->id,
        'country_id' => $region->country_id,
        'kind' => $kind,
        'name_translations' => ['de' => $name, 'en' => $name],
        'info' => $info,
    ]);
}

/** Alle Felder in allen Sprachen gefuellt – nichts mehr zu tun. */
function completeInfo(): array
{
    $texts = [];
    foreach (RegionInfo::allTexts() as $field => [, $kind]) {
        foreach (RegionInfo::locales() as $locale) {
            $texts[$field][$locale] = $kind === 'tags' ? ['Stichwort'] : "Text {$field} {$locale}";
        }
    }

    return ['texts' => $texts, 'best_months' => [5], 'population' => 100, 'area_km2' => 10];
}

/**
 * Fake der KI: Antwort je Prompt (Closure) oder immer dieselbe; merkt sich die
 * Prompts. Die Suche nach neuen Teilgebieten bekommt $discovery (Standard:
 * keine) und landet in $discoveryPrompts.
 */
function fakeGapAi(mixed $answer, array &$prompts = [], mixed $discovery = ['subregions' => []], array &$discoveryPrompts = []): void
{
    config(['services.openai.key' => 'test-key']);

    Http::fake(['api.openai.com/v1/chat/completions' => function ($request) use ($answer, &$prompts, $discovery, &$discoveryPrompts) {
        $prompt = (string) ($request->data()['messages'][0]['content'] ?? '');

        if (str_starts_with($prompt, 'Du pflegst die Teilgebiete')) {
            $discoveryPrompts[] = $prompt;
            $data = $discovery instanceof Closure ? $discovery($prompt) : $discovery;
        } else {
            $prompts[] = $prompt;
            $data = $answer instanceof Closure ? $answer($prompt) : $answer;
        }

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

it('zaehlt im Probelauf nur Regionen und Teilgebiete mit Luecken', function () {
    Http::fake();
    $spain = gapCountry();
    $canaries = gapRegion($spain, 'Kanaren', completeInfo());
    gapRegion($spain, 'Katalonien');
    gapSubregion($canaries, 'Teneriffa');
    gapSubregion($canaries, 'Lanzarote', 'island', completeInfo());
    gapRegion(gapCountry('IT', 'Italien'), 'Venetien');

    $this->artisan('regions:fill-missing', ['--country' => ['es'], '--dry-run' => true])
        ->expectsOutputToContain('1 Regionen, 1 Teilgebiete, Suche nach neuen Teilgebieten in 2 Regionen (nur gezählt)')
        ->assertSuccessful();

    Http::assertNothingSent();
    expect(RegionInfoRun::count())->toBe(0);
});

it('verlangt Laender und gueltige Mailadressen', function () {
    gapCountry();

    $this->artisan('regions:fill-missing')->assertExitCode(2);
    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--notify' => 'a@passolution.de, kein-mail'])->expectsOutputToContain('Ungültige E-Mail-Adresse: kein-mail')->assertExitCode(2);
    $this->artisan('regions:fill-missing', ['--country' => ['XX']])->expectsOutputToContain('Unbekanntes Land: XX')->assertExitCode(2);
});

it('fuellt nur die Luecken, behaelt die Pruefangaben und vermerkt die neuen Felder', function () {
    $prompts = [];
    fakeGapAi([
        'texts' => [
            'short_description' => ['de' => 'Darf nicht ankommen.', 'en' => 'Catalonia in brief.', 'nl' => 'Catalonië in het kort.'],
            'cuisine' => ['de' => 'Pa amb tomàquet und Crema catalana.', 'en' => 'Pa amb tomàquet.', 'nl' => 'Pa amb tomàquet.'],
        ],
        'population' => 8000000,
        'timezone' => 'Atlantic/Canary',
    ], $prompts);

    $catalonia = gapRegion(gapCountry(), 'Katalonien', [
        'texts' => ['short_description' => ['de' => 'Katalonien liegt im Nordosten.']],
        'meta' => ['reviewed_at' => '2026-10-01T10:00:00+02:00', 'reviewed_by' => 'Bea Prüfer'],
    ]);

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--sync' => true])->assertSuccessful();

    $info = $catalonia->refresh()->info;
    expect($info['texts']['short_description'])->toBe(['de' => 'Katalonien liegt im Nordosten.', 'en' => 'Catalonia in brief.', 'nl' => 'Catalonië in het kort.'])
        ->and($info['texts']['cuisine']['de'])->toBe('Pa amb tomàquet und Crema catalana.')
        ->and($info['population'])->toBe(8000000)
        ->and($info)->not->toHaveKey('timezone')
        ->and(RegionInfo::status($info))->toBe(RegionInfo::STATUS_AI)
        ->and($info['meta']['ai_filled'])->toBe(['short_description', 'cuisine', 'population'])
        ->and($info['meta']['last_reviewed_by'])->toBe('Bea Prüfer')
        ->and($info['meta'])->not->toHaveKey('reviewed_at');

    // Die KI bekommt das Vorhandene als Zusammenhang und soll nur Fehlendes liefern.
    expect($prompts[0])->toContain('Bereits hinterlegt – NICHT ändern')
        ->toContain('- short_description (de): Katalonien liegt im Nordosten.')
        ->toContain('NUR noch in: en, nl')
        ->toContain('- cuisine: Küche & Spezialitäten')
        ->toContain('- population: Einwohnerzahl')
        ->not->toContain('- timezone: IANA')
        ->toContain('Übernimm keine Sätze');

    // Im Editor ist zu sehen, was neu ist; "geprueft" raeumt den Vermerk ab.
    Livewire::test(RegionEditor::class, ['region' => $catalonia->id])
        ->assertSee('Von der KI ergänzt: Kurzbeschreibung, Küche & Spezialitäten, Einwohner')
        ->assertSee('Zuvor geprüft am 01.10.2026 von Bea Prüfer')
        ->call('markReviewed')
        ->call('save');

    expect($catalonia->refresh()->info['meta'])->toHaveKey('reviewed_at')->not->toHaveKey('ai_filled')->not->toHaveKey('last_reviewed_at');
});

it('laesst Eintraege unveraendert, zu denen die KI nichts Sicheres weiss', function () {
    fakeGapAi(['texts' => [], 'best_months' => [], 'population' => null, 'area_km2' => null, 'timezone' => null]);
    $info = ['texts' => ['short_description' => ['de' => 'Kurz.']], 'meta' => ['reviewed_at' => '2026-10-01T10:00:00+02:00']];
    $region = gapRegion(gapCountry(), 'Murcia', $info);

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--sync' => true])
        ->expectsOutputToContain('Ohne neue Angaben')
        ->assertSuccessful();

    expect($region->refresh()->info)->toEqual($info)
        ->and(RegionInfoRun::first()->result['filled'])->toBe(1)
        ->and(RegionInfoRun::first()->result['changed'])->toBe(0)
        ->and(RegionInfoRun::first()->failed)->toBe([]);
});

it('arbeitet in der Queue erst die Regionen, dann die Teilgebiete ab und schickt am Ende eine Mail', function () {
    Mail::fake();
    $prompts = [];
    fakeGapAi(fn (string $prompt) => [
        'texts' => ['short_description' => ['de' => str_contains($prompt, 'Teilgebiet') ? 'Eine Insel.' : 'Eine Region.', 'en' => 'Short.', 'nl' => 'Kort.']],
    ], $prompts);

    $spain = gapCountry();
    $canaries = gapRegion($spain, 'Kanaren');
    $tenerife = gapSubregion($canaries, 'Teneriffa');
    $south = gapSubregion($canaries, 'Südküste', 'area');

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--notify' => 'dh@passolution.de, team@passolution.de'])
        ->expectsOutputToContain('Am Ende geht eine Mail an dh@passolution.de, team@passolution.de')
        ->assertSuccessful();

    $run = RegionInfoRun::firstOrFail();
    expect($run->kind)->toBe(RegionInfoRun::KIND_FILL_MISSING)
        ->and($run->status)->toBe(RegionInfoRun::STATUS_DONE)
        ->and($run->done)->toBe(4)
        ->and($run->result['changed'])->toBe(3)
        ->and($run->result['tokens'])->toBe(120)
        ->and($canaries->refresh()->info['texts']['short_description']['de'])->toBe('Eine Region.')
        ->and($tenerife->refresh()->info['texts']['short_description']['de'])->toBe('Eine Insel.')
        ->and($south->refresh()->info['texts']['short_description']['de'])->toBe('Eine Insel.');

    // Teilgebiete kommen nach der Region – ihr Prompt kennt schon deren Kurzbeschreibung.
    expect($prompts[0])->not->toContain('Teilgebiet')
        ->and($prompts[1])->toContain('Kurzbeschreibung der Region (steht bereits an der Region, nicht wiederholen): Eine Region.')
        ->and(collect($prompts)->first(fn ($p) => str_contains($p, 'Reisegebiet: Südküste')))->not->toContain('- area_km2');

    Mail::assertSent(FillMissingRegionInfoMail::class, function (FillMissingRegionInfoMail $mail) {
        $html = $mail->render();

        return $mail->hasTo('dh@passolution.de') && $mail->hasTo('team@passolution.de')
            && str_contains($mail->envelope()->subject, 'Spanien')
            && str_contains($html, 'Ergänzt')
            && str_contains($html, 'info=ai');
    });
    Mail::assertSentCount(1);
});

it('meldet Fehler einzelner Eintraege in Mail und Stand, ohne den Lauf abzubrechen', function () {
    Mail::fake();
    fakeGapAi(fn (string $prompt) => str_contains($prompt, 'Murcia') ? 'Keine Ahnung.' : ['texts' => ['cuisine' => ['de' => 'Paella.']]]);
    $spain = gapCountry();
    gapRegion($spain, 'Murcia');
    $valencia = gapRegion($spain, 'Valencia');

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--notify' => 'dh@passolution.de'])->assertSuccessful();

    $run = RegionInfoRun::firstOrFail();
    expect($run->status)->toBe(RegionInfoRun::STATUS_DONE)
        ->and(array_values($run->failed))->toBe(['Die KI hat kein gültiges JSON geliefert.'])
        ->and($valencia->refresh()->info['texts']['cuisine']['de'])->toBe('Paella.');

    $this->artisan('regions:fill-missing', ['--status' => $run->id])->expectsOutputToContain('Murcia')->assertFailed();

    Mail::assertSent(FillMissingRegionInfoMail::class, fn ($mail) => str_contains($mail->render(), 'Murcia: Die KI hat kein gültiges JSON geliefert.'));
});

it('recherchiert mit --web einzeln im Internet und nimmt die Quellenverweise aus den Texten', function () {
    config(['services.openai.key' => 'test-key']);
    $inputs = [];
    Http::fake(['api.openai.com/v1/responses' => function ($request) use (&$inputs) {
        $inputs[] = (string) $request->data()['input'];

        return Http::response([
            'model' => 'gpt-test',
            'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode([
                'texts' => ['getting_there' => ['de' => 'Flughafen Reus ([wikipedia.org](https://de.wikipedia.org/wiki/Reus?utm_source=openai)) und [Bahnhof Tarragona](https://adif.es).']],
                'area_km2' => 6303,
            ], JSON_UNESCAPED_UNICODE)]]]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 50, 'total_tokens' => 150],
        ]);
    }]);

    $spain = gapCountry();
    $tarragona = gapRegion($spain, 'Tarragona');
    gapRegion($spain, 'Lleida');

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--web' => true, '--no-discover' => true, '--sync' => true])->assertSuccessful();

    expect($inputs)->toHaveCount(2)
        ->and($inputs[0])->toContain('Recherchiere dazu im Internet')
        ->and($inputs[0])->toContain('in eigenen Worten')
        ->and($tarragona->refresh()->info['texts']['getting_there']['de'])->toBe('Flughafen Reus und Bahnhof Tarragona.')
        ->and($tarragona->info['area_km2'])->toBe(6303)
        ->and(RegionInfoRun::first()->result['tokens'])->toBe(300);
});

it('fuellt mit --subregion nur die genannten Teilgebiete, ohne Regionen und ohne Suche', function () {
    Http::fake();
    $spain = gapCountry();
    $navarra = gapRegion($spain, 'Navarra');
    $estella = gapSubregion($navarra, 'Tierra Estella', 'area');
    gapSubregion($navarra, 'Baztan', 'area');

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--subregion' => [$estella->id], '--dry-run' => true])
        ->expectsOutputToContain('0 Regionen, 1 Teilgebiete, Suche nach neuen Teilgebieten in 0 Regionen (nur gezählt)')
        ->doesntExpectOutputToContain('Aufgabe #')
        ->assertSuccessful();
});

it('speichert die Quellen der Websuche je Feld getrennt und zeigt sie nur auf der eigenen Seite', function () {
    config(['services.openai.key' => 'test-key']);
    $requests = [];
    Http::fake(['api.openai.com/v1/responses' => function ($request) use (&$requests) {
        $requests[] = $request->data();
        $text = '{"texts": {"getting_there": {"de": "Flughafen Reus ([reus.cat](https://www.reus.cat/flughafen?utm_source=openai))."}, "climate": {"de": "Mild ([aemet.es](https://aemet.es/klima))."}}, "area_km2": 6303}';

        return Http::response([
            'model' => 'gpt-test',
            'output' => [
                ['type' => 'web_search_call', 'action' => ['type' => 'search', 'sources' => [['type' => 'url', 'url' => 'https://www.reus.cat/flughafen?utm_source=openai'], ['type' => 'url', 'url' => 'https://de.wikipedia.org/wiki/Tarragona']]]],
                ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => $text, 'annotations' => [
                    ['type' => 'url_citation', 'url' => 'https://www.reus.cat/flughafen?utm_source=openai', 'title' => 'Flughafen Reus', 'start_index' => mb_strpos($text, '([reus'), 'end_index' => 0],
                    ['type' => 'url_citation', 'url' => 'https://aemet.es/klima', 'title' => 'AEMET', 'start_index' => mb_strpos($text, '([aemet'), 'end_index' => 0],
                ]]]],
            ],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 50, 'total_tokens' => 150],
        ]);
    }]);

    $spain = gapCountry();
    $tarragona = gapRegion($spain, 'Tarragona');

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--web' => true, '--no-discover' => true, '--sync' => true])->assertSuccessful();

    $run = RegionInfoRun::firstOrFail();
    $sources = RegionInfoSource::query()->orderBy('id')->get();
    expect($requests[0]['include'])->toBe(['web_search_call.action.sources'])
        ->and($requests[0]['input'])->toContain('als Quellen gespeichert')
        ->and($tarragona->refresh()->info['texts']['getting_there']['de'])->toBe('Flughafen Reus.')
        ->and($sources->map(fn ($source) => [$source->field, $source->url, $source->cited])->all())->toBe([
            ['getting_there', 'https://www.reus.cat/flughafen', true],
            ['climate', 'https://aemet.es/klima', true],
            [null, 'https://de.wikipedia.org/wiki/Tarragona', false],
        ])
        ->and($sources->first()->domain)->toBe('reus.cat')
        ->and($sources->first()->sourceable_id)->toBe($tarragona->id)
        ->and($sources->first()->region_info_run_id)->toBe($run->id);

    // Weder Region noch API laden die Quellen; der Editor zeigt nur die Anzahl und verlinkt die eigene Seite.
    expect(array_key_exists('sources', $tarragona->toArray()))->toBeFalse();
    $this->get(route('adminv2.master-data.regions.edit', $tarragona))->assertOk()->assertSee('Quellen der KI-Recherche (3)')->assertDontSee('aemet.es/klima');
    $this->get(route('adminv2.master-data.region-info-sources', ['type' => 'region', 'id' => $tarragona->id]))
        ->assertOk()
        ->assertSee('Quellen der KI-Recherche – Tarragona')
        ->assertSeeInOrder(['Klima', 'AEMET', 'Anreise', 'Flughafen Reus', 'Weitere besuchte Seiten', 'de.wikipedia.org']);
    $this->get('/adminv2/master-data/sources/city/1')->assertNotFound();
});

it('nimmt Belege auch aus Links im Text, wenn die Websuche keine Annotationen liefert', function () {
    config(['services.openai.key' => 'test-key']);
    Http::fake(['api.openai.com/v1/responses' => Http::response([
        'model' => 'gpt-test',
        'output' => [
            ['type' => 'web_search_call', 'action' => ['sources' => [['type' => 'url', 'url' => 'https://idescat.cat/pop?utm_source=openai'], ['type' => 'url', 'url' => 'https://reddit.com/r/catalunya']]]],
            ['type' => 'message', 'content' => [['type' => 'output_text', 'annotations' => [], 'text' => '{"texts": {"cuisine": {"de": "Wein und Olivenöl ([Priorat DOQ](https://doqpriorat.org/de))."}}, "population": 9415 ([Idescat](https://idescat.cat/pop?utm_source=openai))}']]],
        ],
        'usage' => ['total_tokens' => 150],
    ])]);

    $priorat = gapRegion(gapCountry(), 'Priorat');

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--web' => true, '--no-discover' => true, '--sync' => true])->assertSuccessful();

    expect($priorat->refresh()->info['population'])->toBe(9415)
        ->and(RegionInfoSource::query()->orderByDesc('cited')->orderBy('id')->get()->map(fn ($source) => [$source->field, $source->url, $source->title, $source->cited])->all())->toBe([
            ['cuisine', 'https://doqpriorat.org/de', 'Priorat DOQ', true],
            ['population', 'https://idescat.cat/pop', 'Idescat', true],
            [null, 'https://reddit.com/r/catalunya', null, false],
        ]);
});

it('haelt einen Lauf an und setzt einen abgebrochenen Job fort', function () {
    Queue::fake();
    Http::fake();
    $spain = gapCountry();
    gapRegion($spain, 'Aragón');

    $this->artisan('regions:fill-missing', ['--country' => ['ES']])->assertSuccessful();
    $run = RegionInfoRun::firstOrFail();
    Queue::assertPushed(FillMissingRegionInfoJob::class, 1);

    // Ein abgebrochener Job stellt den Lauf mit einer neuen Kette wieder an.
    (new FillMissingRegionInfoJob($run->id))->failed(new RuntimeException('Zeitlimit'));
    Queue::assertPushed(FillMissingRegionInfoJob::class, 2);
    expect($run->refresh()->result['restarts'])->toBe(1)->and($run->isRunning())->toBeTrue();

    $this->artisan('regions:fill-missing', ['--cancel' => $run->id])->expectsOutputToContain('angehalten')->assertSuccessful();
    expect($run->refresh()->status)->toBe(RegionInfoRun::STATUS_CANCELLED)->and($run->pending)->toBe([]);

    FillMissingRegionInfoJob::step($run->id, app(RegionInfoGenerator::class));
    Http::assertNothingSent();
});

it('bricht bei leerem OpenAI-Guthaben sofort ab, laesst das Offene offen und meldet es', function () {
    Mail::fake();
    config(['services.openai.key' => 'test-key']);
    Http::fake(['api.openai.com/*' => Http::response(['error' => [
        'message' => 'You have no credits remaining. Add credits to continue using the API.',
        'type' => 'insufficient_quota',
    ]], 429)]);
    $spain = gapCountry();
    gapRegion($spain, 'Aragón');
    gapRegion($spain, 'Murcia');
    createRegionInfoTasks();
    $task = RegionInfoTask::openFor($spain);

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--no-discover' => true, '--web' => true, '--sync' => true, '--notify' => 'dh@passolution.de'])
        ->expectsOutputToContain('no credits remaining');

    $run = RegionInfoRun::firstOrFail();
    expect($run->status)->toBe(RegionInfoRun::STATUS_FAILED)
        ->and($run->error)->toContain('OpenAI nimmt keine Anfragen an')
        ->and($run->failed)->toBe([])
        ->and($run->pending)->toHaveCount(2)
        ->and($task->refresh()->status)->toBe(AdminTask::STATUS_OPEN);
    Http::assertSentCount(1);
    Mail::assertSent(FillMissingRegionInfoMail::class, fn ($mail) => str_contains($mail->render(), 'no credits remaining'));
});

// ── Aufgaben je Land ────────────────────────────────────────────────────

function runRegionInfoTaskMigration(string $file = '2026_10_12_100000_create_region_info_tasks'): void
{
    (require base_path("database/migrations/{$file}.php"))->up();
}

/**
 * Aufgaben fuer alle Laender mit Regionen – in den Tests auch fuer Spanien,
 * das auf Live keine Aufgabe bekommt (RegionInfoTask::EXCLUDED).
 */
function createRegionInfoTasks(): void
{
    Country::query()->has('regions')->get()->each(fn (Country $country) => RegionInfoTask::createFor($country));
}

it('legt per Migration je Land mit Regionen ausser Spanien und Italien eine Aufgabe an und weist sie Daniel Henninger zu', function () {
    Mail::fake();
    $daniel = User::factory()->create(['email' => RegionInfoTask::RESPONSIBLE_EMAIL, 'name' => 'Daniel Henninger', 'is_admin' => true, 'is_active' => true]);
    gapRegion(gapCountry(), 'Katalonien');
    gapRegion(gapCountry('IT', 'Italien'), 'Venetien');
    $portugal = gapCountry('PT', 'Portugal');
    gapRegion($portugal, 'Algarve');
    $greece = gapCountry('GR', 'Griechenland');
    gapRegion($greece, 'Kreta');
    gapCountry('MC', 'Monaco');

    runRegionInfoTaskMigration();
    runRegionInfoTaskMigration();
    RegionInfoTask::openFor($greece)->update(['status' => AdminTask::STATUS_DONE]);
    runRegionInfoTaskMigration('2026_10_13_100000_assign_region_info_tasks');

    expect(AdminTask::query()->orderBy('title')->pluck('title')->all())->toBe(['Regionsinfos per KI ergänzen – Griechenland', 'Regionsinfos per KI ergänzen – Portugal']);

    $task = RegionInfoTask::openFor($portugal);
    expect($task->subjectLabel())->toContain('Portugal')
        ->and($task->category->name)->toBe('Stammdaten')
        ->and($task->status)->toBe(AdminTask::STATUS_OPEN)
        ->and($task->responsible_id)->toBe($daniel->id)
        ->and($task->description)->toContain('php artisan regions:fill-missing --country=PT')
        // Erledigte bleiben, wie sie sind.
        ->and(AdminTask::where('subject_id', $greece->id)->first()->responsible_id)->not->toBe($daniel->id);
    Mail::assertNothingSent();
});

it('erledigt die Aufgabe des Landes nach Lauf und Mail und schreibt das Ergebnis in den Verlauf', function () {
    Mail::fake();
    fakeGapAi(['texts' => ['cuisine' => ['de' => 'Paella.']]]);
    $spain = gapCountry();
    gapRegion($spain, 'Valencia');
    createRegionInfoTasks();
    $task = RegionInfoTask::openFor($spain);

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--dry-run' => true])->expectsOutputToContain("Aufgabe #{$task->id}")->assertSuccessful();
    expect($task->refresh()->status)->toBe(AdminTask::STATUS_OPEN);

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--notify' => 'dh@passolution.de'])->assertSuccessful();

    $notes = $task->activities()->where('type', AdminTaskActivity::TYPE_NOTE)->orderBy('id')->pluck('body')->all();
    expect($task->refresh()->status)->toBe(AdminTask::STATUS_DONE)
        ->and($task->completed_at)->not->toBeNull()
        ->and($notes[0])->toContain('gestartet: 1 Regionen und 0 Teilgebiete')
        ->and($notes[1])->toContain('fertig: 1 ergänzt, 0 ohne neue Angaben, 0 fehlgeschlagen. Mail an dh@passolution.de verschickt.')
        ->and(RegionInfoRun::first()->result['task_id'])->toBe($task->id);
    Mail::assertSent(FillMissingRegionInfoMail::class);
});

it('erledigt die Aufgabe gleich, wenn nichts fehlt, und laesst sie bei mehreren Laendern in Ruhe', function () {
    Http::fake();
    $spain = gapCountry();
    gapRegion($spain, 'Kanaren', completeInfo());
    $italy = gapCountry('IT', 'Italien');
    gapRegion($italy, 'Venetien', completeInfo());
    createRegionInfoTasks();

    $this->artisan('regions:fill-missing', ['--country' => ['ES', 'IT'], '--no-discover' => true])->assertSuccessful();
    expect(AdminTask::where('status', AdminTask::STATUS_DONE)->count())->toBe(0);

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--no-discover' => true])->expectsOutputToContain('Nichts zu tun')->assertSuccessful();
    expect(AdminTask::where('subject_id', $spain->id)->first()->status)->toBe(AdminTask::STATUS_DONE)
        ->and(RegionInfoTask::openFor($italy))->not->toBeNull();
});

it('setzt die Aufgabe beim Anhalten wieder auf offen', function () {
    Queue::fake();
    $spain = gapCountry();
    gapRegion($spain, 'Aragón');
    createRegionInfoTasks();
    $task = RegionInfoTask::openFor($spain);

    $this->artisan('regions:fill-missing', ['--country' => ['ES']])->assertSuccessful();
    expect($task->refresh()->status)->toBe(AdminTask::STATUS_IN_PROGRESS);

    $this->artisan('regions:fill-missing', ['--cancel' => RegionInfoRun::first()->id])->assertSuccessful();
    expect($task->refresh()->status)->toBe(AdminTask::STATUS_OPEN)
        ->and($task->activities()->latest('id')->where('type', AdminTaskActivity::TYPE_NOTE)->first()->body)->toContain('angehalten');
});

it('startet den Lauf aus der Aufgabe heraus, zeigt den Stand und haelt ihn an', function () {
    Queue::fake();
    $spain = gapCountry();
    gapRegion($spain, 'Aragón');
    gapRegion($spain, 'Kanaren', completeInfo());
    createRegionInfoTasks();
    $task = RegionInfoTask::openFor($spain);

    $this->get(route('adminv2.tasks.show', $task))->assertOk()->assertSee('KI-Lauf')->assertSee('Lauf starten');

    $panel = Livewire::test(RegionInfoRunPanel::class, ['taskId' => $task->id])
        ->assertSeeText('1 Regionen und 0 Teilgebiete mit Lücken')
        ->assertSeeText('Suche nach neuen Teilgebieten in 2 Regionen')
        ->set('discover', false)
        ->assertDontSee('Suche nach neuen Teilgebieten')
        ->set('notify', 'dh@passolution.de, kein-mail')
        ->call('start')
        ->assertHasErrors('notify')
        ->set('notify', 'dh@passolution.de')
        ->set('web', false)
        ->call('start')
        ->assertHasNoErrors()
        ->assertDispatched('adminv2-task-changed');

    $run = RegionInfoRun::firstOrFail();
    expect($run->result['task_id'])->toBe($task->id)
        ->and($run->result['notify'])->toBe(['dh@passolution.de'])
        ->and($run->result['web'])->toBeFalse()
        ->and($run->result['discover'])->toBe(0)
        ->and($run->pending)->toHaveCount(1)
        ->and($task->refresh()->status)->toBe(AdminTask::STATUS_IN_PROGRESS);
    Queue::assertPushed(FillMissingRegionInfoJob::class, 1);

    // Laeuft schon: kein zweiter Lauf – weder hier noch per Befehl.
    $panel->assertSee('Lauf #'.$run->id)->assertSee('Anhalten')->assertDontSee('Lauf starten')->call('start');
    $this->artisan('regions:fill-missing', ['--country' => ['ES']])->expectsOutputToContain('läuft schon ein Lauf')->assertFailed();
    expect(RegionInfoRun::count())->toBe(1);

    $panel->call('cancel')->assertSee('angehalten')->assertSee('Neuen Lauf starten');
    expect($run->refresh()->status)->toBe(RegionInfoRun::STATUS_CANCELLED)
        ->and($task->refresh()->status)->toBe(AdminTask::STATUS_OPEN);
});

it('zeigt den KI-Lauf nur an Aufgaben "Regionsinfos per KI ergänzen" und arbeitet ihn bis zur erledigten Aufgabe ab', function () {
    Mail::fake();
    fakeGapAi(['texts' => ['cuisine' => ['de' => 'Paella.']]]);
    $spain = gapCountry();
    $valencia = gapRegion($spain, 'Valencia');
    createRegionInfoTasks();
    $task = RegionInfoTask::openFor($spain);
    $other = AdminTask::withoutAssignmentMails(fn () => AdminTask::create(['title' => 'Etwas anderes', 'category_id' => $task->category_id, 'subject_type' => $task->subject_type, 'subject_id' => $spain->id]));

    $this->get(route('adminv2.tasks.show', $other))->assertOk()->assertDontSee('KI-Lauf');

    // Ohne Queue-Fake laeuft der Job hier synchron durch.
    Livewire::test(RegionInfoRunPanel::class, ['taskId' => $task->id])
        ->set('web', false)
        ->set('discover', false)
        ->call('start')
        ->call('reload')
        ->assertSee('fertig')
        ->assertSee('Regionen prüfen');

    expect($valencia->refresh()->info['texts']['cuisine']['de'])->toBe('Paella.')
        ->and($task->refresh()->status)->toBe(AdminTask::STATUS_DONE);
    Mail::assertSent(FillMissingRegionInfoMail::class);
});

// ── Neue Teilgebiete ────────────────────────────────────────────────────

it('recherchiert neue Teilgebiete, legt sie ohne Dubletten an, ordnet Staedte zu und fuellt ihre Infos im selben Lauf', function () {
    Mail::fake();
    $prompts = [];
    $discoveryPrompts = [];
    fakeGapAi(
        fn (string $prompt) => ['texts' => ['short_description' => ['de' => str_contains($prompt, 'Costa Brava') ? 'Felsige Buchten.' : 'Text.']]],
        $prompts,
        ['subregions' => [
            ['kind' => 'area', 'name' => ['de' => 'Costa Brava', 'en' => 'Costa Brava', 'nl' => 'Costa Brava'], 'lat' => 41.9, 'lng' => 3.1, 'cities' => ['Tossa de Mar', 'Lloret de Mar']],
            ['kind' => 'area', 'name' => ['de' => 'Costa Dorada', 'en' => 'Costa Daurada'], 'lat' => -33.9, 'lng' => 151.2, 'cities' => []],
            ['kind' => 'area', 'name' => ['de' => 'Pyrenaeen', 'en' => 'Pyrenees'], 'lat' => null, 'lng' => null, 'cities' => []],
            ['kind' => 'area', 'name' => ['de' => 'Katalonien', 'en' => 'Catalonia'], 'cities' => []],
            ['kind' => 'volcano', 'name' => ['en' => 'Ohne deutschen Namen'], 'cities' => []],
        ]],
        $discoveryPrompts,
    );

    $spain = gapCountry();
    $catalonia = gapRegion($spain, 'Katalonien');
    $catalonia->forceFill(['lat' => 41.8, 'lng' => 1.5, 'info' => completeInfo()])->save();
    $pyrenees = gapSubregion($catalonia, 'Pyrenäen', 'area', completeInfo());
    $tossa = City::create(['name_translations' => ['de' => 'Tossa de Mar'], 'country_id' => $spain->id, 'region_id' => $catalonia->id]);
    $lloret = City::create(['name_translations' => ['de' => 'Lloret de Mar'], 'country_id' => $spain->id, 'region_id' => $catalonia->id, 'subregion_id' => $pyrenees->id]);
    $castle = Sight::create(['name_translations' => ['de' => 'Vila Vella'], 'country_id' => $spain->id, 'region_id' => $catalonia->id, 'city_id' => $tossa->id, 'category' => 'castle']);
    createRegionInfoTasks();

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--notify' => 'dh@passolution.de'])->assertSuccessful();

    $brava = Subregion::query()->where('name_translations->de', 'Costa Brava')->firstOrFail();
    $dorada = Subregion::query()->where('name_translations->de', 'Costa Dorada')->firstOrFail();

    // Pyrenäen gab es schon (Akzente egal), Katalonien ist die Region selbst, ohne deutschen Namen faellt weg.
    expect(Subregion::query()->pluck('name_translations')->map(fn ($names) => $names['de'])->sort()->values()->all())->toBe(['Costa Brava', 'Costa Dorada', 'Pyrenäen'])
        ->and($brava->region_id)->toBe($catalonia->id)
        ->and($brava->kind)->toBe('area')
        ->and((float) $brava->lat)->toBe(41.9)
        ->and($dorada->lat)->toBeNull()
        ->and($brava->info['meta']['ai_created_at'])->not->toBeNull()
        // Gleich im selben Lauf mit Infos versehen
        ->and($brava->info['texts']['short_description']['de'])->toBe('Felsige Buchten.')
        ->and(RegionInfo::status($brava->info))->toBe(RegionInfo::STATUS_AI)
        // Zugeordnet wird nur, was noch kein Teilgebiet hat – samt Sehenswuerdigkeiten der Stadt.
        ->and($tossa->refresh()->subregion_id)->toBe($brava->id)
        ->and($lloret->refresh()->subregion_id)->toBe($pyrenees->id)
        ->and($castle->refresh()->subregion_id)->toBe($brava->id)
        ->and($catalonia->refresh()->subregions_checked_at)->not->toBeNull();

    expect($discoveryPrompts)->toHaveCount(1)
        ->and($discoveryPrompts[0])->toContain('Schon angelegte Teilgebiete (NICHT noch einmal nennen): Pyrenäen (Reisegebiet)')
        ->and($discoveryPrompts[0])->toContain('Städte der Region: Tossa de Mar, Lloret de Mar');

    $run = RegionInfoRun::firstOrFail();
    expect($run->result['created'])->toBe(2)->and($run->status)->toBe(RegionInfoRun::STATUS_DONE);

    Mail::assertSent(FillMissingRegionInfoMail::class, fn ($mail) => str_contains($html = $mail->render(), 'Costa Brava (Reisegebiet, Katalonien) · 1 Stadt zugeordnet') && str_contains($html, 'Teilgebiete neu angelegt'));
    expect(RegionInfoTask::openFor($spain))->toBeNull()
        ->and(AdminTask::first()->activities()->latest('id')->where('type', AdminTaskActivity::TYPE_NOTE)->first()->body)->toContain('2 Teilgebiete neu angelegt');

    // Im Editor ist zu sehen, dass die KI das Teilgebiet angelegt hat.
    $this->get('/adminv2/master-data/subregions/'.$brava->id)->assertOk()->assertSee('Von der KI recherchiert und angelegt');
});

it('sucht in durchsuchten Regionen nur mit --rediscover erneut und gar nicht mit --no-discover', function () {
    $discoveryPrompts = [];
    $prompts = [];
    fakeGapAi(['texts' => []], $prompts, ['subregions' => []], $discoveryPrompts);
    $spain = gapCountry();
    gapRegion($spain, 'Galicien', completeInfo());

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--sync' => true])->assertSuccessful();
    expect($discoveryPrompts)->toHaveCount(1);

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--sync' => true])->expectsOutputToContain('Nichts zu tun')->assertSuccessful();
    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--rediscover' => true, '--no-discover' => true, '--sync' => true])->expectsOutputToContain('Nichts zu tun')->assertSuccessful();
    expect($discoveryPrompts)->toHaveCount(1);

    $this->artisan('regions:fill-missing', ['--country' => ['ES'], '--rediscover' => true, '--sync' => true])->assertSuccessful();
    expect($discoveryPrompts)->toHaveCount(2);
});

// ── Export und Import (Migration) ───────────────────────────────────────

it('gibt Regionsinfos und Teilgebiete aus und fuellt beim Einlesen nur Luecken', function () {
    $spain = gapCountry();
    $catalonia = gapRegion($spain, 'Katalonien', [
        'texts' => ['short_description' => ['de' => 'Lokal.', 'en' => 'Local.'], 'cuisine' => ['de' => 'Crema catalana.']],
        'meta' => ['ai_generated_at' => '2026-10-12T10:00:00+02:00', 'ai_model' => 'gpt-test'],
    ]);
    $catalonia->forceFill(['subregions_checked_at' => now()])->save();
    $brava = gapSubregion($catalonia, 'Costa Brava', 'area', ['texts' => ['short_description' => ['de' => 'Buchten.']], 'meta' => ['ai_created_at' => '2026-10-12T10:00:00+02:00', 'ai_generated_at' => '2026-10-12T10:00:00+02:00', 'ai_model' => 'gpt-test']]);
    $pyrenees = gapSubregion($catalonia, 'Pyrenäen', 'area', ['texts' => ['climate' => ['de' => 'Kühl.']]]);
    City::create(['name_translations' => ['de' => 'Tossa de Mar'], 'country_id' => $spain->id, 'region_id' => $catalonia->id, 'subregion_id' => $brava->id]);

    $this->artisan('regions:export-info', ['--country' => ['ES'], '--path' => 'storage/framework/testing/region-info.json'])->expectsOutputToContain('ES: 1 Regionen, 2 Teilgebiete')->assertSuccessful();
    $data = json_decode(file_get_contents(base_path('storage/framework/testing/region-info.json')), true);
    expect($data['ES']['regions']['Katalonien']['subregions_checked'])->toBeTrue();

    // Anderes System: Katalonien mit eigener, gepruefter Kurzbeschreibung, Pyrenaeen schon da, Costa Brava fehlt.
    Subregion::query()->forceDelete();
    City::query()->update(['subregion_id' => null]);
    $catalonia->forceFill(['subregions_checked_at' => null, 'info' => ['texts' => ['short_description' => ['de' => 'Live.']], 'meta' => ['reviewed_at' => '2026-10-01T10:00:00+02:00', 'reviewed_by' => 'Bea']]])->save();
    $livePyrenees = gapSubregion($catalonia, 'Pyrenäen', 'area', ['texts' => ['climate' => ['de' => 'Live-Klima.']]]);

    $result = RegionInfoTransfer::import($data);

    $info = $catalonia->refresh()->info;
    expect($info['texts']['short_description'])->toBe(['de' => 'Live.', 'en' => 'Local.'])
        ->and($info['texts']['cuisine']['de'])->toBe('Crema catalana.')
        ->and($info['meta']['last_reviewed_by'])->toBe('Bea')
        ->and($info['meta']['ai_filled'])->toBe(['short_description', 'cuisine'])
        ->and($catalonia->subregions_checked_at)->not->toBeNull()
        ->and($livePyrenees->refresh()->info['texts']['climate']['de'])->toBe('Live-Klima.')
        ->and($result['subregions']['created'])->toBe(1);

    $newBrava = Subregion::query()->where('name_translations->de', 'Costa Brava')->firstOrFail();
    expect($newBrava->info['meta']['ai_created_at'])->toBe('2026-10-12T10:00:00+02:00')
        ->and(City::first()->subregion_id)->toBe($newBrava->id);

    // Zweites Einlesen aendert nichts mehr.
    expect(RegionInfoTransfer::import($data))->toMatchArray(['regions' => 0, 'checked' => 0])
        ->and(Subregion::count())->toBe(2);

    @unlink(base_path('storage/framework/testing/region-info.json'));
});
