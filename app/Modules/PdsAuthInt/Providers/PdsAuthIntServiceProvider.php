<?php

namespace App\Modules\PdsAuthInt\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * PdsAuthInt Module Service Provider
 *
 * Service Provider für das PdsAuthInt-Modul
 * Verantwortlich für:
 * - Registrierung der Modul-Konfiguration
 * - Laden der Web- und API-Routen
 * - Veröffentlichung der Konfigurationsdateien
 *
 * Responsible for:
 * - Registering module configuration
 * - Loading web and API routes
 * - Publishing configuration files
 */
class PdsAuthIntServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Registriert die Services des Moduls
     * - Merged die Modul-Konfiguration mit der Haupt-App-Konfiguration
     *
     * Registers module services
     * - Merges module configuration with main application configuration
     */
    public function register(): void
    {
        // Merge module configuration / Modul-Konfiguration mergen
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pdsauthint.php',
            'pdsauthint'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * Bootet die Services des Moduls
     * - Lädt die Routen (web.php und api.php)
     * - Registriert publishable Assets
     *
     * Boots module services
     * - Loads routes (web.php and api.php)
     * - Registers publishable assets
     */
    public function boot(): void
    {
        // Load web routes / Web-Routen laden
        if (file_exists(__DIR__ . '/../routes/web.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        // Load API routes / API-Routen laden
        if (file_exists(__DIR__ . '/../routes/api.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }

        // Publish configuration file / Konfigurationsdatei veröffentlichen
        // Can be published using: php artisan vendor:publish --tag=pdsauthint-config
        // Kann veröffentlicht werden mit: php artisan vendor:publish --tag=pdsauthint-config
        $this->publishes([
            __DIR__ . '/../config/pdsauthint.php' => config_path('pdsauthint.php'),
        ], 'pdsauthint-config');
    }
}
