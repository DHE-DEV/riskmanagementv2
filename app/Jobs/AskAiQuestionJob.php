<?php

namespace App\Jobs;

use App\Models\AiQuestion;
use App\Support\AdminV2\AiAreas;
use App\Support\AiProviders;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Beantwortet eine freie Frage an die KI im Hintergrund – mit Websuche des
 * gewaehlten Anbieters (ChatGPT, Claude oder Gemini). Zustand und Antwort
 * stehen an der Frage (AiQuestion); das Formular fragt sie ab.
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
            $answer = $service->searchWeb($this->prompt($question), ['model' => $question->model, 'timeout' => self::TIMEOUT]);

            $question->update([
                'status' => AiQuestion::STATUS_DONE,
                'answer' => $answer,
                'sources' => collect($service->lastSources())
                    ->filter(fn (array $source) => preg_match('#^https?://#i', (string) ($source['url'] ?? '')))
                    ->map(fn (array $source) => ['url' => $source['url'], 'title' => $source['title'] ?? null])
                    ->take(20)
                    ->values()
                    ->all(),
                'usage' => $service->lastUsage(),
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
