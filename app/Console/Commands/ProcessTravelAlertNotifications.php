<?php

namespace App\Console\Commands;

use App\Models\NotificationQueueLog;
use App\Models\NotificationRule;
use App\Services\NotificationRuleService;
use Illuminate\Console\Command;

class ProcessTravelAlertNotifications extends Command
{
    protected $signature = 'notifications:process-travel-alert';

    protected $description = 'Verarbeitet die Travel-Alert-Benachrichtigungsqueue';

    public function handle(NotificationRuleService $service): int
    {
        $queueName = 'travel-alert-notifications';

        $log = NotificationQueueLog::create([
            'queue_name' => $queueName,
            'started_at' => now(),
            'status' => 'running',
        ]);

        $this->info("[{$queueName}] Queue gestartet...");

        try {
            $result = $service->processUnnotifiedEvents(NotificationRule::SOURCE_TRAVEL_ALERT);

            $log->update([
                'completed_at' => now(),
                'events_processed' => $result['events_processed'],
                'notifications_sent' => $result['notifications_sent'],
                'errors' => $result['errors'],
                'status' => 'completed',
            ]);

            $this->info("[{$queueName}] Abgeschlossen: {$result['events_processed']} Events, {$result['notifications_sent']} Benachrichtigungen, {$result['errors']} Fehler");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $log->update([
                'completed_at' => now(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->error("[{$queueName}] Fehler: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
