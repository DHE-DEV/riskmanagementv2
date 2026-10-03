<?php

namespace App\Services;

use App\Models\AiCheck;
use App\Support\AdminV2\AiAreas;
use App\Support\AdminV2\RichText;
use App\Support\AiSettings;

/**
 * Fuehrt eine KI-Pruefung mit den Daten eines Formular-Abschnitts aus.
 *
 * Der Prompt kann einzelne Platzhalter ({name}, {iso_code}, …) und {daten}
 * (alle Angaben als Liste) verwenden. Nutzt er keinen davon, werden die
 * Angaben automatisch angehaengt – die KI bekommt immer, was im Abschnitt steht.
 */
class AiCheckService
{
    /**
     * @param  array<string, mixed>  $context  Angaben des Abschnitts: Schluessel => Wert
     * @param  array<string, string>  $labels  Schluessel => Bezeichnung (fuer die Datenliste)
     * @param  array<string, mixed>  $extra  weitere Angaben des Eintrags, die als Platzhalter nutzbar sind (z. B. {name})
     * @return array{html: string, prompt: string, usage: ?array{model: string, input_tokens: int, output_tokens: int, total_tokens: int, cost: ?float}}
     */
    public function run(AiCheck $check, array $context, array $labels, array $extra = []): array
    {
        $prompt = $this->buildPrompt($check->prompt, $context, $labels, $extra);

        $ai = app(ChatGptService::class);
        $answer = $ai->sendPrompt($prompt, ['model' => $check->model ?: AiSettings::model()]);
        $usage = $ai->lastUsage();

        // Reiner Text behaelt seine Zeilenumbrueche; HTML wird bereinigt.
        $html = $answer === strip_tags($answer) ? nl2br(e(trim($answer))) : (string) RichText::sanitize($answer);

        return [
            'html' => $html,
            'prompt' => $prompt,
            'usage' => $usage ? $usage + ['cost' => AiSettings::cost($usage['model'], $usage['input_tokens'], $usage['output_tokens'])] : null,
        ];
    }

    /**
     * Platzhalter des Abschnitts und des uebrigen Eintrags ersetzen. Die
     * Datenliste ({daten} bzw. der automatische Anhang) enthaelt nur den Abschnitt.
     *
     * @param  array<string, mixed>  $context
     * @param  array<string, string>  $labels
     * @param  array<string, mixed>  $extra
     */
    public function buildPrompt(string $template, array $context, array $labels, array $extra = []): string
    {
        $data = $this->dataBlock($context, $labels);
        $usedPlaceholder = false;
        $prompt = $template;

        foreach ($context + $extra as $key => $value) {
            if (str_contains($prompt, '{'.$key.'}')) {
                $usedPlaceholder = $usedPlaceholder || array_key_exists($key, $context);
                $prompt = str_replace('{'.$key.'}', $this->format($value), $prompt);
            }
        }

        if (str_contains($prompt, '{'.AiAreas::DATA_PLACEHOLDER.'}')) {
            return str_replace('{'.AiAreas::DATA_PLACEHOLDER.'}', $data, $prompt);
        }

        return $usedPlaceholder ? $prompt : rtrim($prompt)."\n\nDaten des Eintrags:\n".$data;
    }

    /**
     * Alle Angaben als Liste "Bezeichnung: Wert".
     *
     * @param  array<string, mixed>  $context
     * @param  array<string, string>  $labels
     */
    public function dataBlock(array $context, array $labels): string
    {
        $lines = [];

        foreach ($context as $key => $value) {
            $lines[] = ($labels[$key] ?? $key).': '.$this->format($value);
        }

        return implode("\n", $lines);
    }

    /**
     * Wert fuer den Prompt: Ja/Nein, Listen als Zeilen, Leeres als "–".
     */
    public function format(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Ja' : 'Nein';
        }

        if ($value === null || $value === '' || $value === []) {
            return '–';
        }

        if (is_array($value)) {
            // Einfache Listen zeilenweise, Strukturen als lesbares JSON.
            if (array_is_list($value) && collect($value)->every(fn ($item) => is_scalar($item) || $item === null)) {
                return implode(', ', array_map(fn ($item) => $this->format($item), $value));
            }

            return (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return trim((string) $value);
    }
}
