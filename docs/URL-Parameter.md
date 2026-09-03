# URL-Parameter für den Global Travel Monitor

> **Achtung:** Diese Seite beschreibt die Parameter des Haupt-Dashboards (`/`).
> Die Plugin-/Embed-Routen (`/embed/events`, `/embed/map`, `/embed/dashboard`)
> haben einen eigenen, abweichenden Parametersatz – siehe
> [Embed-/Plugin-Parameter](#embed--plugin-parameter) am Ende dieser Datei.

## Filter-Parameter

| Parameter | Werte | Beschreibung |
|-----------|-------|--------------|
| `risk` | `info`, `green`, `orange`, `red` | Risikostufen (kommagetrennt) |
| `eventType` | IDs (siehe unten) | Event-Typen (kommagetrennt) |
| `continent` | IDs (siehe unten) | Weltregionen (kommagetrennt) |
| `timePeriod` | `all`, `7days`, `30days`, `none` | Zeitraum |
| `country` | ISO-Codes, z.B. `DE` oder `DE,ES,IT` | Land-Filter (kommagetrennt) |

## Karten-Parameter

| Parameter | Werte | Beschreibung |
|-----------|-------|--------------|
| `lat` | Zahl, z.B. `52.52` | Breitengrad für Kartenzentrierung |
| `lng` | Zahl, z.B. `13.405` | Längengrad für Kartenzentrierung |
| `zoom` | `2`-`19` | Zoom-Stufe (Standard: 12) |
| `marker` | `true` | Zeigt roten Marker an Position |
| `event` | Event-ID | Öffnet ein bestimmtes Event |

## Darstellungs-Parameter

| Parameter | Werte | Beschreibung |
|-----------|-------|--------------|
| `hide` | `hf` | Versteckt Header und Footer (für Embedding) |

---

## Verfügbare Risikostufen

| Wert | Beschreibung |
|------|--------------|
| `info` | Info (blau) |
| `green` | Niedrig (grün) |
| `orange` | Mittel (orange) |
| `red` | Hoch (rot) |

---

## Verfügbare Event-Typen (IDs)

| ID | Name |
|----|------|
| 9 | Reiseverkehr |
| 10 | Sicherheit |
| 11 | Umweltereignisse |
| 12 | Einreisebestimmungen |
| 13 | Allgemein |
| 14 | Gesundheit |

---

## Verfügbare Weltregionen (IDs)

| ID | Name |
|----|------|
| 1 | Europa |
| 2 | Asien |
| 3 | Afrika |
| 4 | Nordamerika |
| 5 | Südamerika |
| 6 | Ozeanien |
| 11 | Südasien |
| 12 | Naher Osten |
| 13 | Mittelamerika |
| 14 | Ost- & Südostasien |

---

## Beispiele

### Nur rote und orange Risikostufen anzeigen
```
/?risk=red,orange
```

### Nur Sicherheits- und Gesundheitsereignisse
```
/?eventType=10,14
```

### Nur Europa und Asien
```
/?continent=1,2
```

### Nur bestimmte Länder anzeigen
```
/?country=DE,ES,IT
```

### Ereignisse der letzten 7 Tage
```
/?timePeriod=7days
```

### Kombination mehrerer Filter
```
/?risk=red,orange&continent=1,2&timePeriod=7days
```

### Karte auf Berlin zentrieren
```
/?lat=52.52&lng=13.405&zoom=10
```

### Karte auf Position mit Marker
```
/?lat=52.52&lng=13.405&zoom=12&marker=true
```

### Für Embedding (ohne Header/Footer)
```
/?hide=hf
```

### Komplettes Beispiel
```
/?risk=red,orange&continent=1&eventType=10,11&timePeriod=30days&hide=hf
```

---

## Embed-/Plugin-Parameter

Gelten für `/embed/events`, `/embed/map` und `/embed/dashboard`.
Die Namen und Werte unterscheiden sich bewusst von denen des Haupt-Dashboards.

| Parameter | Werte | Beschreibung |
|-----------|-------|--------------|
| `key` | `pk_live_...` | Plugin-API-Key (**Pflicht**) |
| `timePeriod` | `all`, `future`, `today`, `week`, `month` | Zeitraum |
| `priorities` | `critical`, `high`, `medium`, `low`, `info` | Prioritäten (kommagetrennt) |
| `continents` | `EU`, `AS`, `AF`, `NA`, `SA`, `OC` | Kontinente (kommagetrennt) |
| `country` | ISO-Codes, z.B. `TR` oder `DE,ES,IT` | Länder (kommagetrennt), Alias: `countries` |
| `eventTypes` | Event-Type-IDs | Ereignistypen (kommagetrennt) |
| `search` | Suchbegriff | Volltextsuche, nur `/embed/events` |

### Darstellung

| Parameter | Werte | Beschreibung |
|-----------|-------|--------------|
| `hide_search` | `1` | Suchfeld ausblenden, nur `/embed/events` |
| `hide_filter` | `1` | Filter-Button und Filter-Dialog ausblenden |
| `hide_reset` | `1` | Links zum Zurücksetzen der Filter ausblenden |
| `hide_badge` | `1` | "Powered by"-Badge ausblenden |

Sind `hide_search` und `hide_filter` beide gesetzt, entfällt in `/embed/events`
die komplette Filterleiste. `hide_filter` blendet auch die Chips der aktiven
Filter aus – die per URL gesetzten Filter bleiben wirksam, sind für den
Besucher aber nicht mehr veränderbar.

### Beispiel

```html
<iframe
  src="https://global-travel-monitor.eu/embed/events?key=pk_live_xxx&country=TR"
  width="400" height="600" frameborder="0"></iframe>
```

Fest auf die Türkei eingestelltes Widget ohne Bedienelemente:

```html
<iframe
  src="https://global-travel-monitor.eu/embed/events?key=pk_live_xxx&country=TR&hide_search=1&hide_filter=1"
  width="400" height="600" frameborder="0"></iframe>
```

### Hinweise

- Unbekannte Parameter werden stillschweigend ignoriert.
- Ungültige Werte (z.B. `country=XYZ`) werden herausgefiltert; bleibt keine
  gültige Angabe übrig, greift der Filter nicht.
- Die Filter wirken clientseitig auf die geladenen Ereignisse
  (`/api/custom-events/dashboard-events?limit=100`). Bei sehr selektiven
  Filtern können daher ältere Treffer außerhalb dieser 100 Ereignisse fehlen.
- Über "Zurücksetzen" im Filterpanel werden auch die per URL gesetzten Filter
  geleert; ein Reload stellt sie wieder her.
