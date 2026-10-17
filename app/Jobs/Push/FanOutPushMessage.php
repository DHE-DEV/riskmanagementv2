<?php

namespace App\Jobs\Push;

use App\Models\Push\AppDevice;
use App\Models\Push\AppDeviceTravelLink;
use App\Models\Push\AppPushMessage;
use App\Services\Push\PushDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Versand ueber die API (POST /api/v1/push/messages): Zielgeraete seitenweise
 * ermitteln und auf Versand-Jobs verteilen – auch fuer "alle Geraete der App".
 */
class FanOutPushMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public int $messageId)
    {
        $this->onQueue(config('push.queue', 'push'));
    }

    public function handle(PushDispatcher $dispatcher): void
    {
        $message = AppPushMessage::find($this->messageId);
        if (! $message) {
            return;
        }

        $target = (array) $message->target;
        $tids = array_values(array_filter(array_map(fn ($tid) => AppDeviceTravelLink::normalizeTid($tid), (array) ($target['tids'] ?? []))));

        $query = AppDevice::reachable()->where('app', $message->app)->select('id');

        if (! empty($target['device_ids'])) {
            $query->whereIn('id', (array) $target['device_ids']);
        } elseif ($tids !== []) {
            $query->whereHas('travelLinks', fn (Builder $q) => $q->whereIn('tid', $tids));
        } elseif (empty($target['all'])) {
            $query->whereRaw('1 = 0');
        }

        $query->chunkById(max(1, (int) config('push.batch_size', 500)) * 10, function ($devices) use ($dispatcher, $message) {
            $dispatcher->dispatch($message, $devices->pluck('id')->all());
        });

        $dispatcher->release($message);
    }
}
