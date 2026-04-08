@extends('docs.api.layout')

@section('title', 'Folder Import API')
@section('api_color', '#3b82f6')

@section('sidebar')
    <span class="sidebar-heading">Folder Import API</span>
    <a href="#uebersicht">Übersicht</a>
    <a href="#authentifizierung">Authentifizierung</a>
    <a href="#base-url">Base-URL</a>
    <a href="#folder-importieren">Folder importieren</a>

    <span class="sidebar-heading">Datenstrukturen</span>
    <a href="#request-struktur">Request-Struktur</a>
    <a href="#daten-struktur">Daten-Struktur (data)</a>
    <a href="#folder-vorgangsdaten">Folder (Vorgangsdaten)</a>
    <a href="#customer-kundendaten">Customer (Kundendaten)</a>
    <a href="#participant-reiseteilnehmer">Participant (Reiseteilnehmer)</a>
    <a href="#itinerary-reiseleistung">Itinerary (Reiseleistung)</a>
    <a href="#hotel">Hotel</a>
    <a href="#flight-flug">Flight (Flug)</a>
    <a href="#flight-segment">Flight Segment</a>
    <a href="#ship-kreuzfahrt">Ship (Kreuzfahrt)</a>
    <a href="#port-call-hafenstopp">Port Call (Hafenstopp)</a>
    <a href="#car-rental-mietwagen">Car Rental (Mietwagen)</a>

    <span class="sidebar-heading">Beispiele</span>
    <a href="#beispiel-minimal">Minimaler Import (nur Hotel)</a>
    <a href="#beispiel-vollstaendig">Vollständiger Import (Hotel + Flug)</a>

    <span class="sidebar-heading">Status & Fehler</span>
    <a href="#import-status">Import-Status abfragen</a>
    <a href="#import-liste">Liste aller Imports</a>
    <a href="#fehlercodes">Fehlercodes</a>
    <a href="#automatische-features">Automatische Features</a>
    <a href="#support">Support</a>
@endsection

@section('content')

    {{-- ===== Übersicht ===== --}}
    <h1 id="uebersicht">Folder Import API</h1>
    <p>Die Folder Import API ermöglicht den Import von Reisedaten (Folders) mit Hotels, Flügen, Kreuzfahrten und Mietwagen. Der Import läuft queue-basiert im Hintergrund und bietet automatisches Airport-Matching, Country-Matching, Timeline-Generierung und Geocoding.</p>

    <hr>

    {{-- ===== Authentifizierung ===== --}}
    <h2 id="authentifizierung">Authentifizierung</h2>
    <p>Alle API-Aufrufe erfordern einen <strong>Bearer-Token</strong> im HTTP-Header:</p>

    <div class="code-block">
        <span class="code-label">Header</span>
        <pre><code>Authorization: Bearer {API_TOKEN}</code></pre>
    </div>

    <h3>Token generieren</h3>
    <p>Der Token wird über die Web-Oberfläche generiert (erfordert eine aktive Session):</p>

    <div class="endpoint-block">
        <span class="method method-post">POST</span>
        <span class="path">/customer/api-tokens/generate</span>
    </div>

    <p><strong>Response:</strong></p>

    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "token": "2|RHej0fNgjGSzvPrEcSuY7nMGI7fldCnOMoBrpl2T173373b5",
  "message": "API Token erfolgreich generiert"
}</code></pre>
    </div>

    <blockquote>
        <strong>Wichtig:</strong> Speichern Sie den Token sicher ab. Er wird nur einmal im Klartext angezeigt.
    </blockquote>

    <hr>

    {{-- ===== Base-URL ===== --}}
    <h2 id="base-url">Base-URL</h2>

    <div class="code-block">
        <pre><code>https://api.global-travel-monitor.de</code></pre>
    </div>

    <p>Alternativ ist die API auch unter <code>https://global-travel-monitor.eu/api</code> erreichbar. Wir empfehlen die Verwendung der API-Subdomain für neue Integrationen.</p>

    <hr>

    {{-- ===== Folder importieren ===== --}}
    <h2 id="folder-importieren">Folder importieren</h2>

    <div class="endpoint-block">
        <span class="method method-post">POST</span>
        <span class="path">/v1/folders/import</span>
    </div>

    <p>Importiert einen kompletten Folder mit allen zugehörigen Daten. Der Import wird in eine Queue eingereiht und im Hintergrund verarbeitet. Die Response enthält eine <code>log_id</code> zum Status-Tracking.</p>

    {{-- Request-Struktur --}}
    <h3 id="request-struktur">Request-Struktur</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>source</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>Import-Quelle: <code>api</code>, <code>file</code>, <code>manual</code></td>
                </tr>
                <tr>
                    <td><code>provider</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>Name des Datenlieferanten (max. 128 Zeichen)</td>
                </tr>
                <tr>
                    <td><code>data</code></td>
                    <td>object</td>
                    <td>Ja</td>
                    <td>Die eigentlichen Reisedaten (siehe unten)</td>
                </tr>
                <tr>
                    <td><code>mapping_config</code></td>
                    <td>object</td>
                    <td>Nein</td>
                    <td>Optionale Mapping-Konfiguration</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Daten-Struktur --}}
    <h3 id="daten-struktur">Daten-Struktur (<code>data</code>)</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>folder</code></td>
                    <td>object</td>
                    <td>Ja</td>
                    <td>Vorgangsdaten</td>
                </tr>
                <tr>
                    <td><code>customer</code></td>
                    <td>object</td>
                    <td>Nein</td>
                    <td>Kundendaten</td>
                </tr>
                <tr>
                    <td><code>participants</code></td>
                    <td>array</td>
                    <td>Nein</td>
                    <td>Reiseteilnehmer</td>
                </tr>
                <tr>
                    <td><code>itineraries</code></td>
                    <td>array</td>
                    <td>Ja</td>
                    <td>Reiseleistungen (Hotels, Flüge, etc.)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ===== Folder (Vorgangsdaten) ===== --}}
    <h3 id="folder-vorgangsdaten">Folder (Vorgangsdaten)</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>folder_number</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Eindeutige Vorgangsnummer (wird automatisch generiert)</td>
                </tr>
                <tr>
                    <td><code>folder_name</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Name der Reise (max. 255 Zeichen)</td>
                </tr>
                <tr>
                    <td><code>travel_start_date</code></td>
                    <td>date</td>
                    <td>Nein</td>
                    <td>Reisebeginn (YYYY-MM-DD)</td>
                </tr>
                <tr>
                    <td><code>travel_end_date</code></td>
                    <td>date</td>
                    <td>Nein</td>
                    <td>Reiseende (YYYY-MM-DD)</td>
                </tr>
                <tr>
                    <td><code>primary_destination</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Hauptreiseziel</td>
                </tr>
                <tr>
                    <td><code>status</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td><code>draft</code>, <code>confirmed</code>, <code>active</code>, <code>completed</code>, <code>cancelled</code> (Standard: <code>draft</code>)</td>
                </tr>
                <tr>
                    <td><code>travel_type</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td><code>business</code>, <code>leisure</code>, <code>mixed</code> (Standard: <code>leisure</code>)</td>
                </tr>
                <tr>
                    <td><code>agent_name</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Name des Bearbeiters</td>
                </tr>
                <tr>
                    <td><code>notes</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Notizen</td>
                </tr>
                <tr>
                    <td><code>currency</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Währung als ISO-Code (Standard: <code>EUR</code>)</td>
                </tr>
                <tr>
                    <td><code>custom_field_1_label</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Label für eigenes Feld 1 (max. 100 Zeichen)</td>
                </tr>
                <tr>
                    <td><code>custom_field_1_value</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Wert für eigenes Feld 1</td>
                </tr>
                <tr>
                    <td><code>custom_field_2_label</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Label für eigenes Feld 2</td>
                </tr>
                <tr>
                    <td><code>custom_field_2_value</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Wert für eigenes Feld 2</td>
                </tr>
                <tr>
                    <td><code>custom_field_3_label</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Label für eigenes Feld 3</td>
                </tr>
                <tr>
                    <td><code>custom_field_3_value</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Wert für eigenes Feld 3</td>
                </tr>
                <tr>
                    <td><code>custom_field_4_label</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Label für eigenes Feld 4</td>
                </tr>
                <tr>
                    <td><code>custom_field_4_value</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Wert für eigenes Feld 4</td>
                </tr>
                <tr>
                    <td><code>custom_field_5_label</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Label für eigenes Feld 5</td>
                </tr>
                <tr>
                    <td><code>custom_field_5_value</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Wert für eigenes Feld 5</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ===== Customer (Kundendaten) ===== --}}
    <h3 id="customer-kundendaten">Customer (Kundendaten)</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>salutation</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Anrede: <code>mr</code>, <code>mrs</code>, <code>diverse</code> (auch <code>Herr</code>, <code>Frau</code>, <code>Divers</code> wird gemappt)</td>
                </tr>
                <tr>
                    <td><code>title</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Titel (max. 64 Zeichen)</td>
                </tr>
                <tr>
                    <td><code>first_name</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>Vorname (max. 128 Zeichen)</td>
                </tr>
                <tr>
                    <td><code>last_name</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>Nachname (max. 128 Zeichen)</td>
                </tr>
                <tr>
                    <td><code>email</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>E-Mail-Adresse</td>
                </tr>
                <tr>
                    <td><code>phone</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Telefonnummer</td>
                </tr>
                <tr>
                    <td><code>mobile</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Mobilnummer</td>
                </tr>
                <tr>
                    <td><code>street</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Straße</td>
                </tr>
                <tr>
                    <td><code>house_number</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Hausnummer</td>
                </tr>
                <tr>
                    <td><code>postal_code</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Postleitzahl</td>
                </tr>
                <tr>
                    <td><code>city</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Stadt</td>
                </tr>
                <tr>
                    <td><code>country_code</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Ländercode (ISO alpha-2, z.B. <code>DE</code>)</td>
                </tr>
                <tr>
                    <td><code>birth_date</code></td>
                    <td>date</td>
                    <td>Nein</td>
                    <td>Geburtsdatum (YYYY-MM-DD)</td>
                </tr>
                <tr>
                    <td><code>nationality</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Staatsangehörigkeit (ISO alpha-2)</td>
                </tr>
                <tr>
                    <td><code>notes</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Notizen</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ===== Participant (Reiseteilnehmer) ===== --}}
    <h3 id="participant-reiseteilnehmer">Participant (Reiseteilnehmer)</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>salutation</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td><code>mr</code>, <code>mrs</code>, <code>child</code>, <code>infant</code>, <code>diverse</code></td>
                </tr>
                <tr>
                    <td><code>title</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Titel</td>
                </tr>
                <tr>
                    <td><code>first_name</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>Vorname</td>
                </tr>
                <tr>
                    <td><code>last_name</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>Nachname</td>
                </tr>
                <tr>
                    <td><code>birth_date</code></td>
                    <td>date</td>
                    <td>Nein</td>
                    <td>Geburtsdatum</td>
                </tr>
                <tr>
                    <td><code>nationality</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Staatsangehörigkeit (ISO alpha-2)</td>
                </tr>
                <tr>
                    <td><code>passport_number</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Reisepassnummer</td>
                </tr>
                <tr>
                    <td><code>passport_issue_date</code></td>
                    <td>date</td>
                    <td>Nein</td>
                    <td>Ausstellungsdatum Pass</td>
                </tr>
                <tr>
                    <td><code>passport_expiry_date</code></td>
                    <td>date</td>
                    <td>Nein</td>
                    <td>Ablaufdatum Pass</td>
                </tr>
                <tr>
                    <td><code>passport_issuing_country</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Ausstellungsland Pass (ISO alpha-2)</td>
                </tr>
                <tr>
                    <td><code>email</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>E-Mail-Adresse</td>
                </tr>
                <tr>
                    <td><code>phone</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Telefonnummer</td>
                </tr>
                <tr>
                    <td><code>dietary_requirements</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Ernährungsanforderungen</td>
                </tr>
                <tr>
                    <td><code>medical_conditions</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Medizinische Hinweise</td>
                </tr>
                <tr>
                    <td><code>notes</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Notizen</td>
                </tr>
                <tr>
                    <td><code>is_main_contact</code></td>
                    <td>boolean</td>
                    <td>Nein</td>
                    <td>Hauptansprechpartner (Standard: <code>false</code>)</td>
                </tr>
                <tr>
                    <td><code>participant_type</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td><code>adult</code>, <code>child</code>, <code>infant</code> (Standard: <code>adult</code>)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ===== Itinerary (Reiseleistung) ===== --}}
    <h3 id="itinerary-reiseleistung">Itinerary (Reiseleistung)</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>booking_reference</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Buchungsreferenz</td>
                </tr>
                <tr>
                    <td><code>itinerary_name</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Name der Leistung</td>
                </tr>
                <tr>
                    <td><code>start_date</code></td>
                    <td>date</td>
                    <td>Nein</td>
                    <td>Startdatum</td>
                </tr>
                <tr>
                    <td><code>end_date</code></td>
                    <td>date</td>
                    <td>Nein</td>
                    <td>Enddatum</td>
                </tr>
                <tr>
                    <td><code>status</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td><code>pending</code>, <code>confirmed</code>, <code>cancelled</code>, <code>completed</code> (Standard: <code>pending</code>)</td>
                </tr>
                <tr>
                    <td><code>provider_name</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Anbietername</td>
                </tr>
                <tr>
                    <td><code>provider_reference</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Anbieterreferenz</td>
                </tr>
                <tr>
                    <td><code>currency</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Währung (Standard: <code>EUR</code>)</td>
                </tr>
                <tr>
                    <td><code>notes</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Notizen</td>
                </tr>
                <tr>
                    <td><code>hotels</code></td>
                    <td>array</td>
                    <td>Nein</td>
                    <td>Hotels (siehe unten)</td>
                </tr>
                <tr>
                    <td><code>flights</code></td>
                    <td>array</td>
                    <td>Nein</td>
                    <td>Flüge (siehe unten)</td>
                </tr>
                <tr>
                    <td><code>ships</code></td>
                    <td>array</td>
                    <td>Nein</td>
                    <td>Kreuzfahrten (siehe unten)</td>
                </tr>
                <tr>
                    <td><code>car_rentals</code></td>
                    <td>array</td>
                    <td>Nein</td>
                    <td>Mietwagen (siehe unten)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ===== Hotel ===== --}}
    <h3 id="hotel">Hotel</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>hotel_name</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>Hotelname</td>
                </tr>
                <tr>
                    <td><code>hotel_code</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Hotel-Code</td>
                </tr>
                <tr>
                    <td><code>hotel_code_type</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Typ des Hotel-Codes</td>
                </tr>
                <tr>
                    <td><code>street</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Straße</td>
                </tr>
                <tr>
                    <td><code>postal_code</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Postleitzahl</td>
                </tr>
                <tr>
                    <td><code>city</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Stadt</td>
                </tr>
                <tr>
                    <td><code>country_code</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Ländercode (ISO alpha-2)</td>
                </tr>
                <tr>
                    <td><code>lat</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Breitengrad (-90 bis 90)</td>
                </tr>
                <tr>
                    <td><code>lng</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Längengrad (-180 bis 180)</td>
                </tr>
                <tr>
                    <td><code>check_in_date</code></td>
                    <td>date</td>
                    <td>Ja</td>
                    <td>Check-in-Datum</td>
                </tr>
                <tr>
                    <td><code>check_out_date</code></td>
                    <td>date</td>
                    <td>Ja</td>
                    <td>Check-out-Datum</td>
                </tr>
                <tr>
                    <td><code>nights</code></td>
                    <td>integer</td>
                    <td>Nein</td>
                    <td>Anzahl Nächte</td>
                </tr>
                <tr>
                    <td><code>room_type</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Zimmertyp</td>
                </tr>
                <tr>
                    <td><code>room_count</code></td>
                    <td>integer</td>
                    <td>Nein</td>
                    <td>Zimmeranzahl (Standard: 1)</td>
                </tr>
                <tr>
                    <td><code>board_type</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Verpflegung (z.B. "All Inclusive")</td>
                </tr>
                <tr>
                    <td><code>booking_reference</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Buchungsreferenz</td>
                </tr>
                <tr>
                    <td><code>total_amount</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Gesamtbetrag</td>
                </tr>
                <tr>
                    <td><code>currency</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Währung (Standard: <code>EUR</code>)</td>
                </tr>
                <tr>
                    <td><code>status</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td><code>pending</code>, <code>confirmed</code>, <code>cancelled</code> (Standard: <code>pending</code>)</td>
                </tr>
                <tr>
                    <td><code>notes</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Notizen</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ===== Flight (Flug) ===== --}}
    <h3 id="flight-flug">Flight (Flug)</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>booking_reference</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Buchungsreferenz</td>
                </tr>
                <tr>
                    <td><code>service_type</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td><code>outbound</code>, <code>return</code>, <code>multi_leg</code> (Standard: <code>outbound</code>)</td>
                </tr>
                <tr>
                    <td><code>airline_pnr</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Airline PNR</td>
                </tr>
                <tr>
                    <td><code>ticket_numbers</code></td>
                    <td>array</td>
                    <td>Nein</td>
                    <td>Ticketnummern</td>
                </tr>
                <tr>
                    <td><code>total_amount</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Gesamtbetrag</td>
                </tr>
                <tr>
                    <td><code>currency</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Währung (Standard: <code>EUR</code>)</td>
                </tr>
                <tr>
                    <td><code>status</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td><code>pending</code>, <code>ticketed</code>, <code>cancelled</code> (Standard: <code>pending</code>)</td>
                </tr>
                <tr>
                    <td><code>segments</code></td>
                    <td>array</td>
                    <td>Ja</td>
                    <td>Flugsegmente (mindestens 1)</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Flight Segment --}}
    <h4 id="flight-segment">Flight Segment</h4>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>segment_number</code></td>
                    <td>integer</td>
                    <td>Nein</td>
                    <td>Segmentnummer (Standard: 1)</td>
                </tr>
                <tr>
                    <td><code>departure_airport_code</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>IATA-Code Abflughafen (z.B. <code>MUC</code>) &ndash; automatisches Matching</td>
                </tr>
                <tr>
                    <td><code>departure_time</code></td>
                    <td>datetime</td>
                    <td>Ja</td>
                    <td>Abflugzeit</td>
                </tr>
                <tr>
                    <td><code>departure_terminal</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Terminal</td>
                </tr>
                <tr>
                    <td><code>arrival_airport_code</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>IATA-Code Zielflughafen (z.B. <code>PMI</code>) &ndash; automatisches Matching</td>
                </tr>
                <tr>
                    <td><code>arrival_time</code></td>
                    <td>datetime</td>
                    <td>Ja</td>
                    <td>Ankunftszeit</td>
                </tr>
                <tr>
                    <td><code>arrival_terminal</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Terminal</td>
                </tr>
                <tr>
                    <td><code>airline_code</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Airline-Code (z.B. <code>LH</code>)</td>
                </tr>
                <tr>
                    <td><code>flight_number</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Flugnummer</td>
                </tr>
                <tr>
                    <td><code>aircraft_type</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Flugzeugtyp (z.B. <code>A320</code>)</td>
                </tr>
                <tr>
                    <td><code>duration_minutes</code></td>
                    <td>integer</td>
                    <td>Nein</td>
                    <td>Flugdauer in Minuten</td>
                </tr>
                <tr>
                    <td><code>booking_class</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Buchungsklasse</td>
                </tr>
                <tr>
                    <td><code>cabin_class</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td><code>economy</code>, <code>premium_economy</code>, <code>business</code>, <code>first</code> (Standard: <code>economy</code>)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <blockquote>
        <strong>Hinweis:</strong> <code>departure_country_code</code>, <code>departure_lat</code>, <code>departure_lng</code>, <code>arrival_country_code</code>, <code>arrival_lat</code>, <code>arrival_lng</code> werden automatisch aus den IATA-Codes ermittelt.
    </blockquote>

    <hr>

    {{-- ===== Ship (Kreuzfahrt) ===== --}}
    <h3 id="ship-kreuzfahrt">Ship (Kreuzfahrt)</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>ship_name</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>Schiffsname</td>
                </tr>
                <tr>
                    <td><code>cruise_line</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Reederei</td>
                </tr>
                <tr>
                    <td><code>ship_code</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Schiffs-Code</td>
                </tr>
                <tr>
                    <td><code>embarkation_date</code></td>
                    <td>date</td>
                    <td>Ja</td>
                    <td>Einschiffungsdatum</td>
                </tr>
                <tr>
                    <td><code>disembarkation_date</code></td>
                    <td>date</td>
                    <td>Ja</td>
                    <td>Ausschiffungsdatum</td>
                </tr>
                <tr>
                    <td><code>nights</code></td>
                    <td>integer</td>
                    <td>Nein</td>
                    <td>Anzahl Nächte</td>
                </tr>
                <tr>
                    <td><code>embarkation_port</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Einschiffungshafen</td>
                </tr>
                <tr>
                    <td><code>embarkation_country_code</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Ländercode Einschiffung (ISO alpha-2)</td>
                </tr>
                <tr>
                    <td><code>embarkation_lat</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Breitengrad Einschiffung</td>
                </tr>
                <tr>
                    <td><code>embarkation_lng</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Längengrad Einschiffung</td>
                </tr>
                <tr>
                    <td><code>disembarkation_port</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Ausschiffungshafen</td>
                </tr>
                <tr>
                    <td><code>disembarkation_country_code</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Ländercode Ausschiffung</td>
                </tr>
                <tr>
                    <td><code>disembarkation_lat</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Breitengrad Ausschiffung</td>
                </tr>
                <tr>
                    <td><code>disembarkation_lng</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Längengrad Ausschiffung</td>
                </tr>
                <tr>
                    <td><code>cabin_number</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Kabinennummer</td>
                </tr>
                <tr>
                    <td><code>cabin_type</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Kabinentyp</td>
                </tr>
                <tr>
                    <td><code>cabin_category</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Kabinenkategorie</td>
                </tr>
                <tr>
                    <td><code>deck</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Deck</td>
                </tr>
                <tr>
                    <td><code>booking_reference</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Buchungsreferenz</td>
                </tr>
                <tr>
                    <td><code>total_amount</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Gesamtbetrag</td>
                </tr>
                <tr>
                    <td><code>currency</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Währung (Standard: <code>EUR</code>)</td>
                </tr>
                <tr>
                    <td><code>status</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td><code>pending</code>, <code>confirmed</code>, <code>cancelled</code> (Standard: <code>pending</code>)</td>
                </tr>
                <tr>
                    <td><code>port_calls</code></td>
                    <td>array</td>
                    <td>Nein</td>
                    <td>Hafenstopps (siehe unten)</td>
                </tr>
                <tr>
                    <td><code>notes</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Notizen</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Port Call --}}
    <h4 id="port-call-hafenstopp">Port Call (Hafenstopp)</h4>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>port</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Hafenname</td>
                </tr>
                <tr>
                    <td><code>country</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Ländercode (ISO alpha-2)</td>
                </tr>
                <tr>
                    <td><code>arrival</code></td>
                    <td>date</td>
                    <td>Nein</td>
                    <td>Ankunftsdatum</td>
                </tr>
                <tr>
                    <td><code>departure</code></td>
                    <td>date</td>
                    <td>Nein</td>
                    <td>Abreisedatum</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ===== Car Rental (Mietwagen) ===== --}}
    <h3 id="car-rental-mietwagen">Car Rental (Mietwagen)</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Feld</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>rental_company</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Mietwagenfirma</td>
                </tr>
                <tr>
                    <td><code>booking_reference</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Buchungsreferenz</td>
                </tr>
                <tr>
                    <td><code>pickup_location</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>Abholort</td>
                </tr>
                <tr>
                    <td><code>pickup_country_code</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Ländercode Abholung (ISO alpha-2)</td>
                </tr>
                <tr>
                    <td><code>pickup_lat</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Breitengrad Abholung</td>
                </tr>
                <tr>
                    <td><code>pickup_lng</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Längengrad Abholung</td>
                </tr>
                <tr>
                    <td><code>pickup_datetime</code></td>
                    <td>datetime</td>
                    <td>Ja</td>
                    <td>Abholdatum/-zeit</td>
                </tr>
                <tr>
                    <td><code>return_location</code></td>
                    <td>string</td>
                    <td>Ja</td>
                    <td>Rückgabeort</td>
                </tr>
                <tr>
                    <td><code>return_country_code</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Ländercode Rückgabe</td>
                </tr>
                <tr>
                    <td><code>return_lat</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Breitengrad Rückgabe</td>
                </tr>
                <tr>
                    <td><code>return_lng</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Längengrad Rückgabe</td>
                </tr>
                <tr>
                    <td><code>return_datetime</code></td>
                    <td>datetime</td>
                    <td>Ja</td>
                    <td>Rückgabedatum/-zeit</td>
                </tr>
                <tr>
                    <td><code>vehicle_category</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Fahrzeugkategorie</td>
                </tr>
                <tr>
                    <td><code>vehicle_type</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Fahrzeugtyp</td>
                </tr>
                <tr>
                    <td><code>vehicle_make_model</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Marke/Modell</td>
                </tr>
                <tr>
                    <td><code>transmission</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td><code>manual</code>, <code>automatic</code></td>
                </tr>
                <tr>
                    <td><code>fuel_type</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td><code>petrol</code>, <code>diesel</code>, <code>electric</code>, <code>hybrid</code></td>
                </tr>
                <tr>
                    <td><code>rental_days</code></td>
                    <td>integer</td>
                    <td>Nein</td>
                    <td>Mietdauer in Tagen</td>
                </tr>
                <tr>
                    <td><code>total_amount</code></td>
                    <td>number</td>
                    <td>Nein</td>
                    <td>Gesamtbetrag</td>
                </tr>
                <tr>
                    <td><code>currency</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Währung (Standard: <code>EUR</code>)</td>
                </tr>
                <tr>
                    <td><code>insurance_options</code></td>
                    <td>array</td>
                    <td>Nein</td>
                    <td>Versicherungsoptionen</td>
                </tr>
                <tr>
                    <td><code>extras</code></td>
                    <td>array</td>
                    <td>Nein</td>
                    <td>Zusatzleistungen</td>
                </tr>
                <tr>
                    <td><code>status</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td><code>pending</code>, <code>confirmed</code>, <code>picked_up</code>, <code>returned</code>, <code>cancelled</code> (Standard: <code>pending</code>)</td>
                </tr>
                <tr>
                    <td><code>notes</code></td>
                    <td>string</td>
                    <td>Nein</td>
                    <td>Notizen</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ===== Beispiele ===== --}}
    <h2 id="beispiele">Beispiele</h2>

    <h3 id="beispiel-minimal">Minimaler Import (nur Hotel)</h3>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X POST https://api.global-travel-monitor.de/v1/folders/import \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "source": "api",
    "provider": "Test System",
    "data": {
      "folder": {
        "folder_name": "Test Reise",
        "travel_start_date": "2026-06-01",
        "travel_end_date": "2026-06-14"
      },
      "customer": {
        "first_name": "Max",
        "last_name": "Mustermann"
      },
      "participants": [
        {
          "first_name": "Max",
          "last_name": "Mustermann",
          "is_main_contact": true
        }
      ],
      "itineraries": [
        {
          "itinerary_name": "Hauptreise",
          "hotels": [
            {
              "hotel_name": "Test Hotel",
              "check_in_date": "2026-06-01",
              "check_out_date": "2026-06-14"
            }
          ]
        }
      ]
    }
  }'</code></pre>
    </div>

    <h3 id="beispiel-vollstaendig">Vollständiger Import (Hotel + Flug)</h3>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X POST https://api.global-travel-monitor.de/v1/folders/import \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "source": "api",
    "provider": "TUI Reisebüro München",
    "data": {
      "folder": {
        "folder_name": "Mallorca Sommerurlaub 2026",
        "travel_start_date": "2026-07-15",
        "travel_end_date": "2026-07-29",
        "primary_destination": "Palma, Spanien",
        "travel_type": "leisure",
        "status": "confirmed",
        "currency": "EUR",
        "custom_field_1_label": "TUI Buchungsnummer",
        "custom_field_1_value": "TUI-2026-12345"
      },
      "customer": {
        "salutation": "Frau",
        "first_name": "Anna",
        "last_name": "Müller",
        "email": "anna.mueller@example.com",
        "phone": "+49 89 12345678",
        "city": "München",
        "country_code": "DE"
      },
      "participants": [
        {
          "salutation": "Frau",
          "first_name": "Anna",
          "last_name": "Müller",
          "birth_date": "1985-03-15",
          "nationality": "DE",
          "passport_number": "C01X12345",
          "is_main_contact": true,
          "participant_type": "adult"
        }
      ],
      "itineraries": [
        {
          "itinerary_name": "Mallorca Hauptreise",
          "start_date": "2026-07-15",
          "end_date": "2026-07-29",
          "status": "confirmed",
          "booking_reference": "MAL-2026-001",
          "currency": "EUR",
          "hotels": [
            {
              "hotel_name": "Hotel Paraíso del Mar",
              "city": "Palma",
              "country_code": "ES",
              "lat": 39.5699,
              "lng": 2.6509,
              "check_in_date": "2026-07-15",
              "check_out_date": "2026-07-29",
              "nights": 14,
              "room_type": "Superior Doppelzimmer",
              "board_type": "All Inclusive",
              "booking_reference": "HTL-001",
              "total_amount": 2450.00,
              "status": "confirmed"
            }
          ],
          "flights": [
            {
              "booking_reference": "LH-PMI-001",
              "service_type": "outbound",
              "status": "ticketed",
              "segments": [
                {
                  "segment_number": 1,
                  "departure_airport_code": "MUC",
                  "departure_time": "2026-07-15 10:00:00",
                  "arrival_airport_code": "PMI",
                  "arrival_time": "2026-07-15 12:15:00",
                  "airline_code": "LH",
                  "flight_number": "1802",
                  "cabin_class": "economy"
                }
              ]
            }
          ]
        }
      ]
    }
  }'</code></pre>
    </div>

    <p><strong>Response (202 Accepted):</strong></p>

    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "message": "Import queued successfully",
  "log_id": "019bef38-f2bc-73fc-bdbc-228ff5a8421e"
}</code></pre>
    </div>

    <hr>

    {{-- ===== Import-Status abfragen ===== --}}
    <h2 id="import-status">Import-Status abfragen</h2>

    <h3>Status eines einzelnen Imports</h3>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/folders/imports/{log_id}/status</span>
    </div>

    <p><strong>Beispiel:</strong></p>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/folders/imports/019bef38-f2bc-73fc-bdbc-228ff5a8421e/status"</code></pre>
    </div>

    <p><strong>Response (200 OK):</strong></p>

    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": {
    "id": "019bef38-f2bc-73fc-bdbc-228ff5a8421e",
    "status": "completed",
    "folder_id": "019bef39-a1b2-c3d4-e5f6-789012345678",
    "records_imported": 5,
    "records_failed": 0,
    "error_message": null,
    "started_at": "2026-06-01T10:00:01Z",
    "completed_at": "2026-06-01T10:00:03Z",
    "duration_seconds": 2
  }
}</code></pre>
    </div>

    <p><strong>Mögliche Status-Werte:</strong></p>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>pending</code></td>
                    <td>Import wartet auf Verarbeitung</td>
                </tr>
                <tr>
                    <td><code>processing</code></td>
                    <td>Import wird gerade verarbeitet</td>
                </tr>
                <tr>
                    <td><code>completed</code></td>
                    <td>Import erfolgreich abgeschlossen</td>
                </tr>
                <tr>
                    <td><code>failed</code></td>
                    <td>Import fehlgeschlagen (siehe <code>error_message</code>)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ===== Liste aller Imports ===== --}}
    <h3 id="import-liste">Liste aller Imports</h3>

    <div class="endpoint-block">
        <span class="method method-get">GET</span>
        <span class="path">/v1/folders/imports</span>
    </div>

    <p><strong>Query-Parameter:</strong></p>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Typ</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>per_page</code></td>
                    <td>integer</td>
                    <td>Einträge pro Seite (Standard: 15, Maximum: 100)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p><strong>Beispiel:</strong></p>

    <div class="code-block">
        <span class="code-label">cURL</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.de/v1/folders/imports?per_page=10"</code></pre>
    </div>

    <hr>

    {{-- ===== Fehlercodes ===== --}}
    <h2 id="fehlercodes">Fehlercodes</h2>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>HTTP-Code</th>
                    <th>Bedeutung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>202</code></td>
                    <td>Import erfolgreich in Queue eingereiht</td>
                </tr>
                <tr>
                    <td><code>200</code></td>
                    <td>Statusabfrage erfolgreich</td>
                </tr>
                <tr>
                    <td><code>401</code></td>
                    <td>Nicht authentifiziert (Token fehlt oder ungültig)</td>
                </tr>
                <tr>
                    <td><code>404</code></td>
                    <td>Import-Log nicht gefunden</td>
                </tr>
                <tr>
                    <td><code>422</code></td>
                    <td>Validierungsfehler (ungültige Daten)</td>
                </tr>
                <tr>
                    <td><code>500</code></td>
                    <td>Serverfehler</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p><strong>Beispiel Validierungsfehler (422):</strong></p>

    <div class="response-block">
        <span class="response-label">Error Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": false,
  "errors": {
    "source": ["The source field is required."],
    "data.folder.folder_name": ["The folder name must not exceed 255 characters."]
  }
}</code></pre>
    </div>

    <hr>

    {{-- ===== Automatische Features ===== --}}
    <h2 id="automatische-features">Automatische Features</h2>

    <ul>
        <li><strong>Airport-Matching:</strong> IATA-Codes (z.B. <code>MUC</code>, <code>PMI</code>) werden automatisch zu vollständigen Flughafendaten aufgelöst inkl. Koordinaten und Ländercode</li>
        <li><strong>Country-Matching:</strong> Ländercodes werden automatisch validiert und zugeordnet</li>
        <li><strong>Timeline-Generierung:</strong> Aus Hotels, Flügen, Kreuzfahrten und Mietwagen wird automatisch eine Reise-Timeline erstellt</li>
        <li><strong>Geocoding:</strong> Hotel- und Standortdaten werden für die Kartendarstellung geocodiert</li>
    </ul>

    <hr>

    {{-- ===== Support ===== --}}
    <h2 id="support">Support</h2>

    <p>Bei Fragen zur API wenden Sie sich an Ihren Ansprechpartner bei Passolution.</p>

@endsection
