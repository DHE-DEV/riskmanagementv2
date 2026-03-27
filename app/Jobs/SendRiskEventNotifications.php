<?php

namespace App\Jobs;

use App\Models\CustomEvent;
use App\Models\DisasterEvent;
use App\Services\NotificationRuleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendRiskEventNotifications implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $uniqueFor = 30;

    public function __construct(
        public CustomEvent|DisasterEvent $event,
        public bool $force = false,
    ) {}

    public function uniqueId(): string
    {
        return class_basename($this->event) . '_' . $this->event->id;
    }

    public function handle(NotificationRuleService $service): void
    {
        // Beide Quellen separat verarbeiten, damit jede ihre eigene
        // Empfänger-Deduplizierung hat und Regeln korrekt greifen
        $sentCount = 0;

        if ($this->event instanceof CustomEvent) {
            $sentCount += $service->processCustomEvent($this->event, $this->force, 'travel-alert');
            $sentCount += $service->processCustomEvent($this->event, $this->force, 'global-travel-monitor');
        } else {
            $sentCount += $service->processDisasterEvent($this->event, $this->force, 'travel-alert');
            $sentCount += $service->processDisasterEvent($this->event, $this->force, 'global-travel-monitor');
        }

        Log::info('Risk-Event Benachrichtigungen verarbeitet', [
            'event_type' => class_basename($this->event),
            'event_id' => $this->event->id,
            'title' => $this->event->title,
            'sent_count' => $sentCount,
        ]);
    }
}
