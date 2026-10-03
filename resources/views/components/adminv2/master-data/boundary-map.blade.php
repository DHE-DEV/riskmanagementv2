{{--
    OpenStreetMap-Karte mit den Laendergrenzen aus unserer Datenbank.

    - url: GeoJSON der Grenzen (BoundaryGeoJsonController)
    - lat / lng: gespeicherter Mittelpunkt – als Markierung, wenn vorhanden
    - height: Hoehe der Karte
--}}
@props([
    'url',
    'lat' => '',
    'lng' => '',
    'height' => 'h-80',
    'emptyText' => 'Für diesen Eintrag liegen keine Grenzdaten vor – die Karte zeigt nur den Mittelpunkt.',
])

@assets
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Karte mit Grenz-Layer. Die Karte selbst liegt ausserhalb der Alpine-Daten;
        // aendern sich Mittelpunkt oder Quelle, baut Livewire das Element neu auf.
        window.adminv2BoundaryMap = (url, center) => {
            let map = null;

            // Ringe, die den 180. Laengengrad kreuzen (Russland, USA, Fidschi), bekommen
            // durchgehende Laengen – sonst zieht Leaflet eine Linie einmal um die Welt.
            const unwrapRing = (ring) => {
                const lngs = ring.map((point) => point[0]);
                if (Math.max(...lngs) - Math.min(...lngs) <= 180) return ring;
                return ring.map(([lng, lat, ...rest]) => [lng < 0 ? lng + 360 : lng, lat, ...rest]);
            };
            const unwrap = (geometry) => {
                if (geometry.type === 'Polygon') geometry.coordinates = geometry.coordinates.map(unwrapRing);
                if (geometry.type === 'MultiPolygon') geometry.coordinates = geometry.coordinates.map((polygon) => polygon.map(unwrapRing));
                return geometry;
            };

            // Der Kartenausschnitt richtet sich nach dem Hauptgebiet jedes Landes (groesstes
            // Polygon) – Uebersee-Inseln wuerden sonst z. B. Europa auf Weltgroesse ziehen.
            const mainlandBounds = (collection) => {
                const bounds = L.latLngBounds([]);
                collection.features.forEach((feature) => {
                    const polygons = feature.geometry.type === 'Polygon' ? [feature.geometry.coordinates] : feature.geometry.coordinates;
                    let best = null;
                    let bestArea = -1;
                    polygons.forEach((polygon) => {
                        const ring = polygon[0];
                        const lngs = ring.map((point) => point[0]);
                        const lats = ring.map((point) => point[1]);
                        const box = L.latLngBounds([Math.min(...lats), Math.min(...lngs)], [Math.max(...lats), Math.max(...lngs)]);
                        const area = (box.getEast() - box.getWest()) * (box.getNorth() - box.getSouth());
                        if (area > bestArea) { bestArea = area; best = box; }
                    });
                    if (best) bounds.extend(best);
                });
                return bounds;
            };

            return {
                loading: true,
                empty: false,
                failed: false,
                init() {
                    map = L.map(this.$refs.map, {
                        center: center ?? [20, 0],
                        zoom: center ? 4 : 2,
                        minZoom: 1,
                        scrollWheelZoom: false,
                        attributionControl: true,
                    });

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 18,
                        attribution: '&copy; OpenStreetMap',
                    }).addTo(map);

                    if (center) {
                        L.marker(center).addTo(map);
                    }

                    fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                        .then((response) => response.ok ? response.json() : Promise.reject(response.status))
                        .then((collection) => {
                            this.loading = false;

                            if (! collection.features?.length) {
                                this.empty = true;

                                return;
                            }

                            collection.features.forEach((feature) => unwrap(feature.geometry));

                            const layer = L.geoJSON(collection, {
                                style: { color: '#0b3250', weight: 1, fillColor: '#2563eb', fillOpacity: 0.25 },
                                onEachFeature: (feature, featureLayer) => {
                                    featureLayer.bindTooltip(feature.properties.name, { sticky: true });
                                    featureLayer.on('mouseover', () => featureLayer.setStyle({ fillOpacity: 0.45 }));
                                    featureLayer.on('mouseout', () => layer.resetStyle(featureLayer));
                                },
                            }).addTo(map);

                            // Erst die tatsaechliche Groesse der Karte nehmen, dann den Ausschnitt
                            // so waehlen, dass alle Hauptgebiete vollstaendig zu sehen sind.
                            map.invalidateSize();
                            const bounds = mainlandBounds(collection);
                            map.fitBounds(bounds.isValid() ? bounds : layer.getBounds(), { padding: [16, 16] });
                        })
                        .catch(() => {
                            this.loading = false;
                            this.failed = true;
                        });

                    setTimeout(() => map && map.invalidateSize(), 50);
                },
                destroy() {
                    map?.remove();
                    map = null;
                },
            };
        };
    </script>
@endassets

@php
    $center = is_numeric($lat) && is_numeric($lng) ? [(float) $lat, (float) $lng] : null;
@endphp

<div
    wire:key="boundary-map-{{ md5($url.($center ? implode(',', $center) : '')) }}"
    wire:ignore
    x-data="adminv2BoundaryMap(@js($url), @js($center))"
    {{ $attributes->class('flex flex-col gap-2') }}
>
    <div x-ref="map" class="{{ $height }} w-full overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-900"></div>
    <p class="text-xs text-zinc-500" x-show="loading" x-cloak>Grenzen werden geladen …</p>
    <p class="text-xs text-zinc-500" x-show="empty" x-cloak>{{ $emptyText }}</p>
    <p class="text-xs text-red-700 dark:text-red-400" x-show="failed" x-cloak>Die Grenzen konnten nicht geladen werden.</p>
</div>
