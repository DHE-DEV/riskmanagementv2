<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add the lat and lng columns if they don't exist
        if (!Schema::hasColumn('countries', 'lat')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->decimal('lat', 10, 8)->nullable()->after('is_schengen_member');
                $table->decimal('lng', 11, 8)->nullable()->after('lat');
            });
        }

        // Add coordinates for major countries (approximate geographic centers or capital cities)
        $coordinates = [
            // Europe
            'DE' => [51.1657, 10.4515],  // Germany (geographic center)
            'FR' => [46.2276, 2.2137],    // France (geographic center)
            'ES' => [40.4637, -3.7492],   // Spain (Madrid)
            'IT' => [41.8719, 12.5674],   // Italy (Rome)
            'GB' => [55.3781, -3.4360],   // United Kingdom (geographic center)
            'PT' => [39.3999, -8.2245],   // Portugal (Lisbon)
            'NL' => [52.1326, 5.2913],    // Netherlands (Utrecht)
            'BE' => [50.5039, 4.4699],    // Belgium (Brussels)
            'CH' => [46.8182, 8.2275],    // Switzerland (Bern)
            'AT' => [47.5162, 14.5501],   // Austria (geographic center)
            'PL' => [51.9194, 19.1451],   // Poland (geographic center)
            'CZ' => [49.8175, 15.4730],   // Czech Republic (Prague)
            'GR' => [39.0742, 21.8243],   // Greece (Athens)
            'SE' => [60.1282, 18.6435],   // Sweden (Stockholm)
            'NO' => [60.4720, 8.4689],    // Norway (geographic center)
            'DK' => [56.2639, 9.5018],    // Denmark (geographic center)
            'FI' => [61.9241, 25.7482],   // Finland (geographic center)
            'IE' => [53.4129, -8.2439],   // Ireland (geographic center)
            'TR' => [38.9637, 35.2433],   // Turkey (geographic center)

            // Americas
            'US' => [37.0902, -95.7129],  // United States (geographic center)
            'CA' => [56.1304, -106.3468], // Canada (geographic center)
            'MX' => [23.6345, -102.5528], // Mexico (geographic center)
            'BR' => [-14.2350, -51.9253], // Brazil (geographic center)
            'AR' => [-38.4161, -63.6167], // Argentina (geographic center)

            // Asia
            'CN' => [35.8617, 104.1954],  // China (geographic center)
            'JP' => [36.2048, 138.2529],  // Japan (geographic center)
            'IN' => [20.5937, 78.9629],   // India (geographic center)
            'KR' => [35.9078, 127.7669],  // South Korea (Seoul)
            'TH' => [15.8700, 100.9925],  // Thailand (Bangkok)
            'VN' => [14.0583, 108.2772],  // Vietnam (geographic center)
            'ID' => [-0.7893, 113.9213],  // Indonesia (geographic center)
            'MY' => [4.2105, 101.9758],   // Malaysia (Kuala Lumpur)
            'SG' => [1.3521, 103.8198],   // Singapore
            'AE' => [23.4241, 53.8478],   // UAE (Abu Dhabi)
            'SA' => [23.8859, 45.0792],   // Saudi Arabia (Riyadh)

            // Africa
            'EG' => [26.8206, 30.8025],   // Egypt (Cairo)
            'ZA' => [-30.5595, 22.9375],  // South Africa (geographic center)
            'KE' => [-0.0236, 37.9062],   // Kenya (Nairobi)
            'NG' => [9.0820, 8.6753],     // Nigeria (Abuja)

            // Oceania
            'AU' => [-25.2744, 133.7751], // Australia (geographic center)
            'NZ' => [-40.9006, 174.8860], // New Zealand (geographic center)
        ];

        // Disable foreign key checks (SQLite compatible)
        $driver = \DB::getDriverName();
        if ($driver === 'mysql') {
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'sqlite') {
            \DB::statement('PRAGMA foreign_keys = OFF');
        }

        foreach ($coordinates as $isoCode => $coords) {
            \DB::table('countries')
                ->where('iso_code', $isoCode)
                ->update([
                    'lat' => $coords[0],
                    'lng' => $coords[1],
                ]);
        }

        // Re-enable foreign key checks
        if ($driver === 'mysql') {
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($driver === 'sqlite') {
            \DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};
