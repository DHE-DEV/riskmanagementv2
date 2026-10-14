<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\QrCode\QrCodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * QR-Codes fuer die Kunden-API – dieselben Arten wie unter adminv2 > System > QR-Codes.
 *
 *   GET /v1/qr-codes/types                                   – verfuegbare Arten
 *   GET /v1/qr-codes?type=tripdata&value=ZIPT-HRG1-Z6Y5       – Inhalt und SVG als JSON
 *   GET /v1/qr-codes?…&format=svg                             – SVG-Grafik
 *   GET /v1/qr-codes?…&format=pdf&title=Meine%20Reise         – A4-PDF zum Herunterladen
 *
 * Arten: tripdata (Vorgabe), tripdata-app, travel-details (mit lang=de|en|nl), free (Text oder Link).
 * POST mit denselben Feldern geht auch, z. B. fuer langen freien Inhalt.
 */
class QrCodeController extends Controller
{
    public function __construct(private readonly QrCodeGenerator $generator) {}

    public function types(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => collect(QrCodeGenerator::TYPES)
                ->map(fn (array $type, string $key) => ['type' => $key] + $type + ['needs_travel_code' => $key !== QrCodeGenerator::TYPE_FREE])
                ->values()
                ->all(),
        ]);
    }

    public function generate(Request $request): JsonResponse|Response
    {
        $data = $request->validate([
            'type' => ['nullable', 'string', Rule::in(array_keys(QrCodeGenerator::TYPES))],
            'value' => ['required', 'string', 'max:2000'],
            'lang' => ['nullable', 'string', Rule::in(QrCodeGenerator::TRAVEL_DETAILS_LANGUAGES)],
            'format' => ['nullable', 'string', Rule::in(['json', 'svg', 'pdf'])],
            'title' => ['nullable', 'string', 'max:120'],
        ]);

        $type = $data['type'] ?? QrCodeGenerator::TYPE_TRIPDATA;
        $content = QrCodeGenerator::contentFor($type, $data['value'], $data['lang'] ?? 'de');
        if ($content === null) {
            return response()->json([
                'success' => false,
                'message' => $type === QrCodeGenerator::TYPE_FREE
                    ? 'Der Inhalt ist leer oder zu lang (höchstens '.QrCodeGenerator::MAX_FREE_LENGTH.' Zeichen).'
                    : 'Keine gültige Reise-ID: Sie hat 12 Zeichen (z. B. ZIPT-HRG1-Z6Y5) oder steckt in einem Link mit „?tid=…“.',
            ], 422);
        }

        $code = $type === QrCodeGenerator::TYPE_FREE ? null : QrCodeGenerator::normalizeTravelDetailsCode($data['value']);
        $fileName = 'qr-'.($code ?: Str::slug(Str::limit($content, 40, '')) ?: 'code');

        return match ($data['format'] ?? 'json') {
            'svg' => response($this->generator->svg($content), 200, [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => 'inline; filename="'.$fileName.'.svg"',
            ]),
            'pdf' => response($this->generator->pdf($content, $this->title($data['title'] ?? null, $code), $code), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$fileName.'.pdf"',
            ]),
            default => response()->json([
                'success' => true,
                'data' => [
                    'type' => $type,
                    'code' => $code,
                    'content' => $content,
                    'svg' => $this->generator->svg($content),
                    'downloads' => [
                        'svg' => $request->fullUrlWithQuery(['format' => 'svg']),
                        'pdf' => $request->fullUrlWithQuery(['format' => 'pdf']),
                    ],
                ],
            ]),
        };
    }

    private function title(?string $title, ?string $code): string
    {
        $title = trim((string) $title);

        return $title !== '' ? $title : ($code ? 'Reise '.$code : 'QR-Code');
    }
}
