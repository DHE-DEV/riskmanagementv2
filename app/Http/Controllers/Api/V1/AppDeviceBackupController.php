<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithAppDevice;
use App\Http\Controllers\Controller;
use App\Models\Push\AppDevice;
use App\Models\Push\AppDeviceBackup;
use App\Services\Push\DeviceAdoption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Sicherung und Wiederherstellung der TripData-App (alles mit X-Device-Secret, unter /v1/devices/{id}):
 *
 *   PUT    backup          Sicherung speichern bzw. ersetzen {schema, platform, app_version?, counts?, data}
 *                          (data hoechstens 8 MB unkomprimiert; Body optional mit Content-Encoding: gzip)
 *   GET    backup          Stand der Sicherung mit data (?meta=1 ohne data)
 *   DELETE backup          Sicherung loeschen
 *   POST   recovery-code   neuen Wiederherstellungscode erzeugen (Klartext nur in dieser Antwort, alter Code ungueltig)
 *   POST   adopt           neue Installation uebernimmt ein Geraet {share_code, recovery_code} (Limit app-device-adopt)
 *
 * Der Inhalt der Sicherung ist fuer die Plattform undurchsichtiges JSON; geprueft werden nur Groesse und Grundform.
 */
class AppDeviceBackupController extends Controller
{
    use RespondsWithAppDevice;

    /** Rohdaten des Requests hoechstens so gross: Sicherung plus Kopfdaten. */
    private const MAX_REQUEST_BYTES = AppDeviceBackup::MAX_BYTES + 256 * 1024;

    public function update(Request $request, string $id): JsonResponse
    {
        $device = $this->device($request, $id);

        $content = $this->content($request);
        if ($content instanceof JsonResponse) {
            return $content;
        }

        $body = json_decode($content);
        if (! $body instanceof \stdClass) {
            return $this->error(422, 'invalid_json', 'Der Inhalt ist kein JSON-Objekt.');
        }

        $input = [
            'schema' => $body->schema ?? null,
            'platform' => $body->platform ?? null,
            'app_version' => $body->app_version ?? null,
            'counts' => isset($body->counts) ? (is_object($body->counts) ? (array) $body->counts : $body->counts) : null,
            'data' => isset($body->data) ? (is_object($body->data) ? ['object'] : $body->data) : null,
        ];
        Validator::make($input, [
            'schema' => ['required', 'integer', 'min:1', 'max:65535'],
            'platform' => ['required', Rule::in([AppDevice::PLATFORM_IOS, AppDevice::PLATFORM_ANDROID])],
            'app_version' => ['nullable', 'string', 'max:64'],
            'counts' => ['nullable', 'array', 'max:50'],
            'counts.*' => ['integer', 'min:0'],
            'data' => ['required', 'array'],
        ])->after(function ($validator) use ($body) {
            if (isset($body->data) && ! is_object($body->data)) {
                $validator->errors()->add('data', 'data muss ein JSON-Objekt sein.');
            }
            if (isset($body->counts) && ! is_object($body->counts)) {
                $validator->errors()->add('counts', 'counts muss ein JSON-Objekt sein.');
            }
        })->validate();

        $data = json_encode($body->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        if (strlen($data) > AppDeviceBackup::MAX_BYTES) {
            return $this->tooLarge();
        }

        $backup = AppDeviceBackup::updateOrCreate(['app_device_id' => $device->id], [
            'schema' => (int) $body->schema,
            'platform' => $body->platform,
            'app_version' => $body->app_version ?? null,
            'payload' => gzencode($data, 6),
            'size' => strlen($data),
            'counts' => $body->counts ?? null,
            'backed_up_at' => now(),
        ]);
        $device->forceFill(['last_seen_at' => now()])->save();

        return response()->json(['success' => true, 'data' => [
            'backed_up_at' => $backup->backed_up_at->toIso8601String(),
            'size' => $backup->size,
            'schema' => $backup->schema,
            'platform' => $backup->platform,
            'counts' => $backup->counts ?? new \stdClass,
        ]]);
    }

    public function show(Request $request, string $id): Response|JsonResponse
    {
        $device = $this->device($request, $id);
        $placeholder = $this->placeholder();
        [$state, $raw] = $this->backupState($device, ! $request->boolean('meta'), $placeholder);

        return $this->json(['success' => true, 'data' => $state], $raw, $placeholder);
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->device($request, $id)->backup()->delete();

        return response()->noContent();
    }

    public function recoveryCode(Request $request, string $id): JsonResponse
    {
        $device = $this->device($request, $id);
        $code = $device->newRecoveryCode();

        return response()->json(['success' => true, 'data' => [
            'code' => AppDevice::formatRecoveryCode($code),
            'created_at' => $device->recovery_created_at->toIso8601String(),
        ]], 201);
    }

    /**
     * Neue Installation (eigene, frisch angemeldete ID und Secret) uebernimmt das Geraet mit dieser App-ID,
     * wenn der Wiederherstellungscode stimmt. Falsche Angaben → 422 mit neutraler Meldung.
     */
    public function adopt(Request $request, string $id, DeviceAdoption $adoption): Response|JsonResponse
    {
        $new = $this->device($request, $id);
        $data = $request->validate([
            'share_code' => ['required', 'string', 'max:32'],
            'recovery_code' => ['required', 'string', 'max:32'],
        ]);

        $old = AppDevice::where('share_code', AppDevice::normalizeShareCode($data['share_code']) ?? '-')->first();
        if (! $old || $old->id === $new->id || ! $old->recoveryCodeMatches($data['recovery_code'])) {
            return $this->error(422, 'invalid_recovery', 'App-ID oder Wiederherstellungscode stimmt nicht.');
        }

        $secret = DB::transaction(function () use ($old, $new, $adoption) {
            $old = AppDevice::lockForUpdate()->findOrFail($old->id);

            return $adoption->adopt($old, $new);
        });
        $old->refresh();

        $placeholder = $this->placeholder();
        [$state, $raw] = $this->backupState($old, true, $placeholder);

        return $this->json(['success' => true, 'data' => [
            'device' => array_merge($old->toApi(), ['secret' => $secret]),
            'backup' => $state['exists'] ? $state : null,
        ]], $raw, $placeholder);
    }

    /** Rohdaten, bei Content-Encoding: gzip entpackt; zu gross → 413. */
    private function content(Request $request): string|JsonResponse
    {
        $content = (string) $request->getContent();
        if (strlen($content) > self::MAX_REQUEST_BYTES) {
            return $this->tooLarge();
        }
        if (strtolower((string) $request->header('Content-Encoding')) !== 'gzip') {
            return $content;
        }

        $inflate = inflate_init(ZLIB_ENCODING_GZIP);
        $out = '';
        foreach (str_split($content, 65536) as $chunk) {
            $part = @inflate_add($inflate, $chunk, ZLIB_SYNC_FLUSH);
            if ($part === false) {
                return $this->error(400, 'invalid_gzip', 'Der Inhalt ist kein gültiges gzip.');
            }
            $out .= $part;
            if (strlen($out) > self::MAX_REQUEST_BYTES) {
                return $this->tooLarge();
            }
        }

        return $out;
    }

    private function tooLarge(): JsonResponse
    {
        return $this->error(413, 'backup_too_large', 'Die Sicherung ist zu groß (höchstens 8 MB).');
    }
}
