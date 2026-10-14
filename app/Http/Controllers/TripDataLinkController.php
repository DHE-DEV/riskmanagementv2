<?php

namespace App\Http\Controllers;

use App\Services\QrCode\QrCodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Reise-Links der TripData-App: https://platform.passolution.de/reise/{code}
 *
 * Ist die App installiert, oeffnet das Smartphone den Link direkt in TripData (Universal Link bzw.
 * Android App Link) – diese Route wird dann gar nicht aufgerufen. Ohne App landet der Link hier und
 * wird auf die Reisenden-Seite travel-details.eu weitergeleitet. Die Verifikationsdateien unter
 * /.well-known erlauben der App, die Links zu uebernehmen.
 */
class TripDataLinkController extends Controller
{
    public const PATH = 'reise';

    public function show(Request $request, string $code): RedirectResponse
    {
        $normalized = QrCodeGenerator::normalizeTravelDetailsCode($code);
        abort_if($normalized === null, 404);

        $language = $request->getPreferredLanguage(QrCodeGenerator::TRAVEL_DETAILS_LANGUAGES) ?? 'de';

        return redirect()->away(QrCodeGenerator::travelDetailsUrl($normalized, $language));
    }

    /** iOS: welche App die Links unter /reise/* oeffnen darf (Associated Domains). */
    public function appleAppSiteAssociation(): JsonResponse
    {
        $details = array_map(fn (string $appId) => [
            'appIDs' => [$appId],
            'components' => [['/' => '/'.self::PATH.'/*', 'comment' => 'Reise-Links der TripData-App']],
        ], config('services.tripdata.ios_app_ids', []));

        return response()->json(['applinks' => ['details' => array_values($details)]])
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /** Android: Digital Asset Links fuer die verifizierten App Links. */
    public function assetLinks(): JsonResponse
    {
        $fingerprints = config('services.tripdata.android_sha256', []);

        $statements = $fingerprints === [] ? [] : [[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => [
                'namespace' => 'android_app',
                'package_name' => config('services.tripdata.android_package'),
                'sha256_cert_fingerprints' => array_values($fingerprints),
            ],
        ]];

        return response()->json($statements)->header('Cache-Control', 'public, max-age=3600');
    }
}
