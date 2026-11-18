<?php

use App\Livewire\AdminV2\System\EventTypes\Editor;
use App\Livewire\AdminV2\System\EventTypes\Index;
use App\Models\EventType;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * System > Event-Typen: Namen je Sprache aus EVENT_LANGUAGES pflegen, fehlende per DeepL füllen.
 */
function eventTypeAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'is_active' => true]);
}

function strikeType(array $attributes = []): EventType
{
    return EventType::create(array_merge(['code' => 'strike', 'name' => 'Streik', 'is_active' => true, 'sort_order' => 1], $attributes));
}

it('zeigt die Event-Typen mit ihren Namen je Sprache und markiert fehlende', function () {
    strikeType(['name_translations' => ['en' => 'Strike']]);

    $response = $this->actingAs(eventTypeAdmin())->get(route('adminv2.system.event-types.index'));

    $response->assertOk()
        ->assertSee('Streik')
        ->assertSee('Strike')
        ->assertSee('fehlt');
});

it('speichert den deutschen Namen in name und die übrigen Sprachen in name_translations', function () {
    $type = strikeType();

    Livewire::actingAs(eventTypeAdmin())
        ->test(Editor::class, ['type' => $type->id])
        ->set('names.de', 'Arbeitskampf')
        ->set('names.en', 'Strike')
        ->set('names.nl', '')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('adminv2.system.event-types.index'));

    $type->refresh();
    expect($type->name)->toBe('Arbeitskampf')
        ->and($type->name_translations)->toBe(['en' => 'Strike']);
});

it('legt einen neuen Event-Typ an und prüft Code und deutschen Namen', function () {
    strikeType();

    Livewire::actingAs(eventTypeAdmin())
        ->test(Editor::class)
        ->set('code', 'strike')
        ->set('names.de', '')
        ->call('save')
        ->assertHasErrors(['code' => 'unique', 'names.de' => 'required']);

    Livewire::actingAs(eventTypeAdmin())
        ->test(Editor::class)
        ->set('code', 'Health')
        ->set('names.de', 'Gesundheit')
        ->call('save')
        ->assertHasNoErrors();

    expect(EventType::where('code', 'health')->value('name'))->toBe('Gesundheit');
});

it('füllt leere Sprachen per DeepL aus dem deutschen Namen, ohne vorhandene zu überschreiben', function () {
    config(['services.deepl.api_key' => 'test-key', 'services.deepl.api_url' => 'https://api.deepl.test/v2/translate']);
    Http::fake(['api.deepl.test/*' => fn (Request $request) => Http::response(['translations' => [['text' => $request['target_lang'].':'.$request['text'][0]]]])]);
    $type = strikeType(['name_translations' => ['en' => 'Walkout']]);

    $component = Livewire::actingAs(eventTypeAdmin())
        ->test(Editor::class, ['type' => $type->id])
        ->call('translateMissing');

    $component->assertSet('names.en', 'Walkout')
        ->assertSet('names.nl', 'NL:Streik');
    expect($type->refresh()->name_translations)->toBe(['en' => 'Walkout']);
});

it('deaktiviert einen Event-Typ statt ihn zu löschen', function () {
    $type = strikeType();

    Livewire::actingAs(eventTypeAdmin())->test(Index::class)->call('toggleActive', $type->id);

    expect($type->refresh()->is_active)->toBeFalse();
});
