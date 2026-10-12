<?php

namespace App\Services\Concerns;

use App\Services\ChatGptService;
use Throwable;

/**
 * Gemeinsames der KI-Generatoren: mehrere Prompts stellen (mit oder ohne
 * Websuche), Quellenverweise der Websuche entfernen, Fehler kuerzen.
 * Die Klasse liefert chat().
 */
trait AsksChatGpt
{
    abstract protected function chat(): ChatGptService;

    /**
     * Prompts stellen – ohne Websuche gemeinsam (faellt das aus, einzeln, damit
     * ein Fehler nicht alle mitreisst), mit Websuche nacheinander.
     *
     * @param  array<int, string>  $prompts  ID => Prompt
     * @return array{answers: array<int, string>, failed: array<int, string>, usage: ?array}
     */
    protected function ask(array $prompts, bool $web = false, int $maxTokens = 6000): array
    {
        $answers = [];
        $failed = [];
        $options = ['timeout' => 180, 'max_tokens' => $maxTokens];

        if (! $web) {
            try {
                $answers = $this->chat()->sendPrompts(array_combine(array_map(fn ($id) => 'r'.$id, array_keys($prompts)), $prompts), $options);

                return [
                    'answers' => array_combine(array_map(fn ($key) => (int) substr($key, 1), array_keys($answers)), $answers),
                    'failed' => [],
                    'usage' => $this->chat()->lastUsage(),
                ];
            } catch (Throwable) {
                $answers = [];
            }
        }

        $usage = null;

        foreach ($prompts as $id => $prompt) {
            try {
                $answers[$id] = $web
                    ? $this->chat()->searchWeb($prompt, ['timeout' => 300])
                    : $this->chat()->sendPrompt($prompt, $options);

                $last = $this->chat()->lastUsage();
                $usage = [
                    'model' => (string) ($last['model'] ?? $usage['model'] ?? ''),
                    'total_tokens' => (int) ($usage['total_tokens'] ?? 0) + (int) ($last['total_tokens'] ?? 0),
                ];
            } catch (Throwable $e) {
                $failed[$id] = $this->shortError($e);
            }
        }

        return ['answers' => $answers, 'failed' => $failed, 'usage' => $usage];
    }

    /**
     * Quellenverweise der Websuche aus dem Text nehmen: "([seite.de](https://…))"
     * faellt weg, "[Text](https://…)" wird zu "Text".
     */
    protected function stripCitations(string $answer): string
    {
        $answer = (string) preg_replace('/\s*\(\[[^\]]*\]\(https?:[^)]*\)\)/u', '', $answer);

        return (string) preg_replace('/\[([^\]]*)\]\(https?:[^)]*\)/u', '$1', $answer);
    }

    protected function shortError(Throwable $e): string
    {
        return mb_substr(str_replace('Fehler bei der Kommunikation mit ChatGPT: ', '', $e->getMessage()), 0, 200);
    }
}
