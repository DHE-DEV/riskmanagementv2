<?php

namespace App\Services\Ai;

use App\Support\AiProviders;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Websuche mit Gemini (Google) fuer die KI-Suche nach Ereignissen – ueber
 * die Gemini-API mit dem Werkzeug "Google Search".
 *
 * Gleiche Schnittstelle wie ChatGptService::searchWeb(): Antworttext,
 * danach lastUsage() und lastSources().
 */
class GeminiService
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

    private const MODELS_CACHE_KEY = 'adminv2.gemini.models';

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
     * @param  array{model: string, timeout?: int}  $options
     *
     * @throws Exception
     */
    public function searchWeb(string $prompt, array $options = []): string
    {
        $model = (string) ($options['model'] ?? '');
        $this->lastUsage = null;
        $this->lastSources = [];

        if (! preg_match('/^gemini-[A-Za-z0-9._-]+$/', $model)) {
            throw new Exception('Bitte ein Gemini-Modell wählen.');
        }

        try {
            $response = $this->request()
                ->timeout($options['timeout'] ?? 300)
                ->post(self::BASE_URL.'/models/'.$model.':generateContent', [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'tools' => [['google_search' => (object) []]],
                ]);
        } catch (ConnectionException $e) {
            Log::error('Gemini-Websuche fehlgeschlagen', ['model' => $model, 'message' => $e->getMessage()]);

            throw new Exception('Fehler bei der Kommunikation mit Gemini: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            Log::error('Gemini-Websuche: API-Fehler', ['status' => $response->status(), 'error' => $response->json('error')]);

            throw new Exception('Gemini-Fehler: '.self::describeError($response));
        }

        $usage = (array) $response->json('usageMetadata', []);
        $input = (int) ($usage['promptTokenCount'] ?? 0) + (int) ($usage['toolUsePromptTokenCount'] ?? 0);
        // Denkschritte werden wie Ausgabe abgerechnet.
        $output = (int) ($usage['candidatesTokenCount'] ?? 0) + (int) ($usage['thoughtsTokenCount'] ?? 0);

        $this->lastUsage = [
            'model' => (string) ($response->json('modelVersion') ?: $model),
            'input_tokens' => $input,
            'output_tokens' => $output,
            'total_tokens' => $input + $output,
        ];

        $candidate = (array) $response->json('candidates.0', []);

        if (in_array($candidate['finishReason'] ?? null, ['SAFETY', 'PROHIBITED_CONTENT', 'BLOCKLIST', 'SPII'], true)) {
            throw new Exception('Gemini hat die Anfrage abgelehnt ('.$candidate['finishReason'].').');
        }

        // Gedankenteile ("thought") gehoeren nicht zur Antwort.
        $text = collect($candidate['content']['parts'] ?? [])
            ->reject(fn ($part) => ! is_array($part) || ! empty($part['thought']))
            ->pluck('text')
            ->filter(fn ($text) => is_string($text))
            ->implode('');

        $this->lastSources = collect($candidate['groundingMetadata']['groundingChunks'] ?? [])
            ->map(fn ($chunk) => is_array($chunk) ? ($chunk['web'] ?? null) : null)
            ->filter(fn ($web) => is_array($web) && filled($web['uri'] ?? null))
            ->unique('uri')
            ->map(fn (array $web) => ['url' => (string) $web['uri'], 'title' => $web['title'] ?? null, 'cited' => true, 'start' => null])
            ->values()
            ->all();

        if (trim($text) === '') {
            throw new Exception('Gemini hat keine auswertbare Antwort geliefert.');
        }

        return trim($text);
    }

    /**
     * Die Gemini-Modelle, die mit dem hinterlegten Schluessel Texte erzeugen
     * koennen – ohne Modelle fuer Bilder, Sprache und Einbettungen.
     *
     * @return array<string, string> Modell-ID => Bezeichnung
     *
     * @throws Exception wenn kein Schluessel hinterlegt ist oder Google ablehnt
     */
    public function models(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::MODELS_CACHE_KEY);
        }

        return Cache::remember(self::MODELS_CACHE_KEY, now()->addMinutes(30), function () {
            $response = $this->request()->timeout(20)->get(self::BASE_URL.'/models', ['pageSize' => 1000]);

            if (! $response->successful()) {
                throw new Exception(self::describeError($response));
            }

            return collect($response->json('models', []))
                ->filter(fn ($model) => is_array($model)
                    && in_array('generateContent', (array) ($model['supportedGenerationMethods'] ?? []), true))
                ->mapWithKeys(fn (array $model) => [str_replace('models/', '', (string) ($model['name'] ?? '')) => (string) ($model['displayName'] ?? '')])
                ->filter(fn (string $label, string $id) => str_starts_with($id, 'gemini-')
                    && ! preg_match('/(embedding|image|tts|audio|live|vision|robotics|computer-use)/', $id))
                ->sortKeysDesc()
                ->all();
        });
    }

    /**
     * Eine verstaendliche Meldung zu einer Fehlerantwort der Gemini-API – mit
     * der Originalmeldung in Klammern fuer die Fehlersuche.
     */
    public static function describeError(Response $response): string
    {
        $original = (string) ($response->json('error.message') ?: 'HTTP '.$response->status());
        $reason = (string) collect((array) $response->json('error.details', []))->pluck('reason')->filter()->first();
        $state = (string) $response->json('error.status');

        $hint = match (true) {
            in_array($reason, ['API_KEY_INVALID', 'API_KEY_EXPIRED'], true), $response->status() === 401 => 'Der Schlüssel ist ungültig oder abgelaufen. Bitte in Google AI Studio einen neuen Schlüssel erzeugen und hier eintragen.',
            $response->status() === 403 => 'Der Schlüssel hat dafür keine Berechtigung (z. B. Gemini-API im Google-Cloud-Projekt nicht aktiviert oder Schlüssel eingeschränkt).',
            $response->status() === 404 => 'Das Modell ist mit diesem Schlüssel nicht verfügbar.',
            $response->status() === 429, $state === 'RESOURCE_EXHAUSTED' => 'Das Kontingent des Google-Projekts ist erreicht (im kostenlosen Tarif sehr niedrig). Bitte später erneut versuchen oder die Abrechnung im Projekt aktivieren.',
            $response->status() >= 500 => 'Gemini ist gerade überlastet oder gestört. Bitte später erneut versuchen.',
            default => null,
        };

        return $hint ? $hint.' ('.$original.')' : $original;
    }

    public function forgetModels(): void
    {
        Cache::forget(self::MODELS_CACHE_KEY);
    }

    /**
     * @throws Exception wenn kein Schluessel hinterlegt ist
     */
    protected function request(): PendingRequest
    {
        $key = AiProviders::apiKey(AiProviders::GEMINI);

        if (! $key) {
            throw new Exception('Für Gemini ist kein API-Schlüssel hinterlegt (System > KI).');
        }

        return Http::withHeaders(['x-goog-api-key' => $key])->acceptJson();
    }
}
