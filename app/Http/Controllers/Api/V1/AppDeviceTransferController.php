<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithAppDevice;
use App\Http\Controllers\Controller;
use App\Models\Push\AppDevice;
use App\Models\Push\AppDeviceTransfer;
use App\Services\Push\DeviceAdoption;
use App\Services\Push\SharingNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Uebertragung auf ein neues Geraet mit Bestaetigung am alten (alles mit X-Device-Secret, unter /v1/devices/{id}):
 *
 *   POST   transfers                       neue Installation fragt an {share_code, label?} (Push an das alte Geraet)
 *   GET    transfers/incoming              altes Geraet: offene Anfragen an dieses Geraet
 *   POST   transfers/{transfer}/approve    altes Geraet bestaetigt (vorher PUT backup, sonst 409)
 *   POST   transfers/{transfer}/decline    altes Geraet lehnt ab
 *   DELETE transfers/{transfer}            neue Installation bricht ab
 *   GET    transfers/{transfer}            neue Installation fragt den Stand ab (alle ~3 s); bei approved wird hier
 *                                          die Identitaet uebernommen und device (mit secret) und backup geliefert
 *
 * Nach der Uebernahme gibt es den Eintrag der neuen Installation nicht mehr: ein erneuter Abruf mit der alten
 * (neuen) ID und ihrem Secret liefert innerhalb von 10 Minuten noch einmal dasselbe Ergebnis.
 */
class AppDeviceTransferController extends Controller
{
    use RespondsWithAppDevice;

    public function store(Request $request, string $id, SharingNotifier $notifier): JsonResponse
    {
        $device = $this->device($request, $id);
        $data = $request->validate([
            'share_code' => ['required', 'string', 'max:32'],
            'label' => ['sometimes', 'nullable', 'string', 'max:80'],
        ]);

        $from = AppDevice::where('share_code', AppDevice::normalizeShareCode($data['share_code']) ?? '-')->first();
        if (! $from) {
            return $this->error(404, 'unknown_share_code', 'Diese App-ID gibt es nicht.');
        }
        if ($from->id === $device->id) {
            return $this->error(422, 'own_share_code', 'Das ist die App-ID dieses Geräts.');
        }

        $transfer = DB::transaction(function () use ($device, $from, $data) {
            AppDeviceTransfer::where('to_device_id', $device->id)->open()->update(['status' => AppDeviceTransfer::STATUS_CANCELLED]);

            return AppDeviceTransfer::create([
                'from_device_id' => $from->id,
                'to_device_id' => $device->id,
                'to_secret_hash' => $device->secret_hash,
                'to_platform' => $device->platform,
                'to_label' => filled($data['label'] ?? null) ? trim($data['label']) : null,
                'status' => AppDeviceTransfer::STATUS_PENDING,
                'expires_at' => now()->addMinutes(AppDeviceTransfer::TTL_MINUTES),
            ]);
        });

        $notifier->transferRequest($from, $transfer);

        return response()->json(['success' => true, 'data' => array_merge($transfer->toApi(), [
            'from_share_code' => AppDevice::formatShareCode($from->share_code),
        ])], 201);
    }

    public function incoming(Request $request, string $id): JsonResponse
    {
        $device = $this->device($request, $id);

        AppDeviceTransfer::where('from_device_id', $device->id)->open()->where('expires_at', '<=', now())
            ->update(['status' => AppDeviceTransfer::STATUS_EXPIRED]);

        $transfers = AppDeviceTransfer::where('from_device_id', $device->id)
            ->where('status', AppDeviceTransfer::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (AppDeviceTransfer $t) => [
                'id' => $t->id,
                'to_label' => $t->to_label,
                'to_platform' => $t->to_platform,
                'created_at' => $t->created_at->toIso8601String(),
                'expires_at' => $t->expires_at->toIso8601String(),
            ]);

        return response()->json(['success' => true, 'data' => $transfers]);
    }

    public function approve(Request $request, string $id, string $transfer): JsonResponse
    {
        $device = $this->device($request, $id);
        $transfer = $this->incomingTransfer($device, $transfer);
        if ($response = $this->notPending($transfer)) {
            return $response;
        }
        if (! $device->backup()->exists()) {
            return $this->error(409, 'backup_required', 'Erst sichern: Es gibt noch keine Sicherung dieses Geräts.');
        }

        // Ab der Bestaetigung hat die neue Installation wieder volle 15 Minuten fuer den Abruf.
        $transfer->forceFill([
            'status' => AppDeviceTransfer::STATUS_APPROVED,
            'approved_at' => now(),
            'expires_at' => now()->addMinutes(AppDeviceTransfer::TTL_MINUTES),
        ])->save();

        return response()->json(['success' => true, 'data' => $transfer->toApi()]);
    }

    public function decline(Request $request, string $id, string $transfer): JsonResponse
    {
        $device = $this->device($request, $id);
        $transfer = $this->incomingTransfer($device, $transfer);
        if ($response = $this->notPending($transfer)) {
            return $response;
        }

        $transfer->forceFill(['status' => AppDeviceTransfer::STATUS_DECLINED])->save();

        return response()->json(['success' => true, 'data' => $transfer->toApi()]);
    }

    public function destroy(Request $request, string $id, string $transfer): Response|JsonResponse
    {
        $device = $this->device($request, $id);
        $transfer = AppDeviceTransfer::where('to_device_id', $device->id)->find($transfer);
        abort_if(! $transfer, 404, 'Transfer not found.');

        if (in_array($transfer->status, [AppDeviceTransfer::STATUS_PENDING, AppDeviceTransfer::STATUS_APPROVED], true)) {
            $transfer->forceFill(['status' => AppDeviceTransfer::STATUS_CANCELLED])->save();
        } elseif ($transfer->status === AppDeviceTransfer::STATUS_COMPLETED) {
            return $this->error(409, 'transfer_completed', 'Die Übertragung ist schon abgeschlossen.', ['data' => $transfer->toApi()]);
        }

        return response()->noContent();
    }

    /**
     * Stand fuer die neue Installation. Bei approved: jetzt Identitaet uebernehmen → completed, mit device und backup.
     */
    public function show(Request $request, string $id, string $transfer, DeviceAdoption $adoption): Response|JsonResponse
    {
        $transfer = AppDeviceTransfer::where('to_device_id', $id)->find($transfer);
        $device = AppDevice::find($id);

        // Nach der Uebernahme gibt es den Eintrag der neuen Installation nicht mehr: dann gilt das Secret der Anfrage.
        if (! $device) {
            abort_if(! $transfer || $transfer->status !== AppDeviceTransfer::STATUS_COMPLETED, 404, 'Device not found.');
            abort_unless($transfer->secretMatches($request->header('X-Device-Secret')), 403, 'Invalid device secret.');
        } else {
            abort_unless($device->secretMatches($request->header('X-Device-Secret')), 403, 'Invalid device secret.');
            abort_if(! $transfer, 404, 'Transfer not found.');
        }

        $transfer->expireIfDue();
        $secret = null;

        if ($transfer->status === AppDeviceTransfer::STATUS_APPROVED && $device) {
            $secret = DB::transaction(function () use ($transfer, $device, $adoption) {
                $locked = AppDeviceTransfer::lockForUpdate()->find($transfer->id);
                if ($locked->status !== AppDeviceTransfer::STATUS_APPROVED) {
                    return null;
                }
                $old = AppDevice::lockForUpdate()->findOrFail($locked->from_device_id);
                $secret = $adoption->adopt($old, $device);
                $locked->forceFill([
                    'status' => AppDeviceTransfer::STATUS_COMPLETED,
                    'completed_at' => now(),
                    'secret_encrypted' => $secret,
                ])->save();

                return $secret;
            });
            $transfer->refresh();
        }

        if ($transfer->status === AppDeviceTransfer::STATUS_COMPLETED) {
            $secret ??= $transfer->keptSecret();
        }

        $body = array_merge($transfer->toApi(), ['from_share_code' => AppDevice::formatShareCode($transfer->fromDevice?->share_code)]);
        if ($secret === null || ! $transfer->fromDevice) {
            return response()->json(['success' => true, 'data' => $body]);
        }

        $old = $transfer->fromDevice;
        $placeholder = $this->placeholder();
        [$state, $raw] = $this->backupState($old, true, $placeholder);
        $body['device'] = array_merge($old->toApi(), ['secret' => $secret]);
        $body['backup'] = $state['exists'] ? $state : null;

        return $this->json(['success' => true, 'data' => $body], $raw, $placeholder);
    }

    private function incomingTransfer(AppDevice $device, string $id): AppDeviceTransfer
    {
        $transfer = AppDeviceTransfer::where('from_device_id', $device->id)->find($id);
        abort_if(! $transfer, 404, 'Transfer not found.');

        return $transfer->expireIfDue();
    }

    private function notPending(AppDeviceTransfer $transfer): ?JsonResponse
    {
        if ($transfer->status === AppDeviceTransfer::STATUS_PENDING) {
            return null;
        }

        return $this->error(409, 'transfer_not_pending', 'Diese Anfrage ist nicht mehr offen.', ['data' => $transfer->toApi()]);
    }
}
