<?php

namespace App\Services\Push;

use App\Models\Push\AppDevice;
use App\Models\Push\AppPushMessage;
use Illuminate\Support\Collection;

/**
 * Baut aus einem Versand die Nachricht je Geraet (Sprache, Reise) und schickt sie
 * ueber APNs bzw. FCM. Ungueltige Tokens werden am Geraet vermerkt.
 */
class PushSender
{
    public function __construct(
        private readonly ApnsClient $apns,
        private readonly FcmClient $fcm,
    ) {}

    /**
     * @param  Collection<int, AppDevice>  $devices
     * @param  array<string, string|null>  $tidByDevice  Geraete-ID => Reise-ID (bei Reise-Aenderungen)
     * @param  array<string, string|null>  $tripNames  Reise-ID => Reisename
     * @return array{sent: int, failed: int, invalid: int}
     */
    public function send(AppPushMessage $message, Collection $devices, array $tidByDevice = [], array $tripNames = []): array
    {
        $apns = [];
        $fcm = [];

        foreach ($devices as $device) {
            $tid = $tidByDevice[$device->id] ?? null;
            [$title, $body, $data] = $this->content($message, $device->locale, $tid, $tid ? ($tripNames[$tid] ?? null) : null);
            $collapse = $tid ? 'trip-'.$tid : ($message->type.'-'.$message->id);

            if ($device->platform === AppDevice::PLATFORM_ANDROID) {
                $fcm[$device->id] = [
                    'token' => $device->push_token,
                    'title' => $title,
                    'body' => $body,
                    'data' => array_map('strval', $data),
                    'collapse_key' => $collapse,
                ];
            } else {
                $apns[$device->id] = [
                    'token' => $device->push_token,
                    'environment' => $device->push_environment,
                    'collapse_id' => $collapse,
                    'payload' => array_merge([
                        'aps' => array_filter([
                            'alert' => ['title' => $title, 'body' => $body],
                            'sound' => 'default',
                            'thread-id' => $tid ? 'trip-'.$tid : null,
                        ]),
                    ], $data),
                ];
            }
        }

        $results = $this->apns->send($apns) + $this->fcm->send($fcm);

        $invalid = array_keys(array_filter($results, fn (string $r) => $r === ApnsClient::RESULT_INVALID));
        if ($invalid !== []) {
            AppDevice::whereIn('id', $invalid)->update(['token_invalid_at' => now()]);
        }

        $counts = array_count_values($results);

        return [
            'sent' => $counts[ApnsClient::RESULT_SENT] ?? 0,
            'failed' => ($counts[ApnsClient::RESULT_FAILED] ?? 0) + max(0, $devices->count() - count($results)),
            'invalid' => count($invalid),
        ];
    }

    /**
     * Stiller Push nur mit Daten (iOS content-available, Android Datennachricht) – die App laedt im Hintergrund neu.
     *
     * @param  Collection<int, AppDevice>  $devices
     * @param  array<string, string>  $data
     */
    public function sendSilent(Collection $devices, array $data): void
    {
        $apns = [];
        $fcm = [];
        foreach ($devices as $device) {
            if ($device->platform === AppDevice::PLATFORM_ANDROID) {
                $fcm[$device->id] = ['token' => $device->push_token, 'data' => array_map('strval', $data), 'silent' => true, 'collapse_key' => 'share-sync'];
            } else {
                $apns[$device->id] = [
                    'token' => $device->push_token,
                    'environment' => $device->push_environment,
                    'silent' => true,
                    'collapse_id' => 'share-sync',
                    'payload' => ['aps' => ['content-available' => 1]] + $data,
                ];
            }
        }

        $results = $this->apns->send($apns) + $this->fcm->send($fcm);
        $invalid = array_keys(array_filter($results, fn (string $r) => $r === ApnsClient::RESULT_INVALID));
        if ($invalid !== []) {
            AppDevice::whereIn('id', $invalid)->update(['token_invalid_at' => now()]);
        }
    }

    /**
     * Titel, Text und Zusatzdaten in der Sprache des Geraets. "{trip}" wird zum Reisenamen.
     *
     * @return array{0: string, 1: string, 2: array<string, string>}
     */
    public function content(AppPushMessage $message, string $locale, ?string $tid = null, ?string $tripName = null): array
    {
        $trip = filled($tripName) ? (string) $tripName : PushTexts::fallbackTrip($locale);
        $title = str_replace('{trip}', $trip, $message->text('title', $locale));
        $body = str_replace('{trip}', $trip, $message->text('body', $locale));

        $data = array_filter((array) $message->data, fn ($v) => is_scalar($v));
        $data['type'] = $message->type;
        if ($tid) {
            $data['tid'] = $tid;
            $data['url'] = 'tripdata://reise/'.$tid;
        }

        return [$title, $body, array_map('strval', $data)];
    }
}
