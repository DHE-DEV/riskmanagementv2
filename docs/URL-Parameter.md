# URL-Parameter für den Global Travel Monitor

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
