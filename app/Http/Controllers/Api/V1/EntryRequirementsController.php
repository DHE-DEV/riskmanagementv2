<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GET /v1/entry-requirements?countries=CL,PE&nationality=AT&lang=de
 *
 * Einreise-, Visa- und Gesundheitsbestimmungen je Reiseland fuer eine Nationalitaet – fuer die TripData-App,
 * wenn Reisende ihre eigene Nationalitaet hinterlegt haben (statt der Nationalitaet des Reise-Links).
 * Quelle ist die PDS-API (content/all/html); die Antwort hat dieselbe Form wie `info.travel` der
 * Reisenden-Seite: {travel: {CL: {records: [...]}}}. Zwischengespeichert fuer 6 Stunden.
 */
class EntryRequirementsController extends Controller
{
    public const MAX_COUNTRIES = 20;

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'countries' => ['required', 'string', 'max:200'],
            'nationality' => ['required', 'string', 'size:2', 'alpha'],
            'lang' => ['sometimes', 'in:de,en,nl'],
        ]);

        $countries = collect(explode(',', strtoupper($data['countries'])))
            ->map(fn ($c) => trim($c))->filter(fn ($c) => preg_match('/^[A-Z]{2}$/', $c))->unique()->take(self::MAX_COUNTRIES)->values();
        abort_if($countries->isEmpty(), 422, 'No valid country codes.');
        $nationality = strtoupper($data['nationality']);
        $lang = $data['lang'] ?? 'de';

        $key = 'entry-requirements:'.$lang.':'.$nationality.':'.$countries->sort()->implode(',');
        $travel = Cache::remember($key, now()->addHours(6), fn () => $this->fetch($countries->all(), $nationality, $lang));
        abort_if($travel === null, 502, 'Entry requirements are currently unavailable.');

        return response()->json(['success' => true, 'data' => ['nationality' => $nationality, 'travel' => $travel]]);
    }

    /** @return array<string, array{records: array<int, array<string, mixed>>}>|null */
    private function fetch(array $countries, string $nationality, string $lang): ?array
    {
        $url = rtrim((string) config('services.passolution.api_url'), '/');
        $key = config('services.passolution.api_key');
        if ($url === '' || blank($key)) {
            return null;
        }

        try {
            $response = Http::withToken($key)->acceptJson()->timeout(30)
                ->get($url.'/content/all/html', ['lang' => $lang, 'countries' => implode(',', $countries), 'nat' => $nationality]);
        } catch (\Throwable $e) {
            Log::warning('Einreisebestimmungen nicht abrufbar', ['error' => $e->getMessage()]);

            return null;
        }
        if (! $response->successful()) {
            Log::warning('Einreisebestimmungen nicht abrufbar', ['status' => $response->status()]);

            return null;
        }

        $travel = [];
        foreach ((array) $response->json('records') as $record) {
            if (! is_array($record) || empty($record['destination'])) {
                continue;
            }
            // Flaggen als data-URI braucht die App nicht (sie hat eigene).
            unset($record['destination_flag'], $record['nationality_flag']);
            $travel[strtoupper($record['destination'])]['records'][] = $record;
        }

        return $travel;
    }
}
