<?php

namespace App\Providers;

use App\Models\CustomEvent;
use App\Observers\CustomEventObserver;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        CustomEvent::observe(CustomEventObserver::class);

        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): HtmlString => new HtmlString('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">')
        );
    }
}
