<?php

namespace App\Support\AdminV2;

use App\Models\RegionInfoRun;
use Illuminate\Support\Facades\DB;

/**
 * Die KI-Vorbefuellung mehrerer Regionen (Stammdaten > Regionen). Es laeuft
 * hoechstens ein Lauf; FillRegionInfoJob arbeitet ihn Stueck fuer Stueck ab.
 * Aenderungen am Stand laufen unter einer Zeilensperre.
 */
class RegionInfoFillRun
{
    /**
     * Der laufende Lauf oder der zuletzt beendete, solange er nicht ausgeblendet ist.
     */
    public static function current(): ?RegionInfoRun
    {
        return RegionInfoRun::query()
            ->where('kind', RegionInfoRun::KIND_FILL)
            ->where('status', '!=', RegionInfoRun::STATUS_DISMISSED)
            ->latest('id')
            ->first();
    }

    public static function isRunning(): bool
    {
        return RegionInfoRun::query()->where('kind', RegionInfoRun::KIND_FILL)->where('status', RegionInfoRun::STATUS_RUNNING)->exists();
    }

    /**
     * @param  array<int, int>  $ids
     */
    public static function start(array $ids, bool $overwrite, ?int $userId): RegionInfoRun
    {
        // Ein frueher beendeter Lauf verschwindet aus der Anzeige.
        RegionInfoRun::query()->where('kind', RegionInfoRun::KIND_FILL)->where('status', '!=', RegionInfoRun::STATUS_RUNNING)->update(['status' => RegionInfoRun::STATUS_DISMISSED]);

        return RegionInfoRun::create([
            'kind' => RegionInfoRun::KIND_FILL,
            'status' => RegionInfoRun::STATUS_RUNNING,
            'pending' => array_values(array_map('intval', $ids)),
            'total' => count($ids),
            'failed' => [],
            'overwrite' => $overwrite,
            'started_by' => $userId,
        ]);
    }

    /**
     * Den Stand unter einer Zeilensperre aendern.
     *
     * @param  callable(RegionInfoRun): mixed  $change
     */
    public static function update(int $runId, callable $change): ?RegionInfoRun
    {
        return DB::transaction(function () use ($runId, $change) {
            $run = RegionInfoRun::query()->lockForUpdate()->find($runId);

            if (! $run) {
                return null;
            }

            $change($run);
            $run->save();

            return $run;
        });
    }

    public static function cancel(): void
    {
        $run = RegionInfoRun::query()->where('kind', RegionInfoRun::KIND_FILL)->where('status', RegionInfoRun::STATUS_RUNNING)->latest('id')->first();

        if ($run) {
            self::update($run->id, function (RegionInfoRun $run) {
                $run->status = RegionInfoRun::STATUS_CANCELLED;
                $run->pending = [];
                $run->finished_at = now();
            });
        }
    }

    public static function dismiss(): void
    {
        RegionInfoRun::query()->where('kind', RegionInfoRun::KIND_FILL)->where('status', '!=', RegionInfoRun::STATUS_RUNNING)->update(['status' => RegionInfoRun::STATUS_DISMISSED]);
    }
}
