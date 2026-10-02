<?php

namespace App\Services;

use App\Support\AiSettings;
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
