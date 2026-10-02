<?php

namespace App\Console\Commands;

use App\Services\AdminTaskRecurrenceService;
use Illuminate\Console\Command;

/**
 * Legt die Aufgaben aus den wiederkehrenden Aufgaben an, deren Termin
 * erreicht ist (System > Wiederkehrende Aufgaben).
 */
class CreateRecurringAdminTasks extends Command
{
    protected $signature = 'tasks:create-recurring';

    protected $description = 'Legt fällige wiederkehrende Aufgaben im Admin-Bereich an';

    public function handle(AdminTaskRecurrenceService $service): int
    {
        $result = $service->runDue();

        $this->info("{$result['created']} Aufgabe(n) angelegt, {$result['skipped']} übersprungen, {$result['failed']} fehlgeschlagen.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
