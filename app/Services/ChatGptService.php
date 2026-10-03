<?php

namespace App\Services;

use App\Support\AiSettings;
use Exception;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatGptService
{
    protected string $apiKey;
    protected string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    protected string $model = 'gpt-4';

    /**
     * Verbrauch der letzten Anfrage, wie ihn OpenAI meldet.
     *
     * @var array{model: string, input_tokens: int, output_tokens: int, total_tokens: int}|null
     */
    protected ?array $lastUsage = null;

    public function lastUsage(): ?array
    {
        return $this->lastUsage;
    }

    public function __construct()
    {
        // Schluessel und Modell kommen aus dem Admin-Bereich (System > KI),
        // ersatzweise aus der .env.
        $this->apiKey = (string) AiSettings::apiKey();
        $this->model = AiSettings::model();

        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API Key nicht konfiguriert. Bitte im Admin-Bereich unter System > KI hinterlegen oder RISK_CHARGPT_KEY in .env setzen.');
        }
    }

    /**
     * Nur die aelteren Modellreihen (GPT-3.5, GPT-4, GPT-4o, GPT-4.1) nehmen
     * max_tokens und eine eigene temperature. Alle neueren (o-Reihe, GPT-5 und
     * spaeter) erwarten max_completion_tokens und lassen temperature nicht zu.
     * Weil sie zusaetzlich "nachdenken", brauchen sie deutlich mehr Antwort-Budget.
     */
    protected function completionParameters(string $model, array $options): array
    {
        $maxTokens = $options['max_tokens'] ?? 2000;

        if (! preg_match('/^(gpt-3\.5|gpt-4|chatgpt-4o)/', $model)) {
            return ['max_completion_tokens' => max(4000, $maxTokens * 4)];
        }

        return [
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $maxTokens,
        ];
    }

    /**
     * Sendet einen Prompt an ChatGPT und gibt die Antwort zurück
     *
     * @param string $prompt Der zu sendende Prompt
     * @param array $options Optionale Konfiguration (model, temperature, max_tokens, timeout)
     * @return string Die Antwort von ChatGPT
     * @throws Exception Bei API-Fehlern
     */
    public function sendPrompt(string $prompt, array $options = []): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout($options['timeout'] ?? 60)
            ->post($this->apiUrl, [
                'model' => $model = $options['model'] ?? $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ] + $this->completionParameters($model, $options));

            if (!$response->successful()) {
                $error = $response->json();
                Log::error('ChatGPT API Error', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);

                throw new Exception(
                    'ChatGPT API Fehler: ' . ($error['error']['message'] ?? 'Unbekannter Fehler')
                );
            }

            $data = $response->json();

            $this->lastUsage = isset($data['usage']) ? [
                'model' => (string) ($data['model'] ?? $model),
                'input_tokens' => (int) ($data['usage']['prompt_tokens'] ?? 0),
                'output_tokens' => (int) ($data['usage']['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($data['usage']['total_tokens'] ?? 0),
            ] : null;

            if (!isset($data['choices'][0]['message']['content'])) {
                throw new Exception('Ungültige API-Antwort von ChatGPT');
            }

            return trim($data['choices'][0]['message']['content']);

        } catch (Exception $e) {
            Log::error('ChatGPT Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception('Fehler bei der Kommunikation mit ChatGPT: ' . $e->getMessage());
        }
    }

    /**
     * Sendet mehrere Prompts gleichzeitig und gibt die Antworten unter denselben
     * Schluesseln zurueck. lastUsage() nennt danach den Verbrauch aller zusammen.
     *
     * @param  array<string, string>  $prompts
     * @param  array  $options  wie bei sendPrompt()
     * @return array<string, string>
     *
     * @throws Exception Bei API-Fehlern
     */
    public function sendPrompts(array $prompts, array $options = []): array
    {
        $model = $options['model'] ?? $this->model;

        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn (string $key) => $pool->as($key)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->timeout($options['timeout'] ?? 60)
                ->post($this->apiUrl, [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => $prompts[$key]]],
                ] + $this->completionParameters($model, $options)),
            array_map('strval', array_keys($prompts)),
        ));

        $answers = [];
        $usage = ['model' => $model, 'input_tokens' => 0, 'output_tokens' => 0, 'total_tokens' => 0];

        foreach (array_keys($prompts) as $key) {
            $response = $responses[$key];

            if ($response instanceof \Throwable) {
                Log::error('ChatGPT Service Exception', ['message' => $response->getMessage()]);

                throw new Exception('Fehler bei der Kommunikation mit ChatGPT: '.$response->getMessage());
            }

            $data = $response->json();

            if (! $response->successful()) {
                Log::error('ChatGPT API Error', ['status' => $response->status(), 'error' => $data]);

                throw new Exception('Fehler bei der Kommunikation mit ChatGPT: ChatGPT API Fehler: '.($data['error']['message'] ?? 'Unbekannter Fehler'));
            }

            if (! isset($data['choices'][0]['message']['content'])) {
                throw new Exception('Fehler bei der Kommunikation mit ChatGPT: Ungültige API-Antwort von ChatGPT');
            }

            $answers[$key] = trim($data['choices'][0]['message']['content']);

            $usage['model'] = (string) ($data['model'] ?? $model);
            $usage['input_tokens'] += (int) ($data['usage']['prompt_tokens'] ?? 0);
            $usage['output_tokens'] += (int) ($data['usage']['completion_tokens'] ?? 0);
            $usage['total_tokens'] += (int) ($data['usage']['total_tokens'] ?? 0);
        }

        $this->lastUsage = $usage;

        return $answers;
    }

    /**
     * Stellt eine Anfrage, bei der das Modell im Internet suchen darf
     * (Responses-API mit dem Werkzeug "web_search").
     *
     * @throws Exception Bei API-Fehlern
     */
    public function searchWeb(string $prompt, array $options = []): string
    {
        $model = $options['model'] ?? $this->model;

        $request = fn (string $tool) => Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ])
            // Suchen und Lesen dauert laenger als eine reine Textantwort.
            ->timeout($options['timeout'] ?? 240)
            ->post('https://api.openai.com/v1/responses', [
                'model' => $model,
                'input' => $prompt,
                'tools' => [['type' => $tool]],
            ]);

        try {
            $response = $request('web_search');

            // Aeltere Modelle kennen das Werkzeug nur unter seinem frueheren Namen.
            if ($response->status() === 400 && str_contains((string) $response->json('error.message'), 'web_search')) {
                $response = $request('web_search_preview');
            }
        } catch (Exception $e) {
            Log::error('OpenAI-Websuche fehlgeschlagen', ['message' => $e->getMessage()]);

            throw new Exception('Fehler bei der Kommunikation mit OpenAI: '.$e->getMessage());
        }

        if (! $response->successful()) {
            Log::error('OpenAI-Websuche: API-Fehler', ['status' => $response->status(), 'error' => $response->json()]);

            throw new Exception('OpenAI-Fehler: '.($response->json('error.message') ?? 'Unbekannter Fehler (HTTP '.$response->status().')'));
        }

        $data = $response->json();

        $this->lastUsage = isset($data['usage']) ? [
            'model' => (string) ($data['model'] ?? $model),
            'input_tokens' => (int) ($data['usage']['input_tokens'] ?? 0),
            'output_tokens' => (int) ($data['usage']['output_tokens'] ?? 0),
            'total_tokens' => (int) ($data['usage']['total_tokens'] ?? 0),
        ] : null;

        // Die Antwort besteht aus Suchschritten und Textbloecken – nur der Text zaehlt.
        $text = collect($data['output'] ?? [])
            ->where('type', 'message')
            ->flatMap(fn (array $item) => $item['content'] ?? [])
            ->where('type', 'output_text')
            ->pluck('text')
            ->implode("\n");

        if (trim($text) === '') {
            throw new Exception('OpenAI hat keine auswertbare Antwort geliefert.');
        }

        return trim($text);
    }

    /**
     * Verarbeitet einen AiPrompt mit Daten und sendet ihn an ChatGPT
     *
     * @param \App\Models\AiPrompt $aiPrompt
     * @param array $data Daten für Platzhalter
     * @param array $options Optionale API-Konfiguration
     * @return string Die Antwort von ChatGPT
     */
    public function processPrompt($aiPrompt, array $data, array $options = []): string
    {
        // Platzhalter im Prompt-Template ersetzen
        $filledPrompt = $aiPrompt->fillPlaceholders($data);

        // An ChatGPT senden
        return $this->sendPrompt($filledPrompt, $options);
    }
}
