<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatGptService
{
    protected string $apiKey;
    protected string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    protected string $model = 'gpt-4';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');

        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API Key nicht konfiguriert. Bitte RISK_CHARGPT_KEY in .env setzen.');
        }
    }

    /**
     * Sendet einen Prompt an ChatGPT und gibt die Antwort zurück
     *
     * @param string $prompt Der zu sendende Prompt
     * @param array $options Optionale Konfiguration (model, temperature, max_tokens)
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
            ->timeout(60)
            ->post($this->apiUrl, [
                'model' => $options['model'] ?? $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 2000,
            ]);

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
