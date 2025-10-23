<?php

namespace Database\Seeders;

use App\Models\BookingLocation;
use Illuminate\Database\Seeder;

class BookingLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 30 Online-Buchungsplattformen
        $onlinePlatforms = [
            [
                'name' => 'Expedia Deutschland',
                'description' => 'Führende Online-Reiseplattform für Flüge, Hotels und Pauschalreisen weltweit',
                'url' => 'https://www.expedia.de',
            ],
            [
                'name' => 'Booking.com',
                'description' => 'Weltweite Hotelreservierungen und Unterkünfte aller Art',
                'url' => 'https://www.booking.com',
            ],
            [
                'name' => 'Check24 Reisen',
                'description' => 'Deutsches Vergleichsportal für Reisen, Hotels und Flüge',
                'url' => 'https://www.check24.de/reisen',
            ],
            [
                'name' => 'HolidayCheck',
                'description' => 'Reiseportal mit Hotelbewertungen und Urlaubsangeboten',
                'url' => 'https://www.holidaycheck.de',
            ],
            [
                'name' => 'Opodo',
                'description' => 'Online-Reisebüro für Flüge, Hotels und Mietwagen',
                'url' => 'https://www.opodo.de',
            ],
            [
                'name' => 'weg.de',
                'description' => 'Günstiger Urlaub und Last Minute Reisen online buchen',
                'url' => 'https://www.weg.de',
            ],
            [
                'name' => 'Trivago',
                'description' => 'Hotelpreisvergleich für die beste Unterkunft zum besten Preis',
                'url' => 'https://www.trivago.de',
            ],
            [
                'name' => 'Agoda',
                'description' => 'Spezialist für Asien-Reisen und Hotels weltweit',
                'url' => 'https://www.agoda.com/de-de',
            ],
            [
                'name' => 'Hotels.com',
                'description' => 'Internationale Hotelbuchungsplattform mit Treueprogramm',
                'url' => 'https://de.hotels.com',
            ],
            [
                'name' => 'Airbnb',
                'description' => 'Plattform für Ferienwohnungen und besondere Unterkünfte',
                'url' => 'https://www.airbnb.de',
            ],
            [
                'name' => 'TUI.com',
                'description' => 'Online-Portal des führenden Reiseveranstalters',
                'url' => 'https://www.tui.com',
            ],
            [
                'name' => 'FTI Touristik',
                'description' => 'Reiseveranstalter mit umfangreichem Online-Angebot',
                'url' => 'https://www.fti.de',
            ],
            [
                'name' => 'Lufthansa Holidays',
                'description' => 'Pauschalreisen und Flugbuchungen von Lufthansa',
                'url' => 'https://www.lufthansa-holidays.com',
            ],
            [
                'name' => 'Skyscanner',
                'description' => 'Flugvergleichsportal für günstige Flüge weltweit',
                'url' => 'https://www.skyscanner.de',
            ],
            [
                'name' => 'Kayak',
                'description' => 'Reisesuchmaschine für Flüge, Hotels und Mietwagen',
                'url' => 'https://www.kayak.de',
            ],
            [
                'name' => 'Momondo',
                'description' => 'Metasuchmaschine für Flüge und Hotels',
                'url' => 'https://www.momondo.de',
            ],
            [
                'name' => 'Fluege.de',
                'description' => 'Flugvergleichsportal für Billigflüge',
                'url' => 'https://www.fluege.de',
            ],
            [
                'name' => 'Ab-in-den-Urlaub.de',
                'description' => 'Portal für Last Minute Reisen und Pauschalurlaub',
                'url' => 'https://www.ab-in-den-urlaub.de',
            ],
            [
                'name' => 'Urlaubsguru',
                'description' => 'Reise-Deals und Urlaubsschnäppchen',
                'url' => 'https://www.urlaubsguru.de',
            ],
            [
                'name' => 'Urlaubspiraten',
                'description' => 'Plattform für günstige Reiseangebote',
                'url' => 'https://www.urlaubspiraten.de',
            ],
            [
                'name' => 'Hotelplan',
                'description' => 'Schweizer Reiseveranstalter mit Online-Buchung',
                'url' => 'https://www.hotelplan.de',
            ],
            [
                'name' => 'Lastminute.de',
                'description' => 'Last Minute Reisen und Kurzurlaub',
                'url' => 'https://www.lastminute.de',
            ],
            [
                'name' => 'Neckermann Reisen',
                'description' => 'Traditionsreicher Veranstalter mit Online-Portal',
                'url' => 'https://www.neckermann-reisen.de',
            ],
            [
                'name' => 'ITS Reisen',
                'description' => 'Reiseveranstalter mit Fokus auf Qualitätsreisen',
                'url' => 'https://www.its.de',
            ],
            [
                'name' => 'Sonnenklar.TV',
                'description' => 'TV-Reiseveranstalter mit Online-Buchung',
                'url' => 'https://www.sonnenklar.tv',
            ],
            [
                'name' => 'Berge & Meer',
                'description' => 'Veranstalter für Rundreisen und Erlebnisreisen',
                'url' => 'https://www.berge-meer.de',
            ],
            [
                'name' => 'DERTOUR',
                'description' => 'Premium-Reiseveranstalter mit breitem Angebot',
                'url' => 'https://www.dertour.de',
            ],
            [
                'name' => 'Alltours',
                'description' => 'Reiseveranstalter mit eigenem Airline-Angebot',
                'url' => 'https://www.alltours.de',
            ],
            [
                'name' => 'Schauinsland Reisen',
                'description' => 'Familiengeführter Reiseveranstalter',
                'url' => 'https://www.schauinsland-reisen.de',
            ],
            [
                'name' => 'L\'TUR',
                'description' => 'Last Minute Spezialist für Spontanreisen',
                'url' => 'https://www.ltur.com',
            ],
        ];

        foreach ($onlinePlatforms as $platform) {
            BookingLocation::create([
                'type' => 'online',
                'name' => $platform['name'],
                'description' => $platform['description'],
                'url' => $platform['url'],
                'email' => 'info@' . strtolower(str_replace([' ', '.', '\''], ['', '', ''], $platform['name'])) . '.de',
            ]);
        }

        // 200 stationäre Reisebüros mit echten deutschen Städten und Postleitzahlen
        $travelAgencies = [
            // Berlin (10 Büros)
            ['name' => 'Reisewelt Berlin Mitte', 'address' => 'Friedrichstraße 123', 'postal_code' => '10117', 'city' => 'Berlin', 'lat' => 52.5200, 'lng' => 13.4050],
            ['name' => 'TUI Reisecenter Berlin Alexanderplatz', 'address' => 'Alexanderplatz 5', 'postal_code' => '10178', 'city' => 'Berlin', 'lat' => 52.5219, 'lng' => 13.4132],
            ['name' => 'Urlaubsparadies Berlin Charlottenburg', 'address' => 'Kurfürstendamm 234', 'postal_code' => '10719', 'city' => 'Berlin', 'lat' => 52.5050, 'lng' => 13.3288],
            ['name' => 'Fernweh Reisebüro Berlin Prenzlauer Berg', 'address' => 'Schönhauser Allee 89', 'postal_code' => '10439', 'city' => 'Berlin', 'lat' => 52.5389, 'lng' => 13.4134],
            ['name' => 'Traumreisen Berlin Steglitz', 'address' => 'Schloßstraße 78', 'postal_code' => '12163', 'city' => 'Berlin', 'lat' => 52.4567, 'lng' => 13.3234],
            ['name' => 'Reisebüro Sonnenschein Berlin Spandau', 'address' => 'Carl-Schurz-Straße 45', 'postal_code' => '13597', 'city' => 'Berlin', 'lat' => 52.5367, 'lng' => 13.1978],
            ['name' => 'Globe Trotter Berlin Neukölln', 'address' => 'Karl-Marx-Straße 156', 'postal_code' => '12043', 'city' => 'Berlin', 'lat' => 52.4789, 'lng' => 13.4389],
            ['name' => 'Weltweit Reisen Berlin Pankow', 'address' => 'Breite Straße 23', 'postal_code' => '13187', 'city' => 'Berlin', 'lat' => 52.5689, 'lng' => 13.4012],
            ['name' => 'Reiseparadies Berlin Tempelhof', 'address' => 'Tempelhofer Damm 112', 'postal_code' => '12101', 'city' => 'Berlin', 'lat' => 52.4634, 'lng' => 13.3867],
            ['name' => 'FTI Reisebüro Berlin Kreuzberg', 'address' => 'Mehringdamm 67', 'postal_code' => '10961', 'city' => 'Berlin', 'lat' => 52.4945, 'lng' => 13.3867],

            // Hamburg (10 Büros)
            ['name' => 'Reisewelt Hamburg Altstadt', 'address' => 'Mönckebergstraße 45', 'postal_code' => '20095', 'city' => 'Hamburg', 'lat' => 53.5511, 'lng' => 10.0011],
            ['name' => 'TUI Reisecenter Hamburg Eppendorf', 'address' => 'Eppendorfer Landstraße 89', 'postal_code' => '20249', 'city' => 'Hamburg', 'lat' => 53.5889, 'lng' => 9.9889],
            ['name' => 'Urlaubsplaner Hamburg Winterhude', 'address' => 'Mühlenkamp 34', 'postal_code' => '22303', 'city' => 'Hamburg', 'lat' => 53.5867, 'lng' => 10.0234],
            ['name' => 'Fernreisen Hamburg Eimsbüttel', 'address' => 'Osterstraße 156', 'postal_code' => '20255', 'city' => 'Hamburg', 'lat' => 53.5789, 'lng' => 9.9567],
            ['name' => 'Traumurlaub Hamburg Harburg', 'address' => 'Lüneburger Straße 23', 'postal_code' => '21073', 'city' => 'Hamburg', 'lat' => 53.4589, 'lng' => 9.9789],
            ['name' => 'Reisebüro Nordlicht Hamburg Altona', 'address' => 'Große Bergstraße 78', 'postal_code' => '22767', 'city' => 'Hamburg', 'lat' => 53.5545, 'lng' => 9.9389],
            ['name' => 'Weltenbummler Hamburg Wandsbek', 'address' => 'Wandsbeker Marktstraße 112', 'postal_code' => '22041', 'city' => 'Hamburg', 'lat' => 53.5734, 'lng' => 10.0712],
            ['name' => 'Reisetraum Hamburg Blankenese', 'address' => 'Blankeneser Bahnhofstraße 45', 'postal_code' => '22587', 'city' => 'Hamburg', 'lat' => 53.5634, 'lng' => 9.8012],
            ['name' => 'FTI Reisebüro Hamburg St. Pauli', 'address' => 'Reeperbahn 89', 'postal_code' => '20359', 'city' => 'Hamburg', 'lat' => 53.5489, 'lng' => 9.9634],
            ['name' => 'Urlaubswelt Hamburg Bergedorf', 'address' => 'Sachsentor 34', 'postal_code' => '21029', 'city' => 'Hamburg', 'lat' => 53.4889, 'lng' => 10.2134],

            // München (10 Büros)
            ['name' => 'Reisezentrum München Marienplatz', 'address' => 'Kaufingerstraße 12', 'postal_code' => '80331', 'city' => 'München', 'lat' => 48.1374, 'lng' => 11.5755],
            ['name' => 'TUI Reisecenter München Schwabing', 'address' => 'Leopoldstraße 123', 'postal_code' => '80802', 'city' => 'München', 'lat' => 48.1567, 'lng' => 11.5889],
            ['name' => 'Alpenreisen München Haidhausen', 'address' => 'Rosenheimer Straße 67', 'postal_code' => '81667', 'city' => 'München', 'lat' => 48.1289, 'lng' => 11.5967],
            ['name' => 'Urlaubsträume München Pasing', 'address' => 'Pasinger Bahnhofsplatz 5', 'postal_code' => '81241', 'city' => 'München', 'lat' => 48.1500, 'lng' => 11.4611],
            ['name' => 'Fernweh München Sendling', 'address' => 'Lindwurmstraße 89', 'postal_code' => '80337', 'city' => 'München', 'lat' => 48.1267, 'lng' => 11.5489],
            ['name' => 'Reisebüro Bayern München Neuhausen', 'address' => 'Nymphenburger Straße 145', 'postal_code' => '80636', 'city' => 'München', 'lat' => 48.1489, 'lng' => 11.5367],
            ['name' => 'Weltreisen München Bogenhausen', 'address' => 'Prinzregentenstraße 78', 'postal_code' => '81675', 'city' => 'München', 'lat' => 48.1456, 'lng' => 11.6012],
            ['name' => 'DERTOUR München Isarvorstadt', 'address' => 'Sonnenstraße 23', 'postal_code' => '80331', 'city' => 'München', 'lat' => 48.1367, 'lng' => 11.5634],
            ['name' => 'Urlaubsparadies München Giesing', 'address' => 'Tegernseer Landstraße 134', 'postal_code' => '81539', 'city' => 'München', 'lat' => 48.1134, 'lng' => 11.5889],
            ['name' => 'Reiseprofi München Maxvorstadt', 'address' => 'Theresienstraße 45', 'postal_code' => '80333', 'city' => 'München', 'lat' => 48.1489, 'lng' => 11.5712],

            // Köln (10 Büros)
            ['name' => 'Reisewelt Köln Innenstadt', 'address' => 'Hohe Straße 67', 'postal_code' => '50667', 'city' => 'Köln', 'lat' => 50.9367, 'lng' => 6.9578],
            ['name' => 'TUI Reisecenter Köln Ehrenfeld', 'address' => 'Venloer Straße 234', 'postal_code' => '50823', 'city' => 'Köln', 'lat' => 50.9489, 'lng' => 6.9234],
            ['name' => 'Rheinreisen Köln Deutz', 'address' => 'Deutzer Freiheit 89', 'postal_code' => '50679', 'city' => 'Köln', 'lat' => 50.9367, 'lng' => 6.9889],
            ['name' => 'Urlaubstraum Köln Nippes', 'address' => 'Neusser Straße 145', 'postal_code' => '50733', 'city' => 'Köln', 'lat' => 50.9612, 'lng' => 6.9567],
            ['name' => 'Fernreisen Köln Lindenthal', 'address' => 'Dürener Straße 78', 'postal_code' => '50931', 'city' => 'Köln', 'lat' => 50.9289, 'lng' => 6.9189],
            ['name' => 'Reisebüro Dom Köln Mülheim', 'address' => 'Buchheimer Straße 56', 'postal_code' => '51063', 'city' => 'Köln', 'lat' => 50.9612, 'lng' => 7.0134],
            ['name' => 'Weltweit Köln Kalk', 'address' => 'Kalker Hauptstraße 123', 'postal_code' => '51103', 'city' => 'Köln', 'lat' => 50.9389, 'lng' => 7.0312],
            ['name' => 'Traumreisen Köln Sülz', 'address' => 'Luxemburger Straße 234', 'postal_code' => '50939', 'city' => 'Köln', 'lat' => 50.9234, 'lng' => 6.9367],
            ['name' => 'FTI Reisebüro Köln Rodenkirchen', 'address' => 'Hauptstraße 67', 'postal_code' => '50996', 'city' => 'Köln', 'lat' => 50.8967, 'lng' => 6.9889],
            ['name' => 'Urlaubswelt Köln Porz', 'address' => 'Friedrich-Ebert-Ufer 45', 'postal_code' => '51143', 'city' => 'Köln', 'lat' => 50.8889, 'lng' => 7.0612],

            // Frankfurt (10 Büros)
            ['name' => 'Reisezentrum Frankfurt Innenstadt', 'address' => 'Zeil 123', 'postal_code' => '60313', 'city' => 'Frankfurt', 'lat' => 50.1155, 'lng' => 8.6942],
            ['name' => 'TUI Reisecenter Frankfurt Sachsenhausen', 'address' => 'Schweizer Straße 78', 'postal_code' => '60594', 'city' => 'Frankfurt', 'lat' => 50.0989, 'lng' => 8.6734],
            ['name' => 'Mainreisen Frankfurt Bornheim', 'address' => 'Berger Straße 234', 'postal_code' => '60385', 'city' => 'Frankfurt', 'lat' => 50.1267, 'lng' => 8.7234],
            ['name' => 'Urlaubsplaner Frankfurt Bockenheim', 'address' => 'Leipziger Straße 56', 'postal_code' => '60487', 'city' => 'Frankfurt', 'lat' => 50.1234, 'lng' => 8.6489],
            ['name' => 'Fernweh Frankfurt Nordend', 'address' => 'Oeder Weg 89', 'postal_code' => '60318', 'city' => 'Frankfurt', 'lat' => 50.1289, 'lng' => 8.6967],
            ['name' => 'Reisebüro Skyline Frankfurt Höchst', 'address' => 'Königsteiner Straße 145', 'postal_code' => '65929', 'city' => 'Frankfurt', 'lat' => 50.0989, 'lng' => 8.5489],
            ['name' => 'Weltreisen Frankfurt Westend', 'address' => 'Bockenheimer Landstraße 67', 'postal_code' => '60325', 'city' => 'Frankfurt', 'lat' => 50.1189, 'lng' => 8.6612],
            ['name' => 'DERTOUR Frankfurt Ostend', 'address' => 'Hanauer Landstraße 234', 'postal_code' => '60314', 'city' => 'Frankfurt', 'lat' => 50.1134, 'lng' => 8.7134],
            ['name' => 'Urlaubstraum Frankfurt Fechenheim', 'address' => 'Alt-Fechenheim 78', 'postal_code' => '60386', 'city' => 'Frankfurt', 'lat' => 50.1089, 'lng' => 8.7489],
            ['name' => 'Reiseprofi Frankfurt Rödelheim', 'address' => 'Radilostraße 45', 'postal_code' => '60489', 'city' => 'Frankfurt', 'lat' => 50.1389, 'lng' => 8.6234],

            // Stuttgart (8 Büros)
            ['name' => 'Reisewelt Stuttgart Mitte', 'address' => 'Königstraße 56', 'postal_code' => '70173', 'city' => 'Stuttgart', 'lat' => 48.7758, 'lng' => 9.1829],
            ['name' => 'TUI Reisecenter Stuttgart Bad Cannstatt', 'address' => 'Marktstraße 89', 'postal_code' => '70372', 'city' => 'Stuttgart', 'lat' => 48.8067, 'lng' => 9.2189],
            ['name' => 'Schwaben-Reisen Stuttgart West', 'address' => 'Schwabstraße 123', 'postal_code' => '70193', 'city' => 'Stuttgart', 'lat' => 48.7689, 'lng' => 9.1567],
            ['name' => 'Urlaubsträume Stuttgart Degerloch', 'address' => 'Löffelstraße 45', 'postal_code' => '70597', 'city' => 'Stuttgart', 'lat' => 48.7489, 'lng' => 9.1789],
            ['name' => 'Fernreisen Stuttgart Feuerbach', 'address' => 'Stuttgarter Straße 78', 'postal_code' => '70469', 'city' => 'Stuttgart', 'lat' => 48.8089, 'lng' => 9.1567],
            ['name' => 'Reisebüro Stuttgart Vaihingen', 'address' => 'Vaihinger Markt 23', 'postal_code' => '70563', 'city' => 'Stuttgart', 'lat' => 48.7234, 'lng' => 9.1089],
            ['name' => 'Weltreisen Stuttgart Zuffenhausen', 'address' => 'Unterländer Straße 134', 'postal_code' => '70435', 'city' => 'Stuttgart', 'lat' => 48.8367, 'lng' => 9.1789],
            ['name' => 'DERTOUR Stuttgart Ost', 'address' => 'Ostendstraße 67', 'postal_code' => '70188', 'city' => 'Stuttgart', 'lat' => 48.7889, 'lng' => 9.2089],

            // Düsseldorf (8 Büros)
            ['name' => 'Reisezentrum Düsseldorf Altstadt', 'address' => 'Flinger Straße 45', 'postal_code' => '40213', 'city' => 'Düsseldorf', 'lat' => 51.2254, 'lng' => 6.7763],
            ['name' => 'TUI Reisecenter Düsseldorf Pempelfort', 'address' => 'Nordstraße 89', 'postal_code' => '40477', 'city' => 'Düsseldorf', 'lat' => 51.2389, 'lng' => 6.7889],
            ['name' => 'Rheinreisen Düsseldorf Oberkassel', 'address' => 'Luegallee 123', 'postal_code' => '40545', 'city' => 'Düsseldorf', 'lat' => 51.2289, 'lng' => 6.7434],
            ['name' => 'Urlaubsparadies Düsseldorf Bilk', 'address' => 'Bilker Allee 67', 'postal_code' => '40219', 'city' => 'Düsseldorf', 'lat' => 51.2089, 'lng' => 6.7789],
            ['name' => 'Fernweh Düsseldorf Benrath', 'address' => 'Benrather Schloßallee 34', 'postal_code' => '40597', 'city' => 'Düsseldorf', 'lat' => 51.1689, 'lng' => 6.8789],
            ['name' => 'Reisebüro Düsseldorf Gerresheim', 'address' => 'Gerresheimer Straße 78', 'postal_code' => '40625', 'city' => 'Düsseldorf', 'lat' => 51.2267, 'lng' => 6.8689],
            ['name' => 'Weltreisen Düsseldorf Unterbilk', 'address' => 'Friedrichstraße 145', 'postal_code' => '40217', 'city' => 'Düsseldorf', 'lat' => 51.2134, 'lng' => 6.7867],
            ['name' => 'FTI Reisebüro Düsseldorf Flingern', 'address' => 'Erkrather Straße 234', 'postal_code' => '40233', 'city' => 'Düsseldorf', 'lat' => 51.2234, 'lng' => 6.8134],

            // Dortmund (8 Büros)
            ['name' => 'Reisewelt Dortmund City', 'address' => 'Westenhellweg 89', 'postal_code' => '44137', 'city' => 'Dortmund', 'lat' => 51.5136, 'lng' => 7.4653],
            ['name' => 'TUI Reisecenter Dortmund Hörde', 'address' => 'Hörder Bahnhofstraße 45', 'postal_code' => '44263', 'city' => 'Dortmund', 'lat' => 51.4867, 'lng' => 7.5134],
            ['name' => 'Ruhr-Reisen Dortmund Hombruch', 'address' => 'Harkortstraße 123', 'postal_code' => '44225', 'city' => 'Dortmund', 'lat' => 51.4989, 'lng' => 7.4234],
            ['name' => 'Urlaubstraum Dortmund Aplerbeck', 'address' => 'Köln-Berliner-Straße 67', 'postal_code' => '44287', 'city' => 'Dortmund', 'lat' => 51.4734, 'lng' => 7.5489],
            ['name' => 'Fernreisen Dortmund Brackel', 'address' => 'Brackeler Hellweg 234', 'postal_code' => '44309', 'city' => 'Dortmund', 'lat' => 51.5189, 'lng' => 7.5789],
            ['name' => 'Reisebüro Dortmund Eving', 'address' => 'Evinger Platz 34', 'postal_code' => '44339', 'city' => 'Dortmund', 'lat' => 51.5489, 'lng' => 7.4989],
            ['name' => 'Weltreisen Dortmund Mengede', 'address' => 'Mengeder Markt 78', 'postal_code' => '44359', 'city' => 'Dortmund', 'lat' => 51.5689, 'lng' => 7.4234],
            ['name' => 'DERTOUR Dortmund Huckarde', 'address' => 'Rahmer Straße 145', 'postal_code' => '44369', 'city' => 'Dortmund', 'lat' => 51.5389, 'lng' => 7.3989],

            // Essen (8 Büros)
            ['name' => 'Reisezentrum Essen City', 'address' => 'Kettwiger Straße 56', 'postal_code' => '45127', 'city' => 'Essen', 'lat' => 51.4556, 'lng' => 7.0116],
            ['name' => 'TUI Reisecenter Essen Rüttenscheid', 'address' => 'Rüttenscheider Straße 123', 'postal_code' => '45130', 'city' => 'Essen', 'lat' => 51.4289, 'lng' => 7.0034],
            ['name' => 'Ruhr-Urlaub Essen Steele', 'address' => 'Steeler Straße 89', 'postal_code' => '45276', 'city' => 'Essen', 'lat' => 51.4489, 'lng' => 7.0789],
            ['name' => 'Urlaubsplaner Essen Werden', 'address' => 'Brückstraße 45', 'postal_code' => '45239', 'city' => 'Essen', 'lat' => 51.3989, 'lng' => 7.0489],
            ['name' => 'Fernweh Essen Borbeck', 'address' => 'Marktstraße 67', 'postal_code' => '45355', 'city' => 'Essen', 'lat' => 51.4789, 'lng' => 6.9489],
            ['name' => 'Reisebüro Essen Frohnhausen', 'address' => 'Frohnhauser Straße 134', 'postal_code' => '45143', 'city' => 'Essen', 'lat' => 51.4567, 'lng' => 6.9789],
            ['name' => 'Weltreisen Essen Kray', 'address' => 'Krayer Straße 234', 'postal_code' => '45307', 'city' => 'Essen', 'lat' => 51.4889, 'lng' => 7.0889],
            ['name' => 'FTI Reisebüro Essen Kettwig', 'address' => 'Hauptstraße 78', 'postal_code' => '45219', 'city' => 'Essen', 'lat' => 51.3489, 'lng' => 6.9489],

            // Leipzig (8 Büros)
            ['name' => 'Reisewelt Leipzig Mitte', 'address' => 'Petersstraße 45', 'postal_code' => '04109', 'city' => 'Leipzig', 'lat' => 51.3397, 'lng' => 12.3731],
            ['name' => 'TUI Reisecenter Leipzig Plagwitz', 'address' => 'Karl-Heine-Straße 89', 'postal_code' => '04229', 'city' => 'Leipzig', 'lat' => 51.3289, 'lng' => 12.3234],
            ['name' => 'Sachsen-Reisen Leipzig Gohlis', 'address' => 'Georg-Schumann-Straße 123', 'postal_code' => '04155', 'city' => 'Leipzig', 'lat' => 51.3567, 'lng' => 12.3689],
            ['name' => 'Urlaubstraum Leipzig Connewitz', 'address' => 'Könneritzstraße 67', 'postal_code' => '04229', 'city' => 'Leipzig', 'lat' => 51.3089, 'lng' => 12.3689],
            ['name' => 'Fernreisen Leipzig Reudnitz', 'address' => 'Wurzner Straße 234', 'postal_code' => '04318', 'city' => 'Leipzig', 'lat' => 51.3467, 'lng' => 12.4089],
            ['name' => 'Reisebüro Leipzig Leutzsch', 'address' => 'Lützner Straße 145', 'postal_code' => '04179', 'city' => 'Leipzig', 'lat' => 51.3434, 'lng' => 12.3189],
            ['name' => 'Weltreisen Leipzig Schönefeld', 'address' => 'Bornaische Straße 78', 'postal_code' => '04279', 'city' => 'Leipzig', 'lat' => 51.3189, 'lng' => 12.4289],
            ['name' => 'DERTOUR Leipzig Stötteritz', 'address' => 'Stötteritzer Straße 134', 'postal_code' => '04317', 'city' => 'Leipzig', 'lat' => 51.3234, 'lng' => 12.4189],

            // Dresden (8 Büros)
            ['name' => 'Reisezentrum Dresden Altstadt', 'address' => 'Prager Straße 23', 'postal_code' => '01069', 'city' => 'Dresden', 'lat' => 51.0504, 'lng' => 13.7373],
            ['name' => 'TUI Reisecenter Dresden Neustadt', 'address' => 'Königsbrücker Straße 89', 'postal_code' => '01099', 'city' => 'Dresden', 'lat' => 51.0689, 'lng' => 13.7434],
            ['name' => 'Elbe-Reisen Dresden Blasewitz', 'address' => 'Loschwitzer Straße 67', 'postal_code' => '01309', 'city' => 'Dresden', 'lat' => 51.0434, 'lng' => 13.7989],
            ['name' => 'Urlaubsplaner Dresden Plauen', 'address' => 'Chemnitzer Straße 123', 'postal_code' => '01187', 'city' => 'Dresden', 'lat' => 51.0267, 'lng' => 13.7089],
            ['name' => 'Fernweh Dresden Pieschen', 'address' => 'Bürgerstraße 145', 'postal_code' => '01127', 'city' => 'Dresden', 'lat' => 51.0789, 'lng' => 13.7234],
            ['name' => 'Reisebüro Dresden Leuben', 'address' => 'Leubener Straße 78', 'postal_code' => '01279', 'city' => 'Dresden', 'lat' => 51.0334, 'lng' => 13.8189],
            ['name' => 'Weltreisen Dresden Cotta', 'address' => 'Hamburger Straße 234', 'postal_code' => '01157', 'city' => 'Dresden', 'lat' => 51.0489, 'lng' => 13.6789],
            ['name' => 'FTI Reisebüro Dresden Striesen', 'address' => 'Schandauer Straße 56', 'postal_code' => '01277', 'city' => 'Dresden', 'lat' => 51.0389, 'lng' => 13.7889],

            // Bremen (7 Büros)
            ['name' => 'Reisewelt Bremen Mitte', 'address' => 'Obernstraße 45', 'postal_code' => '28195', 'city' => 'Bremen', 'lat' => 53.0793, 'lng' => 8.8017],
            ['name' => 'TUI Reisecenter Bremen Schwachhausen', 'address' => 'Schwachhauser Heerstraße 89', 'postal_code' => '28211', 'city' => 'Bremen', 'lat' => 53.0889, 'lng' => 8.8434],
            ['name' => 'Weser-Reisen Bremen Findorff', 'address' => 'Hemmstraße 123', 'postal_code' => '28215', 'city' => 'Bremen', 'lat' => 53.0867, 'lng' => 8.7789],
            ['name' => 'Urlaubstraum Bremen Neustadt', 'address' => 'Neuenlander Straße 67', 'postal_code' => '28199', 'city' => 'Bremen', 'lat' => 53.0634, 'lng' => 8.7989],
            ['name' => 'Fernreisen Bremen Walle', 'address' => 'Waller Heerstraße 234', 'postal_code' => '28217', 'city' => 'Bremen', 'lat' => 53.0934, 'lng' => 8.7434],
            ['name' => 'Reisebüro Bremen Hemelingen', 'address' => 'Godehardstraße 145', 'postal_code' => '28309', 'city' => 'Bremen', 'lat' => 53.0567, 'lng' => 8.9234],
            ['name' => 'Weltreisen Bremen Vegesack', 'address' => 'Gerhard-Rohlfs-Straße 78', 'postal_code' => '28757', 'city' => 'Bremen', 'lat' => 53.1634, 'lng' => 8.6189],

            // Hannover (8 Büros)
            ['name' => 'Reisezentrum Hannover Mitte', 'address' => 'Bahnhofstraße 23', 'postal_code' => '30159', 'city' => 'Hannover', 'lat' => 52.3759, 'lng' => 9.7320],
            ['name' => 'TUI Reisecenter Hannover Linden', 'address' => 'Limmerstraße 89', 'postal_code' => '30451', 'city' => 'Hannover', 'lat' => 52.3667, 'lng' => 9.7089],
            ['name' => 'Niedersachsen-Reisen Hannover List', 'address' => 'Podbielskistraße 123', 'postal_code' => '30177', 'city' => 'Hannover', 'lat' => 52.3934, 'lng' => 9.7589],
            ['name' => 'Urlaubsparadies Hannover Südstadt', 'address' => 'Hildesheimer Straße 67', 'postal_code' => '30169', 'city' => 'Hannover', 'lat' => 52.3567, 'lng' => 9.7434],
            ['name' => 'Fernweh Hannover Vahrenwald', 'address' => 'Vahrenwalder Straße 234', 'postal_code' => '30165', 'city' => 'Hannover', 'lat' => 52.3989, 'lng' => 9.7289],
            ['name' => 'Reisebüro Hannover Bothfeld', 'address' => 'Sutelstraße 145', 'postal_code' => '30659', 'city' => 'Hannover', 'lat' => 52.4234, 'lng' => 9.7789],
            ['name' => 'Weltreisen Hannover Ricklingen', 'address' => 'Göttinger Chaussee 78', 'postal_code' => '30459', 'city' => 'Hannover', 'lat' => 52.3389, 'lng' => 9.7089],
            ['name' => 'DERTOUR Hannover Kleefeld', 'address' => 'Kirchröder Straße 134', 'postal_code' => '30625', 'city' => 'Hannover', 'lat' => 52.3789, 'lng' => 9.8089],

            // Nürnberg (8 Büros)
            ['name' => 'Reisewelt Nürnberg Mitte', 'address' => 'Königstraße 34', 'postal_code' => '90402', 'city' => 'Nürnberg', 'lat' => 49.4521, 'lng' => 11.0767],
            ['name' => 'TUI Reisecenter Nürnberg Südstadt', 'address' => 'Allersberger Straße 89', 'postal_code' => '90461', 'city' => 'Nürnberg', 'lat' => 49.4234, 'lng' => 11.0889],
            ['name' => 'Franken-Reisen Nürnberg Gostenhof', 'address' => 'Fürther Straße 123', 'postal_code' => '90429', 'city' => 'Nürnberg', 'lat' => 49.4434, 'lng' => 11.0489],
            ['name' => 'Urlaubstraum Nürnberg Langwasser', 'address' => 'Breslauer Straße 67', 'postal_code' => '90471', 'city' => 'Nürnberg', 'lat' => 49.4089, 'lng' => 11.1234],
            ['name' => 'Fernreisen Nürnberg Schoppershof', 'address' => 'Nordostpark 234', 'postal_code' => '90411', 'city' => 'Nürnberg', 'lat' => 49.4789, 'lng' => 11.1089],
            ['name' => 'Reisebüro Nürnberg Wöhrd', 'address' => 'Äußere Sulzbacher Straße 145', 'postal_code' => '90491', 'city' => 'Nürnberg', 'lat' => 49.4634, 'lng' => 11.1034],
            ['name' => 'Weltreisen Nürnberg Zerzabelshof', 'address' => 'Ostendstraße 78', 'postal_code' => '90482', 'city' => 'Nürnberg', 'lat' => 49.4467, 'lng' => 11.1189],
            ['name' => 'FTI Reisebüro Nürnberg Steinbühl', 'address' => 'Schweinauer Straße 134', 'postal_code' => '90439', 'city' => 'Nürnberg', 'lat' => 49.4289, 'lng' => 11.0634],

            // Weitere Städte (je 5-6 Büros)
            // Duisburg (6 Büros)
            ['name' => 'Reisezentrum Duisburg Mitte', 'address' => 'Königstraße 45', 'postal_code' => '47051', 'city' => 'Duisburg', 'lat' => 51.4344, 'lng' => 6.7623],
            ['name' => 'TUI Reisecenter Duisburg Meiderich', 'address' => 'Von-der-Mark-Straße 89', 'postal_code' => '47137', 'city' => 'Duisburg', 'lat' => 51.4634, 'lng' => 6.7489],
            ['name' => 'Ruhr-Reisen Duisburg Homberg', 'address' => 'Homberger Straße 123', 'postal_code' => '47198', 'city' => 'Duisburg', 'lat' => 51.4789, 'lng' => 6.7989],
            ['name' => 'Urlaubswelt Duisburg Walsum', 'address' => 'Friedrich-Ebert-Straße 67', 'postal_code' => '47179', 'city' => 'Duisburg', 'lat' => 51.5234, 'lng' => 6.7389],
            ['name' => 'Fernreisen Duisburg Hamborn', 'address' => 'Duisburger Straße 234', 'postal_code' => '47166', 'city' => 'Duisburg', 'lat' => 51.4889, 'lng' => 6.7689],
            ['name' => 'Reisebüro Duisburg Rheinhausen', 'address' => 'Krefelder Straße 145', 'postal_code' => '47226', 'city' => 'Duisburg', 'lat' => 51.3989, 'lng' => 6.7289],

            // Bochum (6 Büros)
            ['name' => 'Reisewelt Bochum City', 'address' => 'Kortumstraße 56', 'postal_code' => '44787', 'city' => 'Bochum', 'lat' => 51.4818, 'lng' => 7.2196],
            ['name' => 'TUI Reisecenter Bochum Wattenscheid', 'address' => 'Hochstraße 89', 'postal_code' => '44866', 'city' => 'Bochum', 'lat' => 51.4889, 'lng' => 7.1489],
            ['name' => 'Ruhr-Urlaub Bochum Langendreer', 'address' => 'Alte Bahnhofstraße 123', 'postal_code' => '44892', 'city' => 'Bochum', 'lat' => 51.5034, 'lng' => 7.3234],
            ['name' => 'Urlaubstraum Bochum Weitmar', 'address' => 'Hattinger Straße 67', 'postal_code' => '44795', 'city' => 'Bochum', 'lat' => 51.4567, 'lng' => 7.1989],
            ['name' => 'Fernreisen Bochum Linden', 'address' => 'Herner Straße 234', 'postal_code' => '44809', 'city' => 'Bochum', 'lat' => 51.5134, 'lng' => 7.2489],
            ['name' => 'Reisebüro Bochum Querenburg', 'address' => 'Querenburger Höhe 145', 'postal_code' => '44801', 'city' => 'Bochum', 'lat' => 51.4434, 'lng' => 7.2634],

            // Wuppertal (6 Büros)
            ['name' => 'Reisezentrum Wuppertal Elberfeld', 'address' => 'Herzogstraße 34', 'postal_code' => '42103', 'city' => 'Wuppertal', 'lat' => 51.2562, 'lng' => 7.1508],
            ['name' => 'TUI Reisecenter Wuppertal Barmen', 'address' => 'Werther Brücke 78', 'postal_code' => '42275', 'city' => 'Wuppertal', 'lat' => 51.2689, 'lng' => 7.1989],
            ['name' => 'Bergische Reisen Wuppertal Vohwinkel', 'address' => 'Vohwinkeler Straße 123', 'postal_code' => '42329', 'city' => 'Wuppertal', 'lat' => 51.2389, 'lng' => 7.0789],
            ['name' => 'Urlaubsplaner Wuppertal Ronsdorf', 'address' => 'Lüttringhauser Straße 67', 'postal_code' => '42369', 'city' => 'Wuppertal', 'lat' => 51.2134, 'lng' => 7.2234],
            ['name' => 'Fernweh Wuppertal Cronenberg', 'address' => 'Hauptstraße 234', 'postal_code' => '42349', 'city' => 'Wuppertal', 'lat' => 51.2234, 'lng' => 7.1134],
            ['name' => 'Reisebüro Wuppertal Oberbarmen', 'address' => 'Berliner Straße 145', 'postal_code' => '42289', 'city' => 'Wuppertal', 'lat' => 51.2789, 'lng' => 7.2234],

            // Bielefeld (6 Büros)
            ['name' => 'Reisewelt Bielefeld Mitte', 'address' => 'Bahnhofstraße 45', 'postal_code' => '33602', 'city' => 'Bielefeld', 'lat' => 52.0302, 'lng' => 8.5325],
            ['name' => 'TUI Reisecenter Bielefeld Schildesche', 'address' => 'Jöllenbecker Straße 89', 'postal_code' => '33611', 'city' => 'Bielefeld', 'lat' => 52.0489, 'lng' => 8.5089],
            ['name' => 'Ostwestfalen-Reisen Bielefeld Sennestadt', 'address' => 'Windelsbleicher Straße 123', 'postal_code' => '33659', 'city' => 'Bielefeld', 'lat' => 51.9789, 'lng' => 8.6234],
            ['name' => 'Urlaubstraum Bielefeld Brackwede', 'address' => 'Hauptstraße 67', 'postal_code' => '33647', 'city' => 'Bielefeld', 'lat' => 52.0089, 'lng' => 8.5789],
            ['name' => 'Fernreisen Bielefeld Heepen', 'address' => 'Salzufler Straße 234', 'postal_code' => '33719', 'city' => 'Bielefeld', 'lat' => 52.0567, 'lng' => 8.5889],
            ['name' => 'Reisebüro Bielefeld Jöllenbeck', 'address' => 'Jöllenbecker Straße 145', 'postal_code' => '33739', 'city' => 'Bielefeld', 'lat' => 52.0834, 'lng' => 8.5234],

            // Bonn (6 Büros)
            ['name' => 'Reisezentrum Bonn City', 'address' => 'Poststraße 23', 'postal_code' => '53111', 'city' => 'Bonn', 'lat' => 50.7374, 'lng' => 7.0982],
            ['name' => 'TUI Reisecenter Bonn Bad Godesberg', 'address' => 'Koblenzer Straße 78', 'postal_code' => '53177', 'city' => 'Bonn', 'lat' => 50.6867, 'lng' => 7.1589],
            ['name' => 'Rhein-Reisen Bonn Beuel', 'address' => 'Friedrich-Breuer-Straße 123', 'postal_code' => '53225', 'city' => 'Bonn', 'lat' => 50.7434, 'lng' => 7.1389],
            ['name' => 'Urlaubsparadies Bonn Poppelsdorf', 'address' => 'Clemens-August-Straße 67', 'postal_code' => '53115', 'city' => 'Bonn', 'lat' => 50.7234, 'lng' => 7.0789],
            ['name' => 'Fernweh Bonn Duisdorf', 'address' => 'Rochusstraße 234', 'postal_code' => '53123', 'city' => 'Bonn', 'lat' => 50.7089, 'lng' => 7.0489],
            ['name' => 'Reisebüro Bonn Endenich', 'address' => 'Endenicher Straße 145', 'postal_code' => '53115', 'city' => 'Bonn', 'lat' => 50.7334, 'lng' => 7.0689],

            // Mannheim (6 Büros)
            ['name' => 'Reisewelt Mannheim Innenstadt', 'address' => 'Planken P5, 12', 'postal_code' => '68161', 'city' => 'Mannheim', 'lat' => 49.4875, 'lng' => 8.4660],
            ['name' => 'TUI Reisecenter Mannheim Neckarstadt', 'address' => 'Mittelstraße 89', 'postal_code' => '68169', 'city' => 'Mannheim', 'lat' => 49.5034, 'lng' => 8.4789],
            ['name' => 'Kurpfalz-Reisen Mannheim Lindenhof', 'address' => 'Meerfeldstraße 123', 'postal_code' => '68163', 'city' => 'Mannheim', 'lat' => 49.4734, 'lng' => 8.4589],
            ['name' => 'Urlaubstraum Mannheim Käfertal', 'address' => 'Mannheimer Straße 67', 'postal_code' => '68309', 'city' => 'Mannheim', 'lat' => 49.5234, 'lng' => 8.5089],
            ['name' => 'Fernreisen Mannheim Seckenheim', 'address' => 'Hauptstraße 234', 'postal_code' => '68239', 'city' => 'Mannheim', 'lat' => 49.4634, 'lng' => 8.5434],
            ['name' => 'Reisebüro Mannheim Sandhofen', 'address' => 'Sandhofer Straße 145', 'postal_code' => '68307', 'city' => 'Mannheim', 'lat' => 49.5434, 'lng' => 8.5234],

            // Karlsruhe (5 Büros)
            ['name' => 'Reisezentrum Karlsruhe City', 'address' => 'Kaiserstraße 78', 'postal_code' => '76133', 'city' => 'Karlsruhe', 'lat' => 49.0069, 'lng' => 8.4037],
            ['name' => 'TUI Reisecenter Karlsruhe Durlach', 'address' => 'Pfinztalstraße 123', 'postal_code' => '76227', 'city' => 'Karlsruhe', 'lat' => 48.9989, 'lng' => 8.4689],
            ['name' => 'Baden-Reisen Karlsruhe Mühlburg', 'address' => 'Rheinstraße 67', 'postal_code' => '76185', 'city' => 'Karlsruhe', 'lat' => 49.0134, 'lng' => 8.3689],
            ['name' => 'Urlaubswelt Karlsruhe Nordstadt', 'address' => 'Erzbergerstraße 234', 'postal_code' => '76133', 'city' => 'Karlsruhe', 'lat' => 49.0189, 'lng' => 8.3989],
            ['name' => 'Fernweh Karlsruhe Südstadt', 'address' => 'Ettlinger Straße 145', 'postal_code' => '76137', 'city' => 'Karlsruhe', 'lat' => 48.9934, 'lng' => 8.4189],

            // Wiesbaden (5 Büros)
            ['name' => 'Reisewelt Wiesbaden Mitte', 'address' => 'Wilhelmstraße 45', 'postal_code' => '65183', 'city' => 'Wiesbaden', 'lat' => 50.0826, 'lng' => 8.2400],
            ['name' => 'TUI Reisecenter Wiesbaden Biebrich', 'address' => 'Rathausstraße 89', 'postal_code' => '65203', 'city' => 'Wiesbaden', 'lat' => 50.0434, 'lng' => 8.2434],
            ['name' => 'Hessen-Reisen Wiesbaden Dotzheim', 'address' => 'Freudenbergstraße 123', 'postal_code' => '65197', 'city' => 'Wiesbaden', 'lat' => 50.0689, 'lng' => 8.1989],
            ['name' => 'Urlaubsparadies Wiesbaden Schierstein', 'address' => 'Söhnleinstraße 67', 'postal_code' => '65201', 'city' => 'Wiesbaden', 'lat' => 50.0367, 'lng' => 8.2889],
            ['name' => 'Fernreisen Wiesbaden Erbenheim', 'address' => 'Berliner Straße 234', 'postal_code' => '65205', 'city' => 'Wiesbaden', 'lat' => 50.0589, 'lng' => 8.3134],

            // Münster (5 Büros)
            ['name' => 'Reisezentrum Münster City', 'address' => 'Ludgeristraße 34', 'postal_code' => '48143', 'city' => 'Münster', 'lat' => 51.9606, 'lng' => 7.6261],
            ['name' => 'TUI Reisecenter Münster Hiltrup', 'address' => 'Marktallee 78', 'postal_code' => '48165', 'city' => 'Münster', 'lat' => 51.9089, 'lng' => 7.6434],
            ['name' => 'Westfalen-Reisen Münster Gievenbeck', 'address' => 'Dieckmannstraße 123', 'postal_code' => '48161', 'city' => 'Münster', 'lat' => 51.9489, 'lng' => 7.5689],
            ['name' => 'Urlaubstraum Münster Kinderhaus', 'address' => 'Idenbrockplatz 67', 'postal_code' => '48159', 'city' => 'Münster', 'lat' => 51.9789, 'lng' => 7.6089],
            ['name' => 'Fernreisen Münster Roxel', 'address' => 'Roxeler Straße 234', 'postal_code' => '48161', 'city' => 'Münster', 'lat' => 51.9689, 'lng' => 7.5434],

            // Augsburg (5 Büros)
            ['name' => 'Reisewelt Augsburg City', 'address' => 'Bahnhofstraße 23', 'postal_code' => '86150', 'city' => 'Augsburg', 'lat' => 48.3668, 'lng' => 10.8986],
            ['name' => 'TUI Reisecenter Augsburg Pfersee', 'address' => 'Augsburger Straße 89', 'postal_code' => '86157', 'city' => 'Augsburg', 'lat' => 48.3789, 'lng' => 10.8689],
            ['name' => 'Schwaben-Reisen Augsburg Haunstetten', 'address' => 'Bgm.-Ackermann-Straße 123', 'postal_code' => '86179', 'city' => 'Augsburg', 'lat' => 48.3234, 'lng' => 10.9134],
            ['name' => 'Urlaubsplaner Augsburg Göggingen', 'address' => 'Bgm.-Widmeier-Straße 67', 'postal_code' => '86199', 'city' => 'Augsburg', 'lat' => 48.3434, 'lng' => 10.8734],
            ['name' => 'Fernreisen Augsburg Lechhausen', 'address' => 'Neuburger Straße 234', 'postal_code' => '86167', 'city' => 'Augsburg', 'lat' => 48.3889, 'lng' => 10.9234],

            // Mönchengladbach (5 Büros)
            ['name' => 'Reisezentrum Mönchengladbach City', 'address' => 'Hindenburgstraße 45', 'postal_code' => '41061', 'city' => 'Mönchengladbach', 'lat' => 51.1948, 'lng' => 6.4332],
            ['name' => 'TUI Reisecenter Mönchengladbach Rheydt', 'address' => 'Hauptstraße 89', 'postal_code' => '41236', 'city' => 'Mönchengladbach', 'lat' => 51.1634, 'lng' => 6.4434],
            ['name' => 'Niederrhein-Reisen MG Wickrath', 'address' => 'Wickrather Straße 123', 'postal_code' => '41189', 'city' => 'Mönchengladbach', 'lat' => 51.1534, 'lng' => 6.3989],
            ['name' => 'Urlaubswelt MG Hardt', 'address' => 'Hardter Landstraße 67', 'postal_code' => '41169', 'city' => 'Mönchengladbach', 'lat' => 51.2134, 'lng' => 6.4689],
            ['name' => 'Fernreisen MG Eicken', 'address' => 'Viersener Straße 234', 'postal_code' => '41063', 'city' => 'Mönchengladbach', 'lat' => 51.2034, 'lng' => 6.4234],

            // Gelsenkirchen (5 Büros)
            ['name' => 'Reisewelt Gelsenkirchen City', 'address' => 'Bahnhofstraße 34', 'postal_code' => '45879', 'city' => 'Gelsenkirchen', 'lat' => 51.5107, 'lng' => 7.0994],
            ['name' => 'TUI Reisecenter Gelsenkirchen Buer', 'address' => 'Hochstraße 78', 'postal_code' => '45894', 'city' => 'Gelsenkirchen', 'lat' => 51.5789, 'lng' => 7.0589],
            ['name' => 'Ruhr-Reisen Gelsenkirchen Erle', 'address' => 'Cranger Straße 123', 'postal_code' => '45891', 'city' => 'Gelsenkirchen', 'lat' => 51.5434, 'lng' => 7.0234],
            ['name' => 'Urlaubstraum Gelsenkirchen Ückendorf', 'address' => 'Bochumer Straße 67', 'postal_code' => '45886', 'city' => 'Gelsenkirchen', 'lat' => 51.5234, 'lng' => 7.0689],
            ['name' => 'Fernreisen Gelsenkirchen Horst', 'address' => 'Horster Straße 234', 'postal_code' => '45899', 'city' => 'Gelsenkirchen', 'lat' => 51.5589, 'lng' => 7.1234],

            // Braunschweig (5 Büros)
            ['name' => 'Reisezentrum Braunschweig City', 'address' => 'Schützenstraße 23', 'postal_code' => '38100', 'city' => 'Braunschweig', 'lat' => 52.2646, 'lng' => 10.5236],
            ['name' => 'TUI Reisecenter Braunschweig Weststadt', 'address' => 'Wolfenbütteler Straße 89', 'postal_code' => '38102', 'city' => 'Braunschweig', 'lat' => 52.2567, 'lng' => 10.5089],
            ['name' => 'Löwen-Reisen Braunschweig Lehndorf', 'address' => 'Saarbrückener Straße 123', 'postal_code' => '38116', 'city' => 'Braunschweig', 'lat' => 52.2889, 'lng' => 10.5434],
            ['name' => 'Urlaubsplaner Braunschweig Querum', 'address' => 'Bevenroder Straße 67', 'postal_code' => '38108', 'city' => 'Braunschweig', 'lat' => 52.2989, 'lng' => 10.5789],
            ['name' => 'Fernreisen Braunschweig Wenden', 'address' => 'Hamburger Straße 234', 'postal_code' => '38112', 'city' => 'Braunschweig', 'lat' => 52.2789, 'lng' => 10.4989],

            // Chemnitz (5 Büros)
            ['name' => 'Reisewelt Chemnitz Zentrum', 'address' => 'Innere Klosterstraße 34', 'postal_code' => '09111', 'city' => 'Chemnitz', 'lat' => 50.8322, 'lng' => 12.9252],
            ['name' => 'TUI Reisecenter Chemnitz Kaßberg', 'address' => 'Zschopauer Straße 78', 'postal_code' => '09111', 'city' => 'Chemnitz', 'lat' => 50.8434, 'lng' => 12.9089],
            ['name' => 'Sachsen-Reisen Chemnitz Gablenz', 'address' => 'Straße Usti nad Labem 123', 'postal_code' => '09119', 'city' => 'Chemnitz', 'lat' => 50.8567, 'lng' => 12.9689],
            ['name' => 'Urlaubstraum Chemnitz Sonnenberg', 'address' => 'Clausstraße 67', 'postal_code' => '09130', 'city' => 'Chemnitz', 'lat' => 50.8189, 'lng' => 12.9434],
            ['name' => 'Fernreisen Chemnitz Markersdorf', 'address' => 'Neefestraße 234', 'postal_code' => '09119', 'city' => 'Chemnitz', 'lat' => 50.8689, 'lng' => 12.9789],

            // Kiel (5 Büros)
            ['name' => 'Reisezentrum Kiel City', 'address' => 'Holstenstraße 45', 'postal_code' => '24103', 'city' => 'Kiel', 'lat' => 54.3233, 'lng' => 10.1394],
            ['name' => 'TUI Reisecenter Kiel Wik', 'address' => 'Stoschstraße 89', 'postal_code' => '24143', 'city' => 'Kiel', 'lat' => 54.3489, 'lng' => 10.1634],
            ['name' => 'Förde-Reisen Kiel Gaarden', 'address' => 'Elisabethstraße 123', 'postal_code' => '24143', 'city' => 'Kiel', 'lat' => 54.3189, 'lng' => 10.1789],
            ['name' => 'Urlaubsplaner Kiel Elmschenhagen', 'address' => 'Preetzer Straße 67', 'postal_code' => '24146', 'city' => 'Kiel', 'lat' => 54.2989, 'lng' => 10.1889],
            ['name' => 'Fernreisen Kiel Mettenhof', 'address' => 'Vaasastraße 234', 'postal_code' => '24109', 'city' => 'Kiel', 'lat' => 54.3589, 'lng' => 10.1089],

            // Aachen (5 Büros)
            ['name' => 'Reisewelt Aachen City', 'address' => 'Adalbertstraße 23', 'postal_code' => '52062', 'city' => 'Aachen', 'lat' => 50.7753, 'lng' => 6.0839],
            ['name' => 'TUI Reisecenter Aachen Brand', 'address' => 'Trierer Straße 78', 'postal_code' => '52078', 'city' => 'Aachen', 'lat' => 50.7434, 'lng' => 6.1234],
            ['name' => 'Euregio-Reisen Aachen Burtscheid', 'address' => 'Burtscheider Straße 123', 'postal_code' => '52064', 'city' => 'Aachen', 'lat' => 50.7634, 'lng' => 6.0634],
            ['name' => 'Urlaubstraum Aachen Laurensberg', 'address' => 'Vaalser Straße 67', 'postal_code' => '52074', 'city' => 'Aachen', 'lat' => 50.7889, 'lng' => 6.0489],
            ['name' => 'Fernreisen Aachen Richterich', 'address' => 'Lütticher Straße 234', 'postal_code' => '52072', 'city' => 'Aachen', 'lat' => 50.8034, 'lng' => 6.0689],
        ];

        foreach ($travelAgencies as $agency) {
            BookingLocation::create([
                'type' => 'stationary',
                'name' => $agency['name'],
                'description' => 'Persönliche Beratung für Ihre perfekte Reise',
                'address' => $agency['address'],
                'postal_code' => $agency['postal_code'],
                'city' => $agency['city'],
                'latitude' => $agency['lat'],
                'longitude' => $agency['lng'],
                'phone' => '+49 ' . rand(100, 999) . ' ' . rand(100000, 999999),
                'email' => 'info@' . strtolower(str_replace([' ', '-', '.'], ['', '', ''], $agency['name'])) . '.de',
            ]);
        }
    }
}
