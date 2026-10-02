<?php

namespace App\Console\Commands;

use App\Services\AiEventSearchService;
use Illuminate\Console\Command;

/**
 * Fuehrt die unter System > KI hinterlegten Suchen nach Ereignissen aus,
 * deren Zeitpunkt erreicht ist.
 */
class RunAiEventSearches extends Command
{
    protected $signature = 'ai:run-event-searches';

    protected $description = 'Führt fällige hinterlegte KI-Suchen nach Ereignissen aus';

    public function handle(AiEventSearchService $service): int
    {
        // Jede Suche durchsucht das Internet und dauert ein bis zwei Minuten.
        set_time_limit(0);

        $result = $service->runDueProfiles();

        $this->info("{$result['run']} Suche(n) ausgeführt, {$result['failed']} fehlgeschlagen.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
