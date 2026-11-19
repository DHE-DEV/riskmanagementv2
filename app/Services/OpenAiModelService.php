<?php

namespace App\Services;

use App\Support\AiSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Liest bei OpenAI die Modelle aus, die mit dem hinterlegten Schluessel
 * verfuegbar sind, und reduziert sie auf Chat-Modelle.
 */
class OpenAiModelService
{
    private const CACHE_KEY = 'adminv2.openai.models';

    /**
     * @return array<int, array{id: string, created: ?string}> neueste zuerst
     *
     * @throws \RuntimeException wenn kein Schluessel hinterlegt ist oder OpenAI ablehnt
     */
    public function chatModels(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, now()->addMinutes(30), function () {
            $key = AiSettings::apiKey();

            if (! $key) {
                throw new \RuntimeException('Es ist kein API-Schlüssel hinterlegt.');
            }

            $response = Http::withToken($key)->acceptJson()->timeout(20)->get('https://api.openai.com/v1/models');

            if (! $response->successful()) {
                throw new \RuntimeException($response->json('error.message') ?: 'OpenAI antwortet mit Status '.$response->status().'.');
            }

            return collect($response->json('data', []))
                ->filter(fn ($model) => is_array($model) && $this->isChatModel((string) ($model['id'] ?? '')))
                ->sortByDesc('created')
                ->map(fn (array $model) => [
                    'id' => $model['id'],
                    'created' => isset($model['created']) ? Carbon::createFromTimestamp($model['created'])->format('d.m.Y') : null,
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Kurze Anfrage an das Modell – zeigt, ob Schluessel, Guthaben und
     * Modell zusammen funktionieren. Fehler kommen als verstaendliche Meldung.
     *
     * @throws \RuntimeException mit verstaendlicher Meldung
     */
    public function testConnection(string $model): string
    {
        $key = AiSettings::apiKey();

        if (! $key) {
            throw new \RuntimeException('Es ist kein API-Schlüssel hinterlegt.');
        }

        try {
            $response = Http::withToken($key)->acceptJson()->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => 'Antworte nur mit dem Wort: bereit']],
            ] + app(ChatGptService::class)->completionParameters($model, ['max_tokens' => 20, 'temperature' => 0]));
        } catch (ConnectionException $e) {
            throw new \RuntimeException('OpenAI ist nicht erreichbar. ('.$e->getMessage().')', previous: $e);
        }

        if (! $response->successful()) {
            throw new \RuntimeException(self::describeError($response));
        }

        $answer = trim((string) $response->json('choices.0.message.content'));

        return $answer !== '' ? $answer : '(keine Textantwort, Grund: '.($response->json('choices.0.finish_reason') ?: 'unbekannt').')';
    }

    /**
     * Eine verstaendliche Meldung zu einer Fehlerantwort von OpenAI – mit der
     * Originalmeldung in Klammern fuer die Fehlersuche.
     */
    public static function describeError(Response $response): string
    {
        $status = $response->status();
        $original = (string) ($response->json('error.message') ?: 'HTTP '.$status);
        $code = (string) ($response->json('error.code') ?: $response->json('error.type'));
        $lower = strtolower($original);

        $hint = match (true) {
            $status === 401, $code === 'invalid_api_key' => 'Der Schlüssel ist ungültig, abgelaufen oder wurde widerrufen. Bitte unter platform.openai.com einen neuen Schlüssel erzeugen und hier eintragen.',
            $code === 'insufficient_quota', $status === 429 && (str_contains($lower, 'quota') || str_contains($lower, 'credit') || str_contains($lower, 'billing')) => 'Das Guthaben bzw. Kontingent des OpenAI-Kontos ist aufgebraucht. Bitte unter platform.openai.com bei Billing aufladen oder das Ausgabelimit erhöhen.',
            $status === 429 => 'Das Anfragelimit des OpenAI-Kontos ist erreicht. Bitte kurz warten und erneut versuchen.',
            $status === 403 => 'Der Schlüssel hat dafür keine Berechtigung (z. B. Projekt oder Organisation eingeschränkt, Modell nicht freigegeben oder Region gesperrt).',
            $status === 404, $code === 'model_not_found' => 'Das gewählte Modell ist mit diesem Schlüssel nicht verfügbar.',
            $status >= 500 => 'OpenAI ist gerade überlastet oder gestört. Bitte später erneut versuchen.',
            default => null,
        };

        return $hint ? $hint.' ('.$original.')' : $original;
    }

    /**
     * Eine verstaendliche Meldung, wenn OpenAI gar nicht oder nicht rechtzeitig
     * antwortet – mit der Originalmeldung in Klammern.
     */
    public static function describeConnectionError(\Throwable $e, int $timeoutSeconds): string
    {
        $message = $e->getMessage();
        // Die technische Meldung von cURL ohne den Verweis auf deren Doku.
        $original = trim((string) preg_replace('/\s*\(see https?:\/\/[^)]*\)/', '', $message));

        $hint = match (true) {
            str_contains($message, 'cURL error 28'), stripos($message, 'timed out') !== false => 'OpenAI hat nicht innerhalb von '.$timeoutSeconds.' Sekunden geantwortet. Gründliche Websuchen dauern teils länger – bitte später erneut versuchen oder ein schnelleres Modell bzw. weniger Ergebnisse wählen.',
            str_contains($message, 'cURL error 6'), str_contains($message, 'cURL error 7') => 'OpenAI ist nicht erreichbar (Verbindung oder Namensauflösung fehlgeschlagen).',
            default => 'Fehler bei der Kommunikation mit OpenAI.',
        };

        return $hint.' ('.$original.')';
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Nur Modelle fuer Textantworten: OpenAI liefert in derselben Liste auch
     * Modelle fuer Sprache, Bilder, Einbettungen und Moderation.
     */
    public function isChatModel(string $id): bool
    {
        if (! preg_match('/^(gpt-|o\d|chatgpt-)/', $id)) {
            return false;
        }

        return ! preg_match('/(audio|realtime|transcribe|tts|image|embedding|moderation|search|instruct|whisper|dall-e|codex)/', $id);
    }
}
