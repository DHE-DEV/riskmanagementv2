<?php

use App\Livewire\AdminV2\System\Ai;
use App\Models\AiEventSearch;
use App\Models\AiEventSuggestion;
use App\Models\Country;
use App\Models\EventType;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AiEventSearchService;
use App\Support\AiProviders;
use App\Support\AiSettings;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * KI-Suche nach Ereignissen mit Claude (Anthropic) und Gemini (Google):
 * das Modell der Vorlage bestimmt den Anbieter.
 */
function providerSearchEvents(): string
{
    return json_encode(['events' => [[
        'title' => 'Streik legt Nahverkehr in Rom lahm',
        'summary' => 'In Rom streiken Bus- und Bahnpersonal.',
        'priority' => 'medium',
        'start_date' => '2026-10-03',
        'end_date' => '2026-10-04',
        'event_types' => ['strike'],
        'countries' => ['IT'],
        'location' => 'Rom',
        'sources' => [['title' => 'ANSA', 'url' => 'https://ansa.example/rom-streik']],
    ]]]);
}

/**
 * Eine Antwort der Anthropic-API, wie sie nach einer Websuche kommt.
 */
function claudeMessage(array $content, string $stopReason = 'end_turn', string $model = 'claude-opus-5-5'): Response
{
    return new Response(200, ['Content-Type' => 'application/json', 'request-id' => 'req_test'], json_encode([
        'id' => 'msg_'.uniqid(),
        'type' => 'message',
        'role' => 'assistant',
        'model' => $model,
        'content' => $content,
        'stop_reason' => $stopReason,
        'stop_sequence' => null,
        'usage' => ['input_tokens' => 10000, 'output_tokens' => 2000],
    ]));
}

/**
 * Hinterlegt einen Anthropic-Client, dessen Anfragen nicht ins Netz gehen.
 *
 * @return array<int, array{request: \Psr\Http\Message\RequestInterface}> die gesendeten Anfragen
 */
function fakeClaude(Response ...$responses): ArrayObject
{
    $history = new ArrayObject;
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(Middleware::history($history));

    app()->instance(Anthropic\Client::class, new Anthropic\Client(
        apiKey: 'sk-ant-test-1234567890',
        requestOptions: ['transporter' => new GuzzleHttp\Client(['handler' => $stack]), 'maxRetries' => 0],
    ));

    return $history;
}

function runProviderSearch(string $model): AiEventSearch
{
    $search = AiEventSearch::create(['status' => AiEventSearch::STATUS_RUNNING, 'exclude_existing' => false, 'model' => $model]);

    return app(AiEventSearchService::class)->run($search)->fresh();
}

beforeEach(function () {
    Carbon::setTestNow('2026-10-02 10:00:00');
    SystemSetting::flushResolved();
    Http::preventStrayRequests();

    SystemSetting::write(AiSettings::KEY_API_KEY, 'sk-test-1234567890', encrypted: true);
    SystemSetting::write(AiSettings::KEY_MODEL, 'gpt-4o');

    Country::factory()->create(['iso_code' => 'IT', 'iso3_code' => 'ITA', 'name_translations' => ['de' => 'Italien', 'en' => 'Italy']]);
    EventType::create(['code' => 'strike', 'name' => 'Streik', 'icon' => 'fa-x', 'color' => '#000000', 'is_active' => true, 'sort_order' => 1]);
});

it('ordnet Modelle ihrem Anbieter zu', function () {
    expect(AiProviders::forModel('claude-opus-5-5'))->toBe(AiProviders::ANTHROPIC)
        ->and(AiProviders::forModel('gemini-flash'))->toBe(AiProviders::GEMINI)
        ->and(AiProviders::forModel('gpt-4o'))->toBe(AiProviders::OPENAI)
        ->and(AiProviders::forModel(null))->toBe(AiProviders::OPENAI);
});

it('sucht mit Claude im Internet und uebernimmt Vorschlaege, Verbrauch und Kosten', function () {
    $answer = providerSearchEvents();
    $history = fakeClaude(claudeMessage([
        ['type' => 'text', 'text' => 'Ich suche nach aktuellen Meldungen {vorab}.'],
        ['type' => 'server_tool_use', 'id' => 'srvtoolu_1', 'name' => 'web_search', 'input' => ['query' => 'Streik Rom']],
        ['type' => 'web_search_tool_result', 'tool_use_id' => 'srvtoolu_1', 'content' => [
            ['type' => 'web_search_result', 'url' => 'https://ansa.example/rom-streik', 'title' => 'ANSA', 'encrypted_content' => 'x', 'page_age' => null],
        ]],
        // Belegstellen teilen den Text in mehrere Bloecke.
        ['type' => 'text', 'text' => mb_substr($answer, 0, 40), 'citations' => [
            ['type' => 'web_search_result_location', 'url' => 'https://ansa.example/rom-streik', 'title' => 'ANSA', 'cited_text' => 'Streik', 'encrypted_index' => 'y'],
        ]],
        ['type' => 'text', 'text' => mb_substr($answer, 40)],
    ]));

    $search = runProviderSearch('claude-opus-5-5');

    expect($search->status)->toBe(AiEventSearch::STATUS_DONE)
        ->and($search->new_count)->toBe(1)
        ->and($search->model)->toBe('claude-opus-5-5')
        ->and($search->input_tokens)->toBe(10000)
        ->and($search->output_tokens)->toBe(2000)
        // 10.000 × 4 $ + 2.000 × 20 $ je 1 Mio. Token
        ->and((float) $search->cost)->toBe(0.08)
        ->and(AiEventSuggestion::query()->value('title'))->toBe('Streik legt Nahverkehr in Rom lahm');

    $request = $history[0]['request'];
    $body = json_decode((string) $request->getBody(), true);

    expect((string) $request->getUri())->toContain('/v1/messages')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-ant-test-1234567890')
        ->and($request->getHeaderLine('anthropic-beta'))->toContain('server-side-fallback-2026-07-01')
        ->and($body['model'])->toBe('claude-opus-5-5')
        ->and($body['fallbacks'])->toBe('default')
        ->and($body['tools'][0])->toMatchArray(['type' => 'web_search_20260209', 'name' => 'web_search'])
        ->and($body['messages'][0]['content'])->toContain('Antworte AUSSCHLIESSLICH mit JSON');
});

it('setzt eine unterbrochene Claude-Suche mit dem bisherigen Verlauf fort', function () {
    $history = fakeClaude(
        claudeMessage([
            ['type' => 'server_tool_use', 'id' => 'srvtoolu_1', 'name' => 'web_search', 'input' => ['query' => 'Streik Rom']],
        ], 'pause_turn'),
        claudeMessage([['type' => 'text', 'text' => providerSearchEvents()]]),
    );

    $search = runProviderSearch('claude-opus-5-5');

    expect($search->status)->toBe(AiEventSearch::STATUS_DONE)
        ->and($search->new_count)->toBe(1)
        ->and($search->input_tokens)->toBe(20000)
        ->and($history)->toHaveCount(2);

    $second = json_decode((string) $history[1]['request']->getBody(), true);

    expect($second['messages'])->toHaveCount(2)
        ->and($second['messages'][1]['role'])->toBe('assistant')
        ->and($second['messages'][1]['content'][0]['type'])->toBe('server_tool_use');
});

it('fragt Claude Haiku ohne Ersatzmodell', function () {
    $history = fakeClaude(claudeMessage([['type' => 'text', 'text' => providerSearchEvents()]], model: 'claude-haiku-5-5'));

    runProviderSearch('claude-haiku-5-5');

    $body = json_decode((string) $history[0]['request']->getBody(), true);

    expect($body)->not->toHaveKey('fallbacks')
        ->and($history[0]['request']->getHeaderLine('anthropic-beta'))->toBe('');
});

it('haelt einen Fehler von Claude am Suchlauf fest', function () {
    fakeClaude(new Response(401, ['Content-Type' => 'application/json'], json_encode([
        'type' => 'error', 'error' => ['type' => 'authentication_error', 'message' => 'invalid x-api-key'],
    ])));

    $search = runProviderSearch('claude-opus-5-5');

    expect($search->status)->toBe(AiEventSearch::STATUS_FAILED)
        ->and($search->error)->toContain('Claude-Fehler');
});

it('meldet einen fehlenden Claude-Schluessel', function () {
    $search = runProviderSearch('claude-opus-5-5');

    expect($search->status)->toBe(AiEventSearch::STATUS_FAILED)
        ->and($search->error)->toContain('kein API-Schlüssel');
});

it('sucht mit Gemini und der Google-Suche', function () {
    SystemSetting::write(AiProviders::PROVIDERS[AiProviders::GEMINI]['setting'], 'AIza-test-1234567890abc', encrypted: true);

    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
        'candidates' => [[
            'content' => ['role' => 'model', 'parts' => [
                ['text' => 'Ich prüfe die Lage.', 'thought' => true],
                ['text' => "```json\n".providerSearchEvents()."\n```"],
            ]],
            'finishReason' => 'STOP',
            'groundingMetadata' => ['groundingChunks' => [['web' => ['uri' => 'https://ansa.example/rom-streik', 'title' => 'ansa.example']]]],
        ]],
        'usageMetadata' => ['promptTokenCount' => 8000, 'candidatesTokenCount' => 1500, 'thoughtsTokenCount' => 500, 'totalTokenCount' => 10000],
        'modelVersion' => 'gemini-test-pro',
    ])]);

    $search = runProviderSearch('gemini-test-pro');

    expect($search->status)->toBe(AiEventSearch::STATUS_DONE)
        ->and($search->new_count)->toBe(1)
        ->and($search->model)->toBe('gemini-test-pro')
        ->and($search->input_tokens)->toBe(8000)
        ->and($search->output_tokens)->toBe(2000);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/models/gemini-test-pro:generateContent')
        && $request->header('x-goog-api-key') === ['AIza-test-1234567890abc']
        && array_key_exists('google_search', $request['tools'][0]));
});

it('hinterlegt unter System > KI Schluessel fuer Claude und Gemini und bietet deren Modelle in den Vorlagen an', function () {
    Http::fake(['generativelanguage.googleapis.com/v1beta/models*' => Http::response(['models' => [
        ['name' => 'models/gemini-test-pro', 'displayName' => 'Gemini Test Pro', 'supportedGenerationMethods' => ['generateContent']],
        ['name' => 'models/gemini-embedding-test', 'displayName' => 'Embedding', 'supportedGenerationMethods' => ['embedContent']],
    ]])]);

    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));

    $page = Livewire::test(Ai::class)
        ->assertSee('Claude und Gemini')
        ->set('newProviderKeys.anthropic', 'kurz')
        ->call('saveProviderKey', 'anthropic')
        ->assertHasErrors('newProviderKeys.anthropic')
        ->set('newProviderKeys.anthropic', 'sk-ant-api-1234567890abcdef')
        ->call('saveProviderKey', 'anthropic')
        ->assertHasNoErrors()
        ->set('newProviderKeys.gemini', 'AIza-test-1234567890abc')
        ->call('saveProviderKey', 'gemini')
        ->assertHasNoErrors();

    expect(AiProviders::apiKey(AiProviders::ANTHROPIC))->toBe('sk-ant-api-1234567890abcdef')
        ->and(SystemSetting::query()->where('key', 'ai.anthropic.api_key')->value('value'))->not->toContain('sk-ant-api');

    $ids = array_column($page->instance()->promptModelOptions(), 'id');

    expect($ids)->toContain('gpt-4o')
        ->toContain('claude-opus-5-5')
        ->toContain('gemini-test-pro')
        ->not->toContain('gemini-embedding-test')
        // KI-Pruefungen laufen weiter nur ueber OpenAI.
        ->and($page->instance()->checkModelOptions())->each->not->toStartWith('claude-');

    $page->call('removeProviderKey', 'anthropic');

    expect(AiProviders::apiKey(AiProviders::ANTHROPIC))->toBeNull();
});
