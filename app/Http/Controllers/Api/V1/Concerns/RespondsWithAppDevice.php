<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Push\AppDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Gemeinsames fuer Sicherung und Uebertragung: Geraet mit Secret pruefen, Stand der Sicherung bauen und Antworten
 * mit dem (bis zu 8 MB grossen) JSON der App, ohne es dafuer zu dekodieren.
 */
trait RespondsWithAppDevice
{
    private function device(Request $request, string $id): AppDevice
    {
        $device = AppDevice::find($id);
        abort_if(! $device, 404, 'Device not found.');
        abort_unless($device->secretMatches($request->header('X-Device-Secret')), 403, 'Invalid device secret.');

        return $device;
    }

    /** Fehler mit festem Code fuer die App. */
    private function error(int $status, string $code, string $message, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['success' => false, 'code' => $code, 'message' => $message], $extra), $status);
    }

    /**
     * Stand der Sicherung wie bei GET backup. Mit $withData steht in data der Platzhalter $placeholder,
     * den json() durch das gesicherte JSON ersetzt.
     *
     * @return array{0: array<string, mixed>, 1: string|null} Stand und gesichertes JSON
     */
    private function backupState(AppDevice $device, bool $withData, ?string $placeholder = null): array
    {
        $backup = $device->backup()->first();
        $state = array_merge(
            ['exists' => (bool) $backup],
            $backup ? $backup->toApi() : array_fill_keys(['backed_up_at', 'size', 'schema', 'platform', 'app_version', 'counts'], null),
            [
                'has_recovery_code' => filled($device->recovery_hash),
                'recovery_code_created_at' => $device->recovery_created_at?->toIso8601String(),
            ],
        );

        $raw = null;
        if ($withData) {
            $raw = $backup?->rawData();
            $state['data'] = $raw !== null ? $placeholder : null;
        }

        return [$state, $raw];
    }

    /** JSON-Antwort; ein Platzhalter-String wird durch das gesicherte JSON ersetzt. */
    private function json(array $body, ?string $raw = null, ?string $placeholder = null, int $status = 200): Response|JsonResponse
    {
        if ($raw === null || $placeholder === null) {
            return response()->json($body, $status);
        }

        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return response(str_replace('"'.$placeholder.'"', $raw, $json), $status, ['Content-Type' => 'application/json']);
    }

    private function placeholder(): string
    {
        return '@@backup-data-'.Str::random(32).'@@';
    }
}
