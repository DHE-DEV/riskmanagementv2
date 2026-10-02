<?php

namespace App\Jobs;

use App\Models\AiEventSearch;
use App\Services\AiEventSearchService;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Fuehrt einen Lauf der KI-Suche nach aktuellen Ereignissen aus.
 *
 * Die Suche dauert laenger als eine gewoehnliche Seitenanfrage. Sie wird
 * deshalb nach dem Absenden der Antwort im selben Prozess erledigt
 * (dispatchAfterResponse) – ohne dass ein Queue-Worker laufen muss; die
 * Seite fragt den Stand in kurzen Abstaenden ab.
 */
class RunAiEventSearch
{
    use Dispatchable;

    public function __construct(public int $searchId) {}

    public function handle(AiEventSearchService $service): void
    {
        set_time_limit(360);

        $search = AiEventSearch::find($this->searchId);

        if ($search && $search->status === AiEventSearch::STATUS_RUNNING) {
            $service->run($search);
        }
    }
}
