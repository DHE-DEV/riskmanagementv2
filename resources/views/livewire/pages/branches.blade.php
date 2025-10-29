@php
    $active = 'branches';
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Filialen & Standorte - Global Travel Monitor</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <!-- Font Awesome -->
    @php($faKit = config('services.fontawesome.kit'))
    @if(!empty($faKit))
        <script src="https://kit.fontawesome.com/{{ e($faKit) }}.js" crossorigin="anonymous" onload="window.__faKitOk=true" onerror="window.__faKitOk=false"></script>
        <script>
        (function(){
            function addCss(href){
                var l=document.createElement('link'); l.rel='stylesheet'; l.href=href; document.head.appendChild(l);
            }
            var fallbackHref = '{{ file_exists(public_path('vendor/fontawesome/css/all.min.css')) ? asset('vendor/fontawesome/css/all.min.css') : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' }}';
            window.addEventListener('DOMContentLoaded', function(){
                setTimeout(function(){ if(!window.__faKitOk){ addCss(fallbackHref); } }, 800);
            });
        })();
        </script>
    @elseif (file_exists(public_path('vendor/fontawesome/css/all.min.css')))
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" />
    @else
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    @endif

    <style>
        /* Basis-Layout */
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }

        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* Header - feststehend */
        .header {
            flex-shrink: 0;
            height: 64px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            z-index: 10000;
        }

        /* Footer - feststehend */
        .footer {
            flex-shrink: 0;
            height: 32px;
            background: white;
            color: black;
            z-index: 9999;
            border-top: 1px solid #e5e7eb;
        }

        /* Hauptbereich - dynamisch */
        .main-content {
            flex: 1;
            display: flex;
            min-height: 0;
        }

        /* Navigation - feste Breite */
        .navigation {
            flex-shrink: 0;
            width: 64px;
            background: black;
        }

        /* Sidebar - feste Breite */
        .sidebar {
            flex-shrink: 0;
            width: 320px;
            background: #f9fafb;
            overflow-y: auto;
            height: 100%;
            position: relative;
            border-right: 1px solid #e5e7eb;
        }

        /* Map Container - nimmt restlichen Platz ein */
        .map-container {
            flex: 1;
            position: relative;
            height: 100%;
            overflow: hidden;
        }

        #branches-map {
            width: 100%;
            height: 100%;
        }

        /* Marker Cluster Custom Styling */
        .marker-cluster div {
            width: 40px;
            height: 40px;
            text-align: center;
            border-radius: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .marker-cluster-small {
            background-color: rgba(34, 197, 94, 0.6);
        }

        .marker-cluster-small div {
            background-color: rgba(34, 197, 94, 0.8);
            color: white;
        }

        .marker-cluster-medium {
            background-color: rgba(59, 130, 246, 0.6);
        }

        .marker-cluster-medium div {
            background-color: rgba(59, 130, 246, 0.8);
            color: white;
        }

        .marker-cluster-large {
            background-color: rgba(239, 68, 68, 0.6);
        }

        .marker-cluster-large div {
            background-color: rgba(239, 68, 68, 0.8);
            color: white;
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Header -->
    <x-public-header />

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navigation -->
        <x-public-navigation :active="$active" />

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="p-4">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fa-regular fa-building mr-2"></i>
                    Filialen & Standorte
                </h2>

                <div class="bg-white p-4 rounded-lg border border-gray-200 mb-4">
                    <p class="text-sm text-gray-600">
                        Hier können Sie Ihre Filialen und Standorte verwalten.
                    </p>
                </div>

                <div class="space-y-3">
                    <button class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                        <i class="fa-regular fa-plus"></i>
                        Neue Filiale hinzufügen
                    </button>
                </div>

                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Ihre Filialen</h3>
                    <div class="space-y-2">
                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900 text-sm">Hauptsitz</h4>
                                    <p class="text-xs text-gray-600 mt-1">
                                        @if($customer->company_street)
                                            {{ $customer->company_street }} {{ $customer->company_house_number }}<br>
                                            {{ $customer->company_postal_code }} {{ $customer->company_city }}
                                        @else
                                            <span class="text-gray-400 italic">Keine Adresse hinterlegt</span>
                                        @endif
                                    </p>
                                </div>
                                <button class="text-blue-600 hover:text-blue-800 text-xs">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg border border-dashed border-gray-300 text-center">
                            <i class="fa-regular fa-building text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-500">Keine weiteren Filialen</p>
                            <p class="text-xs text-gray-400 mt-1">Fügen Sie Ihre ersten Standorte hinzu</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Container -->
        <div class="map-container">
            <div id="branches-map"></div>
        </div>
    </div>

    <!-- Footer -->
    <x-public-footer />
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
    // Initialize map
    let map;
    let markersLayer;

    document.addEventListener('DOMContentLoaded', function() {
        initMap();
    });

    function initMap() {
        // Initialize the map centered on Germany with Zoom-Beschränkungen
        map = L.map('branches-map', {
            center: [51.1657, 10.4515],
            zoom: 6,
            zoomControl: true,
            worldCopyJump: false,
            maxBounds: [[-90, -180], [90, 180]],
            minZoom: 2
        });

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Window Resize Event-Listener für dynamische Karten-Anpassung
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                if (map) {
                    map.invalidateSize();
                }
            }, 250);
        });

        // Initialize marker cluster group
        markersLayer = L.markerClusterGroup({
            chunkedLoading: true,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
            maxClusterRadius: 50
        });

        map.addLayer(markersLayer);

        // Load and display branches
        loadBranches();
    }

    async function loadBranches() {
        // Add main office marker if address exists
        @if($customer->company_postal_code && $customer->company_city)
            const mainOfficeAddress = '{{ $customer->company_street }} {{ $customer->company_house_number }}, {{ $customer->company_postal_code }} {{ $customer->company_city }}, {{ $customer->company_country }}';

            try {
                const coords = await geocodeAddress(mainOfficeAddress);
                if (coords) {
                    // Create custom icon for main office
                    const mainOfficeIcon = L.divIcon({
                        className: 'custom-marker',
                        html: '<div style="background-color: #3b82f6; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="fa-solid fa-building" style="font-size: 20px;"></i></div>',
                        iconSize: [40, 40],
                        iconAnchor: [20, 20]
                    });

                    const marker = L.marker([coords.lat, coords.lon], { icon: mainOfficeIcon });

                    // Add popup with office information
                    const popupContent = `
                        <div style="min-width: 200px;">
                            <h3 style="font-weight: bold; margin-bottom: 8px; color: #1f2937;">
                                <i class="fa-solid fa-building" style="color: #3b82f6; margin-right: 4px;"></i>
                                Hauptsitz
                            </h3>
                            <p style="margin: 4px 0; font-size: 14px;">
                                <strong>{{ $customer->company_name }}</strong>
                            </p>
                            @if($customer->company_additional)
                            <p style="margin: 4px 0; font-size: 13px; color: #6b7280;">{{ $customer->company_additional }}</p>
                            @endif
                            <p style="margin: 4px 0; font-size: 13px;">
                                {{ $customer->company_street }} {{ $customer->company_house_number }}<br>
                                {{ $customer->company_postal_code }} {{ $customer->company_city }}
                            </p>
                            @if($customer->company_country)
                            <p style="margin: 4px 0; font-size: 13px; color: #6b7280;">{{ $customer->company_country }}</p>
                            @endif
                        </div>
                    `;

                    marker.bindPopup(popupContent);
                    markersLayer.addLayer(marker);

                    // Center map on main office
                    map.setView([coords.lat, coords.lon], 13);
                }
            } catch (error) {
                console.error('Error geocoding main office:', error);
            }
        @endif

        // TODO: Load additional branches from database
        // This would be done via an API call to fetch all branches
    }

    async function geocodeAddress(address) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`);
            const data = await response.json();

            if (data && data.length > 0) {
                return {
                    lat: parseFloat(data[0].lat),
                    lon: parseFloat(data[0].lon)
                };
            }
            return null;
        } catch (error) {
            console.error('Geocoding error:', error);
            return null;
        }
    }

    function centerMap() {
        @if($customer->company_postal_code && $customer->company_city)
            // Re-geocode and center on main office
            const mainOfficeAddress = '{{ $customer->company_street }} {{ $customer->company_house_number }}, {{ $customer->company_postal_code }} {{ $customer->company_city }}, {{ $customer->company_country }}';
            geocodeAddress(mainOfficeAddress).then(coords => {
                if (coords) {
                    map.setView([coords.lat, coords.lon], 13);
                }
            });
        @else
            // Default center on Germany
            map.setView([51.1657, 10.4515], 6);
        @endif
    }
</script>
</body>
</html>
