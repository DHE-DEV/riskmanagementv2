<?php

namespace App\Jobs\Push;

use App\Models\Push\AppDevice;
use App\Services\Push\PushSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Stiller Push an die anderen Teilnehmer einer geteilten Reise (data.type = share_sync): ihre App laedt den
 * Stand neu, ohne Hinweis auf dem Bildschirm. Kein Eintrag in den Versaenden, kein Wiederholversuch.
 */
class SendSharingSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    /** @param  array<int, string>  $deviceIds */
    public function __construct(public string $tripId, public array $deviceIds)
    {
        $this->onQueue(config('push.queue', 'push'));
    }

    public function handle(PushSender $sender): void
    {
        // Auch ohne eingeschaltete Benachrichtigungen: der stille Push zeigt nichts an.
        $devices = AppDevice::whereIn('id', $this->deviceIds)->whereNotNull('push_token')->whereNull('token_invalid_at')->get();
        $sender->sendSilent($devices, ['type' => 'share_sync', 'trip' => $this->tripId]);
    }
}
