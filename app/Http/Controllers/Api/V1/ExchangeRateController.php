<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ExchangeRateController extends Controller
{
    /**
     * Aktuelle Wechselkurse, optional mit anderer Basiswaehrung (?base=USD)
     * und auf einzelne Waehrungen begrenzt (?symbols=EGP,THB).
     */
    public function latest(Request $request, ExchangeRateService $service): JsonResponse
    {
        $request->validate([
            'base' => ['nullable', 'string', 'regex:/^[A-Za-z]{3}$/'],
            'symbols' => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $data = $service->withBase((string) $request->query('base', ExchangeRateService::BASE));
        } catch (RuntimeException $exception) {
            $status = str_starts_with($exception->getMessage(), 'Unknown base') ? 422 : 503;

            return response()->json(['success' => false, 'message' => $exception->getMessage()], $status);
        }

        if ($request->filled('symbols')) {
            $wanted = array_map('strtoupper', array_filter(array_map('trim', explode(',', (string) $request->query('symbols')))));
            $data['rates'] = array_intersect_key($data['rates'], array_flip($wanted));
        }

        return response()->json(['success' => true, 'data' => $data]);
    }
}
