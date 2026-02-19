# Folder Import API – Kundenanleitung

## Übersicht

Die Folder Import API ermöglicht den Import von Reisedaten (Folders) mit Hotels, Flügen, Kreuzfahrten und Mietwagen. Der Import läuft queue-basiert im Hintergrund und bietet automatisches Airport-Matching, Country-Matching, Timeline-Generierung und Geocoding.

---

## Authentifizierung

Alle API-Aufrufe erfordern einen **Bearer-Token** im HTTP-Header:

```
Authorization: Bearer {API_TOKEN}
```

### Token generieren

Der Token wird über die Web-Oberfläche generiert (erfordert eine aktive Session):

```
POST /customer/api-tokens/generate
```

**Response:**

```json
{
  "success": true,
  "token": "2|RHej0fNgjGSzvPrEcSuY7nMGI7fldCnOMoBrpl2T173373b5",
  "message": "API Token erfolgreich generiert"
}
```

> **Wichtig:** Speichern Sie den Token sicher ab. Er wird nur einmal im Klartext angezeigt.

---

## Base-URL

```
https://[domain]/api
```

---

## Folder importieren

```
POST /customer/folders/import
```

Importiert einen kompletten Folder mit allen zugehörigen Daten. Der Import wird in eine Queue eingereiht und im Hintergrund verarbeitet. Die Response enthält eine `log_id` zum Status-Tracking.

### Request-Struktur

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `source` | string | Ja | Import-Quelle: `api`, `file`, `manual` |
| `provider` | string | Ja | Name des Datenlieferanten (max. 128 Zeichen) |
| `data` | object | Ja | Die eigentlichen Reisedaten (siehe unten) |
| `mapping_config` | object | Nein | Optionale Mapping-Konfiguration |

### Daten-Struktur (`data`)

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `folder` | object | Ja | Vorgangsdaten |
| `customer` | object | Nein | Kundendaten |
| `participants` | array | Nein | Reiseteilnehmer |
| `itineraries` | array | Ja | Reiseleistungen (Hotels, Flüge, etc.) |

---

### Folder (Vorgangsdaten)

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `folder_number` | string | Nein | Eindeutige Vorgangsnummer (wird automatisch generiert) |
| `folder_name` | string | Nein | Name der Reise (max. 255 Zeichen) |
| `travel_start_date` | date | Nein | Reisebeginn (YYYY-MM-DD) |
| `travel_end_date` | date | Nein | Reiseende (YYYY-MM-DD) |
| `primary_destination` | string | Nein | Hauptreiseziel |
| `status` | string | Nein | `draft`, `confirmed`, `active`, `completed`, `cancelled` (Standard: `draft`) |
| `travel_type` | string | Nein | `business`, `leisure`, `mixed` (Standard: `leisure`) |
| `agent_name` | string | Nein | Name des Bearbeiters |
| `notes` | string | Nein | Notizen |
| `currency` | string | Nein | Währung als ISO-Code (Standard: `EUR`) |
| `custom_field_1_label` | string | Nein | Label für eigenes Feld 1 (max. 100 Zeichen) |
| `custom_field_1_value` | string | Nein | Wert für eigenes Feld 1 |
| `custom_field_2_label` | string | Nein | Label für eigenes Feld 2 |
| `custom_field_2_value` | string | Nein | Wert für eigenes Feld 2 |
| `custom_field_3_label` | string | Nein | Label für eigenes Feld 3 |
| `custom_field_3_value` | string | Nein | Wert für eigenes Feld 3 |
| `custom_field_4_label` | string | Nein | Label für eigenes Feld 4 |
| `custom_field_4_value` | string | Nein | Wert für eigenes Feld 4 |
| `custom_field_5_label` | string | Nein | Label für eigenes Feld 5 |
| `custom_field_5_value` | string | Nein | Wert für eigenes Feld 5 |

---

### Customer (Kundendaten)

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `salutation` | string | Nein | Anrede: `mr`, `mrs`, `diverse` (auch `Herr`, `Frau`, `Divers` wird gemappt) |
| `title` | string | Nein | Titel (max. 64 Zeichen) |
| `first_name` | string | Ja | Vorname (max. 128 Zeichen) |
| `last_name` | string | Ja | Nachname (max. 128 Zeichen) |
| `email` | string | Nein | E-Mail-Adresse |
| `phone` | string | Nein | Telefonnummer |
| `mobile` | string | Nein | Mobilnummer |
| `street` | string | Nein | Straße |
| `house_number` | string | Nein | Hausnummer |
| `postal_code` | string | Nein | Postleitzahl |
| `city` | string | Nein | Stadt |
| `country_code` | string | Nein | Ländercode (ISO alpha-2, z.B. `DE`) |
| `birth_date` | date | Nein | Geburtsdatum (YYYY-MM-DD) |
| `nationality` | string | Nein | Staatsangehörigkeit (ISO alpha-2) |
| `notes` | string | Nein | Notizen |

---

### Participant (Reiseteilnehmer)

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `salutation` | string | Nein | `mr`, `mrs`, `child`, `infant`, `diverse` |
| `title` | string | Nein | Titel |
| `first_name` | string | Ja | Vorname |
| `last_name` | string | Ja | Nachname |
| `birth_date` | date | Nein | Geburtsdatum |
| `nationality` | string | Nein | Staatsangehörigkeit (ISO alpha-2) |
| `passport_number` | string | Nein | Reisepassnummer |
| `passport_issue_date` | date | Nein | Ausstellungsdatum Pass |
| `passport_expiry_date` | date | Nein | Ablaufdatum Pass |
| `passport_issuing_country` | string | Nein | Ausstellungsland Pass (ISO alpha-2) |
| `email` | string | Nein | E-Mail-Adresse |
| `phone` | string | Nein | Telefonnummer |
| `dietary_requirements` | string | Nein | Ernährungsanforderungen |
| `medical_conditions` | string | Nein | Medizinische Hinweise |
| `notes` | string | Nein | Notizen |
| `is_main_contact` | boolean | Nein | Hauptansprechpartner (Standard: `false`) |
| `participant_type` | string | Nein | `adult`, `child`, `infant` (Standard: `adult`) |

---

### Itinerary (Reiseleistung)

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `booking_reference` | string | Nein | Buchungsreferenz |
| `itinerary_name` | string | Nein | Name der Leistung |
| `start_date` | date | Nein | Startdatum |
| `end_date` | date | Nein | Enddatum |
| `status` | string | Nein | `pending`, `confirmed`, `cancelled`, `completed` (Standard: `pending`) |
| `provider_name` | string | Nein | Anbietername |
| `provider_reference` | string | Nein | Anbieterreferenz |
| `currency` | string | Nein | Währung (Standard: `EUR`) |
| `notes` | string | Nein | Notizen |
| `hotels` | array | Nein | Hotels (siehe unten) |
| `flights` | array | Nein | Flüge (siehe unten) |
| `ships` | array | Nein | Kreuzfahrten (siehe unten) |
| `car_rentals` | array | Nein | Mietwagen (siehe unten) |

---

### Hotel

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `hotel_name` | string | Ja | Hotelname |
| `hotel_code` | string | Nein | Hotel-Code |
| `hotel_code_type` | string | Nein | Typ des Hotel-Codes |
| `street` | string | Nein | Straße |
| `postal_code` | string | Nein | Postleitzahl |
| `city` | string | Nein | Stadt |
| `country_code` | string | Nein | Ländercode (ISO alpha-2) |
| `lat` | number | Nein | Breitengrad (-90 bis 90) |
| `lng` | number | Nein | Längengrad (-180 bis 180) |
| `check_in_date` | date | Ja | Check-in-Datum |
| `check_out_date` | date | Ja | Check-out-Datum |
| `nights` | integer | Nein | Anzahl Nächte |
| `room_type` | string | Nein | Zimmertyp |
| `room_count` | integer | Nein | Zimmeranzahl (Standard: 1) |
| `board_type` | string | Nein | Verpflegung (z.B. "All Inclusive") |
| `booking_reference` | string | Nein | Buchungsreferenz |
| `total_amount` | number | Nein | Gesamtbetrag |
| `currency` | string | Nein | Währung (Standard: `EUR`) |
| `status` | string | Nein | `pending`, `confirmed`, `cancelled` (Standard: `pending`) |
| `notes` | string | Nein | Notizen |

---

### Flight (Flug)

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `booking_reference` | string | Nein | Buchungsreferenz |
| `service_type` | string | Nein | `outbound`, `return`, `multi_leg` (Standard: `outbound`) |
| `airline_pnr` | string | Nein | Airline PNR |
| `ticket_numbers` | array | Nein | Ticketnummern |
| `total_amount` | number | Nein | Gesamtbetrag |
| `currency` | string | Nein | Währung (Standard: `EUR`) |
| `status` | string | Nein | `pending`, `ticketed`, `cancelled` (Standard: `pending`) |
| `segments` | array | Ja | Flugsegmente (mindestens 1) |

#### Flight Segment

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `segment_number` | integer | Nein | Segmentnummer (Standard: 1) |
| `departure_airport_code` | string | Ja | IATA-Code Abflughafen (z.B. `MUC`) – automatisches Matching |
| `departure_time` | datetime | Ja | Abflugzeit |
| `departure_terminal` | string | Nein | Terminal |
| `arrival_airport_code` | string | Ja | IATA-Code Zielflughafen (z.B. `PMI`) – automatisches Matching |
| `arrival_time` | datetime | Ja | Ankunftszeit |
| `arrival_terminal` | string | Nein | Terminal |
| `airline_code` | string | Nein | Airline-Code (z.B. `LH`) |
| `flight_number` | string | Nein | Flugnummer |
| `aircraft_type` | string | Nein | Flugzeugtyp (z.B. `A320`) |
| `duration_minutes` | integer | Nein | Flugdauer in Minuten |
| `booking_class` | string | Nein | Buchungsklasse |
| `cabin_class` | string | Nein | `economy`, `premium_economy`, `business`, `first` (Standard: `economy`) |

> **Hinweis:** `departure_country_code`, `departure_lat`, `departure_lng`, `arrival_country_code`, `arrival_lat`, `arrival_lng` werden automatisch aus den IATA-Codes ermittelt.

---

### Ship (Kreuzfahrt)

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `ship_name` | string | Ja | Schiffsname |
| `cruise_line` | string | Nein | Reederei |
| `ship_code` | string | Nein | Schiffs-Code |
| `embarkation_date` | date | Ja | Einschiffungsdatum |
| `disembarkation_date` | date | Ja | Ausschiffungsdatum |
| `nights` | integer | Nein | Anzahl Nächte |
| `embarkation_port` | string | Nein | Einschiffungshafen |
| `embarkation_country_code` | string | Nein | Ländercode Einschiffung (ISO alpha-2) |
| `embarkation_lat` | number | Nein | Breitengrad Einschiffung |
| `embarkation_lng` | number | Nein | Längengrad Einschiffung |
| `disembarkation_port` | string | Nein | Ausschiffungshafen |
| `disembarkation_country_code` | string | Nein | Ländercode Ausschiffung |
| `disembarkation_lat` | number | Nein | Breitengrad Ausschiffung |
| `disembarkation_lng` | number | Nein | Längengrad Ausschiffung |
| `cabin_number` | string | Nein | Kabinennummer |
| `cabin_type` | string | Nein | Kabinentyp |
| `cabin_category` | string | Nein | Kabinenkategorie |
| `deck` | string | Nein | Deck |
| `booking_reference` | string | Nein | Buchungsreferenz |
| `total_amount` | number | Nein | Gesamtbetrag |
| `currency` | string | Nein | Währung (Standard: `EUR`) |
| `status` | string | Nein | `pending`, `confirmed`, `cancelled` (Standard: `pending`) |
| `port_calls` | array | Nein | Hafenstopps (siehe unten) |
| `notes` | string | Nein | Notizen |

#### Port Call (Hafenstopp)

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `port` | string | Nein | Hafenname |
| `country` | string | Nein | Ländercode (ISO alpha-2) |
| `arrival` | date | Nein | Ankunftsdatum |
| `departure` | date | Nein | Abreisedatum |

---

### Car Rental (Mietwagen)

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `rental_company` | string | Nein | Mietwagenfirma |
| `booking_reference` | string | Nein | Buchungsreferenz |
| `pickup_location` | string | Ja | Abholort |
| `pickup_country_code` | string | Nein | Ländercode Abholung (ISO alpha-2) |
| `pickup_lat` | number | Nein | Breitengrad Abholung |
| `pickup_lng` | number | Nein | Längengrad Abholung |
| `pickup_datetime` | datetime | Ja | Abholdatum/-zeit |
| `return_location` | string | Ja | Rückgabeort |
| `return_country_code` | string | Nein | Ländercode Rückgabe |
| `return_lat` | number | Nein | Breitengrad Rückgabe |
| `return_lng` | number | Nein | Längengrad Rückgabe |
| `return_datetime` | datetime | Ja | Rückgabedatum/-zeit |
| `vehicle_category` | string | Nein | Fahrzeugkategorie |
| `vehicle_type` | string | Nein | Fahrzeugtyp |
| `vehicle_make_model` | string | Nein | Marke/Modell |
| `transmission` | string | Nein | `manual`, `automatic` |
| `fuel_type` | string | Nein | `petrol`, `diesel`, `electric`, `hybrid` |
| `rental_days` | integer | Nein | Mietdauer in Tagen |
| `total_amount` | number | Nein | Gesamtbetrag |
| `currency` | string | Nein | Währung (Standard: `EUR`) |
| `insurance_options` | array | Nein | Versicherungsoptionen |
| `extras` | array | Nein | Zusatzleistungen |
| `status` | string | Nein | `pending`, `confirmed`, `picked_up`, `returned`, `cancelled` (Standard: `pending`) |
| `notes` | string | Nein | Notizen |

---

## Beispiele

### Minimaler Import (nur Hotel)

```bash
curl -X POST https://[domain]/api/customer/folders/import \
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
  }'
```

### Vollständiger Import (Hotel + Flug)

```bash
curl -X POST https://[domain]/api/customer/folders/import \
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
  }'
```

**Response (202 Accepted):**

```json
{
  "success": true,
  "message": "Import queued successfully",
  "log_id": "019bef38-f2bc-73fc-bdbc-228ff5a8421e"
}
```

---

## Import-Status abfragen

### Status eines einzelnen Imports

```
GET /customer/folders/imports/{log_id}/status
```

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://[domain]/api/customer/folders/imports/019bef38-f2bc-73fc-bdbc-228ff5a8421e/status"
```

**Response (200 OK):**

```json
{
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
}
```

**Mögliche Status-Werte:**

| Status | Beschreibung |
|--------|--------------|
| `pending` | Import wartet auf Verarbeitung |
| `processing` | Import wird gerade verarbeitet |
| `completed` | Import erfolgreich abgeschlossen |
| `failed` | Import fehlgeschlagen (siehe `error_message`) |

---

### Liste aller Imports

```
GET /customer/folders/imports
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `per_page` | integer | Einträge pro Seite (Standard: 15, Maximum: 100) |

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://[domain]/api/customer/folders/imports?per_page=10"
```

---

## Fehlercodes

| HTTP-Code | Bedeutung |
|-----------|-----------|
| `202` | Import erfolgreich in Queue eingereiht |
| `200` | Statusabfrage erfolgreich |
| `401` | Nicht authentifiziert (Token fehlt oder ungültig) |
| `404` | Import-Log nicht gefunden |
| `422` | Validierungsfehler (ungültige Daten) |
| `500` | Serverfehler |

**Beispiel Validierungsfehler (422):**

```json
{
  "success": false,
  "errors": {
    "source": ["The source field is required."],
    "data.folder.folder_name": ["The folder name must not exceed 255 characters."]
  }
}
```

---

## Automatische Features

- **Airport-Matching:** IATA-Codes (z.B. `MUC`, `PMI`) werden automatisch zu vollständigen Flughafendaten aufgelöst inkl. Koordinaten und Ländercode
- **Country-Matching:** Ländercodes werden automatisch validiert und zugeordnet
- **Timeline-Generierung:** Aus Hotels, Flügen, Kreuzfahrten und Mietwagen wird automatisch eine Reise-Timeline erstellt
- **Geocoding:** Hotel- und Standortdaten werden für die Kartendarstellung geocodiert

---

## Support

Bei Fragen zur API wenden Sie sich an Ihren Ansprechpartner bei Passolution.
