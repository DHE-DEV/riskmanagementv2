<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Travel Monitor</title>

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet MarkerCluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <!-- Font Awesome Einbindung: 1) Kit per .env (bevorzugt), 2) lokal (Zip entpackt), 3) CDN-Fallback -->
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
            height: 64px; /* 16 * 4 = 64px */
            background: white;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            z-index: 50;
        }
        
        /* Footer - feststehend */
        .footer {
            flex-shrink: 0;
            height: 32px; /* 50% weniger als 64px */
            background: white;
            color: black;
            z-index: 50;
            border-top: 1px solid #e5e7eb;
        }
        
        /* Hauptbereich - dynamisch */
        .main-content {
            flex: 1;
            display: flex;
            min-height: 0; /* Wichtig für Flexbox */
        }
        
        /* Navigation - feste Breite */
        .navigation {
            flex-shrink: 0;
            width: 64px; /* 16 * 4 = 64px */
            background: black;
        }
        
        /* Sidebar - feste Breite */
        .sidebar {
            flex-shrink: 0;
            width: 320px; /* 20 * 16 = 320px */
            background: #e5e7eb;
            overflow-y: auto;
            height: 100vh;
        }
        
        /* Statistics Container - gleiche Größe wie Sidebar */
        .statistics-container {
            flex-shrink: 0;
            width: 320px; /* 20 * 16 = 320px */
            background: #e5e7eb; /* Gleiche graue Farbe wie die Sidebar */
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .statistics-content {
            padding: 0;
            height: 100%;
            overflow-y: auto;
        }
        
        /* Karten-Bereich - dynamisch */
        .map-area {
            flex: 1;
            position: relative;
            min-width: 0; /* Wichtig für Flexbox */
        }
        
        .leaflet-container {
            height: 100%;
            width: 100%;
        }
        
        #map {
            height: 100%;
            width: 100%;
        }
        
        /* Rest der Styles bleiben gleich */
        .custom-marker {
            background: none;
            border: none;
        }
        .custom-marker i {
            font-size: 24px;
            filter: drop-shadow(2px 2px 2px rgba(0,0,0,0.5));
        }
        .social-marker {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 9999px;
            color: white;
            font-size: 14px;
        }
        .social-tiktok { background-color: #000000; }
        .social-instagram { background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fd5949 45%, #d6249f 60%, #285AEB 90%); }
        .social-facebook { background-color: #1877F2; }
        .social-youtube { background-color: #FF0000; }
        .social-generic { background-color: #0ea5e9; }
        .marker-popup {
            max-width: 280px;
            min-width: 250px;
        }
        .marker-popup h3 {
            font-weight: bold;
            margin-bottom: 8px;
            color: #1f2937;
        }
        .marker-popup .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 14px;
        }
        .marker-popup .info-label {
            font-weight: 500;
            color: #6b7280;
        }
        .marker-popup .info-value {
            color: #374151;
        }
        
        .popup-actions {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }
        
        .details-btn {
            width: 100%;
            padding: 8px 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .details-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        .severity-green { color: #10b981; }
        .severity-orange { color: #f59e0b; }
        .severity-red { color: #ef4444; }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Wetter und Zeitzonen Styles */
        .weather-timezone-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }
        
        .loading-weather {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            font-size: 14px;
        }
        
        .weather-section, .timezone-section {
            margin-bottom: 15px;
        }
        
        .weather-section h4, .timezone-section h4 {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .weather-grid, .timezone-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            font-size: 12px;
        }
        
        .weather-item, .timezone-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
        }
        
        .weather-label, .timezone-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .weather-value, .timezone-value {
            color: #374151;
            font-weight: 600;
        }
        
        .weather-value.temperature {
            color: #ef4444;
        }
        
        .weather-value.humidity {
            color: #3b82f6;
        }
        
        .weather-value.wind {
            color: #10b981;
        }
        
        .weather-notice {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            color: #856404;
            font-size: 12px;
        }
        
        .weather-error {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            color: #721c24;
            font-size: 12px;
        }
        
        /* Neue übersichtliche Zeit-Darstellung */
        .timezone-display {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 16px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            align-items: center; /* Zentriert horizontal alles */
            text-align: center; /* Textzentrierung */
        }
        
        .time-main {
            text-align: center;
            padding: 8px 0;
        }
        
        .time-large {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
            font-family: 'Courier New', monospace;
        }
        
        .date-medium {
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .timezone-details {
            display: flex;
            justify-content: center; /* Zentriert Inhalt */
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            width: 100%;
        }
        
        .timezone-info-inline {
            display: flex;
            align-items: center;
            justify-content: center; /* Zentriert Badges */
            gap: 12px;
            padding: 8px 12px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            margin-top: 8px;
            width: 100%;
        }
        
        .timezone-zone-inline {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }
        
        .timezone-abbr-inline {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
        }
        
        .berlin-diff-inline {
            font-size: 11px;
            color: #1e40af;
            font-weight: 600;
            padding: 2px 6px;
            background: #dbeafe;
            border-radius: 4px;
            border: 1px solid #bfdbfe;
        }
        
        .timezone-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .timezone-zone {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }
        
        .timezone-abbr {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
        }
        
        .berlin-diff {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            background: #dbeafe;
            border-radius: 4px;
            border: 1px solid #bfdbfe;
        }
        
        .diff-label {
            font-size: 11px;
            color: #1e40af;
            font-weight: 600;
        }
        
        .diff-value {
            font-size: 11px;
            color: #1e40af;
            font-weight: 700;
        }
        
        /* Neue verbesserte Wetter-Darstellung */
        .weather-display {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #bae6fd;
        }
        
        .weather-main {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e0f2fe;
        }
        
        .weather-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
        }
        
        /* Spezielle Sonnen-Icon-Styles */
        .weather-icon i.fa-sun {
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .weather-icon i.fa-cloud {
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
        }
        
        .weather-icon i.fa-cloud-rain {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }
        
        .weather-icon i.fa-bolt {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .weather-icon i.fa-snowflake {
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
            color: #0369a1;
        }
        
        .weather-primary {
            flex: 1;
        }
        
        .temperature-large {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
            font-family: 'Courier New', monospace;
        }
        
        .weather-description {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .weather-feels-like {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            border: 1px solid #e0f2fe;
        }
        
        .feels-label {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 2px;
        }
        
        .feels-value {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .weather-details {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .weather-detail-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e0f2fe;
            transition: all 0.2s ease;
        }
        
        .weather-detail-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .detail-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            font-size: 16px;
        }
        
        .detail-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .detail-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }
        
        .detail-value {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }
        
        /* Events Container Layout Fix */
        #eventsWrapper {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        #currentEvents {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }
        
        #eventsList {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        /* Event Sidebar Styles */
        .event-sidebar {
            position: fixed;
            top: 64px; /* Header height */
            bottom: 64px; /* Footer height */
            right: -400px; /* Start hidden */
            width: 400px;
            background: white;
            box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
            transition: right 0.3s ease-in-out;
            z-index: 9999; /* Höchster Z-Index für Vordergrund */
            display: flex;
            flex-direction: column;
        }

        .event-sidebar.w-2x { width: 800px; right: -800px; }
        .event-sidebar.w-3x { width: 1200px; right: -1200px; }
        
        .event-sidebar.open { right: 0; }
        
        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }
        
        .sidebar-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        
        .close-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .close-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }
        
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        /* Compact weather display for sidebar */
        .sidebar-weather-display {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #bae6fd;
            margin-bottom: 20px;
            margin-top: -10px;
        }
        
        .sidebar-weather-main {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .sidebar-weather-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        /* Spezielle Sonnen-Icon-Styles für Sidebar */
        .sidebar-weather-icon i.fa-sun {
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .sidebar-temperature {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            font-family: 'Courier New', monospace;
        }
        
        .sidebar-weather-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .sidebar-weather-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e0f2fe;
        }
        
        .sidebar-weather-item i {
            color: #475569;
            font-size: 14px;
        }
        
        .sidebar-weather-label {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }
        
        .sidebar-weather-value {
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
        }
        
        /* Event Details Styles */
        .event-details {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .event-header {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 16px;
        }
        
        .event-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 8px 0;
            line-height: 1.3;
        }
        
        .event-meta {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .event-type {
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .event-severity {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .event-info-grid {
            display: grid;
            gap: 12px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        
        .info-item .info-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }
        
        .info-item .info-value {
            font-size: 13px;
            color: #1e293b;
            font-weight: 600;
        }

        /* Event Type Filter Buttons */
        #eventTypeButtons button:not(#toggleAllEventTypes) {
            min-height: 60px;
            max-width: 100%;
        }

        #eventTypeButtons button span {
            display: block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 10px;
            line-height: 1.2;
        }

        /* Lighter Font Awesome Icons */
        #eventTypeButtons .font-light {
            font-weight: 300 !important;
        }

        #eventTypeButtons i.fa {
            font-weight: 300;
        }
    </style>
    
    <!-- GDACS Configuration -->
    <script>
        window.GDACS_ENABLED = {{ config('app.gdacs_enabled') ? 'true' : 'false' }};

        // Check for hide parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const hideParam = urlParams.get('hide');

            if (hideParam === 'hf') {
                // Hide header and footer
                const header = document.querySelector('.header');
                const footer = document.querySelector('.footer');
                const mainContent = document.querySelector('.main-content');

                if (header) {
                    header.style.display = 'none';
                }
                if (footer) {
                    footer.style.display = 'none';
                }
                if (mainContent) {
                    // Adjust main content to use full height
                    mainContent.style.height = '100vh';
                }
            }
        });
    </script>
</head>
<body>
<div class="app-container">
    <!-- Fixed Header -->
    <header class="header">
        <div class="flex items-center justify-between h-full px-4">
            <!-- Logo and Search -->
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <img src="/logo.png" alt="Logo" class="h-8 w-auto" style="margin-left:-5px"/>
                    <span class="text-xl font-semibold text-gray-800" style="margin-left: 30px;">Global Travel Monitor</span>
                    </div>
                <!--
                <div class="relative">
                    <input 
                        type="text" 
                        placeholder="Land suchen..." 
                        class="w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        id="countrySearch"
                    >
                    <div class="absolute right-3 top-2.5">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>-->
            </div>

            <!-- Status and Actions -->
            <div class="flex items-center space-x-4">
                <button
                    class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                    title="Daten aktualisieren"
                    onclick="refreshData()"
                    id="refreshButton"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Black Navigation Bar -->
        <nav class="navigation flex flex-col items-center justify-between py-4 h-full">
            <!-- Top Buttons -->
            <div class="flex flex-col items-center space-y-6">
                <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Menü" onclick="toggleRightContainer()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                
                <!-- Statistiken Button - vorübergehend auskommentiert
                <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Statistiken" onclick="toggleStatistics()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </button>
                -->
                
                <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Events" onclick="showSidebarLiveStatistics()">
                    <i class="fa-regular fa-brake-warning text-2xl" aria-hidden="true"></i>
                </button>
                <!--
                <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Flugzeuge" onclick="createAirportSidebar()">
                    <i class="fa-regular fa-plane text-2xl" aria-hidden="true"></i>
                </button>-->
<!--
                <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Social Media" onclick="createSocialSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v16H4z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 8h8v8H8z"></path>
                    </svg>
                </button>
                    -->
            </div>
            
            <!-- Bottom Buttons -->
            <div class="flex flex-col items-center space-y-3">
                <!--
                <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Filter" onclick="createNewFilterSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                </button>
    -->
                <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Karte zentrieren" onclick="centerMap()">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6" fill="currentColor" aria-hidden="true"><!--!Font Awesome Pro v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2025 Fonticons, Inc.--><path d="M320 544C443.7 544 544 443.7 544 320C544 196.3 443.7 96 320 96C196.3 96 96 196.3 96 320C96 325.9 96.2 331.8 96.7 337.6L91.8 339.2C81.9 342.6 73.3 348.1 66.4 355.1C64.8 343.6 64 331.9 64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C308.1 576 296.4 575.2 284.9 573.6C291.9 566.7 297.4 558 300.7 548.2L302.3 543.3C308.1 543.8 314 544 319.9 544zM320 160C408.4 160 480 231.6 480 320C480 407.2 410.2 478.1 323.5 480L334.4 447.2C398.3 440 448 385.8 448 320C448 249.3 390.7 192 320 192C254.2 192 200 241.7 192.8 305.6L160 316.5C161.9 229.8 232.8 160 320 160zM315.3 324.7C319.6 329 321.1 335.3 319.2 341.1L255.2 533.1C253 539.6 246.9 544 240 544C233.1 544 227 539.6 224.8 533.1L201 461.6L107.3 555.3C101.1 561.5 90.9 561.5 84.7 555.3C78.5 549.1 78.5 538.9 84.7 532.7L178.4 439L107 415.2C100.4 413 96 406.9 96 400C96 393.1 100.4 387 106.9 384.8L298.9 320.8C304.6 318.9 311 320.4 315.3 324.7zM162.6 400L213.1 416.8C217.9 418.4 221.6 422.1 223.2 426.9L240 477.4L278.7 361.3L162.6 400z"/></svg>
                </button>
                <!--
                <button class="p-3 text-white hover:bg-gray-800 rounded-lg transition-colors" title="Einstellungen" onclick="createSettingsSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </button>
    -->
            </div>
        </nav>

        <!-- Gray Sidebar -->
        <aside class="sidebar overflow-y-auto">
            <!-- Container ID Display -->
            <!--
            <div class="bg-blue-100 border-b border-blue-200 p-2">
                <p class="text-xs text-blue-800 font-mono text-center">Container ID: sidebar-liveStatistics</p>
            </div>
    -->
            <!-- Live Statistics -->
            <div class="bg-white rounded-lg shadow-sm" style="display: none;">
                <div class="flex items-center justify-between p-4 border-b border-gray-200 cursor-pointer" onclick="toggleSection('liveStatistics')">
                    <h3 class="font-semibold text-gray-800">Live Statistiken</h3>
                    <button class="text-gray-500 hover:text-gray-700" onclick="event.stopPropagation(); toggleSection('liveStatistics')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>
                
                <div id="liveStatistics" class="p-4">
                    <p class="text-sm text-gray-500 text-center">Live-Statistiken werden hier nicht angezeigt</p>
                </div>
            </div>
            
            <!-- Filters -->
            <div id="filtersWrapper" class="bg-white shadow-sm">
                <div class="flex items-center justify-between p-4 border-b border-gray-200 cursor-pointer" onclick="toggleSection('filters')">
                    <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        <span>Filter</span>
                        <span id="activeFilterIndicator" class="hidden ml-2 px-2 py-0.5 text-xs font-semibold bg-blue-500 text-white rounded-full">Aktiv</span>
                    </h3>
                    <button class="text-gray-500 hover:text-gray-700" onclick="event.stopPropagation(); toggleSection('filters')">
                        <svg id="filtersToggleIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>
                
                <div id="filters" class="p-4 space-y-4" style="display: none;">
                    <!-- Reset Filters Link -->
                    <div id="resetFiltersContainer">
                        <a href="#" onclick="window.location.reload(); return false;" class="text-sm text-blue-600 hover:text-blue-800 underline flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Alle Filter zurücksetzen
                        </a>
                    </div>

                    <!-- Länder -->
                    <div class="xborder xborder-gray-200 xrounded-lg bg-gray-100">
                        <div class="flex items-center justify-between p-3 border-b border-gray-200 cursor-pointer hover:bg-gray-50" onclick="toggleFilterSubSection('countriesSection')">
                            <h4 class="text-sm font-medium text-gray-700">Länder</h4>
                            <svg id="countriesToggleIcon" class="w-4 h-4 transform transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div id="countriesSection" class="p-3">
                            <div class="space-y-2">
                                <input
                                    type="text"
                                    placeholder="Land suchen (Name oder Code)..."
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    id="countryFilterInput"
                                    onkeyup="handleCountryFilterKeyup(event)"
                                    onkeydown="handleCountryFilterKeydown(event)"
                                >
                                <div id="countryFilterResults" class="space-y-1 text-sm text-gray-700 max-h-32 overflow-y-auto transition-all duration-200"></div>
                                <div id="selectedCountriesFilterDisplay" class="mt-2 space-y-1">
                                    <!-- Ausgewählte Länder werden hier dynamisch eingefügt -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Continents -->
                    <div class="xborder xborder-gray-200 xrounded-lg bg-gray-100">
                        <div class="flex items-center justify-between p-3 border-b border-gray-200 cursor-pointer hover:bg-gray-50" onclick="toggleFilterSubSection('continentsSection')">
                            <h4 class="text-sm font-medium text-gray-700">Welt Regionen</h4>
                            <svg id="continentsToggleIcon" class="w-4 h-4 transform transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div id="continentsSection" class="p-3">
                            <div class="grid grid-cols-2 gap-2" id="continentsList">
                                <!-- Kontinente werden hier dynamisch eingefügt -->
                            </div>
                        </div>
                    </div>

                    <!-- Anbieter -->
                     <!--
                    <div class="xborder xborder-gray-200 xrounded-lg bg-gray-100">
                        <div class="flex items-center justify-between p-3 border-b border-gray-200 cursor-pointer hover:bg-gray-50" onclick="toggleFilterSubSection('providersSection')">
                            <h4 class="text-sm font-medium text-gray-700">Anbieter</h4>
                            <svg id="providersToggleIcon" class="w-4 h-4 transform transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div id="providersSection" class="p-3">
                            <div class="grid gap-2" id="provider-buttons-container">
                                <button type="button" id="provider-gdacs" class="px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black" data-provider="gdacs" onclick="toggleProviderFilter('gdacs', this)" style="display: none;">GDACS</button>
                                <button type="button" id="provider-custom" class="px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black" data-provider="custom" onclick="toggleProviderFilter('custom', this)">Passolution</button>
                            </div>
                        </div>
                    </div>
    -->
                    <!-- Risikostufe -->
                    <div class="xborder xborder-gray-200 xrounded-lg bg-gray-100">
                        <div class="flex items-center justify-between p-3 border-b border-gray-200 cursor-pointer hover:bg-gray-50" onclick="toggleFilterSubSection('riskLevelSection')">
                            <h4 class="text-sm font-medium text-gray-700">Risikostufe</h4>
                            <svg id="riskLevelToggleIcon" class="w-4 h-4 transform transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div id="riskLevelSection" class="p-3">
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <button type="button" id="toggleAllRiskLevels" class="px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black col-span-2" onclick="toggleAllRiskLevels()">Alle ausblenden</button>
                                <button type="button" id="risk-info" class="px-3 py-2 text-xs rounded-lg border transition-colors text-white" style="background-color: #0066cc; border-color: #0066cc;" data-risk="info" onclick="toggleRiskFilter('info', this)">Information</button>
                                <button type="button" id="risk-green" class="px-3 py-2 text-xs rounded-lg border transition-colors text-white" style="background-color: #0fb67f; border-color: #0fb67f;" data-risk="green" onclick="toggleRiskFilter('green', this)">Niedrig</button>
                                <button type="button" id="risk-orange" class="px-3 py-2 text-xs rounded-lg border transition-colors text-white" style="background-color: #e6a50a; border-color: #e6a50a;" data-risk="orange" onclick="toggleRiskFilter('orange', this)">Mittel</button>
                                <button type="button" id="risk-red" class="px-3 py-2 text-xs rounded-lg border transition-colors text-white" style="background-color: #ff0000; border-color: #ff0000;" data-risk="red" onclick="toggleRiskFilter('red', this)">Hoch</button>
                            </div>
                        </div>
                    </div>

                    <!-- Eventtype -->
                    <div class="xborder xborder-gray-200 xrounded-lg bg-gray-100">
                        <div class="flex items-center justify-between p-3 border-b border-gray-200 cursor-pointer hover:bg-gray-50" onclick="toggleFilterSubSection('eventTypeSection')">
                            <h4 class="text-sm font-medium text-gray-700">Event-Typen</h4>
                            <svg id="eventTypeToggleIcon" class="w-4 h-4 transform transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div id="eventTypeSection" class="p-3">
                            <div class="grid grid-cols-2 gap-2 mb-2" id="eventTypeButtons">
                                <button type="button" id="toggleAllEventTypes" class="px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black col-span-2" onclick="toggleAllEventTypes()">Alle ausblenden</button>
                                <!-- Event-Type Buttons werden hier dynamisch eingefügt -->
                            </div>
                        </div>
                    </div>

                    <!-- Zeitraum -->
                    <div class="xborder xborder-gray-200 xrounded-lg bg-gray-100">
                        <div class="flex items-center justify-between p-3 border-b border-gray-200 cursor-pointer hover:bg-gray-50" onclick="toggleFilterSubSection('timePeriodSection')">
                            <h4 class="text-sm font-medium text-gray-700">Zeitraum</h4>
                            <svg id="timePeriodToggleIcon" class="w-4 h-4 transform transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div id="timePeriodSection" class="p-3">
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <button type="button" id="period-all" class="px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black" data-period="all" onclick="toggleTimePeriodFilter('all', this)">Alle ausblenden</button>
                                <button type="button" id="period-7days" class="px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-200 text-gray-700 border-gray-300" data-period="7days" onclick="toggleTimePeriodFilter('7days', this)">Letzte 7 Tage</button>
                                <button type="button" id="period-30days" class="px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-200 text-gray-700 border-gray-300" data-period="30days" onclick="toggleTimePeriodFilter('30days', this)">Letzte 30 Tage</button>
                            </div>
                        </div>
                    </div>

                    
                </div>
            </div>

            <!-- Current Events -->
            <div id="eventsWrapper" class="bg-white shadow-sm">
                <div class="flex items-center justify-between p-4 border-b border-gray-200 cursor-pointer" onclick="toggleSection('currentEvents')">
                    <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fa-regular fa-brake-warning"></i>
                        <span>Ereignisse (<span id="currentEventsCount">0</span>)</span>
                    </h3>
                    <!--
                    <button class="text-gray-500 hover:text-gray-700" onclick="event.stopPropagation(); toggleSection('currentEvents')">
                        <svg id="currentEventsToggleIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>-->
                </div>
                
                <div id="currentEvents" class="p-2" style="display: none; padding-bottom: 170px;">
                    <div id="futureEventsSection" style="display: none;">
                        <div class="flex items-center justify-between px-2 py-2 mb-2 cursor-pointer bg-gray-200 rounded" onclick="toggleEventSection('futureEvents')">
                            <p class="text-xs text-gray-700 font-medium">Zukünftige Ereignisse (<span id="futureEventsCount">0</span>)</p>
                            <svg id="futureEventsToggleIcon" class="w-4 h-4 text-gray-700 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transform: rotate(0deg);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div id="futureEventsList" class="space-y-2" style="display: none; padding-top: 10px; padding-bottom: 20px; margin-bottom: 20px;">
                            <!-- Zukünftige Events werden hier dynamisch eingefügt -->
                        </div>
                    </div>
                    <div class="flex items-center justify-between px-2 py-2 mb-2 cursor-pointer bg-gray-200 rounded" onclick="toggleEventSection('currentPastEvents')" style="position: relative; z-index: 2;">
                        <p class="text-xs text-gray-700 font-medium">Aktuelle Ereignisse (<span id="currentPastEventsCount">0</span>)</p>
                        <svg id="currentPastEventsToggleIcon" class="w-4 h-4 text-gray-700 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transform: rotate(180deg);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                    <div id="eventsList" class="space-y-2" style="position: relative; z-index: 1; padding-bottom: 60px; margin-bottom: 60px; padding-top: 10px; display: block;">
                        <!-- Aktuelle/vergangene Events werden hier dynamisch eingefügt -->
                    </div>
                </div>
            </div>

            <!-- Filters (entfernt am alten Platz) -->

            <!-- Map Control -->
            <div class="bg-white rounded-lg shadow-sm" style="display: none;">
                <div class="flex items-center justify-between p-4 border-b border-gray-200 cursor-pointer" onclick="toggleSection('mapControl')">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <h3 class="font-semibold text-gray-800">Karten-Steuerung</h3>
                    </div>
                    <button class="text-gray-500 hover:text-gray-700" onclick="event.stopPropagation(); toggleSection('mapControl')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>
                
                <div id="mapControl" class="p-4">
                    <button 
                        onclick="centerMap()"
                        class="w-full bg-gray-300 text-black py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center space-x-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                        </svg>
                        <span>Karte zentrieren</span>
                    </button>
                    
                    <button 
                        onclick="fetchGdacsEvents()"
                        class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center space-x-2 mt-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>GDACS aktualisieren</span>
                    </button>
                </div>
            </div>
        </aside>
        
        <!-- Statistics Container - wird anstelle der Sidebar angezeigt -->
        <div class="statistics-container bg-white" id="statisticsContainer" style="display: none; background-color:#ffffff;">
            
        <!-- Filter Sidebar - wird anstelle der Sidebar angezeigt -->
        <aside class="sidebar" id="filter-container" style="display: none;">
            <!-- Container ID Display -->
            <div class="bg-blue-100 border-b border-blue-200 p-2">
                <p class="text-xs text-blue-800 font-mono text-center">Container ID: filter-container</p>
            </div>
            
            <!-- Filter Content -->
            <div class="bg-white shadow-sm">
                <div class="flex items-center justify-between p-4 border-b border-gray-200 cursor-pointer hover:bg-gray-50" onclick="toggleFilterSection()">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        <h3 class="font-semibold text-gray-800">Erweiterte Filter</h3>
                    </div>
                    <svg id="filterToggleIcon" class="w-5 h-5 transform transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
                
                <div id="filterContent" class="p-4 space-y-4">
                    <!-- Continents -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Welt Regionen</h4>
                        <div class="grid grid-cols-2 gap-2" id="filterContinentsList">
                            <!-- Kontinente werden hier dynamisch eingefügt -->
                        </div>
                    </div>

                    <!-- Countries -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Länder</h4>
                        <div class="space-y-2">
                            <input 
                                type="text" 
                                placeholder="Land suchen..." 
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                id="filterCountryInput"
                                onkeyup="filterCountries(this.value)"
                            >
                        </div>
                    </div>

                    

                    <!-- Event Types -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Event-Typen</h4>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" checked> Alle Events
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" checked> GDACS Events
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" checked> Manuelle Events
                            </label>
                        </div>
                    </div>

                    <!-- Severity Levels -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Schweregrade</h4>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" checked> <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span> Rot (Hoch)
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" checked> <span class="w-3 h-3 bg-orange-500 rounded-full mr-2"></span> Orange (Mittel)
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" checked> <span class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></span> Gelb (Niedrig)
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" checked> <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span> Grün (Minimal)
                            </label>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Zeitraum</h4>
                        <div class="space-y-2">
                            <input 
                                type="date" 
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                id="filterStartDate"
                            >
                            <input 
                                type="date" 
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                id="filterEndDate"
                            >
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-4 border-t border-gray-200">
                        <button class="w-full bg-gray-300 text-black py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors mb-2">
                            Filter anwenden
                        </button>
                        <button class="w-full bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition-colors">
                            Filter zurücksetzen
                        </button>
                    </div>
                </div>
            </div>
        </aside>
            <!-- Container ID Display -->
            <div class="bg-blue-100 border-b border-blue-200 p-2">
                <p class="text-xs text-blue-800 font-mono text-center">Container ID: statisticsContainer-liveStatistics</p>
            </div>
            
            <div class="statistics-content">
                <!-- Überschrift für Statistik-Container -->
                <div class="bg-gray-300 text-black p-4 mb-4 rounded-t-lg" style="display: none;">
                    <h2 class="text-xl font-bold">Detaillierte Statistiken</h2>
                    <p class="text-blue-100 text-sm mt-1">Übersicht aller Event-Daten und Risiko-Analysen</p>
                </div>
                
                                        <!-- Live Statistics -->
            <div class="bg-white shadow-sm">
                <div class="flex items-center justify-between p-4 border-b border-gray-200 cursor-pointer hover:bg-gray-50" onclick="toggleStatisticsSection()">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <h3 class="font-semibold text-gray-800">Live Statistiken</h3>
                    </div>
                    <svg id="statisticsToggleIcon" class="w-5 h-5 transform transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
                
                <div id="statisticsContent" class="p-4 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Gesamt Events</span>
                            <span class="font-semibold text-lg" id="totalEvents">0</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Aktive Events</span>
                            <span class="font-semibold text-green-600" id="activeEvents">0</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Letzte 7 Tage</span>
                            <span class="font-semibold text-blue-600" id="lastWeekEvents">0</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Hochrisiko</span>
                            <span class="font-semibold text-red-600" id="highRiskEvents">0</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">GDACS Events</span>
                            <span class="font-semibold text-orange-600" id="gdacsEvents">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Map Area -->
        <main class="map-area">
            <!-- Map Container -->
            <div id="map">
                <!-- Leaflet Map wird hier initialisiert -->
            </div>
            
            <!-- Event Details Sidebar -->
            <div id="eventSidebar" class="event-sidebar">
                <div class="sidebar-header">
                    <div class="flex flex-col gap-1">
                        <h3 id="sidebarTitle">Erweiterte Informationen</h3>
                        <div class="flex gap-2 text-xs">
                            <button id="decreaseBtn" class="px-2 py-1 rounded bg-zinc-200 hover:bg-zinc-300" onclick="decreaseSidebarWidth()" title="Verkleinern">
                                <i class="fa-solid fa-magnifying-glass-minus"></i>
                            </button>
                            <button id="increaseBtn" class="px-2 py-1 rounded bg-zinc-200 hover:bg-zinc-300" onclick="increaseSidebarWidth()" title="Vergrößern">
                                <i class="fa-solid fa-magnifying-glass-plus"></i>
                            </button>
                        </div>
                    </div>
                    <button onclick="closeEventSidebar()" class="close-btn">
                        <i class="fa-regular fa-xmark"></i>
                    </button>
                </div>
                <div id="sidebarContent" class="sidebar-content">
                    <!-- Content will be loaded here -->
                </div>
            </div>

            <!-- Map Controls Overlay entfernt - Leaflet bietet eigene Controls -->

            <!-- Legend -->
             <!--
            <div class="absolute bottom-4 right-4 bg-white rounded-lg shadow-lg z-[1000] border border-gray-200 transition-all duration-300" id="legendContainer" style="bottom: 35px; right: 15px;">

                <div class="p-3 border-b border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors" onclick="toggleLegend()">
                    <div class="flex items-center justify-between" id="legendHeader">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-semibold text-gray-800">Legende</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-500 transition-all duration-300" id="legendToggleIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="p-4 max-w-xs" id="legendContent">
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full" style="background-color: #ff0000;"></div>
                            <span class="text-sm text-gray-700 font-medium">Hohes Risiko</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full" style="background-color: #e6a50a;"></div>
                            <span class="text-sm text-gray-700 font-medium">Mittleres Risiko</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full" style="background-color: #0fb67f;"></div>
                            <span class="text-sm text-gray-700 font-medium">Niedriges Risiko</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-sm text-gray-700 font-medium">Information</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <p class="text-xs text-gray-500 flex items-center">
                            <i class="fa-regular fa-pointer mr-1"></i>
                            Auf Marker klicken für Details
                        </p>
                    </div>
                </div>
            </div>-->

            <!-- Map Attribution -->
            <div class="absolute bottom-2 left-2 bg-white bg-opacity-90 rounded px-2 py-1 text-xs text-gray-600 z-[1000]">
                Leaflet | OpenStreetMap Deutschland - Kartendaten © OpenStreetMap-Mitwirkende
            </div>
        </main>
    </div>

    <!-- Fixed Footer -->
    <footer class="footer">
        <div class="flex items-center justify-between px-4 h-full">
            <div class="flex items-center space-x-6 text-sm">
                <span>© 2025 Passolution GmbH</span>
                <a href="https://www.passolution.de/impressum/" target="_blank" rel="noopener noreferrer" class="hover:text-blue-300 transition-colors">Impressum</a>
                <a href="https://www.passolution.de/datenschutz/" target="_blank" rel="noopener noreferrer" class="hover:text-blue-300 transition-colors">Datenschutz</a>
                <a href="https://www.passolution.de/agb/" target="_blank" rel="noopener noreferrer" class="hover:text-blue-300 transition-colors">AGB</a>
            </div>
            <div class="flex items-center space-x-4 text-sm">
                <span>Version 1.0.17</span>
                <span>Build: 2025-09-30</span>
            </div>
        </div>
    </footer>
</div>

<!-- Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
// Globale Variablen
let map;
let markers = [];
let markerClusterGroup = null;
let currentEvents = [];
let selectedContinent = null;
let countryOverlaysLayer = null;
let countryGeoJsonData = null;
let selectedCountry = null;
let isLoading = false;
let eventTypes = []; // Store loaded event types

// Kontinente - werden dynamisch von der API geladen
let continents = [];

// Load continents from database
async function loadContinentsFromDB() {
    try {
        console.log('Loading continents from database...');
        const response = await fetch('/api/continents');

        if (response.ok) {
            const data = await response.json();
            if (data.success && data.data) {
                continents = data.data;
                console.log(`Loaded ${continents.length} continents from database`);
                return true;
            }
        }
    } catch (error) {
        console.error('Failed to load continents from database:', error);
        console.log('Using empty continents array as fallback');
    }
    return false;
}

// Load country mappings from database
async function loadCountryMappingsFromDB() {
    try {
        console.log('Loading country mappings from database...');
        const response = await fetch('/api/countries/mappings');

        if (response.ok) {
            const data = await response.json();
            if (data.success && data.data) {
                // Override static mappings with database mappings
                countryNameMapping = data.data;
                console.log(`Loaded ${data.count} country mappings from database`);
                return true;
            }
        }
    } catch (error) {
        console.error('Failed to load country mappings from database:', error);
        console.log('Using static country mappings as fallback');
    }
    return false;
}

// Track event clicks
async function trackEventClick(eventId, clickType) {
    try {
        // Only track custom events
        if (!eventId || typeof eventId !== 'number') {
            return;
        }

        const response = await fetch('/api/custom-events/track-click', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                event_id: eventId,
                click_type: clickType
            })
        });

        if (!response.ok) {
            console.error('Failed to track click');
        }
    } catch (error) {
        console.error('Error tracking click:', error);
    }
}

// Initialize when the page is loaded
document.addEventListener('DOMContentLoaded', async () => {
    initializeMap();

    // Load continents and country mappings from database first
    await loadContinentsFromDB();
    await loadCountryMappingsFromDB();

    await loadEventTypes(); // Load event types first
    await loadInitialData();
    renderContinents();
    updateLastUpdated();
    updateFilterIndicator(); // Check initial filter state

    // URL-Parameter prüfen für direkten Zoom
    const urlParams = new URLSearchParams(window.location.search);
    const lat = urlParams.get('lat');
    const lng = urlParams.get('lng');
    const zoom = urlParams.get('zoom');
    const eventId = urlParams.get('event');
    const showMarker = urlParams.get('marker');

    if (lat && lng) {
        // Zur angegebenen Position zoomen
        const zoomLevel = zoom ? parseInt(zoom) : 12;
        const latitude = parseFloat(lat);
        const longitude = parseFloat(lng);
        map.setView([latitude, longitude], zoomLevel);

        // Wenn marker=true, einen temporären Marker anzeigen
        if (showMarker === 'true') {
            // Erstelle einen roten Marker an der Position
            const tempMarker = L.marker([latitude, longitude], {
                icon: L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style="background-color: #ef4444; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.4);"></div>`,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                })
            }).addTo(map);

            // Popup mit Koordinaten hinzufügen
            tempMarker.bindPopup(`
                <div class="p-2">
                    <strong>Standort</strong><br>
                    Breite: ${latitude.toFixed(6)}<br>
                    Länge: ${longitude.toFixed(6)}
                </div>
            `).openPopup();
        }

        // Optional: Marker für das spezifische Event öffnen
        if (eventId) {
            setTimeout(() => {
                markers.forEach(marker => {
                    // Prüfe ob dieser Marker zum Event gehört
                    const markerLatLng = marker.getLatLng();
                    if (Math.abs(markerLatLng.lat - latitude) < 0.0001 &&
                        Math.abs(markerLatLng.lng - longitude) < 0.0001) {
                        marker.openPopup();
                    }
                });
            }, 1000); // Warte bis Marker geladen sind
        }
    }

    // Automatische Aktualisierung alle 5 Minuten
    setInterval(loadDashboardData, 5 * 60 * 1000);

    // Pfeil-Icons initial an angezeigten Zustand anpassen
    // Filter standardmäßig zugeklappt, currentEvents standardmäßig geöffnet
    const filters = document.getElementById('filters');
    const currentEvents = document.getElementById('currentEvents');
    if (filters) filters.style.display = 'none';
    if (currentEvents) currentEvents.style.display = 'block';
    adjustSidebarLayout();
    syncSectionToggleIcon('filters');
    syncSectionToggleIcon('currentEvents');
    syncSectionToggleIcon('liveStatistics');
    syncSectionToggleIcon('mapControl');

    // Filter Unterbereiche Zustand wiederherstellen
    restoreFilterSubSections();
});

// Karte initialisieren
let initialMapView = null;

function initializeMap() {
    // Karte erstellen mit Weltansicht und Zoom-Beschränkungen
    map = L.map('map', {
        worldCopyJump: false,
        maxBounds: [[-90, -180], [90, 180]],
        minZoom: 2  // Verhindert Herauszoomen über Weltansicht hinaus
    }).setView([20, 0], 2);

    // Save initial map view for reset functionality
    initialMapView = {
        center: [20, 0],
        zoom: 2
    };

    // OpenStreetMap Tile Layer mit deutschen Namen
    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Satelliten-Layer
    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: '© Esri',
        maxZoom: 19
    });

    // Terrain-Layer
    const terrainLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenTopoMap contributors',
        maxZoom: 19
    });

    // Layer Control für Kartenansichten
    const baseMaps = {
        "Straße": osmLayer,
        "Satellit": satelliteLayer,
        "Terrain": terrainLayer
    };

    // Layer Control hinzufügen (nur Basiskarten, keine Overlays)
    L.control.layers(baseMaps, null, {
        position: 'topright',
        collapsed: true
    }).addTo(map);

    // Initialize country overlays layer as FeatureGroup (supports getBounds)
    if (!countryOverlaysLayer) {
        countryOverlaysLayer = L.featureGroup().addTo(map);
    }

    // Load GeoJSON data for countries
    loadCountryBoundaries();

    console.log('Leaflet Map initialized');
}

// Initiale Daten laden
async function loadInitialData() {
    await loadDashboardData();
    await loadStatistics();
}

// Dashboard-Daten laden
async function loadDashboardData() {
    try {
        let allEvents = [];
        
        // GDACS-Events laden (nur wenn aktiviert)
        if (window.GDACS_ENABLED) {
            const gdacsResponse = await fetch('/api/gdacs/dashboard-events');
            const gdacsResult = await gdacsResponse.json();
            
            // GDACS-Events verarbeiten
            if (gdacsResult.success) {
                const gdacsEvents = processGdacsEvents(gdacsResult.data.events);
                allEvents = allEvents.concat(gdacsEvents);
                console.log(`Loaded ${gdacsEvents.length} GDACS events`);
            } else {
                console.log('GDACS integration disabled or failed:', gdacsResult.message);
            }
        } else {
            console.log('GDACS integration disabled via configuration');
        }
        
        // CustomEvents laden
        const customResponse = await fetch('/api/custom-events/dashboard-events');
        const customResult = await customResponse.json();
        
        // CustomEvents verarbeiten
        if (customResult.success) {
            const customEvents = processCustomEvents(customResult.data.events);
            allEvents = allEvents.concat(customEvents);
            console.log(`Loaded ${customEvents.length} custom events`);
        }
        
        // Globale allEvents Variable setzen
        window.allEvents = allEvents;
        
        // Nach Datum/Zeit sortieren (neueste zuerst)
        const getEventTimeMs = (e) => {
            let v = e?.start_date || e?.date_iso || e?.date || e?.pub_date || e?.created_at;
            if (!v) return 0;
            if (typeof v === 'string') {
                v = v.replace(' ', 'T');
            }
            const t = Date.parse(v);
            return isNaN(t) ? 0 : t;
        };
        allEvents.sort((a, b) => getEventTimeMs(b) - getEventTimeMs(a));

        // Kontinente-Filter ZUERST anwenden (hat spezielle Logik)
        let filteredByContinent = [...allEvents];
        if (window.selectedContinents) {
            if (window.selectedContinents.size === 0) {
                // Keine Kontinente ausgewählt - keine Events anzeigen
                filteredByContinent = [];
            } else if (continents && continents.length > 0) {
                const allContinents = new Set(continents.map(c => c.id));
                const isAllContinentsSelected = allContinents.size === window.selectedContinents.size &&
                                               Array.from(allContinents).every(continent => window.selectedContinents.has(continent));
                
                if (!isAllContinentsSelected) {
                    // Nur spezifische Kontinente ausgewählt
                    filteredByContinent = allEvents.filter(event => {
                        const eventContinent = getEventContinent(event);
                        // Events ohne Kontinent (null) werden immer angezeigt
                        if (eventContinent === null) return true;
                        return window.selectedContinents.has(eventContinent);
                    });
                }
                // Wenn alle Kontinente ausgewählt sind, bleibt filteredByContinent = [...allEvents]
            }
        }

        // Debug: Risikostufen-Werte in den ersten paar Events ausgeben
        if (filteredByContinent.length > 0 && window.riskFilterDebug !== true) {
            window.riskFilterDebug = true;
            console.log('=== RISK LEVEL DEBUG ===');
            filteredByContinent.slice(0, 5).forEach((e, i) => {
                console.log(`Event ${i + 1}:`, {
                    risk_level: e.risk_level,
                    alert_level: e.alert_level,
                    alertlevel: e.alertlevel,
                    severity: e.severity,
                    title: e.title || e.event_title
                });
            });
            console.log('=== END RISK LEVEL DEBUG ===');
        }

        // Dann alle anderen Filter auf das Kontinente-gefilterte Ergebnis anwenden
        const filtered = filteredByContinent.filter(e => {
            // Provider-Filter
            const isGdacs = e.is_gdacs === true || e.source === 'gdacs';
            const isCustom = e.is_gdacs === false || e.source === 'custom';
            const allowGdacs = (window.providerFilter?.gdacs ?? true);
            const allowCustom = (window.providerFilter?.custom ?? true);
            const providerMatch = (isGdacs && allowGdacs) || (isCustom && allowCustom);
            
            // Länder-Filter
            let countryMatch = true;
            if (window.selectedCountries && window.selectedCountries.size > 0) {
                const eventCountry = e.country_name || e.country || '';
                // Events ohne Land werden immer angezeigt
                if (eventCountry === '') {
                    countryMatch = true;
                } else {
                    countryMatch = Array.from(window.selectedCountries.keys()).some(selectedCountry =>
                        eventCountry.toLowerCase().includes(selectedCountry.toLowerCase())
                    );
                }
            }
            
            // Risikostufe-Filter
            const originalRiskLevel = e.risk_level || e.alert_level || e.alertlevel || e.severity;
            const originalPriority = e.priority;
            let riskLevel = originalRiskLevel || 'green';
            
            // Priorität von CustomEvents hat Vorrang
            if (e.source === 'custom' && originalPriority) {
                const priority = originalPriority.toLowerCase();
                if (priority === 'info') riskLevel = 'info';
                else if (priority === 'low') riskLevel = 'green';
                else if (priority === 'medium') riskLevel = 'orange';
                else if (priority === 'high') riskLevel = 'red';
                else riskLevel = 'info';  // Default to info instead of green
            } else if (typeof riskLevel === 'string') {
                riskLevel = riskLevel.toLowerCase();
                // Exakte Matches für GDACS Events
                if (riskLevel === 'low' || riskLevel === 'green') {
                    riskLevel = 'green';
                } else if (riskLevel === 'medium' || riskLevel === 'orange' || riskLevel === 'yellow') {
                    riskLevel = 'orange';
                } else if (riskLevel === 'high' || riskLevel === 'red') {
                    riskLevel = 'red';
                } else if (riskLevel === 'critical') {
                    riskLevel = 'critical';
                } else {
                    riskLevel = 'green'; // Default fallback
                }
            } else if (typeof riskLevel === 'number') {
                // Numerische Werte auf Farben mappen
                if (riskLevel <= 1) riskLevel = 'green';
                else if (riskLevel <= 2) riskLevel = 'orange';
                else if (riskLevel <= 3) riskLevel = 'red';
                else riskLevel = 'critical';
            }
            
            // Debug logging für Risiko-Filter (nur erste 3 Events) - NACH dem Mapping
            if (!window.riskFilterDebugCount) window.riskFilterDebugCount = 0;
            if (window.riskFilterDebugCount < 3) {
                console.log(`Event ${window.riskFilterDebugCount + 1} Risk Mapping:`, {
                    title: e.title,
                    originalRiskLevel,
                    originalPriority,
                    source: e.source,
                    finalMappedRiskLevel: riskLevel
                });
                window.riskFilterDebugCount++;
            }
            
            const allowRisk = window.riskFilter?.[riskLevel] ?? true;
            
            // Eventtype-Filter
            // For CustomEvents, use event_type_id directly
            // For GDACS events, map the type to our event types
            let allowEventType = true;

            if (e.source === 'custom') {
                // CustomEvent - check if any of the event type IDs are enabled
                if (e.event_type_ids && e.event_type_ids.length > 0) {
                    // Many-to-many relationship: check if any of the event types are enabled
                    allowEventType = e.event_type_ids.some(typeId => window.eventTypeFilter?.[typeId] ?? true);
                } else if (e.event_type_id) {
                    // Legacy single event type
                    allowEventType = window.eventTypeFilter?.[e.event_type_id] ?? true;
                }
            } else {
                // GDACS event - try to map to our event types by code
                let eventTypeStr = (e.event_type || e.type || '').toLowerCase();

                // Find matching event type by code or name
                const matchedType = eventTypes.find(et => {
                    const code = et.code.toLowerCase();
                    const name = et.name.toLowerCase();
                    return eventTypeStr.includes(code) || eventTypeStr.includes(name) ||
                           (code === 'flood' && eventTypeStr.includes('überschwemmung')) ||
                           (code === 'hurricane' && (eventTypeStr.includes('cyclone') || eventTypeStr.includes('zyklon'))) ||
                           (code === 'wildfire' && (eventTypeStr.includes('fire') || eventTypeStr.includes('waldbrand'))) ||
                           (code === 'volcano' && eventTypeStr.includes('vulkan')) ||
                           (code === 'earthquake' && eventTypeStr.includes('erdbeben'));
                });

                if (matchedType) {
                    allowEventType = window.eventTypeFilter?.[matchedType.id] ?? true;
                }
            }
            
            // Zeitraum-Filter
            let timeMatch = true;
            if (window.timePeriodFilter === 'none') {
                // Wenn "none" gesetzt ist, keine Events anzeigen
                timeMatch = false;
            } else if (window.timePeriodFilter && window.timePeriodFilter !== 'all') {
                const now = new Date();
                const eventDate = new Date(e.start_date || e.event_date || e.created_at || e.date);
                const daysDiff = Math.floor((now - eventDate) / (1000 * 60 * 60 * 24));

                if (window.timePeriodFilter === '7days') {
                    timeMatch = daysDiff <= 7;
                } else if (window.timePeriodFilter === '30days') {
                    timeMatch = daysDiff <= 30;
                }
            }
            
            return providerMatch && countryMatch && allowRisk && allowEventType && timeMatch;
        });
        currentEvents = filtered;
        addMarkersToMap();
        renderEvents();
        updateStatistics();
        updateLastUpdated();
        
        console.log(`Total: ${currentEvents.length} events loaded`);
        
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        // Fallback zu Beispieldaten
        loadSampleData();
    }
}

// GDACS-Events mit echten Koordinaten verarbeiten
function processGdacsEvents(events) {
    const now = new Date();

    return events
        .filter(event => {
            // Prüfe ob das Event-Enddatum überschritten ist
            if (event.end_date) {
                const endDate = new Date(event.end_date);
                if (endDate < now) {
                    // Event ist abgelaufen - herausfiltern
                    return false;
                }
            }
            return true;
        })
        .map(event => {
        // Echte Koordinaten aus der Datenbank verwenden
        let latitude = null;
        let longitude = null;
        
        // Prüfe verschiedene mögliche Koordinaten-Felder
        if (event.lat && event.lng) {
            // Direkte lat/lng Felder (aus der Datenbank)
            latitude = parseFloat(event.lat);
            longitude = parseFloat(event.lng);
        } else if (event.latitude && event.longitude) {
            // API-Format latitude/longitude
            latitude = parseFloat(event.latitude);
            longitude = parseFloat(event.longitude);
        } else {
            // Fallback zu Länder-Koordinaten
            const coordinates = getCountryCoordinates(event.country);
            latitude = coordinates.latitude;
            longitude = coordinates.longitude;
        }
        
        // Store event in global repository for onClick handlers
        const processedEvent = {
            ...event,
            latitude: latitude,
            longitude: longitude,
            lat: latitude,  // Ensure both formats are available
            lng: longitude,  // Ensure both formats are available
            date: event.pub_date || event.date || null,
            icon: getEventIcon(event.event_type, event.severity),
            iconColor: getSeverityColor(event.severity),
            source: 'gdacs',
            is_gdacs: true,
            // Ländername aus der Beziehung laden - Backend liefert bereits den Namen
            country_name: event.country || 'Unbekannt',
            // GDACS Date Added verfügbar machen
            gdacs_date_added: event.gdacs_date_added || null,
            // Startdatum für einheitliche Anzeige - verwende date_iso vom Backend
            start_date: event.date_iso || event.date || event.pub_date || null,
            // Priorität für GDACS Events aus severity ableiten
            priority: event.severity || 'unknown'
        };
        
        // Store in global repository
        if (processedEvent.id != null) {
            window.eventById[processedEvent.id] = processedEvent;
        }
        
        return processedEvent;
    });
}

// Globales Event-Repository für OnClick-Handler
window.eventById = window.eventById || {};

// CustomEvents verarbeiten
function processCustomEvents(events) {
    const processedEvents = [];
    const now = new Date();

    events.forEach(event => {
        // Prüfe ob das Event-Enddatum überschritten ist
        if (event.end_date) {
            const endDate = new Date(event.end_date);
            if (endDate < now) {
                // Event ist abgelaufen - überspringen
                return;
            }
        }
        // Wenn Event mehrere Länder hat, erstelle einen Marker pro Land
        if (event.countries && event.countries.length > 0) {
            event.countries.forEach((country, index) => {
                const mapped = {
                    ...event,
                    // Eindeutige ID für jeden Marker
                    id: `${event.id}_country_${index}`,
                    original_event_id: event.id,
                    latitude: parseFloat(country.latitude),
                    longitude: parseFloat(country.longitude),
                    icon: getCustomEventIcon(event.marker_icon, event.event_type),
                    iconColor: event.marker_color,
                    source: 'custom',
                    event_type: event.event_type,
                    event_type_name: event.event_type_name,
                    // Ländername und Standort-Info
                    country_name: country.name,
                    country_iso: country.iso_code,
                    location_note: country.location_note,
                    severity: event.severity,
                    priority: event.priority,
                    country: country.name,
                    title: event.title,
                    description: event.description,
                    start_date: event.start_date,
                    end_date: event.end_date,
                    category: event.category,
                    tags: event.tags,
                    popup_content: event.popup_content,
                    marker_size: event.marker_size
                };

                if (mapped.id != null) {
                    window.eventById[mapped.id] = mapped;
                }
                processedEvents.push(mapped);
            });
        } else if (event.latitude && event.longitude) {
            // Fallback auf alte Single-Location-Logik
            const mapped = {
                ...event,
                latitude: parseFloat(event.latitude),
                longitude: parseFloat(event.longitude),
                icon: getCustomEventIcon(event.marker_icon, event.event_type),
                iconColor: event.marker_color,
                source: 'custom',
                event_type: event.event_type,
                event_type_name: event.event_type_name,
                country_name: event.country_relation ? event.country_relation.name_translations?.de || event.country_relation.name_translations?.en || event.country_relation.iso_code : (event.country || 'Unbekannt'),
                severity: event.severity,
                priority: event.priority,
                country: event.country || 'Unbekannt',
                title: event.title,
                description: event.description,
                start_date: event.start_date,
                end_date: event.end_date,
                category: event.category,
                tags: event.tags,
                popup_content: event.popup_content,
                marker_size: event.marker_size
            };

            if (mapped.id != null) {
                window.eventById[mapped.id] = mapped;
            }
            processedEvents.push(mapped);
        } else {
            // Events ohne Koordinaten und ohne Länder (nur für Liste)
            const mapped = {
                ...event,
                latitude: null,
                longitude: null,
                icon: getCustomEventIcon(event.marker_icon, event.event_type),
                iconColor: event.marker_color,
                source: 'custom',
                event_type: event.event_type,
                event_type_name: event.event_type_name,
                country_name: null,
                severity: event.severity,
                priority: event.priority,
                country: event.country || null,
                title: event.title,
                description: event.description,
                start_date: event.start_date,
                end_date: event.end_date,
                category: event.category,
                tags: event.tags,
                popup_content: event.popup_content,
                marker_size: event.marker_size
            };

            if (mapped.id != null) {
                window.eventById[mapped.id] = mapped;
            }
            processedEvents.push(mapped);
        }
    });

    return processedEvents;
}

// Länder-Koordinaten für Fallback
function getCountryCoordinates(country) {
    const coordinates = {
        'Indonesia': { latitude: -2.5489, longitude: 118.0149 },
        'Japan': { latitude: 36.2048, longitude: 138.2529 },
        'New': { latitude: -20.9043, longitude: 165.6180 }, // New Caledonia
        'India': { latitude: 20.5937, longitude: 78.9629 },
        'Russia': { latitude: 61.5240, longitude: 105.3188 },
        'Iceland': { latitude: 64.9631, longitude: -19.0208 },
        'Spain': { latitude: 40.4168, longitude: -3.7038 },
        'Botswana': { latitude: -22.3285, longitude: 24.6849 },
        'Solomon': { latitude: -9.6457, longitude: 160.1562 },
        'Tonga': { latitude: -21.1790, longitude: -175.1982 },
        'Pakistan': { latitude: 30.3753, longitude: 69.3451 },
        'United States': { latitude: 37.0902, longitude: -95.7129 },
        'Australia': { latitude: -25.2744, longitude: 133.7751 },
        'The': { latitude: -4.0383, longitude: 21.7587 }, // Democratic Republic of Congo
        'Portugal': { latitude: 39.3999, longitude: -8.2245 },
        'Angola': { latitude: -11.2027, longitude: 17.8739 },
        'Greece': { latitude: 39.0742, longitude: 21.8243 },
        'Canada': { latitude: 56.1304, longitude: -106.3468 },
        'Brazil': { latitude: -14.2350, longitude: -51.9253 },
        'Zambia': { latitude: -13.1339, longitude: 27.8493 },
        'Bolivia': { latitude: -16.2902, longitude: -63.5887 },
        'Albania': { latitude: 41.1533, longitude: 20.1683 },
        'Montenegro': { latitude: 42.7087, longitude: 19.3744 },
        'Guinea': { latitude: 9.9456, longitude: -9.6966 },
        'South': { latitude: 35.9078, longitude: 127.7669 }, // South Korea
        'Nigeria': { latitude: 9.0820, longitude: 8.6753 },
        'Ethiopia': { latitude: 9.1450, longitude: 40.4897 },
        'Sudan': { latitude: 12.8628, longitude: 30.2176 },
        'South Sudan': { latitude: 6.8770, longitude: 31.3070 },
        'China': { latitude: 35.8617, longitude: 104.1954 },
        'The Bahamas': { latitude: 25.0343, longitude: -77.3963 },
        'Belize': { latitude: 17.1899, longitude: -88.4976 },
        'Cuba': { latitude: 21.5218, longitude: -77.7812 },
        'Guatemala': { latitude: 15.7835, longitude: -90.2308 },
        'Mexico': { latitude: 23.6345, longitude: -102.5528 },
        'Madagascar': { latitude: -18.7669, longitude: 46.8691 },
        'Bulgaria': { latitude: 42.7339, longitude: 25.4858 },
        'North Macedonia': { latitude: 41.6086, longitude: 21.7453 },
        'Türkiye': { latitude: 38.9637, longitude: 35.2433 },
        'Burkina': { latitude: 12.2383, longitude: -1.5616 }, // Burkina Faso
        'Benin': { latitude: 9.3077, longitude: 2.3158 },
        'Central African Republic': { latitude: 6.6111, longitude: 20.9394 },
        'Cameroon': { latitude: 7.3697, longitude: 12.3547 },
        'Niger': { latitude: 17.6078, longitude: 8.0817 },
        'Chad': { latitude: 15.4542, longitude: 18.7322 },
        'Afghanistan': { latitude: 33.9391, longitude: 67.7100 },
        'Israel': { latitude: 31.0461, longitude: 34.8516 },
        'Iraq': { latitude: 33.2232, longitude: 43.6793 },
        'Iran': { latitude: 32.4279, longitude: 53.6880 },
        'Jordan': { latitude: 30.5852, longitude: 36.2384 },
        'Kyrgyzstan': { latitude: 41.2044, longitude: 74.7661 },
        'Kazakhstan': { latitude: 48.0196, longitude: 66.9237 },
        'Lebanon': { latitude: 33.8547, longitude: 35.8623 },
        'Gaza Strip': { latitude: 31.5017, longitude: 34.4668 },
        'Syria': { latitude: 34.8021, longitude: 38.9968 },
        'Tajikistan': { latitude: 38.5358, longitude: 71.0965 },
        'Turkmenistan': { latitude: 38.9697, longitude: 59.5563 },
        'Uzbekistan': { latitude: 41.3775, longitude: 64.5853 },
        'Austria': { latitude: 47.5162, longitude: 14.5501 },
        'Bosnia': { latitude: 43.9159, longitude: 17.6791 },
        'Belgium': { latitude: 50.8503, longitude: 4.3517 },
        'Switzerland': { latitude: 46.8182, longitude: 8.2275 },
        'Czech Republic': { latitude: 49.8175, longitude: 15.4730 },
        'Germany': { latitude: 51.1657, longitude: 10.4515 },
        'France': { latitude: 46.2276, longitude: 2.2137 },
        'Croatia': { latitude: 45.1000, longitude: 15.2000 },
        'Hungary': { latitude: 47.1625, longitude: 19.5033 },
        'Italy': { latitude: 41.8719, longitude: 12.5674 },
        'Liechtenstein': { latitude: 47.1660, longitude: 9.5554 },
        'Luxembourg': { latitude: 49.8153, longitude: 6.1296 },
        'Netherlands': { latitude: 52.1326, longitude: 5.2913 },
        'Poland': { latitude: 51.9194, longitude: 19.1451 },
        'Romania': { latitude: 45.9432, longitude: 24.9668 },
        'Serbia': { latitude: 44.0165, longitude: 21.0059 },
        'Slovenia': { latitude: 46.0569, longitude: 14.5058 },
        'Slovakia': { latitude: 48.6690, longitude: 19.6990 },
        'Ukraine': { latitude: 48.3794, longitude: 31.1656 },
        'Moldova': { latitude: 47.4116, longitude: 28.3699 },
        'Philippines': { latitude: 12.8797, longitude: 121.7740 },
        'Puerto Rico': { latitude: 18.2208, longitude: -66.5901 },
        'Dominican Republic': { latitude: 18.7357, longitude: -70.1627 },
        'Virgin Islands': { latitude: 18.3358, longitude: -64.8963 },
        'Anguilla': { latitude: 18.2206, longitude: -63.0686 },
        'Turks and Caicos Islands': { latitude: 21.6940, longitude: -71.7979 },
        'Bahamas': { latitude: 25.0343, longitude: -77.3963 },
        'Bermuda': { latitude: 32.3078, longitude: -64.7505 },
        'Taiwan': { latitude: 23.6978, longitude: 120.9605 },
        'default': { latitude: 0, longitude: 0 }
    };
    
    return coordinates[country] || coordinates['default'];
}

// Event-Icon basierend auf Typ und Schweregrad
function getEventIcon(eventType, severity) {
    const icons = {
        'earthquake': 'fa-solid fa-house-crack',
        'flood': 'fa-solid fa-water',
        'volcano': 'fa-solid fa-volcano',
        'storm': 'fa-solid fa-wind',
        'cyclone': 'fa-solid fa-hurricane',
        'drought': 'fa-solid fa-sun',
        'wildfire': 'fa-solid fa-fire',
        'unknown': 'fa-solid fa-circle-exclamation'
    };
    
    return icons[eventType] || icons['unknown'];
}

// CustomEvent-Icon normalisieren: bevorzugt explizites Icon, sonst Fallback nach Typ
function getCustomEventIcon(markerIcon, eventType) {
    if (markerIcon && markerIcon.startsWith('fa-')) {
        return markerIcon.includes('fa-solid') ? markerIcon : `fa-solid ${markerIcon}`;
    }
    const fallbackIcons = {
        'exercise': 'fa-solid fa-dumbbell',
        'earthquake': 'fa-solid fa-house-crack',
        'flood': 'fa-solid fa-water',
        'volcano': 'fa-solid fa-volcano',
        'storm': 'fa-solid fa-wind',
        'cyclone': 'fa-solid fa-hurricane',
        'drought': 'fa-solid fa-sun',
        'wildfire': 'fa-solid fa-fire',
        'other': 'fa-solid fa-location-pin'
    };
    return fallbackIcons[eventType] || 'fa-solid fa-location-pin';
}

// Deutsche Bezeichnungen für Event-Typen
function mapEventType(type, typeName) {
    // Wenn ein typeName vorhanden ist (aus der Datenbank), verwende diesen
    if (typeName) {
        return typeName;
    }

    // Fallback auf hardcodierte Map für Legacy-Daten
    const map = {
        'earthquake': 'Erdbeben',
        'hurricane': 'Hurrikan',
        'flood': 'Überschwemmung',
        'wildfire': 'Waldbrand',
        'volcano': 'Vulkan',
        'drought': 'Dürre',
        'exercise': 'Übung',
        'other': 'Sonstiges',
        'storm': 'Sturm',
        'cyclone': 'Zyklon',
        'tsunami': 'Tsunami',
        'terrorist_attack': 'Terroranschlag',
        'epidemic': 'Epidemie',
        'pandemic': 'Pandemie',
        'nuclear_accident': 'Nuklearunfall',
        'chemical_accident': 'Chemieunfall',
        'transportation_accident': 'Verkehrsunfall',
        'infrastructure_failure': 'Infrastrukturausfall',
        'cybersecurity': 'Cyber-Sicherheit',
        'political_unrest': 'Politische Unruhen',
        'financial_crisis': 'Finanzkrise',
        'general': 'Allgemein',
        'travel': 'Reiseverkehr',
        'safety': 'Sicherheit',
        'environment': 'Umweltereignisse'
    };

    const result = map[type?.toLowerCase()] || (type || 'Unbekannt');
    return result;
}

// Deutsche Bezeichnungen für Priorität
function mapPriority(priority) {
    const map = {
        'info': 'Information',
        'low': 'Niedrig',
        'medium': 'Mittel',
        'high': 'Hoch'
    };
    return map[priority] || priority || 'Unbekannt';
}

// Datum/Uhrzeit auf Deutsch ohne Sekunden formatieren
function formatDateTimeDE(dateInput) {
    if (!dateInput) return 'Unbekannt';
    const date = new Date(dateInput);
    if (Number.isNaN(date.getTime())) return 'Unbekannt';
    const fmt = new Intl.DateTimeFormat('de-DE', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hour12: false
    });
    return fmt.format(date);
}

// Schweregrad-Farbe (Legacy-Funktion)
function getSeverityColor(severity) {
    const colors = {
        'red': '#ef4444',
        'orange': '#f59e0b',
        'green': '#10b981',
        'low': '#10b981',
        'medium': '#f59e0b',
        'high': '#ef4444',
        'critical': '#dc2626'
    };
    
    return colors[severity] || '#6b7280';
}

// Prioritäts-basierte Farben für Marker
function getPriorityColor(priority) {
    const colors = {
        'info': '#0066cc',    // Blau - Information
        'low': '#0fb67f',     // Grün - geringes Risiko
        'medium': '#e6a50a',  // Orange - mittleres Risiko
        'high': '#ff0000',    // Rot - hohes Risiko
        // Auch severity-Werte für GDACS Events unterstützen
        'green': '#0fb67f',
        'yellow': '#e6a50a',
        'orange': '#e6a50a',
        'red': '#ff0000'
    };

    return colors[priority?.toLowerCase()] || '#e6a50a';
}

// Wetter-Icon basierend auf Wetterbedingung
function getWeatherIcon(weatherMain) {
    const icons = {
        'Clear': 'fa-sun',
        'Clouds': 'fa-cloud',
        'Rain': 'fa-cloud-rain',
        'Drizzle': 'fa-cloud-drizzle',
        'Thunderstorm': 'fa-bolt',
        'Snow': 'fa-snowflake',
        'Mist': 'fa-smog',
        'Smoke': 'fa-smog',
        'Haze': 'fa-smog',
        'Dust': 'fa-smog',
        'Fog': 'fa-smog',
        'Sand': 'fa-smog',
        'Ash': 'fa-smog',
        'Squall': 'fa-wind',
        'Tornado': 'fa-wind'
    };
    
    return icons[weatherMain] || 'fa-cloud-sun';
}

// Handle Details button click with tracking
function handleDetailsClick(eventId, isCustom, countryName = null) {
    console.log('handleDetailsClick aufgerufen mit eventId:', eventId, 'countryName:', countryName);

    // Track click for custom events
    if (isCustom && eventId) {
        trackEventClick(eventId, 'details_button');
    }

    // Finde das richtige Event basierend auf der Original-ID
    let event = null;

    // Zuerst versuche direkt über die ID zu finden
    event = window.eventById[eventId];

    if (event) {
        console.log('Event direkt gefunden:', event.title, 'ID:', event.id);
    }

    // Falls nicht gefunden, suche nur nach der exakten original_event_id (nicht nach id)
    // Dies verhindert, dass das falsche Event gefunden wird
    if (!event) {
        // Sammle alle passenden Events
        const matchingEvents = [];
        for (const [key, value] of Object.entries(window.eventById)) {
            // Nur nach original_event_id suchen, NICHT nach id
            if (value.original_event_id == eventId) {
                matchingEvents.push(value);
            }
        }

        if (matchingEvents.length > 0) {
            // Wenn ein countryName übergeben wurde, versuche das passende Event zu finden
            if (countryName) {
                const countryMatch = matchingEvents.find(e => e.country_name === countryName);
                if (countryMatch) {
                    event = countryMatch;
                    console.log('Event mit passendem Land gefunden:', event.title, 'Land:', countryName);
                } else {
                    // Fallback: erstes Event
                    event = matchingEvents[0];
                    console.log('Kein Event mit Land gefunden, verwende erstes:', event.title);
                }
            } else {
                // Nimm das erste gefundene Event (alle sollten dieselben Basis-Daten haben)
                event = matchingEvents[0];
                console.log('Event über original_event_id gefunden:', event.title, 'aus', matchingEvents.length, 'Matches');
            }
        }
    }

    // Wenn immer noch nicht gefunden, versuche API-Call für Custom Events
    if (!event && isCustom && eventId) {
        console.log('Event nicht im Cache, lade von API...');
        // Lade Event direkt von der API
        fetch(`/api/custom-events/${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    openEventSidebar(data.data);
                } else {
                    console.error('Fehler beim Laden des Events');
                    openEventSidebar({});
                }
            })
            .catch(error => {
                console.error('API-Fehler:', error);
                openEventSidebar({});
            });
        return;
    }

    // Fallback auf leeres Objekt
    if (!event) {
        console.warn('Event nicht gefunden für ID:', eventId);
        event = {};
    }

    // Open sidebar with event details
    openEventSidebar(event);
}

// Event Sidebar Funktionen
function openEventSidebar(event) {
    document.getElementById('sidebarTitle').textContent = 'Erweiterte Informationen';
    document.getElementById('eventSidebar').classList.add('open');

    // Button-Zustände initialisieren
    updateSidebarButtons();

    // Lade Event-Details in die Seitenleiste
    loadEventDetails(event);
}

function closeEventSidebar() {
    document.getElementById('eventSidebar').classList.remove('open');
}

// Sidebar Breite steuern
let currentSidebarWidth = 1; // Startwert 1x

function setSidebarWidth(multiplier) {
    const el = document.getElementById('eventSidebar');
    el.classList.remove('w-2x', 'w-3x');
    if (multiplier === 2) {
        el.classList.add('w-2x');
    } else if (multiplier === 3) {
        el.classList.add('w-3x');
    }
    currentSidebarWidth = multiplier;
    updateSidebarButtons();
}

function updateSidebarButtons() {
    const decreaseBtn = document.getElementById('decreaseBtn');
    const increaseBtn = document.getElementById('increaseBtn');
    
    // Verkleinern-Button: deaktiviert bei minimaler Größe (1x)
    if (currentSidebarWidth <= 1) {
        decreaseBtn.classList.remove('bg-zinc-200', 'hover:bg-zinc-300');
        decreaseBtn.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        decreaseBtn.style.pointerEvents = 'none';
    } else {
        decreaseBtn.classList.remove('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        decreaseBtn.classList.add('bg-zinc-200', 'hover:bg-zinc-300');
        decreaseBtn.style.pointerEvents = 'auto';
    }
    
    // Vergrößern-Button: deaktiviert bei maximaler Größe (3x)
    if (currentSidebarWidth >= 3) {
        increaseBtn.classList.remove('bg-zinc-200', 'hover:bg-zinc-300');
        increaseBtn.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        increaseBtn.style.pointerEvents = 'none';
    } else {
        increaseBtn.classList.remove('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        increaseBtn.classList.add('bg-zinc-200', 'hover:bg-zinc-300');
        increaseBtn.style.pointerEvents = 'auto';
    }
}

function decreaseSidebarWidth() {
    if (currentSidebarWidth > 1) {
        currentSidebarWidth--;
        setSidebarWidth(currentSidebarWidth);
    }
}

function increaseSidebarWidth() {
    if (currentSidebarWidth < 3) {
        currentSidebarWidth++;
        setSidebarWidth(currentSidebarWidth);
    }
}

// Event-Details in die Seitenleiste laden
async function loadEventDetails(event) {
    const sidebarContent = document.getElementById('sidebarContent');
    
    // Zeige Loading-Zustand
    sidebarContent.innerHTML = `
        <div class="flex items-center justify-center h-32">
            <div class="loading"></div>
            <span class="ml-2">Lade Event-Details...</span>
        </div>
    `;
    
    try {
        // Lade Wetter- und Zeitzonen-Daten
        const response = await fetch('/api/gdacs/event-details', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                latitude: event.latitude, 
                longitude: event.longitude 
            })
        });
        
        const result = await response.json();
        
        // Debug-Logging
        console.log('Event Details Debug:', {
            id: event.id,
            title: event.title,
            source: event.source,
            event_type: event.event_type,
            mapped_type: mapEventType(event.event_type, event.event_type_name),
            priority: event.priority,
            severity: event.severity,
            country: event.country,
            country_name: event.country_name,
            country_relation: event.country_relation,
            gdacs_date_added: event.gdacs_date_added
        });

        // Erstelle detaillierte Event-Anzeige
        let detailsHtml = `
            <div class="event-details">
                <div class="event-header">
                    <h2 class="event-title">${event.title}</h2>
                    <div class="event-meta">
                        ${event.event_types && event.event_types.length > 0
                            ? event.event_types.map(type => `<span class="event-type">${type}</span>`).join(' ')
                            : `<span class="event-type">${mapEventType(event.event_type, event.event_type_name)}</span>`
                        }
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium text-white" style="background-color: ${getPriorityColor(event.priority || event.severity)}">${mapPriority(event.priority || event.severity)}</span>
                        ${event.archived ? '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-warning text-white ml-2" style="background-color: #f59e0b">Archiviert</span>' : ''}
                    </div>
                </div>
                ${event.description ? `
                    <div class="event-description mt-3 mb-3">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Beschreibung</h4>
                        <div class="text-sm leading-6 text-gray-800 bg-gray-50 p-3 rounded-lg border-l-4" style="border-left-color: ${getPriorityColor(event.priority || event.severity)}">${escapeHtml(event.description)}</div>
                    </div>
                ` : ''}
                ${event.source === 'custom' && event.popup_content ? `
                    <div class="event-description mt-3 mb-3">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Beschreibung</h4>
                        <div class="text-sm leading-6 text-gray-800 bg-gray-50 p-3 rounded-lg border-l-4" style="border-left-color: ${getPriorityColor(event.priority || event.severity)}">${event.popup_content}</div>
                    </div>
                ` : ''}
                
                <div class="event-info-grid">
                    <div class="info-item">
                        <span class="info-label">Land:</span>
                        <span class="info-value">${event.country_name || event.country || 'Unbekannt'}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Startdatum:</span>
                        <span class="info-value">${event.start_date ? formatDateTimeDE(event.start_date) : (event.date_iso ? formatDateTimeDE(event.date_iso) : (event.date ? formatDateTimeDE(event.date) : 'Unbekannt'))}</span>
                    </div>
                    ${event.gdacs_date_added && event.source === 'gdacs' ? `
                    <div class="info-item">
                        <span class="info-label">GDACS hinzugefügt:</span>
                        <span class="info-value">${formatDateTimeDE(event.gdacs_date_added)}</span>
                    </div>
                    ` : ''}
                    ${event.magnitude ? `
                    <div class="info-item">
                        <span class="info-label">Magnitude:</span>
                        <span class="info-value">${event.magnitude}</span>
                    </div>
                    ` : ''}
                    ${event.affected_population ? `
                    <div class="info-item">
                        <span class="info-label">Betroffene:</span>
                        <span class="info-value">${event.affected_population}</span>
                    </div>
                    ` : ''}
                    <div class="info-item">
                        <span class="info-label">Quelle:</span>
                        <span class="info-value">${event.source === 'custom' ? '<img src="/Passolution-Logo-klein.png" alt="Passolution" style="height:14px; vertical-align:middle;" />' : 'GDACS'}</span>
                    </div>
                    ${event.archived ? `
                    <div class="info-item col-span-2">
                        <span class="info-label">Archiviert am:</span>
                        <span class="info-value">${event.archived_at ? formatDateTimeDE(event.archived_at) : 'Unbekannt'}</span>
                    </div>
                    <div class="info-item col-span-2">
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mt-2">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Dieses archivierte Event wird noch bis ${event.end_date ? formatDateTimeDE(new Date(new Date(event.end_date).getTime() + 365 * 24 * 60 * 60 * 1000).toISOString()) : (event.archived_at ? formatDateTimeDE(new Date(new Date(event.archived_at).getTime() + 365 * 24 * 60 * 60 * 1000).toISOString()) : 'unbekannt')} auf der Karte angezeigt.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
        `;
        
        // Wetter-Daten hinzufügen
        if (result.success && result.data.weather) {
            const weather = result.data.weather;
            detailsHtml += `
                <div class="mt-2"><i class="fa-regular fa-cloud-sun"></i> Aktuelles Wetter</div>
                <div class="sidebar-weather-display">
                    <div class="sidebar-weather-main">
                        <div class="sidebar-weather-icon">
                            <i class="fa-regular ${getWeatherIcon(weather.main)}"></i>
                        </div>
                        <div>
                            <div class="sidebar-temperature">${weather.temperature}°C</div>
                            <div class="weather-description">${weather.description}</div>
                        </div>
                    </div>
                    <div class="sidebar-weather-details">
                        <div class="sidebar-weather-item">
                            <i class="fa-regular fa-thermometer-half"></i>
                            <div>
                                <div class="sidebar-weather-label">Gefühlt</div>
                                <div class="sidebar-weather-value">${weather.feels_like}°C</div>
                            </div>
                        </div>
                        <div class="sidebar-weather-item">
                            <i class="fa-regular fa-tint"></i>
                            <div>
                                <div class="sidebar-weather-label">Luftfeuchtigkeit</div>
                                <div class="sidebar-weather-value">${weather.humidity}%</div>
                            </div>
                        </div>
                        <div class="sidebar-weather-item">
                            <i class="fa-regular fa-wind"></i>
                            <div>
                                <div class="sidebar-weather-label">Wind</div>
                                <div class="sidebar-weather-value">${weather.wind_speed} km/h</div>
                            </div>
                        </div>
                        <div class="sidebar-weather-item">
                            <i class="fa-regular fa-eye"></i>
                            <div>
                                <div class="sidebar-weather-label">Sichtweite</div>
                                <div class="sidebar-weather-value">${weather.visibility} km</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            detailsHtml += `
                <div class="weather-notice">
                    <i class="fa-regular fa-circle-info"></i>
                    <span>Wetter-Daten nicht verfügbar</span>
                </div>
            `;
        }
        
        // Zeitzonen-Daten: immer einen Abschnitt einfügen (unabhängig von Wetter/API)
        {
            detailsHtml += `
                <div class="timezone-section">
                    <h4><i class="fa-regular fa-clock"></i> Lokale Zeit</h4>
                    <div class="timezone-display">
                        <div class="time-main">
                            <div class="time-large" id="local-time-display">--:--</div>
                            <div class="date-medium" id="local-date-display">--.--.----</div>
                        </div>
                        <div class="timezone-info-inline">
                            <span class="timezone-zone-inline" id="tz-zone">Unbekannt</span>
                            <span class="timezone-abbr-inline" id="tz-abbr"></span>
                            <span class="berlin-diff-inline" id="tz-berlin-diff"></span>
                        </div>
                    </div>
                </div>
            `;
        }
        
        detailsHtml += `</div>`;
        
        sidebarContent.innerHTML = detailsHtml;

        // Live-Uhr starten (zunächst mit Fallback), anschließend ggf. mit API-Werten überschreiben
        try {
            const timeEl = document.getElementById('local-time-display');
            const dateEl = document.getElementById('local-date-display');
            const zoneEl = document.getElementById('tz-zone');
            const abbrEl = document.getElementById('tz-abbr');
            const diffEl = document.getElementById('tz-berlin-diff');

            // Prüfe geografische Lage für Zeitzone (nicht mehr automatisch Berlin für CustomEvents)
            const isInGermany = (event.latitude >= 47 && event.latitude <= 55.2 && event.longitude >= 5.5 && event.longitude <= 15.7);
            const isInSpain = (event.latitude >= 36 && event.latitude <= 43.8 && event.longitude >= -9.3 && event.longitude <= 3.3);
            
            // Setze Zeitzonen basierend auf tatsächlichen Koordinaten, nicht auf Event-Typ
            let tzName = null;
            if (isInGermany) {
                tzName = 'Europe/Berlin';
            } else if (isInSpain) {
                tzName = 'Europe/Madrid';
            }
            // Für alle anderen Standorte (inkl. CustomEvents): Lass die API die korrekte Zeitzone bestimmen
            
            let offset = tzName ? null : Math.round((event.longitude || 0) / 15);

            const formatDiff = (hours) => {
                if (hours === 0) return 'Berlin: Gleiche Zeit';
                const sign = hours > 0 ? '+' : '-';
                const abs = Math.abs(hours);
                return `Berlin: ${sign}${abs} ${abs === 1 ? 'Stunde' : 'Stunden'}`;
            };

            // Berlin Offset ermitteln (DST-aware)
            const getBerlinOffset = () => {
                const now = new Date();
                const berlin = new Date(now.toLocaleString('en-US', { timeZone: 'Europe/Berlin' }));
                const utc = new Date(now.toLocaleString('en-US', { timeZone: 'UTC' }));
                return (berlin - utc) / 3600000;
            };
            
            // Zeitunterschied zwischen zwei Zeitzonen berechnen (DST-aware)
            const getTimezoneOffset = (timezoneName) => {
                const now = new Date();
                const targetTime = new Date(now.toLocaleString('en-US', { timeZone: timezoneName }));
                const berlinTime = new Date(now.toLocaleString('en-US', { timeZone: 'Europe/Berlin' }));
                return (targetTime - berlinTime) / 3600000;
            };

            const tick = () => {
                const now = new Date();
                if (tzName) {
                    const timeFmt = new Intl.DateTimeFormat('de-DE', { timeZone: tzName, hour: '2-digit', minute: '2-digit', hour12: false });
                    const dateFmt = new Intl.DateTimeFormat('de-DE', { timeZone: tzName, day: '2-digit', month: '2-digit', year: 'numeric' });
                    if (timeEl) timeEl.textContent = timeFmt.format(now);
                    if (dateEl) dateEl.textContent = dateFmt.format(now);
                    if (zoneEl) zoneEl.textContent = tzName;
                    if (abbrEl) abbrEl.textContent = '';
                    // Korrekte Zeitdifferenz zu Berlin berechnen
                    if (diffEl) diffEl.textContent = formatDiff(getTimezoneOffset(tzName));
                } else {
                    const utcMs = now.getTime() + now.getTimezoneOffset() * 60 * 1000;
                    const local = new Date(utcMs + (offset || 0) * 3600 * 1000);
                    const hh = String(local.getHours()).padStart(2, '0');
                    const mm = String(local.getMinutes()).padStart(2, '0');
                    const dd = String(local.getDate()).padStart(2, '0');
                    const mo = String(local.getMonth() + 1).padStart(2, '0');
                    const yyyy = local.getFullYear();
                    if (timeEl) timeEl.textContent = `${hh}:${mm}`;
                    if (dateEl) dateEl.textContent = `${dd}.${mo}.${yyyy}`;
                    if (zoneEl) zoneEl.textContent = `UTC${offset >= 0 ? '+' : ''}${offset || 0}`;
                    if (abbrEl) abbrEl.textContent = '';
                    if (diffEl) diffEl.textContent = formatDiff((offset || 0) - getBerlinOffset());
                }
            };
            window.clearInterval(window.__localClockInterval);
            tick();
            window.__localClockInterval = window.setInterval(tick, 1000);

            // Wenn API-Zeitzone vorhanden, auf diese umstellen (nicht überschreiben, wenn Berlin erzwungen wurde)
            if (result.success && result.data.timezone) {
                const tz = result.data.timezone;
                if (!tzName && tz.timezone) tzName = tz.timezone;
                offset = (typeof tz.utc_offset_hours === 'number') ? tz.utc_offset_hours : offset;
                if (zoneEl && tz.timezone) zoneEl.textContent = tz.timezone;
                if (abbrEl && tz.abbreviation) abbrEl.textContent = tz.abbreviation;
                if (diffEl && typeof offset === 'number') diffEl.textContent = formatDiff(offset - getBerlinOffset());
            }
        } catch (e) { /* noop */ }
        
    } catch (error) {
        console.error('Error loading event details:', error);
        sidebarContent.innerHTML = `
            <div class="weather-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Fehler beim Laden der Event-Details</span>
            </div>
        `;
    }
}

// Beispieldaten laden
function loadSampleData() {
    currentEvents = [
        {
            id: 1,
            title: "Erdbeben in Italien",
            event_type: "earthquake",
            severity: "red",
            latitude: 41.9028,
            longitude: 12.4964,
            country: "Italien",
            date: "2025-01-17",
            magnitude: "6.2",
            affected_population: "2.3 Millionen",
            is_gdacs: true,
            icon: "fa-solid fa-house-crack",
            iconColor: "#ef4444"
        },
        {
            id: 2,
            title: "Überschwemmung in Deutschland",
            event_type: "flood",
            severity: "orange",
            latitude: 52.5200,
            longitude: 13.4050,
            country: "Deutschland",
            date: "2025-01-16",
            magnitude: null,
            affected_population: "500.000",
            is_gdacs: true,
            icon: "fa-solid fa-water",
            iconColor: "#f59e0b"
        },
        {
            id: 3,
            title: "Vulkanausbruch in Island",
            event_type: "volcano",
            severity: "red",
            latitude: 64.9631,
            longitude: -19.0208,
            country: "Island",
            date: "2025-01-15",
            magnitude: "4.8",
            affected_population: "100.000",
            is_gdacs: true,
            icon: "fa-solid fa-volcano",
            iconColor: "#ef4444"
        },
        {
            id: 4,
            title: "Sturm in Frankreich",
            event_type: "storm",
            severity: "green",
            latitude: 48.8566,
            longitude: 2.3522,
            country: "Frankreich",
            date: "2025-01-14",
            magnitude: null,
            affected_population: "1.2 Millionen",
            is_gdacs: true,
            icon: "fa-solid fa-wind",
            iconColor: "#10b981"
        },
        {
            id: 5,
            title: "Dürre in Spanien",
            event_type: "drought",
            severity: "orange",
            latitude: 40.4168,
            longitude: -3.7038,
            country: "Spanien",
            date: "2025-01-13",
            magnitude: null,
            affected_population: "3.5 Millionen",
            is_gdacs: false,
            icon: "fa-solid fa-sun",
            iconColor: "#f59e0b"
        }
    ];
    
    addMarkersToMap();
    renderEvents();
    updateStatistics();
    updateLastUpdated();
    
    console.log(`Loaded ${currentEvents.length} sample events`);
}

// Statistiken laden
async function loadStatistics() {
    try {
        const [gdacsRes, customRes] = await Promise.all([
            fetch('/api/gdacs/statistics'),
            fetch('/api/custom-events/statistics')
        ]);

        const gdacsJson = await gdacsRes.json();
        const customJson = await customRes.json();

        if (gdacsJson.success) {
            updateStatisticsFromApi(gdacsJson.data);
        } else {
            console.error('Failed to load statistics:', gdacsJson.message);
        }

        if (customJson.success) {
            // Passolution Events zum Zähler ergänzen
            const totalEl = document.getElementById('totalEvents');
            if (totalEl) {
                const current = parseInt(totalEl.textContent || '0', 10);
                totalEl.textContent = current + (customJson.data.total_events || 0);
            }
            // Einfache Zeile für eigene Events anhängen
            appendCustomEventsToList(customJson);
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

function appendCustomEventsToList(customJson) {
    const listContainer = document.getElementById('statisticsContent');
    if (!listContainer) return;
    const count = customJson?.data?.total_events ?? 0;
    const row = document.createElement('div');
    row.className = 'flex justify-between items-center';
    row.innerHTML = `<span class="text-sm text-gray-600">Passolution Events</span><span class="font-semibold text-lg">${count}</span>`;
    listContainer.appendChild(row);
}

// GDACS Events manuell aktualisieren
async function fetchGdacsEvents() {
    if (isLoading) return;
    
    isLoading = true;
    const button = document.querySelector('button[onclick="fetchGdacsEvents()"]');
    const originalText = button.innerHTML;
    
    try {
        button.innerHTML = '<div class="loading"></div>';
        button.disabled = true;
        
        const response = await fetch('/api/gdacs/fetch-events');
        const result = await response.json();
        
        if (result.success) {
            console.log('GDACS Events updated:', result.data);
            await loadDashboardData(); // Daten neu laden
            showNotification('GDACS Events erfolgreich aktualisiert!', 'success');
        } else {
            console.error('Failed to fetch GDACS events:', result.message);
            showNotification('Fehler beim Aktualisieren der GDACS Events', 'error');
        }
    } catch (error) {
        console.error('Error fetching GDACS events:', error);
        showNotification('Fehler beim Aktualisieren der GDACS Events', 'error');
    } finally {
        isLoading = false;
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

// Marker zur Karte hinzufügen
function addMarkersToMap() {
    // Bestehende Marker entfernen
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];

    // MarkerCluster-Gruppe entfernen falls vorhanden
    if (markerClusterGroup) {
        map.removeLayer(markerClusterGroup);
    }

    // Neue MarkerCluster-Gruppe erstellen mit angepassten Einstellungen
    markerClusterGroup = L.markerClusterGroup({
        maxClusterRadius: 40,  // Kleinerer Radius für engeres Clustering
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,  // Aktiviere automatisches Zoomen beim Click wieder
        disableClusteringAtZoom: 17,  // Bei höherem Zoom-Level Clustering deaktivieren
        spiderfyDistanceMultiplier: 2.5,  // Größere Distanz beim Spiderfy
        spiderLegPolylineOptions: { weight: 2, color: '#222', opacity: 0.5 },
        animateAddingMarkers: false,  // Deaktiviere Animation beim Hinzufügen
        iconCreateFunction: function(cluster) {
            const childCount = cluster.getChildCount();
            let c = ' marker-cluster-';
            let size = 40;
            if (childCount < 10) {
                c += 'small';
                size = 35;
            } else if (childCount < 100) {
                c += 'medium';
                size = 40;
            } else {
                c += 'large';
                size = 45;
            }
            return new L.DivIcon({
                html: '<div style="background: #3B4154; color: white; border-radius: 50%; width: ' + size + 'px; height: ' + size + 'px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);"><span>' + childCount + '</span></div>',
                className: 'custom-cluster-icon',
                iconSize: new L.Point(size, size)
            });
        }
    });

    currentEvents.forEach(event => {
        if (event.latitude && event.longitude) {
            // Check if event type should be shown based on filter
            let shouldShow = true;

            if (event.source === 'custom') {
                // CustomEvent - check if any of the event type IDs are enabled
                if (event.event_type_ids && event.event_type_ids.length > 0) {
                    // Many-to-many relationship: check if any of the event types are enabled
                    shouldShow = event.event_type_ids.some(typeId => window.eventTypeFilter?.[typeId] ?? true);
                } else if (event.event_type_id) {
                    // Legacy single event type
                    shouldShow = window.eventTypeFilter?.[event.event_type_id] ?? true;
                }
            } else {
                // GDACS event - try to map to our event types by code
                let eventTypeStr = (event.event_type || event.type || '').toLowerCase();

                // Find matching event type by code or name
                const matchedType = eventTypes.find(et => {
                    const code = et.code.toLowerCase();
                    const name = et.name.toLowerCase();
                    return eventTypeStr.includes(code) || eventTypeStr.includes(name) ||
                           (code === 'flood' && eventTypeStr.includes('überschwemmung')) ||
                           (code === 'hurricane' && (eventTypeStr.includes('cyclone') || eventTypeStr.includes('zyklon'))) ||
                           (code === 'wildfire' && (eventTypeStr.includes('fire') || eventTypeStr.includes('waldbrand'))) ||
                           (code === 'volcano' && eventTypeStr.includes('vulkan')) ||
                           (code === 'earthquake' && eventTypeStr.includes('erdbeben'));
                });

                if (matchedType) {
                    shouldShow = window.eventTypeFilter?.[matchedType.id] ?? true;
                }
            }

            // Only add marker if event type is enabled in filter
            if (shouldShow) {
                const marker = createCustomMarker(event);
                markers.push(marker);
                // Marker zur Cluster-Gruppe hinzufügen statt direkt zur Karte
                markerClusterGroup.addLayer(marker);
            }
        }
    });

    // MarkerCluster-Gruppe zur Karte hinzufügen
    map.addLayer(markerClusterGroup);
}

// Benutzerdefinierten Marker erstellen
function createCustomMarker(event) {
    let iconHtml;
    let iconSize = 28; // Einheitliche Größe für alle Events
    
    // Bestimme Icon und Farbe basierend auf Event-Typ
    let iconClass, markerColor;
    
    if (event.source === 'custom') {
        // CustomEvent: Verwende normalisiertes Icon und prioritätsbasierte Farbe
        iconClass = event.icon || event.marker_icon || 'fa-solid fa-location-pin';
        markerColor = event.marker_color || getPriorityColor(event.priority);
    } else {
        // GDACS-Event: Verwende prioritätsbasierte Farbe basierend auf Severity
        iconClass = event.icon || 'fa-solid fa-circle-exclamation';
        markerColor = event.iconColor || getPriorityColor(event.severity);
    }
    
    // Einheitliches Kreis-Design für alle Events
    iconHtml = `
        <div style="
            background-color: ${markerColor}; 
            border: 2px solid white; 
            border-radius: 50%; 
            width: ${iconSize}px; 
            height: ${iconSize}px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.4);
            cursor: pointer;
            transition: transform 0.2s ease;
        " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
            <i class="${iconClass}" style="color: #FFFFFF; font-size: ${iconSize * 0.5}px; text-shadow: 0 1px 2px rgba(0,0,0,0.3);"></i>
        </div>
    `;
    
    const icon = L.divIcon({
        className: 'custom-marker',
        html: iconHtml,
        iconSize: [iconSize, iconSize],
        iconAnchor: [iconSize / 2, iconSize / 2]
    });
    
    const marker = L.marker([event.latitude, event.longitude], { icon: icon });

    // Store event reference on the marker for later access
    // Stelle sicher, dass die Original-Event-ID erhalten bleibt
    marker.eventData = {
        ...event,
        original_id: event.original_event_id || event.id
    };

    // Popup erstellen
    const popupContent = createPopupContent(event);
    marker.bindPopup(popupContent);

    // Click-Event hinzufügen
    marker.on('click', function() {
        // Track click for custom events - verwende Original-ID falls vorhanden
        if (event.source === 'custom') {
            const trackId = event.original_event_id || event.id;
            if (trackId) {
                trackEventClick(trackId, 'map_marker');
            }
        }

        // Sidebar schließen
        const eventSidebar = document.getElementById('eventSidebar');
        if (eventSidebar && eventSidebar.classList.contains('open')) {
            eventSidebar.classList.remove('open');
        }
    });

    return marker;
}

// Popup-Inhalt erstellen
function createPopupContent(event) {
	const sourceValue = event.source === 'custom'
		? '<img src=\"/Passolution-Logo-klein.png\" alt=\"Passolution\" style=\"height:14px; vertical-align:middle;\" />'
		: 'GDACS';

	// Archivierungsstatus-Badge
	const archivedBadge = event.archived
		? '<span class="badge bg-warning text-white ml-2" style="font-size: 11px; padding: 2px 6px; border-radius: 3px;">Archiviert</span>'
		: '';

	// Prioritäts-Farbe bestimmen
	const priorityColor = getPriorityColor(event.priority || event.severity);
	const priorityLabel = mapPriority(event.priority || event.severity);

	// Event-Typen formatieren
	let eventTypesDisplay = '';
	if (event.event_types && event.event_types.length > 0) {
		eventTypesDisplay = event.event_types.join(', ');
	} else {
		eventTypesDisplay = mapEventType(event.event_type, event.event_type_name);
	}

	// Escape country_name for safe usage in onclick handler
	const countryNameEscaped = (event.country_name || event.country || '').replace(/'/g, "\\'").replace(/"/g, '\\"');

	return `
		<div class=\"marker-popup\">
			<h3>${event.title}${archivedBadge}</h3>
			<div class=\"info-row\">
				<span class=\"info-label\">Typ${event.event_types && event.event_types.length > 1 ? 'en' : ''}:</span>
				<span class=\"info-value\">${eventTypesDisplay}</span>
			</div>
			<div class=\"info-row\">
				<span class=\"info-label\">Risikostufe:</span>
				<span class=\"info-value\" style=\"color: ${priorityColor}; font-weight: 600;\">${priorityLabel}</span>
			</div>
			<div class=\"info-row mt-2\">
				<span class=\"info-label\">Quelle:</span>
				<span class=\"info-value\">${sourceValue}</span>
			</div>
			<div class=\"popup-actions\">
				<button onclick=\"handleDetailsClick('${event.original_event_id || event.id}', ${event.source === 'custom' ? 'true' : 'false'}, '${countryNameEscaped}')\" class=\"details-btn\">
					<i class=\"fa-solid fa-circle-info\"></i>
					Details anzeigen
				</button>
			</div>
		</div>
	`;
}

// Wetter- und Zeitzonen-Daten für Event laden
async function loadWeatherAndTimezoneData(latitude, longitude) {
    try {
        const response = await fetch('/api/gdacs/event-details', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ latitude, longitude })
        });
        
        const result = await response.json();
        
        if (result.success) {
            updateWeatherDisplay(latitude, longitude, result.data);
        } else {
            console.error('Failed to load weather data:', result.message);
            // Zeige Fehlermeldung an
            const weatherContainer = document.getElementById(`weather-${latitude}-${longitude}`);
            if (weatherContainer) {
                weatherContainer.innerHTML = `
                    <div class="weather-error">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Wetter-Daten nicht verfügbar</span>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading weather data:', error);
        // Zeige Fehlermeldung an
        const weatherContainer = document.getElementById(`weather-${latitude}-${longitude}`);
        if (weatherContainer) {
            weatherContainer.innerHTML = `
                <div class="weather-error">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Fehler beim Laden der Wetter-Daten</span>
                </div>
            `;
        }
    }
}

// Wetter-Anzeige aktualisieren
function updateWeatherDisplay(latitude, longitude, data) {
    const weatherContainer = document.getElementById(`weather-${latitude}-${longitude}`);
    if (!weatherContainer) return;
    
    const weather = data.weather;
    const timezone = data.timezone;
    
    let weatherHtml = '';
    
    if (weather) {
        weatherHtml += `
            <div class="weather-section">
                <h4><i class="fa-regular fa-cloud-sun"></i> Aktuelles Wetter</h4>
                <div class="weather-display">
                    <div class="weather-main">
                        <div class="weather-icon">
                            <i class="fa-regular ${getWeatherIcon(weather.main)}"></i>
                        </div>
                        <div class="weather-primary">
                            <div class="temperature-large">${weather.temperature}°C</div>
                            <div class="weather-description">${weather.description}</div>
                        </div>
                        <div class="weather-feels-like">
                            <span class="feels-label">Gefühlt</span>
                            <span class="feels-value">${weather.feels_like}°C</span>
                        </div>
                    </div>
                    <div class="weather-details">
                        <div class="weather-detail-item">
                            <div class="detail-icon">
                                <i class="fa-regular fa-tint"></i>
                            </div>
                            <div class="detail-info">
                                <span class="detail-label">Luftfeuchtigkeit</span>
                                <span class="detail-value">${weather.humidity}%</span>
                            </div>
                        </div>
                        <div class="weather-detail-item">
                            <div class="detail-icon">
                                <i class="fa-regular fa-wind"></i>
                            </div>
                            <div class="detail-info">
                                <span class="detail-label">Wind</span>
                                <span class="detail-value">${weather.wind_speed} km/h</span>
                            </div>
                        </div>
                        <div class="weather-detail-item">
                            <div class="detail-icon">
                                <i class="fa-regular fa-eye"></i>
                            </div>
                            <div class="detail-info">
                                <span class="detail-label">Sichtweite</span>
                                <span class="detail-value">${weather.visibility} km</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else {
        weatherHtml += `
            <div class="weather-section">
                <h4><i class="fa-regular fa-cloud-sun"></i> Wetter</h4>
                <div class="weather-notice">
                    <i class="fa-regular fa-circle-info"></i>
                    <span>Wetter-Daten nicht verfügbar (OpenWeatherMap API nicht konfiguriert)</span>
                </div>
            </div>
        `;
    }
    
    if (timezone) {
        weatherHtml += `
            <div class="timezone-section">
                <h4><i class="fa-regular fa-clock"></i> Lokale Zeit</h4>
                <div class="timezone-display">
                    <div class="time-main">
                        <div class="time-large">${timezone.local_time}</div>
                        <div class="date-medium">${timezone.local_date}</div>
                    </div>
                    <div class="timezone-details">
                        <div class="timezone-info">
                            <span class="timezone-zone">${timezone.timezone}</span>
                            <span class="timezone-abbr">${timezone.abbreviation}</span>
                        </div>
                        <div class="berlin-diff">
                            <span class="diff-label">Berlin:</span>
                            <span class="diff-value">${timezone.time_diff_to_berlin}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    weatherContainer.innerHTML = weatherHtml;
}

// Statistiken aktualisieren
function updateStatistics() {
    const totalEvents = currentEvents.length;
    const activeEvents = currentEvents.filter(e => e.severity !== 'green').length;
    const lastWeekEvents = Math.floor(Math.random() * 10) + 5; // Simuliert
    const highRiskEvents = currentEvents.filter(e => e.severity === 'red' || e.severity === 'orange').length;
    const gdacsEvents = currentEvents.filter(e => e.is_gdacs).length;

    // Berechne die Anzahl eindeutiger Events (gleiche Logik wie in renderEvents)
    const uniqueEvents = new Set();
    if (window.allEvents) {
        window.allEvents.forEach(event => {
            if (event.source === 'custom' && event.original_event_id) {
                // Verwende die original_event_id für Multi-Country Events
                const mainEventId = event.original_event_id.toString().split('_')[0];
                uniqueEvents.add(mainEventId);
            } else if (!event.original_event_id) {
                // Normales Event ohne mehrere Länder
                uniqueEvents.add(event.id);
            }
        });
    }
    const allEventsCount = uniqueEvents.size > 0 ? uniqueEvents.size : currentEvents.length;

    // Alle Elemente mit den gleichen IDs aktualisieren
    document.querySelectorAll('#totalEvents').forEach(el => el.textContent = totalEvents);
    document.querySelectorAll('#activeEvents').forEach(el => el.textContent = activeEvents);
    document.querySelectorAll('#lastWeekEvents').forEach(el => el.textContent = lastWeekEvents);
    document.querySelectorAll('#highRiskEvents').forEach(el => el.textContent = highRiskEvents);
    document.querySelectorAll('#gdacsEvents').forEach(el => el.textContent = gdacsEvents);
    document.querySelectorAll('#currentEventsCount').forEach(el => el.textContent = allEventsCount);
}

function toggleProviderFilter(key, btn) {
    if (!window.providerFilter) window.providerFilter = { gdacs: true, custom: true };
    window.providerFilter[key] = !window.providerFilter[key];
    // Button-Style toggeln
    if (window.providerFilter[key]) {
        btn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black';
    } else {
        btn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-white text-gray-700 border-gray-300 hover:bg-gray-50';
    }
    // Liste & Statistiken neu berechnen
    // Check if any filters are active
    checkActiveFilters();

    if (typeof loadDashboardData === 'function') {
        // Nur die Darstellung neu aufbauen, ohne erneut zu laden:
        try {
            const eventsList = document.getElementById('eventsList');
            if (eventsList && Array.isArray(currentEvents)) {
                // Re-run render pipeline by reloading from cached allEvents if available
                // Fallback: call loadDashboardData to rebuild
                loadDashboardData();
            }
        } catch (e) { loadDashboardData(); }
    }
}

// Risk Level Filter Toggle
function toggleRiskFilter(key, btn) {
    if (!window.riskFilter) window.riskFilter = { info: true, green: true, orange: true, red: true };
    window.riskFilter[key] = !window.riskFilter[key];

    // Check if any filters are active
    checkActiveFilters();
    
    // Button-Style toggeln mit prioritätsbasierten Farben
    if (window.riskFilter[key]) {
        const colorStyle = key === 'info' ? 'background-color: #0066cc; border-color: #0066cc;' :
                          key === 'green' ? 'background-color: #0fb67f; border-color: #0fb67f;' :
                          key === 'orange' ? 'background-color: #e6a50a; border-color: #e6a50a;' :
                          'background-color: #ff0000; border-color: #ff0000;';
        btn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors text-white';
        btn.style.cssText = colorStyle;
    } else {
        btn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-white text-gray-700 border-gray-300 hover:bg-gray-50';
        btn.style.cssText = '';
    }
    
    // "Alle"/"Keine" Button aktualisieren
    const toggleButton = document.getElementById('toggleAllRiskLevels');
    if (toggleButton) {
        const allActive = window.riskFilter.info && window.riskFilter.green && window.riskFilter.orange && window.riskFilter.red;
        const allInactive = !window.riskFilter.info && !window.riskFilter.green && !window.riskFilter.orange && !window.riskFilter.red;
        
        if (allActive) {
            toggleButton.textContent = 'Alle ausblenden';
            toggleButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black font-medium';
        } else if (allInactive) {
            toggleButton.textContent = 'Alle einblenden';
            toggleButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-200 text-gray-700 border-gray-300 font-medium';
        } else {
            // Gemischter Zustand
            toggleButton.textContent = 'Alle einblenden';
            toggleButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-200 text-gray-700 border-gray-300 font-medium';
        }
    }
    
    // Liste & Statistiken neu berechnen und Karte aktualisieren
    if (typeof loadDashboardData === 'function') {
        try {
            const eventsList = document.getElementById('eventsList');
            if (eventsList && Array.isArray(currentEvents)) {
                loadDashboardData();
            }
        } catch (e) { loadDashboardData(); }
    }

    // Karte neu zeichnen mit aktualisierten Filtern
    if (typeof addMarkersToMap === 'function') {
        addMarkersToMap();
    }
}

// Load EventTypes from API
async function loadEventTypes() {
    try {
        const response = await fetch('/api/custom-events/event-types');
        const result = await response.json();

        if (result.success) {
            eventTypes = result.data;
            initializeEventTypeFilters();
        } else {
            console.error('Failed to load event types:', result.message);
        }
    } catch (error) {
        console.error('Error loading event types:', error);
    }
}

// Initialize Event Type Filters
function initializeEventTypeFilters() {
    const container = document.getElementById('eventTypeButtons');
    if (!container) return;

    // Initialize the filter object with all event types enabled
    window.eventTypeFilter = {};
    eventTypes.forEach(eventType => {
        window.eventTypeFilter[eventType.id] = true;
    });

    // Clear existing buttons except toggle all
    container.innerHTML = '';

    // Re-add toggle all button
    const toggleAllBtn = document.createElement('button');
    toggleAllBtn.type = 'button';
    toggleAllBtn.id = 'toggleAllEventTypes';
    toggleAllBtn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black col-span-2';
    toggleAllBtn.textContent = 'Alle ausblenden';
    toggleAllBtn.onclick = toggleAllEventTypes;
    container.appendChild(toggleAllBtn);

    // Add buttons for each event type
    eventTypes.forEach(eventType => {
        const button = document.createElement('button');
        button.type = 'button';
        button.id = `event-type-${eventType.id}`;
        button.className = 'px-2 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black flex flex-col items-center justify-center gap-1 overflow-hidden';
        button.innerHTML = `
            <i class="fa ${eventType.icon} text-sm font-light"></i>
            <span class="truncate w-full text-center px-1">${eventType.name}</span>
        `;
        button.title = eventType.name; // Tooltip für vollen Text
        button.onclick = () => toggleEventTypeFilter(eventType.id, button);
        container.appendChild(button);
    });
}

// Event Type Filter Toggle
function toggleEventTypeFilter(eventTypeId, btn) {
    window.eventTypeFilter[eventTypeId] = !window.eventTypeFilter[eventTypeId];

    // Toggle button style
    if (window.eventTypeFilter[eventTypeId]) {
        btn.className = 'px-2 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black flex flex-col items-center justify-center gap-1 overflow-hidden';
    } else {
        btn.className = 'px-2 py-2 text-xs rounded-lg border transition-colors bg-white text-gray-700 border-gray-300 hover:bg-gray-50 flex flex-col items-center justify-center gap-1 overflow-hidden';
    }

    // Check if any filters are active
    checkActiveFilters();

    // Update "All show/hide" button
    const toggleButton = document.getElementById('toggleAllEventTypes');
    if (toggleButton) {
        const allActive = eventTypes.every(et => window.eventTypeFilter[et.id]);
        const allInactive = eventTypes.every(et => !window.eventTypeFilter[et.id]);

        if (allActive) {
            toggleButton.textContent = 'Alle ausblenden';
            toggleButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black col-span-2';
        } else {
            toggleButton.textContent = 'Alle einblenden';
            toggleButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-200 text-gray-700 border-gray-300 col-span-2';
        }
    }
    
    // Liste & Statistiken neu berechnen und Karte aktualisieren
    if (typeof loadDashboardData === 'function') {
        try {
            const eventsList = document.getElementById('eventsList');
            if (eventsList && Array.isArray(currentEvents)) {
                loadDashboardData();
            }
        } catch (e) { loadDashboardData(); }
    }

    // Karte neu zeichnen mit aktualisierten Filtern
    if (typeof addMarkersToMap === 'function') {
        addMarkersToMap();
    }
}

// Time Period Filter Toggle
function toggleTimePeriodFilter(key, btn) {
    if (!window.timePeriodFilter) window.timePeriodFilter = 'all';
    
    // Special handling for "Alle" button - it should toggle
    if (key === 'all') {
        const currentText = btn.textContent;
        const isCurrentlyActive = window.timePeriodFilter === 'all';
        
        // Get other period buttons
        const button7days = document.getElementById('period-7days');
        const button30days = document.getElementById('period-30days');
        
        if (isCurrentlyActive && currentText === 'Alle ausblenden') {
            // Switch to "Alle einblenden" (deactivate all)
            btn.textContent = 'Alle einblenden';
            btn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-200 text-gray-700 border-gray-300';
            window.timePeriodFilter = 'none';
            
            // Deactivate other period buttons (white background when hidden)
            if (button7days) {
                button7days.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-white text-gray-700 border-gray-300';
            }
            if (button30days) {
                button30days.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-white text-gray-700 border-gray-300';
            }
        } else {
            // Switch to "Alle ausblenden" (activate all)
            btn.textContent = 'Alle ausblenden';
            btn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black';
            window.timePeriodFilter = 'all';
            
            // Activate other period buttons (same style as EventTypes when active)
            if (button7days) {
                button7days.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black';
            }
            if (button30days) {
                button30days.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black';
            }
        }
    } else {
        // Regular period selection (7days, 30days)
        // Reset all period buttons to inactive state
        const allPeriodButtons = ['period-7days', 'period-30days'];
        allPeriodButtons.forEach(id => {
            const button = document.getElementById(id);
            if (button) {
                button.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-200 text-gray-700 border-gray-300';
            }
        });
        
        // Reset "Alle" button and set correct text
        const allButton = document.getElementById('period-all');
        if (allButton) {
            allButton.textContent = 'Alle einblenden';
            allButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-200 text-gray-700 border-gray-300';
        }
        
        // Set the selected period as active
        window.timePeriodFilter = key;
        btn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black';
    }

    // Check if any filters are active
    checkActiveFilters();

    // Liste & Statistiken neu berechnen und Karte aktualisieren
    if (typeof loadDashboardData === 'function') {
        try {
            const eventsList = document.getElementById('eventsList');
            if (eventsList && Array.isArray(currentEvents)) {
                loadDashboardData();
            }
        } catch (e) { loadDashboardData(); }
    }

    // Karte neu zeichnen mit aktualisierten Filtern
    if (typeof addMarkersToMap === 'function') {
        addMarkersToMap();
    }
}

// Risk Level "Alle"/"Keine" Toggle
function toggleAllRiskLevels() {
    if (!window.riskFilter) window.riskFilter = { info: true, green: true, orange: true, red: true };

    const toggleButton = document.getElementById('toggleAllRiskLevels');
    const riskButtons = ['risk-info', 'risk-green', 'risk-orange', 'risk-red'];

    // Prüfen ob alle Risikostufen aktiv sind
    const allActive = window.riskFilter.info && window.riskFilter.green && window.riskFilter.orange && window.riskFilter.red;
    
    if (allActive) {
        // Alle deaktivieren
        toggleButton.textContent = 'Alle einblenden';
        toggleButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-200 text-gray-700 border-gray-300 font-medium';
        
        // Alle Risikostufen deaktivieren
        window.riskFilter = { info: false, green: false, orange: false, red: false };
        
        // Button-Styles auf inaktiv setzen
        riskButtons.forEach(id => {
            const button = document.getElementById(id);
            if (button) {
                button.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-white text-gray-700 border-gray-300 hover:bg-gray-50';
                button.style.cssText = '';  // Inline-Styles entfernen für weißen Hintergrund
            }
        });
    } else {
        // Alle aktivieren
        toggleButton.textContent = 'Alle ausblenden';
        toggleButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black font-medium';
        
        // Alle Risikostufen aktivieren
        window.riskFilter = { info: true, green: true, orange: true, red: true };

        // Button-Styles auf aktiv setzen mit prioritätsbasierten Farben
        const colorStyles = {
            'risk-info': 'background-color: #0066cc; border-color: #0066cc;',
            'risk-green': 'background-color: #0fb67f; border-color: #0fb67f;',
            'risk-orange': 'background-color: #e6a50a; border-color: #e6a50a;',
            'risk-red': 'background-color: #ff0000; border-color: #ff0000;'
        };
        
        riskButtons.forEach(id => {
            const button = document.getElementById(id);
            if (button) {
                const colorStyle = colorStyles[id];
                button.className = 'px-3 py-2 text-xs rounded-lg border transition-colors text-white';
                button.style.cssText = colorStyle;
            }
        });
    }

    // Check if any filters are active
    checkActiveFilters();

    // Liste & Statistiken neu berechnen und Karte aktualisieren
    if (typeof loadDashboardData === 'function') {
        try {
            const eventsList = document.getElementById('eventsList');
            if (eventsList && Array.isArray(currentEvents)) {
                loadDashboardData();
            }
        } catch (e) { loadDashboardData(); }
    }

    // Karte neu zeichnen mit aktualisierten Filtern
    if (typeof addMarkersToMap === 'function') {
        addMarkersToMap();
    }
}

// Event Type "Alle einblenden"/"Alle ausblenden" Toggle
function toggleAllEventTypes() {
    const toggleButton = document.getElementById('toggleAllEventTypes');

    // Check if all event types are active
    const allActive = eventTypes.every(et => window.eventTypeFilter[et.id]);

    if (allActive) {
        // Deactivate all
        toggleButton.textContent = 'Alle einblenden';
        toggleButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-200 text-gray-700 border-gray-300 col-span-2';

        eventTypes.forEach(eventType => {
            window.eventTypeFilter[eventType.id] = false;
            const btn = document.getElementById(`event-type-${eventType.id}`);
            if (btn) {
                btn.className = 'px-2 py-2 text-xs rounded-lg border transition-colors bg-white text-gray-700 border-gray-300 hover:bg-gray-50 flex flex-col items-center justify-center gap-1 overflow-hidden';
            }
        });
    } else {
        // Activate all
        toggleButton.textContent = 'Alle ausblenden';
        toggleButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black col-span-2';

        eventTypes.forEach(eventType => {
            window.eventTypeFilter[eventType.id] = true;
            const btn = document.getElementById(`event-type-${eventType.id}`);
            if (btn) {
                btn.className = 'px-2 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black flex flex-col items-center justify-center gap-1 overflow-hidden';
            }
        });
    }

    // Check if any filters are active
    checkActiveFilters();

    // Liste & Statistiken neu berechnen und Karte aktualisieren
    if (typeof loadDashboardData === 'function') {
        try {
            const eventsList = document.getElementById('eventsList');
            if (eventsList && Array.isArray(currentEvents)) {
                loadDashboardData();
            }
        } catch (e) { loadDashboardData(); }
    }

    // Karte neu zeichnen mit aktualisierten Filtern
    if (typeof addMarkersToMap === 'function') {
        addMarkersToMap();
    }
}

// Statistiken von API aktualisieren
function updateStatisticsFromApi(stats) {
    // Alle Elemente mit den gleichen IDs aktualisieren
    document.querySelectorAll('#totalEvents').forEach(el => el.textContent = stats.total_events);
    document.querySelectorAll('#activeEvents').forEach(el => el.textContent = stats.active_events);
    document.querySelectorAll('#lastWeekEvents').forEach(el => el.textContent = stats.last_week_events);
    document.querySelectorAll('#highRiskEvents').forEach(el => el.textContent = stats.high_risk_events);
    document.querySelectorAll('#gdacsEvents').forEach(el => el.textContent = stats.gdacs_events);
}

// Events in der Sidebar rendern
function renderEvents() {
    const eventsList = document.getElementById('eventsList');
    const futureEventsList = document.getElementById('futureEventsList');
    const futureEventsSection = document.getElementById('futureEventsSection');

    eventsList.innerHTML = '';
    futureEventsList.innerHTML = '';

    // Gruppiere Events nach ihrer Original-ID oder ihrer eigenen ID
    const uniqueEvents = new Map();

    currentEvents.forEach(event => {
        if (event.source === 'custom' && event.original_event_id) {
            // Dies ist ein Länder-spezifischer Marker - füge ihn zur Haupt-Event-Gruppe hinzu
            const mainEventId = event.original_event_id.toString().split('_')[0];
            if (!uniqueEvents.has(mainEventId)) {
                // Erstelle ein zusammengefasstes Event für die Anzeige
                const mainEvent = {...event};
                mainEvent.id = mainEventId;
                mainEvent.original_event_id = mainEventId; // Behalte die Original-ID für die Marker-Suche
                mainEvent.countries_list = [event.country_name || event.country];
                // Bereinige den Titel - entferne Länder-Suffixe
                if (mainEvent.title && mainEvent.title.includes(' - ')) {
                    mainEvent.title = mainEvent.title.split(' - ')[0];
                }
                uniqueEvents.set(mainEventId, mainEvent);
            } else {
                // Füge das Land zur Liste hinzu
                const mainEvent = uniqueEvents.get(mainEventId);
                if (!mainEvent.countries_list.includes(event.country_name || event.country)) {
                    mainEvent.countries_list.push(event.country_name || event.country);
                }
            }
        } else if (!event.original_event_id) {
            // Normales Event ohne mehrere Länder
            uniqueEvents.set(event.id, event);
        }
    });

    // Trenne zukünftige von aktuellen/vergangenen Events
    const now = new Date();
    now.setHours(0, 0, 0, 0); // Auf Mitternacht setzen für Datumsvergleich
    const futureEvents = [];
    const currentPastEvents = [];

    uniqueEvents.forEach(event => {
        // Wenn es mehrere Länder gibt, zeige sie kommagetrennt
        if (event.countries_list && event.countries_list.length > 0) {
            event.country = event.countries_list.join(', ');
        }

        // Bestimme das Event-Datum
        const eventDate = event.start_date || event.date_iso || event.date || event.event_date;
        if (eventDate) {
            const eventDateObj = new Date(eventDate);
            eventDateObj.setHours(0, 0, 0, 0);

            if (eventDateObj > now) {
                futureEvents.push(event);
            } else {
                currentPastEvents.push(event);
            }
        } else {
            // Ohne Datum zu aktuellen Events hinzufügen
            currentPastEvents.push(event);
        }
    });

    // Sortiere zukünftige Events (älteste zuerst = nächste zuerst)
    futureEvents.sort((a, b) => {
        const dateA = new Date(a.start_date || a.date_iso || a.date || a.event_date);
        const dateB = new Date(b.start_date || b.date_iso || b.date || b.event_date);
        return dateA - dateB;
    });

    // Rendere zukünftige Events
    if (futureEvents.length > 0) {
        futureEventsSection.style.display = 'block';
        document.getElementById('futureEventsCount').textContent = futureEvents.length;
        futureEvents.forEach(event => {
            const eventElement = createEventElement(event);
            futureEventsList.appendChild(eventElement);
        });
    } else {
        futureEventsSection.style.display = 'none';
    }

    // Rendere aktuelle/vergangene Events (bereits nach Datum sortiert durch currentEvents)
    document.getElementById('currentPastEventsCount').textContent = currentPastEvents.length;
    currentPastEvents.forEach(event => {
        const eventElement = createEventElement(event);
        eventsList.appendChild(eventElement);
    });
}

// Event-Element erstellen
function createEventElement(event) {
    const div = document.createElement('div');
    // Prioritäts-basierte Farben - exakte hex-Werte verwenden
    const sevMap = {
        info: {
            label: 'INFORMATION',
            border: 'border-l-4',
            borderColor: '#0066cc',
            dot: 'bg-blue-500',
            text: 'text-blue-600'
        },
        low: {
            label: 'NIEDRIG',
            border: 'border-l-4',
            borderColor: '#0fad78',
            dot: 'bg-green-500',
            text: 'text-green-600'
        },
        medium: {
            label: 'MITTEL',
            border: 'border-l-4',
            borderColor: '#e6a50a',
            dot: 'bg-orange-500',
            text: 'text-orange-600'
        },
        high: {
            label: 'HOCH',
            border: 'border-l-4',
            borderColor: '#ff0000',
            dot: 'bg-red-500',
            text: 'text-red-600'
        },
    };
    const pickSeverity = (e) => {
        if (e?.source === 'custom') {
            const key = (e.priority || e.severity || '').toString().toLowerCase();
            return sevMap[key] || sevMap.low;
        }
        const s = (e?.severity || '').toLowerCase();
        if (s === 'green') return sevMap.low;
        if (s === 'orange') return sevMap.medium;
        if (s === 'red') return sevMap.high;
        return sevMap.low;
    };
    const sev = pickSeverity(event);
    const severityClass = sev.border;
    const severityBorderColor = sev.borderColor;
    const severityColor = sev.dot;
    const severityTextClass = sev.text;
    const rightHtml = event.source === 'custom'
        ? '<img src="/Passolution-Logo-klein.png" alt="Passolution" style="height:12px;vertical-align:middle;" />'
        : '<span class="text-xs text-gray-500 uppercase">GDACS</span>';
    const displayDate = event.source === 'custom'
        ? (event.start_date ? new Date(event.start_date).toLocaleDateString('de-DE') : (event.date ? new Date(event.date).toLocaleDateString('de-DE') : ''))
        : (event.start_date ? new Date(event.start_date).toLocaleDateString('de-DE') : (event.date_iso ? new Date(event.date_iso).toLocaleDateString('de-DE') : (event.date ? new Date(event.date).toLocaleDateString('de-DE') : '')));

    // Wenn es ein zusammengefasstes Event ist (aus mehreren Ländern), zeige den Original-Titel
    // Ansonsten sammle alle Länder für die Anzeige
    let countryDisplay = event.country || event.country_name || 'Kein Land zugewiesen';
    if (event.original_event_id && event.countries_summary) {
        // Dies ist ein individueller Marker für ein Multi-Country-Event
        countryDisplay = event.countries_summary;
    } else if (!event.original_event_id && window.eventById && event.id) {
        // Dies ist das Haupt-Event in der Liste - sammle alle Länder
        const allCountriesForEvent = [];
        // Durchsuche alle Events nach solchen mit der gleichen original_event_id
        Object.values(window.eventById).forEach(e => {
            if (e.original_event_id == event.id && e.country_name) {
                allCountriesForEvent.push(e.country_name);
            }
        });
        if (allCountriesForEvent.length > 0) {
            countryDisplay = [...new Set(allCountriesForEvent)].join(', ');
        }
    }
    
    div.className = `bg-gray-50 rounded-lg p-3 border-l-4 cursor-pointer hover:bg-gray-100 transition-colors`;
    div.style.borderLeftColor = severityBorderColor;
    div.innerHTML = `
        <div class="flex items-start space-x-2">
            <div class="w-2 h-2 rounded-full mt-2 ${severityColor}"></div>
            <div class="flex-1 min-w-0">
                <div class="flex items-start gap-2">
                    <div class="flex-1 min-w-0" style="max-width: 66.666%;">
                        <span class="text-xs font-medium uppercase text-gray-800 break-words">${countryDisplay}</span>
                    </div>
                    <div class="flex-shrink-0 flex items-start justify-end" style="width: 33.333%; padding-top: 5px;">${rightHtml}</div>
                </div>
                <p class="text-[11px] ${severityTextClass} mt-0.5">${sev.label}</p>
                <p class="text-sm font-medium text-gray-800 mt-1">${event.title}</p>
                <p class="text-xs text-gray-600 mt-1">
                    ${event.event_types && event.event_types.length > 0
                        ? event.event_types.join(', ')
                        : mapEventType(event.event_type, event.event_type_name)
                    } • ${displayDate || (event.date || 'Unbekannt')}
                    ${event.magnitude ? ` • Magnitude: ${event.magnitude}` : ''}
                </p>
                ${event.affected_population ? `<p class="text-xs text-gray-500 mt-1">${event.affected_population}</p>` : ''}
            </div>
        </div>
    `;
    
    // Klick-Event hinzufügen
    div.addEventListener('click', () => {
        // Track click for custom events only
        if (event.source === 'custom' && event.id) {
            trackEventClick(event.id, 'list');
        }

        // Wenn kein Land/keine Koordinaten zugewiesen: Direkt Details anzeigen
        if (!event.latitude || !event.longitude) {
            console.log('Event ohne Koordinaten - öffne direkt Details:', event.title);
            handleDetailsClick(event.original_event_id || event.id, event.source === 'custom');
            return;
        }

        // Schließe zuerst die Sidebar, falls sie offen ist
        const eventSidebar = document.getElementById('eventSidebar');
        if (eventSidebar && eventSidebar.classList.contains('open')) {
            eventSidebar.classList.remove('open');
        }

        // Marker öffnen - suche nach Event ID statt Koordinaten
        let targetMarker = null;

        // Finde den richtigen Marker
        console.log('Suche Marker für Event:', event.title, 'ID:', event.id, 'Original-ID:', event.original_event_id);

        // Array für alle passenden Marker
        const matchingMarkers = [];

        markers.forEach(marker => {
            // Prüfe ob der Marker zu diesem Event gehört
            if (marker.eventData) {
                const markerId = marker.eventData.original_id || marker.eventData.original_event_id || marker.eventData.id;
                const eventId = event.original_event_id || event.id;

                // Debug-Ausgabe für jeden Marker
                if (marker.eventData.title && (marker.eventData.title.includes('Hundetaxe') || marker.eventData.title.includes('Chikungunya'))) {
                    console.log('Prüfe Marker:', marker.eventData.title,
                        'Marker-ID:', marker.eventData.id,
                        'Original-ID:', marker.eventData.original_event_id,
                        'Original_id:', marker.eventData.original_id,
                        'Vergleiche mit Event-ID:', eventId);
                }

                // Prüfe verschiedene ID-Kombinationen
                if (markerId == eventId || // == statt === für String/Number-Vergleich
                    marker.eventData.id == event.id ||
                    (marker.eventData.original_event_id && marker.eventData.original_event_id == eventId) ||
                    (marker.eventData.original_id && marker.eventData.original_id == eventId)) {
                    console.log('MATCH gefunden für:', marker.eventData.title);
                    matchingMarkers.push(marker);
                }
            }
        });

        // Wenn mehrere Marker gefunden wurden, nimm den mit dem passenden Titel
        if (matchingMarkers.length > 0) {
            // Versuche den Marker mit dem exakt passenden Titel zu finden
            targetMarker = matchingMarkers.find(m => m.eventData.title === event.title) || matchingMarkers[0];
            console.log('Gewählter Marker:', targetMarker.eventData?.title);
        }

        // Fallback: Falls kein Marker über ID gefunden, versuche Koordinaten-Matching
        if (!targetMarker) {
            markers.forEach(marker => {
                if (Math.abs(marker.getLatLng().lat - event.latitude) < 0.0001 &&
                    Math.abs(marker.getLatLng().lng - event.longitude) < 0.0001) {
                    targetMarker = marker;
                }
            });
        }

        if (targetMarker && markerClusterGroup) {
            console.log('Ziel-Marker gefunden:', targetMarker.eventData?.title || 'Unbekannt');

            // Verwende die eingebaute zoomToShowLayer Methode
            // Diese zoomt automatisch und löst Cluster auf wenn nötig
            markerClusterGroup.zoomToShowLayer(targetMarker, function() {
                console.log('zoomToShowLayer Callback aufgerufen');

                // Kleine Verzögerung für Animation
                setTimeout(() => {
                    // Prüfe ob der Marker in einem Cluster ist
                    const cluster = markerClusterGroup.getVisibleParent(targetMarker);
                    console.log('Visible parent:', cluster);

                    if (cluster && cluster !== targetMarker) {
                        console.log('Marker ist in einem Cluster - versuche Spiderfy');
                        // Marker ist immer noch in einem Cluster
                        // Simuliere einen Klick auf den Cluster
                        cluster.fire('click');

                        // Warte auf Spiderfy und öffne dann Popup
                        setTimeout(() => {
                            targetMarker.openPopup();
                        }, 500);
                    } else {
                        console.log('Marker ist direkt sichtbar - öffne Popup');
                        // Marker ist direkt sichtbar
                        targetMarker.openPopup();
                    }
                }, 300);
            });
        } else if (targetMarker) {
            // Kein Clustering aktiv
            map.setView([event.latitude, event.longitude], 12, { animate: true });
            setTimeout(() => {
                targetMarker.openPopup();
            }, 300);
        } else {
            // Kein Marker gefunden, nur zur Position zoomen
            map.setView([event.latitude, event.longitude], 12, { animate: true });
        }
    });
    
    return div;
}

// Kontinente rendern
function renderContinents() {
    const continentsList = document.getElementById('continentsList');
    if (!continentsList) {
        return;
    }

    continentsList.innerHTML = '';

    if (continents.length === 0) {
        return;
    }

    // Globale Variable für ausgewählte Kontinente initialisieren (falls noch nicht gesetzt)
    if (!window.selectedContinents) {
        // Initialisiere mit allen Kontinent-IDs aus der Datenbank
        window.selectedContinents = new Set(continents.map(c => c.id));
    }

    // "Alle einblenden/ausblenden" Schaltfläche hinzufügen
    const toggleAllButton = document.createElement('button');
    toggleAllButton.id = 'toggleAllContinents';
    toggleAllButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black';
    toggleAllButton.textContent = 'Alle ausblenden';
    toggleAllButton.onclick = toggleAllContinents;
    continentsList.appendChild(toggleAllButton);

    continents.forEach(continent => {
        const button = document.createElement('button');
        // Alle Kontinente sind initial aktiviert
        button.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black';
        button.textContent = continent.name;
        button.onclick = () => selectContinent(continent.id);
        continentsList.appendChild(button);
    });
}

// Alle Kontinente ein-/ausblenden
function toggleAllContinents() {
    const toggleButton = document.getElementById('toggleAllContinents');
    const continentButtons = document.querySelectorAll('#continentsList button:not(#toggleAllContinents)');
    const isAllActive = toggleButton.textContent === 'Alle ausblenden';
    
    if (isAllActive) {
        // Alle Kontinente deaktivieren
        toggleButton.textContent = 'Alle einblenden';
        toggleButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200';
        
        continentButtons.forEach(button => {
            button.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-white text-gray-700 border-gray-300 hover:bg-gray-50';
        });
        
        // Filter zurücksetzen
        window.selectedContinents = new Set();
        checkActiveFilters(); // Check filters after deactivating all
        filterEventsByContinent();
    } else {
        // Alle Kontinente aktivieren
        toggleButton.textContent = 'Alle ausblenden';
        toggleButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black';

        continentButtons.forEach(button => {
            button.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black';
        });

        // Alle Kontinente zur Auswahl hinzufügen
        window.selectedContinents = new Set(continents.map(c => c.id)); // Alle Kontinent-IDs aus Datenbank
        checkActiveFilters(); // Check filters after activating all
        filterEventsByContinent();
    }
}

// Kontinent auswählen
function selectContinent(continentId) {
    // Globale Variable für ausgewählte Kontinente initialisieren
    if (!window.selectedContinents) {
        // Beim ersten Aufruf alle Kontinente aktivieren
        window.selectedContinents = new Set(continents.map(c => c.id));
    }
    
    // Kontinent zur Auswahl hinzufügen oder entfernen
    if (window.selectedContinents.has(continentId)) {
        window.selectedContinents.delete(continentId);
    } else {
        window.selectedContinents.add(continentId);
    }
    
    selectedCountry = null;
    
    // Button-Styles aktualisieren (ohne die "Alle einblenden/ausblenden" Schaltfläche)
    const buttons = document.querySelectorAll('#continentsList button:not(#toggleAllContinents)');
    buttons.forEach((button, index) => {
        // Hole die tatsächliche ID aus dem continents Array
        const buttonContinentId = continents[index]?.id;
        if (buttonContinentId && window.selectedContinents.has(buttonContinentId)) {
            button.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black';
        } else {
            button.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-white text-gray-700 border-gray-300 hover:bg-gray-50';
        }
    });
    
    // "Alle einblenden/ausblenden" Schaltfläche zurücksetzen
    const toggleButton = document.getElementById('toggleAllContinents');
    if (toggleButton) {
        toggleButton.textContent = 'Alle einblenden';
        toggleButton.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200 font-medium';
    }
    
    // Check if any filters are active
    checkActiveFilters();

    // Events nach Kontinent filtern
    filterEventsByContinent();

    console.log(`Selected continents: ${Array.from(window.selectedContinents).join(', ') || 'none'}`);
}

// Globale Variable für alle Events (ungefiltert) - wird in loadDashboardData gesetzt

// Globale Variable für ausgewählte Länder im Event-Filter
// Now stores objects with {name, iso2} instead of just names
window.selectedCountries = new Map();

// Events nach Kontinent filtern
function filterEventsByContinent() {
    console.log('filterEventsByContinent called');
    console.log('Selected continents:', window.selectedContinents ? Array.from(window.selectedContinents) : 'none');
    
    // Verwende die zentrale Filterlogik anstatt eigener Implementierung
    if (typeof loadDashboardData === 'function') {
        try {
            const eventsList = document.getElementById('eventsList');
            if (eventsList && Array.isArray(allEvents)) {
                loadDashboardData();
            }
        } catch (e) { 
            loadDashboardData(); 
        }
    }
    
    console.log(`Total filtered events: ${currentEvents.length}`);
}

// Events nach Land filtern
function filterEventsByCountry() {
    console.log('filterEventsByCountry called');

    // Verwende die zentrale Filterlogik anstatt eigener Implementierung
    if (typeof loadDashboardData === 'function') {
        try {
            const eventsList = document.getElementById('eventsList');
            if (eventsList && Array.isArray(allEvents)) {
                loadDashboardData();
            }
        } catch (e) {
            loadDashboardData();
        }
    }

    console.log(`Total filtered events: ${currentEvents.length}`);

    // Don't zoom here - let updateCountryOverlays handle the zoom
    // The zoom should be based on country boundaries, not just events
}

// Zoom map to show all filtered events
function zoomToFilteredEvents() {
    if (!map || !currentEvents || currentEvents.length === 0) return;

    // Get coordinates of all visible events
    const eventCoordinates = currentEvents
        .filter(event => event.latitude && event.longitude)
        .map(event => [event.latitude, event.longitude]);

    if (eventCoordinates.length === 0) return;

    try {
        // Create bounds from event coordinates
        const bounds = L.latLngBounds(eventCoordinates);

        // Adjust zoom based on number of events
        const eventCount = eventCoordinates.length;
        let maxZoom = 8;
        let padding = [100, 100];

        if (eventCount === 1) {
            // Single event - zoom in close
            maxZoom = 10;
            padding = [200, 200];
        } else if (eventCount <= 5) {
            // Few events - moderate zoom
            maxZoom = 7;
            padding = [150, 150];
        } else if (eventCount <= 20) {
            // Multiple events - wider view
            maxZoom = 6;
            padding = [100, 100];
        } else {
            // Many events - country/continent view
            maxZoom = 5;
            padding = [80, 80];
        }

        // Fit map to bounds with animation
        map.fitBounds(bounds, {
            padding: padding,
            maxZoom: maxZoom,
            animate: true,
            duration: 0.8
        });

        console.log(`Map zoomed to show ${eventCount} filtered events`);
    } catch (e) {
        console.error('Error zooming to events:', e);
    }
}

// Kontinent eines Events bestimmen
function getEventContinent(event) {
    const country = event.country_name || event.country || '';
    
    // Länder-zu-Kontinent-Mapping
    const countryToContinent = {
        // Europa (1)
        'Deutschland': 1, 'Germany': 1, 'Frankreich': 1, 'France': 1, 'Italien': 1, 'Italy': 1,
        'Spanien': 1, 'Spain': 1, 'Portugal': 1, 'Niederlande': 1, 'Netherlands': 1,
        'Belgien': 1, 'Belgium': 1, 'Österreich': 1, 'Austria': 1, 'Schweiz': 1, 'Switzerland': 1,
        'Polen': 1, 'Poland': 1, 'Tschechien': 1, 'Czech Republic': 1, 'Ungarn': 1, 'Hungary': 1,
        'Slowakei': 1, 'Slovakia': 1, 'Slowenien': 1, 'Slovenia': 1, 'Kroatien': 1, 'Croatia': 1,
        'Serbien': 1, 'Serbia': 1, 'Bosnien': 1, 'Bosnia': 1, 'Montenegro': 1, 'Albanien': 1, 'Albania': 1,
        'Griechenland': 1, 'Greece': 1, 'Bulgarien': 1, 'Bulgaria': 1, 'Rumänien': 1, 'Romania': 1,
        'Ukraine': 1, 'Moldau': 1, 'Moldova': 1, 'Weißrussland': 1, 'Belarus': 1,
        'Litauen': 1, 'Lithuania': 1, 'Lettland': 1, 'Latvia': 1, 'Estland': 1, 'Estonia': 1,
        'Finnland': 1, 'Finland': 1, 'Schweden': 1, 'Sweden': 1, 'Norwegen': 1, 'Norway': 1,
        'Dänemark': 1, 'Denmark': 1, 'Island': 1, 'Iceland': 1, 'Irland': 1, 'Ireland': 1,
        'Vereinigtes Königreich': 1, 'United Kingdom': 1, 'Großbritannien': 1, 'Russland': 1, 'Russia': 1,
        'Türkiye': 1, 'Turkey': 1, 'Zypern': 1, 'Cyprus': 1, 'Malta': 1,
        
        // Asien (2)
        'China': 2, 'Japan': 2, 'Indien': 2, 'India': 2, 'Indonesien': 2, 'Indonesia': 2,
        'Thailand': 2, 'Vietnam': 2, 'Malaysia': 2, 'Singapur': 2, 'Singapore': 2,
        'Philippinen': 2, 'Philippines': 2, 'Südkorea': 2, 'South Korea': 2, 'South': 2,
        'Nordkorea': 2, 'North Korea': 2, 'Myanmar': 2, 'Kambodscha': 2, 'Cambodia': 2,
        'Laos': 2, 'Brunei': 2, 'Pakistan': 2, 'Bangladesch': 2, 'Bangladesh': 2,
        'Sri Lanka': 2, 'Nepal': 2, 'Bhutan': 2, 'Malediven': 2, 'Maldives': 2,
        'Afghanistan': 2, 'Iran': 2, 'Irak': 2, 'Iraq': 2, 'Saudi-Arabien': 2, 'Saudi Arabia': 2,
        'Vereinigte Arabische Emirate': 2, 'UAE': 2, 'Katar': 2, 'Qatar': 2, 'Kuwait': 2,
        'Bahrain': 2, 'Oman': 2, 'Jemen': 2, 'Yemen': 2, 'Jordanien': 2, 'Jordan': 2,
        'Syrien': 2, 'Syria': 2, 'Libanon': 2, 'Lebanon': 2, 'Israel': 2, 'Palästina': 2,
        'Gaza Strip': 2, 'Kasachstan': 2, 'Kazakhstan': 2, 'Usbekistan': 2, 'Uzbekistan': 2,
        'Kirgisistan': 2, 'Kyrgyzstan': 2, 'Tadschikistan': 2, 'Tajikistan': 2,
        'Turkmenistan': 2, 'Mongolei': 2, 'Mongolia': 2,
        
        // Afrika (3)
        'Ägypten': 3, 'Egypt': 3, 'Libyen': 3, 'Libya': 3, 'Tunesien': 3, 'Tunisia': 3,
        'Algerien': 3, 'Algeria': 3, 'Marokko': 3, 'Morocco': 3, 'Sudan': 3, 'South Sudan': 3,
        'Äthiopien': 3, 'Ethiopia': 3, 'Kenia': 3, 'Kenya': 3, 'Uganda': 3, 'Tansania': 3, 'Tanzania': 3,
        'Ruanda': 3, 'Rwanda': 3, 'Burundi': 3, 'Demokratische Republik Kongo': 3, 'The': 3,
        'Kongo': 3, 'Congo': 3, 'Zentralafrikanische Republik': 3, 'Central African Republic': 3,
        'Tschad': 3, 'Chad': 3, 'Niger': 3, 'Nigeria': 3, 'Kamerun': 3, 'Cameroon': 3,
        'Benin': 3, 'Togo': 3, 'Ghana': 3, 'Burkina Faso': 3, 'Burkina': 3, 'Mali': 3,
        'Senegal': 3, 'Gambia': 3, 'Guinea-Bissau': 3, 'Guinea': 3, 'Sierra Leone': 3,
        'Liberia': 3, 'Elfenbeinküste': 3, 'Côte d\'Ivoire': 3, 'Mauretanien': 3, 'Mauritania': 3,
        'Südafrika': 3, 'South Africa': 3, 'Namibia': 3, 'Botswana': 3, 'Simbabwe': 3, 'Zimbabwe': 3,
        'Sambia': 3, 'Zambia': 3, 'Malawi': 3, 'Mosambik': 3, 'Mozambique': 3,
        'Madagaskar': 3, 'Madagascar': 3, 'Mauritius': 3, 'Seychellen': 3, 'Seychelles': 3,
        'Komoren': 3, 'Comoros': 3, 'Dschibuti': 3, 'Djibouti': 3, 'Eritrea': 3, 'Somalia': 3,
        'Angola': 3, 'Lesotho': 3, 'Eswatini': 3, 'Swaziland': 3,
        
        // Nordamerika (4)
        'Vereinigte Staaten': 4, 'United States': 4, 'USA': 4, 'Kanada': 4, 'Canada': 4,
        'Mexiko': 4, 'Mexico': 4, 'Guatemala': 4, 'Belize': 4, 'El Salvador': 4,
        'Honduras': 4, 'Nicaragua': 4, 'Costa Rica': 4, 'Panama': 4, 'Kuba': 4, 'Cuba': 4,
        'Jamaika': 4, 'Jamaica': 4, 'Haiti': 4, 'Dominikanische Republik': 4, 'Dominican Republic': 4,
        'Puerto Rico': 4, 'Bahamas': 4, 'The Bahamas': 4, 'Barbados': 4, 'Trinidad und Tobago': 4,
        'Grenada': 4, 'St. Vincent': 4, 'St. Lucia': 4, 'Dominica': 4, 'Antigua': 4,
        'St. Kitts': 4, 'Bermuda': 4, 'Grönland': 4, 'Greenland': 4,
        
        // Südamerika (5)
        'Brasilien': 5, 'Brazil': 5, 'Argentinien': 5, 'Argentina': 5, 'Chile': 5,
        'Peru': 5, 'Kolumbien': 5, 'Colombia': 5, 'Venezuela': 5, 'Ecuador': 5,
        'Bolivien': 5, 'Bolivia': 5, 'Paraguay': 5, 'Uruguay': 5, 'Guyana': 5,
        'Suriname': 5, 'Französisch-Guayana': 5, 'French Guiana': 5,
        
        // Australien/Ozeanien (6)
        'Australien': 6, 'Australia': 6, 'Neuseeland': 6, 'New Zealand': 6, 'New': 6,
        'Papua-Neuguinea': 6, 'Papua New Guinea': 6, 'Fidschi': 6, 'Fiji': 6,
        'Salomonen': 6, 'Solomon': 6, 'Vanuatu': 6, 'Neukaledonien': 6, 'New Caledonia': 6,
        'Samoa': 6, 'Tonga': 6, 'Kiribati': 6, 'Tuvalu': 6, 'Nauru': 6, 'Palau': 6,
        'Marshallinseln': 6, 'Marshall Islands': 6, 'Mikronesien': 6, 'Micronesia': 6,
        
        // Antarktis (7)
        'Antarktis': 7, 'Antarctica': 7
    };
    
    // Direkte Suche nach Ländername
    if (countryToContinent[country]) {
        return countryToContinent[country];
    }
    
    // Fallback: Suche nach Teilstring (für zusammengesetzte Namen)
    for (const [countryName, continentId] of Object.entries(countryToContinent)) {
        if (country.includes(countryName) || countryName.includes(country)) {
            return continentId;
        }
    }
    
    // Fallback basierend auf Koordinaten
    if (event.latitude && event.longitude) {
        const lat = parseFloat(event.latitude);
        const lng = parseFloat(event.longitude);
        
        // Grobe geografische Zuordnung
        if (lat >= 35 && lat <= 71 && lng >= -10 && lng <= 40) return 1; // Europa
        if (lat >= -10 && lat <= 55 && lng >= 25 && lng <= 180) return 2; // Asien
        if (lat >= -35 && lat <= 37 && lng >= -20 && lng <= 55) return 3; // Afrika
        if (lat >= 15 && lat <= 72 && lng >= -170 && lng <= -30) return 4; // Nordamerika
        if (lat >= -56 && lat <= 15 && lng >= -82 && lng <= -30) return 5; // Südamerika
        if (lat >= -50 && lat <= -10 && lng >= 110 && lng <= 180) return 6; // Australien/Ozeanien
        if (lat < -60) return 7; // Antarktis
    }
    
    // Kein Kontinent gefunden
    return null;
}

// Karte zentrieren
function centerMap() {
    if (window.selectedContinents && window.selectedContinents.size > 0) {
        // Wenn nur ein Kontinent ausgewählt ist, diesen zentrieren
        if (window.selectedContinents.size === 1) {
            const selectedContinent = Array.from(window.selectedContinents)[0];
            const continentCenters = {
                1: [54.5260, 15.2551], // Europa
                2: [34.0479, 100.6197], // Asien
                3: [8.7832, 34.5085],   // Afrika
                4: [45.0, -100.0],      // Nordamerika
                5: [-8.7832, -55.4915], // Südamerika
                6: [-25.2744, 133.7751], // Australien/Ozeanien
                7: [-82.8628, 135.0000]  // Antarktis
            };
            map.setView(continentCenters[selectedContinent], 4);
        } else {
            // Bei mehreren Kontinenten: Weltweit zentrieren
            map.setView([20, 0], 2);
        }
    } else {
        // Kein Kontinent ausgewählt - Weltweit zentrieren
        map.setView([20, 0], 2);
    }
}

// Daten aktualisieren
async function refreshData() {
    if (isLoading) return;
    
    isLoading = true;
    const button = document.getElementById('refreshButton');
    const originalHTML = button.innerHTML;
    
    try {
        button.innerHTML = '<div class="loading"></div>';
        
        // Zuerst neue GDACS Events von API holen
        await fetch('/api/gdacs/fetch-events');
        
        await loadDashboardData();
        await loadStatistics();
        showNotification('Daten erfolgreich aktualisiert!', 'success');
    } catch (error) {
        console.error('Error refreshing data:', error);
        showNotification('Fehler beim Aktualisieren der Daten', 'error');
    } finally {
        isLoading = false;
        button.innerHTML = originalHTML;
    }
}

// Letzte Aktualisierung aktualisieren
function updateLastUpdated() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('de-DE', {
        hour: '2-digit',
        minute: '2-digit'
    });
    // Update time display removed - element no longer exists
}

// Sektionen ein-/ausklappen
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return;

    if (section.style.display === 'none') {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
    }

    syncSectionToggleIcon(sectionId);

    // Höhe dynamisch aufteilen: wenn Filter zugeklappt -> Events bekommt volle Höhe, sonst 50/50
    adjustSidebarLayout();
}

function toggleEventSection(sectionId) {
    const listId = sectionId === 'futureEvents' ? 'futureEventsList' : 'eventsList';
    const iconId = sectionId === 'futureEvents' ? 'futureEventsToggleIcon' : 'currentPastEventsToggleIcon';

    const list = document.getElementById(listId);
    const icon = document.getElementById(iconId);

    if (!list || !icon) return;

    if (list.style.display === 'none') {
        list.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        list.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }

    // Adjust container padding based on open sections
    adjustEventContainerPadding();
}

function adjustEventContainerPadding() {
    const futureEventsList = document.getElementById('futureEventsList');
    const eventsList = document.getElementById('eventsList');
    const currentEventsContainer = document.getElementById('currentEvents');

    if (!futureEventsList || !eventsList || !currentEventsContainer) return;

    const isFutureOpen = futureEventsList.style.display !== 'none';
    const isCurrentOpen = eventsList.style.display !== 'none';

    // Wenn beide zugeklappt sind, weniger Padding
    if (!isFutureOpen && !isCurrentOpen) {
        currentEventsContainer.style.paddingBottom = '30px';
    } else {
        currentEventsContainer.style.paddingBottom = '170px';
    }
}

function adjustSidebarLayout() {
    const filtersWrapper = document.getElementById('filtersWrapper');
    const eventsWrapper = document.getElementById('eventsWrapper');
    const currentEvents = document.getElementById('currentEvents');
    const eventsList = document.getElementById('eventsList');
    
    if (!(filtersWrapper && eventsWrapper)) return;
    
    const filtersOpen = document.getElementById('filters')?.style.display !== 'none';
    
    if (filtersOpen) {
        // Filter nur so hoch wie Inhalt, Rest für Events
        filtersWrapper.style.flex = '0 0 auto';
        eventsWrapper.style.flex = '1 1 auto';
        // Sicherstellen dass Events Container richtige Höhe behält
        eventsWrapper.style.minHeight = '0';
        eventsWrapper.style.height = 'auto';
        // Optische Trennlinie unten über komplette Breite
        filtersWrapper.style.borderBottom = '1px solid #e5e7eb';
        
        // Events Container Flex Layout korrigieren
        if (currentEvents) {
            currentEvents.style.display = 'flex';
            currentEvents.style.flexDirection = 'column';
            currentEvents.style.minHeight = '0';
            currentEvents.style.flex = '1 1 auto';
        }
        
        // Events List Overflow sicherstellen
        if (eventsList) {
            eventsList.style.flex = '1 1 auto';
            eventsList.style.minHeight = '0';
            eventsList.style.overflowY = 'auto';
            eventsList.style.position = 'relative';
        }
    } else {
        filtersWrapper.style.flex = '0 0 auto';
        eventsWrapper.style.flex = '1 1 auto';
        // Bei geschlossenem Filter keine zusätzliche Trennlinie nötig
        filtersWrapper.style.borderBottom = 'none';
        
        // Reset der Event Container Stile
        if (currentEvents) {
            currentEvents.style.display = 'flex';
            currentEvents.style.flexDirection = 'column';
            currentEvents.style.minHeight = '0';
            currentEvents.style.flex = '1 1 auto';
        }
        
        if (eventsList) {
            eventsList.style.flex = '1 1 auto';
            eventsList.style.minHeight = '0';
            eventsList.style.overflowY = 'auto';
            eventsList.style.position = 'relative';
        }
    }
}

// Pfeil-Icon entsprechend Zustand drehen
function syncSectionToggleIcon(sectionId) {
    const isOpen = document.getElementById(sectionId)?.style.display !== 'none';
    // Mapping von Section -> Icon-Element-ID oder Query
    const icon = (
        sectionId === 'filters' ? document.getElementById('filtersToggleIcon') :
        sectionId === 'currentEvents' ? document.getElementById('currentEventsToggleIcon') :
        sectionId === 'liveStatistics' ? document.querySelector('#liveStatistics')?.previousElementSibling?.querySelector('svg.w-5.h-5') :
        sectionId === 'mapControl' ? document.querySelector('#mapControl')?.previousElementSibling?.querySelector('svg.w-5.h-5') :
        null
    );
    if (icon) {
        // Korrektur: Geschlossen = Pfeil nach unten (0deg), Geöffnet = Pfeil nach oben (180deg)
        icon.style.transform = isOpen ? 'rotate(180deg)' : 'rotate(0deg)';
        icon.style.transition = 'transform 150ms ease';
    }
}

// Länder filtern
function filterCountries(query) {
    console.log('Filtering countries:', query);
    // Hier könnte man echte Länder-Filterung implementieren
}

// Flughäfen suchen (Debounce + API)
let airportSearchTimer;
function debouncedAirportSearch(query) {
    clearTimeout(airportSearchTimer);
    airportSearchTimer = setTimeout(() => searchAirports(query), 250);
}

function debouncedAirportSearchWithFilters() {
    const mainInput = document.getElementById('airportFilter') || document.querySelector('#sidebar-airport input[placeholder="Flughafen suchen (Code oder Name)"]');
    const q = mainInput ? mainInput.value : '';
    clearTimeout(airportSearchTimer);
    airportSearchTimer = setTimeout(() => searchAirports(q), 250);
}

// Länder-Suche (Name, ISO2, ISO3)
let countrySearchTimer;
function debouncedCountrySearch(query) {
    clearTimeout(countrySearchTimer);
    countrySearchTimer = setTimeout(() => searchCountries(query), 250);
}

// Länder-Filter-Suche für Event-Filter
let countryFilterSearchTimer;
let countryFilterActiveIndex = 0;

function debouncedCountryFilterSearch(query) {
    clearTimeout(countryFilterSearchTimer);
    countryFilterSearchTimer = setTimeout(() => searchCountriesForFilter(query), 250);
}

// Keyboard navigation for country filter
function handleCountryFilterKeyup(event) {
    if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp' && event.key !== 'Enter') {
        debouncedCountryFilterSearch(event.target.value);
    }
}

function handleCountryFilterKeydown(event) {
    const box = document.getElementById('countryFilterResults');
    if (!box) return;

    const items = box.querySelectorAll('.autocomplete-item');
    if (items.length === 0) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        countryFilterActiveIndex = Math.min(countryFilterActiveIndex + 1, items.length - 1);
        setCountryFilterActiveIndex(countryFilterActiveIndex);
        // Scroll into view if needed
        items[countryFilterActiveIndex]?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        countryFilterActiveIndex = Math.max(countryFilterActiveIndex - 1, 0);
        setCountryFilterActiveIndex(countryFilterActiveIndex);
        // Scroll into view if needed
        items[countryFilterActiveIndex]?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    } else if (event.key === 'Enter') {
        event.preventDefault();
        const activeItem = items[countryFilterActiveIndex];
        if (activeItem) {
            const countryName = activeItem.getAttribute('data-name');
            if (countryName) {
                addCountryToFilter(countryName);
                box.innerHTML = '';
                event.target.value = '';
            }
        }
    } else if (event.key === 'Escape') {
        event.preventDefault();
        box.innerHTML = '';
        countryFilterActiveIndex = 0;
    }
}

async function searchCountries(query) {
    const box = document.getElementById('countrySearchResults');
    if (!box) return;
    const q = (query || '').trim();
    if (!q) { box.innerHTML = ''; return; }
    try {
        box.innerHTML = '<div class="text-xs text-gray-500">Suche…</div>';
        const res = await fetch('/api/countries/search?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('Network');
        const data = await res.json();
        const list = Array.isArray(data.data) ? data.data : [];
        if (!list.length) { box.innerHTML = '<div class="text-xs text-gray-500">Keine Treffer</div>'; return; }
        box.innerHTML = list.map((c, i) => (
            `<div class="autocomplete-item px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 flex items-center justify-between ${i === 0 ? 'bg-blue-50 border-blue-300' : ''}" data-index="${i}" data-name="${escapeForAttr(c.name)}">
                <div>
                    <div class="font-medium">${escapeHtml(c.name)}</div>
                    <div class="text-xs text-gray-500">${escapeHtml(c.iso2 || '')}${c.iso3 ? ' / ' + escapeHtml(c.iso3) : ''}</div>
                </div>
                <button class="text-xs px-2 py-1 border rounded text-gray-700 bg-gray-300 hover:bg-gray-200">Übernehmen</button>
            </div>`
        )).join('');

        // Reset active index to 0 for new search results
        countryFilterActiveIndex = 0;
        box.querySelectorAll('.autocomplete-item').forEach(el => {
            el.addEventListener('mouseenter', () => {
                const idx = parseInt(el.getAttribute('data-index'));
                setCountryFilterActiveIndex(idx);
            });
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const countryName = el.getAttribute('data-name');
                addCountryToFilter(countryName);
                box.innerHTML = '';
                document.getElementById('countryFilterInput').value = '';
            });
            el.querySelector('button')?.addEventListener('click', (e) => {
                e.stopPropagation();
                const countryName = el.parentElement.getAttribute('data-name');
                addCountryToFilter(countryName);
                box.innerHTML = '';
                document.getElementById('countryFilterInput').value = '';
            });
        });
    } catch (e) {
        box.innerHTML = '<div class="text-xs text-red-600">Fehler bei der Suche</div>';
        console.error(e);
    }
}

async function searchCountriesForFilter(query) {
    const box = document.getElementById('countryFilterResults');
    if (!box) return;
    const q = (query || '').trim();
    if (!q) { box.innerHTML = ''; return; }
    
    console.log('Searching countries for filter with query:', q);
    
    try {
        box.innerHTML = '<div class="text-xs text-gray-500">Suche…</div>';
        const res = await fetch('/api/countries/search?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
        
        console.log('Response status:', res.status);
        
        if (!res.ok) {
            const errorText = await res.text();
            console.error('API Error:', errorText);
            throw new Error(`HTTP ${res.status}: ${errorText}`);
        }
        
        const data = await res.json();
        console.log('API Response:', data);
        
        const list = Array.isArray(data.data) ? data.data : [];
        
        console.log('Country search results:', list);
        console.log('Data type:', typeof data.data);
        console.log('Is array:', Array.isArray(data.data));
        console.log('Data keys:', Object.keys(data.data || {}));
        
        if (data.error) {
            console.error('API returned error:', data.error);
            box.innerHTML = '<div class="text-xs text-red-600">API Fehler: ' + escapeHtml(data.error) + '</div>';
            return;
        }
        
        if (!list.length) { 
            box.innerHTML = '<div class="text-xs text-gray-500">Keine Treffer für "' + escapeHtml(q) + '"</div>'; 
            // Höhe zurücksetzen
            box.style.maxHeight = '8rem'; // 32 (max-h-32)
            return; 
        }
        
        // Höhe basierend auf Anzahl der Ergebnisse anpassen
        if (list.length > 2) {
            box.style.maxHeight = '16rem'; // Doppelte Höhe (64)
        } else {
            box.style.maxHeight = '8rem'; // Standard Höhe (32)
        }
        
        box.innerHTML = list.map((c, i) => (
            `<div class="autocomplete-item px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 flex items-center justify-between" data-index="${i}" data-name="${escapeForAttr(c.name)}" data-iso2="${escapeForAttr(c.iso2 || '')}">
                <div>
                    <div class="font-medium">${escapeHtml(c.name)}</div>
                    <div class="text-xs text-gray-500">${escapeHtml(c.iso2 || '')}${c.iso3 ? ' / ' + escapeHtml(c.iso3) : ''}</div>
                </div>
                <button class="text-xs px-2 py-1 border rounded text-gray-700 bg-gray-300 hover:bg-gray-100">Übernehmen</button>
            </div>`
        )).join('');

        box.querySelectorAll('.autocomplete-item').forEach(el => {
            el.addEventListener('mouseenter', () => {
                const idx = parseInt(el.getAttribute('data-index'));
                setCountryFilterActiveIndex(idx);
            });
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const countryName = el.getAttribute('data-name');
                const iso2 = el.getAttribute('data-iso2');
                console.log('Adding country to filter:', countryName, 'ISO:', iso2);
                addCountryToFilter(countryName, iso2);
                box.innerHTML = '';
            });
            el.querySelector('button')?.addEventListener('click', (e) => {
                e.stopPropagation();
                const countryName = el.getAttribute('data-name');
                const iso2 = el.getAttribute('data-iso2');
                console.log('Adding country to filter via button:', countryName, 'ISO:', iso2);
                addCountryToFilter(countryName, iso2);
                box.innerHTML = '';
            });
        });
    } catch (e) {
        console.error('Error in searchCountriesForFilter:', e);
        box.innerHTML = '<div class="text-xs text-red-600">Fehler bei der Suche: ' + escapeHtml(e.message) + '</div>';
    }
}

function applyCountryFilter(countryName) {
    const badgeWrap = document.getElementById('selectedCountryDisplay');
    const badgeText = document.getElementById('selectedCountryName');
    if (badgeWrap && badgeText) {
        badgeText.textContent = countryName;
        badgeWrap.classList.remove('hidden');
    }
    // Suchfeld leeren, damit alle Flughäfen des Landes geladen werden
    const mainInput = document.getElementById('airportFilter') || document.querySelector('#sidebar-airport input[placeholder="Flughafen suchen (Code oder Name)"]');
    if (mainInput) mainInput.value = '';
    // Länder-Suchstring leeren
    const countryInput = document.getElementById('countrySearchInput');
    if (countryInput) countryInput.value = '';
    const resultsBox = document.getElementById('countrySearchResults');
    if (resultsBox) resultsBox.innerHTML = '';
    debouncedAirportSearchWithFilters();
}

function clearSelectedCountry() {
    const badgeWrap = document.getElementById('selectedCountryDisplay');
    if (badgeWrap) badgeWrap.classList.add('hidden');
    debouncedAirportSearchWithFilters();
}

function addCountryToFilter(countryName, iso2) {
    console.log('addCountryToFilter called with:', countryName, iso2);

    // Land zur Auswahl hinzufügen (mit ISO-Code für besseres Matching)
    window.selectedCountries.set(countryName, { name: countryName, iso2: iso2 || null });

    // Suchfeld leeren
    const countryInput = document.getElementById('countryFilterInput');
    if (countryInput) countryInput.value = '';

    // Ausgewählte Länder anzeigen
    renderSelectedCountries();

    // Check if any filters are active
    checkActiveFilters();

    // Events nach Ländern filtern
    filterEventsByCountry();
}

function removeCountryFromFilter(countryName) {
    console.log('removeCountryFromFilter called with:', countryName);
    
    // Land aus der Auswahl entfernen
    window.selectedCountries.delete(countryName);
    
    // Ausgewählte Länder anzeigen
    renderSelectedCountries();

    // Check if any filters are active
    checkActiveFilters();

    // Events nach Ländern filtern
    filterEventsByCountry();
}

function renderSelectedCountries() {
    const displayContainer = document.getElementById('selectedCountriesFilterDisplay');
    if (!displayContainer) return;

    if (window.selectedCountries.size === 0) {
        displayContainer.innerHTML = '';
        // Clear country overlays when no countries selected
        updateCountryOverlays();
        return;
    }

    const countryBadges = Array.from(window.selectedCountries.entries()).map(([name, data]) => `
        <span class="inline-flex items-center gap-2 bg-blue-50 text-blue-800 border border-blue-200 rounded px-2 py-1 text-sm">
            <span>${escapeHtml(name)}${data.iso2 ? ' (' + escapeHtml(data.iso2) + ')' : ''}</span>
            <button type="button" class="text-blue-700 hover:text-blue-900" onclick="removeCountryFromFilter('${escapeForAttr(name)}')" style="cursor: pointer;">&times;</button>
        </span>
    `).join('');

    displayContainer.innerHTML = countryBadges;

    // Update country overlays on map
    updateCountryOverlays();
}

function clearAllCountryFilters() {
    console.log('clearAllCountryFilters called');

    // Alle Länder aus der Auswahl entfernen
    window.selectedCountries.clear();

    // Suchfeld leeren
    const countryInput = document.getElementById('countryFilterInput');
    if (countryInput) countryInput.value = '';

    // Ergebnisse leeren
    const resultsBox = document.getElementById('countryFilterResults');
    if (resultsBox) resultsBox.innerHTML = '';

    // Ausgewählte Länder anzeigen
    renderSelectedCountries();

    // Check if any filters are active
    checkActiveFilters();

    // Events nach Ländern filtern
    filterEventsByCountry();

    // Reset map view to initial state
    if (map && initialMapView) {
        map.setView(initialMapView.center, initialMapView.zoom, {
            animate: true,
            duration: 0.8
        });
    }
}

// Test-Funktion für Länder-Suche
function testCountrySearch() {
    console.log('Testing country search...');
    const testQueries = ['Deutschland', 'Germany', 'DE', 'USA', 'Frankreich'];
    
    testQueries.forEach(query => {
        setTimeout(() => {
            console.log(`Testing query: ${query}`);
            searchCountriesForFilter(query);
        }, 1000);
    });
}

// Debug-Funktion für Länder-Suche
async function debugCountrySearch(query) {
    console.log('Debug country search for:', query);
    try {
        const res = await fetch('/api/countries/search-debug?q=' + encodeURIComponent(query), { 
            headers: { 'Accept': 'application/json' } 
        });
        if (!res.ok) throw new Error('Network');
        const data = await res.json();
        console.log('Debug results:', data);
        return data;
    } catch (e) {
        console.error('Debug error:', e);
        return null;
    }
}

function setCountryFilterActiveIndex(index) {
    const box = document.getElementById('countryFilterResults');
    if (!box) return;

    countryFilterActiveIndex = index;

    box.querySelectorAll('.autocomplete-item').forEach((el, i) => {
        if (i === index) {
            el.classList.add('bg-blue-50', 'border-blue-300');
            el.classList.remove('hover:bg-gray-50');
        } else {
            el.classList.remove('bg-blue-50', 'border-blue-300');
            el.classList.add('hover:bg-gray-50');
        }
    });
}

async function searchAirports(query) {
    // Unterstützt statischen Container (Filter) und dynamische Sidebar
    const resultsContainer = document.getElementById('airportResultsDynamic') || document.getElementById('airportResults');
    if (!resultsContainer) return;

    const q = (query || '').trim();
    // Prüfe Filter. Wenn keine Eingabe und keine Filter → nichts laden.
    const country = (document.getElementById('airportCountryFilter')?.value || '').trim();
    const badgeWrap = document.getElementById('selectedCountryDisplay');
    const badgeActive = !!(badgeWrap && !badgeWrap.classList.contains('hidden'));
    const hasFilters = !!country || badgeActive;
    if (q.length === 0 && !hasFilters) {
        resultsContainer.innerHTML = '';
        clearAirportResultMarkers();
        return;
    }

    try {
        resultsContainer.innerHTML = '<div class="text-xs text-gray-500">Suche…</div>';
        // Wenn Badge gesetzt, hat Vorrang vor Dropdown
        const displayCountry = badgeActive ? (document.getElementById('selectedCountryName')?.textContent || '') : country;
        const params = new URLSearchParams({ q });
        if (displayCountry) params.set('country', displayCountry);
        // Falls Dropdown eine data-id hat, CountryID mitsenden (präziser als Name)
        const countrySel = document.getElementById('airportCountryFilter');
        const selectedOpt = countrySel ? countrySel.options[countrySel.selectedIndex] : null;
        const countryId = badgeActive ? '' : (selectedOpt && selectedOpt.getAttribute('data-id') ? selectedOpt.getAttribute('data-id') : '');
        if (countryId) params.set('country_id', countryId);
        const response = await fetch('/api/airports/search?' + params.toString(), {
            headers: { 'Accept': 'application/json' },
        });
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        const list = Array.isArray(data.data) ? data.data : [];
        if (list.length === 0) {
            resultsContainer.innerHTML = '<div class="text-xs text-gray-500">Keine Treffer</div>';
            clearAirportResultMarkers();
            // Auf Land zoomen, wenn gewählt
            if (displayCountry) {
                try {
                    const locateParams = new URLSearchParams();
                    locateParams.set('q', displayCountry);
                    const locRes = await fetch('/api/countries/locate?' + locateParams.toString(), { headers: { 'Accept': 'application/json' } });
                    const locData = await locRes.json();
                    if (locData && locData.data && locData.data.latitude && locData.data.longitude && typeof map !== 'undefined' && map) {
                        const lat = parseFloat(locData.data.latitude);
                        const lng = parseFloat(locData.data.longitude);
                        if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
                            map.setView([lat, lng], 7);
                        }
                    }
                } catch (e) { console.error('Country locate failed', e); }
            }
            return;
        }
        resultsContainer.innerHTML = list.map(a => {
            const title = `${a.name}`;
            const codes = `${a.iata_code ?? ''}${a.icao_code ? ' / ' + a.icao_code : ''}`;
            const canCenter = (a.latitude != null && a.longitude != null);
            return (
                `<div class="px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 flex items-center justify-between gap-2">
                    <div class="truncate">
                        <a href="#" onclick="openAirportSidebar({id:${a.id}, name:'${escapeForAttr(title)}', iata:'${escapeForAttr(a.iata_code ?? '')}', icao:'${escapeForAttr(a.icao_code ?? '')}', latitude:${a.latitude ?? 'null'}, longitude:${a.longitude ?? 'null'}}); return false;" class="font-medium text-blue-600 hover:underline">${escapeHtml(title)}</a>
                        <div class="text-xs text-gray-500 mt-0.5">${escapeHtml(codes)}</div>
                    </div>
                    <button class="text-xs px-2 py-1 border rounded text-gray-700 hover:bg-gray-100 disabled:opacity-50" ${canCenter ? '' : 'disabled'} onclick="centerMapOn(${a.latitude ?? 'null'}, ${a.longitude ?? 'null'}, '${escapeForAttr(title)}', '${escapeForAttr(codes)}', ${a.id}, '${escapeForAttr(a.iata_code ?? '')}', '${escapeForAttr(a.icao_code ?? '')}')">Karte</button>
                </div>`
            );
        }).join('');
        updateAirportResultMarkers(list);
    } catch (e) {
        resultsContainer.innerHTML = '<div class="text-xs text-red-600">Fehler bei der Suche</div>';
        console.error(e);
    }
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeForAttr(str) {
    return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// Check if any filters are active (kept for potential future use)
function checkActiveFilters() {
    updateFilterIndicator();
}

function updateFilterIndicator() {
    const indicator = document.getElementById('activeFilterIndicator');
    if (!indicator) return;

    let hasActiveFilters = false;

    // Check Provider filters - only if custom is deactivated
    // (GDACS can be off by default due to config)
    if (window.providerFilter) {
        if (!window.providerFilter.custom) {
            hasActiveFilters = true;
        }
        // Only check GDACS if it's enabled in config
        if (window.GDACS_ENABLED && !window.providerFilter.gdacs) {
            hasActiveFilters = true;
        }
    }

    // Check Risk filters
    if (window.riskFilter) {
        const allRisksActive = window.riskFilter.info && window.riskFilter.green &&
                                window.riskFilter.orange && window.riskFilter.red;
        if (!allRisksActive) {
            hasActiveFilters = true;
        }
    }

    // Check Event Type filters
    if (window.eventTypeFilter && eventTypes && eventTypes.length > 0) {
        const allTypesActive = eventTypes.every(type => window.eventTypeFilter[type.id] === true);
        if (!allTypesActive) {
            hasActiveFilters = true;
        }
    }

    // Check Continent filters (Weltregionen)
    if (window.selectedContinents && continents && continents.length > 0) {
        const allContinents = new Set(continents.map(c => c.id));
        const isAllContinentsSelected = allContinents.size === window.selectedContinents.size &&
                                       Array.from(allContinents).every(continent => window.selectedContinents.has(continent));
        if (!isAllContinentsSelected) {
            hasActiveFilters = true;
        }
    }

    // Check Country filter
    if (window.selectedCountries && window.selectedCountries.size > 0) {
        hasActiveFilters = true;
    }

    // Check Time Period filter
    if (window.timePeriodFilter && window.timePeriodFilter !== 'all') {
        hasActiveFilters = true;
    }

    // Show or hide indicator
    if (hasActiveFilters) {
        indicator.classList.remove('hidden');
    } else {
        indicator.classList.add('hidden');
    }
}

// Reset all filters to default
function resetAllFilters() {
    // Reset Event Type filters - all active by default
    if (eventTypes && eventTypes.length > 0) {
        window.eventTypeFilter = {};
        eventTypes.forEach(eventType => {
            window.eventTypeFilter[eventType.id] = true;
            const btn = document.getElementById(`event-type-${eventType.id}`);
            if (btn) {
                btn.className = 'px-2 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black flex flex-col items-center justify-center gap-1 overflow-hidden';
            }
        });
    }

    // Update Event Type toggle all button
    const toggleAllBtn = document.getElementById('toggleAllEventTypes');
    if (toggleAllBtn) {
        toggleAllBtn.textContent = 'Alle ausblenden';
        toggleAllBtn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black col-span-2';
    }

    // Reset Risk filters - all active by default
    window.riskFilter = { info: true, green: true, orange: true, red: true };
    ['info', 'green', 'orange', 'red'].forEach(risk => {
        const btn = document.getElementById(`risk-${risk}`);
        if (btn) {
            const colorStyle = risk === 'info' ? 'background-color: #0066cc; border-color: #0066cc;' :
                               risk === 'green' ? 'background-color: #0fb67f; border-color: #0fb67f;' :
                               risk === 'orange' ? 'background-color: #e6a50a; border-color: #e6a50a;' :
                               'background-color: #ff0000; border-color: #ff0000;';
            btn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors text-white';
            btn.style = colorStyle;
        }
    });

    // Update Risk toggle all button
    const toggleAllRiskBtn = document.getElementById('toggleAllRiskLevels');
    if (toggleAllRiskBtn) {
        toggleAllRiskBtn.textContent = 'Alle ausblenden';
        toggleAllRiskBtn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black col-span-2';
    }

    // Reset Provider filters - based on GDACS_ENABLED configuration
    window.providerFilter = { gdacs: window.GDACS_ENABLED, custom: true };
    const gdacsBtn = document.getElementById('provider-gdacs');
    const customBtn = document.getElementById('provider-custom');
    if (gdacsBtn) {
        gdacsBtn.className = window.GDACS_ENABLED ?
            'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black' :
            'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-200 text-gray-700 border-gray-300';
    }
    if (customBtn) {
        customBtn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black';
    }

    // Reset Time Period filter - 'all' is default
    window.timePeriodFilter = 'all';
    const allPeriodBtn = document.getElementById('period-all');
    const period7Btn = document.getElementById('period-7days');
    const period30Btn = document.getElementById('period-30days');

    if (allPeriodBtn) {
        allPeriodBtn.textContent = 'Alle ausblenden';
        allPeriodBtn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black';
    }
    if (period7Btn) {
        period7Btn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-200 text-gray-700 border-gray-300';
    }
    if (period30Btn) {
        period30Btn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-200 text-gray-700 border-gray-300';
    }

    // Reset Country filters - empty by default
    window.selectedCountries = new Map();
    const countryDisplay = document.getElementById('selectedCountriesFilterDisplay');
    if (countryDisplay) countryDisplay.innerHTML = '';

    // Reset Continent filters - all continents active by default
    window.selectedContinents = new Set(continents.map(c => c.id));
    // Update continent buttons
    document.querySelectorAll('[id^="continent-"]').forEach(btn => {
        btn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black';
    });

    // Update Continent toggle all button
    const toggleAllContinentsBtn = document.getElementById('toggleAllContinents');
    if (toggleAllContinentsBtn) {
        toggleAllContinentsBtn.textContent = 'Alle ausblenden';
        toggleAllContinentsBtn.className = 'px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black col-span-2';
    }

    // Reset link is now always visible, no need to hide it

    // Reload data and map
    if (typeof loadDashboardData === 'function') {
        loadDashboardData();
    }
    if (typeof addMarkersToMap === 'function') {
        addMarkersToMap();
    }
}

// Dropdowns für Länder/Kontinente initial befüllen
document.addEventListener('DOMContentLoaded', async () => {
    // Provider Filter basierend auf GDACS-Konfiguration initialisieren
    window.providerFilter = { gdacs: window.GDACS_ENABLED, custom: true };
    
    // GDACS-Button sichtbar machen wenn aktiviert
    const gdacsButton = document.getElementById('provider-gdacs');
    const containerDiv = document.getElementById('provider-buttons-container');
    if (window.GDACS_ENABLED && gdacsButton) {
        gdacsButton.style.display = 'block';
        // Grid-Layout anpassen wenn beide Buttons sichtbar sind
        containerDiv.className = 'grid grid-cols-2 gap-2';
    } else {
        // Nur Custom-Button, daher grid-cols-1
        containerDiv.className = 'grid grid-cols-1 gap-2';
    }
    try {
        const [countriesRes, continentsRes] = await Promise.all([
            fetch('/api/airports/countries', { headers: { 'Accept': 'application/json' } }),
            fetch('/api/airports/continents', { headers: { 'Accept': 'application/json' } }),
        ]);
        if (countriesRes.ok) {
            const data = await countriesRes.json();
            const select = document.getElementById('airportCountryFilter');
            if (select && Array.isArray(data.data)) {
                data.data.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.name;
                    opt.setAttribute('data-id', c.id ?? '');
                    opt.textContent = c.name + (c.code ? ` (${c.code})` : '');
                    select.appendChild(opt);
                });
                // Wenn es bereits eine Vorauswahl gibt, Suche auslösen
                if (select.value) {
                    debouncedAirportSearchWithFilters();
                }
            }
        }
        if (continentsRes.ok) {
            const data = await continentsRes.json();
            const select = document.getElementById('airportContinentFilter');
            if (select && Array.isArray(data.data)) {
                data.data.forEach(ct => {
                    const opt = document.createElement('option');
                    opt.value = ct.name;
                    opt.textContent = ct.name;
                    select.appendChild(opt);
                });
            }
        }
    } catch (e) {
        console.error('Dropdowns laden fehlgeschlagen', e);
    }
    // Airport-Filterbereich Zustand wiederherstellen
    try {
        const saved = localStorage.getItem('airportFilterOpen');
        const content = document.getElementById('airportFilterContent');
        const icon = document.getElementById('airportFilterToggleIcon');
        if (saved !== null && content && icon) {
            const shouldOpen = saved === 'true';
            content.style.display = shouldOpen ? 'block' : 'none';
            // Icon: geschlossen = 0deg (nach unten), geöffnet = 180deg (nach oben)
            icon.style.transform = shouldOpen ? 'rotate(180deg)' : 'rotate(0deg)';
        }
    } catch (e) {}
    try { resizeAirportSidebar(); } catch (e) {}
    window.addEventListener('resize', () => { try { resizeAirportSidebar(); } catch (e) {} });
});

function resizeAirportSidebar() {
    const aside = document.getElementById('sidebar-airport');
    if (!aside) return;
    const footer = document.querySelector('footer');
    const footerHeight = footer ? footer.getBoundingClientRect().height : 40;
    const rect = aside.getBoundingClientRect();
    const top = rect.top >= 0 ? rect.top : 0;
    const padding = 8;
    const maxH = Math.max(200, Math.floor(window.innerHeight - footerHeight - top - padding));
    aside.style.maxHeight = maxH + 'px';
    aside.style.overflowY = 'auto';
}

// Social links Sidebar
async function createSocialSidebar() {
    hideAllRightContainers();
    let existing = document.getElementById('sidebar-social-links');
    if (!existing) {
        existing = document.createElement('aside');
        existing.id = 'sidebar-social-links';
        existing.className = 'sidebar bg-white';
        existing.innerHTML = `
            <div class="bg-blue-100 border-b border-blue-200 p-2">
                <p class="text-xs text-blue-800 font-mono text-center">Container ID: sidebar-social-links</p>
            </div>
            <div class="bg-white shadow-sm flex flex-col min-h-0">
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-800">Social Media Links</h3>
                </div>
                <div class="p-4 space-y-3 flex flex-col min-h-0 flex-1">
                    <div class="grid grid-cols-2 gap-2">
                        <button class="px-3 py-2 text-xs rounded-lg border transition-colors bg-gray-300 text-black inline-flex items-center gap-2" data-platform="all" onclick="setActiveSocialPlatform(this); loadSocialLinks('')"><i class="fa-solid fa-layer-group"></i><span>Alle</span></button>
                        <button class="px-3 py-2 text-xs rounded-lg border transition-colors bg-white text-gray-700 border-gray-300 hover:bg-gray-50 inline-flex items-center gap-2" data-platform="tiktok" onclick="setActiveSocialPlatform(this); loadSocialLinks('tiktok')"><i class="fa-brands fa-tiktok"></i><span>TikTok</span></button>
                        <button class="px-3 py-2 text-xs rounded-lg border transition-colors bg-white text-gray-700 border-gray-300 hover:bg-gray-50 inline-flex items-center gap-2" data-platform="instagram" onclick="setActiveSocialPlatform(this); loadSocialLinks('instagram')"><i class="fa-brands fa-instagram"></i><span>Instagram</span></button>
                        <button class="px-3 py-2 text-xs rounded-lg border transition-colors bg-white text-gray-700 border-gray-300 hover:bg-gray-50 inline-flex items-center gap-2" data-platform="facebook" onclick="setActiveSocialPlatform(this); loadSocialLinks('facebook')"><i class="fa-brands fa-facebook"></i><span>Facebook</span></button>
                        <button class="px-3 py-2 text-xs rounded-lg border transition-colors bg-white text-gray-700 border-gray-300 hover:bg-gray-50 inline-flex items-center gap-2" data-platform="youtube" onclick="setActiveSocialPlatform(this); loadSocialLinks('youtube')"><i class="fa-brands fa-youtube"></i><span>YouTube</span></button>
                    </div>
                    <input id="socialSearch" type="text" placeholder="Suchen..." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onkeyup="debouncedLoadSocial()"/>
                    <div id="socialList" class="flex-1 min-h-0 overflow-y-auto space-y-2"></div>
                </div>
            </div>
        `;
        const mainContent = document.querySelector('.main-content');
        const navigation = mainContent.querySelector('.navigation');
        mainContent.insertBefore(existing, navigation.nextSibling);
    }
    existing.style.display = 'block';
    await loadSocialLinks('');
    setTimeout(() => { if (map) { map.invalidateSize(); } }, 150);
}

let socialMarkersLayer = null;
let socialDebounceTimer;
function debouncedLoadSocial(){
    clearTimeout(socialDebounceTimer);
    socialDebounceTimer = setTimeout(() => {
        const active = document.querySelector('#sidebar-social-links [data-platform].bg-gray-300');
        const platform = active ? (active.getAttribute('data-platform') === 'all' ? '' : active.getAttribute('data-platform')) : '';
        loadSocialLinks(platform);
    }, 250);
}

// Settings Sidebar
function createSettingsSidebar(){
    hideAllRightContainers();
    let existing = document.getElementById('sidebar-settings');
    if (!existing) {
        existing = document.createElement('aside');
        existing.id = 'sidebar-settings';
        existing.className = 'sidebar bg-white';
        existing.innerHTML = `
            <div class="bg-blue-100 border-b border-blue-200 p-2">
                <p class="text-xs text-blue-800 font-mono text-center">Container ID: sidebar-settings</p>
            </div>
            <div class="bg-white shadow-sm flex flex-col min-h-0">
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-800">Einstellungen</h3>
                </div>
                <div class="p-4 space-y-3 flex-1 overflow-y-auto">
                    <div class="text-sm text-gray-700">Hier können künftig allgemeine Einstellungen platziert werden (z. B. UI-Präferenzen, Kartenoptionen).</div>
                </div>
            </div>
        `;
        const mainContent = document.querySelector('.main-content');
        const navigation = mainContent.querySelector('.navigation');
        mainContent.insertBefore(existing, navigation.nextSibling);
    }
    existing.style.display = 'block';
    setTimeout(() => { if (map) { map.invalidateSize(); } }, 150);
}

async function loadSocialLinks(platform){
    const q = document.getElementById('socialSearch')?.value || '';
    const params = new URLSearchParams();
    if (platform) params.set('platform', platform);
    if (q) params.set('q', q);
    const res = await fetch('/api/social-links?' + params.toString(), { headers: { 'Accept': 'application/json' } });
    const json = await res.json();
    const list = Array.isArray(json.data) ? json.data : [];
    const box = document.getElementById('socialList');
    if (box) {
        box.innerHTML = list.map(l => {
            const p = (l.platform || '').toLowerCase();
            const icon = platformIconInline(p);
            return (
                `<div class=\"border rounded p-2 text-sm\">` +
                `<div class=\"flex items-center gap-2\">${icon}<span class=\"font-semibold\">${escapeHtml(l.title || '')}</span></div>`+
                `<div class=\"text-gray-600\">${escapeHtml(l.platform || '')} · ${escapeHtml(l.city || '')} ${escapeHtml(l.country || '')}</div>`+
                (l.url ? `<a class=\"text-blue-600 underline\" href=\"${escapeHtml(l.url)}\" target=\"_blank\">Link öffnen</a>` : '')+
                `</div>`
            );
        }).join('');
    }
    updateSocialMarkers(list);
}

function setActiveSocialPlatform(btn){
    const root = document.getElementById('sidebar-social-links');
    if (!root) return;
    root.querySelectorAll('[data-platform]').forEach(b => {
        b.classList.remove('bg-gray-300','text-white','border-blue-600');
        b.classList.add('bg-white','text-gray-700','border-gray-300');
    });
    btn.classList.remove('bg-white','text-gray-700','border-gray-300');
    btn.classList.add('bg-gray-300','text-white','border-blue-600');
}

function platformIconInline(platform){
    const p = (platform || '').toLowerCase();
    if (p === 'tiktok') return '<i class=\"fa-brands fa-tiktok text-black\"></i>';
    if (p === 'instagram') return '<i class=\"fa-brands fa-instagram text-pink-600\"></i>';
    if (p === 'facebook') return '<i class=\"fa-brands fa-facebook text-blue-600\"></i>';
    if (p === 'youtube') return '<i class=\"fa-brands fa-youtube text-red-600\"></i>';
    return '<i class=\"fa-solid fa-share-nodes text-gray-500\"></i>';
}

function platformIconClass(platform){
    const p = (platform || '').toLowerCase();
    if (p === 'tiktok') return 'fa-brands fa-tiktok';
    if (p === 'instagram') return 'fa-brands fa-instagram';
    if (p === 'facebook') return 'fa-brands fa-facebook';
    if (p === 'youtube') return 'fa-brands fa-youtube';
    return 'fa-solid fa-share-nodes';
}

function platformCssClass(platform){
    const p = (platform || '').toLowerCase();
    if (p === 'tiktok') return 'social-tiktok';
    if (p === 'instagram') return 'social-instagram';
    if (p === 'facebook') return 'social-facebook';
    if (p === 'youtube') return 'social-youtube';
    return 'social-generic';
}

function updateSocialMarkers(list){
    if (!map) return;
    if (!socialMarkersLayer) socialMarkersLayer = L.layerGroup().addTo(map);
    socialMarkersLayer.clearLayers();
    const points = [];
    list.forEach(l => {
        if (l.latitude == null || l.longitude == null) return;
        const lat = parseFloat(l.latitude); const lng = parseFloat(l.longitude);
        if (isNaN(lat) || isNaN(lng)) return;
        const cssClass = platformCssClass(l.platform);
        const faClass = platformIconClass(l.platform);
        const html = `<div class=\"social-marker ${cssClass}\"><i class=\"${faClass}\"></i></div>`;
        const icon = L.divIcon({ html: html, iconSize: [24,24], className: '' });
        const m = L.marker([lat, lng], { icon });
        m.bindPopup(`<div class=\"font-medium\">${escapeHtml(l.title || '')}</div><div class=\"text-xs text-gray-600\">${escapeHtml(l.platform || '')}</div>` + (l.url ? `<div><a class=\"text-blue-600 underline\" href=\"${escapeHtml(l.url)}\" target=\"_blank\">Link</a></div>` : ''));
        socialMarkersLayer.addLayer(m);
        points.push([lat,lng]);
    });
    if (points.length) {
        const bounds = L.latLngBounds(points);
        map.fitBounds(bounds, { padding: [40,40], maxZoom: 8 });
    }
}
// Karten-Einstellungen umschalten
function toggleMapSettings() {
    console.log('Toggle map settings');
    // Hier könnte man Karten-Einstellungen implementieren
}

// Legende auf-/zuklappen
function toggleLegend() {
    const content = document.getElementById('legendContent');
    const icon = document.getElementById('legendToggleIcon');
    const container = document.getElementById('legendContainer');
    const header = document.getElementById('legendHeader');
    
    if (content.style.display === 'none') {
        // Legende öffnen
        content.style.display = 'block';
        icon.style.transform = 'rotate(0deg)';
        container.style.maxHeight = 'none';
        header.style.justifyContent = 'space-between'; // Normaler Abstand
    } else {
        // Legende schließen
        content.style.display = 'none';
        icon.style.transform = 'rotate(180deg)';
        container.style.maxHeight = '60px'; // Nur Header-Höhe
        header.style.justifyContent = 'space-between';
        header.style.gap = '20px'; // 20px zusätzlicher Abstand
    }
}

// Karte auf Flughafengeokoordinaten zentrieren
function centerMapOn(lat, lng, title, codes, airportId = null, iata = '', icao = '') {
    if (!map || lat == null || lng == null) return;
    try {
        const latNum = parseFloat(lat);
        const lngNum = parseFloat(lng);
        if (Number.isNaN(latNum) || Number.isNaN(lngNum)) return;

        // Icon nur einmal erzeugen
        if (!window.airportFocusIcon) {
            window.airportFocusIcon = L.divIcon({
                className: 'airport-focus-marker',
                html: '<div style="width:28px;height:28px;border-radius:9999px;background:#2563eb;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 0 0 2px rgba(37,99,235,.35)"><span style="color:#fff;font-size:14px;line-height:1">✈</span></div>',
                iconSize: [28, 28],
                iconAnchor: [14, 28],
                popupAnchor: [0, -28],
            });
        }

        // Vorherigen Fokus-Marker entfernen
        if (window.airportFocusMarker) {
            try { map.removeLayer(window.airportFocusMarker); } catch (e) {}
        }

        window.airportFocusMarker = L.marker([latNum, lngNum], { icon: window.airportFocusIcon, zIndexOffset: 1000 }).addTo(map);
        if (title) {
            const detailsBtn = `
                <div class=\"popup-actions mt-2\"> 
                    <button class=\"details-btn\" onclick=\"openAirportSidebar({id:${airportId ?? 'null'}, name:'${escapeForAttr(title)}', iata:'${escapeForAttr(iata)}', icao:'${escapeForAttr(icao)}', latitude:${latNum}, longitude:${lngNum}})\"> 
                        <i class=\"fa-solid fa-circle-info\"></i> 
                        Details anzeigen
                    </button>
                </div>`;
            const content = `<div class=\"font-medium\">${escapeHtml(title)}</div>` + (codes ? `<div class=\"text-xs text-gray-600\">${escapeHtml(codes)}</div>` : '') + detailsBtn;
            try { window.airportFocusMarker.bindPopup(content).openPopup(); } catch (e) {}
        }

        // Weiter hinein zoomen als vorher (z. B. 12)
        map.setView([latNum, lngNum], 12);
    } catch (e) {
        console.error('Karte zentrieren fehlgeschlagen', e);
    }
}

// Seitenleiste für Flughafen öffnen und Wetter/Zeit laden (Re-Use der bestehenden Styles)
async function openAirportSidebar(airport) {
    document.getElementById('sidebarTitle').textContent = 'Flughafen-Informationen';
    document.getElementById('eventSidebar').classList.add('open');

    const sidebarContent = document.getElementById('sidebarContent');
    sidebarContent.innerHTML = `
        <div class="event-details">
            <div class="event-header">
                <h2 class="event-title">${escapeHtml(airport.name || 'Flughafen')}</h2>
                <div class="event-meta">
                    <span class="event-type">${airport.iata ? 'IATA: ' + escapeHtml(airport.iata) : ''}</span>
                    <span class="event-severity">${airport.icao ? 'ICAO: ' + escapeHtml(airport.icao) : ''}</span>
                </div>
            </div>

            <div class="event-info-grid">
                <div class="info-item">
                    <span class="info-label">Koordinaten:</span>
                    <span class="info-value">${airport.latitude?.toFixed ? airport.latitude.toFixed(5) : airport.latitude}, ${airport.longitude?.toFixed ? airport.longitude.toFixed(5) : airport.longitude}</span>
                </div>
            </div>

            <div class="mt-2"><i class="fa-regular fa-cloud-sun"></i> Aktuelles Wetter</div>
            <div id="weather-${airport.latitude}-${airport.longitude}"></div>
        </div>
    `;

    try {
        const response = await fetch('/api/gdacs/event-details', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ latitude: airport.latitude, longitude: airport.longitude })
        });
        const result = await response.json();
        if (result.success) {
            updateWeatherDisplay(airport.latitude, airport.longitude, result.data);
        } else {
            const container = document.getElementById(`weather-${airport.latitude}-${airport.longitude}`);
            if (container) {
                container.innerHTML = `<div class=\"weather-error\"><i class=\"fa-solid fa-triangle-exclamation\"></i><span>Wetter-Daten nicht verfügbar</span></div>`;
            }
        }
    } catch (e) {
        const container = document.getElementById(`weather-${airport.latitude}-${airport.longitude}`);
        if (container) {
            container.innerHTML = `<div class=\"weather-error\"><i class=\"fa-solid fa-triangle-exclamation\"></i><span>Wetter-Daten nicht verfügbar</span></div>`;
        }
    }
}

// Statistik-Container einblenden (nur einseitig)
function toggleStatistics() {
    const statisticsContainer = document.getElementById('statisticsContainer');
    // Zuerst alle anderen Container verbergen
    hideAllRightContainers();
    // Dann ausschließlich den Statistik-Container zeigen
    if (statisticsContainer) statisticsContainer.style.display = 'block';
    
    // Statistiken laden
    loadDetailedStatistics();
    
    // Karte nach Animation neu zeichnen
    setTimeout(() => { if (map) { map.invalidateSize(); } }, 300);
}

// Live-Statistiken laden
function loadDetailedStatistics() {
    // Die Live-Statistiken werden bereits durch die bestehende loadDashboardData() Funktion geladen
    // Diese Funktion wird nur aufgerufen, um sicherzustellen, dass die Statistiken im neuen Container angezeigt werden
    
    // Aktualisiere die Statistiken im Statistik-Container
    updateStatisticsDisplay();
}

// Statistiken im Display aktualisieren
function updateStatisticsDisplay() {
    // Diese Funktion stellt sicher, dass die Statistiken im Statistik-Container korrekt angezeigt werden
    // Die eigentlichen Werte kommen aus der loadDashboardData() Funktion
}

// Statistics Section auf-/zuklappen
function toggleStatisticsSection() {
    const content = document.getElementById('statisticsContent');
    const icon = document.getElementById('statisticsToggleIcon');
    
    if (content.style.display === 'none') {
        // Statistiken öffnen
        content.style.display = 'block';
        icon.style.transform = 'rotate(0deg)';
    } else {
        // Statistiken schließen
        content.style.display = 'none';
        icon.style.transform = 'rotate(180deg)';
    }
}

// Filter Section auf-/zuklappen
function toggleFilterSection() {
    const content = document.getElementById('filterContent');
    const icon = document.getElementById('filterToggleIcon');
    
    if (content.style.display === 'none') {
        // Filter öffnen
        content.style.display = 'block';
        icon.style.transform = 'rotate(0deg)';
    } else {
        // Filter schließen
        content.style.display = 'none';
        icon.style.transform = 'rotate(180deg)';
    }
}

// Filter Container anzeigen
function showFilterContainer() {
    const sidebar = document.querySelector('.sidebar');
    const statisticsContainer = document.getElementById('statisticsContainer');
    const filterContainer = document.getElementById('filter-container');
    
    // Sidebar und Statistics Container ausblenden, Filter Container einblenden
    sidebar.style.display = 'none';
    statisticsContainer.style.display = 'none';
    filterContainer.style.display = 'block';
    
    // Karte nach Animation neu zeichnen
    setTimeout(() => {
        if (map) {
            map.invalidateSize();
        }
    }, 300);
}

// Neue Filter Sidebar erstellen und anzeigen
function createNewFilterSidebar() {
    // Bestehende Sidebars ausblenden
    const sidebar = document.querySelector('.sidebar');
    const statisticsContainer = document.getElementById('statisticsContainer');
    const existingFilterContainer = document.getElementById('filter-container');
    
    if (sidebar) sidebar.style.display = 'none';
    if (statisticsContainer) statisticsContainer.style.display = 'none';
    if (existingFilterContainer) existingFilterContainer.style.display = 'none';
    
    // Neue Filter Sidebar erstellen
    const newFilterSidebar = document.createElement('aside');
    newFilterSidebar.className = 'sidebar';
    newFilterSidebar.id = 'new-filter-sidebar';
    newFilterSidebar.style.display = 'block';
    
    newFilterSidebar.innerHTML = `
        <!-- Container ID Display -->
        <div class="bg-blue-100 border-b border-blue-200 p-2">
            <p class="text-xs text-blue-800 font-mono text-center">Container ID: new-filter-sidebar</p>
        </div>
        
        <!-- Filter Content -->
        <div class="bg-white shadow-sm">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 cursor-pointer hover:bg-gray-50" onclick="toggleNewFilterSection()">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <h3 class="font-semibold text-gray-800">Neue Filter Sidebar</h3>
                </div>
                <svg id="newFilterToggleIcon" class="w-5 h-5 transform transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
            
            <div id="newFilterContent" class="p-4 space-y-4">
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Neue Filter-Optionen</h4>
                    <p class="text-sm text-gray-600">Diese ist eine komplett neue Sidebar mit eigenen Filtern.</p>
                </div>
                
                <!-- Custom Filter Options -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Benutzerdefinierte Filter</h4>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-2" checked> Erweiterte Suche
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-2" checked> Geografische Filter
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-2" checked> Zeitbasierte Filter
                        </label>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="pt-4 border-t border-gray-200">
                    <button class="w-full bg-gray-300 text-black py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors mb-2">
                        Neue Filter anwenden
                    </button>
                    <button class="w-full bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition-colors">
                        Zurücksetzen
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Neue Sidebar in das Layout einfügen (rechts neben der schwarzen Navigation)
    // Hinweis: firstChild kann ein Textknoten sein -> gezielt nach dem Navigation-Element einfügen
    const mainContent = document.querySelector('.main-content');
    const navigation = mainContent.querySelector('.navigation');
    mainContent.insertBefore(newFilterSidebar, navigation.nextSibling);
    
    // Karte nach Animation neu zeichnen
    setTimeout(() => {
        if (map) {
            map.invalidateSize();
        }
    }, 300);
}

// Neue Airport Sidebar erstellen und anzeigen
function createAirportSidebar() {
    // Alle anderen Container ausblenden
    hideAllRightContainers();
    const existingAirport = document.getElementById('sidebar-airport');
    if (existingAirport) {
        existingAirport.style.display = 'block';
        setTimeout(() => { if (map) { map.invalidateSize(); } resizeAirportSidebar(); }, 150);
        return;
    }

    const airportSidebar = document.createElement('aside');
    airportSidebar.className = 'sidebar bg-white';
    airportSidebar.style.backgroundColor = '#ffffff';
    airportSidebar.id = 'sidebar-airport';
    airportSidebar.style.display = 'block';

    airportSidebar.innerHTML = `
        <div class="bg-blue-100 border-b border-blue-200 p-2">
            <p class="text-xs text-blue-800 font-mono text-center">Container ID: sidebar-airport</p>
        </div>
        <div class="bg-white shadow-sm flex flex-col min-h-0">
            <!-- Filterbereich analog sidebar-liveStatistics -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 cursor-pointer hover:bg-gray-50" onclick="toggleAirportFilterSection()">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <h3 class="font-semibold text-gray-800">Filter</h3>
                </div>
                <svg id="airportFilterToggleIcon" class="w-5 h-5 transform transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
            <div id="airportFilterContent" class="p-4 space-y-3" style="display:none;">
                <div class="mt-2">
                    <input id="countrySearchInput" type="text" placeholder="Land: Name, ISO2, ISO3" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onkeyup="debouncedCountrySearch(this.value)">
                    <div id="countrySearchResults" class="mt-2 space-y-1 text-sm text-gray-700 max-h-64 overflow-y-auto"></div>
                </div>
                <div id="selectedCountryDisplay" class="hidden mt-2 text-sm">
                    <span class="inline-flex items-center gap-2 bg-blue-50 text-blue-800 border border-blue-200 rounded px-2 py-1">
                        <span id="selectedCountryName">—</span>
                        <button type="button" class="text-blue-700 hover:text-blue-900" onclick="clearSelectedCountry()">&times;</button>
                    </span>
                </div>
            </div>
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-800">Flughäfen</h3>
            </div>
            <div class="p-4 space-y-3 flex flex-col min-h-0 flex-1">
                <input type="text" placeholder="Flughafen suchen (Code oder Name)" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onkeyup="debouncedAirportSearch(this.value)">
                <div id="airportResultsDynamic" class="mt-1 space-y-1 text-sm text-gray-700 flex-1 min-h-0 overflow-y-auto"></div>
            </div>
        </div>
    `;

    const mainContent = document.querySelector('.main-content');
    const navigation = mainContent.querySelector('.navigation');
    mainContent.insertBefore(airportSidebar, navigation.nextSibling);

    setTimeout(() => { if (map) { map.invalidateSize(); } resizeAirportSidebar(); }, 300);
    window.addEventListener('resize', () => { try { resizeAirportSidebar(); } catch (e) {} });
}

function toggleAirportFilterSection() {
    const content = document.getElementById('airportFilterContent');
    const icon = document.getElementById('airportFilterToggleIcon');
    if (!content || !icon) return;
    const open = content.style.display !== 'none';
    const newOpen = !open;
    content.style.display = newOpen ? 'block' : 'none';
    // Korrekte Icon-Ausrichtung: geschlossen = 0deg (nach unten), geöffnet = 180deg (nach oben)
    icon.style.transform = newOpen ? 'rotate(180deg)' : 'rotate(0deg)';
    try { localStorage.setItem('airportFilterOpen', newOpen.toString()); } catch (e) {}
    // Nach Layout-Änderung Karte neu berechnen und Sidebarhöhe anpassen
    setTimeout(() => { try { resizeAirportSidebar(); } catch (e) {} if (typeof map !== 'undefined' && map) { try { map.invalidateSize(); } catch (e) {} } }, 150);
}

// Neue Filter Section auf-/zuklappen
function toggleNewFilterSection() {
    const content = document.getElementById('newFilterContent');
    const icon = document.getElementById('newFilterToggleIcon');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(0deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(180deg)';
    }
}

// Filter Unterbereiche auf-/zuklappen
function toggleFilterSubSection(sectionId) {
    const content = document.getElementById(sectionId);
    let iconId;
    switch(sectionId) {
        case 'continentsSection':
            iconId = 'continentsToggleIcon';
            break;
        case 'countriesSection':
            iconId = 'countriesToggleIcon';
            break;
        case 'providersSection':
            iconId = 'providersToggleIcon';
            break;
        case 'riskLevelSection':
            iconId = 'riskLevelToggleIcon';
            break;
        case 'eventTypeSection':
            iconId = 'eventTypeToggleIcon';
            break;
        case 'timePeriodSection':
            iconId = 'timePeriodToggleIcon';
            break;
        default:
            iconId = 'continentsToggleIcon';
    }
    const icon = document.getElementById(iconId);
    
    if (!content || !icon) return;
    
    const isOpen = content.style.display !== 'none';
    const newOpen = !isOpen;
    
    content.style.display = newOpen ? 'block' : 'none';
    // Icon-Ausrichtung: geschlossen = 0deg (nach unten), geöffnet = 180deg (nach oben)
    icon.style.transform = newOpen ? 'rotate(180deg)' : 'rotate(0deg)';
    
    // Zustand im localStorage speichern
    try {
        localStorage.setItem(`filterSubSection_${sectionId}`, newOpen.toString());
    } catch (e) {}
}

// Filter Unterbereiche Zustand wiederherstellen
function restoreFilterSubSections() {
    const sections = ['continentsSection', 'countriesSection', 'providersSection', 'riskLevelSection', 'eventTypeSection', 'timePeriodSection'];
    
    sections.forEach(sectionId => {
        const content = document.getElementById(sectionId);
        let iconId;
        switch(sectionId) {
            case 'continentsSection':
                iconId = 'continentsToggleIcon';
                break;
            case 'countriesSection':
                iconId = 'countriesToggleIcon';
                break;
            case 'providersSection':
                iconId = 'providersToggleIcon';
                break;
            case 'riskLevelSection':
                iconId = 'riskLevelToggleIcon';
                break;
            case 'eventTypeSection':
                iconId = 'eventTypeToggleIcon';
                break;
            case 'timePeriodSection':
                iconId = 'timePeriodToggleIcon';
                break;
            default:
                iconId = null;
        }
        const icon = document.getElementById(iconId);
        
        if (!content || !icon) return;
        
        try {
            const saved = localStorage.getItem(`filterSubSection_${sectionId}`);
            if (saved !== null) {
                const shouldOpen = saved === 'true';
                content.style.display = shouldOpen ? 'block' : 'none';
                icon.style.transform = shouldOpen ? 'rotate(180deg)' : 'rotate(0deg)';
            } else {
                // Standardmäßig geöffnet
                content.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            }
        } catch (e) {
            // Fallback: Standardmäßig geöffnet
            content.style.display = 'block';
            icon.style.transform = 'rotate(180deg)';
        }
    });
}

// Sidebar Live Statistics anzeigen
function showSidebarLiveStatistics() {
    const sidebar = getDefaultSidebar();
    const statisticsContainer = document.getElementById('statisticsContainer');
    const airportSidebar = document.getElementById('sidebar-airport');
    const socialSidebar = document.getElementById('sidebar-social-links');
    
    // Airport-Sidebar ausblenden, Standard-Sidebar anzeigen, Statistiken ausblenden (nur Live-Stats Sektion sichtbar)
    if (airportSidebar) airportSidebar.style.display = 'none';
    if (socialSidebar) socialSidebar.style.display = 'none';
    if (sidebar) sidebar.style.display = 'block';
    statisticsContainer.style.display = 'none';
    
    // Sicherstellen, dass die Live Statistics Sektion sichtbar ist
    const liveStatisticsSection = document.getElementById('liveStatistics');
    if (liveStatisticsSection) {
        liveStatisticsSection.style.display = 'block';
    }
    
    // Karte nach Animation neu zeichnen
    setTimeout(() => {
        if (map) {
            map.invalidateSize();
        }
    }, 300);
}

// Aktuell sichtbaren rechten Container (neben der schwarzen Leiste) ein-/ausblenden
// Unterstützt: Standard-Sidebar, Statistik-Container, Filter-Container, neue Filter-Sidebar
window._lastRightContainerId = window._lastRightContainerId || null;

function getDefaultSidebar() {
    const mainContent = document.querySelector('.main-content');
    if (!mainContent) return null;
    // Erste Sidebar rechts neben der Navigation, die nicht einer der speziellen Container ist
    return mainContent.querySelector('aside.sidebar:not(#filter-container):not(#new-filter-sidebar):not(#sidebar-airport):not(#sidebar-social-links):not(#sidebar-settings)');
}

function isElementVisible(el) {
    if (!el) return false;
    const style = window.getComputedStyle(el);
    return style.display !== 'none' && style.visibility !== 'hidden' && el.offsetWidth > 0;
}

function findVisibleRightContainer() {
    const candidates = [
        document.getElementById('new-filter-sidebar'),
        document.getElementById('filter-container'),
        document.getElementById('statisticsContainer'),
        getDefaultSidebar(),
        document.getElementById('sidebar-airport'),
        document.getElementById('sidebar-social-links'),
        document.getElementById('sidebar-settings'),
    ];
    for (const el of candidates) {
        if (isElementVisible(el)) return el;
    }
    return null;
}

function hideAllRightContainers() {
    const mainContent = document.querySelector('.main-content');
    if (!mainContent) return;
    const all = [
        getDefaultSidebar(),
        document.getElementById('statisticsContainer'),
        document.getElementById('filter-container'),
        document.getElementById('new-filter-sidebar'),
        document.getElementById('sidebar-airport'),
        document.getElementById('sidebar-social-links'),
        document.getElementById('sidebar-settings'),
    ].filter(Boolean);
    all.forEach(el => el.style.display = 'none');
}

function toggleRightContainer() {
    const visible = findVisibleRightContainer();
    if (visible) {
        // Merken, was zuletzt sichtbar war, um es später wieder zu öffnen
        window._lastRightContainerId = visible.id || '_defaultSidebar';
        visible.style.display = 'none';
    } else {
        // Nichts sichtbar -> letztes wieder anzeigen oder Standard-Sidebar
        const targetId = window._lastRightContainerId;
        hideAllRightContainers();
        let target = null;
        if (targetId && targetId !== '_defaultSidebar') {
            target = document.getElementById(targetId);
        }
        if (!target) {
            target = getDefaultSidebar();
        }
        if (target) {
            target.style.display = 'block';
        }
    }

    // Karte nach Layout-Änderung neu berechnen
    setTimeout(() => {
        if (map) {
            map.invalidateSize();
        }
    }, 250);
}

// Benachrichtigung anzeigen
function showNotification(message, type = 'info') {
    // Einfache Benachrichtigung
    const notification = document.createElement('div');
    notification.className = `fixed top-20 right-4 z-50 px-4 py-2 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Marker-Layer für Suchergebnisse
function getAirportListIcon() {
    if (!window.airportListIcon) {
        // Größe analog zu Event-Markern (nutzt 24px Grundgröße aus custom-marker)
        const eventIconSize = 24;
        window.airportListIcon = L.divIcon({
            className: 'airport-list-marker',
            html: `<div style="width:${eventIconSize}px;height:${eventIconSize}px;border-radius:9999px;background:#1e40af;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.4)"><span style="color:#fff;font-size:${Math.round(eventIconSize*0.55)}px;line-height:1">✈</span></div>`,
            iconSize: [eventIconSize, eventIconSize],
            iconAnchor: [eventIconSize/2, eventIconSize],
            popupAnchor: [0, -eventIconSize],
        });
    }
    return window.airportListIcon;
}
function clearAirportResultMarkers() {
    if (window.airportResultsLayer && typeof map !== 'undefined' && map) {
        try { window.airportResultsLayer.clearLayers(); } catch (e) {}
    }
}
function updateAirportResultMarkers(list) {
    if (typeof map === 'undefined' || !map) return;
    if (!window.airportResultsLayer) {
        window.airportResultsLayer = L.layerGroup().addTo(map);
    } else {
        window.airportResultsLayer.clearLayers();
    }
    const boundsPoints = [];
    const icon = getAirportListIcon();
    list.forEach(a => {
        if (a.latitude == null || a.longitude == null) return;
        const lat = parseFloat(a.latitude); const lng = parseFloat(a.longitude);
        if (Number.isNaN(lat) || Number.isNaN(lng)) return;
        const codes = `${a.iata_code ?? ''}${a.icao_code ? ' / ' + a.icao_code : ''}`;
        const marker = L.marker([lat, lng], { icon });
        const detailsBtn = `
            <div class=\"popup-actions mt-2\"> 
                <button class=\"details-btn\" onclick=\"openAirportSidebar({id:${a.id}, name:'${escapeForAttr(a.name)}', iata:'${escapeForAttr(a.iata_code ?? '')}', icao:'${escapeForAttr(a.icao_code ?? '')}', latitude:${lat}, longitude:${lng}})\"> 
                    <i class=\"fa-solid fa-circle-info\"></i> 
                    Details anzeigen
                </button>
            </div>`;
        marker.bindPopup(`<div class=\"font-medium\">${escapeHtml(a.name)}</div>${codes ? `<div class=\"text-xs text-gray-600\">${escapeHtml(codes)}</div>` : ''}${detailsBtn}`);
        window.airportResultsLayer.addLayer(marker);
        boundsPoints.push([lat, lng]);
    });
    if (boundsPoints.length) {
        const bounds = L.latLngBounds(boundsPoints);
        try { map.fitBounds(bounds, { padding: [40, 40], maxZoom: 7 }); } catch (e) {}
    }
}

// Load country boundaries from GeoJSON
async function loadCountryBoundaries() {
    console.log('Loading country boundaries...');

    if (countryGeoJsonData) {
        console.log('Country boundaries already loaded');
        return true;
    }

    try {
        // Using local GeoJSON file - faster and more reliable
        const response = await fetch('/api/countries-geojson');
        if (response.ok) {
            countryGeoJsonData = await response.json();
            console.log('Country boundaries loaded from local file');
            console.log('Total countries in GeoJSON:', countryGeoJsonData.features.length);

            // Log some sample country names to check the data structure
            if (countryGeoJsonData.features && countryGeoJsonData.features.length > 0) {
                const sampleCountries = countryGeoJsonData.features.slice(0, 5).map(f => ({
                    name: f.properties?.name || f.properties?.NAME || f.properties?.ADMIN,
                    props: Object.keys(f.properties || {})
                }));
                console.log('Sample countries:', sampleCountries);
            }

            return true;
        }
    } catch (error) {
        console.error('Error loading local GeoJSON:', error);

        // Fallback to remote sources if local file fails
        try {
            const response = await fetch('https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_50m_admin_0_countries.geojson');
            if (response.ok) {
                countryGeoJsonData = await response.json();
                console.log('Country boundaries loaded from Natural Earth (fallback)');
                return true;
            }
        } catch (fallbackError) {
            console.error('All sources failed:', fallbackError);
            return false;
        }
    }
    return false;
}

// Country name mapping - will be loaded from database
let countryNameMapping = {
    'deutschland': ['germany', 'federal republic of germany', 'bundesrepublik deutschland'],
    'frankreich': ['france', 'french republic'],
    'spanien': ['spain', 'kingdom of spain'],
    'italien': ['italy', 'italian republic'],
    'österreich': ['austria', 'republic of austria'],
    'schweiz': ['switzerland', 'swiss confederation'],
    'niederlande': ['netherlands', 'kingdom of the netherlands', 'holland'],
    'belgien': ['belgium', 'kingdom of belgium'],
    'polen': ['poland', 'republic of poland'],
    'tschechien': ['czech republic', 'czechia'],
    'dänemark': ['denmark', 'kingdom of denmark'],
    'schweden': ['sweden', 'kingdom of sweden'],
    'norwegen': ['norway', 'kingdom of norway'],
    'finnland': ['finland', 'republic of finland'],
    'griechenland': ['greece', 'hellenic republic'],
    'portugal': ['portugal', 'portuguese republic'],
    'ungarn': ['hungary', 'republic of hungary'],
    'rumänien': ['romania'],
    'bulgarien': ['bulgaria', 'republic of bulgaria'],
    'kroatien': ['croatia', 'republic of croatia'],
    'serbien': ['serbia', 'republic of serbia'],
    'slowenien': ['slovenia', 'republic of slovenia'],
    'slowakei': ['slovakia', 'slovak republic'],
    'luxemburg': ['luxembourg', 'grand duchy of luxembourg'],
    'irland': ['ireland', 'republic of ireland'],
    'vereinigtes königreich': ['united kingdom', 'uk', 'great britain'],
    'großbritannien': ['united kingdom', 'uk', 'great britain'],
    'russland': ['russia', 'russian federation'],
    'ukraine': ['ukraine'],
    'türkei': ['turkey', 'republic of turkey', 'türkiye'],
    'usa': ['united states', 'united states of america', 'usa', 'us'],
    'vereinigte staaten': ['united states', 'united states of america', 'usa', 'us'],
    'kanada': ['canada'],
    'mexiko': ['mexico', 'united mexican states'],
    'brasilien': ['brazil', 'federative republic of brazil'],
    'argentinien': ['argentina', 'argentine republic'],
    'chile': ['chile', 'republic of chile'],
    'peru': ['peru', 'republic of peru'],
    'kolumbien': ['colombia', 'republic of colombia'],
    'venezuela': ['venezuela', 'bolivarian republic of venezuela'],
    'china': ['china', "people's republic of china"],
    'japan': ['japan'],
    'südkorea': ['south korea', 'republic of korea', 'korea, south'],
    'nordkorea': ['north korea', "democratic people's republic of korea", 'korea, north'],
    'indien': ['india', 'republic of india'],
    'australien': ['australia', 'commonwealth of australia'],
    'neuseeland': ['new zealand'],
    'ägypten': ['egypt', 'arab republic of egypt'],
    'südafrika': ['south africa', 'republic of south africa'],
    'marokko': ['morocco', 'kingdom of morocco'],
    'saudi-arabien': ['saudi arabia', 'kingdom of saudi arabia'],
    'vereinigte arabische emirate': ['united arab emirates', 'uae'],
    'israel': ['israel', 'state of israel'],
    // Weitere wichtige Länder
    'bangladesch': ['bangladesh', "people's republic of bangladesh"],
    'kap verde': ['cape verde', 'cabo verde', 'republic of cabo verde'],
    'kapverden': ['cape verde', 'cabo verde', 'republic of cabo verde'],
    'kapverdische inseln': ['cape verde', 'cabo verde'],
    'elfenbeinküste': ['ivory coast', "côte d'ivoire", 'cote divoire'],
    'tansania': ['tanzania', 'united republic of tanzania'],
    'kenia': ['kenya', 'republic of kenya'],
    'äthiopien': ['ethiopia', 'federal democratic republic of ethiopia'],
    'nigeria': ['nigeria', 'federal republic of nigeria'],
    'ghana': ['ghana', 'republic of ghana'],
    'kamerun': ['cameroon', 'republic of cameroon'],
    'algerien': ['algeria', "people's democratic republic of algeria"],
    'tunesien': ['tunisia', 'tunisian republic'],
    'libyen': ['libya', 'state of libya'],
    'sudan': ['sudan', 'republic of the sudan'],
    'südsudan': ['south sudan', 'republic of south sudan'],
    'somalia': ['somalia', 'federal republic of somalia'],
    'uganda': ['uganda', 'republic of uganda'],
    'ruanda': ['rwanda', 'republic of rwanda'],
    'burundi': ['burundi', 'republic of burundi'],
    'angola': ['angola', 'republic of angola'],
    'mosambik': ['mozambique', 'republic of mozambique'],
    'simbabwe': ['zimbabwe', 'republic of zimbabwe'],
    'sambia': ['zambia', 'republic of zambia'],
    'malawi': ['malawi', 'republic of malawi'],
    'madagaskar': ['madagascar', 'republic of madagascar'],
    'mauritius': ['mauritius', 'republic of mauritius'],
    'seychellen': ['seychelles', 'republic of seychelles'],
    'komoren': ['comoros', 'union of the comoros'],
    // Asien
    'indonesien': ['indonesia', 'republic of indonesia'],
    'philippinen': ['philippines', 'republic of the philippines'],
    'vietnam': ['vietnam', 'socialist republic of vietnam', 'viet nam'],
    'thailand': ['thailand', 'kingdom of thailand'],
    'myanmar': ['myanmar', 'republic of the union of myanmar', 'burma'],
    'malaysia': ['malaysia'],
    'singapur': ['singapore', 'republic of singapore'],
    'kambodscha': ['cambodia', 'kingdom of cambodia'],
    'laos': ['laos', "lao people's democratic republic"],
    'pakistan': ['pakistan', 'islamic republic of pakistan'],
    'afghanistan': ['afghanistan', 'islamic republic of afghanistan'],
    'iran': ['iran', 'islamic republic of iran'],
    'irak': ['iraq', 'republic of iraq'],
    'syrien': ['syria', 'syrian arab republic'],
    'jemen': ['yemen', 'republic of yemen'],
    'oman': ['oman', 'sultanate of oman'],
    'katar': ['qatar', 'state of qatar'],
    'kuwait': ['kuwait', 'state of kuwait'],
    'bahrain': ['bahrain', 'kingdom of bahrain'],
    'jordanien': ['jordan', 'hashemite kingdom of jordan'],
    'libanon': ['lebanon', 'lebanese republic'],
    'georgien': ['georgia'],
    'armenien': ['armenia', 'republic of armenia'],
    'aserbaidschan': ['azerbaijan', 'republic of azerbaijan'],
    'kasachstan': ['kazakhstan', 'republic of kazakhstan'],
    'usbekistan': ['uzbekistan', 'republic of uzbekistan'],
    'turkmenistan': ['turkmenistan'],
    'tadschikistan': ['tajikistan', 'republic of tajikistan'],
    'kirgisistan': ['kyrgyzstan', 'kyrgyz republic'],
    'mongolei': ['mongolia'],
    'nepal': ['nepal', 'federal democratic republic of nepal'],
    'bhutan': ['bhutan', 'kingdom of bhutan'],
    'sri lanka': ['sri lanka', 'democratic socialist republic of sri lanka'],
    'malediven': ['maldives', 'republic of maldives'],
    // Mittel- und Südamerika
    'kuba': ['cuba', 'republic of cuba'],
    'jamaika': ['jamaica'],
    'haiti': ['haiti', 'republic of haiti'],
    'dominikanische republik': ['dominican republic'],
    'puerto rico': ['puerto rico'],
    'trinidad und tobago': ['trinidad and tobago', 'republic of trinidad and tobago'],
    'barbados': ['barbados'],
    'panama': ['panama', 'republic of panama'],
    'costa rica': ['costa rica', 'republic of costa rica'],
    'nicaragua': ['nicaragua', 'republic of nicaragua'],
    'honduras': ['honduras', 'republic of honduras'],
    'el salvador': ['el salvador', 'republic of el salvador'],
    'guatemala': ['guatemala', 'republic of guatemala'],
    'belize': ['belize'],
    'ecuador': ['ecuador', 'republic of ecuador'],
    'bolivien': ['bolivia', 'plurinational state of bolivia'],
    'paraguay': ['paraguay', 'republic of paraguay'],
    'uruguay': ['uruguay', 'oriental republic of uruguay'],
    'guyana': ['guyana', 'co-operative republic of guyana'],
    'suriname': ['suriname', 'republic of suriname'],
    'französisch-guayana': ['french guiana', 'guyane'],
    // Ozeanien
    'papua-neuguinea': ['papua new guinea', 'independent state of papua new guinea'],
    'fidschi': ['fiji', 'republic of fiji'],
    'salomonen': ['solomon islands'],
    'vanuatu': ['vanuatu', 'republic of vanuatu'],
    'samoa': ['samoa', 'independent state of samoa'],
    'tonga': ['tonga', 'kingdom of tonga'],
    'palau': ['palau', 'republic of palau'],
    'marshallinseln': ['marshall islands', 'republic of the marshall islands'],
    'mikronesien': ['micronesia', 'federated states of micronesia'],
    'nauru': ['nauru', 'republic of nauru'],
    'tuvalu': ['tuvalu'],
    'kiribati': ['kiribati', 'republic of kiribati'],
    // Europa weitere
    'estland': ['estonia', 'republic of estonia'],
    'lettland': ['latvia', 'republic of latvia'],
    'litauen': ['lithuania', 'republic of lithuania'],
    'weißrussland': ['belarus', 'republic of belarus', 'white russia'],
    'moldawien': ['moldova', 'republic of moldova', 'moldavia'],
    'nordmazedonien': ['north macedonia', 'republic of north macedonia', 'macedonia'],
    'kosovo': ['kosovo', 'republic of kosovo'],
    'bosnien und herzegowina': ['bosnia and herzegovina'],
    'montenegro': ['montenegro'],
    'albanien': ['albania', 'republic of albania'],
    'malta': ['malta', 'republic of malta'],
    'zypern': ['cyprus', 'republic of cyprus'],
    'island': ['iceland', 'republic of iceland'],
    'färöer': ['faroe islands', 'faroes'],
    'grönland': ['greenland']
};

// Update country overlays based on selected countries
async function updateCountryOverlays() {
    console.log('=== updateCountryOverlays called ===');
    console.log('Selected countries:', Array.from(window.selectedCountries.entries()));

    if (!countryOverlaysLayer) {
        console.error('countryOverlaysLayer not initialized');
        return;
    }

    if (!countryGeoJsonData) {
        console.warn('GeoJSON data not loaded yet, loading now...');
        // Load and wait for completion
        await loadCountryBoundaries();

        // Check again after loading
        if (!countryGeoJsonData) {
            console.error('Failed to load GeoJSON data');
            return;
        }
    }

    // Clear existing overlays
    countryOverlaysLayer.clearLayers();

    if (window.selectedCountries.size === 0) {
        console.log('No countries selected');
        return;
    }

    let matchedCountries = [];
    let unmatchedSelections = new Map(window.selectedCountries);

    // Debug: Log all available countries in GeoJSON
    if (!window.debuggedCountries) {
        console.log('Available countries in GeoJSON (first 10):');
        countryGeoJsonData.features.slice(0, 10).forEach(f => {
            const iso2 = f.properties['ISO3166-1-Alpha-2'] || f.properties.ISO_A2 || f.properties.iso_a2;
            console.log(`- ${f.properties.name || f.properties.NAME || f.properties.ADMIN} (ISO: ${iso2})`);
        });
        window.debuggedCountries = true;
    }

    // Add overlays for selected countries
    countryGeoJsonData.features.forEach(feature => {
        const props = feature.properties;

        // Get ISO-2 code from GeoJSON (try different possible property names)
        const geoJsonISO2 = (props['ISO3166-1-Alpha-2'] || props.ISO_A2 || props.iso_a2 || '').toUpperCase();
        const geoJsonName = (props.name || props.NAME || props.ADMIN || props.name_long || props.NAME_LONG || '').toLowerCase().trim();

        // Special debug for Netherlands/Greenland issue
        if (geoJsonName.includes('netherland') || geoJsonName.includes('greenland') ||
            geoJsonName.includes('groenland') || geoJsonName.includes('niederlande')) {
            console.log('DEBUG - Potential match:', {
                geoJsonName: geoJsonName,
                originalName: props.name || props.NAME,
                allProps: props
            });
        }

        // Check if this country is selected by ISO code (much more reliable!)
        let matchedSelection = null;
        let isSelected = false;

        // First, try to match by ISO-2 code (most reliable method)
        if (geoJsonISO2) {
            for (const [name, data] of window.selectedCountries.entries()) {
                if (data.iso2 && data.iso2.toUpperCase() === geoJsonISO2) {
                    matchedSelection = name;
                    isSelected = true;
                    break;
                }
            }
        }

        // If no ISO match found, fall back to name matching (for countries without ISO codes in our data)
        if (!isSelected) {
            for (const [name, data] of window.selectedCountries.entries()) {
                const selectedLower = name.toLowerCase().trim();

                // Direct EXACT match first
                if (geoJsonName === selectedLower) {
                    matchedSelection = name;
                    isSelected = true;
                    break;
                }

                // Check if selected is a German name
                if (countryNameMapping[selectedLower]) {
                    // Check if GeoJSON name matches any of the English variants EXACTLY
                    const matches = countryNameMapping[selectedLower].some(englishName => {
                        const englishLower = englishName.toLowerCase();
                        return geoJsonName === englishLower;
                    });
                    if (matches) {
                        matchedSelection = name;
                        isSelected = true;
                        break;
                    }
                }

                // Check reverse mapping (if selected is English, check for German)
                for (const [germanName, englishNames] of Object.entries(countryNameMapping)) {
                    if (englishNames.some(n => n.toLowerCase() === selectedLower)) {
                        if (geoJsonName === germanName || englishNames.some(n =>
                            geoJsonName === n.toLowerCase()
                        )) {
                            matchedSelection = name;
                            isSelected = true;
                            break;
                        }
                    }
                }
                if (isSelected) break;
            }
        }

        if (isSelected && matchedSelection) {
            const displayName = props.name || props.NAME || props.ADMIN;
            console.log('Match found! Selected:', matchedSelection, '-> GeoJSON:', displayName, 'ISO:', geoJsonISO2);
            matchedCountries.push(displayName);
            unmatchedSelections.delete(matchedSelection);

            const countryLayer = L.geoJSON(feature, {
                style: {
                    fillColor: '#3B82F6',  // Blue color
                    fillOpacity: 0.3,      // Slightly more visible
                    color: '#1E40AF',      // Darker blue border
                    weight: 3,             // Thicker border
                    opacity: 0.8
                },
                onEachFeature: (feature, layer) => {
                    layer.bindPopup(`<strong>${displayName}</strong>`);
                }
            });
            countryOverlaysLayer.addLayer(countryLayer);
        }
    });

    // Log unmatched selections for debugging
    if (unmatchedSelections.size > 0) {
        console.warn('Could not find overlays for:', Array.from(unmatchedSelections));
    }

    console.log('Successfully matched countries:', matchedCountries);
    console.log('Country overlay layers count:', countryOverlaysLayer.getLayers().length);

    // Auto-zoom to show all selected countries
    if (countryOverlaysLayer.getLayers().length > 0) {
        console.log('Preparing to zoom to countries...');
        // Add a small delay to ensure layers are properly rendered
        setTimeout(() => {
            try {
                console.log('Getting bounds for zoom...');

                // Calculate bounds manually from all layers
                let bounds = null;
                const layers = countryOverlaysLayer.getLayers();
                console.log('Total layers in countryOverlaysLayer:', layers.length);

                layers.forEach((layer, index) => {
                    console.log(`Layer ${index}:`, layer);

                    // Try different methods to get bounds
                    let layerBounds = null;

                    if (layer.getBounds) {
                        // If the layer has getBounds method
                        layerBounds = layer.getBounds();
                    } else if (layer._layers) {
                        // If it's a nested layer group
                        Object.values(layer._layers).forEach(subLayer => {
                            if (subLayer.getBounds) {
                                const subBounds = subLayer.getBounds();
                                if (!layerBounds) {
                                    layerBounds = subBounds;
                                } else {
                                    layerBounds.extend(subBounds);
                                }
                            }
                        });
                    }

                    if (layerBounds) {
                        if (!bounds) {
                            bounds = layerBounds;
                        } else {
                            bounds.extend(layerBounds);
                        }
                        console.log(`Added bounds from layer ${index}:`, layerBounds.toBBoxString());
                    }
                });

                if (!bounds) {
                    console.error('No bounds could be calculated from layers');
                    return;
                }

                console.log('Calculated combined bounds:', bounds);
                console.log('Bounds valid:', bounds.isValid());
                console.log('Bounds details:', {
                    north: bounds.getNorth(),
                    south: bounds.getSouth(),
                    east: bounds.getEast(),
                    west: bounds.getWest()
                });

                if (bounds.isValid()) {
                    // Adjust zoom based on number of countries selected
                    const countryCount = matchedCountries.length;
                    let zoomOptions = {};

                    if (countryCount === 1) {
                        // Single country - zoom in closer
                        console.log('Zooming to single country...');
                        zoomOptions = {
                            padding: [100, 100],
                            maxZoom: 7,
                            animate: true,
                            duration: 0.8
                        };
                    } else if (countryCount === 2) {
                        // Two countries - check if they're far apart
                        const boundsSize = bounds.getNorthEast().distanceTo(bounds.getSouthWest());
                        console.log('Distance between countries:', boundsSize);

                        if (boundsSize > 5000000) { // More than 5000km apart
                            // Far apart countries (e.g., Germany and Canada)
                            zoomOptions = {
                                padding: [50, 50],
                                maxZoom: 4,
                                animate: true,
                                duration: 0.8
                            };
                        } else {
                            // Nearby countries
                            zoomOptions = {
                                padding: [80, 80],
                                maxZoom: 6,
                                animate: true,
                                duration: 0.8
                            };
                        }
                    } else {
                        // Multiple countries
                        zoomOptions = {
                            padding: [60, 60],
                            maxZoom: 5,
                            animate: true,
                            duration: 0.8
                        };
                    }

                    console.log('Zoom options:', zoomOptions);
                    console.log('Fitting bounds to:', bounds.toBBoxString());

                    // Force the zoom by directly fitting bounds
                    try {
                        map.fitBounds(bounds, zoomOptions);
                        console.log('✓ Map zoom completed');

                        // Double-check zoom happened
                        setTimeout(() => {
                            const newZoom = map.getZoom();
                            const center = map.getCenter();
                            console.log('New map state - Zoom:', newZoom, 'Center:', center);
                        }, 1000);
                    } catch (zoomError) {
                        console.error('Error during zoom:', zoomError);
                        // Fallback: try simple setView
                        const center = bounds.getCenter();
                        map.setView(center, 5, { animate: true });
                        console.log('Used fallback zoom to center:', center);
                    }
                } else {
                    console.error('Invalid bounds for country layers');
                }
            } catch (e) {
                console.error('Error fitting bounds to countries:', e);
                console.error('Error details:', e.stack);
            }
        }, 100); // Small delay to ensure layers are rendered
    } else {
        console.log('No country layers added to map');
        // If no countries selected, reset to default view
        if (window.selectedCountries.size === 0 && initialMapView) {
            map.setView(initialMapView.center, initialMapView.zoom, {
                animate: true,
                duration: 0.8
            });
        }
    }
}

// Test function to manually trigger zoom to Germany
window.testZoomToGermany = function() {
    console.log('Testing zoom to Germany...');
    if (!map) {
        console.error('Map not initialized');
        return;
    }

    // Germany approximate bounds
    const germanyBounds = L.latLngBounds(
        [47.2701, 5.8663],  // Southwest
        [55.0585, 15.0419]  // Northeast
    );

    console.log('Germany bounds:', germanyBounds);

    try {
        map.fitBounds(germanyBounds, {
            padding: [100, 100],
            maxZoom: 6,
            animate: true,
            duration: 1
        });
        console.log('Zoom to Germany completed');
    } catch (e) {
        console.error('Zoom failed:', e);
    }
};

// Test function to check if zoom works at all
window.testBasicZoom = function(lat, lng, zoomLevel) {
    console.log(`Testing zoom to ${lat}, ${lng} at level ${zoomLevel}`);
    if (!map) {
        console.error('Map not initialized');
        return;
    }

    map.setView([lat, lng], zoomLevel, {
        animate: true,
        duration: 1
    });
    console.log('Basic zoom completed');
};

console.log('Risk Management Dashboard loaded with GDACS API integration');
console.log('Test functions available:');
console.log('- testZoomToGermany() : Zoom to Germany');
console.log('- testBasicZoom(lat, lng, zoom) : Test basic zoom');
</script>
</body>
</html>
