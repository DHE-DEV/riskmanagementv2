<?php

use App\Services\QrCode\QrCodeGenerator;

// DB-frei: QR-Matrix, SVG, PDF und die Reise-ID der Travel Detail Links.
uses(Tests\AppTestCase::class);

it('normalizes travel details codes from every common spelling', function (string $input) {
    expect(QrCodeGenerator::normalizeTravelDetailsCode($input))->toBe('ZIPT-HRG1-Z6Y5');
})->with([
    'ZIPT-HRG1-Z6Y5',
    'zipthrg1z6y5',
    ' zipt hrg1 z6y5 ',
    'https://travel-details.eu/de?tid=ZIPTHRG1Z6Y5',
    'https://travel-details.eu/en?status=ok&TID=zipt-hrg1-z6y5',
    'https://travel-details.eu/t/ZIPTHRG1Z6Y5',
]);

it('rejects input that is not a travel details code', function (string $input) {
    expect(QrCodeGenerator::normalizeTravelDetailsCode($input))->toBeNull();
})->with(['', 'zu-kurz', 'AAAA-BBBB-CCCC-DDDD', 'https://travel-details.eu/de']);

it('builds the traveller link and falls back to german', function () {
    expect(QrCodeGenerator::travelDetailsUrl('ZIPT-HRG1-Z6Y5'))->toBe('https://travel-details.eu/de?tid=ZIPTHRG1Z6Y5')
        ->and(QrCodeGenerator::travelDetailsUrl('ZIPT-HRG1-Z6Y5', 'nl'))->toBe('https://travel-details.eu/nl?tid=ZIPTHRG1Z6Y5')
        ->and(QrCodeGenerator::travelDetailsUrl('ZIPT-HRG1-Z6Y5', 'fr'))->toBe('https://travel-details.eu/de?tid=ZIPTHRG1Z6Y5');
});

it('produces a square matrix with the three finder patterns', function () {
    $matrix = (new QrCodeGenerator)->matrix('https://travel-details.eu/de?tid=ZIPTHRG1Z6Y5');
    $size = count($matrix);

    expect($size)->toBeGreaterThanOrEqual(21)
        ->and(($size - 17) % 4)->toBe(0)
        ->and($matrix[0][0])->toBeTrue()->and($matrix[0][$size - 1])->toBeTrue()->and($matrix[$size - 1][0])->toBeTrue()
        ->and($matrix[$size - 1][$size - 1])->toBeFalse();
});

it('renders svg and a single page pdf', function () {
    $generator = new QrCodeGenerator;
    $svg = $generator->svg('Grüße aus Köln');
    $pdf = $generator->pdf('https://travel-details.eu/de?tid=ZIPTHRG1Z6Y5', 'Reise ZIPT-HRG1-Z6Y5', 'ZIPT-HRG1-Z6Y5');

    expect($svg)->toStartWith('<svg')->toContain('viewBox=')->toContain('<path')
        ->and($pdf)->toStartWith('%PDF-1.4')->toEndWith("%%EOF\n")->toContain('/Count 1')->toContain('(ZIPT-HRG1-Z6Y5) Tj');
});

it('throws for empty content', function () {
    (new QrCodeGenerator)->matrix('');
})->throws(InvalidArgumentException::class);
