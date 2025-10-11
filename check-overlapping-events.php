<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CustomEvent;
use App\Models\Country;

echo "=== Events mit gleichen Koordinaten in Italien ===\n\n";

$italy = Country::where('iso_code', 'IT')->first();
$defaultLat = $italy->capital->lat ?? $italy->lat;
$defaultLng = $italy->capital->lng ?? $italy->lng;

echo "Italien Standard-Koordinaten: $defaultLat, $defaultLng\n\n";

$italyEvents = CustomEvent::with(['eventTypes', 'countries'])
    ->where('is_active', true)
    ->whereHas('countries', function($q) {
        $q->where('iso_code', 'IT');
    })
    ->get();

echo "Events mit Italien Standard-Koordinaten:\n\n";

$sameLocationEvents = [];
foreach ($italyEvents as $event) {
    $country = $event->countries->where('iso_code', 'IT')->first();
    if ($country) {
        $lat = $country->pivot->use_default_coordinates ? $country->lat : $country->pivot->latitude;
        $lng = $country->pivot->use_default_coordinates ? $country->lng : $country->pivot->longitude;

        // Falls keine Koordinaten gesetzt, nimm Hauptstadt
        if (empty($lat) || empty($lng)) {
            if ($country->capital) {
                $lat = $country->capital->lat;
                $lng = $country->capital->lng;
            }
        }

        // Prüfe ob gleiche Koordinaten wie Standard
        if (abs((float)$lat - (float)$defaultLat) < 0.001 && abs((float)$lng - (float)$defaultLng) < 0.001) {
            $icon = $event->eventTypes->isNotEmpty() ? $event->eventTypes->first()->icon : $event->marker_icon;
            $eventTypeName = $event->eventTypes->isNotEmpty() ? $event->eventTypes->first()->name : 'Keine';

            $sameLocationEvents[] = [
                'id' => $event->id,
                'title' => $event->title,
                'icon' => $icon,
                'event_type' => $eventTypeName,
                'lat' => $lat,
                'lng' => $lng
            ];
        }
    }
}

foreach ($sameLocationEvents as $evt) {
    echo sprintf("ID %-3d | Icon: %-30s | Type: %-25s | %s\n",
        $evt['id'],
        $evt['icon'],
        $evt['event_type'],
        substr($evt['title'], 0, 50)
    );
}

echo "\n\nGesamt: " . count($sameLocationEvents) . " Events an der gleichen Position!\n";
echo "\nDas ist das Problem: Alle diese Events haben die gleichen Koordinaten.\n";
echo "Der Marker-Cluster zeigt nur ein Icon an - je nachdem welches Event zuerst kommt.\n";
