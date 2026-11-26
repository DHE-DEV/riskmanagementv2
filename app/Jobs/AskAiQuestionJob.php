<?php

namespace App\Jobs;

use App\Models\AiQuestion;
use App\Support\AdminV2\AiAreas;
use App\Support\AiCitations;
use App\Support\AiProviders;
use App\Support\AiSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Beantwortet eine Anfrage an die KI im Hintergrund – mit Websuche des
 * gewaehlten Anbieters (ChatGPT, Claude oder Gemini). Zustand und Antwort
 * stehen an der Anfrage (AiQuestion); das Formular fragt sie ab.
 *
 * Bei einer Feldrecherche steht der fertige Prompt (mit aufgeloesten
 * Platzhaltern und dem verlangten Antwortformat) schon an der Anfrage; die
 * Antwort wird in Wert und Begruendung zerlegt.
 */
class AskAiQuestionJob implements ShouldQueue
{
    use Queueable;

    /** So lange darf die KI fuer eine Antwort brauchen (Sekunden). */
    public const TIMEOUT = 300;

    public int $tries = 1;

    public int $timeout = self::TIMEOUT + 30;

    public function __construct(public int $questionId)
    {
        $this->onQueue(config('queue.ai_queue'));
    }

    public function handle(): void
    {
        // Nur ein Worker uebernimmt die Frage – auch wenn die Queue sie erneut ausliefert.
        $claimed = AiQuestion::query()
            ->whereKey($this->questionId)
            ->where('status', AiQuestion::STATUS_QUEUED)
            ->update(['status' => AiQuestion::STATUS_RUNNING, 'started_at' => now()]);

        $question = $claimed ? AiQuestion::find($this->questionId) : null;

        if (! $question) {
            return;
        }

        try {
            $service = AiProviders::webSearchFor($question->model);
            $answer = $service->searchWeb($question->prompt ?: $this->prompt($question), ['model' => $question->model, 'timeout' => self::TIMEOUT]);
            $usage = $service->lastUsage();
            $suggestion = $question->field ? self::parseSuggestion($answer) : ['value' => null, 'note' => null];

            $question->update([
                'status' => AiQuestion::STATUS_DONE,
                'answer' => $answer,
                'value' => $suggestion['value'],
                'note' => $suggestion['note'],
                'sources' => collect($service->lastSources())
                    ->filter(fn (array $source) => preg_match('#^https?://#i', (string) ($source['url'] ?? '')))
                    ->map(fn (array $source) => ['url' => AiCitations::cleanUrl($source['url']), 'title' => $source['title'] ?? null])
                    ->unique('url')
                    ->take(20)
                    ->values()
                    ->all(),
                'usage' => $usage ? $usage + ['cost' => AiSettings::cost($usage['model'], $usage['input_tokens'], $usage['output_tokens'])] : null,
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $question->update([
                'status' => AiQuestion::STATUS_FAILED,
                'error' => mb_substr(str_replace('Fehler bei der Kommunikation mit ChatGPT: ', '', $exception->getMessage()), 0, 500),
                'finished_at' => now(),
            ]);
        }
    }

    /**
     * Wert und Begruendung aus der Antwort einer Feldrecherche (JSON, auch in
     * einem Codeblock). Passt die Antwort nicht, bleiben beide leer – die
     * Rohantwort steht trotzdem an der Anfrage.
     *
     * @return array{value: ?string, note: ?string}
     */
    public static function parseSuggestion(string $answer): array
    {
        $start = strpos($answer, '{');
        $end = strrpos($answer, '}');
        $data = $start !== false && $end > $start ? json_decode(substr($answer, $start, $end - $start + 1), true) : null;

        $text = function (mixed $value): ?string {
            if (is_bool($value)) {
                return $value ? 'Ja' : 'Nein';
            }

            $value = is_scalar($value) ? trim((string) $value) : '';

            return $value === '' || in_array(mb_strtolower($value), ['null', 'unknown', 'unbekannt'], true) ? null : $value;
        };

        // Die Websuche setzt Quellenverweise in den Text, auch wenn der Prompt das untersagt.
        return is_array($data)
            ? ['value' => AiCitations::strip($text($data['value'] ?? null)) ?: null, 'note' => AiCitations::strip($text($data['note'] ?? null)) ?: null]
            : ['value' => null, 'note' => null];
    }

    /**
     * Die Frage mit dem Eintrag und Abschnitt, zu dem sie gestellt wurde.
     */
    protected function prompt(AiQuestion $question): string
    {
        $entry = trim(AiAreas::label($question->area).($question->title ? ' › „'.$question->title.'“' : ''));

        return <<<TEXT
            Du unterstützt die Redaktion der Passolution Travel Information Platform bei der Pflege von Reise-Stammdaten.
            Die Frage wurde im Eintrag {$entry}, Abschnitt „{$question->sectionLabel()}“ gestellt – beziehe sie darauf, sofern sie nichts anderes nennt.
            Antworte auf Deutsch, sachlich und so knapp wie möglich. Recherchiere bei Bedarf im Internet und bevorzuge amtliche Quellen. Formatiere mit Markdown.

            Frage:
            {$question->question}
            TEXT;
    }
}
