@extends('docs.api.layout')

@section('title', 'Customer Settings API – Organisation & Stammdaten')

@section('api_color', '#a855f7')

@section('sidebar')
    <span class="sidebar-heading">Customer Settings API</span>
    <a href="#uebersicht">Übersicht</a>
    <a href="#authentifizierung">Authentifizierung</a>

    <span class="sidebar-heading">Ressourcen</span>
    <a href="#stammdaten">1. Stammdaten</a>
    <a href="#adressen">2. Adressen</a>
    <a href="#rufnummern">3. Rufnummern</a>
    <a href="#email-adressen">4. E-Mail-Adressen</a>
    <a href="#webseiten">5. Webseiten</a>
    <a href="#ansprechpartner">6. Ansprechpartner</a>
    <a href="#organisationsstruktur">7. Organisationsstruktur</a>
    <a href="#abteilungen">8. Abteilungen</a>
    <a href="#benutzer">9. Benutzer</a>
    <a href="#benutzergruppen">10. Benutzergruppen</a>

    <span class="sidebar-heading">Referenz</span>
    <a href="#fehlerbehandlung">Fehlerbehandlung</a>
    <a href="#hinweise">Hinweise</a>
@endsection

@section('content')

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- Übersicht                                              --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h1 id="uebersicht">Customer Settings API</h1>
    <p>
        Die Customer Settings API ermöglicht die Verwaltung von Kundenstammdaten, Niederlassungen/Adressen,
        Kontaktinformationen (Rufnummern, E-Mail-Adressen, Webseiten), Organisationsstrukturen, Abteilungen,
        Benutzer (Mitarbeiter) und Benutzergruppen. Alle Änderungen werden sofort wirksam.
    </p>

    <hr>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- Authentifizierung                                      --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h2 id="authentifizierung">Authentifizierung</h2>
    <p>Alle API-Aufrufe erfordern einen <strong>Bearer-Token</strong> im HTTP-Header:</p>

    <div class="code-block">
        <span class="code-label">Header</span>
        <pre><code>Authorization: Bearer {API_TOKEN}</code></pre>
    </div>

    <h3>Token generieren</h3>
    <p>Der Token wird über die Web-Oberfläche generiert (erfordert eine aktive Session):</p>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/customer/api-tokens/generate</span>
    </div>

    <div class="response-block">
        <span class="response-label">Response</span>
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

    <h3>Base-URL</h3>
    <div class="code-block">
        <pre><code>https://platform.passolution.de/api</code></pre>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 1. Stammdaten                                          --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h2 id="stammdaten">1. Stammdaten (Master Data)</h2>

    <h3>Firmendaten abrufen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/v1/customer/settings</span>
    </div>

    <p>Gibt die vollständigen Kundenstammdaten zurück, inklusive Firmenanschrift, Rechnungsadresse, Kundentyp und Branchentyp.</p>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X GET https://platform.passolution.de/api/v1/customer/settings \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"</code></pre>
    </div>

    <div class="response-block">
        <span class="response-label">Response 200 OK</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": {
    "company_name": "Musterfirma GmbH",
    "customer_type": "business",
    "business_type": "travel_agency",
    "company_address": {
      "name": "Musterfirma GmbH",
      "street": "Musterstraße",
      "house_number": "1",
      "postal_code": "80331",
      "city": "München",
      "country": "DE"
    },
    "billing_address": {
      "name": "Musterfirma GmbH – Buchhaltung",
      "street": "Musterstraße",
      "house_number": "1",
      "postal_code": "80331",
      "city": "München",
      "country": "DE"
    }
  }
}</code></pre>
    </div>

    <h3>Firmenanschrift aktualisieren</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-put">PUT</span></span>
        <span class="path">/v1/customer/settings/company-address</span>
    </div>

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
                <tr><td><code>name</code></td><td>string</td><td>Ja</td><td>Firmenname</td></tr>
                <tr><td><code>additional</code></td><td>string</td><td>Nein</td><td>Adresszusatz</td></tr>
                <tr><td><code>street</code></td><td>string</td><td>Ja</td><td>Straße</td></tr>
                <tr><td><code>house_number</code></td><td>string</td><td>Nein</td><td>Hausnummer</td></tr>
                <tr><td><code>postal_code</code></td><td>string</td><td>Ja</td><td>Postleitzahl</td></tr>
                <tr><td><code>city</code></td><td>string</td><td>Ja</td><td>Stadt</td></tr>
                <tr><td><code>country</code></td><td>string</td><td>Ja</td><td>Ländercode (ISO alpha-2, z.B. <code>DE</code>)</td></tr>
            </tbody>
        </table>
    </div>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X PUT https://platform.passolution.de/api/v1/customer/settings/company-address \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Musterfirma GmbH",
    "street": "Neue Straße",
    "house_number": "42",
    "postal_code": "80333",
    "city": "München",
    "country": "DE"
  }'</code></pre>
    </div>

    <div class="response-block">
        <span class="response-label">Response 200 OK</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "message": "Firmenanschrift erfolgreich aktualisiert"
}</code></pre>
    </div>

    <blockquote>
        <strong>Hinweis:</strong> Die Firmenanschrift kann über die API aktualisiert, aber nicht gelöscht werden.
    </blockquote>

    <h3>Rechnungsadresse aktualisieren</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-put">PUT</span></span>
        <span class="path">/v1/customer/settings/billing-address</span>
    </div>

    <p>Die Felder sind identisch zur Firmenanschrift (siehe oben).</p>

    <blockquote>
        <strong>Hinweis:</strong> Die Rechnungsadresse kann über die API aktualisiert, aber nicht gelöscht werden.
    </blockquote>

    <h3>Kundentyp ändern</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-put">PUT</span></span>
        <span class="path">/v1/customer/settings/customer-type</span>
    </div>

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
                <tr><td><code>customer_type</code></td><td>string</td><td>Ja</td><td><code>private</code> oder <code>business</code></td></tr>
            </tbody>
        </table>
    </div>

    <h3>Branchentyp ändern</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-put">PUT</span></span>
        <span class="path">/v1/customer/settings/business-type</span>
    </div>

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
                <tr><td><code>business_type</code></td><td>string</td><td>Ja</td><td>Branchentyp (z.B. <code>travel_agency</code>, <code>corporate</code>, <code>insurance</code>)</td></tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 2. Adressen                                            --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h2 id="adressen">2. Adressen (Branches)</h2>

    <h3>Alle Adressen auflisten</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/v1/customer/branches</span>
    </div>

    <h4>Query-Parameter</h4>
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
                <tr><td><code>search</code></td><td>string</td><td>Volltextsuche über Name, Straße, Stadt</td></tr>
                <tr><td><code>city</code></td><td>string</td><td>Filterung nach Stadt</td></tr>
            </tbody>
        </table>
    </div>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X GET "https://platform.passolution.de/api/v1/customer/branches?city=München" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"</code></pre>
    </div>

    <h3>Einzelne Adresse abrufen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/v1/customer/branches/{id}</span>
    </div>

    <h3>Neue Adresse anlegen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/branches</span>
    </div>

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
                <tr><td><code>name</code></td><td>string</td><td>Ja</td><td>Name der Niederlassung</td></tr>
                <tr><td><code>additional</code></td><td>string</td><td>Nein</td><td>Adresszusatz</td></tr>
                <tr><td><code>street</code></td><td>string</td><td>Ja</td><td>Straße</td></tr>
                <tr><td><code>house_number</code></td><td>string</td><td>Nein</td><td>Hausnummer</td></tr>
                <tr><td><code>postal_code</code></td><td>string</td><td>Ja</td><td>Postleitzahl</td></tr>
                <tr><td><code>city</code></td><td>string</td><td>Ja</td><td>Stadt</td></tr>
                <tr><td><code>country</code></td><td>string</td><td>Ja</td><td>Ländercode (ISO alpha-2, z.B. <code>DE</code>)</td></tr>
                <tr><td><code>org_node_ids</code></td><td>array</td><td>Nein</td><td>IDs bestehender Organisationsknoten</td></tr>
                <tr><td><code>org_node_data</code></td><td>array</td><td>Nein</td><td>Daten für neue Organisationsknoten</td></tr>
            </tbody>
        </table>
    </div>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X POST https://platform.passolution.de/api/v1/customer/branches \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Niederlassung Hamburg",
    "street": "Jungfernstieg",
    "house_number": "10",
    "postal_code": "20095",
    "city": "Hamburg",
    "country": "DE"
  }'</code></pre>
    </div>

    <div class="response-block">
        <span class="response-label">Response 201 Created</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": {
    "id": "019bef38-a1b2-c3d4-e5f6-789012345678",
    "name": "Niederlassung Hamburg",
    "street": "Jungfernstieg",
    "house_number": "10",
    "postal_code": "20095",
    "city": "Hamburg",
    "country": "DE"
  }
}</code></pre>
    </div>

    <h3>Adresse aktualisieren</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-put">PUT</span></span>
        <span class="path">/v1/customer/branches/{id}</span>
    </div>

    <p>Die Felder sind identisch zur Erstellung (siehe oben).</p>

    <h3>Adresse löschen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-delete">DELETE</span></span>
        <span class="path">/v1/customer/branches/{id}</span>
    </div>

    <h4>Query-Parameter</h4>
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
                <tr><td><code>scheduled_deletion_at</code></td><td>datetime</td><td>Optionales Datum für geplante Löschung (YYYY-MM-DD HH:MM:SS)</td></tr>
            </tbody>
        </table>
    </div>

    <blockquote>
        <strong>Hinweis:</strong> Firmenanschrift und Hauptsitz-Adressen (<code>is_headquarters=true</code>) können nicht gelöscht werden.
    </blockquote>

    <h3>Geplante Löschung abbrechen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/branches/{id}/cancel-deletion</span>
    </div>

    <p>Bricht eine zuvor geplante Löschung ab.</p>

    <hr>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 3. Rufnummern                                          --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h2 id="rufnummern">3. Rufnummern (Phone Numbers)</h2>

    <h3>Alle Rufnummern auflisten</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/v1/customer/phone-numbers</span>
    </div>

    <h4>Query-Parameter</h4>
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
                <tr><td><code>branch_id</code></td><td>string</td><td>Filterung nach Niederlassung</td></tr>
            </tbody>
        </table>
    </div>

    <h3>Rufnummer anlegen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/phone-numbers</span>
    </div>

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
                <tr><td><code>label</code></td><td>string</td><td>Ja</td><td>Bezeichnung (z.B. "Zentrale", "Notfall")</td></tr>
                <tr><td><code>number</code></td><td>string</td><td>Ja</td><td>Rufnummer</td></tr>
                <tr><td><code>type</code></td><td>string</td><td>Ja</td><td><code>phone</code>, <code>mobile</code> oder <code>fax</code></td></tr>
                <tr><td><code>is_primary</code></td><td>boolean</td><td>Nein</td><td>Als primäre Rufnummer setzen (Standard: <code>false</code>)</td></tr>
                <tr><td><code>notes</code></td><td>string</td><td>Nein</td><td>Notizen</td></tr>
                <tr><td><code>department_id</code></td><td>string</td><td>Nein</td><td>Zugehörige Abteilung</td></tr>
                <tr><td><code>branch_id</code></td><td>string</td><td>Nein</td><td>Zugehörige Niederlassung</td></tr>
            </tbody>
        </table>
    </div>

    <blockquote>
        <strong>Hinweis:</strong> Wenn <code>is_primary</code> auf <code>true</code> gesetzt wird, werden alle anderen Rufnummern automatisch auf nicht-primär zurückgesetzt.
    </blockquote>

    <h3>Rufnummer aktualisieren</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-put">PUT</span></span>
        <span class="path">/v1/customer/phone-numbers/{id}</span>
    </div>

    <p>Die Felder sind identisch zur Erstellung.</p>

    <h3>Rufnummer löschen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-delete">DELETE</span></span>
        <span class="path">/v1/customer/phone-numbers/{id}</span>
    </div>

    <h3>Sortierung ändern</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/phone-numbers/reorder</span>
    </div>

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
                <tr><td><code>ids</code></td><td>array</td><td>Ja</td><td>Geordnete Liste der Rufnummern-IDs</td></tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 4. E-Mail-Adressen                                     --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h2 id="email-adressen">4. E-Mail-Adressen</h2>

    <h3>Alle E-Mail-Adressen auflisten</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/v1/customer/email-addresses</span>
    </div>

    <h4>Query-Parameter</h4>
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
                <tr><td><code>branch_id</code></td><td>string</td><td>Filterung nach Niederlassung</td></tr>
            </tbody>
        </table>
    </div>

    <h3>E-Mail-Adresse anlegen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/email-addresses</span>
    </div>

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
                <tr><td><code>label</code></td><td>string</td><td>Ja</td><td>Bezeichnung (z.B. "Allgemein", "Buchhaltung")</td></tr>
                <tr><td><code>email</code></td><td>string</td><td>Ja</td><td>E-Mail-Adresse</td></tr>
                <tr><td><code>is_primary</code></td><td>boolean</td><td>Nein</td><td>Als primäre E-Mail setzen (Standard: <code>false</code>)</td></tr>
                <tr><td><code>notes</code></td><td>string</td><td>Nein</td><td>Notizen</td></tr>
                <tr><td><code>department_id</code></td><td>string</td><td>Nein</td><td>Zugehörige Abteilung</td></tr>
                <tr><td><code>branch_id</code></td><td>string</td><td>Nein</td><td>Zugehörige Niederlassung</td></tr>
            </tbody>
        </table>
    </div>

    <blockquote>
        <strong>Hinweis:</strong> Wenn <code>is_primary</code> auf <code>true</code> gesetzt wird, werden alle anderen E-Mail-Adressen automatisch auf nicht-primär zurückgesetzt.
    </blockquote>

    <h3>E-Mail-Adresse aktualisieren</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-put">PUT</span></span>
        <span class="path">/v1/customer/email-addresses/{id}</span>
    </div>

    <p>Die Felder sind identisch zur Erstellung.</p>

    <h3>E-Mail-Adresse löschen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-delete">DELETE</span></span>
        <span class="path">/v1/customer/email-addresses/{id}</span>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 5. Webseiten                                           --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h2 id="webseiten">5. Webseiten</h2>

    <h3>Alle Webseiten auflisten</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/v1/customer/websites</span>
    </div>

    <h3>Webseite anlegen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/websites</span>
    </div>

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
                <tr><td><code>label</code></td><td>string</td><td>Ja</td><td>Bezeichnung (z.B. "Homepage", "Online-Shop")</td></tr>
                <tr><td><code>url</code></td><td>string</td><td>Ja</td><td>URL der Webseite</td></tr>
                <tr><td><code>is_primary</code></td><td>boolean</td><td>Nein</td><td>Als primäre Webseite setzen (Standard: <code>false</code>)</td></tr>
                <tr><td><code>notes</code></td><td>string</td><td>Nein</td><td>Notizen</td></tr>
                <tr><td><code>branch_id</code></td><td>string</td><td>Nein</td><td>Zugehörige Niederlassung</td></tr>
            </tbody>
        </table>
    </div>

    <blockquote>
        <strong>Hinweis:</strong> Wenn <code>is_primary</code> auf <code>true</code> gesetzt wird, werden alle anderen Webseiten automatisch auf nicht-primär zurückgesetzt.
    </blockquote>

    <h3>Webseite aktualisieren</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-put">PUT</span></span>
        <span class="path">/v1/customer/websites/{id}</span>
    </div>

    <p>Die Felder sind identisch zur Erstellung.</p>

    <h3>Webseite löschen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-delete">DELETE</span></span>
        <span class="path">/v1/customer/websites/{id}</span>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 6. Ansprechpartner                                     --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h2 id="ansprechpartner">6. Ansprechpartner (Branch Contacts)</h2>

    <h3>Ansprechpartner auflisten</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/v1/customer/branch-contacts</span>
    </div>

    <h4>Query-Parameter</h4>
    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Typ</th>
                    <th>Pflicht</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><code>branch_id</code></td><td>string</td><td>Ja</td><td>Niederlassungs-ID (Pflichtparameter)</td></tr>
            </tbody>
        </table>
    </div>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X GET "https://platform.passolution.de/api/v1/customer/branch-contacts?branch_id=019bef38-a1b2-c3d4-e5f6-789012345678" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"</code></pre>
    </div>

    <h3>Ansprechpartner anlegen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/branch-contacts</span>
    </div>

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
                <tr><td><code>branch_id</code></td><td>string</td><td>Ja</td><td>Zugehörige Niederlassung</td></tr>
                <tr><td><code>salutation</code></td><td>string</td><td>Nein</td><td>Anrede</td></tr>
                <tr><td><code>title</code></td><td>string</td><td>Nein</td><td>Titel (z.B. "Dr.", "Prof.")</td></tr>
                <tr><td><code>first_name</code></td><td>string</td><td>Ja</td><td>Vorname</td></tr>
                <tr><td><code>last_name</code></td><td>string</td><td>Ja</td><td>Nachname</td></tr>
                <tr><td><code>function</code></td><td>string</td><td>Nein</td><td>Funktion/Position</td></tr>
                <tr><td><code>department</code></td><td>string</td><td>Nein</td><td>Abteilung</td></tr>
                <tr><td><code>phone</code></td><td>string</td><td>Nein</td><td>Telefonnummer</td></tr>
                <tr><td><code>mobile</code></td><td>string</td><td>Nein</td><td>Mobilnummer</td></tr>
                <tr><td><code>fax</code></td><td>string</td><td>Nein</td><td>Faxnummer</td></tr>
                <tr><td><code>email</code></td><td>string</td><td>Nein</td><td>E-Mail-Adresse</td></tr>
                <tr><td><code>notes</code></td><td>string</td><td>Nein</td><td>Notizen</td></tr>
            </tbody>
        </table>
    </div>

    <h3>Ansprechpartner aktualisieren</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-put">PUT</span></span>
        <span class="path">/v1/customer/branch-contacts/{id}</span>
    </div>

    <p>Die Felder sind identisch zur Erstellung (ohne <code>branch_id</code>).</p>

    <h3>Ansprechpartner löschen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-delete">DELETE</span></span>
        <span class="path">/v1/customer/branch-contacts/{id}</span>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 7. Organisationsstruktur                               --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h2 id="organisationsstruktur">7. Organisationsstruktur (Org Nodes)</h2>

    <h3>Hierarchische Struktur abrufen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/v1/customer/org-nodes</span>
    </div>

    <p>Gibt die gesamte Organisationsstruktur als hierarchischen Baum zurück.</p>

    <h3>Einzelnen Knoten abrufen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/v1/customer/org-nodes/{id}</span>
    </div>

    <h3>Knoten erstellen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/org-nodes</span>
    </div>

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
                <tr><td><code>name</code></td><td>string</td><td>Ja</td><td>Name des Knotens</td></tr>
                <tr><td><code>code</code></td><td>string</td><td>Nein</td><td>Kurzcode (z.B. "DE-MUC")</td></tr>
                <tr><td><code>relation_label</code></td><td>string</td><td>Nein</td><td>Beziehungsbezeichnung</td></tr>
                <tr><td><code>description</code></td><td>string</td><td>Nein</td><td>Beschreibung</td></tr>
                <tr><td><code>parent_id</code></td><td>string</td><td>Nein</td><td>ID des übergeordneten Knotens</td></tr>
                <tr><td><code>after_id</code></td><td>string</td><td>Nein</td><td>ID des vorhergehenden Geschwisterknotens (für Sortierung)</td></tr>
                <tr><td><code>color</code></td><td>string</td><td>Nein</td><td>Farbcode (z.B. <code>#FF5733</code>)</td></tr>
            </tbody>
        </table>
    </div>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X POST https://platform.passolution.de/api/v1/customer/org-nodes \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Region Süd",
    "code": "REG-SUED",
    "description": "Alle Niederlassungen in Süddeutschland",
    "parent_id": "019bef38-0000-0000-0000-000000000001",
    "color": "#2196F3"
  }'</code></pre>
    </div>

    <div class="response-block">
        <span class="response-label">Response 201 Created</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": {
    "id": "019bef38-a1b2-c3d4-e5f6-000000000099",
    "name": "Region Süd",
    "code": "REG-SUED",
    "description": "Alle Niederlassungen in Süddeutschland",
    "parent_id": "019bef38-0000-0000-0000-000000000001",
    "color": "#2196F3"
  }
}</code></pre>
    </div>

    <h3>Knoten aktualisieren</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-put">PUT</span></span>
        <span class="path">/v1/customer/org-nodes/{id}</span>
    </div>

    <p>Die Felder sind identisch zur Erstellung.</p>

    <h3>Knoten löschen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-delete">DELETE</span></span>
        <span class="path">/v1/customer/org-nodes/{id}</span>
    </div>

    <blockquote>
        <strong>Hinweis:</strong> Beim Löschen eines Knotens werden alle Unterknoten ebenfalls gelöscht.
    </blockquote>

    <h3>Sortierung ändern</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/org-nodes/reorder</span>
    </div>

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
                <tr><td><code>ids</code></td><td>array</td><td>Ja</td><td>Geordnete Liste der Knoten-IDs</td></tr>
            </tbody>
        </table>
    </div>

    <h3>Knoten verschieben</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/org-nodes/{id}/move</span>
    </div>

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
                <tr><td><code>parent_id</code></td><td>string</td><td>Nein</td><td>Neue übergeordnete Knoten-ID (<code>null</code> für Wurzelebene)</td></tr>
                <tr><td><code>after_id</code></td><td>string</td><td>Nein</td><td>ID des vorhergehenden Geschwisterknotens</td></tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 8. Abteilungen                                         --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h2 id="abteilungen">8. Abteilungen (Departments)</h2>

    <h3>Alle Abteilungen auflisten</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/v1/customer/departments</span>
    </div>

    <h4>Query-Parameter</h4>
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
                <tr><td><code>is_active</code></td><td>boolean</td><td>Filterung nach Status (<code>true</code>/<code>false</code>)</td></tr>
            </tbody>
        </table>
    </div>

    <h3>Abteilung anlegen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/departments</span>
    </div>

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
                <tr><td><code>name</code></td><td>string</td><td>Ja</td><td>Name der Abteilung</td></tr>
                <tr><td><code>description</code></td><td>string</td><td>Nein</td><td>Beschreibung</td></tr>
                <tr><td><code>code</code></td><td>string</td><td>Nein</td><td>Kurzcode</td></tr>
                <tr><td><code>is_active</code></td><td>boolean</td><td>Nein</td><td>Aktiv-Status (Standard: <code>true</code>)</td></tr>
            </tbody>
        </table>
    </div>

    <h3>Abteilung aktualisieren</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-put">PUT</span></span>
        <span class="path">/v1/customer/departments/{id}</span>
    </div>

    <p>Die Felder sind identisch zur Erstellung.</p>

    <h3>Abteilung löschen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-delete">DELETE</span></span>
        <span class="path">/v1/customer/departments/{id}</span>
    </div>

    <h3>Sortierung ändern</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/departments/reorder</span>
    </div>

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
                <tr><td><code>ids</code></td><td>array</td><td>Ja</td><td>Geordnete Liste der Abteilungs-IDs</td></tr>
            </tbody>
        </table>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 9. Benutzer                                            --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h2 id="benutzer">9. Benutzer (Employees)</h2>

    <h3>Alle Benutzer auflisten</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/v1/customer/employees</span>
    </div>

    <h4>Query-Parameter</h4>
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
                <tr><td><code>search</code></td><td>string</td><td>Volltextsuche über Name, E-Mail, Personalnummer</td></tr>
                <tr><td><code>branch_id</code></td><td>string</td><td>Filterung nach Niederlassung</td></tr>
                <tr><td><code>department_id</code></td><td>string</td><td>Filterung nach Abteilung</td></tr>
                <tr><td><code>group_id</code></td><td>string</td><td>Filterung nach Benutzergruppe</td></tr>
                <tr><td><code>is_active</code></td><td>boolean</td><td>Filterung nach Status (<code>true</code>/<code>false</code>)</td></tr>
                <tr><td><code>per_page</code></td><td>integer</td><td>Einträge pro Seite (Standard: 50, Maximum: 200, <code>0</code> = alle)</td></tr>
            </tbody>
        </table>
    </div>

    <div class="code-block">
        <span class="code-label">curl</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>curl -X GET "https://platform.passolution.de/api/v1/customer/employees?search=Müller&amp;is_active=true&amp;per_page=25" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"</code></pre>
    </div>

    <div class="response-block">
        <span class="response-label">Response 200 OK</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": true,
  "data": [
    {
      "id": "019bef38-a1b2-c3d4-e5f6-789012345678",
      "salutation": "herr",
      "first_name": "Thomas",
      "last_name": "Müller",
      "email": "t.mueller@musterfirma.de",
      "phone": "+49 89 12345678",
      "position": "Reiseberater",
      "department": "Vertrieb",
      "personnel_number": "P-1001",
      "is_active": true,
      "branch_id": "019bef38-0000-0000-0000-000000000001",
      "group_ids": [
        "019bef38-0000-0000-0000-000000000010"
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 1
  }
}</code></pre>
    </div>

    <h3>Einzelnen Benutzer abrufen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/v1/customer/employees/{id}</span>
    </div>

    <h3>Benutzer anlegen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/employees</span>
    </div>

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
                <tr><td><code>salutation</code></td><td>string</td><td>Nein</td><td><code>herr</code>, <code>frau</code> oder <code>divers</code></td></tr>
                <tr><td><code>title</code></td><td>string</td><td>Nein</td><td>Titel (z.B. "Dr.", "Prof.")</td></tr>
                <tr><td><code>first_name</code></td><td>string</td><td>Ja</td><td>Vorname</td></tr>
                <tr><td><code>last_name</code></td><td>string</td><td>Ja</td><td>Nachname</td></tr>
                <tr><td><code>email</code></td><td>string</td><td>Nein</td><td>E-Mail-Adresse</td></tr>
                <tr><td><code>phone</code></td><td>string</td><td>Nein</td><td>Telefonnummer</td></tr>
                <tr><td><code>mobile</code></td><td>string</td><td>Nein</td><td>Mobilnummer</td></tr>
                <tr><td><code>position</code></td><td>string</td><td>Nein</td><td>Position/Funktion</td></tr>
                <tr><td><code>department</code></td><td>string</td><td>Nein</td><td>Abteilung (Freitext)</td></tr>
                <tr><td><code>department_id</code></td><td>string</td><td>Nein</td><td>Zugehörige Abteilungs-ID</td></tr>
                <tr><td><code>personnel_number</code></td><td>string</td><td>Nein</td><td>Personalnummer</td></tr>
                <tr><td><code>branch_id</code></td><td>string</td><td>Nein</td><td>Zugehörige Niederlassung</td></tr>
                <tr><td><code>is_active</code></td><td>boolean</td><td>Nein</td><td>Aktiv-Status (Standard: <code>true</code>)</td></tr>
                <tr><td><code>notes</code></td><td>string</td><td>Nein</td><td>Notizen</td></tr>
                <tr><td><code>active_from</code></td><td>date</td><td>Nein</td><td>Aktiv ab (YYYY-MM-DD)</td></tr>
                <tr><td><code>active_until</code></td><td>date</td><td>Nein</td><td>Aktiv bis (YYYY-MM-DD)</td></tr>
                <tr><td><code>group_ids</code></td><td>array</td><td>Nein</td><td>Liste von Benutzergruppen-IDs</td></tr>
            </tbody>
        </table>
    </div>

    <h3>Benutzer aktualisieren</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-put">PUT</span></span>
        <span class="path">/v1/customer/employees/{id}</span>
    </div>

    <p>Die Felder sind identisch zur Erstellung.</p>

    <h3>Benutzer löschen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-delete">DELETE</span></span>
        <span class="path">/v1/customer/employees/{id}</span>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 10. Benutzergruppen                                    --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h2 id="benutzergruppen">10. Benutzergruppen (Employee Groups)</h2>

    <h3>Alle Benutzergruppen auflisten</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-get">GET</span></span>
        <span class="path">/v1/customer/employee-groups</span>
    </div>

    <h3>Benutzergruppe anlegen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-post">POST</span></span>
        <span class="path">/v1/customer/employee-groups</span>
    </div>

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
                <tr><td><code>name</code></td><td>string</td><td>Ja</td><td>Name der Gruppe</td></tr>
                <tr><td><code>description</code></td><td>string</td><td>Nein</td><td>Beschreibung</td></tr>
            </tbody>
        </table>
    </div>

    <h3>Benutzergruppe aktualisieren</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-put">PUT</span></span>
        <span class="path">/v1/customer/employee-groups/{id}</span>
    </div>

    <p>Die Felder sind identisch zur Erstellung.</p>

    <blockquote>
        <strong>Hinweis:</strong> Systemgruppen (<code>is_system=true</code>) können nicht bearbeitet werden.
    </blockquote>

    <h3>Benutzergruppe löschen</h3>

    <div class="endpoint-block">
        <span class="method"><span class="method-delete">DELETE</span></span>
        <span class="path">/v1/customer/employee-groups/{id}</span>
    </div>

    <blockquote>
        <strong>Hinweis:</strong> Systemgruppen (<code>is_system=true</code>) können nicht gelöscht werden.
    </blockquote>

    <hr>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- Fehlerbehandlung                                       --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h2 id="fehlerbehandlung">Fehlerbehandlung</h2>

    <div class="table-responsive">
        <table class="field-table">
            <thead>
                <tr>
                    <th>HTTP-Code</th>
                    <th>Bedeutung</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><code>200</code></td><td>Anfrage erfolgreich</td></tr>
                <tr><td><code>201</code></td><td>Ressource erfolgreich erstellt</td></tr>
                <tr><td><code>401</code></td><td>Nicht authentifiziert (Token fehlt oder ungültig)</td></tr>
                <tr><td><code>403</code></td><td>Keine Berechtigung für diese Aktion</td></tr>
                <tr><td><code>404</code></td><td>Ressource nicht gefunden</td></tr>
                <tr><td><code>422</code></td><td>Validierungsfehler (ungültige Daten)</td></tr>
                <tr><td><code>429</code></td><td>Zu viele Anfragen (Rate Limit überschritten)</td></tr>
            </tbody>
        </table>
    </div>

    <h3>Authentifizierungsfehler (401)</h3>
    <div class="response-block">
        <span class="response-label">Response 401</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "message": "Unauthenticated."
}</code></pre>
    </div>

    <h3>Validierungsfehler (422)</h3>
    <div class="response-block">
        <span class="response-label">Response 422</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": false,
  "errors": {
    "name": ["The name field is required."],
    "street": ["The street field is required."],
    "city": ["The city field is required."]
  }
}</code></pre>
    </div>

    <h3>Nicht gefunden (404)</h3>
    <div class="response-block">
        <span class="response-label">Response 404</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": false,
  "message": "Resource not found."
}</code></pre>
    </div>

    <h3>Keine Berechtigung (403)</h3>
    <div class="response-block">
        <span class="response-label">Response 403</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "success": false,
  "message": "Systemgruppen können nicht gelöscht werden."
}</code></pre>
    </div>

    <h3>Rate Limit (429)</h3>
    <div class="response-block">
        <span class="response-label">Response 429</span>
        <button class="copy-btn"><i class="fas fa-copy"></i> Kopieren</button>
        <pre><code>{
  "message": "Too Many Attempts."
}</code></pre>
    </div>

    <hr>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- Hinweise                                               --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <h2 id="hinweise">Hinweise</h2>

    <ul>
        <li><strong>Firmenanschrift und Rechnungsadresse</strong> können über die API aktualisiert, aber nicht gelöscht werden</li>
        <li><strong>Hauptsitz-Adressen</strong> (<code>is_headquarters=true</code>) können nicht gelöscht werden</li>
        <li><strong>Systemgruppen</strong> (<code>is_system=true</code>) können nicht bearbeitet oder gelöscht werden</li>
        <li><strong>Primär-Markierung:</strong> Bei Rufnummern, E-Mail-Adressen und Webseiten gilt: Wenn <code>is_primary</code> auf <code>true</code> gesetzt wird, werden alle anderen Einträge desselben Typs automatisch auf nicht-primär zurückgesetzt</li>
        <li><strong>Geplante Löschung:</strong> Adressen können mit <code>scheduled_deletion_at</code> zur Löschung vorgemerkt werden. Die geplante Löschung kann jederzeit über den <code>cancel-deletion</code>-Endpunkt abgebrochen werden</li>
        <li><strong>Pagination:</strong> Bei Benutzern kann mit <code>per_page=0</code> die Pagination deaktiviert werden, um alle Einträge auf einmal abzurufen (maximal 200 pro Seite, <code>0</code> = alle)</li>
    </ul>

    <hr>

    <p>Bei Fragen zur API wenden Sie sich an Ihren Ansprechpartner bei Passolution.</p>

@endsection
