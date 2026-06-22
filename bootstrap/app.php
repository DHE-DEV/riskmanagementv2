<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/customer.php'));

            // API subdomain routes (api.global-travel-monitor.de/v1/...)
            Route::domain(config('app.api_domain'))
                ->middleware('api')
                ->group(base_path('routes/api-subdomain.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'allow.embedding' => \App\Http\Middleware\AllowEmbedding::class,
            'plugin.onboarded' => \App\Http\Middleware\EnsurePluginOnboarded::class,
            'validate.embed.key' => \App\Http\Middleware\ValidateEmbedKey::class,
            'admin-tools' => \App\Http\Middleware\AdminToolsAccess::class,
        ]);

        // Frontend-Anzeigesprache (mehrsprachige Events) anhand ?lang/Session/Cookie/Browser.
        // Auf web (Session + Cookie setzen) und api (Cookie lesen, zustandslos).
        $middleware->web(append: [
            \App\Http\Middleware\SetEventLocale::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\SetEventLocale::class,
        ]);

        // app_locale-Cookie unverschlüsselt lassen, damit die zustandslose API
        // ihn auf derselben Domain lesen kann.
        $middleware->encryptCookies(except: [
            \App\Http\Middleware\SetEventLocale::COOKIE,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('admin/*')) {
                return route('filament.admin.auth.login');
            }

            return route('customer.login');
        });

        // Enable CORS for API routes
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }
            if ($request->is('customer/register')) {
                return redirect()->route('customer.register')
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->with('error', 'Ihre Sitzung ist abgelaufen. Bitte versuchen Sie es erneut.');
            }
            if ($request->is('customer/*')) {
                return redirect()->route('customer.login')
                    ->with('error', 'Ihre Sitzung ist abgelaufen. Bitte versuchen Sie es erneut.');
            }
        });
    })->create();
