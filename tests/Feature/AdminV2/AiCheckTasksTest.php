<?php

use App\Livewire\AdminV2\MasterData\Airports\Editor as AirportEditor;
use App\Livewire\AdminV2\System\Ai;
use App\Mail\AdminTaskAssignedMail;
use App\Models\AdminTask;
use App\Models\AdminTaskActivity;
use App\Models\AdminTaskCategory;
use App\Models\AiCheck;
use App\Models\AiCheckRun;
use App\Models\Airport;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\User;
use App\Services\AiCheckBatchService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/**
 * KI-Pruefungen, die Aufgaben anlegen: trifft die Bedingung bei einem
 * Datensatz zu, entsteht unter der Sammelaufgabe der Pruefung eine
 * Unteraufgabe – am einzelnen Datensatz und im Sammellauf ueber alle.
 */
function aiTaskAdmin(string $name = 'Anna'): User
{
    return User::factory()->create(['name' => $name, 'is_admin' => true, 'is_active' => true]);
}

function aiTaskAirport(string $name, string $iata, array $attributes = []): Airport
{
    $continent = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);
    $country = Country::firstOrCreate(['iso_code' => 'DE'], ['name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'], 'iso3_code' => 'DEU', 'continent_id' => $continent->id]);
    $city = City::create(['name_translations' => ['de' => 'Stadt '.$iata, 'en' => 'City '.$iata], 'country_id' => $country->id]);

    return Airport::create(array_merge([
        'name' => $name,
        'iata_code' => $iata,
        'icao_code' => 'E'.$iata,
        'city_id' => $city->id,
        'country_id' => $country->id,
        'type' => 'international',
        'is_active' => true,
        'operates_24h' => false,
    ], $attributes));
}

function loungeCheck(array $attributes = []): AiCheck
{
    return AiCheck::create(array_merge([
        'name' => 'Lounges prüfen',
        'area' => 'airports',
        'section' => 'lounges',
        'prompt' => 'Prüfe die Lounges von {name} ({iata_code}): {daten}',
        'task_enabled' => true,
        'task_condition' => 'Es gibt Lounges am Flughafen, die nicht eingetragen sind.',
    ], $attributes));
}

/**
 * Die KI antwortet, was $responder zum Prompt liefert: ein Array (als JSON) oder Text.
 */
function aiTaskFake(callable $responder): void
{
    config(['services.openai.key' => 'test-key']);

    Http::fake(['api.openai.com/*' => function ($request) use ($responder) {
        $prompt = $request['messages'][0]['content'] ?? null;

        // Die Liste der Modelle des Schluessels.
        if ($prompt === null) {
            return Http::response(['data' => []]);
        }

        $answer = $responder($prompt);

        return Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => is_array($answer) ? json_encode($answer, JSON_UNESCAPED_UNICODE) : $answer]]],
            'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 20, 'total_tokens' => 120],
        ]);
    }]);
}

beforeEach(fn () => Mail::fake());

it('legt aus dem KI-Fenster eine Unteraufgabe an, wenn die Bedingung der Pruefung zutrifft', function () {
    $anna = aiTaskAdmin();
    $this->actingAs($anna);

    $cgn = aiTaskAirport('Cologne Bonn Airport', 'CGN', ['lounges' => [['name' => 'Business Lounge', 'location' => 'Terminal 1']]]);
    $check = loungeCheck();

    $met = true;
    aiTaskFake(function (string $prompt) use (&$met) {
        return ['answer' => 'Es gibt **zwei** Lounges.', 'met' => $met, 'title' => $met ? 'Zweite Lounge nachtragen' : '', 'description' => $met ? 'Laut KI gibt es zusätzlich die Airport Lounge World.' : ''];
    });

    $editor = Livewire::test(AirportEditor::class, ['airport' => $cgn->id])
        ->call('openAiCheck', 'lounges')
        ->assertSee('legt Aufgaben an')
        ->call('runAiCheck')
        ->assertSet('aiError', null)
        ->assertSee('Die Bedingung trifft zu – Unteraufgabe angelegt:')
        ->assertSee('Cologne Bonn Airport (CGN): Zweite Lounge nachtragen')
        // Die Karte "Aufgaben" auf der Seite laedt neu.
        ->assertDispatched('adminv2-tasks-changed');

    // Die Antwort der KI steht wie bei jeder Pruefung im Fenster.
    expect($editor->get('aiResult.html'))->toContain('<strong>zwei</strong>');

    // Der Auftrag der Pruefung mit den Daten des Abschnitts – ergaenzt um die Bedingung.
    Http::assertSent(fn ($request) => str_contains($request->body(), 'Business Lounge')
        && str_contains($request->body(), 'die nicht eingetragen sind')
        && str_contains($request->body(), 'met'));

    // Die Sammelaufgabe bezieht sich auf alle Flughaefen; die Unteraufgabe auf diesen.
    $parent = $check->fresh()->taskParent;
    $subtask = AdminTask::where('parent_id', $parent->id)->sole();

    expect($parent->title)->toBe('KI-Prüfung „Lounges prüfen“ – Flughäfen › Lounges')
        ->and($parent->ai_check_id)->toBe($check->id)
        ->and($parent->subject_id)->toBeNull()
        ->and($parent->category->name)->toBe('Stammdaten')
        ->and($parent->responsible_id)->toBe($anna->id)
        ->and($subtask->title)->toBe('Cologne Bonn Airport (CGN): Zweite Lounge nachtragen')
        ->and($subtask->description)->toBe('Laut KI gibt es zusätzlich die Airport Lounge World.')
        ->and($subtask->subject->is($cgn))->toBeTrue()
        ->and($subtask->ai_check_id)->toBe($check->id)
        ->and($subtask->category_id)->toBe($parent->category_id)
        ->and($subtask->responsible_id)->toBe($anna->id)
        ->and($subtask->created_by)->toBe($anna->id);

    // Beide Aufgaben nennen auf ihrer Seite die Pruefung; die Unteraufgabe fuehrt zum Flughafen.
    $this->get(route('adminv2.tasks.show', $parent))->assertOk()->assertSee('Sammelaufgabe der KI-Prüfung')->assertSee('alle Flughäfen › Lounges');
    $this->get(route('adminv2.tasks.show', $subtask))
        ->assertOk()
        ->assertSee('Angelegt von der KI-Prüfung')
        ->assertSee(route('adminv2.master-data.airports.edit', $cgn->id), false);

    // Erneut auffaellig: keine zweite Unteraufgabe – das Ergebnis steht als Notiz an der offenen.
    $editor->call('runAiCheck')->assertSee('es gibt bereits eine offene Unteraufgabe');

    expect(AdminTask::where('parent_id', $parent->id)->count())->toBe(1)
        ->and($subtask->activities()->where('type', AdminTaskActivity::TYPE_NOTE)->count())->toBe(1);

    // Trifft die Bedingung nicht zu, entsteht nichts.
    $met = false;
    $editor->call('runAiCheck')->assertSee('Die Bedingung trifft nicht zu – keine Aufgabe.');

    expect(AdminTask::count())->toBe(2);

    // Ist die Unteraufgabe erledigt und die Bedingung trifft wieder zu, entsteht eine neue.
    $met = true;
    $subtask->update(['status' => AdminTask::STATUS_DONE]);
    $editor->call('runAiCheck')->assertSee('Unteraufgabe angelegt:');

    expect(AdminTask::where('parent_id', $parent->id)->count())->toBe(2);

    // Ein fuer diesen Lauf angepasster Prompt legt keine Aufgabe an.
    $editor->set('aiPromptDraft', 'Nenne die Lounges von {name} in einem Satz.')
        ->call('runAiCheck')
        ->assertSet('aiError', null)
        ->assertDontSee('Die Bedingung trifft');

    expect(AdminTask::count())->toBe(3)
        ->and($editor->get('aiResult.task'))->toBeNull();
});

it('fuehrt eine Pruefung als Sammellauf ueber alle Flughaefen aus', function () {
    $anna = aiTaskAdmin();
    $dennis = aiTaskAdmin('Dennis');
    $this->actingAs($anna);

    $cgn = aiTaskAirport('Cologne Bonn Airport', 'CGN');
    aiTaskAirport('Flughafen München', 'MUC');
    aiTaskAirport('Hamburg Airport', 'HAM');
    $fra = aiTaskAirport('Frankfurt Airport', 'FRA');
    aiTaskAirport('Flughafen Berlin Brandenburg', 'BER');
    aiTaskAirport('Alter Flughafen', 'OLD')->delete();

    // Eine vorhandene Aufgabe dient als Sammelaufgabe; verantwortlich ist Dennis.
    $parent = AdminTask::create([
        'title' => 'Lounges aller Flughäfen prüfen',
        'category_id' => AdminTaskCategory::firstOrCreate(['name' => 'Stammdaten'])->id,
        'responsible_id' => $dennis->id,
        'created_by' => $anna->id,
    ]);
    $check = loungeCheck(['task_parent_id' => $parent->id]);

    // Auffaellig sind Koeln und Frankfurt; zu Muenchen kommt keine auswertbare Antwort.
    aiTaskFake(fn (string $prompt) => match (true) {
        str_contains($prompt, '(CGN)'), str_contains($prompt, '(FRA)') => ['answer' => 'Es fehlt eine Lounge.', 'met' => true, 'title' => 'Lounge nachtragen', 'description' => 'Bitte prüfen.'],
        str_contains($prompt, '(MUC)') => 'Dazu kann ich nichts sagen.',
        default => ['answer' => 'Alles vollständig.', 'met' => false, 'title' => '', 'description' => ''],
    });

    $batches = app(AiCheckBatchService::class);

    expect($batches->recordCount($check))->toBe(5);

    $run = $batches->start($check, $anna->id);

    expect($run->total)->toBe(5)->and($run->isRunning())->toBeTrue();
    // Je Pruefung laeuft nur ein Sammellauf.
    expect(fn () => $batches->start($check, $anna->id))->toThrow(RuntimeException::class);

    $run = $batches->advance($run, 60);

    expect($run->status)->toBe(AiCheckRun::STATUS_FINISHED)
        ->and($run->processed)->toBe(5)
        ->and($run->matched)->toBe(2)
        ->and($run->created)->toBe(2)
        ->and($run->failed)->toBe(1)
        ->and($run->total_tokens)->toBe(600)
        ->and($run->finished_at)->not->toBeNull();

    $subtasks = AdminTask::where('parent_id', $parent->id)->orderBy('id')->get();

    expect($subtasks->pluck('title')->all())->toBe(['Cologne Bonn Airport (CGN): Lounge nachtragen', 'Frankfurt Airport (FRA): Lounge nachtragen'])
        ->and($subtasks[0]->subject->is($cgn))->toBeTrue()
        ->and($subtasks[1]->subject->is($fra))->toBeTrue()
        // Verantwortung kommt von der Sammelaufgabe, angelegt hat, wer den Lauf gestartet hat.
        ->and($subtasks[0]->responsible_id)->toBe($dennis->id)
        ->and($subtasks[0]->created_by)->toBe($anna->id)
        ->and($parent->fresh()->ai_check_id)->toBe($check->id);

    // Der Flughafen im Papierkorb wurde nicht geprueft.
    Http::assertNotSent(fn ($request) => str_contains($request->body(), '(OLD)'));

    // Im Sammellauf geht keine Mail je Unteraufgabe raus – nur die zur Sammelaufgabe selbst.
    Mail::assertSent(AdminTaskAssignedMail::class, 1);

    // Ein zweiter Lauf legt nichts doppelt an: die offenen Unteraufgaben bekommen eine Notiz.
    $second = $batches->advance($batches->start($check, $anna->id), 60);

    expect($second->matched)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and(AdminTask::where('parent_id', $parent->id)->count())->toBe(2)
        ->and($subtasks[0]->activities()->where('type', AdminTaskActivity::TYPE_NOTE)->count())->toBe(1);

    // Der Zeitplan fuehrt laufende Sammellaeufe weiter.
    $third = $batches->start($check, $anna->id);
    $this->artisan('ai:run-check-batches')->assertSuccessful();

    expect($third->fresh()->status)->toBe(AiCheckRun::STATUS_FINISHED);

    // Abbrechen laesst angelegte Unteraufgaben stehen.
    $fourth = $batches->start($check, $anna->id);
    $batches->cancel($fourth);

    expect($fourth->fresh()->status)->toBe(AiCheckRun::STATUS_CANCELLED)
        ->and($batches->advance($fourth, 5)->processed)->toBe(0);
});

it('bricht einen Sammellauf ab, wenn die KI gar nicht antwortet', function () {
    $this->actingAs(aiTaskAdmin());

    foreach (range(1, 9) as $number) {
        aiTaskAirport('Flughafen '.$number, 'A0'.$number);
    }

    config(['services.openai.key' => 'test-key']);
    Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'Rate limit']], 429)]);

    $batches = app(AiCheckBatchService::class);
    $run = $batches->advance($batches->start(loungeCheck()), 60);

    // Nach zwei vergeblichen Schritten ist Schluss – der Rest wird nicht mehr versucht.
    expect($run->status)->toBe(AiCheckRun::STATUS_FAILED)
        ->and($run->processed)->toBe(AiCheckBatchService::PARALLEL * 2)
        ->and($run->failed)->toBe(AiCheckBatchService::PARALLEL * 2)
        ->and($run->error)->toContain('Rate limit')
        ->and(AdminTask::whereNotNull('parent_id')->count())->toBe(0);
});

it('hinterlegt Bedingung und Sammelaufgabe an der Pruefung und startet den Sammellauf unter System > KI', function () {
    $anna = aiTaskAdmin();
    $this->actingAs($anna);

    aiTaskAirport('Cologne Bonn Airport', 'CGN');
    aiTaskAirport('Frankfurt Airport', 'FRA');

    aiTaskFake(fn (string $prompt) => ['answer' => 'ok', 'met' => str_contains($prompt, '(CGN)'), 'title' => 'Lounge nachtragen', 'description' => 'Bitte prüfen.']);

    $page = Livewire::withQueryParams(['tab' => 'airports'])
        ->test(Ai::class)
        ->call('createCheck')
        ->set('checkSection', 'lounges')
        ->set('checkName', 'Lounges prüfen')
        ->set('checkPrompt', 'Prüfe die Lounges von {name} ({iata_code}): {daten}')
        ->set('checkTaskEnabled', true)
        ->call('saveCheck')
        // Ohne Bedingung laesst sich "Aufgaben anlegen" nicht speichern.
        ->assertHasErrors(['checkTaskCondition'])
        ->set('checkTaskCondition', 'Es gibt Lounges am Flughafen, die nicht eingetragen sind.')
        ->call('saveCheck')
        ->assertHasNoErrors();

    $check = AiCheck::sole();
    $parent = $check->taskParent;

    // Mit dem Speichern steht die Sammelaufgabe, die sich auf alle Flughaefen bezieht.
    expect($check->task_enabled)->toBeTrue()
        ->and($parent)->not->toBeNull()
        ->and($parent->responsible_id)->toBe($anna->id);

    $page->assertSee('Legt Aufgaben an')
        ->assertSee('Es gibt Lounges am Flughafen, die nicht eingetragen sind.')
        ->assertSee('Sammelaufgabe: KI-Prüfung „Lounges prüfen“ – Flughäfen › Lounges')
        ->assertSee('Für alle 2 Flughäfen ausführen')
        ->call('startBatch', $check->id)
        ->assertSee('Sammellauf läuft: 0 von 2 geprüft')
        // Die geoeffnete Seite fuehrt den Lauf weiter.
        ->call('advanceBatches')
        ->assertSee('Letzter Sammellauf')
        ->assertSee('2 von 2 geprüft, 1 auffällig, 1 neue Unteraufgabe');

    expect(AdminTask::where('parent_id', $parent->id)->sole()->title)->toBe('Cologne Bonn Airport (CGN): Lounge nachtragen');

    // Bearbeiten zeigt die hinterlegten Angaben; eine andere offene Hauptaufgabe laesst sich als Sammelaufgabe waehlen.
    $other = AdminTask::create(['title' => 'Eigene Sammelaufgabe', 'category_id' => $parent->category_id, 'responsible_id' => $anna->id]);

    $page->call('editCheck', $check->id)
        ->assertSet('checkTaskEnabled', true)
        ->assertSet('checkTaskCondition', 'Es gibt Lounges am Flughafen, die nicht eingetragen sind.')
        ->assertSet('checkTaskParentId', (string) $parent->id)
        ->assertSee('Eigene Sammelaufgabe')
        // Eine Unteraufgabe ist keine Sammelaufgabe.
        ->set('checkTaskParentId', (string) AdminTask::where('parent_id', $parent->id)->value('id'))
        ->call('saveCheck')
        ->assertHasErrors(['checkTaskParentId'])
        ->set('checkTaskParentId', (string) $other->id)
        ->call('saveCheck')
        ->assertHasNoErrors();

    expect($check->fresh()->task_parent_id)->toBe($other->id)
        ->and($other->fresh()->ai_check_id)->toBe($check->id);

    // Starten und abbrechen.
    $page->call('startBatch', $check->id)->call('cancelBatch', $check->id)->assertSee('(abgebrochen)');

    // Ohne "Aufgaben anlegen" verschwindet der Abschnitt von der Karte.
    $page->call('editCheck', $check->id)->set('checkTaskEnabled', false)->call('saveCheck')->assertHasNoErrors()->assertDontSee('Legt Aufgaben an');

    expect(fn () => app(AiCheckBatchService::class)->start($check->fresh()))->toThrow(RuntimeException::class);
});

it('fuehrt den Sammellauf in jedem Stammdaten-Bereich mit den Angaben des jeweiligen Formulars aus', function () {
    $anna = aiTaskAdmin();
    $this->actingAs($anna);

    $europe = Continent::firstOrCreate(['code' => 'EU'], ['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'sort_order' => 1]);
    Continent::create(['code' => 'AS', 'name_translations' => ['de' => 'Asien', 'en' => 'Asia'], 'sort_order' => 2]);
    Country::create(['name_translations' => ['de' => 'Deutschland', 'en' => 'Germany'], 'iso_code' => 'DE', 'iso3_code' => 'DEU', 'continent_id' => $europe->id]);
    $cgn = aiTaskAirport('Cologne Bonn Airport', 'CGN', ['lounges' => [['name' => 'Business Lounge', 'location' => 'Terminal 1']]]);

    // Jeder Bereich liefert die Angaben seines Formulars – dieselben wie im KI-Fenster des Eintrags.
    foreach (array_keys(\App\Support\AdminV2\AiAreas::areas()) as $area) {
        expect(\App\Support\AdminV2\AiRecordContexts::supports($area))->toBeTrue();
    }

    $fromForm = Livewire::test(AirportEditor::class, ['airport' => $cgn->id])->call('openAiCheck', 'general')->get('aiData');
    $fromRecord = \App\Support\AdminV2\AiRecordContexts::context('airports', $cgn);

    expect(array_keys($fromRecord))->toEqualCanonicalizing(array_keys($fromForm))
        ->and($fromRecord['lounges'])->toBe(['Business Lounge, Terminal 1'])
        ->and(\App\Support\AdminV2\AiRecordContexts::context('continents', $europe)['countries'])->toBe(['Deutschland (DE)']);

    // Sammellauf ueber die Kontinente: auffaellig ist Asien.
    aiTaskFake(fn (string $prompt) => ['answer' => 'ok', 'met' => str_contains($prompt, 'Asien'), 'title' => 'Länder ergänzen', 'description' => 'Dem Kontinent sind keine Länder zugeordnet.']);

    $check = AiCheck::create(['name' => 'Länder vollständig', 'area' => 'continents', 'section' => 'countries', 'prompt' => 'Prüfe die Länder von {name}: {daten}', 'task_enabled' => true, 'task_condition' => 'Dem Kontinent sind keine Länder zugeordnet.']);

    Livewire::withQueryParams(['tab' => 'continents'])
        ->test(Ai::class)
        ->assertSee('Legt Aufgaben an')
        ->assertSee('Für alle 2 Kontinente ausführen')
        ->call('startBatch', $check->id)
        ->call('advanceBatches')
        ->assertSee('2 von 2 geprüft, 1 auffällig, 1 neue Unteraufgabe');

    expect(AdminTask::whereNotNull('parent_id')->sole()->title)->toBe('Asien: Länder ergänzen');

    // Die Länderliste des Kontinents ging mit.
    Http::assertSent(fn ($request) => str_contains($request->body(), 'Deutschland (DE)'));
});

it('arbeitet im Zeitplan als die Person, die den Sammellauf gestartet hat', function () {
    $anna = aiTaskAdmin();
    aiTaskAirport('Cologne Bonn Airport', 'CGN');
    aiTaskFake(fn () => ['answer' => 'Es fehlt eine Lounge.', 'met' => true, 'title' => 'Lounge nachtragen', 'description' => 'Bitte prüfen.']);

    $batches = app(AiCheckBatchService::class);

    // Niemand ist angemeldet – wie beim Aufruf durch den Zeitplan.
    $run = $batches->start(loungeCheck(), $anna->id);
    $this->artisan('ai:run-check-batches')->assertSuccessful();

    $subtask = AdminTask::whereNotNull('parent_id')->sole();

    expect($run->fresh()->status)->toBe(AiCheckRun::STATUS_FINISHED)
        ->and($subtask->created_by)->toBe($anna->id)
        ->and($subtask->activities()->first()->user_id)->toBe($anna->id);

    // Gibt es keinen aktiven Admin mehr, endet der Lauf mit einem Hinweis statt mit einem Fehler je Eintrag.
    auth('web')->logout();
    auth('web')->forgetUser();
    User::query()->update(['is_active' => false]);

    $stuck = $batches->advance($batches->start(AiCheck::first(), $anna->id), 10);

    expect($stuck->status)->toBe(AiCheckRun::STATUS_FAILED)
        ->and($stuck->error)->toContain('aktiven Admin')
        ->and($stuck->processed)->toBe(0);
});
