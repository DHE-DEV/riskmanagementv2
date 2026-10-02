<?php

namespace App\Support;

use App\Models\SystemSetting;

/**
 * Schluessel und Modell der KI (OpenAI).
 *
 * Massgeblich ist, was im Admin-Bereich unter System > KI hinterlegt ist.
 * Fehlt dort ein Wert, gilt die .env (RISK_CHARGPT_KEY bzw. OPENAI_MODEL).
 */
class AiSettings
{
    public const KEY_API_KEY = 'ai.openai.api_key';

    public const KEY_MODEL = 'ai.openai.model';

    /** JSON: {"<modell>": {"input": <USD je 1 Mio. Token>, "output": <USD je 1 Mio. Token>}} */
    public const KEY_PRICES = 'ai.openai.prices';

    public const DEFAULT_MODEL = 'gpt-4';

    /** Auftrag fuer die KI-Suche nach aktuellen Ereignissen (System > KI). */
    public const KEY_EVENT_SEARCH_PROMPT = 'ai.event_search.prompt';

    /** "1"/"0": bereits erfasste Ereignisse bei der Suche ausschliessen. */
    public const KEY_EVENT_SEARCH_EXCLUDE = 'ai.event_search.exclude_existing';

    public const DEFAULT_EVENT_SEARCH_PROMPT = <<<'TEXT'
Suche im Internet nach aktuellen Ereignissen der letzten 48 Stunden, die für Reisende und Reiseveranstalter relevant sind – weltweit, mit Schwerpunkt auf beliebten Reisezielen deutscher Urlauber und Geschäftsreisender.

Dazu gehören:
- Unwetter und Naturkatastrophen (Stürme, Überschwemmungen, Waldbrände, Erdbeben, Vulkanausbrüche)
- Streiks und Ausfälle im Reiseverkehr (Flug, Bahn, Fähre, Nahverkehr), Sperrungen von Flughäfen oder Strecken
- Sicherheitslage (Unruhen, Anschläge, Ausgangssperren, Demonstrationen mit Auswirkungen)
- neue oder geänderte Einreisebestimmungen (Visa, Grenzkontrollen, Dokumente)
- Gesundheitsrisiken (Krankheitsausbrüche, Impfvorschriften)
- sonstige Lagen mit konkreten Folgen für Reisen

Bevorzuge offizielle Quellen (Auswärtiges Amt, Behörden, Flughäfen, Bahn- und Fluggesellschaften) und seriöse Nachrichtenmedien. Lass Meldungen ohne konkrete Auswirkung auf Reisende weg, ebenso Vergangenes, das abgeschlossen ist.
TEXT;

    public static function apiKey(): ?string
    {
        return SystemSetting::read(self::KEY_API_KEY) ?: (config('services.openai.key') ?: null);
    }

    /**
     * Woher der Schluessel stammt: "admin", "env" oder null (keiner hinterlegt).
     */
    public static function apiKeySource(): ?string
    {
        return match (true) {
            filled(SystemSetting::read(self::KEY_API_KEY)) => 'admin',
            filled(config('services.openai.key')) => 'env',
            default => null,
        };
    }

    /**
     * Der Schluessel in unkenntlicher Form, z. B. "sk-…a1B2".
     */
    public static function maskedApiKey(): ?string
    {
        $key = self::apiKey();

        if (! $key) {
            return null;
        }

        return mb_strlen($key) <= 8
            ? str_repeat('•', mb_strlen($key))
            : mb_substr($key, 0, 3).'…'.mb_substr($key, -4);
    }

    /**
     * Der Auftrag fuer die KI-Suche nach aktuellen Ereignissen.
     */
    public static function eventSearchPrompt(): string
    {
        return trim((string) SystemSetting::read(self::KEY_EVENT_SEARCH_PROMPT)) ?: self::DEFAULT_EVENT_SEARCH_PROMPT;
    }

    /**
     * Sollen bereits erfasste Ereignisse ausgeschlossen werden? Standard: ja.
     */
    public static function eventSearchExcludesExisting(): bool
    {
        return SystemSetting::read(self::KEY_EVENT_SEARCH_EXCLUDE, '1') !== '0';
    }

    public static function model(): string
    {
        return SystemSetting::read(self::KEY_MODEL)
            ?: (config('services.openai.model') ?: self::DEFAULT_MODEL);
    }

    /**
     * Preise eines Modells in US-Dollar je 1 Mio. Token: der unter
     * System > KI von Hand hinterlegte Preis, sonst der aus der Preisliste
     * (config/ai_prices.php). OpenAI liefert Preise nicht ueber die Schnittstelle.
     *
     * @return array{input: float, output: float}|null
     */
    public static function prices(string $model): ?array
    {
        return self::customPrices($model) ?? self::listPrices($model);
    }

    /**
     * Der von Hand hinterlegte Preis eines Modells.
     *
     * @return array{input: float, output: float}|null
     */
    public static function customPrices(string $model): ?array
    {
        $all = json_decode((string) SystemSetting::read(self::KEY_PRICES, '{}'), true);

        return self::validPrices(is_array($all) ? ($all[$model] ?? null) : null);
    }

    /**
     * Der Preis laut Preisliste. Ein Modellstand mit Datum
     * ("gpt-4o-2024-08-06") nutzt den Preis seines Modells, sofern er nicht
     * eigens gelistet ist.
     *
     * @return array{input: float, output: float}|null
     */
    public static function listPrices(string $model): ?array
    {
        $list = (array) config('ai_prices.models', []);

        return self::validPrices($list[$model] ?? null)
            ?? self::validPrices($list[preg_replace('/-\d{4}-\d{2}-\d{2}$/', '', $model)] ?? null);
    }

    /**
     * Woher der Preis eines Modells stammt: "admin", "list" oder null.
     */
    public static function priceSource(string $model): ?string
    {
        return match (true) {
            self::customPrices($model) !== null => 'admin',
            self::listPrices($model) !== null => 'list',
            default => null,
        };
    }

    /**
     * @return array{input: float, output: float}|null
     */
    protected static function validPrices(mixed $prices): ?array
    {
        if (! is_array($prices) || ! is_numeric($prices['input'] ?? null) || ! is_numeric($prices['output'] ?? null)) {
            return null;
        }

        return ['input' => (float) $prices['input'], 'output' => (float) $prices['output']];
    }

    public static function setPrices(string $model, ?float $input, ?float $output): void
    {
        $all = json_decode((string) SystemSetting::read(self::KEY_PRICES, '{}'), true);
        $all = is_array($all) ? $all : [];

        if ($input === null || $output === null) {
            unset($all[$model]);
        } else {
            $all[$model] = ['input' => $input, 'output' => $output];
        }

        SystemSetting::write(self::KEY_PRICES, $all === [] ? null : json_encode($all));
    }

    /**
     * Kosten einer Anfrage in US-Dollar – null, wenn fuer das Modell kein Preis hinterlegt ist.
     */
    public static function cost(string $model, int $inputTokens, int $outputTokens): ?float
    {
        // OpenAI meldet das Modell oft mit Datumsstand zurueck ("gpt-4o-2024-08-06"):
        // dann gilt der Preis des gewaehlten Modells, zu dem dieser Stand gehoert.
        $prices = self::prices($model) ?? (str_starts_with($model, self::model()) ? self::prices(self::model()) : null);

        if (! $prices) {
            return null;
        }

        return round(($inputTokens * $prices['input'] + $outputTokens * $prices['output']) / 1_000_000, 6);
    }

    /**
     * Woher das Modell stammt: "admin", "env" oder "default".
     */
    public static function modelSource(): string
    {
        return match (true) {
            filled(SystemSetting::read(self::KEY_MODEL)) => 'admin',
            filled(config('services.openai.model')) => 'env',
            default => 'default',
        };
    }
}
