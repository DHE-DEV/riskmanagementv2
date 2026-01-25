# Folder Import API - Vollständige Dokumentation

## Übersicht

Die Folder Import API ermöglicht das automatische Importieren von Reisedaten (Folders) in das System. Die API unterstützt komplexe Reisestrukturen mit Hotels, Flügen, Kreuzfahrten und Mietwagen.

## Authentifizierung

Die API verwendet Laravel Sanctum Token-basierte Authentifizierung.

### Token generieren

**Endpoint:** `POST /my-travelers/api-tokens/generate`

**Response:**
```json
{
  "success": true,
  "token": "2|RHej0fNgjGSzvPrEcSuY7nMGI7fldCnOMoBrpl2T173373b5",
  "message": "API Token erfolgreich generiert"
}
```

### Token verwenden

Alle API-Requests benötigen folgenden Header:
```
Authorization: Bearer {YOUR_TOKEN}
```

## Basis-Endpoint

```
POST /api/customer/folders/import
```

**Headers:**
- `Content-Type: application/json`
- `Accept: application/json`
- `Authorization: Bearer {YOUR_TOKEN}`

## Request-Struktur

### Root-Level

| Feld | Typ | Erforderlich | Beschreibung |
|------|-----|--------------|--------------|
| `source` | string | ✓ | Import-Quelle: `api`, `file`, `manual` |
| `provider` | string | ✓ | Name des Datenlieferanten (max. 128 Zeichen) |
| `data` | object | ✓ | Enthält alle Reisedaten |
| `mapping_config` | object | ✗ | Optionale Mapping-Konfiguration |

### Data-Struktur

Das `data`-Objekt enthält:
- `folder` (required) - Hauptreisedaten
- `customer` (optional) - Kundendaten
- `participants` (optional) - Array von Reiseteilnehmern
- `itineraries` (required) - Array von Reiserouten

---

## 1. Folder (Hauptreise)

### Feldübersicht

| Feld | Typ | Erforderlich | Max. Länge | Beschreibung |
|------|-----|--------------|------------|--------------|
| `folder_number` | string | ✗ | 64 | Eindeutige Vorgangsnummer (wird automatisch generiert falls nicht angegeben) |
| `folder_name` | string | ✗ | 255 | Name der Reise |
| `travel_start_date` | date | ✗ | - | Reisebeginn (Format: YYYY-MM-DD) |
| `travel_end_date` | date | ✗ | - | Reiseende (Format: YYYY-MM-DD) |
| `primary_destination` | string | ✗ | 255 | Hauptziel der Reise |
| `status` | enum | ✗ | - | Status: `draft`, `confirmed`, `active`, `completed`, `cancelled` |
| `travel_type` | enum | ✗ | - | Reiseart: `business`, `leisure`, `mixed` |
| `agent_name` | string | ✗ | 255 | Name des bearbeitenden Agenten |
| `notes` | text | ✗ | - | Interne Notizen |
| `currency` | string | ✗ | 3 | Währungscode (ISO 4217, default: EUR) |
| `custom_field_1_label` | string | ✗ | 100 | Bezeichnung für Freifeld 1 |
| `custom_field_1_value` | text | ✗ | - | Wert für Freifeld 1 (z.B. Buchungsnummer, URL) |
| `custom_field_2_label` | string | ✗ | 100 | Bezeichnung für Freifeld 2 |
| `custom_field_2_value` | text | ✗ | - | Wert für Freifeld 2 |
| `custom_field_3_label` | string | ✗ | 100 | Bezeichnung für Freifeld 3 |
| `custom_field_3_value` | text | ✗ | - | Wert für Freifeld 3 |
| `custom_field_4_label` | string | ✗ | 100 | Bezeichnung für Freifeld 4 |
| `custom_field_4_value` | text | ✗ | - | Wert für Freifeld 4 |
| `custom_field_5_label` | string | ✗ | 100 | Bezeichnung für Freifeld 5 |
| `custom_field_5_value` | text | ✗ | - | Wert für Freifeld 5 |

### Beispiel

```json
{
  "folder": {
    "folder_name": "Mallorca Sommerurlaub 2026",
    "travel_start_date": "2026-07-15",
    "travel_end_date": "2026-07-29",
    "primary_destination": "Palma, Spanien",
    "travel_type": "leisure",
    "status": "confirmed",
    "agent_name": "Maria Schmidt",
    "currency": "EUR",
    "custom_field_1_label": "TUI Buchungsnummer",
    "custom_field_1_value": "TUI-2026-12345",
    "custom_field_2_label": "Versicherungspolice",
    "custom_field_2_value": "https://insurance.example.com/POL-98765",
    "custom_field_3_label": "Notfallkontakt",
    "custom_field_3_value": "+34 971 123 456"
  }
}
```

---

## 2. Customer (Kundendaten)

### Feldübersicht

| Feld | Typ | Erforderlich | Max. Länge | Beschreibung |
|------|-----|--------------|------------|--------------|
| `salutation` | enum | ✗ | - | Anrede: `mr`, `mrs`, `diverse` (oder `Herr`, `Frau`, `Divers`) |
| `title` | string | ✗ | 64 | Titel (z.B. Dr., Prof.) |
| `first_name` | string | ✓ | 128 | Vorname |
| `last_name` | string | ✓ | 128 | Nachname |
| `email` | string | ✗ | 255 | E-Mail-Adresse |
| `phone` | string | ✗ | 64 | Telefonnummer |
| `mobile` | string | ✗ | 64 | Mobilnummer |
| `street` | string | ✗ | 255 | Straße |
| `house_number` | string | ✗ | 20 | Hausnummer |
| `postal_code` | string | ✗ | 20 | Postleitzahl |
| `city` | string | ✗ | 128 | Stadt |
| `country_code` | string | ✗ | 2 | Ländercode (ISO 3166-1 alpha-2) |
| `birth_date` | date | ✗ | - | Geburtsdatum (YYYY-MM-DD) |
| `nationality` | string | ✗ | 2 | Nationalität (ISO 3166-1 alpha-2) |
| `notes` | text | ✗ | - | Notizen |

### Salutation Mapping

Das System akzeptiert sowohl englische als auch deutsche Anreden:
- `Herr`, `Mr`, `Mr.`, `Mister` → `mr`
- `Frau`, `Mrs`, `Mrs.`, `Ms`, `Ms.`, `Miss` → `mrs`
- `Divers`, `Diverse`, `Other` → `diverse`

### Beispiel

```json
{
  "customer": {
    "salutation": "Frau",
    "first_name": "Anna",
    "last_name": "Müller",
    "email": "anna.mueller@example.com",
    "phone": "+49 89 12345678",
    "mobile": "+49 170 1234567",
    "street": "Hauptstraße",
    "house_number": "42",
    "postal_code": "80331",
    "city": "München",
    "country_code": "DE",
    "birth_date": "1985-03-15",
    "nationality": "DE"
  }
}
```

---

## 3. Participants (Reiseteilnehmer)

Array von Teilnehmern, die an der Reise teilnehmen.

### Feldübersicht

| Feld | Typ | Erforderlich | Max. Länge | Beschreibung |
|------|-----|--------------|------------|--------------|
| `salutation` | enum | ✗ | - | Anrede: `mr`, `mrs`, `child`, `infant`, `diverse` |
| `title` | string | ✗ | 64 | Titel |
| `first_name` | string | ✓ | 128 | Vorname |
| `last_name` | string | ✓ | 128 | Nachname |
| `birth_date` | date | ✗ | - | Geburtsdatum (YYYY-MM-DD) |
| `nationality` | string | ✗ | 2 | Nationalität (ISO 3166-1 alpha-2) |
| `passport_number` | string | ✗ | 64 | Reisepassnummer |
| `passport_issue_date` | date | ✗ | - | Ausstellungsdatum Pass |
| `passport_expiry_date` | date | ✗ | - | Ablaufdatum Pass |
| `passport_issuing_country` | string | ✗ | 2 | Ausstellungsland Pass (ISO 3166-1) |
| `email` | string | ✗ | 255 | E-Mail-Adresse |
| `phone` | string | ✗ | 64 | Telefonnummer |
| `dietary_requirements` | text | ✗ | - | Diätanforderungen |
| `medical_conditions` | text | ✗ | - | Medizinische Bedingungen |
| `notes` | text | ✗ | - | Notizen |
| `is_main_contact` | boolean | ✗ | - | Hauptkontaktperson (default: false) |
| `participant_type` | enum | ✗ | - | Typ: `adult`, `child`, `infant` |

### Beispiel

```json
{
  "participants": [
    {
      "salutation": "Frau",
      "first_name": "Anna",
      "last_name": "Müller",
      "birth_date": "1985-03-15",
      "nationality": "DE",
      "passport_number": "C01X12345",
      "passport_expiry_date": "2030-03-15",
      "email": "anna.mueller@example.com",
      "phone": "+49 170 1234567",
      "is_main_contact": true,
      "participant_type": "adult"
    },
    {
      "salutation": "child",
      "first_name": "Max",
      "last_name": "Müller",
      "birth_date": "2015-06-20",
      "nationality": "DE",
      "dietary_requirements": "Vegetarisch",
      "participant_type": "child"
    }
  ]
}
```

---

## 4. Itineraries (Reiserouten)

Array von Reiserouten, die verschiedene Services (Flüge, Hotels, etc.) enthalten.

### Feldübersicht

| Feld | Typ | Erforderlich | Max. Länge | Beschreibung |
|------|-----|--------------|------------|--------------|
| `booking_reference` | string | ✗ | 64 | Buchungsreferenz |
| `itinerary_name` | string | ✗ | 255 | Name der Reiseroute |
| `start_date` | date | ✗ | - | Startdatum (YYYY-MM-DD) |
| `end_date` | date | ✗ | - | Enddatum (YYYY-MM-DD) |
| `status` | enum | ✗ | - | Status: `pending`, `confirmed`, `cancelled`, `completed` |
| `provider_name` | string | ✗ | 255 | Anbieter/Veranstalter |
| `provider_reference` | string | ✗ | 128 | Anbieter-Referenznummer |
| `currency` | string | ✗ | 3 | Währung (ISO 4217) |
| `notes` | text | ✗ | - | Notizen |
| `hotels` | array | ✗ | - | Array von Hotel-Services |
| `flights` | array | ✗ | - | Array von Flug-Services |
| `ships` | array | ✗ | - | Array von Schiffs-Services |
| `car_rentals` | array | ✗ | - | Array von Mietwagen-Services |

### Beispiel

```json
{
  "itineraries": [
    {
      "itinerary_name": "Mallorca Hauptreise",
      "start_date": "2026-07-15",
      "end_date": "2026-07-29",
      "status": "confirmed",
      "booking_reference": "MAL-2026-001",
      "provider_name": "TUI Deutschland",
      "currency": "EUR",
      "hotels": [...],
      "flights": [...]
    }
  ]
}
```

---

## 5. Hotels

Hotels werden innerhalb von Itineraries definiert.

### Feldübersicht

| Feld | Typ | Erforderlich | Max. Länge | Beschreibung |
|------|-----|--------------|------------|--------------|
| `hotel_name` | string | ✓ | 255 | Name des Hotels |
| `hotel_code` | string | ✗ | 64 | Hotelcode (z.B. Giata-Code) |
| `hotel_code_type` | string | ✗ | 32 | Typ des Codes (z.B. "giata") |
| `street` | string | ✗ | 255 | Straße |
| `postal_code` | string | ✗ | 20 | Postleitzahl |
| `city` | string | ✗ | 128 | Stadt |
| `country_code` | string | ✗ | 2 | Ländercode (ISO 3166-1) |
| `lat` | decimal | ✗ | - | Breitengrad (WGS 84, -90 bis 90) |
| `lng` | decimal | ✗ | - | Längengrad (WGS 84, -180 bis 180) |
| `check_in_date` | date | ✓ | - | Check-in Datum (YYYY-MM-DD) |
| `check_out_date` | date | ✓ | - | Check-out Datum (YYYY-MM-DD) |
| `nights` | integer | ✗ | - | Anzahl Nächte |
| `room_type` | string | ✗ | 128 | Zimmertyp |
| `room_count` | integer | ✗ | - | Anzahl Zimmer (default: 1) |
| `board_type` | string | ✗ | 64 | Verpflegung (z.B. "All Inclusive") |
| `booking_reference` | string | ✗ | 64 | Hotelbuchungsnummer |
| `total_amount` | decimal | ✗ | - | Gesamtpreis |
| `currency` | string | ✗ | 3 | Währung |
| `status` | enum | ✗ | - | Status: `pending`, `confirmed`, `cancelled` |
| `notes` | text | ✗ | - | Notizen |

### Automatisches Matching

**WICHTIG:** Wenn Sie Geokoordinaten (`lat`, `lng`) angeben, werden Hotels automatisch auf der Karte angezeigt.

### Beispiel

```json
{
  "hotels": [
    {
      "hotel_name": "Hotel Paraíso del Mar",
      "city": "Palma",
      "country_code": "ES",
      "lat": 39.569900,
      "lng": 2.650900,
      "check_in_date": "2026-07-15",
      "check_out_date": "2026-07-29",
      "nights": 14,
      "room_type": "Superior Doppelzimmer Meerblick",
      "room_count": 1,
      "board_type": "All Inclusive",
      "booking_reference": "HTL-MAL-2026-001",
      "total_amount": 2450.00,
      "currency": "EUR",
      "status": "confirmed"
    }
  ]
}
```

---

## 6. Flights (Flüge)

Flüge bestehen aus einem Haupt-Flugservice mit mehreren Segmenten.

### Flight Service

| Feld | Typ | Erforderlich | Max. Länge | Beschreibung |
|------|-----|--------------|------------|--------------|
| `booking_reference` | string | ✗ | 64 | Flugbuchungsnummer |
| `service_type` | enum | ✗ | - | Typ: `outbound`, `return`, `multi_leg` |
| `airline_pnr` | string | ✗ | 32 | Airline PNR/Locator |
| `ticket_numbers` | array | ✗ | - | Array von Ticketnummern |
| `total_amount` | decimal | ✗ | - | Gesamtpreis |
| `currency` | string | ✗ | 3 | Währung |
| `status` | enum | ✗ | - | Status: `pending`, `ticketed`, `cancelled` |
| `segments` | array | ✓ | - | Array von Flugsegmenten |

### Flight Segments

| Feld | Typ | Erforderlich | Max. Länge | Beschreibung |
|------|-----|--------------|------------|--------------|
| `segment_number` | integer | ✗ | - | Segmentnummer (default: 1) |
| `departure_airport_code` | string | ✓ | 3 | IATA Abflughafen (z.B. "MUC") |
| `departure_time` | datetime | ✓ | - | Abflugzeit (YYYY-MM-DD HH:MM:SS) |
| `departure_terminal` | string | ✗ | 16 | Abflugterminal |
| `departure_country_code` | string | ✗ | 2 | Abflugland (wird automatisch ermittelt) |
| `departure_lat` | decimal | ✗ | - | Breitengrad (wird automatisch ermittelt) |
| `departure_lng` | decimal | ✗ | - | Längengrad (wird automatisch ermittelt) |
| `arrival_airport_code` | string | ✓ | 3 | IATA Ankunftsflughafen (z.B. "PMI") |
| `arrival_time` | datetime | ✓ | - | Ankunftszeit (YYYY-MM-DD HH:MM:SS) |
| `arrival_terminal` | string | ✗ | 16 | Ankunftsterminal |
| `arrival_country_code` | string | ✗ | 2 | Ankunftsland (wird automatisch ermittelt) |
| `arrival_lat` | decimal | ✗ | - | Breitengrad (wird automatisch ermittelt) |
| `arrival_lng` | decimal | ✗ | - | Längengrad (wird automatisch ermittelt) |
| `airline_code` | string | ✗ | 3 | IATA Airline-Code (z.B. "LH") |
| `flight_number` | string | ✗ | 10 | Flugnummer (z.B. "400") |
| `aircraft_type` | string | ✗ | 32 | Flugzeugtyp (z.B. "A320") |
| `duration_minutes` | integer | ✗ | - | Flugdauer in Minuten |
| `booking_class` | string | ✗ | 2 | Buchungsklasse (z.B. "Y") |
| `cabin_class` | enum | ✗ | - | Kabinenklasse: `economy`, `premium_economy`, `business`, `first` |

### Automatisches Airport & Country Matching

**WICHTIG:** Das System führt automatisch folgendes Matching durch:

1. **Airport-Codes** (z.B. "MUC", "PMI") werden automatisch zur `airport_codes_1` Tabelle gematched
2. **Geokoordinaten** werden automatisch aus der Airport-Datenbank übernommen (falls nicht angegeben)
3. **Länder** werden über ISO-Codes automatisch zur `countries` Tabelle gematched

Sie müssen nur die **3-Letter IATA-Codes** angeben, der Rest wird automatisch ergänzt!

### Beispiel

```json
{
  "flights": [
    {
      "booking_reference": "LH-PMI-2026-001",
      "service_type": "outbound",
      "airline_pnr": "ABC123",
      "status": "ticketed",
      "total_amount": 450.00,
      "currency": "EUR",
      "segments": [
        {
          "segment_number": 1,
          "departure_airport_code": "MUC",
          "departure_time": "2026-07-15 10:00:00",
          "departure_terminal": "2",
          "arrival_airport_code": "PMI",
          "arrival_time": "2026-07-15 12:15:00",
          "airline_code": "LH",
          "flight_number": "1802",
          "aircraft_type": "A320",
          "cabin_class": "economy",
          "duration_minutes": 135
        }
      ]
    },
    {
      "booking_reference": "LH-PMI-2026-001-RTN",
      "service_type": "return",
      "airline_pnr": "ABC123",
      "status": "ticketed",
      "segments": [
        {
          "segment_number": 1,
          "departure_airport_code": "PMI",
          "departure_time": "2026-07-29 13:00:00",
          "arrival_airport_code": "MUC",
          "arrival_time": "2026-07-29 15:15:00",
          "airline_code": "LH",
          "flight_number": "1803",
          "aircraft_type": "A320",
          "cabin_class": "economy",
          "duration_minutes": 135
        }
      ]
    }
  ]
}
```

---

## 7. Ships (Kreuzfahrten)

### Feldübersicht

| Feld | Typ | Erforderlich | Max. Länge | Beschreibung |
|------|-----|--------------|------------|--------------|
| `ship_name` | string | ✓ | 255 | Name des Schiffes |
| `cruise_line` | string | ✗ | 128 | Reederei |
| `ship_code` | string | ✗ | 64 | Schiffscode |
| `embarkation_date` | date | ✓ | - | Einschiffungsdatum |
| `disembarkation_date` | date | ✓ | - | Ausschiffungsdatum |
| `nights` | integer | ✗ | - | Anzahl Nächte |
| `embarkation_port` | string | ✗ | 128 | Einstiegshafen |
| `embarkation_country_code` | string | ✗ | 2 | Land Einstiegshafen |
| `embarkation_lat` | decimal | ✗ | - | Breitengrad Einstieg |
| `embarkation_lng` | decimal | ✗ | - | Längengrad Einstieg |
| `disembarkation_port` | string | ✗ | 128 | Ausstiegshafen |
| `disembarkation_country_code` | string | ✗ | 2 | Land Ausstiegshafen |
| `disembarkation_lat` | decimal | ✗ | - | Breitengrad Ausstieg |
| `disembarkation_lng` | decimal | ✗ | - | Längengrad Ausstieg |
| `cabin_number` | string | ✗ | 32 | Kabinennummer |
| `cabin_type` | string | ✗ | 128 | Kabinentyp |
| `cabin_category` | string | ✗ | 64 | Kabinenkategorie |
| `deck` | string | ✗ | 32 | Deck |
| `booking_reference` | string | ✗ | 64 | Buchungsnummer |
| `total_amount` | decimal | ✗ | - | Gesamtpreis |
| `currency` | string | ✗ | 3 | Währung |
| `status` | enum | ✗ | - | Status: `pending`, `confirmed`, `cancelled` |
| `port_calls` | array | ✗ | - | JSON Array der Hafenanlaufstellen |
| `notes` | text | ✗ | - | Notizen |

### Beispiel

```json
{
  "ships": [
    {
      "ship_name": "Mein Schiff 3",
      "cruise_line": "TUI Cruises",
      "embarkation_date": "2026-07-15",
      "disembarkation_date": "2026-07-29",
      "nights": 14,
      "embarkation_port": "Palma de Mallorca",
      "embarkation_country_code": "ES",
      "embarkation_lat": 39.5696,
      "embarkation_lng": 2.6502,
      "disembarkation_port": "Palma de Mallorca",
      "disembarkation_country_code": "ES",
      "cabin_number": "8042",
      "cabin_type": "Balkonkabine",
      "cabin_category": "Premium",
      "deck": "8",
      "booking_reference": "CRU-MS3-2026-001",
      "total_amount": 3500.00,
      "currency": "EUR",
      "status": "confirmed",
      "port_calls": [
        {
          "port": "Barcelona",
          "country": "ES",
          "arrival": "2026-07-17",
          "departure": "2026-07-17"
        },
        {
          "port": "Marseille",
          "country": "FR",
          "arrival": "2026-07-19",
          "departure": "2026-07-19"
        }
      ]
    }
  ]
}
```

---

## 8. Car Rentals (Mietwagen)

### Feldübersicht

| Feld | Typ | Erforderlich | Max. Länge | Beschreibung |
|------|-----|--------------|------------|--------------|
| `rental_company` | string | ✗ | 128 | Mietwagenunternehmen |
| `booking_reference` | string | ✗ | 64 | Buchungsnummer |
| `pickup_location` | string | ✓ | 255 | Abholstation |
| `pickup_country_code` | string | ✗ | 2 | Land Abholstation |
| `pickup_lat` | decimal | ✗ | - | Breitengrad Abholung |
| `pickup_lng` | decimal | ✗ | - | Längengrad Abholung |
| `pickup_datetime` | datetime | ✓ | - | Abholzeitpunkt |
| `return_location` | string | ✓ | 255 | Rückgabestation |
| `return_country_code` | string | ✗ | 2 | Land Rückgabestation |
| `return_lat` | decimal | ✗ | - | Breitengrad Rückgabe |
| `return_lng` | decimal | ✗ | - | Längengrad Rückgabe |
| `return_datetime` | datetime | ✓ | - | Rückgabezeitpunkt |
| `vehicle_category` | string | ✗ | 64 | Fahrzeugkategorie (z.B. "SCAR") |
| `vehicle_type` | string | ✗ | 128 | Fahrzeugtyp (z.B. "Kleinwagen") |
| `vehicle_make_model` | string | ✗ | 255 | Marke/Modell (z.B. "VW Golf") |
| `transmission` | enum | ✗ | - | Getriebe: `manual`, `automatic` |
| `fuel_type` | enum | ✗ | - | Kraftstoff: `petrol`, `diesel`, `electric`, `hybrid` |
| `rental_days` | integer | ✗ | - | Anzahl Miettage |
| `total_amount` | decimal | ✗ | - | Gesamtpreis |
| `currency` | string | ✗ | 3 | Währung |
| `insurance_options` | array | ✗ | - | JSON Array der Versicherungen |
| `extras` | array | ✗ | - | JSON Array der Extras |
| `status` | enum | ✗ | - | Status: `pending`, `confirmed`, `picked_up`, `returned`, `cancelled` |
| `notes` | text | ✗ | - | Notizen |

### Beispiel

```json
{
  "car_rentals": [
    {
      "rental_company": "Sixt",
      "booking_reference": "SIXT-PMI-2026-001",
      "pickup_location": "Palma Flughafen",
      "pickup_country_code": "ES",
      "pickup_lat": 39.5517,
      "pickup_lng": 2.7388,
      "pickup_datetime": "2026-07-15 14:00:00",
      "return_location": "Palma Flughafen",
      "return_country_code": "ES",
      "return_lat": 39.5517,
      "return_lng": 2.7388,
      "return_datetime": "2026-07-29 10:00:00",
      "vehicle_category": "CCAR",
      "vehicle_type": "Kompaktklasse",
      "vehicle_make_model": "VW Golf oder ähnlich",
      "transmission": "manual",
      "fuel_type": "diesel",
      "rental_days": 14,
      "total_amount": 420.00,
      "currency": "EUR",
      "insurance_options": [
        {
          "type": "Vollkasko",
          "amount": 150.00
        }
      ],
      "extras": [
        {
          "type": "GPS Navigation",
          "amount": 70.00
        }
      ],
      "status": "confirmed"
    }
  ]
}
```

---

## Vollständiges Beispiel

```json
{
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
            "lat": 39.569900,
            "lng": 2.650900,
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
}
```

---

## Response

### Erfolgreiche Response (202 Accepted)

```json
{
  "success": true,
  "message": "Import queued successfully",
  "log_id": "019bef38-f2bc-73fc-bdbc-228ff5a8421e"
}
```

### Fehler-Response (422 Validation Error)

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

## Import-Status abfragen

```
GET /api/customer/folders/imports/{logId}/status
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "019bef38-f2bc-73fc-bdbc-228ff5a8421e",
    "status": "completed",
    "folder_id": "019bef39-1510-732d-afca-fb5aa0d04705",
    "records_imported": 1,
    "records_failed": 0,
    "error_message": null,
    "started_at": "2026-01-24 09:57:34",
    "completed_at": "2026-01-24 09:57:35",
    "duration_seconds": 1
  }
}
```

**Status-Werte:**
- `pending` - Import wartet auf Verarbeitung
- `processing` - Import wird gerade verarbeitet
- `completed` - Import erfolgreich abgeschlossen
- `failed` - Import fehlgeschlagen

---

## Best Practices

### 1. Geokoordinaten
- Verwenden Sie WGS 84 (SRID 4326)
- Breitengrad: -90 bis +90
- Längengrad: -180 bis +180
- Werden automatisch auf Karte angezeigt

### 2. Airport-Codes
- Verwenden Sie IATA 3-Letter Codes (z.B. "MUC", "JFK")
- System matched automatisch zu Airport-Datenbank
- Geokoordinaten und Länder werden automatisch ergänzt

### 3. Datumsformate
- Datum: `YYYY-MM-DD` (z.B. "2026-07-15")
- DateTime: `YYYY-MM-DD HH:MM:SS` (z.B. "2026-07-15 10:00:00")

### 4. Währungen
- Verwenden Sie ISO 4217 Codes (EUR, USD, GBP, etc.)

### 5. Ländercodes
- Verwenden Sie ISO 3166-1 alpha-2 (z.B. "DE", "FR", "ES")

### 6. Custom Fields
- 5 flexible Freifelder verfügbar
- Unterstützen URLs (werden automatisch als Links erkannt)
- Ideal für Buchungsnummern, Versicherungspolicen, Notfallkontakte

### 7. Queue-basierter Import
- Import läuft im Hintergrund
- Sofortige Response mit log_id
- Status kann abgefragt werden
- Timeline wird automatisch generiert

---

## Fehlerbehandlung

### Validation Errors
- HTTP 422: Validierungsfehler
- Detaillierte Fehlermeldungen pro Feld

### Import Errors
- Import-Log enthält `error_message`
- Status wird auf `failed` gesetzt
- Fehler können über Status-Endpoint abgefragt werden

---

## Rate Limiting

- Standard: 60 Requests pro Minute pro Token
- Bei Überschreitung: HTTP 429 (Too Many Requests)

---

## Support

Bei Fragen oder Problemen:
- Prüfen Sie die Validierungsregeln
- Kontrollieren Sie Datumsformate
- Verifizieren Sie Airport-Codes
- Checken Sie Import-Status über Status-Endpoint

**Version:** 1.0
**Letzte Aktualisierung:** 2026-01-24
