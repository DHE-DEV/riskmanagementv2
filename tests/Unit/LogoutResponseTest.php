<?php

use App\Http\Responses\LogoutResponse;

// DB-frei: testet nur die Redirect-Logik der Fortify-LogoutResponse.
uses(Tests\AppTestCase::class);

it('redirects logout to the sso logout url with return_to (single-logout)', function () {
    config([
        'services.sso.logout_url' => 'https://auth.example.test/auth/sso/logout',
        'services.sso.logout_redirect' => 'https://platform.example.test/danach',
    ]);

    $response = (new LogoutResponse)->toResponse(request());

    expect($response->getTargetUrl())->toBe(
        'https://auth.example.test/auth/sso/logout?return_to='.urlencode('https://platform.example.test/danach')
    );
});

it('falls back to the post-logout target when no sso logout url is set', function () {
    config([
        'services.sso.logout_url' => null,
        'services.sso.logout_redirect' => 'https://platform.example.test/login',
    ]);

    $response = (new LogoutResponse)->toResponse(request());

    expect($response->getTargetUrl())->toBe('https://platform.example.test/login');
});
