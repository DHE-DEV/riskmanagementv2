<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\Push\FanOutPushMessage;
use App\Models\Push\AppPushMessage;
use App\Services\Push\PushDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Push-Benachrichtigungen ueber die Plattform-API verschicken (Token mit push:send):
 *   POST /v1/push/messages        Versand anlegen – laeuft im Hintergrund, Antwort 202
 *   GET  /v1/push/messages/{id}   Stand und Zaehler eines eigenen Versands
 */
class PushMessageController extends Controller
{
    public function store(Request $request, PushDispatcher $dispatcher): JsonResponse
    {
        $this->authorizeToken($request);

        $data = $request->validate([
            'app' => ['sometimes', 'string', 'in:tripdata'],
            'title' => ['required'],
            'title.*' => ['string', 'max:120'],
            'body' => ['required'],
            'body.*' => ['string', 'max:500'],
            'data' => ['sometimes', 'array', 'max:10'],
            'data.*' => ['nullable', 'string', 'max:500'],
            'target' => ['required', 'array'],
            'target.all' => ['sometimes', 'boolean'],
            'target.tids' => ['sometimes', 'array', 'max:1000'],
            'target.tids.*' => ['string', 'max:64'],
            'target.device_ids' => ['sometimes', 'array', 'max:1000'],
            'target.device_ids.*' => ['string', 'max:32'],
        ]);

        $title = $this->texts($data['title'], 120);
        $body = $this->texts($data['body'], 500);
        $target = array_intersect_key($data['target'], array_flip(['all', 'tids', 'device_ids']));

        abort_if($title === [] || $body === [], 422, 'title and body must be a string or an object like {"de": "...", "en": "..."}.');
        abort_if(empty($target['all']) && empty($target['tids']) && empty($target['device_ids']), 422, 'target needs all, tids or device_ids.');

        $message = $dispatcher->begin([
            'app' => $data['app'] ?? 'tripdata',
            'type' => AppPushMessage::TYPE_CUSTOM,
            'source' => AppPushMessage::SOURCE_API,
            'customer_id' => $request->user()->getKey(),
            'title' => $title,
            'body' => $body,
            'data' => $data['data'] ?? null,
            'target' => $target,
        ]);

        FanOutPushMessage::dispatch($message->id);

        return response()->json(['success' => true, 'data' => $message->fresh()->toApi()], 202);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeToken($request);

        $message = AppPushMessage::where('customer_id', $request->user()->getKey())->findOrFail($id);

        return response()->json(['success' => true, 'data' => $message->toApi()]);
    }

    private function authorizeToken(Request $request): void
    {
        abort_unless($request->user()?->tokenCan('push:send'), 403, 'Token does not have the push:send ability.');
    }

    /**
     * "Text" oder {"de": "...", "en": "..."} → Sprache => Text.
     *
     * @return array<string, string>
     */
    private function texts(mixed $value, int $max): array
    {
        if (is_string($value)) {
            return trim($value) !== '' ? ['de' => mb_substr(trim($value), 0, $max)] : [];
        }

        $texts = [];
        foreach ((array) $value as $locale => $text) {
            if (is_string($locale) && preg_match('/^[a-z]{2}$/', $locale) && is_string($text) && trim($text) !== '') {
                $texts[$locale] = mb_substr(trim($text), 0, $max);
            }
        }

        return $texts;
    }
}
