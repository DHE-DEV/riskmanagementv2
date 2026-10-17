<?php

namespace App\Services\Push;

use App\Jobs\Push\SendPushBatch;
use App\Models\Push\AppPushMessage;
use Illuminate\Support\Facades\DB;

/**
 * Verteilt einen Versand auf Jobs zu je push.batch_size Geraeten. Der Versand haelt
 * waehrend des Verteilens einen "Platzhalter" in pending_batches, damit er nicht
 * als fertig gilt, solange noch Jobs dazukommen (release() gibt ihn frei).
 */
class PushDispatcher
{
    public function begin(array $attributes): AppPushMessage
    {
        return AppPushMessage::create(array_merge([
            'app' => 'tripdata',
            'status' => AppPushMessage::STATUS_SENDING,
            'pending_batches' => 1,
        ], $attributes));
    }

    /**
     * @param  array<int, string>  $deviceIds
     * @param  array<string, string|null>  $tidByDevice
     * @param  array<string, string|null>  $tripNames
     */
    public function dispatch(AppPushMessage $message, array $deviceIds, array $tidByDevice = [], array $tripNames = []): void
    {
        foreach (array_chunk($deviceIds, max(1, (int) config('push.batch_size', 500))) as $chunk) {
            $tids = array_intersect_key($tidByDevice, array_flip($chunk));

            DB::table($message->getTable())->where('id', $message->id)->update([
                'targeted' => DB::raw('targeted + '.count($chunk)),
                'pending_batches' => DB::raw('pending_batches + 1'),
            ]);

            SendPushBatch::dispatch(
                $message->id,
                $chunk,
                $tids,
                array_intersect_key($tripNames, array_flip(array_filter($tids))),
            );
        }
    }

    public function release(AppPushMessage $message): void
    {
        $message->recordBatch(0, 0, 0);
    }
}
