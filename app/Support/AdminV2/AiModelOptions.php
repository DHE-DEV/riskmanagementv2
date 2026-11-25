<?php

namespace App\Support\AdminV2;

use App\Services\Ai\ClaudeService;
use App\Services\Ai\GeminiService;
use App\Services\OpenAiModelService;
use App\Support\AiProviders;
use App\Support\AiSettings;
use Throwable;

/**
 * Auswahl von KI und Modell fuer Fragen an die KI (Fragefeld, Feldrecherche):
 * nur Anbieter mit hinterlegtem Schluessel, je Anbieter seine Modelle.
 */
class AiModelOptions
{
    /** Anbieter => Bezeichnung in der Auswahl */
    public const PROVIDER_LABELS = [
        AiProviders::OPENAI => 'ChatGPT',
        AiProviders::ANTHROPIC => 'Claude',
        AiProviders::GEMINI => 'Gemini',
    ];

    /**
     * Anbieter, fuer die ein Schluessel hinterlegt ist.
     *
     * @return array<string, string>
     */
    public static function providers(): array
    {
        return array_filter(self::PROVIDER_LABELS, fn (string $label, string $provider) => filled(AiProviders::apiKey($provider)), ARRAY_FILTER_USE_BOTH);
    }

    public static function providerLabel(?string $provider): string
    {
        return self::PROVIDER_LABELS[$provider] ?? AiProviders::label((string) $provider);
    }

    /**
     * Die Modelle eines Anbieters: Modell-ID => Bezeichnung.
     *
     * @return array<string, string>
     */
    public static function models(string $provider): array
    {
        return match ($provider) {
            AiProviders::OPENAI => self::openAiModels(),
            AiProviders::ANTHROPIC => ClaudeService::MODELS,
            AiProviders::GEMINI => self::geminiModels(),
            default => [],
        };
    }

    /**
     * Das voreingestellte Modell eines Anbieters: bei ChatGPT das unter
     * System > KI gewaehlte, sonst das erste der Liste.
     */
    public static function defaultModel(string $provider): string
    {
        $options = array_keys(self::models($provider));

        if ($provider === AiProviders::OPENAI && in_array(AiSettings::model(), $options, true)) {
            return AiSettings::model();
        }

        return (string) ($options[0] ?? '');
    }

    /**
     * Die Modelle des OpenAI-Schluessels, ersatzweise die der Preisliste.
     *
     * @return array<string, string>
     */
    protected static function openAiModels(): array
    {
        try {
            $ids = array_column(app(OpenAiModelService::class)->chatModels(), 'id');
        } catch (Throwable) {
            $ids = [];
        }

        $ids = $ids ?: array_values(array_filter(
            array_keys(config('ai_prices.models', [])),
            fn (string $id) => AiProviders::forModel($id) === AiProviders::OPENAI,
        ));

        return array_combine($ids, $ids) ?: [];
    }

    /**
     * @return array<string, string>
     */
    protected static function geminiModels(): array
    {
        try {
            $models = app(GeminiService::class)->models();
        } catch (Throwable) {
            return [];
        }

        return collect($models)->mapWithKeys(fn (string $label, string $id) => [$id => $label !== '' ? $label.' ('.$id.')' : $id])->all();
    }
}
