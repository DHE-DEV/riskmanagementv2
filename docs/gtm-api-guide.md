# Global Travel Monitor Events API – Kundenanleitung

## Übersicht

Die Events API bietet **read-only** Zugriff auf alle aktuell aktiven Sicherheits- und Reiserisiko-Events. Dies umfasst sowohl von Global Travel Monitor gepflegte Events als auch Events, die von API-Partnern eingestellt wurden. Es werden nur freigegebene, aktive und nicht archivierte Events angezeigt.

Die API ermöglicht die Abfrage aktueller Events gefiltert nach Risikostufe, Land, Event-Typ und Region sowie Länder-Übersichten mit Anzahl aktiver Events.

---

## Authentifizierung

Alle API-Aufrufe erfordern einen **Bearer-Token** im HTTP-Header:

```
Authorization: Bearer {API_TOKEN}
```

Den Token erhalten Sie von Ihrem Ansprechpartner bei Passolution.

---

## Base-URL

```
https://platform.passolution.de/api/v1
```

Die bisherigen Adressen `https://api.global-travel-monitor.de/v1` und `https://global-travel-monitor.eu/api/v1` bleiben weiterhin gültig; bestehende Integrationen müssen nicht umgestellt werden.

---

## Rate Limit

Standardmäßig sind **60 Requests pro Minute** erlaubt. Das Limit kann je Kunde höher eingestellt sein; den aktuellen Wert liefern die Antwort-Header `X-RateLimit-Limit` und `X-RateLimit-Remaining`. Bei Überschreitung erhalten Sie einen `429 Too Many Requests`-Response. Prüfen Sie den `Retry-After`-Header für die Wartezeit in Sekunden.

---

## Pagination

Der Events-Endpoint liefert alle aktiven Events (einschließlich zukünftiger Events), paginiert über die Query-Parameter `page` und `per_page` (Standard: 25, Maximum: **100** pro Seite). Pagination-Metadaten sind im `meta`-Objekt jeder Antwort enthalten.

Es werden nur Events zurückgegeben, die freigegeben (`approved`), aktiv und nicht archiviert sind. Mit den Parametern `start_date` und `end_date` kann der Zeitraum eingegrenzt werden.

---

## Herkunft der Events (Source)

Jedes Event enthält ein `source`-Objekt, das die Herkunft anzeigt:

| `source.type` | Bedeutung |
|----------------|-----------|
| `manual` | Manuell von Global Travel Monitor erstellt |
| `api_client` | Von einem API-Partner über die Event API eingestellt |
| `passolution_infosystem` | Automatisch aus dem Passolution Infosystem importiert |

Bei Events vom Typ `api_client` enthält `source.name` den Namen des Partners (z.B. "Partner XY GmbH").

Mit dem `source`-Filter können Sie gezielt Events einer bestimmten Herkunft abfragen:

```bash
# Nur manuell erstellte Events
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/events?source=manual"

# Nur Events von einem bestimmten Partner (nach Name)
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/events?source=Partner%20XY%20GmbH"
```

---

## Events

### Events auflisten

```
GET /v1/events
```

**Query-Parameter:**

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|--------------|
| `risk_level` | string | Nein | Filter nach Risikostufe: `high`, `medium`, `low`, `info` |
| `country` | string | Nein | Filter nach Ländercode – ISO alpha-2 (z.B. `DE`) oder alpha-3 (z.B. `DEU`) |
| `event_category` | string | Nein | Filter nach Event-Kategorie-Code (z.B. `safety`, siehe Tabelle unten) |
| `region` | integer | Nein | Filter nach Region-ID (numerische ID) |
| `source` | string | Nein | Filter nach Event-Herkunft (z.B. `manual`, `passolution_infosystem` oder Name des API-Partners) |
| `start_date` | date | Nein | Nur Events ab diesem Datum (z.B. `2026-03-01`) |
| `end_date` | date | Nein | Nur Events bis zu diesem Datum (z.B. `2026-04-30`) |
| `per_page` | integer | Nein | Einträge pro Seite (Standard: 25, Maximum: 100) |
| `page` | integer | Nein | Seitennummer (Standard: 1) |

**Beispiele:**

```bash
# Alle aktiven Events (paginiert)
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/events?per_page=25&page=1"

# Nur Events mit hoher Risikostufe
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/events?risk_level=high"

# Events für ein bestimmtes Land
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/events?country=TR"

# Events eines bestimmten Typs
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/events?event_category=safety"

# Nur manuell erstellte Events
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/events?source=manual"

# Events in einem Zeitraum
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/events?start_date=2026-03-01&end_date=2026-03-31"

# Filter kombinieren
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/events?risk_level=high&country=TR&source=manual&per_page=10"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "title": "Earthquake in Turkey",
      "description": "A 6.2 magnitude earthquake struck southeastern Turkey.",
      "risk_level": "high",
      "start_date": "2025-03-15T08:30:00Z",
      "end_date": null,
      "latitude": 37.7749,
      "longitude": 35.3214,
      "is_nationwide": false,
      "event_categories": [
        {
          "code": "safety",
          "name": "Sicherheit"
        }
      ],
      "countries": [
        {
          "iso_code": "TR",
          "iso3_code": "TUR",
          "name_de": "Tuerkei",
          "name_en": "Turkey",
          "continent": "Asia",
          "latitude": 37.7749,
          "longitude": 35.3214
        }
      ],
      "source": {
        "type": "api_client",
        "name": "Partner XY GmbH"
      },
      "created_at": "2025-03-15T09:00:00Z",
      "updated_at": "2025-03-15T10:15:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 142,
    "last_page": 15
  }
}
```

---

### Einzelnes Event anzeigen

```
GET /v1/events/{uuid}
```

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/events/550e8400-e29b-41d4-a716-446655440000"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Earthquake in Turkey",
    "description": "A 6.2 magnitude earthquake struck southeastern Turkey.",
    "risk_level": "high",
    "start_date": "2025-03-15T08:30:00Z",
    "end_date": null,
    "latitude": 37.7749,
    "longitude": 35.3214,
    "is_nationwide": false,
    "event_categories": [
      {
        "code": "safety",
        "name": "Sicherheit"
      }
    ],
    "countries": [
      {
        "iso_code": "TR",
        "iso3_code": "TUR",
        "name_de": "Tuerkei",
        "name_en": "Turkey",
        "continent": "Asia",
        "latitude": 37.7749,
        "longitude": 35.3214
      }
    ],
    "source": {
      "type": "api_client",
      "name": "Partner XY GmbH"
    },
    "created_at": "2025-03-15T09:00:00Z",
    "updated_at": "2025-03-15T10:15:00Z"
  }
}
```

---

### Events im Umkreis suchen (Nearby)

```
GET /v1/events/nearby
```

Sucht aktive Events im Umkreis eines Standorts. Der Standort kann entweder über einen **3-Letter IATA-Code** (z.B. Flughafen) oder über **Geokoordinaten** angegeben werden. Events mit `is_nationwide: true` werden unabhängig vom Radius geliefert, sobald der Abfragepunkt in einem der betroffenen Länder liegt.

**Query-Parameter:**

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|--------------|
| `code` | string | Ja* | 3-Letter IATA-Code (z.B. `FRA`, `MUC`, `JFK`) |
| `latitude` | numeric | Ja* | Breitengrad (-90 bis 90) |
| `longitude` | numeric | Ja* | Längengrad (-180 bis 180) |
| `radius` | numeric | Ja | Umkreis in Kilometern (1–20.000) |
| `per_page` | integer | Nein | Ergebnisse pro Seite (1–100, Standard: 25) |
| `page` | integer | Nein | Seitennummer (Standard: 1) |

\* Entweder `code` oder `latitude` + `longitude` muss angegeben werden.

**Beispiele:**

```bash
# Mit 3-Letter-Code
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/events/nearby?code=FRA&radius=500"

# Mit Geokoordinaten
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/events/nearby?latitude=50.0379&longitude=8.5622&radius=500"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "title": "Storm Warning Central Europe",
      "description": "Severe storm warning for the Frankfurt area.",
      "risk_level": "medium",
      "is_nationwide": false,
      "start_date": "2026-03-20T06:00:00+00:00",
      "end_date": "2026-03-21T18:00:00+00:00",
      "latitude": 50.1109,
      "longitude": 8.6821,
      "event_categories": [
        {
          "code": "environment",
          "name": "Umweltereignisse"
        }
      ],
      "countries": [
        {
          "iso_code": "DE",
          "iso3_code": "DEU",
          "name_de": "Deutschland",
          "name_en": "Germany",
          "continent": "Europe",
          "latitude": 50.1109,
          "longitude": 8.6821
        }
      ],
      "source": {
        "type": "manual",
        "name": null
      },
      "created_at": "2026-03-20T07:00:00+00:00",
      "updated_at": "2026-03-20T07:00:00+00:00"
    }
  ],
  "location": {
    "code": "FRA",
    "name": "Frankfurt Airport",
    "latitude": 50.030241,
    "longitude": 8.561096,
    "radius_km": 500
  },
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 3,
    "last_page": 1
  }
}
```

---

## Länder mit aktiven Events

```
GET /v1/events/countries
```

Gibt eine Liste aller Länder zurück, die mindestens ein aktives Event haben, zusammen mit der Anzahl aktiver Events. Sortiert nach Anzahl (absteigend). Nicht paginiert.

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/events/countries"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "iso_code": "DE",
      "iso3_code": "DEU",
      "name_de": "Deutschland",
      "name_en": "Germany",
      "continent": "Europe",
      "continent_de": "Europa",
      "lat": 51.1657,
      "lng": 10.4515,
      "is_eu_member": true,
      "is_schengen_member": true,
      "active_events_count": 3
    },
    {
      "iso_code": "TR",
      "iso3_code": "TUR",
      "name_de": "Tuerkei",
      "name_en": "Turkey",
      "continent": "Asia",
      "continent_de": "Asien",
      "lat": 38.9637,
      "lng": 35.2433,
      "is_eu_member": false,
      "is_schengen_member": false,
      "active_events_count": 7
    }
  ]
}
```

---

## Basisdaten

### Kontinente

```
GET /v1/continents
```

Gibt eine Liste aller Kontinente zurück.

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/continents"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "code": "EU",
      "name_de": "Europa",
      "name_en": "Europe",
      "lat": 54.526,
      "lng": 15.2551
    },
    {
      "code": "AS",
      "name_de": "Asien",
      "name_en": "Asia",
      "lat": 34.0479,
      "lng": 100.6197
    }
  ]
}
```

---

### Länder

```
GET /v1/countries
```

Gibt eine Liste aller Länder zurück. Optional nach Kontinent filterbar.

**Query-Parameter:**

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|--------------|
| `continent` | string | Nein | Filter nach Kontinent-Code (z.B. `EU`, `AS`) |

**Beispiele:**

```bash
# Alle Länder
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/countries"

# Nur europäische Länder
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/countries?continent=EU"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "iso_code": "DE",
      "iso3_code": "DEU",
      "name_de": "Deutschland",
      "name_en": "Germany",
      "continent": "Europe",
      "continent_de": "Europa",
      "lat": 51.1657,
      "lng": 10.4515,
      "is_eu_member": true,
      "is_schengen_member": true,
      "flag_url": "https://flagcdn.com/de.svg",
      "hero_image_url": "https://global-travel-monitor.eu/images/countries/de.jpg"
    }
  ]
}
```

---

### Länderinformationen

```
GET /v1/countries/{code}
```

Liefert alle Angaben eines Landes strukturiert: Grunddaten, Länderbeschreibung, Reiseinformationen, Strom, Trinkgeld, Taxi-Apps, Mobilfunkanbieter, Feiertage, Bilder und Risikoprofil. `{code}` ist der ISO-3166-1-Code mit zwei (`DE`) oder drei Buchstaben (`DEU`), Groß-/Kleinschreibung spielt keine Rolle.

Mehrsprachige Texte kommen als Objekt je Sprache (`{"de": "…", "en": "…", "nl": "…"}`). Mit `lang` wird daraus der Text in dieser Sprache; fehlt er, kommt die deutsche Fassung.

**Query-Parameter:**

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|--------------|
| `lang` | string | Nein | Nur diese Sprache (`de`, `en`, `nl`) statt aller Sprachen |
| `year` | integer | Nein | Jahr der Feiertage (Standard: laufendes Jahr) |

**Beispiele:**

```bash
# Alle Angaben zu Deutschland, alle Sprachen
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/countries/DE"

# Nur Englisch, Feiertage 2027
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/countries/DEU?lang=en&year=2027"
```

**Response (200 OK, gekürzt):**

```json
{
  "success": true,
  "data": {
    "iso_code": "DE",
    "iso3_code": "DEU",
    "name": {"de": "Deutschland", "en": "Germany", "nl": "Duitsland"},
    "continent": {"code": "EU", "name": {"de": "Europa", "en": "Europe"}},
    "territory": {"type": "sovereign", "type_label": "Souveräner Staat", "parent_country": null},
    "membership": {"eu": true, "schengen": true},
    "currency": {"code": "EUR", "name": "Euro", "symbol": "€"},
    "phone_prefix": "+49",
    "timezone": "Europe/Berlin",
    "languages": ["de"],
    "population": 84000000,
    "area_km2": 357588,
    "coordinates": {"lat": 51.1657, "lng": 10.4515},
    "capital": {"name": {"de": "Berlin", "en": "Berlin"}, "lat": 52.52, "lng": 13.405},
    "flag": {"svg_url": "https://flagcdn.com/de.svg", "emoji": "🇩🇪"},
    "description": {
      "short": {"de": "…", "en": "…", "nl": "…"},
      "long": {"de": "…", "en": "…", "nl": "…"},
      "known_for": {"de": ["Berlin", "Oktoberfest"], "en": ["Berlin", "Oktoberfest"]}
    },
    "travel_info": {
      "intro": {"de": "…"},
      "driving_side": "right",
      "driving_side_label": "Rechtsverkehr",
      "emergency": {"general": "112", "police": "110", "ambulance": "112", "fire": "112"},
      "religions": [{"key": "christianity", "name": {"de": "Christentum", "en": "Christianity"}}],
      "national_day": {"date": "1990-10-03", "day_month": "10-03", "name": {"de": "Tag der Deutschen Einheit"}}
    },
    "power": {
      "voltage": 230,
      "frequency": 50,
      "plug_types": [{"type": "F", "description": "Schuko (Deutschland)", "image": "https://…/images/plug-types/f.svg", "image_png": "https://…/images/plug-types/png/f.png"}],
      "notes": {}
    },
    "tipping": {
      "hotels": {"mode": "range", "from": 1, "to": 2, "unit": "amount", "currency": "EUR", "description": {"de": "…"}},
      "guides": null,
      "restaurants": {"mode": "range", "from": 5, "to": 10, "unit": "percent", "currency": null, "description": {"de": "…"}},
      "taxi": {"mode": "fixed", "from": 1, "to": null, "unit": "amount", "currency": "EUR", "description": {}}
    },
    "airlines": [{"name": "Lufthansa", "iata_code": "LH", "icao_code": "DLH", "headquarters": "Köln", "website_url": "https://www.lufthansa.com", "booking_url": "…"}],
    "airports": [{"iata_code": "FRA", "icao_code": "EDDF", "name": "Frankfurt Airport", "type": "international", "city": {"de": "Frankfurt am Main", "en": "Frankfurt"}}],
    "taxi_apps": [{"name": "FREENOW", "description": {"de": "…"}, "logo_url": "…", "website_url": "…", "app_store_url": "…", "play_store_url": "…"}],
    "mobile_operators": [{"name": "Telekom", "description": {}, "logo_url": "…", "website_url": "…", "prepaid_url": null, "offers_esim": true}],
    "holidays": {
      "year": 2026,
      "items": [{"id": 1, "date": "2026-10-03", "weekday": 6, "name": {"de": "Tag der Deutschen Einheit"}, "comment": {}, "is_national": true, "regions": []}]
    },
    "images": {"hero": null, "hero_url": "https://…/images/countries/de.jpg", "gallery": []},
    "risk_profile": {
      "overall": {"level": 2, "label": "Niedrig"},
      "categories": {
        "security": {"label": "Sicherheit", "fields": {"overall_risk_level": {"value": 2, "label": "Niedrig", "note": {}}, "...": "…"}},
        "...": "…"
      }
    },
    "updated_at": "2026-10-08T15:20:11+00:00"
  }
}
```

Hinweise zu einzelnen Feldern:

- `tipping.*.mode`: `range` (von–bis) oder `fixed` (fester Wert, dann ist `to` leer); `unit`: `percent` oder `amount` in `currency`.
- `holidays.items[].weekday`: ISO-Wochentag, 1 = Montag; `regions` leer bedeutet landesweit.
- `risk_profile.categories.*.fields.*`: `name` (Bezeichnung des Punkts), `type` (`level`, `bool`, `number`, `tags`, `text`, `textarea`), `value` (Stufen 1–5 mit `label`, Ja/Nein, Zahlen, Listen oder Text); `note` nur bei Punkten mit Notiz.
- Fehlt ein Bereich (z. B. kein Risikoprofil), ist der Wert `null`; leere mehrsprachige Texte sind `{}`.

---

### Landesgrenzen (GeoJSON)

```
GET /v1/countries/{code}/boundary
GET /v1/boundaries?codes=EG,DE,FR
```

Grenzen als GeoJSON für Karten – ein Land als `Feature`, mehrere Länder (bis zu 60 je Abruf) als `FeatureCollection`. Die Geometrie (`Polygon` oder `MultiPolygon`, Koordinaten als `[lng, lat]`) ist auf rund 5 km vereinfacht und damit für Landes- und Kontinentkarten gedacht; `bbox` ist `[min_lng, min_lat, max_lng, max_lat]`. Quelle: Natural Earth. Länder ohne Grenzdaten fehlen in der Sammlung bzw. liefern 404.

**Query-Parameter:**

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|--------------|
| `codes` | string | Ja (nur `/v1/boundaries`) | ISO-Codes, kommagetrennt |
| `lang` | string | Nein | Sprache für `properties.name` (`de`, `en`, `nl`) |

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/boundaries?codes=EG,DE&lang=de"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "type": "FeatureCollection",
    "features": [
      {
        "type": "Feature",
        "properties": {"iso_code": "EG", "iso3_code": "EGY", "name": "Ägypten"},
        "bbox": [24.7, 21.99, 36.87, 31.65],
        "geometry": {"type": "MultiPolygon", "coordinates": [[[[34.9, 29.49], [34.26, 31.22], "…"]]]}
      }
    ]
  },
  "meta": {"requested": 2, "found": 1}
}
```

Hinweis für Karten: Ringe, die den 180. Längengrad kreuzen (Russland, USA, Fidschi), sollten vor dem Zeichnen auf durchgehende Längen umgerechnet werden (negative Längen + 360).

---

### Regionen eines Landes

```
GET /v1/countries/{code}/regions
```

Alle Regionen (Bundesländer, Provinzen, Kantone …) eines Landes, alphabetisch nach Name, mit der Anzahl der zugeordneten Städte. `is_popular` ist die Markierung „beliebt“ aus der Verwaltung.

**Query-Parameter:**

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|--------------|
| `lang` | string | Nein | Nur diese Sprache (`de`, `en`, `nl`) statt aller Sprachen |

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/countries/DE/regions?lang=de"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "code": "DE-BW",
      "name": "Baden-Württemberg",
      "description": null,
      "is_popular": false,
      "coordinates": {"lat": 48.6616, "lng": 9.3501},
      "cities_count": 8
    }
  ],
  "meta": {"total": 16}
}
```

---

### Städte eines Landes

```
GET /v1/countries/{code}/cities
```

Alle Städte eines Landes, alphabetisch nach Name, mit Region, Einwohnerzahl und Koordinaten. Mit `region` nur die Städte einer Region (ID aus `/v1/countries/{code}/regions`). `is_popular` ist die Markierung „beliebt“ aus der Verwaltung.

**Query-Parameter:**

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|--------------|
| `region` | integer | Nein | Nur Städte dieser Region |
| `lang` | string | Nein | Nur diese Sprache (`de`, `en`, `nl`) statt aller Sprachen |

**Beispiele:**

```bash
# Alle Städte
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/countries/DE/cities?lang=de"

# Nur Städte einer Region
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/countries/DE/cities?lang=de&region=10"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1382,
      "name": "Düsseldorf",
      "region": {"id": 12, "code": "DE-NW", "name": "Nordrhein-Westfalen"},
      "population": 620000,
      "coordinates": {"lat": 51.2277, "lng": 6.7735},
      "is_capital": false,
      "is_regional_capital": true,
      "is_popular": true
    }
  ],
  "meta": {"total": 54}
}
```

---

### Flughäfen

```
GET /v1/airports
GET /v1/airports/{code}
```

Flughäfen aus der Plattform (Quelle OurAirports plus redaktionelle Pflege). Die Liste liefert Kurzdaten und Zähler, der Einzelabruf per IATA-Code (3 Zeichen) oder ICAO-Code (4 Zeichen) zusätzlich Lounges, Hotels in der Nähe, Mobilität und die dort fliegenden Airlines. Nur aktive Einträge.

**Query-Parameter (Liste):**

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|--------------|
| `country` | string | Nein | ISO-2- oder ISO-3-Code des Landes |
| `q` | string | Nein | Suche in Name, IATA- oder ICAO-Code |
| `type` | string | Nein | `international`, `large_airport`, `medium_airport`, `small_airport` |
| `lang` | string | Nein | Sprache für Stadt- und Ländernamen (`de`, `en`, `nl`) |

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/airports/CAI?lang=de"
```

**Response (200 OK, gekürzt):**

```json
{
  "success": true,
  "data": {
    "iata_code": "CAI", "icao_code": "HECA", "name": "Cairo International Airport",
    "type": "international", "type_label": "Internationaler Flughafen",
    "city": "Kairo", "country": {"iso_code": "EG", "name": "Ägypten"},
    "coordinates": {"lat": 30.1219, "lng": 31.4056}, "timezone": "Africa/Cairo", "altitude_m": 116,
    "operates_24h": true, "website_url": "…", "security_timeslot_url": null,
    "counts": {"lounges": 3, "nearby_hotels": 2, "airlines": 48},
    "lounges": [{"name": "Ahlan Lounge", "location": "Terminal 3", "access": "Alle Passagiere mit Bordkarte", "price_per_person": 40, "children_welcome": true, "url": "…"}],
    "nearby_hotels": [{"name": "Le Méridien Cairo Airport", "distance_km": 0.2, "shuttle": true, "booking_url": "…", "notes": null}],
    "mobility": {
      "taxi": {"available": true, "info": "Vor allen Terminals", "approx_cost": null},
      "parking": {"available": true, "options": [{"name": "P1", "url": "…", "distance": "100m"}]},
      "car_rental": {"available": true, "providers": [{"name": "Hertz", "url": "…"}]},
      "airport_shuttle": {"available": false, "info": null, "url": null},
      "public_transport": {"available": true, "types": [{"name": "Metro", "url": "…"}]}
    },
    "airlines": [{"iata_code": "MS", "icao_code": "MSR", "name": "EgyptAir", "terminal": "3", "direction": "both", "cabin_classes": [{"key": "economy", "label": "Economy"}]}],
    "updated_at": "2026-02-08T09:55:19+00:00"
  }
}
```

Die Liste (`/v1/airports`) enthält je Flughafen dieselben Felder bis einschließlich `counts`; `meta.total` ist die Trefferzahl.

---

### Airlines

```
GET /v1/airlines
GET /v1/airlines/{code}
```

Fluggesellschaften aus der Plattform. Die Liste liefert Kurzdaten, der Einzelabruf per IATA-Code (2 Zeichen) oder ICAO-Code (3 Zeichen) zusätzlich Kontakt, Gepäckregeln je Kabinenklasse, Tierregelung und die angeflogenen Flughäfen. Nur aktive Einträge.

**Query-Parameter:**

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|--------------|
| `country` | string | Nein | Liste: nur Airlines mit Sitz in diesem Land (ISO-2 oder ISO-3) |
| `q` | string | Nein | Liste: Suche in Name, IATA- oder ICAO-Code |
| `include` | string | Nein | Einzelabruf: `lounges`, `hotels` (kommagetrennt) hängt Lounges bzw. Hotels an jeden Flughafen |
| `lang` | string | Nein | Sprache für Stadt- und Ländernamen (`de`, `en`, `nl`) |

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/airlines/MS?lang=de&include=lounges,hotels"
```

**Response (200 OK, gekürzt):**

```json
{
  "success": true,
  "data": {
    "iata_code": "MS", "icao_code": "MSR", "name": "EgyptAir",
    "home_country": {"iso_code": "EG", "name": "Ägypten"}, "headquarters": "Kairo",
    "website_url": "…", "booking_url": "…",
    "cabin_classes": [{"key": "economy", "label": "Economy"}, {"key": "business", "label": "Business Class"}],
    "counts": {"airports": 62},
    "contact": {"hotline": "+20 2 2696 6300", "email": null, "chat_url": null, "help_url": "…"},
    "baggage": {
      "classes": {
        "economy": {"hand": {"allowance": "1x8kg", "dimensions_cm": {"length": 55, "width": 40, "height": 20}}, "checked": {"allowance": "23kg"}},
        "premium_economy": {"hand": {"allowance": null, "dimensions_cm": null}, "checked": {"allowance": null}},
        "business": {"…": "…"}, "first": {"…": "…"}
      },
      "notes": null, "info_url": "…"
    },
    "pet_policy": {
      "allowed": true,
      "in_cabin": {"allowed": true, "max_weight": "8kg", "carrier_dimensions_cm": {"length": 55, "width": 40, "height": 26}, "weight_includes_bag": true, "advance_notice_required": true},
      "in_hold": {"allowed": true, "advance_notice_required": true, "notes": "…"},
      "restrictions": [{"key": "breed_restrictions", "label": "Rasseeinschränkungen"}],
      "info_url": "…", "notes": null
    },
    "airports": [
      {"iata_code": "CAI", "icao_code": "HECA", "name": "Cairo International Airport", "city": "Kairo", "country": "EG", "terminal": "3", "direction": "both",
       "lounges": [{"name": "Ahlan Lounge", "location": "Terminal 3", "access": "…", "price_per_person": 40, "children_welcome": true, "url": "…"}],
       "nearby_hotels": [{"name": "Le Méridien Cairo Airport", "distance_km": 0.2, "shuttle": true, "booking_url": "…", "notes": null}]}
    ],
    "updated_at": "2026-02-08T09:55:19+00:00"
  }
}
```

Gepäckangaben (`allowance`) sind Freitext, wie in der Plattform gepflegt (z. B. `1x8kg`, `23kg`); fehlende Werte sind `null`. Lounges hängen immer am Flughafen; `/v1/countries/{code}` liefert `airlines[]` und `airports[]` nur als Kurzliste mit Codes für den Absprung in diese Endpunkte.

---

### Wechselkurse

```
GET /v1/exchange-rates
```

Aktuelle Wechselkurse für Umrechnungen in Apps. Die Plattform holt die Kurse stündlich beim Anbieter (ExchangeRate-API, Stand jeweils in `updated_at`) und liefert sie aus dem Cache; bei einem Ausfall des Anbieters bleiben die letzten Kurse bis zu einem Tag verfügbar. Basis ist der Euro, andere Basiswährungen werden als Kreuzkurs berechnet.

**Query-Parameter:**

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|--------------|
| `base` | string | Nein | Basiswährung (ISO 4217), Standard `EUR` |
| `symbols` | string | Nein | Nur diese Währungen, kommagetrennt (z.B. `EGP,THB`) |

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/exchange-rates?base=EUR&symbols=EGP,USD"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "base": "EUR",
    "rates": {"EGP": 54.21, "USD": 1.08},
    "updated_at": "2026-10-09T00:02:31+00:00",
    "next_update_at": "2026-10-10T00:02:31+00:00",
    "source": "Rates by ExchangeRate-API (https://www.exchangerate-api.com)"
  }
}
```

`source` ist bei Anzeige der Kurse zu nennen (Bedingung des Anbieters). Unbekannte Basiswährung: 422, Anbieter nicht erreichbar und kein Cache: 503.

---

### Regionen

```
GET /v1/regions
```

Gibt eine Liste aller Regionen zurück. Optional nach Land filterbar. Nützlich um die gültigen Werte für den `region`-Filter zu ermitteln.

**Query-Parameter:**

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|--------------|
| `country` | string | Nein | Filter nach Ländercode – ISO alpha-2 (z.B. `DE`) oder alpha-3 (z.B. `DEU`) |

**Beispiele:**

```bash
# Alle Regionen
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/regions"

# Nur Regionen in Deutschland
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/regions?country=DE"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "id": 42,
      "name_de": "Bayern",
      "name_en": "Bavaria",
      "code": "BY",
      "country_iso_code": "DE",
      "country_name_de": "Deutschland",
      "lat": 48.7904,
      "lng": 11.4979
    }
  ]
}
```

---

### Event-Kategorien

```
GET /v1/event-categories
```

Gibt eine Liste aller verfügbaren Event-Kategorien zurück. Nützlich um die gültigen Werte für den `event_category`-Filter zu ermitteln.

**Aktuell verfügbare Kategorien:**

| Code | Name |
|------|------|
| `environment` | Umweltereignisse |
| `travel` | Reiseverkehr |
| `safety` | Sicherheit |
| `entry` | Einreisebestimmungen |
| `general` | Allgemein |
| `health` | Gesundheit |
| `strike` | Streik |

> **Hinweis:** Diese Liste kann sich ändern. Nutzen Sie den Endpoint `GET /v1/event-categories`, um stets die aktuellen Kategorien abzurufen.

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://platform.passolution.de/api/v1/event-categories"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "code": "environment",
      "name": "Umweltereignisse"
    },
    {
      "code": "safety",
      "name": "Sicherheit"
    }
  ]
}
```

---

## Datenmodelle

### Event

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | string (UUID) | Eindeutige ID des Events |
| `title` | string | Kurzer Titel des Events |
| `description` | string / null | Detaillierte Beschreibung |
| `risk_level` | string | Risikostufe: `high`, `medium`, `low`, `info` |
| `start_date` | datetime / null | Startdatum (ISO 8601) |
| `end_date` | datetime / null | Enddatum (null = andauernd) |
| `latitude` | number / null | Breitengrad |
| `longitude` | number / null | Längengrad |
| `is_nationwide` | boolean | Landesweite Geltung. Bei `true` liefert `/events/nearby` das Event unabhängig vom Radius, sobald der Abfragepunkt in einem der unter `countries` genannten Länder liegt |
| `event_categories` | array | Liste der zugewiesenen Event-Typen |
| `countries` | array | Liste betroffener Länder |
| `locations` | array | Alle Standorte des Events: je Eintrag `country_name`, `iso_code`, `region_id`, `region_name`, `city_id`, `city_name`, `latitude`, `longitude`, `location_note`, `label` |
| `source` | object | Herkunft des Events |
| `source.type` | string | Quelle: `manual`, `api_client`, `passolution_infosystem`, etc. |
| `source.name` | string / null | Name des API-Partners (bei API-Client-Events) |
| `created_at` | datetime | Erstellungszeitpunkt |
| `updated_at` | datetime | Letzter Änderungszeitpunkt |

### Event-Typ

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `code` | string | Maschinenlesbarer Code (z.B. `safety`) |
| `name` | string | Anzeigename |

### Land (Event-Kontext)

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `iso_code` | string | ISO 3166-1 alpha-2 Code (z.B. `DE`) |
| `iso3_code` | string | ISO 3166-1 alpha-3 Code (z.B. `DEU`) |
| `name_de` | string | Ländername (deutsch) |
| `name_en` | string | Ländername (englisch) |
| `continent` | string | Kontinent |
| `latitude` | number / null | Breitengrad (Event-Standort im Land) |
| `longitude` | number / null | Längengrad (Event-Standort im Land) |
| `region` | object / null | Betroffene Region: `id`, `name_de`, `name_en` |
| `city` | object / null | Betroffene Stadt: `id`, `name_de`, `name_en` |
| `location_note` | string / null | Freitext zum Standort |

### Land (Countries-Endpoint)

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `iso_code` | string | ISO 3166-1 alpha-2 Code |
| `iso3_code` | string | ISO 3166-1 alpha-3 Code |
| `name_de` | string | Ländername (deutsch) |
| `name_en` | string | Ländername (englisch) |
| `continent` | string / null | Kontinent (englisch) |
| `continent_de` | string / null | Kontinent (deutsch) |
| `lat` | number / null | Breitengrad (Zentroid) |
| `lng` | number / null | Längengrad (Zentroid) |
| `is_eu_member` | boolean | EU-Mitglied |
| `is_schengen_member` | boolean | Schengen-Mitglied |
| `name_nl` | string / null | Ländername (niederländisch) |
| `flag_url` | string / null | Flagge als SVG |
| `hero_image_url` | string / null | Titelbild für Listen |
| `active_events_count` | integer | Anzahl aktiver Events |

---

### Länderinformationen (Countries/{code}-Endpoint)

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `iso_code`, `iso3_code` | string | ISO-3166-1-Codes |
| `name` | Text | Ländername je Sprache |
| `continent` | object / null | `code`, `name` (Text) |
| `territory` | object | `type` (`sovereign`, `dependent`, `disputed`, `special`), `type_label`, `parent_country` (`iso_code`, `name`) |
| `membership` | object | `eu`, `schengen` (boolean) |
| `currency` | object / null | `code`, `name`, `symbol` |
| `phone_prefix`, `timezone` | string / null | Vorwahl, IANA-Zeitzone |
| `languages` | string[] | Sprachcodes |
| `population`, `area_km2` | number / null | Einwohner, Fläche |
| `coordinates` | object / null | `lat`, `lng` (Mittelpunkt) |
| `capital` | object / null | `name` (Text), `lat`, `lng` der Hauptstadt |
| `flag` | object | `svg_url`, `emoji` |
| `description` | object | `short` (Text), `long` (Text, Absätze durch Leerzeilen), `known_for` (Liste je Sprache) |
| `travel_info` | object | `intro` (Text), `driving_side` (`right`/`left`), `emergency` (`general`, `police`, `ambulance`, `fire`), `religions[]` (`key`, `name`), `national_day` (`date`, `day_month`, `name`) |
| `power` | object | `voltage`, `frequency`, `plug_types[]` (`type`, `description`, `image` als SVG, `image_png`), `notes` (Text) |
| `tipping` | object | je `hotels`, `guides`, `restaurants`, `taxi`: `mode`, `from`, `to`, `unit`, `currency`, `description` oder `null` |
| `airlines[]` | object | Aktive Fluggesellschaften mit Sitz im Land, nach Name sortiert: `name`, `iata_code`, `icao_code`, `headquarters`, `website_url`, `booking_url` |
| `airports[]` | object | Aktive Flughäfen im Land, nach Name sortiert: `iata_code`, `icao_code`, `name`, `type`, `city` (Text) – Details über `/v1/airports/{code}` |
| `taxi_apps[]` | object | `name`, `description`, `logo_url`, `website_url`, `app_store_url`, `play_store_url` |
| `mobile_operators[]` | object | `name`, `description`, `logo_url`, `website_url`, `prepaid_url`, `offers_esim` |
| `holidays` | object | `year`, `items[]` (`id`, `date`, `weekday`, `name`, `comment`, `is_national`, `regions[]`) |
| `images` | object | `hero` (Bild oder null), `hero_url`, `gallery[]` (Bilder mit `url`, `thumb_url`, `width`, `height`, `focal`, `alt`, `caption`, `credit`, `license`) |
| `risk_profile` | object / null | `overall` (`level`, `label`), `categories` je Bereich mit `label` und `fields` (`value`, bei Stufen `label`, `note`) |
| `updated_at` | string | Letzte Änderung (ISO 8601) |

„Text“ steht für ein Objekt je Sprache, mit `?lang=` für einen String.

---

## Fehlercodes

| HTTP-Code | Bedeutung |
|-----------|-----------|
| `200` | Erfolgreich |
| `401` | Nicht authentifiziert (Token fehlt oder ungültig) |
| `403` | Zugriff verweigert |
| `404` | Ressource nicht gefunden |
| `422` | Validierungsfehler (ungültige Filter-Parameter) |
| `429` | Rate Limit überschritten |

**Beispiel Fehler-Response (401):**

```json
{
  "message": "Unauthenticated."
}
```

**Beispiel Fehler-Response (404):**

```json
{
  "success": false,
  "message": "Event not found"
}
```

**Beispiel Validierungsfehler (422):**

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "risk_level": ["The selected risk_level is invalid."],
    "per_page": ["The per page field must be between 1 and 100."]
  }
}
```

---

## Support

Bei Fragen zur API wenden Sie sich an Ihren Ansprechpartner bei Passolution.
