<?php

namespace App\Jobs\Push;

use App\Models\Push\AppDevice;
use App\Models\Push\AppPushMessage;
use App\Services\Push\PushSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Schickt einen Versand an bis zu push.batch_size Geraete. Kein automatischer
 * Wiederholversuch – ein zweiter Lauf wuerde bereits Benachrichtigte doppelt treffen.
 */
class SendPushBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    /**
     * @param  array<int, string>  $deviceIds
     * @param  array<string, string|null>  $tidByDevice  Geraete-ID => Reise-ID
     * @param  array<string, string|null>  $tripNames  Reise-ID => Reisename
     */
    public function __construct(
        public int $messageId,
        public array $deviceIds,
        public array $tidByDevice = [],
        public array $tripNames = [],
    ) {
        $this->onQueue(config('push.queue', 'push'));
    }

    public function handle(PushSender $sender): void
    {
        $message = AppPushMessage::find($this->messageId);
        if (! $message) {
            return;
        }

        $devices = AppDevice::reachable()->whereIn('id', $this->deviceIds)->get();
        $skipped = count($this->deviceIds) - $devices->count();

        $stats = $devices->isEmpty() ? ['sent' => 0, 'failed' => 0, 'invalid' => 0] : $sender->send($message, $devices, $this->tidByDevice, $this->tripNames);

        // Inzwischen abgemeldete oder stummgeschaltete Geraete zaehlen als fehlgeschlagen.
        $message->recordBatch($stats['sent'], $stats['failed'] + $skipped, $stats['invalid']);
    }

    public function failed(?Throwable $exception): void
    {
        AppPushMessage::find($this->messageId)?->recordBatch(0, count($this->deviceIds), 0);
    }
}
