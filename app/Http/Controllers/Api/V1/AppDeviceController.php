<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Push\AppDevice;
use App\Models\Push\AppDeviceTravelLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Geraete der Apps fuer Push-Benachrichtigungen:
 *   POST   /v1/devices                     anmelden (liefert ID und Secret, das Secret nur einmal)
 *   PUT    /v1/devices/{id}                Token, Sprache, an/aus aendern
 *   PUT    /v1/devices/{id}/travel-links   Reise-Links, fuer die das Geraet benachrichtigt werden will
 *   DELETE /v1/devices/{id}                abmelden
 * Alles ausser der Anmeldung braucht den Header X-Device-Secret.
 */
class AppDeviceController extends Controller
{
    public const MAX_TRAVEL_LINKS = 200;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(true));
        [$secret, $hash] = AppDevice::newSecret();

        $device = DB::transaction(function () use ($data, $hash, $request) {
            $this->releaseToken($data['push_token'] ?? null);

            $device = AppDevice::create(array_merge($this->attributes($data), [
                'customer_id' => $request->user()?->getKey(),
                'app' => $data['app'] ?? 'tripdata',
                'platform' => $data['platform'],
                'secret_hash' => $hash,
                'last_seen_at' => now(),
            ]));
            $device->ensureShareCode();

            return $device;
        });

        return response()->json(['success' => true, 'data' => array_merge($device->toApi(), ['secret' => $secret])], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $device = $this->device($request, $id);
        $data = $request->validate($this->rules(false));
        $nameBefore = $device->publicName();

        DB::transaction(function () use ($device, $data) {
            if (array_key_exists('push_token', $data) && $data['push_token'] !== $device->push_token) {
                $this->releaseToken($data['push_token'], $device->id);
                $device->token_invalid_at = null;
            }

            $device->fill($this->attributes($data));
            $device->last_seen_at = now();
            $device->save();
        });

        // Neuer Name: alle Geraete in gemeinsamen Gruppen und Reisen laden neu – der Name erscheint ueberall.
        if ($device->publicName() !== $nameBefore) {
            app(\App\Services\Push\SharingNotifier::class)->nameChanged($device);
        }

        return response()->json(['success' => true, 'data' => $device->toApi()]);
    }

    public function travelLinks(Request $request, string $id): JsonResponse
    {
        $device = $this->device($request, $id);
        $data = $request->validate([
            'tids' => ['present', 'array', 'max:'.self::MAX_TRAVEL_LINKS],
            'tids.*' => ['string', 'max:64'],
        ]);

        $tids = array_values(array_unique(array_filter(array_map(
            fn ($tid) => AppDeviceTravelLink::normalizeTid($tid),
            $data['tids'],
        ))));

        DB::transaction(function () use ($device, $tids) {
            $device->travelLinks()->whereNotIn('tid', $tids ?: [''])->delete();

            $known = $device->travelLinks()->pluck('tid')->all();
            $new = array_values(array_diff($tids, $known));
            if ($new !== []) {
                // Neue Links ab jetzt: aeltere Aenderungen loesen keinen Push aus.
                AppDeviceTravelLink::insert(array_map(fn (string $tid) => [
                    'app_device_id' => $device->id,
                    'tid' => $tid,
                    'created_at' => now(),
                ], $new));
            }

            $device->forceFill(['last_seen_at' => now()])->save();
        });

        return response()->json(['success' => true, 'data' => ['tids' => $tids]]);
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->device($request, $id)->delete();

        return response()->noContent();
    }

    private function device(Request $request, string $id): AppDevice
    {
        $device = AppDevice::find($id);
        abort_if(! $device, 404, 'Device not found.');
        abort_unless($device->secretMatches($request->header('X-Device-Secret')), 403, 'Invalid device secret.');

        return $device;
    }

    /**
     * Ein Push-Token gehoert genau einem Geraet: meldet sich die App neu an (z. B. nach
     * Neuinstallation), verliert der alte Eintrag den Token.
     */
    private function releaseToken(?string $token, ?string $exceptId = null): void
    {
        if (blank($token)) {
            return;
        }

        AppDevice::where('push_token', $token)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->update(['push_token' => null]);
    }

    private function rules(bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'app' => ['sometimes', 'string', Rule::in(['tripdata'])],
            'platform' => [$required, Rule::in([AppDevice::PLATFORM_IOS, AppDevice::PLATFORM_ANDROID])],
            'push_token' => ['sometimes', 'nullable', 'string', 'max:255'],
            'push_environment' => ['sometimes', Rule::in(['production', 'sandbox'])],
            'locale' => ['sometimes', 'string', 'max:8'],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:64'],
            'notifications_enabled' => ['sometimes', 'boolean'],
            'notify_itinerary' => ['sometimes', 'boolean'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:60'],
        ];
    }

    private function attributes(array $data): array
    {
        $attributes = array_intersect_key($data, array_flip(['push_token', 'push_environment', 'app_version', 'notifications_enabled', 'notify_itinerary']));

        if (array_key_exists('display_name', $data)) {
            $attributes['display_name'] = filled($data['display_name']) ? trim($data['display_name']) : null;
        }

        if (isset($data['locale'])) {
            $locale = strtolower(substr($data['locale'], 0, 2));
            $attributes['locale'] = in_array($locale, AppDevice::LOCALES, true) ? $locale : 'en';
        }

        return $attributes;
    }
}
