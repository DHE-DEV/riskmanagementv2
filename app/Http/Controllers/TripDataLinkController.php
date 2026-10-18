<?php

namespace App\Http\Controllers;

use App\Models\Push\AppDevice;
use App\Models\Push\AppInvite;
use App\Services\QrCode\QrCodeGenerator;
use Illuminate\Contracts\View\View;
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

    /**
     * Einladung zu einer Reise oder Gruppe: https://platform.passolution.de/einladung/{code}. Mit installierter App
     * oeffnet sie direkt TripData; ohne App diese Seite mit Infos, Links zu den Stores und dem Code zum Eintippen.
     */
    public function invite(string $code): View
    {
        $invite = AppInvite::with(['trip', 'group', 'creator'])->where('code', AppDevice::normalizeShareCode($code) ?? '-')->first();

        return view('tripdata.invite', [
            'invite' => $invite && $invite->isValid() ? $invite->preview() : null,
            'appStoreUrl' => config('services.tripdata.app_store_url'),
            'playStoreUrl' => config('services.tripdata.play_store_url'),
            'appUrl' => 'tripdata://einladung/'.($invite?->formattedCode() ?? ''),
        ]);
    }

    /** iOS: welche App die Links unter /reise/* oeffnen darf (Associated Domains). */
    public function appleAppSiteAssociation(): JsonResponse
    {
        $details = array_map(fn (string $appId) => [
            'appIDs' => [$appId],
            'components' => [
                ['/' => '/'.self::PATH.'/*', 'comment' => 'Reise-Links der TripData-App'],
                ['/' => '/'.AppInvite::PATH.'/*', 'comment' => 'Einladungen zu Reisen und Gruppen'],
            ],
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
