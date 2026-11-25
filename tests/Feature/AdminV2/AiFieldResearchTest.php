<?php

use App\Jobs\AskAiQuestionJob;
use App\Livewire\AdminV2\MasterData\Countries\Editor as CountryEditor;
use App\Models\AiFieldPrompt;
use App\Models\AiQuestion;
use App\Models\Continent;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * KI-Recherche je Feld des Risikoprofils: Prompt mit Platzhaltern testen, als
 * Vorlage fuer alle Laender speichern, Vorschlag uebernehmen – und alles im
 * Verlauf nachvollziehen.
 */
const CRIME = 'risk_security_crime_level';

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true, 'is_active' => true, 'name' => 'Dana Redaktion']);
    $this->actingAs($this->admin);

    config(['services.openai.key' => 'test-key', 'services.anthropic.key' => null, 'services.gemini.key' => null]);

    $continent = Continent::create(['name_translations' => ['de' => 'Europa', 'en' => 'Europe'], 'code' => 'EU', 'sort_order' => 0]);
    $this->spain = Country::create(['name_translations' => ['de' => 'Spanien', 'en' => 'Spain'], 'iso_code' => 'ES', 'iso3_code' => 'ESP', 'continent_id' => $continent->id]);
    $this->france = Country::create(['name_translations' => ['de' => 'Frankreich', 'en' => 'France'], 'iso_code' => 'FR', 'iso3_code' => 'FRA', 'continent_id' => $continent->id]);
});

/**
 * OpenAI antwortet mit dem gegebenen Text und einer Quelle.
 */
function fakeOpenAi(string $text): void
{
    Http::fake([
        'api.openai.com/v1/models' => Http::response(['data' => [['id' => 'gpt-5.4', 'created' => 1760000000]]]),
        'api.openai.com/v1/responses' => Http::response([
            'model' => 'gpt-5.4',
            'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => $text, 'annotations' => [
                ['type' => 'url_citation', 'url' => 'https://www.auswaertiges-amt.de/spanien', 'title' => 'AA Spanien', 'start_index' => 0, 'end_index' => 1],
            ]]]]],
            'usage' => ['input_tokens' => 1000, 'output_tokens' => 200, 'total_tokens' => 1200],
        ]),
    ]);
}

function countryEditor(Country $country): \Livewire\Features\SupportTesting\Testable
{
    return Livewire::test(CountryEditor::class, ['country' => $country->id]);
}

it('sendet den Prompt mit aufgeloesten Platzhaltern und dem verlangten Format in die Queue', function () {
    fakeOpenAi('{}');
    Queue::fake();

    countryEditor($this->spain)
        ->call('openAiResearch', CRIME)
        ->assertSet('aiResearch.'.CRIME.'.provider', 'openai')
        ->assertSet('aiResearch.'.CRIME.'.model', 'gpt-5.4')
        ->set('aiResearch.'.CRIME.'.prompt', 'Wie hoch ist die Kriminalität in {name} ({iso_code})? {gibtsnicht}')
        ->assertSee('Unbekannte Platzhalter bleiben stehen: {gibtsnicht}')
        ->call('sendAiResearch', CRIME)
        ->assertHasNoErrors()
        ->assertSee('Wartet auf Bearbeitung');

    $run = AiQuestion::sole();
    expect($run)
        ->field->toBe(CRIME)
        ->section->toBe('risk_security')
        ->subject_id->toBe($this->spain->id)
        ->question->toBe('Wie hoch ist die Kriminalität in {name} ({iso_code})? {gibtsnicht}')
        ->prompt->toContain('Wie hoch ist die Kriminalität in Spanien (ES)? {gibtsnicht}')
        ->prompt->toContain('„Sicherheit › Kriminalitätsniveau“')
        ->prompt->toContain('3 – Mittel')
        ->prompt->toContain('"note"');

    Queue::assertPushed(AskAiQuestionJob::class, fn (AskAiQuestionJob $job) => $job->questionId === $run->id);
});

it('liefert Wert, Begruendung, Quellen und Kosten und laesst sie per Schaltflaeche uebernehmen', function () {
    fakeOpenAi("```json\n{\"value\": \"3 – Mittel\", \"note\": \"Taschendiebstahl in Touristenzentren ist häufig.\"}\n```");

    $component = countryEditor($this->spain)
        ->set('riskProfile.security.crime_level', '2')
        ->call('openAiResearch', CRIME)
        ->call('sendAiResearch', CRIME)
        ->assertDispatched('adminv2-toast')
        ->assertSet('aiResearchOpenIds', [])
        ->assertSee('Vorschlag: 3 – Mittel')
        ->assertSee('Taschendiebstahl in Touristenzentren ist häufig.');

    $run = AiQuestion::sole();
    expect($run->value)->toBe('3 – Mittel')
        ->and($run->sources)->toBe([['url' => 'https://www.auswaertiges-amt.de/spanien', 'title' => 'AA Spanien']])
        ->and($run->usage['total_tokens'])->toBe(1200)
        ->and($run->cost())->not->toBeNull();

    $component->call('applyAiResearch', $run->id)
        ->assertSet('riskProfile.security.crime_level', '3')
        ->assertSet('riskProfile.security.notes.crime_level.de', 'Taschendiebstahl in Touristenzentren ist häufig.')
        ->assertSee('übernommen, nicht gespeichert');

    expect($run->fresh())
        ->previous_value->toBe('2 – Niedrig')
        ->applied_value->toBe('3 – Mittel')
        ->applied_by->toBe($this->admin->id)
        ->saved_at->toBeNull();

    $component->call('save')->assertHasNoErrors();

    expect($run->fresh()->saved_at)->not->toBeNull()
        ->and($this->spain->fresh()->risk_profile['security']['crime_level'])->toBe(3);
});

it('uebernimmt keinen Vorschlag, der nicht ins Feld passt', function () {
    fakeOpenAi('{"value": "ziemlich gefährlich", "note": "Unklar."}');

    $component = countryEditor($this->spain)->call('openAiResearch', CRIME)->call('sendAiResearch', CRIME)
        ->assertSee('Passt nicht ins Feld');

    $component->call('applyAiResearch', AiQuestion::sole()->id)
        ->assertDispatched('adminv2-toast', variant: 'danger')
        ->assertSet('riskProfile.security.crime_level', '');

    expect(AiQuestion::sole()->applied_at)->toBeNull();
});

it('speichert einen guten Prompt als Vorlage und nutzt ihn bei anderen Laendern', function () {
    fakeOpenAi('{}');
    Queue::fake();

    countryEditor($this->spain)
        ->call('openAiResearch', CRIME)
        ->set('aiResearch.'.CRIME.'.prompt', 'Kriminalität in {name} laut Auswärtigem Amt?')
        ->call('saveAiResearchTemplate', CRIME, true)
        ->assertHasErrors(['aiResearch.'.CRIME.'.name' => 'required'])
        ->set('aiResearch.'.CRIME.'.name', 'Kriminalität nach AA')
        ->call('saveAiResearchTemplate', CRIME, true)
        ->assertHasNoErrors();

    $template = AiFieldPrompt::sole();
    // Die erste Vorlage eines Feldes ist dessen Standard.
    expect($template)->field->toBe(CRIME)->is_default->toBeTrue()->model->toBe('gpt-5.4')->created_by->toBe($this->admin->id);

    countryEditor($this->france)
        ->call('openAiResearch', CRIME)
        ->assertSet('aiResearch.'.CRIME.'.template', (string) $template->id)
        ->assertSet('aiResearch.'.CRIME.'.prompt', 'Kriminalität in {name} laut Auswärtigem Amt?')
        ->assertSee('Kriminalität in Frankreich laut Auswärtigem Amt?')
        ->call('sendAiResearch', CRIME);

    expect(AiQuestion::sole())
        ->ai_field_prompt_id->toBe($template->id)
        ->subject_id->toBe($this->france->id)
        ->prompt->toContain('Kriminalität in Frankreich laut Auswärtigem Amt?');
});

it('aendert eine Vorlage, macht eine andere zum Standard und behaelt den Verlauf beim Loeschen', function () {
    fakeOpenAi('{}');
    Queue::fake();
    $first = AiFieldPrompt::factory()->default()->create(['field' => CRIME, 'name' => 'Erste']);

    $component = countryEditor($this->spain)
        ->call('openAiResearch', CRIME)
        ->set('aiResearch.'.CRIME.'.prompt', 'Geänderter Prompt für {name}.')
        ->call('saveAiResearchTemplate', CRIME);

    expect($first->fresh()->prompt)->toBe('Geänderter Prompt für {name}.');

    $component->set('aiResearch.'.CRIME.'.name', 'Zweite')
        ->set('aiResearch.'.CRIME.'.default', true)
        ->call('saveAiResearchTemplate', CRIME, true)
        ->call('sendAiResearch', CRIME);

    $second = AiFieldPrompt::where('name', 'Zweite')->sole();
    expect($second->is_default)->toBeTrue()->and($first->fresh()->is_default)->toBeFalse();

    $component->call('deleteAiResearchTemplate', CRIME);

    expect(AiFieldPrompt::whereKey($second->id)->exists())->toBeFalse()
        ->and(AiQuestion::sole())->ai_field_prompt_id->toBeNull()->question->toBe('Geänderter Prompt für {name}.');
});

it('weist Anfragen ohne Prompt oder mit einer KI ohne Schluessel ab', function () {
    fakeOpenAi('{}');
    Queue::fake();

    countryEditor($this->spain)
        ->call('openAiResearch', CRIME)
        ->set('aiResearch.'.CRIME.'.prompt', '')
        ->call('sendAiResearch', CRIME)
        ->assertHasErrors(['aiResearch.'.CRIME.'.prompt' => 'required'])
        ->set('aiResearch.'.CRIME.'.prompt', 'Eine ausreichend lange Frage?')
        ->set('aiResearch.'.CRIME.'.provider', 'anthropic')
        ->call('sendAiResearch', CRIME)
        ->assertHasErrors(['aiResearch.'.CRIME.'.provider']);

    Queue::assertNothingPushed();
});

it('zeigt im Verlauf Prompt, Antwort, Quellen und die Uebernahme mit vorher und nachher', function () {
    fakeOpenAi('{}');
    $subject = ['subject_type' => $this->spain->getMorphClass(), 'subject_id' => $this->spain->id, 'section' => 'risk_security', 'field' => CRIME];
    AiQuestion::factory()->for($this->admin)->answered('{"value": "3"}')->create($subject + [
        'prompt' => 'Gesendet: Kriminalität in Spanien',
        'value' => '3',
        'sources' => [['url' => 'https://example.org/quelle', 'title' => 'Quelle A']],
        'previous_value' => '2 – Niedrig',
        'applied_value' => '3 – Mittel',
        'applied_at' => now(),
        'applied_by' => $this->admin->id,
        'saved_at' => now(),
    ]);
    AiQuestion::factory()->answered('Anderes Land')->create(['subject_type' => $this->france->getMorphClass(), 'subject_id' => $this->france->id, 'field' => CRIME, 'prompt' => 'Gesendet: Frankreich']);

    countryEditor($this->spain)
        ->call('showAiResearchHistory', CRIME)
        ->assertSee('Gesendet: Kriminalität in Spanien')
        ->assertSee('Quelle A')
        ->assertSee('„2 – Niedrig“ → „3 – Mittel“', false)
        ->assertSee('übernommen &amp; gespeichert', false)
        ->assertSee('Dana Redaktion')
        ->assertDontSee('Gesendet: Frankreich');
});

it('zerlegt die Antwort der KI in Wert und Begruendung', function (string $answer, ?string $value, ?string $note) {
    expect(AskAiQuestionJob::parseSuggestion($answer))->toBe(['value' => $value, 'note' => $note]);
})->with([
    'Codeblock' => ["Hier:\n```json\n{\"value\": \"4 – Hoch\", \"note\": \"Lage angespannt.\"}\n```", '4 – Hoch', 'Lage angespannt.'],
    'nicht feststellbar' => ['{"value": null, "note": "Keine Daten."}', null, 'Keine Daten.'],
    'Ja/Nein' => ['{"value": true}', 'Ja', null],
    'kein JSON' => ['Die Kriminalität ist mittel.', null, null],
]);

it('behaelt das Protokoll, wenn der Benutzer geloescht wird', function () {
    $user = User::factory()->create();
    $run = AiQuestion::factory()->for($user)->answered()->create(['field' => CRIME]);

    $user->delete();

    expect($run->fresh())->not->toBeNull()->user_id->toBeNull();
});

it('recherchiert auch Felder ausserhalb des Risikoprofils und uebernimmt sie ins Formular', function () {
    fakeOpenAi('{"value": "eur", "note": "Amtliche Währung laut Zentralbank."}');

    $component = countryEditor($this->spain)
        ->call('openAiResearch', 'currency_code')
        ->call('sendAiResearch', 'currency_code')
        ->assertSee('Vorschlag: eur');

    $run = AiQuestion::sole();
    expect($run->section)->toBe('details')
        ->and($run->prompt)->toContain('„Weitere Informationen › Währungscode“')
        ->and($run->prompt)->toContain('ISO-4217-Code');

    $component->call('applyAiResearch', $run->id)
        ->assertSet('currencyCode', 'EUR');

    expect($run->fresh()->applied_value)->toBe('EUR');
});

it('verlangt Texte in der Sprache des Feldes und gibt die Hinweise des Abschnitts mit', function () {
    fakeOpenAi('{}');
    Queue::fake();

    countryEditor($this->spain)
        ->call('openAiResearch', 'description_en')
        ->call('sendAiResearch', 'description_en');

    expect(AiQuestion::sole()->prompt)
        ->toContain('Fließtext auf Englisch')
        ->toContain('Hinweise zum Abschnitt:');
});

it('zeigt Ergebnisse zu Listenfeldern nur zur Information', function () {
    fakeOpenAi('{"value": "Cabify, FreeNow", "note": "Beide in Madrid verbreitet."}');

    $component = countryEditor($this->spain)
        ->call('openAiResearch', 'taxi_apps')
        ->call('sendAiResearch', 'taxi_apps')
        ->assertSee('Cabify, FreeNow')
        ->assertSee('Zur Information – bitte von Hand pflegen.')
        ->assertDontSee('Übernehmen');

    $component->call('applyAiResearch', AiQuestion::sole()->id);

    expect(AiQuestion::sole()->applied_at)->toBeNull();
});

it('bietet die KI-Recherche erst an gespeicherten Laendern an', function () {
    fakeOpenAi('{}');

    Livewire::test(CountryEditor::class)
        ->assertOk()
        ->assertDontSee('KI-Recherche');

    countryEditor($this->spain)->assertSee('KI-Recherche: Breitengrad');
});

it('rendert die Schaltflaechen der KI-Recherche mit ausfuehrbaren Aufrufen', function () {
    fakeOpenAi('{}');
    $key = "'risk_security_crime_level'";

    $component = countryEditor($this->spain)
        ->assertSeeHtml('$wire.openAiResearch('.$key.')')
        ->call('openAiResearch', CRIME)
        ->assertSeeHtml('wire:click="sendAiResearch('.$key.')"');

    expect($component->html())->not->toContain('@js(');
});
