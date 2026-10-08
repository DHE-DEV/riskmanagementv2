<?php

namespace Database\Seeders;

use App\Models\TaxiApp;
use Illuminate\Database\Seeder;

/**
 * Die verbreiteten Taxi-Apps als Startbestand unter System > Taxi Apps.
 * Vorhandene Eintraege (gleicher Name) bleiben unveraendert.
 */
class TaxiAppSeeder extends Seeder
{
    public function run(): void
    {
        $apps = [
            [
                'name' => 'Uber',
                'sort_order' => 1,
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/79/Uber_App_Icon.svg',
                'website_url' => 'https://www.uber.com',
                'app_store_url' => 'https://apps.apple.com/de/app/uber-ride-hailing-taxis/id368677368',
                'play_store_url' => 'https://play.google.com/store/apps/details?id=com.ubercab',
                'description_translations' => [
                    'de' => 'Weltweit verbreitete Fahrdienst-App: Fahrt in der App bestellen, Preis vorab sehen, bargeldlos bezahlen. In vielen Städten auch mit Taxis und Fahrrad- oder Rollerverleih.',
                    'en' => 'Ride-hailing app available worldwide: book a ride in the app, see the fare upfront and pay cashless. In many cities it also offers taxis plus bike and scooter rentals.',
                    'nl' => 'Wereldwijd beschikbare ritten-app: bestel een rit in de app, zie de prijs vooraf en betaal zonder contant geld. In veel steden ook met taxi\'s en fiets- of scooterverhuur.',
                ],
            ],
            [
                'name' => 'Bolt',
                'sort_order' => 2,
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/8/8a/Bolt_Technology_Logo_2019.svg',
                'website_url' => 'https://bolt.eu',
                'app_store_url' => 'https://apps.apple.com/de/app/bolt-fahrten-anfordern/id675033630',
                'play_store_url' => 'https://play.google.com/store/apps/details?id=ee.mtakso.client',
                'description_translations' => [
                    'de' => 'Fahrdienst- und Mobilitäts-App aus Estland, in Europa und Afrika stark vertreten. Fahrten, E-Scooter und Leihfahrräder, oft günstiger als Taxis. Bezahlung per Karte in der App.',
                    'en' => 'Ride-hailing and mobility app from Estonia with a strong presence in Europe and Africa. Rides, e-scooters and rental bikes, often cheaper than taxis. Card payment in the app.',
                    'nl' => 'Ritten- en mobiliteitsapp uit Estland, sterk aanwezig in Europa en Afrika. Ritten, e-scooters en huurfietsen, vaak goedkoper dan een taxi. Betalen met kaart in de app.',
                ],
            ],
            [
                'name' => 'FREENOW',
                'sort_order' => 3,
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/32/Free_Now_2024_logo.svg',
                'website_url' => 'https://www.free-now.com',
                'app_store_url' => 'https://apps.apple.com/de/app/freenow-by-lyft-taxi-mehr/id357852748',
                'play_store_url' => 'https://play.google.com/store/apps/details?id=taxi.android.client',
                'description_translations' => [
                    'de' => 'Europäische Taxi-App (früher mytaxi): vermittelt lizenzierte Taxis sowie Mietwagen mit Fahrer, E-Scooter und Carsharing in über 150 Städten. Bezahlung in der App oder beim Fahrer.',
                    'en' => 'European taxi app (formerly mytaxi): connects you with licensed taxis, private hire cars, e-scooters and car sharing in more than 150 cities. Pay in the app or to the driver.',
                    'nl' => 'Europese taxi-app (voorheen mytaxi): bemiddelt gelicentieerde taxi\'s, huurauto\'s met chauffeur, e-scooters en deelauto\'s in meer dan 150 steden. Betalen in de app of bij de chauffeur.',
                ],
            ],
        ];

        foreach ($apps as $app) {
            TaxiApp::firstOrCreate(['name' => $app['name']], $app + ['is_active' => true]);
        }
    }
}
