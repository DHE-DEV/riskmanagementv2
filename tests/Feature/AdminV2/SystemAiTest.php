<?php

use App\Livewire\AdminV2\System\Ai;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ChatGptService;
use App\Services\OpenAiModelService;
use App\Support\AiSettings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * System > KI: Schluessel und Modell werden im Admin-Bereich gepflegt und
 * gelten fuer alle KI-Funktionen; die .env ist nur noch der Rueckfall.
 */
beforeEach(function () {
    SystemSetting::flushResolved();
    Cache::flush();
    config(['services.openai.key' => null, 'services.openai.model' => null]);

    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));
});

function fakeOpenAiModels(): void
{
    Http::fake([
        'api.openai.com/v1/models' => Http::response(['data' => [
            ['id' => 'gpt-4o-mini', 'created' => 1721172741],
            ['id' => 'gpt-4.1', 'created' => 1744316542],
            ['id' => 'o3-mini', 'created' => 1737146383],
            ['id' => 'text-embedding-3-small', 'created' => 1705948997],
            ['id' => 'whisper-1', 'created' => 1677532384],
            ['id' => 'gpt-4o-audio-preview', 'created' => 1727460443],
            ['id' => 'dall-e-3', 'created' => 1698785189],
        ]]),
        'api.openai.com/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'bereit']]]]),
    ]);
}

it('speichert den Schluessel verschluesselt und zeigt ihn nur unkenntlich', function () {
    fakeOpenAiModels();

    Livewire::test(Ai::class)
        ->assertSee('Fehlt')
        ->set('newApiKey', 'sk-test-1234567890abcdefGHIJ')
        ->call('saveApiKey')
        ->assertHasNoErrors()
        ->assertSet('newApiKey', '')
        ->assertSee('Hinterlegt')
        ->assertSee('sk-…GHIJ')
        ->assertDontSee('sk-test-1234567890abcdefGHIJ');

    $stored = DB::table('system_settings')->where('key', AiSettings::KEY_API_KEY)->first();

    expect($stored->value)->not->toContain('sk-test')
        ->and((bool) $stored->is_encrypted)->toBeTrue()
        ->and(AiSettings::apiKey())->toBe('sk-test-1234567890abcdefGHIJ')
        ->and(AiSettings::apiKeySource())->toBe('admin');
});

it('faellt ohne hinterlegten Schluessel auf die .env zurueck', function () {
    config(['services.openai.key' => 'sk-env-0000000000000000WXYZ', 'services.openai.model' => 'gpt-4o']);

    expect(AiSettings::apiKey())->toBe('sk-env-0000000000000000WXYZ')
        ->and(AiSettings::apiKeySource())->toBe('env')
        ->and(AiSettings::model())->toBe('gpt-4o')
        ->and(AiSettings::modelSource())->toBe('env');

    SystemSetting::write(AiSettings::KEY_API_KEY, 'sk-admin-111111111111111ABCD', encrypted: true);
    SystemSetting::write(AiSettings::KEY_MODEL, 'gpt-4.1');

    expect(AiSettings::apiKey())->toBe('sk-admin-111111111111111ABCD')
        ->and(AiSettings::model())->toBe('gpt-4.1');

    // Entfernen stellt den Rueckfall wieder her.
    Livewire::test(Ai::class)->call('removeApiKey');

    expect(AiSettings::apiKeySource())->toBe('env');
});

it('listet nur Chat-Modelle des Schluessels und speichert die Auswahl', function () {
    fakeOpenAiModels();
    SystemSetting::write(AiSettings::KEY_API_KEY, 'sk-admin-111111111111111ABCD', encrypted: true);

    expect(array_column(app(OpenAiModelService::class)->chatModels(), 'id'))->toBe(['gpt-4.1', 'o3-mini', 'gpt-4o-mini']);

    Livewire::test(Ai::class)
        ->assertSee('gpt-4.1')
        ->assertSee('o3-mini')
        ->assertDontSee('whisper-1')
        ->assertDontSee('text-embedding-3-small')
        ->assertSee('Standard')
        ->set('modelSearch', 'mini')
        ->assertDontSee('gpt-4.1')
        ->set('modelSearch', '')
        ->set('model', 'gpt-4o-mini')
        ->call('saveModel')
        ->assertHasNoErrors();

    expect(AiSettings::model())->toBe('gpt-4o-mini')
        ->and(AiSettings::modelSource())->toBe('admin');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.openai.com/v1/models'
        && $request->hasHeader('Authorization', 'Bearer sk-admin-111111111111111ABCD'));
});

it('verwendet Schluessel und Modell aus dem Admin-Bereich fuer KI-Anfragen', function () {
    fakeOpenAiModels();
    SystemSetting::write(AiSettings::KEY_API_KEY, 'sk-admin-111111111111111ABCD', encrypted: true);
    SystemSetting::write(AiSettings::KEY_MODEL, 'gpt-4o-mini');

    expect(app(ChatGptService::class)->sendPrompt('Hallo', ['max_tokens' => 50]))->toBe('bereit');

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'chat/completions')
        && $request->hasHeader('Authorization', 'Bearer sk-admin-111111111111111ABCD')
        && $request['model'] === 'gpt-4o-mini'
        && $request['max_tokens'] === 50
        && isset($request['temperature']));

    // Neuere Modellreihen (o-Reihe, GPT-5 und spaeter) erwarten andere Parameter.
    foreach (['o3-mini', 'gpt-5.5', 'gpt-6.1-sol'] as $model) {
        SystemSetting::write(AiSettings::KEY_MODEL, $model);
        app(ChatGptService::class)->sendPrompt('Hallo', ['max_tokens' => 50]);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'chat/completions')
            && $request['model'] === $model
            && isset($request['max_completion_tokens'])
            && ! isset($request['max_tokens'])
            && ! isset($request['temperature']));
    }
});

it('zeigt beim Verbindungstest Erfolg und die Fehlermeldung von OpenAI', function () {
    SystemSetting::write(AiSettings::KEY_API_KEY, 'sk-admin-111111111111111ABCD', encrypted: true);
    fakeOpenAiModels();

    Livewire::test(Ai::class)->call('testConnection')->assertSee('Verbindung in Ordnung.');

    Http::swap(new \Illuminate\Http\Client\Factory);
    Http::fake([
        'api.openai.com/v1/models' => Http::response(['data' => []]),
        'api.openai.com/v1/chat/completions' => Http::response(['error' => ['message' => 'You have no credits remaining.']], 429),
    ]);

    Livewire::test(Ai::class)
        ->call('testConnection')
        ->assertSee('Verbindung fehlgeschlagen')
        ->assertSee('You have no credits remaining.');
});

it('speichert die Preise je Modell fuer die Kostenanzeige', function () {
    // Ohne Preisliste: es zaehlt nur, was von Hand hinterlegt wird.
    config(['ai_prices.models' => []]);

    SystemSetting::write(AiSettings::KEY_MODEL, 'gpt-4o-mini');

    Livewire::test(Ai::class)
        ->set('priceInput', '0,15')
        ->set('priceOutput', 'abc')
        ->call('savePrices')
        ->assertHasErrors('priceInput')
        ->set('priceOutput', '0,6')
        ->call('savePrices')
        ->assertHasNoErrors();

    expect(AiSettings::prices('gpt-4o-mini'))->toBe(['input' => 0.15, 'output' => 0.6])
        ->and(AiSettings::prices('gpt-4.1'))->toBeNull()
        ->and(AiSettings::cost('gpt-4o-mini', 1_000_000, 500_000))->toBe(0.45)
        // Rueckgabe mit Datumsstand zaehlt zum gewaehlten Modell.
        ->and(AiSettings::cost('gpt-4o-mini-2024-07-18', 1_000_000, 0))->toBe(0.15)
        ->and(AiSettings::cost('o3-mini', 1000, 1000))->toBeNull();

    // Beim erneuten Oeffnen stehen die Preise des aktiven Modells in den Feldern.
    Livewire::test(Ai::class)->assertSet('priceInput', '0,15')->assertSet('priceOutput', '0,6');
});

it('ist nur fuer Administratoren erreichbar', function () {
    $this->get('/adminv2/system/ai')->assertOk()->assertSee('API-Schlüssel');

    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->get('/adminv2/system/ai')
        ->assertForbidden();
});

it('kennt die Preise der Modelle aus der Preisliste – ein eigener Preis hat Vorrang', function () {
    SystemSetting::write(AiSettings::KEY_MODEL, 'gpt-6.1-sol');

    expect(AiSettings::prices('gpt-6.1-sol'))->toBe(['input' => 2.0, 'output' => 10.0])
        ->and(AiSettings::priceSource('gpt-6.1-sol'))->toBe('list')
        // 100.000 Eingabe- und 5.000 Ausgabe-Token
        ->and(AiSettings::cost('gpt-6.1-sol', 100_000, 5_000))->toBe(0.25)
        // Ein Modellstand mit Datum nutzt den Preis seines Modells – ausser er ist eigens gelistet.
        ->and(AiSettings::prices('gpt-5.5-2026-04-23'))->toBe(['input' => 5.0, 'output' => 30.0])
        ->and(AiSettings::prices('gpt-4o-2024-08-06'))->toBe(['input' => 2.5, 'output' => 10.0])
        ->and(AiSettings::prices('gpt-4o-2024-05-13'))->toBe(['input' => 5.0, 'output' => 15.0])
        // Nicht in der Preisliste
        ->and(AiSettings::prices('gpt-5.3-chat-latest'))->toBeNull()
        ->and(AiSettings::priceSource('gpt-live-1'))->toBeNull();

    // Die Seite zeigt den Listenpreis; die Felder bleiben leer.
    Livewire::test(Ai::class)
        ->assertSet('priceInput', '')
        ->assertSee('2 $ Eingabe, 10 $ Ausgabe')
        // Eigener Preis: hat Vorrang.
        ->set('priceInput', '3')
        ->set('priceOutput', '12,5')
        ->call('savePrices')
        ->assertHasNoErrors();

    expect(AiSettings::prices('gpt-6.1-sol'))->toBe(['input' => 3.0, 'output' => 12.5])
        ->and(AiSettings::priceSource('gpt-6.1-sol'))->toBe('admin');

    // Entfernen: es gilt wieder die Preisliste.
    Livewire::test(Ai::class)->set('priceInput', '')->set('priceOutput', '')->call('savePrices');

    expect(AiSettings::prices('gpt-6.1-sol'))->toBe(['input' => 2.0, 'output' => 10.0]);
});
