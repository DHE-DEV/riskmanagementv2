# Feed API – Kundenanleitung

## Übersicht

Die Feed API stellt aktuelle Sicherheits- und Reiserisiko-Events sowie Länderinformationen als RSS/Atom-Feeds bereit. Die Feeds können in Feed-Reader, CMS-Systeme oder eigene Anwendungen eingebunden werden.

**Keine Authentifizierung erforderlich** – alle Feed-Endpunkte sind öffentlich zugänglich.

---

## Base-URL

```
https://global-travel-monitor.eu/feed
```

---

## Caching

Feed-Antworten werden serverseitig gecacht. Die Cache-Dauer beträgt standardmäßig **1 Stunde** (3600 Sekunden). Bei neuen oder geänderten Events wird der Cache automatisch invalidiert.

---

## Metadaten

### Verfügbare Priorities und Event-Typen

```
GET /feed/events/meta.json
```

Gibt die gültigen Werte für Priority-Filter und Event-Typ-Filter als JSON zurück.

**Beispiel:**

```bash
curl https://global-travel-monitor.eu/feed/events/meta.json
```

**Response:**

```json
{
  "priorities": [
    { "code": "high", "name_de": "Hoch", "name_en": "High" },
    { "code": "medium", "name_de": "Mittel", "name_en": "Medium" },
    { "code": "low", "name_de": "Niedrig", "name_en": "Low" },
    { "code": "info", "name_de": "Information", "name_en": "Info" }
  ],
  "event_types": [
    {
      "code": "earthquake",
      "name": "Erdbeben",
      "description": "...",
      "icon": "fa-house-crack",
      "color": "#FF0000"
    }
  ]
}
```

---

## Event-Feeds

Alle Event-Feeds liefern nur **aktive, nicht-archivierte Events**, deren Startdatum in der Vergangenheit liegt. Maximal 100 Events pro Feed, sortiert nach Startdatum (neueste zuerst).

### Alle Events

| Format | URL |
|--------|-----|
| RSS 2.0 | `/feed/events/all.xml` |
| Atom 1.0 | `/feed/events/all.atom` |

**Beispiel:**

```bash
curl https://global-travel-monitor.eu/feed/events/all.xml
```

---

### Events nach Priorität

```
GET /feed/events/priority/{priority}.xml
```

| Parameter | Gültige Werte |
|-----------|---------------|
| `priority` | `high`, `medium`, `low`, `info` |

**Beispiel:**

```bash
curl https://global-travel-monitor.eu/feed/events/priority/high.xml
```

---

### Events nach Land

```
GET /feed/events/countries/{code}.xml
```

| Parameter | Beschreibung |
|-----------|--------------|
| `code` | ISO 3166-1 alpha-2 (z.B. `de`) oder alpha-3 (z.B. `deu`), case-insensitive |

**Beispiel:**

```bash
curl https://global-travel-monitor.eu/feed/events/countries/de.xml
```

---

### Events nach Event-Typ

```
GET /feed/events/types/{type}.xml
```

| Parameter | Beschreibung |
|-----------|--------------|
| `type` | Event-Typ-Code (aus `meta.json`), case-insensitive |

**Beispiel:**

```bash
curl https://global-travel-monitor.eu/feed/events/types/earthquake.xml
```

---

### Events nach Region

```
GET /feed/events/regions/{region}.xml
```

| Parameter | Beschreibung |
|-----------|--------------|
| `region` | Numerische Region-ID |

**Beispiel:**

```bash
curl https://global-travel-monitor.eu/feed/events/regions/3.xml
```

---

## RSS-Struktur (Events)

Jedes Event-Item im Feed enthält folgende Elemente:

### Standard-RSS-Elemente

| Element | Beschreibung |
|---------|--------------|
| `<title>` | Titel des Events |
| `<link>` | URL zur Event-Detailseite |
| `<guid>` | Permanenter Link (identisch mit `<link>`) |
| `<description>` | Kurzübersicht: Typ, Zeitraum, Priorität, Länder |
| `<content:encoded>` | Vollständige Beschreibung |
| `<pubDate>` | Erstellungsdatum (RFC 2822) |
| `<category>` | Priorität und Event-Typen |
| `<dc:creator>` | Ersteller (falls vorhanden) |
| `<source>` | Quellenangabe mit URL |
| `<enclosure>` | Länderbild (JPEG, falls vorhanden) |

### Benutzerdefinierte Elemente: `article:data`

```xml
<article:data>
  <article:start_date>Mon, 11 Feb 2026 08:00:00 +0000</article:start_date>
  <article:end_date>Tue, 18 Feb 2026 08:00:00 +0000</article:end_date>
  <article:priority>high</article:priority>
  <article:event_type code="earthquake">Erdbeben</article:event_type>
</article:data>
```

| Element | Beschreibung |
|---------|--------------|
| `article:start_date` | Startdatum des Events |
| `article:end_date` | Enddatum des Events |
| `article:priority` | Prioritätsstufe: `high`, `medium`, `low`, `info` |
| `article:event_type` | Event-Typ mit `code`-Attribut und Name als Inhalt (mehrere möglich) |

### Benutzerdefinierte Elemente: `country:data`

Pro betroffenem Land wird ein `<country:data>`-Block ausgegeben:

```xml
<country:data>
  <country:name_de>Thailand</country:name_de>
  <country:name_en>Thailand</country:name_en>
  <country:iso_code>TH</country:iso_code>
  <country:iso3_code>THA</country:iso3_code>
  <country:is_eu_member>false</country:is_eu_member>
  <country:is_schengen_member>false</country:is_schengen_member>
  <country:continent>Asien</country:continent>
  <country:currency_code>THB</country:currency_code>
  <country:phone_prefix>+66</country:phone_prefix>
  <country:capital>
    <country:capital_name>Bangkok</country:capital_name>
    <geo:lat>13.7563</geo:lat>
    <geo:long>100.5018</geo:long>
  </country:capital>
</country:data>
```

| Element | Beschreibung |
|---------|--------------|
| `country:name_de` | Ländername (deutsch) |
| `country:name_en` | Ländername (englisch) |
| `country:iso_code` | ISO 3166-1 alpha-2 Code |
| `country:iso3_code` | ISO 3166-1 alpha-3 Code |
| `country:is_eu_member` | `true` / `false` |
| `country:is_schengen_member` | `true` / `false` |
| `country:continent` | Kontinent (deutsch) |
| `country:currency_code` | ISO 4217 Währungscode |
| `country:phone_prefix` | Internationale Telefonvorwahl |
| `country:capital_name` | Name der Hauptstadt |
| `geo:lat` | Breitengrad der Hauptstadt |
| `geo:long` | Längengrad der Hauptstadt |

### XML-Namespaces

```xml
<rss version="2.0"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:country="http://global-travel-monitor.eu/ns/country"
  xmlns:article="http://global-travel-monitor.eu/ns/article"
  xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#">
```

---

## Länder-Feeds

Die Länder-Feeds liefern Verzeichnisse mit Länderdetails (Name, ISO-Codes, EU/Schengen-Status, Kontinent, Währung, Hauptstadt mit Koordinaten).

### Alle Länder

```
GET /feed/countries/names/all.xml
```

### Länder nach Kontinent

```
GET /feed/countries/continent/{code}.xml
```

| Parameter | Gültige Werte |
|-----------|---------------|
| `code` | `EU` (Europa), `AS` (Asien), `AF` (Afrika), `NA` (Nordamerika), `SA` (Südamerika), `OC` (Ozeanien), `AN` (Antarktis) |

**Beispiel:**

```bash
curl https://global-travel-monitor.eu/feed/countries/continent/EU.xml
```

### EU-Mitgliedsstaaten

```
GET /feed/countries/eu.xml
```

### Schengen-Staaten

```
GET /feed/countries/schengen.xml
```

---

## Endpunkt-Übersicht

| Endpunkt | Format | Beschreibung |
|----------|--------|--------------|
| `/feed/events/meta.json` | JSON | Verfügbare Priorities und Event-Typen |
| `/feed/events/all.xml` | RSS 2.0 | Alle aktiven Events |
| `/feed/events/all.atom` | Atom 1.0 | Alle aktiven Events |
| `/feed/events/priority/{priority}.xml` | RSS 2.0 | Events nach Priorität |
| `/feed/events/countries/{code}.xml` | RSS 2.0 | Events nach Land |
| `/feed/events/types/{type}.xml` | RSS 2.0 | Events nach Event-Typ |
| `/feed/events/regions/{region}.xml` | RSS 2.0 | Events nach Region |
| `/feed/countries/names/all.xml` | RSS 2.0 | Alle Länder |
| `/feed/countries/continent/{code}.xml` | RSS 2.0 | Länder nach Kontinent |
| `/feed/countries/eu.xml` | RSS 2.0 | EU-Mitgliedsstaaten |
| `/feed/countries/schengen.xml` | RSS 2.0 | Schengen-Staaten |

---

## Support

Bei Fragen zur Feed API wenden Sie sich an Ihren Ansprechpartner bei Passolution.
