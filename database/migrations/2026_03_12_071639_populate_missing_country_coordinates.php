<?php

use App\Models\Country;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Fill missing country lat/lng from capital city coordinates,
     * with manual fallback for countries without a capital entry.
     */
    public function up(): void
    {
        // Step 1: Fill from capital cities
        Country::whereNull('lat')->orWhereNull('lng')->each(function (Country $country) {
            $capital = $country->capital;
            if ($capital && $capital->lat && $capital->lng) {
                $country->update(['lat' => $capital->lat, 'lng' => $capital->lng]);
            }
        });

        // Step 2: Manual fallback for countries without capital entry
        $fallback = [
            'RU' => [55.7558, 37.6173],
            'KZ' => [51.1694, 71.4491],
            'SY' => [33.5138, 36.2765],
            'ST' => [0.1864, 6.6131],
            'SD' => [15.5007, 32.5599],
            'TG' => [6.1256, 1.2254],
            'CW' => [12.1696, -68.9900],
            'GL' => [64.1836, -51.7214],
            'PR' => [18.4655, -66.1057],
            'KN' => [17.3026, -62.7177],
            'TK' => [-9.2002, -171.8484],
            'TO' => [-21.2087, -175.1982],
        ];

        foreach ($fallback as $iso => [$lat, $lng]) {
            Country::where('iso_code', $iso)
                ->where(fn ($q) => $q->whereNull('lat')->orWhereNull('lng'))
                ->update(['lat' => $lat, 'lng' => $lng]);
        }
    }

    public function down(): void
    {
        // Not reversible - coordinates are supplementary data
    }
};
