<?php

namespace App\Services\Concerns;

use App\Models\RegionInfoSource;
use App\Services\ChatGptService;
use App\Support\AdminV2\RegionInfo;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Gemeinsames der KI-Generatoren: mehrere Prompts stellen (mit oder ohne
 * Websuche), Quellenverweise der Websuche entfernen und als Quellen
 * speichern (region_info_sources), Fehler kuerzen. Die Klasse liefert chat().
 */
trait AsksChatGpt
{
    /** Lauf, dem gespeicherte Quellen zugeordnet werden. */
    protected ?int $runId = null;

    abstract protected function chat(): ChatGptService;

    public function forRun(?int $runId): static
    {
        $this->runId = $runId;

        return $this;
    }

    /**
     * Prompts stellen – ohne Websuche gemeinsam (faellt das aus, einzeln, damit
     * ein Fehler nicht alle mitreisst), mit Websuche nacheinander.
     *
     * @param  array<int, string>  $prompts  ID => Prompt
     * @return array{answers: array<int, string>, failed: array<int, string>, usage: ?array, sources: array<int, array>}
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
                    'sources' => [],
                ];
            } catch (Throwable) {
                $answers = [];
            }
        }

        $usage = null;
        $sources = [];

        foreach ($prompts as $id => $prompt) {
            try {
                $answers[$id] = $web
                    ? $this->chat()->searchWeb($prompt, ['timeout' => 300])
                    : $this->chat()->sendPrompt($prompt, $options);

                if ($web) {
                    $sources[$id] = $this->chat()->lastSources();
                }

                $last = $this->chat()->lastUsage();
                $usage = [
                    'model' => (string) ($last['model'] ?? $usage['model'] ?? ''),
                    'total_tokens' => (int) ($usage['total_tokens'] ?? 0) + (int) ($last['total_tokens'] ?? 0),
                ];
            } catch (Throwable $e) {
                $failed[$id] = $this->shortError($e);
            }
        }

        return ['answers' => $answers, 'failed' => $failed, 'usage' => $usage, 'sources' => $sources];
    }

    /**
     * Quellen einer Antwort am Datensatz speichern. Belegte Links bekommen das
     * Feld, hinter dem sie in der (JSON-)Antwort stehen – ausser $field gibt
     * es fest vor; besuchte Seiten bleiben ohne Feld bzw. bekommen $field.
     *
     * @param  array<int, array{url: string, title: ?string, cited: bool, start: ?int}>  $sources
     */
    protected function storeSources(Model $target, array $sources, string $answer, ?string $field = null): void
    {
        if ($sources === []) {
            return;
        }

        // Links im Text (die Websuche liefert sie nicht immer als Annotation) zaehlen als belegt.
        preg_match_all('/\[([^\]]*)\]\((https?:[^)\s]+)\)/u', $answer, $links, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($links as $link) {
            $sources[] = ['url' => $link[2][0], 'title' => trim($link[1][0]) ?: null, 'cited' => true, 'start' => mb_strlen(substr($answer, 0, $link[0][1]))];
        }

        $citedUrls = array_flip(array_map(fn ($source) => $this->sourceUrl((string) $source['url']), array_filter($sources, fn ($source) => $source['cited'])));

        $keys = implode('|', array_map(fn ($key) => preg_quote((string) $key, '/'), [...array_keys(RegionInfo::allTexts()), ...array_keys(RegionInfo::FACT_LABELS)]));
        preg_match_all('/"('.$keys.')"\s*:/u', $answer, $matches, PREG_OFFSET_CAPTURE);
        // Positionen in Zeichen (die Websuche zaehlt Zeichen, preg Bytes).
        $positions = array_map(fn ($match) => [mb_strlen(substr($answer, 0, $match[1])), $match[0]], $matches[1]);
        $model = (string) ($this->chat()->lastUsage()['model'] ?? '');
        $rows = [];

        foreach ($sources as $source) {
            $url = $this->sourceUrl((string) $source['url']);
            // Nur besucht, aber auch belegt: steht schon beim Feld.
            if ($url === '' || ! preg_match('#^https?://#i', $url) || (! $source['cited'] && isset($citedUrls[$url]))) {
                continue;
            }

            $sourceField = $field;
            if ($sourceField === null && $source['cited'] && $source['start'] !== null) {
                foreach ($positions as [$position, $key]) {
                    if ($position > $source['start']) {
                        break;
                    }
                    $sourceField = $key;
                }
            }

            // Je Feld und URL einmal – die Annotation (mit Seitentitel) steht vorne und gewinnt.
            if (isset($rows[($sourceField ?? '').'|'.$url])) {
                continue;
            }
            $rows[($sourceField ?? '').'|'.$url] = [
                'sourceable_type' => $target->getMorphClass(),
                'sourceable_id' => $target->getKey(),
                'field' => $sourceField,
                'url' => $url,
                'domain' => mb_substr((string) preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST)), 0, 255) ?: null,
                'title' => filled($source['title'] ?? null) ? mb_substr(trim((string) $source['title']), 0, 255) : null,
                'cited' => (bool) $source['cited'],
                'region_info_run_id' => $this->runId,
                'model' => $model ?: null,
                'created_at' => now(),
            ];
        }

        if ($rows !== []) {
            RegionInfoSource::insert(array_values($rows));
        }
    }

    /**
     * URL ohne den Verfolgungs-Parameter der Websuche, hoechstens 2048 Zeichen.
     */
    protected function sourceUrl(string $url): string
    {
        $url = (string) preg_replace('/([?&])utm_source=openai(&|$)/', '$1', trim($url));

        return mb_substr(rtrim($url, '?&'), 0, 2048);
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
