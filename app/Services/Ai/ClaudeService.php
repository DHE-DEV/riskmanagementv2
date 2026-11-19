<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use App\Support\AiProviders;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Websuche mit Claude (Anthropic) fuer die KI-Suche nach Ereignissen.
 *
 * Gleiche Schnittstelle wie ChatGptService::searchWeb(): Antworttext,
 * danach lastUsage() und lastSources(). Gesucht wird mit dem Werkzeug
 * "web_search" der Anthropic-API; Claude entscheidet selbst, wie oft.
 */
class ClaudeService
{
    /** Die Modelle, die fuer die Suche zur Auswahl stehen – neueste zuerst. */
    public const MODELS = [
        'claude-opus-5-5' => 'Claude Opus 5.5',
        'claude-sonnet-5-5' => 'Claude Sonnet 5.5',
        'claude-haiku-5-5' => 'Claude Haiku 5.5',
        'claude-fable-5-2' => 'Claude Fable 5.2',
    ];

    /**
     * Modelle mit serverseitigem Ersatz: lehnt das Modell eine Anfrage aus
     * Sicherheitsgruenden ab, beantwortet sie ein von Anthropic bestimmtes
     * Ersatzmodell im selben Aufruf.
     */
    private const MODELS_WITH_FALLBACK = ['claude-opus-5-5', 'claude-sonnet-5-5', 'claude-fable-5-2'];

    private const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

    /** So oft wird eine unterbrochene Suche (pause_turn) hoechstens fortgesetzt. */
    private const MAX_CONTINUATIONS = 5;

    /** @var array{model: string, input_tokens: int, output_tokens: int, total_tokens: int}|null */
    protected ?array $lastUsage = null;

    /** @var array<int, array{url: string, title: ?string, cited: bool, start: ?int}> */
    protected array $lastSources = [];

    public function lastUsage(): ?array
    {
        return $this->lastUsage;
    }

    public function lastSources(): array
    {
        return $this->lastSources;
    }

    /**
     * @param  array{model?: string, timeout?: int, max_searches?: int}  $options
     *
     * @throws Exception
     */
    public function searchWeb(string $prompt, array $options = []): string
    {
        $model = $options['model'] ?? array_key_first(self::MODELS);
        $this->lastUsage = null;
        $this->lastSources = [];

        $messages = [['role' => 'user', 'content' => $prompt]];
        $inputTokens = 0;
        $outputTokens = 0;
        $content = [];
        $servedBy = $model;

        try {
            for ($round = 0; $round <= self::MAX_CONTINUATIONS; $round++) {
                $withFallback = in_array($model, self::MODELS_WITH_FALLBACK, true);

                $message = $this->client()->beta->messages->create(
                    maxTokens: 16000,
                    messages: $messages,
                    model: $model,
                    fallbacks: $withFallback ? 'default' : null,
                    outputConfig: ['effort' => 'high'],
                    tools: [[
                        'type' => 'web_search_20260209',
                        'name' => 'web_search',
                        'maxUses' => $options['max_searches'] ?? 15,
                    ]],
                    betas: $withFallback ? [self::FALLBACK_BETA] : null,
                    requestOptions: ['timeout' => (float) ($options['timeout'] ?? 300)],
                );

                $inputTokens += (int) $message->usage->inputTokens + (int) $message->usage->cacheReadInputTokens + (int) $message->usage->cacheCreationInputTokens;
                $outputTokens += (int) $message->usage->outputTokens;
                $servedBy = (string) $message->model;
                $content = array_merge($content, $message->content);

                // Die Suche wurde unterbrochen: mit dem bisherigen Verlauf fortsetzen.
                if ($message->stopReason !== 'pause_turn') {
                    break;
                }

                $messages = [
                    ['role' => 'user', 'content' => $prompt],
                    ['role' => 'assistant', 'content' => $content],
                ];
            }
        } catch (APIException $e) {
            Log::error('Claude-Websuche fehlgeschlagen', ['model' => $model, 'message' => $e->getMessage()]);

            throw new Exception('Claude-Fehler: '.$e->getMessage(), previous: $e);
        }

        $this->lastUsage = [
            'model' => $servedBy,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $inputTokens + $outputTokens,
        ];

        if (($message->stopReason ?? null) === 'refusal') {
            throw new Exception('Claude hat die Anfrage abgelehnt.');
        }

        $text = $this->answerText($content);
        $this->lastSources = $this->sources($content);

        if (trim($text) === '') {
            throw new Exception('Claude hat keine auswertbare Antwort geliefert.');
        }

        return trim($text);
    }

    /**
     * Der Antworttext nach der letzten Suche. Belegstellen teilen den Text in
     * mehrere Bloecke – sie werden ohne Trennzeichen aneinandergehaengt, damit
     * ein JSON-Text heil bleibt.
     *
     * @param  array<int, object>  $content
     */
    protected function answerText(array $content): string
    {
        $lastToolBlock = -1;

        foreach ($content as $index => $block) {
            if (in_array($block->type ?? null, ['server_tool_use', 'web_search_tool_result'], true)) {
                $lastToolBlock = $index;
            }
        }

        $texts = [];
        foreach ($content as $index => $block) {
            if ($index > $lastToolBlock && ($block->type ?? null) === 'text') {
                $texts[] = (string) $block->text;
            }
        }

        // Kein Text nach der letzten Suche: dann aller Text.
        if (trim(implode('', $texts)) === '') {
            $texts = [];
            foreach ($content as $block) {
                if (($block->type ?? null) === 'text') {
                    $texts[] = (string) $block->text;
                }
            }
        }

        return implode('', $texts);
    }

    /**
     * Belegte Links (Zitate im Text) und die uebrigen gefundenen Seiten – je URL einmal.
     *
     * @param  array<int, object>  $content
     * @return array<int, array{url: string, title: ?string, cited: bool, start: ?int}>
     */
    protected function sources(array $content): array
    {
        $sources = [];
        $seen = [];

        foreach ($content as $block) {
            if (($block->type ?? null) !== 'text') {
                continue;
            }

            foreach ((array) ($block->citations ?? []) as $citation) {
                $url = (string) ($citation->url ?? '');
                if ($url !== '' && ! isset($seen[$url])) {
                    $sources[] = ['url' => $url, 'title' => $citation->title ?? null, 'cited' => true, 'start' => null];
                    $seen[$url] = true;
                }
            }
        }

        foreach ($content as $block) {
            if (($block->type ?? null) !== 'web_search_tool_result' || ! is_array($block->content ?? null)) {
                continue;
            }

            foreach ($block->content as $result) {
                $url = (string) ($result->url ?? '');
                if ($url !== '' && ! isset($seen[$url])) {
                    $sources[] = ['url' => $url, 'title' => $result->title ?? null, 'cited' => false, 'start' => null];
                    $seen[$url] = true;
                }
            }
        }

        return $sources;
    }

    /**
     * Der Anthropic-Client mit dem unter System > KI hinterlegten Schluessel.
     * Tests hinterlegen einen eigenen Client im Container.
     *
     * @throws Exception wenn kein Schluessel hinterlegt ist
     */
    protected function client(): Client
    {
        if (app()->bound(Client::class)) {
            return app(Client::class);
        }

        $key = AiProviders::apiKey(AiProviders::ANTHROPIC);

        if (! $key) {
            throw new Exception('Für Claude ist kein API-Schlüssel hinterlegt (System > KI).');
        }

        return new Client(apiKey: $key);
    }
}
