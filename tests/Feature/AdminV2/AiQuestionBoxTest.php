<?php

use App\Jobs\AskAiQuestionJob;
use App\Livewire\AdminV2\AiQuestionBox;
use App\Models\AiQuestion;
use App\Models\Continent;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * Freie Fragen an die KI unter den Abschnitten eines Eintrags – beantwortet
 * im Hintergrund (Queue), die Antwort erscheint unter dem Fragefeld.
 */
beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
    $this->actingAs($this->admin);

    config(['services.openai.key' => 'test-key', 'services.anthropic.key' => null, 'services.gemini.key' => null]);

    $continent = Continent::create(['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'code' => 'EU', 'sort_order' => 0]);
    $this->country = Country::create([
        'name_translations' => ['de' => 'Spanien', 'en' => 'Spain'],
        'iso_code' => 'ES',
        'iso3_code' => 'ESP',
        'continent_id' => $continent->id,
    ]);

    Http::fake([
        'api.openai.com/v1/models' => Http::response(['data' => [['id' => 'gpt-5.4', 'created' => 1760000000]]]),
        'api.openai.com/v1/responses' => Http::response([
            'model' => 'gpt-5.4',
            'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => "In Spanien sind **5–10 %** üblich.\n\n<script>alert(1)</script>", 'annotations' => [
                ['type' => 'url_citation', 'url' => 'https://www.spain.info/trinkgeld', 'title' => 'Spain.info', 'start_index' => 0, 'end_index' => 5],
            ]]]]],
            'usage' => ['input_tokens' => 120, 'output_tokens' => 40, 'total_tokens' => 160],
        ]),
    ]);
});

function questionBox(Country $country, string $section = 'tipping'): \Livewire\Features\SupportTesting\Testable
{
    return Livewire::test(AiQuestionBox::class, ['area' => 'countries', 'section' => $section, 'subject' => $country, 'title' => 'Spanien']);
}

it('zeigt das Fragefeld an jedem KI-Abschnitt der Laenderseite', function () {
    $this->get(route('adminv2.master-data.countries.edit', $this->country))
        ->assertOk()
        ->assertSeeLivewire(AiQuestionBox::class)
        ->assertSee('Frage an die KI zu „Trinkgeld“')
        ->assertSee('Frage an die KI zu „Koordinaten“');
});

it('nimmt eine Frage als Auftrag in die Queue und leert das Feld', function () {
    Queue::fake();

    questionBox($this->country)
        ->assertSet('provider', 'openai')
        ->assertSet('model', 'gpt-5.4')
        ->set('question', 'Wie viel Trinkgeld gibt man im Restaurant?')
        ->call('ask')
        ->assertHasNoErrors()
        ->assertSet('question', '')
        ->assertSee('Wartet auf Bearbeitung');

    $question = AiQuestion::sole();
    expect($question)
        ->user_id->toBe($this->admin->id)
        ->section->toBe('tipping')
        ->subject_id->toBe($this->country->id)
        ->model->toBe('gpt-5.4')
        ->status->toBe(AiQuestion::STATUS_QUEUED);

    Queue::assertPushed(AskAiQuestionJob::class, fn (AskAiQuestionJob $job) => $job->questionId === $question->id);
});

it('weist leere Fragen, KIs ohne Schluessel und fremde Modelle ab', function () {
    Queue::fake();

    questionBox($this->country)
        ->call('ask')->assertHasErrors(['question' => 'required'])
        ->set('question', 'Eine Frage?')
        ->set('provider', 'anthropic')
        ->call('ask')->assertHasErrors(['provider'])
        ->set('provider', 'openai')
        ->set('model', 'claude-opus-5-5')
        ->call('ask')->assertHasErrors(['model']);

    expect(AiQuestion::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('bietet nur KIs mit Schluessel an und wechselt mit der KI das Modell', function () {
    config(['services.anthropic.key' => 'sk-ant-test']);

    questionBox($this->country)
        ->assertSee('ChatGPT')
        ->assertSee('Claude')
        ->assertDontSee('Gemini')
        ->set('provider', 'anthropic')
        ->assertSet('model', 'claude-opus-5-5');
});

it('beantwortet die Frage im Hintergrund und zeigt die Antwort unter dem Feld', function () {
    $component = questionBox($this->country)
        ->set('question', 'Wie viel Trinkgeld gibt man im Restaurant?')
        ->call('ask');

    $question = AiQuestion::sole();
    expect($question->status)->toBe(AiQuestion::STATUS_DONE)
        ->and($question->sources)->toBe([['url' => 'https://www.spain.info/trinkgeld', 'title' => 'Spain.info']]);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/responses')
        && $request['model'] === 'gpt-5.4'
        && str_contains($request['input'], 'Länder › „Spanien“, Abschnitt „Trinkgeld“')
        && str_contains($request['input'], 'Wie viel Trinkgeld gibt man im Restaurant?'));

    $component->call('refreshQuestions')
        ->assertDispatched('adminv2-toast')
        ->assertSet('openIds', [])
        ->assertSeeHtml('<strong>5–10 %</strong>')
        ->assertDontSeeHtml('<script>alert(1)</script>')
        ->assertSee('Quellen (1)');
});

it('meldet einen Fehler der KI an der Frage', function () {
    // Ohne Claude-Schluessel scheitert der Aufruf – die Meldung steht an der Frage.
    $question = AiQuestion::factory()->for($this->admin)->create(['provider' => 'anthropic', 'model' => 'claude-opus-5-5']);

    (new AskAiQuestionJob($question->id))->handle();

    expect($question->fresh())
        ->status->toBe(AiQuestion::STATUS_FAILED)
        ->error->toContain('kein API-Schlüssel')
        ->failureMessage()->toContain('kein API-Schlüssel');
});

it('bearbeitet eine Frage nur einmal, auch wenn die Queue sie erneut ausliefert', function () {
    $question = AiQuestion::factory()->for($this->admin)->answered('Schon beantwortet.')->create();

    (new AskAiQuestionJob($question->id))->handle();

    expect($question->fresh()->answer)->toBe('Schon beantwortet.');
    Http::assertNothingSent();
});

it('zeigt und loescht nur die eigenen Fragen', function () {
    $subject = ['subject_type' => $this->country->getMorphClass(), 'subject_id' => $this->country->id, 'section' => 'tipping'];
    $own = AiQuestion::factory()->for($this->admin)->answered('Eigene Antwort')->create($subject + ['question' => 'Eigene Frage?']);
    $foreign = AiQuestion::factory()->answered('Fremde Antwort')->create($subject + ['question' => 'Fremde Frage?']);

    questionBox($this->country)
        ->assertSee('Eigene Frage?')
        ->assertDontSee('Fremde Frage?')
        ->call('deleteQuestion', $foreign->id)
        ->call('deleteQuestion', $own->id)
        ->assertDontSee('Eigene Frage?');

    expect(AiQuestion::pluck('id')->all())->toBe([$foreign->id]);
});

it('laesst nur Administratoren fragen', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false, 'is_active' => true]));

    questionBox($this->country)->assertForbidden();
});
