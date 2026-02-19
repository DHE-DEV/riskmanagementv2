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
            Route::domain(config('app.api_domain') ?: 'api.global-travel-monitor.de')
                ->middleware('api')
                ->group(base_path('routes/api-subdomain.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'allow.embedding' => \App\Http\Middleware\AllowEmbedding::class,
            'plugin.onboarded' => \App\Http\Middleware\EnsurePluginOnboarded::class,
            'validate.embed.key' => \App\Http\Middleware\ValidateEmbedKey::class,
        ]);

        // Enable CORS for API routes
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
