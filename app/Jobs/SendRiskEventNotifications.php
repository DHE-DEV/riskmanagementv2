<?php

namespace App\Jobs;

use App\Models\CustomEvent;
use App\Models\DisasterEvent;
use App\Services\NotificationRuleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendRiskEventNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public CustomEvent|DisasterEvent $event,
    ) {}

    public function handle(NotificationRuleService $service): void
    {
        $sentCount = match (true) {
            $this->event instanceof CustomEvent => $service->processCustomEvent($this->event),
            $this->event instanceof DisasterEvent => $service->processDisasterEvent($this->event),
        };

        Log::info('Risk-Event Benachrichtigungen verarbeitet', [
            'event_type' => class_basename($this->event),
            'event_id' => $this->event->id,
            'title' => $this->event->title,
            'sent_count' => $sentCount,
        ]);
    }
}
