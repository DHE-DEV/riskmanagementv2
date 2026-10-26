<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\CustomerGeocoder;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Ermittelt im Hintergrund die Koordinaten der Unteraccounts einer Zentrale
 * (fuer die Karte "Filialen & Standorte"), soweit noch nicht geschehen.
 * Nominatim erlaubt etwa eine Anfrage je Sekunde – wie FillRegionInfoJob
 * arbeitet ein Job nur $runSeconds lang und stellt sich dann neu ein.
 */
class GeocodeSubAccounts implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $timeout = 290;

    public int $tries = 1;

    /** Arbeitszeit je Job in Sekunden. */
    public static int $runSeconds = 60;

    public function __construct(public int $agencyId) {}

    public function uniqueId(): string
    {
        return (string) $this->agencyId;
    }

    /**
     * Unteraccounts dieser Zentrale ohne ermittelte Koordinaten.
     */
    public static function pending(int $agencyId)
    {
        return Customer::query()->where('assign_to', $agencyId)->whereNull('company_geocoded_at');
    }

    public function handle(CustomerGeocoder $geocoder): void
    {
        if (! $geocoder->enabled()) {
            return;
        }

        $deadline = microtime(true) + self::$runSeconds;

        foreach (self::pending($this->agencyId)->orderBy('id')->lazyById(50) as $customer) {
            $geocoder->geocode($customer);

            if (microtime(true) >= $deadline) {
                break;
            }
        }

        if (self::pending($this->agencyId)->exists()) {
            self::dispatch($this->agencyId);
        }
    }
}
