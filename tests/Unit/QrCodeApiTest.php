<?php

use App\Http\Controllers\Api\V1\QrCodeController;
use App\Services\QrCode\QrCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

// DB-frei: der Controller wird direkt aufgerufen; Auth und Logging der API-Gruppe sind nicht Teil des Tests.
uses(Tests\AppTestCase::class);

function qrApi(array $query, string $method = 'GET')
{
    $request = Request::create('/api/v1/qr-codes', $method, $query);
    app()->instance('request', $request);

    return app(QrCodeController::class)->generate($request);
}

it('lists all qr code types', function () {
    $types = collect(app(QrCodeController::class)->types()->getData(true)['data'])->pluck('type');

    expect($types->all())->toBe(['tripdata', 'tripdata-app', 'travel-details', 'free']);
});

it('returns the tripdata link with svg and download urls by default', function () {
    config(['services.tripdata.link_base' => 'https://platform.passolution.de/reise']);

    $data = qrApi(['value' => 'zipt-hrg1-z6y5'])->getData(true)['data'];

    expect($data['type'])->toBe('tripdata')
        ->and($data['code'])->toBe('ZIPT-HRG1-Z6Y5')
        ->and($data['content'])->toBe('https://platform.passolution.de/reise/ZIPTHRG1Z6Y5')
        ->and($data['svg'])->toContain('shape-rendering="crispEdges"')
        ->and($data['downloads']['pdf'])->toContain('format=pdf');
});

it('builds every type', function (string $type, array $extra, string $content) {
    expect(qrApi(['type' => $type, 'value' => 'ZIPTHRG1Z6Y5'] + $extra)->getData(true)['data']['content'])->toBe($content);
})->with([
    'app direct link' => ['tripdata-app', [], 'tripdata://reise/ZIPTHRG1Z6Y5'],
    'travel details' => ['travel-details', ['lang' => 'en'], 'https://travel-details.eu/en?tid=ZIPTHRG1Z6Y5'],
    'free content' => ['free', [], 'ZIPTHRG1Z6Y5'],
]);

it('delivers svg and pdf files', function () {
    $svg = qrApi(['type' => 'free', 'value' => 'https://example.com', 'format' => 'svg']);
    $pdf = qrApi(['value' => 'ZIPTHRG1Z6Y5', 'format' => 'pdf', 'title' => 'Meine Reise'], 'POST');

    expect($svg->headers->get('Content-Type'))->toBe('image/svg+xml')
        ->and($svg->getContent())->toStartWith('<svg')
        ->and($pdf->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($pdf->headers->get('Content-Disposition'))->toContain('qr-ZIPT-HRG1-Z6Y5.pdf')
        ->and($pdf->getContent())->toStartWith('%PDF');
});

it('rejects an invalid travel code with 422', function () {
    $response = qrApi(['type' => 'tripdata', 'value' => 'abc']);

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true)['success'])->toBeFalse();
});

it('rejects too long free content', function () {
    expect(qrApi(['type' => 'free', 'value' => str_repeat('x', QrCodeGenerator::MAX_FREE_LENGTH + 1)])->getStatusCode())->toBe(422);
});

it('validates type and format', function () {
    qrApi(['type' => 'unknown', 'value' => 'x', 'format' => 'gif']);
})->throws(ValidationException::class);
