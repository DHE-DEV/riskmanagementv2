<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\CustomEvent;
use App\Models\Customer;
use App\Observers\BranchObserver;
use App\Observers\CustomEventObserver;
use App\Observers\CustomerObserver;
use Filament\Support\Facades\FilamentView;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register PdsAuthInt Module Service Provider
        // Registriere PdsAuthInt Modul Service Provider
        $this->app->register(\App\Modules\PdsAuthInt\Providers\PdsAuthIntServiceProvider::class);

        // Register SSO Log Service as singleton
        // Registriere SSO Log Service als Singleton
        $this->app->singleton(\App\Services\SsoLogService::class, function ($app) {
            return new \App\Services\SsoLogService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        CustomEvent::observe(CustomEventObserver::class);
        Customer::observe(CustomerObserver::class);
        Branch::observe(BranchObserver::class);

        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): HtmlString => new HtmlString('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">')
        );

        // GTM API Rate Limiter - per customer, configurable rate limit
        RateLimiter::for('gtm-api', function (Request $request) {
            $customer = $request->user();
            $limit = $customer?->gtm_api_rate_limit ?? 60;

            return Limit::perMinute($limit)->by($customer?->id ?: $request->ip());
        });

        // API Client Rate Limiter - per API client, configurable rate limit
        RateLimiter::for('api-client', function (Request $request) {
            $apiClient = $request->attributes->get('api_client');
            $limit = $apiClient?->rate_limit ?? 60;

            return Limit::perMinute($limit)->by('api-client:' . ($apiClient?->id ?: $request->ip()));
        });
    }
}
