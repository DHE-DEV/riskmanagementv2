<?php

namespace App\Jobs;

use App\Models\AiFieldReview;
use App\Services\AiFieldReviewService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Feldpruefung eines Abschnitts mit Websuche im Hintergrund (dauert oft laenger,
 * als eine Web-Anfrage darf). Zustand und Ergebnis stehen am Auftrag
 * (AiFieldReview); Formular und "KI-Auftraege" fragen ihn ab.
 */
class ReviewAiFieldsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = AiFieldReviewService::TIMEOUT + 30;

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, string>  $labels
     * @param  array<string, mixed>  $extra
     * @param  array<string, string>  $extraLabels
     */
    public function __construct(
        public int $reviewId,
        public array $context,
        public array $labels,
        public array $extra,
        public array $extraLabels,
        public ?string $hint = null,
    ) {
        $this->onQueue(config('queue.ai_queue'));
    }

    public function handle(AiFieldReviewService $service): void
    {
        // Nur ein Worker uebernimmt den Auftrag – auch wenn die Queue ihn erneut ausliefert.
        $claimed = AiFieldReview::query()
            ->whereKey($this->reviewId)
            ->where('status', AiFieldReview::STATUS_QUEUED)
            ->update(['status' => AiFieldReview::STATUS_RUNNING, 'started_at' => now()]);

        $review = $claimed ? AiFieldReview::find($this->reviewId) : null;

        if (! $review) {
            return;
        }

        try {
            $result = $service->review($review->area, $review->section, $this->context, $this->labels, $this->extra, $this->extraLabels, $review->model, $this->hint);

            $review->update(['status' => AiFieldReview::STATUS_DONE, 'result' => $result, 'finished_at' => now()]);
        } catch (Throwable $exception) {
            $review->update([
                'status' => AiFieldReview::STATUS_FAILED,
                'error' => mb_substr(str_replace('Fehler bei der Kommunikation mit ChatGPT: ', '', $exception->getMessage()), 0, 500),
                'finished_at' => now(),
            ]);
        }
    }
}
