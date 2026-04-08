@extends('docs.api.layout')

@section('title', 'Feed API')
@section('api_color', '#22c55e')

@section('sidebar')
    <span class="sidebar-heading">Feed API</span>
    <a href="#overview">Übersicht</a>
    <a href="#base-url">Base-URL</a>
    <a href="#caching">Caching</a>

    <span class="sidebar-heading">Metadaten</span>
    <a href="#meta">Priorities &amp; Event-Typen</a>

    <span class="sidebar-heading">Event-Feeds</span>
    <a href="#events-all">Alle Events</a>
    <a href="#events-priority">Nach Priorität</a>
    <a href="#events-country">Nach Land</a>
    <a href="#events-type">Nach Event-Typ</a>
    <a href="#events-region">Nach Region</a>

    <span class="sidebar-heading">RSS-Struktur</span>
    <a href="#rss-standard">Standard-Elemente</a>
    <a href="#rss-article">article:data</a>
    <a href="#rss-country">country:data</a>
    <a href="#rss-namespaces">XML-Namespaces</a>

    <span class="sidebar-heading">Länder-Feeds</span>
    <a href="#countries-all">Alle Länder</a>
    <a href="#countries-continent">Nach Kontinent</a>
    <a href="#countries-eu">EU-Mitgliedsstaaten</a>
    <a href="#countries-schengen">Schengen-Staaten</a>

    <span class="sidebar-heading">Referenz</span>
    <a href="#endpoint-overview">Endpunkt-Übersicht</a>
    <a href="#support">Support</a>
@endsection

@section('content')

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- ÜBERSICHT --}}
    {{-- ══════════════════════════════════════════════════ --}}

    <h1 id="overview">Feed API</h1>

    <p>
        Die Feed API stellt aktuelle Sicherheits- und Reiserisiko-Events sowie Länderinformationen als RSS/Atom-Feeds bereit.
        Die Feeds können in Feed-Reader, CMS-Systeme oder eigene Anwendungen eingebunden werden.
    </p>

    <blockquote>
        <strong>Keine Authentifizierung erforderlich</strong> &ndash; alle Feed-Endpunkte sind öffentlich zugänglich.
    </blockquote>

    <hr>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- BASE-URL --}}
    {{-- ══════════════════════════════════════════════════ --}}

    <h2 id="base-url">Base-URL</h2>

    <div class="code-block">
        <pre><code>https://global-travel-monitor.eu/feed</code></pre>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- CACHING --}}
    {{-- ══════════════════════════════════════════════════ --}}

    <h2 id="caching">Caching</h2>

    <p>
        Feed-Antworten werden serverseitig gecacht. Die Cache-Dauer beträgt standardmäßig <strong>1 Stunde</strong> (3600 Sekunden).
        Bei neuen oder geänderten Events wird der Cache automatisch invalidiert.
    </p>

    <hr>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- METADATEN --}}
    {{-- ══════════════════════════════════════════════════ --}}

    <h2 id="meta">Verfügbare Priorities und Event-Typen</h2>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/feed/events/meta.json</span>
    </div>

    <p>Gibt die gültigen Werte für Priority-Filter und Event-Typ-Filter als JSON zurück.</p>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl https://global-travel-monitor.eu/feed/events/meta.json</code></pre>
    </div>

    <h4>Response</h4>

    <div class="response-block">
        <span class="response-label">JSON Response</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "priorities": [
    { "code": "high", "name_de": "Hoch", "name_en": "High" },
    { "code": "medium", "name_de": "Mittel", "name_en": "Medium" },
    { "code": "low", "name_de": "Niedrig", "name_en": "Low" },
    { "code": "info", "name_de": "Information", "name_en": "Info" }
  ],
  "event_categories": [
    {
      "code": "environment",
      "name": "Umweltereignisse",
      "description": "...",
      "icon": "fa-house-crack",
      "color": "#FF0000"
    }
  ]
}</code></pre>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- EVENT-FEEDS --}}
    {{-- ══════════════════════════════════════════════════ --}}

    <h2 id="events-all">Event-Feeds</h2>

    <p>
        Alle Event-Feeds liefern nur <strong>aktive, nicht-archivierte Events</strong>, deren Startdatum in der Vergangenheit liegt.
        Maximal 100 Events pro Feed, sortiert nach Startdatum (neueste zuerst).
    </p>

    <h3>Alle Events</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Format</th>
                    <th>URL</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>RSS 2.0</td>
                    <td><code>/feed/events/all.xml</code></td>
                </tr>
                <tr>
                    <td>Atom 1.0</td>
                    <td><code>/feed/events/all.atom</code></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl https://global-travel-monitor.eu/feed/events/all.xml</code></pre>
    </div>

    <hr>

    {{-- ── Events nach Priorität ── --}}

    <h3 id="events-priority">Events nach Priorität</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/feed/events/priority/{priority}.xml</span>
    </div>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Gültige Werte</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>priority</code></td>
                    <td><code>high</code>, <code>medium</code>, <code>low</code>, <code>info</code></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl https://global-travel-monitor.eu/feed/events/priority/high.xml</code></pre>
    </div>

    <hr>

    {{-- ── Events nach Land ── --}}

    <h3 id="events-country">Events nach Land</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/feed/events/countries/{code}.xml</span>
    </div>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>code</code></td>
                    <td>ISO 3166-1 alpha-2 (z.B. <code>de</code>) oder alpha-3 (z.B. <code>deu</code>), case-insensitive</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl https://global-travel-monitor.eu/feed/events/countries/de.xml</code></pre>
    </div>

    <hr>

    {{-- ── Events nach Event-Typ ── --}}

    <h3 id="events-type">Events nach Event-Typ</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/feed/events/types/{type}.xml</span>
    </div>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>type</code></td>
                    <td>Event-Typ-Code (aus <code>meta.json</code>), case-insensitive</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl https://global-travel-monitor.eu/feed/events/types/earthquake.xml</code></pre>
    </div>

    <hr>

    {{-- ── Events nach Region ── --}}

    <h3 id="events-region">Events nach Region</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/feed/events/regions/{region}.xml</span>
    </div>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>region</code></td>
                    <td>Numerische Region-ID</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl https://global-travel-monitor.eu/feed/events/regions/3.xml</code></pre>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- RSS-STRUKTUR --}}
    {{-- ══════════════════════════════════════════════════ --}}

    <h2 id="rss-standard">RSS-Struktur (Events)</h2>

    <p>Jedes Event-Item im Feed enthält folgende Elemente:</p>

    <h3>Standard-RSS-Elemente</h3>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Element</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>&lt;title&gt;</code></td>
                    <td>Titel des Events</td>
                </tr>
                <tr>
                    <td><code>&lt;link&gt;</code></td>
                    <td>URL zur Event-Detailseite</td>
                </tr>
                <tr>
                    <td><code>&lt;guid&gt;</code></td>
                    <td>Permanenter Link (identisch mit <code>&lt;link&gt;</code>)</td>
                </tr>
                <tr>
                    <td><code>&lt;description&gt;</code></td>
                    <td>Kurzübersicht: Typ, Zeitraum, Priorität, Länder</td>
                </tr>
                <tr>
                    <td><code>&lt;content:encoded&gt;</code></td>
                    <td>Vollständige Beschreibung</td>
                </tr>
                <tr>
                    <td><code>&lt;pubDate&gt;</code></td>
                    <td>Erstellungsdatum (RFC 2822)</td>
                </tr>
                <tr>
                    <td><code>&lt;category&gt;</code></td>
                    <td>Priorität und Event-Typen</td>
                </tr>
                <tr>
                    <td><code>&lt;dc:creator&gt;</code></td>
                    <td>Ersteller (falls vorhanden)</td>
                </tr>
                <tr>
                    <td><code>&lt;source&gt;</code></td>
                    <td>Quellenangabe mit URL</td>
                </tr>
                <tr>
                    <td><code>&lt;enclosure&gt;</code></td>
                    <td>Länderbild (JPEG, falls vorhanden)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ── article:data ── --}}

    <h3 id="rss-article">Benutzerdefinierte Elemente: <code>article:data</code></h3>

    <div class="code-block">
        <span class="code-label">XML</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>&lt;article:data&gt;
  &lt;article:start_date&gt;Mon, 11 Feb 2026 08:00:00 +0000&lt;/article:start_date&gt;
  &lt;article:end_date&gt;Tue, 18 Feb 2026 08:00:00 +0000&lt;/article:end_date&gt;
  &lt;article:priority&gt;high&lt;/article:priority&gt;
  &lt;article:event_type code="earthquake"&gt;Erdbeben&lt;/article:event_type&gt;
&lt;/article:data&gt;</code></pre>
    </div>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Element</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>article:start_date</code></td>
                    <td>Startdatum des Events</td>
                </tr>
                <tr>
                    <td><code>article:end_date</code></td>
                    <td>Enddatum des Events</td>
                </tr>
                <tr>
                    <td><code>article:priority</code></td>
                    <td>Prioritätsstufe: <code>high</code>, <code>medium</code>, <code>low</code>, <code>info</code></td>
                </tr>
                <tr>
                    <td><code>article:event_type</code></td>
                    <td>Event-Typ mit <code>code</code>-Attribut und Name als Inhalt (mehrere möglich)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ── country:data ── --}}

    <h3 id="rss-country">Benutzerdefinierte Elemente: <code>country:data</code></h3>

    <p>Pro betroffenem Land wird ein <code>&lt;country:data&gt;</code>-Block ausgegeben:</p>

    <div class="code-block">
        <span class="code-label">XML</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>&lt;country:data&gt;
  &lt;country:name_de&gt;Thailand&lt;/country:name_de&gt;
  &lt;country:name_en&gt;Thailand&lt;/country:name_en&gt;
  &lt;country:iso_code&gt;TH&lt;/country:iso_code&gt;
  &lt;country:iso3_code&gt;THA&lt;/country:iso3_code&gt;
  &lt;country:is_eu_member&gt;false&lt;/country:is_eu_member&gt;
  &lt;country:is_schengen_member&gt;false&lt;/country:is_schengen_member&gt;
  &lt;country:continent&gt;Asien&lt;/country:continent&gt;
  &lt;country:currency_code&gt;THB&lt;/country:currency_code&gt;
  &lt;country:phone_prefix&gt;+66&lt;/country:phone_prefix&gt;
  &lt;country:capital&gt;
    &lt;country:capital_name&gt;Bangkok&lt;/country:capital_name&gt;
    &lt;geo:lat&gt;13.7563&lt;/geo:lat&gt;
    &lt;geo:long&gt;100.5018&lt;/geo:long&gt;
  &lt;/country:capital&gt;
&lt;/country:data&gt;</code></pre>
    </div>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Element</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>country:name_de</code></td>
                    <td>Ländername (deutsch)</td>
                </tr>
                <tr>
                    <td><code>country:name_en</code></td>
                    <td>Ländername (englisch)</td>
                </tr>
                <tr>
                    <td><code>country:iso_code</code></td>
                    <td>ISO 3166-1 alpha-2 Code</td>
                </tr>
                <tr>
                    <td><code>country:iso3_code</code></td>
                    <td>ISO 3166-1 alpha-3 Code</td>
                </tr>
                <tr>
                    <td><code>country:is_eu_member</code></td>
                    <td><code>true</code> / <code>false</code></td>
                </tr>
                <tr>
                    <td><code>country:is_schengen_member</code></td>
                    <td><code>true</code> / <code>false</code></td>
                </tr>
                <tr>
                    <td><code>country:continent</code></td>
                    <td>Kontinent (deutsch)</td>
                </tr>
                <tr>
                    <td><code>country:currency_code</code></td>
                    <td>ISO 4217 Währungscode</td>
                </tr>
                <tr>
                    <td><code>country:phone_prefix</code></td>
                    <td>Internationale Telefonvorwahl</td>
                </tr>
                <tr>
                    <td><code>country:capital_name</code></td>
                    <td>Name der Hauptstadt</td>
                </tr>
                <tr>
                    <td><code>geo:lat</code></td>
                    <td>Breitengrad der Hauptstadt</td>
                </tr>
                <tr>
                    <td><code>geo:long</code></td>
                    <td>Längengrad der Hauptstadt</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ── XML-Namespaces ── --}}

    <h3 id="rss-namespaces">XML-Namespaces</h3>

    <div class="code-block">
        <span class="code-label">XML</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>&lt;rss version="2.0"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:country="http://global-travel-monitor.eu/ns/country"
  xmlns:article="http://global-travel-monitor.eu/ns/article"
  xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"&gt;</code></pre>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- LÄNDER-FEEDS --}}
    {{-- ══════════════════════════════════════════════════ --}}

    <h2 id="countries-all">Länder-Feeds</h2>

    <p>
        Die Länder-Feeds liefern Verzeichnisse mit Länderdetails (Name, ISO-Codes, EU/Schengen-Status, Kontinent, Währung, Hauptstadt mit Koordinaten).
    </p>

    <h3>Alle Länder</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/feed/countries/names/all.xml</span>
    </div>

    <hr>

    {{-- ── Länder nach Kontinent ── --}}

    <h3 id="countries-continent">Länder nach Kontinent</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/feed/countries/continent/{code}.xml</span>
    </div>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Gültige Werte</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>code</code></td>
                    <td>
                        <code>EU</code> (Europa),
                        <code>AS</code> (Asien),
                        <code>AF</code> (Afrika),
                        <code>NA</code> (Nordamerika),
                        <code>SA</code> (Südamerika),
                        <code>OC</code> (Ozeanien),
                        <code>AN</code> (Antarktis)
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <h4>Beispiel</h4>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl https://global-travel-monitor.eu/feed/countries/continent/EU.xml</code></pre>
    </div>

    <hr>

    {{-- ── EU-Mitgliedsstaaten ── --}}

    <h3 id="countries-eu">EU-Mitgliedsstaaten</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/feed/countries/eu.xml</span>
    </div>

    <hr>

    {{-- ── Schengen-Staaten ── --}}

    <h3 id="countries-schengen">Schengen-Staaten</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/feed/countries/schengen.xml</span>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- ENDPUNKT-ÜBERSICHT --}}
    {{-- ══════════════════════════════════════════════════ --}}

    <h2 id="endpoint-overview">Endpunkt-Übersicht</h2>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Endpunkt</th>
                    <th>Format</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>/feed/events/meta.json</code></td>
                    <td>JSON</td>
                    <td>Verfügbare Priorities und Event-Typen</td>
                </tr>
                <tr>
                    <td><code>/feed/events/all.xml</code></td>
                    <td>RSS 2.0</td>
                    <td>Alle aktiven Events</td>
                </tr>
                <tr>
                    <td><code>/feed/events/all.atom</code></td>
                    <td>Atom 1.0</td>
                    <td>Alle aktiven Events</td>
                </tr>
                <tr>
                    <td><code>/feed/events/priority/{priority}.xml</code></td>
                    <td>RSS 2.0</td>
                    <td>Events nach Priorität</td>
                </tr>
                <tr>
                    <td><code>/feed/events/countries/{code}.xml</code></td>
                    <td>RSS 2.0</td>
                    <td>Events nach Land</td>
                </tr>
                <tr>
                    <td><code>/feed/events/types/{type}.xml</code></td>
                    <td>RSS 2.0</td>
                    <td>Events nach Event-Typ</td>
                </tr>
                <tr>
                    <td><code>/feed/events/regions/{region}.xml</code></td>
                    <td>RSS 2.0</td>
                    <td>Events nach Region</td>
                </tr>
                <tr>
                    <td><code>/feed/countries/names/all.xml</code></td>
                    <td>RSS 2.0</td>
                    <td>Alle Länder</td>
                </tr>
                <tr>
                    <td><code>/feed/countries/continent/{code}.xml</code></td>
                    <td>RSS 2.0</td>
                    <td>Länder nach Kontinent</td>
                </tr>
                <tr>
                    <td><code>/feed/countries/eu.xml</code></td>
                    <td>RSS 2.0</td>
                    <td>EU-Mitgliedsstaaten</td>
                </tr>
                <tr>
                    <td><code>/feed/countries/schengen.xml</code></td>
                    <td>RSS 2.0</td>
                    <td>Schengen-Staaten</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════ --}}
    {{-- SUPPORT --}}
    {{-- ══════════════════════════════════════════════════ --}}

    <h2 id="support">Support</h2>

    <p>Bei Fragen zur Feed API wenden Sie sich an Ihren Ansprechpartner bei Passolution.</p>

@endsection
