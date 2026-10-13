<?php

use App\Http\Controllers\TripDataLinkController;
use Illuminate\Http\Request;

// DB-frei: Reise-Links der TripData-App und die Verifikationsdateien fuer iOS/Android.
uses(Tests\AppTestCase::class);

it('redirects a travel link to the traveller page in the preferred language', function () {
    $request = Request::create('/reise/zipthrg1z6y5', 'GET', server: ['HTTP_ACCEPT_LANGUAGE' => 'nl-NL,nl;q=0.9']);

    $response = (new TripDataLinkController)->show($request, 'zipthrg1z6y5');

    expect($response->getTargetUrl())->toBe('https://travel-details.eu/nl?tid=ZIPTHRG1Z6Y5');
});

it('rejects codes that are not travel details ids', function () {
    (new TripDataLinkController)->show(Request::create('/reise/x'), 'ABCDEFGHIJKLMN0');
})->throws(Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

it('publishes the apple app site association for /reise/*', function () {
    config(['services.tripdata.ios_app_ids' => ['5843TDW7F4.de.passolution.tripdata']]);

    $json = (new TripDataLinkController)->appleAppSiteAssociation()->getData(true);

    expect($json['applinks']['details'][0]['appIDs'])->toBe(['5843TDW7F4.de.passolution.tripdata'])
        ->and($json['applinks']['details'][0]['components'][0]['/'])->toBe('/reise/*');
});

it('publishes asset links only with a signing fingerprint', function () {
    config(['services.tripdata.android_sha256' => []]);
    expect((new TripDataLinkController)->assetLinks()->getData(true))->toBe([]);

    config(['services.tripdata.android_package' => 'de.passolution.tripdata', 'services.tripdata.android_sha256' => ['AA:BB']]);
    $json = (new TripDataLinkController)->assetLinks()->getData(true);

    expect($json[0]['target']['package_name'])->toBe('de.passolution.tripdata')
        ->and($json[0]['target']['sha256_cert_fingerprints'])->toBe(['AA:BB']);
});
