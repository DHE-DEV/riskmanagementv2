<?php

namespace App\Support;

use App\Models\SystemSetting;
use App\Services\Ai\ClaudeService;
use App\Services\Ai\GeminiService;
use App\Services\ChatGptService;

/**
 * Die KI-Anbieter der Plattform: OpenAI (Standard fuer alle KI-Funktionen),
 * dazu Anthropic (Claude) und Google (Gemini) fuer die KI-Suche nach
 * Ereignissen. Welcher Anbieter gefragt wird, ergibt sich aus dem Modell.
 */
class AiProviders
{
    public const OPENAI = 'openai';

    public const ANTHROPIC = 'anthropic';

    public const GEMINI = 'gemini';

    /**
     * Anbieter => Bezeichnung, Einstellung im Admin, Eintrag in config/services.php, Variable der .env.
     *
     * @var array<string, array{label: string, setting: string, config: string, env: string, placeholder: string}>
     */
    public const PROVIDERS = [
        self::OPENAI => ['label' => 'OpenAI', 'setting' => AiSettings::KEY_API_KEY, 'config' => 'services.openai.key', 'env' => 'RISK_CHARGPT_KEY', 'placeholder' => 'sk-…'],
        self::ANTHROPIC => ['label' => 'Anthropic (Claude)', 'setting' => 'ai.anthropic.api_key', 'config' => 'services.anthropic.key', 'env' => 'ANTHROPIC_API_KEY bzw. RISK_CLAUDE_KEY', 'placeholder' => 'sk-ant-…'],
        self::GEMINI => ['label' => 'Google (Gemini)', 'setting' => 'ai.gemini.api_key', 'config' => 'services.gemini.key', 'env' => 'GEMINI_API_KEY', 'placeholder' => 'AIza…'],
    ];

    /**
     * Der Anbieter eines Modells: "claude-…" bei Anthropic, "gemini-…" bei
     * Google, alles andere bei OpenAI.
     */
    public static function forModel(?string $model): string
    {
        $model = strtolower(trim((string) $model));

        return match (true) {
            str_starts_with($model, 'claude-') => self::ANTHROPIC,
            str_starts_with($model, 'gemini-') => self::GEMINI,
            default => self::OPENAI,
        };
    }

    public static function label(string $provider): string
    {
        return self::PROVIDERS[$provider]['label'] ?? $provider;
    }

    public static function apiKey(string $provider): ?string
    {
        $definition = self::PROVIDERS[$provider] ?? null;

        if (! $definition) {
            return null;
        }

        return SystemSetting::read($definition['setting']) ?: (config($definition['config']) ?: null);
    }

    /**
     * Woher der Schluessel stammt: "admin", "env" oder null (keiner hinterlegt).
     */
    public static function apiKeySource(string $provider): ?string
    {
        $definition = self::PROVIDERS[$provider] ?? null;

        return match (true) {
            $definition === null => null,
            filled(SystemSetting::read($definition['setting'])) => 'admin',
            filled(config($definition['config'])) => 'env',
            default => null,
        };
    }

    /**
     * Der Schluessel in unkenntlicher Form, z. B. "sk-…a1B2".
     */
    public static function maskedApiKey(string $provider): ?string
    {
        $key = self::apiKey($provider);

        if (! $key) {
            return null;
        }

        return mb_strlen($key) <= 8
            ? str_repeat('•', mb_strlen($key))
            : mb_substr($key, 0, 3).'…'.mb_substr($key, -4);
    }

    /**
     * Der Dienst, der fuer dieses Modell im Internet sucht. Alle bieten
     * searchWeb(), lastUsage() und lastSources().
     */
    public static function webSearchFor(?string $model): ChatGptService|ClaudeService|GeminiService
    {
        return match (self::forModel($model)) {
            self::ANTHROPIC => app(ClaudeService::class),
            self::GEMINI => app(GeminiService::class),
            default => app(ChatGptService::class),
        };
    }
}
