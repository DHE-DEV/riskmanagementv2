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
        Schema::table('airports', function (Blueprint $table) {
            // 24h Betrieb für Passagierflugzeuge
            $table->boolean('operates_24h')->default(false)->after('type');

            // Lounges (JSON: Array von Lounge-Informationen)
            // Struktur: [{"name": "Lufthansa Lounge", "location": "Terminal 1", "access": "Business Class"}]
            $table->json('lounges')->nullable()->after('operates_24h');

            // Hotels in Flughafennähe (JSON)
            // Struktur: [{"name": "Airport Hotel", "distance_km": 0.5, "shuttle": true, "booking_url": "https://..."}]
            $table->json('nearby_hotels')->nullable()->after('lounges');

            // Mobilitätsangebote (JSON)
            // Struktur: {
            //   "car_rental": {"available": true, "providers": ["Sixt", "Hertz"], "booking_url": "..."},
            //   "public_transport": {"available": true, "types": ["S-Bahn", "Bus"], "info_url": "..."},
            //   "airport_shuttle": {"available": true, "info": "Kostenloser Shuttle zu Hotels", "url": "..."},
            //   "taxi": {"available": true, "info": "24/7 verfügbar", "approx_cost": "50 EUR"},
            //   "parking": {
            //     "available": true,
            //     "types": ["Parkhaus P1", "Parkhaus P2"],
            //     "distances": {"P1": "100m", "P2": "200m"},
            //     "booking_url": "..."
            //   }
            // }
            $table->json('mobility_options')->nullable()->after('nearby_hotels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->dropColumn([
                'operates_24h',
                'lounges',
                'nearby_hotels',
                'mobility_options',
            ]);
        });
    }
};
