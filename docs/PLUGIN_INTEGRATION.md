# Global Travel Monitor - Plugin Integration

Dieses Dokument beschreibt alle verfügbaren Integrationsmöglichkeiten des Global Travel Monitor Plugins für Websites und Anwendungen.

---

## Inhaltsverzeichnis

1. [Übersicht](#übersicht)
2. [Integrationsmethoden](#integrationsmethoden)
   - [Web-Embed (iframe)](#1-web-embed-iframe)
   - [App-Integration (WebView)](#2-app-integration-webview)
3. [Verfügbare Ansichten](#verfügbare-ansichten)
4. [URL-Parameter](#url-parameter)
5. [Anleitung für Softwareanbieter](#anleitung-für-softwareanbieter)
6. [Technische Anforderungen](#technische-anforderungen)
7. [Sicherheit & Datenschutz](#sicherheit--datenschutz)
8. [FAQ](#faq)

---

## Übersicht

Das Global Travel Monitor Plugin ermöglicht die Integration von Echtzeit-Reiseinformationen und Sicherheitsereignissen in externe Websites und Anwendungen. Das Plugin bietet:

- **Aktuelle Reiseereignisse** weltweit (Naturkatastrophen, politische Ereignisse, Einreisebestimmungen, etc.)
- **Interaktive Weltkarte** mit Ereignis-Markierungen
- **Filteroptionen** nach Priorität, Region, Ereignistyp und Zeitraum
- **Echtzeit-Updates** - Daten werden automatisch aktualisiert
- **Responsive Design** - optimiert für alle Bildschirmgrößen

### Voraussetzungen

Um das Plugin nutzen zu können, benötigen Sie:

1. **API-Key**: Kostenlose Registrierung unter `https://global-travel-monitor.eu/plugin/register`
2. **Registrierte Domain** (für Web-Embed) oder **aktivierter App-Zugang** (für App-Integration)

---

## Integrationsmethoden

Es gibt zwei Hauptmethoden, um das Global Travel Monitor Plugin zu integrieren:

| Methode | Anwendungsfall | Domain-Validierung | Konfiguration erforderlich |
|---------|----------------|-------------------|---------------------------|
| **Web-Embed** | Websites (iframe) | Ja (Referer-Header) | Domain registrieren |
| **App-Integration** | Desktop/Mobile Apps (WebView) | Nein | App-Zugang aktivieren |

---

### 1. Web-Embed (iframe)

Die klassische Methode zur Integration auf Websites. Das Plugin wird als iframe eingebettet und validiert automatisch die aufrufende Domain.

#### Funktionsweise

1. Der Browser sendet beim Laden des iframes einen `Referer`-Header
2. Das Plugin prüft, ob die Domain im Kundenkonto registriert ist
3. Bei erfolgreicher Validierung wird das Plugin angezeigt

#### Einrichtung

1. **Im Plugin-Dashboard anmelden**: `https://global-travel-monitor.eu/plugin/dashboard`
2. **Domain hinzufügen**: Registrieren Sie die Domain(s), auf denen das Plugin eingebettet werden soll
3. **iframe-Code einbinden**: Kopieren Sie den Embed-Code auf Ihre Website

#### Beispiel-Code

```html
<!-- Ereignisliste -->
<iframe
  src="https://global-travel-monitor.eu/embed/events?key=IHR_API_KEY"
  width="400"
  height="600"
  frameborder="0">
</iframe>

<!-- Kartenansicht -->
<iframe
  src="https://global-travel-monitor.eu/embed/map?key=IHR_API_KEY"
  width="100%"
  height="600"
  frameborder="0">
</iframe>

<!-- Komplettansicht (empfohlen) -->
<iframe
  src="https://global-travel-monitor.eu/embed/dashboard?key=IHR_API_KEY"
  width="100%"
  height="800"
  frameborder="0">
</iframe>
```

#### Mehrere Domains

Sie können beliebig viele Domains in Ihrem Dashboard registrieren. Das Plugin funktioniert auf allen registrierten Domains mit demselben API-Key.

**Beispiele für gültige Domains:**
- `meine-firma.de`
- `app.meine-firma.de`
- `intranet.meine-firma.de`

---

### 2. App-Integration (WebView)

Für Desktop- und Mobile-Anwendungen, die das Plugin in einem WebView laden. Da native Apps keinen `Referer`-Header senden, ist eine separate Aktivierung erforderlich.

#### Funktionsweise

1. Die App lädt die Plugin-URL in einem WebView
2. Da kein `Referer`-Header vorhanden ist, prüft das Plugin den App-Zugang
3. Bei aktiviertem App-Zugang wird das Plugin angezeigt

#### Einrichtung

1. **Im Plugin-Dashboard anmelden**: `https://global-travel-monitor.eu/plugin/dashboard`
2. **App-Zugang aktivieren**: Klicken Sie auf "Aktivieren" im Bereich "App-Integration"
3. **URL in WebView laden**: Verwenden Sie die angezeigte URL in Ihrer Anwendung

#### Unterstützte Plattformen

| Plattform | WebView-Komponente | Beispiel |
|-----------|-------------------|----------|
| **Android** | `WebView` | `webView.loadUrl("https://...")` |
| **iOS** | `WKWebView` | `webView.load(URLRequest(url: url))` |
| **Electron** | `<webview>` oder `BrowserView` | `<webview src="https://...">` |
| **Qt** | `QWebEngineView` | `view->load(QUrl("https://..."))` |
| **Flutter** | `webview_flutter` | `WebView(initialUrl: "https://...")` |
| **React Native** | `react-native-webview` | `<WebView source={{uri: "https://..."}} />` |
| **.NET MAUI** | `WebView` | `<WebView Source="https://..." />` |
| **JavaFX** | `WebView` | `webView.getEngine().load("https://...")` |

#### Beispiel-Implementierungen

**Android (Kotlin):**
```kotlin
class MainActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        val webView: WebView = findViewById(R.id.webView)
        webView.settings.javaScriptEnabled = true
        webView.settings.domStorageEnabled = true

        webView.loadUrl("https://global-travel-monitor.eu/embed/dashboard?key=IHR_API_KEY")
    }
}
```

**iOS (Swift):**
```swift
import WebKit

class ViewController: UIViewController {
    var webView: WKWebView!

    override func viewDidLoad() {
        super.viewDidLoad()

        webView = WKWebView(frame: view.bounds)
        view.addSubview(webView)

        if let url = URL(string: "https://global-travel-monitor.eu/embed/dashboard?key=IHR_API_KEY") {
            webView.load(URLRequest(url: url))
        }
    }
}
```

**Electron (JavaScript):**
```javascript
const { BrowserWindow } = require('electron');

function createWindow() {
    const win = new BrowserWindow({
        width: 1200,
        height: 800,
        webPreferences: {
            nodeIntegration: false
        }
    });

    win.loadURL('https://global-travel-monitor.eu/embed/dashboard?key=IHR_API_KEY');
}
```

**Flutter (Dart):**
```dart
import 'package:webview_flutter/webview_flutter.dart';

class TravelMonitorWidget extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return WebView(
      initialUrl: 'https://global-travel-monitor.eu/embed/dashboard?key=IHR_API_KEY',
      javascriptMode: JavascriptMode.unrestricted,
    );
  }
}
```

---

## Verfügbare Ansichten

Das Plugin bietet drei verschiedene Ansichten, die je nach Anwendungsfall gewählt werden können:

### 1. Ereignisliste (`/embed/events`)

Kompakte Listenansicht aller aktuellen Ereignisse.

| Eigenschaft | Wert |
|-------------|------|
| **URL** | `/embed/events?key=API_KEY` |
| **Empfohlene Breite** | 300-500px |
| **Empfohlene Höhe** | 500-800px |
| **Anwendungsfall** | Sidebar-Widgets, schmale Spalten |

**Features:**
- Volltextsuche
- Filter nach Priorität, Region, Ereignistyp
- Zeitraumfilter
- Klickbare Ereignisse mit Detailansicht

### 2. Kartenansicht (`/embed/map`)

Interaktive Weltkarte mit Ereignis-Markierungen.

| Eigenschaft | Wert |
|-------------|------|
| **URL** | `/embed/map?key=API_KEY` |
| **Empfohlene Breite** | 100% (volle Breite) |
| **Empfohlene Höhe** | 400-700px |
| **Anwendungsfall** | Visuelle Darstellung, Dashboards |

**Features:**
- Interaktive Weltkarte (Leaflet-basiert)
- Farbcodierte Marker nach Priorität
- Zoom und Pan
- Popup mit Ereignisdetails bei Klick

### 3. Komplettansicht (`/embed/dashboard`)

Kombinierte Ansicht mit Sidebar und Karte.

| Eigenschaft | Wert |
|-------------|------|
| **URL** | `/embed/dashboard?key=API_KEY` |
| **Empfohlene Breite** | 100% (volle Breite) |
| **Empfohlene Höhe** | 600-900px |
| **Anwendungsfall** | Hauptintegration, dedizierte Seiten |

**Features:**
- Alle Features der Ereignisliste
- Alle Features der Kartenansicht
- Synchronisierte Interaktion (Klick in Liste markiert auf Karte)
- Optimales Nutzererlebnis

---

## URL-Parameter

Alle Ansichten unterstützen URL-Parameter zur Vorkonfiguration. Parameter werden als Query-String angehängt.

### Pflichtparameter

| Parameter | Beschreibung | Beispiel |
|-----------|--------------|----------|
| `key` | Ihr API-Key (Pflicht) | `?key=pk_live_xxx` |

### Optionale Filter-Parameter

| Parameter | Werte | Beschreibung | Beispiel |
|-----------|-------|--------------|----------|
| `timePeriod` | `all`, `future`, `today`, `week`, `month` | Zeitraum der Ereignisse | `&timePeriod=future` |
| `priorities` | `high`, `medium`, `low`, `info` | Prioritäten (kommagetrennt) | `&priorities=high,medium` |
| `continents` | `EU`, `AS`, `AF`, `NA`, `SA`, `OC`, `AN` | Kontinente (kommagetrennt) | `&continents=EU,AS` |
| `countries` | ISO-3166-1 Alpha-2 Codes | Länder (kommagetrennt) | `&countries=DE,AT,CH` |
| `eventTypes` | Event-Type-IDs | Ereignistypen (kommagetrennt) | `&eventTypes=9,10,11` |
| `search` | Beliebiger Text | Volltextsuche | `&search=Erdbeben` |

### Kontinente-Kürzel

| Kürzel | Kontinent |
|--------|-----------|
| `EU` | Europa |
| `AS` | Asien |
| `AF` | Afrika |
| `NA` | Nordamerika |
| `SA` | Südamerika |
| `OC` | Ozeanien |
| `AN` | Antarktis |

### Event-Type-IDs

| ID | Ereignistyp |
|----|-------------|
| `9` | Reiseverkehr |
| `10` | Sicherheit |
| `11` | Umweltereignisse |
| `12` | Einreisebestimmungen |
| `13` | Allgemein |
| `14` | Gesundheit |

### Beispiele für kombinierte Parameter

```
# Nur kritische Ereignisse in Europa (Zukunft)
/embed/dashboard?key=API_KEY&timePeriod=future&priorities=high&continents=EU

# Sicherheitsereignisse weltweit
/embed/events?key=API_KEY&eventTypes=10

# DACH-Region mit hoher und mittlerer Priorität
/embed/dashboard?key=API_KEY&countries=DE,AT,CH&priorities=high,medium

# Naturkatastrophen in Asien
/embed/map?key=API_KEY&continents=AS&eventTypes=11
```

---

## Anleitung für Softwareanbieter

Diese Sektion richtet sich speziell an Softwareanbieter, die das Global Travel Monitor Plugin in ihre Produkte integrieren möchten.

### Typische Anwendungsfälle

1. **Reisebüro-Software**: Zeigen Sie Ihren Mitarbeitern und Kunden aktuelle Reisewarnungen
2. **Travel Management Systeme**: Integrieren Sie Risikoinformationen in Buchungsprozesse
3. **HR-Software**: Informieren Sie über Risiken für Geschäftsreisende
4. **Intranet-Portale**: Stellen Sie Reiseinformationen für Mitarbeiter bereit
5. **Mobile Apps**: Bieten Sie Reisenden aktuelle Sicherheitsinformationen

### Schritt-für-Schritt-Anleitung

#### Schritt 1: Registrierung

1. Besuchen Sie `https://global-travel-monitor.eu/plugin/register`
2. Geben Sie Ihre Firmendaten ein
3. Bestätigen Sie Ihre E-Mail-Adresse
4. Vervollständigen Sie das Onboarding

#### Schritt 2: Dashboard aufrufen

Nach der Registrierung haben Sie Zugang zum Plugin-Dashboard unter:
`https://global-travel-monitor.eu/plugin/dashboard`

Hier finden Sie:
- Ihren **API-Key** (beginnt mit `pk_live_`)
- **Domain-Verwaltung** für Web-Embeds
- **App-Zugang** Toggle für WebView-Integration
- **Nutzungsstatistiken**

#### Schritt 3: Integrationsart wählen

**Für Web-Anwendungen (Browser-basiert):**
1. Registrieren Sie alle Domains, auf denen das Plugin laufen soll
2. Verwenden Sie den iframe-Code auf Ihren Seiten

**Für Desktop/Mobile-Anwendungen (Native Apps):**
1. Aktivieren Sie den "App-Zugang" im Dashboard
2. Laden Sie die Plugin-URL in einem WebView

#### Schritt 4: Integration implementieren

##### Web-Integration (HTML/JavaScript)

```html
<!DOCTYPE html>
<html>
<head>
    <title>Reiseinformationen</title>
    <style>
        .gtm-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        .gtm-container iframe {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="gtm-container">
        <h1>Aktuelle Reiseinformationen</h1>
        <iframe
            id="gtm-plugin"
            src="https://global-travel-monitor.eu/embed/dashboard?key=IHR_API_KEY"
            width="100%"
            height="800"
            loading="lazy">
        </iframe>
    </div>
</body>
</html>
```

##### Dynamische Filter per JavaScript

```javascript
// Basis-URL
const baseUrl = 'https://global-travel-monitor.eu/embed/dashboard';
const apiKey = 'IHR_API_KEY';

// Filter-Funktion
function updatePluginFilter(options) {
    const params = new URLSearchParams();
    params.set('key', apiKey);

    if (options.timePeriod) params.set('timePeriod', options.timePeriod);
    if (options.priorities) params.set('priorities', options.priorities.join(','));
    if (options.continents) params.set('continents', options.continents.join(','));
    if (options.countries) params.set('countries', options.countries.join(','));
    if (options.eventTypes) params.set('eventTypes', options.eventTypes.join(','));
    if (options.search) params.set('search', options.search);

    const iframe = document.getElementById('gtm-plugin');
    iframe.src = `${baseUrl}?${params.toString()}`;
}

// Beispiel: Nur Europa anzeigen
updatePluginFilter({
    continents: ['EU'],
    priorities: ['high', 'medium']
});

// Beispiel: Suche nach "Streik"
updatePluginFilter({
    search: 'Streik'
});
```

##### Native App-Integration (Konzept)

```
┌─────────────────────────────────────────────────────┐
│                  Ihre Anwendung                      │
├─────────────────────────────────────────────────────┤
│                                                      │
│    ┌─────────────────────────────────────────┐      │
│    │            WebView-Container             │      │
│    │                                          │      │
│    │   URL: global-travel-monitor.eu/        │      │
│    │         embed/dashboard?key=xxx         │      │
│    │                                          │      │
│    │   ┌──────────────────────────────────┐  │      │
│    │   │     Global Travel Monitor        │  │      │
│    │   │         Plugin-Inhalt            │  │      │
│    │   │                                  │  │      │
│    │   │   [Ereignisliste] [Weltkarte]    │  │      │
│    │   │                                  │  │      │
│    │   └──────────────────────────────────┘  │      │
│    │                                          │      │
│    └─────────────────────────────────────────┘      │
│                                                      │
│    [Weitere App-Funktionen...]                       │
│                                                      │
└─────────────────────────────────────────────────────┘
```

### White-Label-Optionen

Das Plugin verwendet ein neutrales Design, das sich in verschiedene Anwendungen einfügt. Derzeit sind keine White-Label-Anpassungen verfügbar. Bei Interesse an individuellen Anpassungen kontaktieren Sie uns.

### Mehrere Kunden/Mandanten

Als Softwareanbieter haben Sie mehrere Möglichkeiten, das Plugin für Ihre Kunden bereitzustellen:

**Option A: Ein API-Key für alle**
- Sie nutzen einen gemeinsamen API-Key
- Einfache Implementierung
- Statistiken werden aggregiert

**Option B: Separate API-Keys pro Kunde**
- Jeder Kunde registriert sich selbst
- Individuelle Statistiken pro Kunde
- Kunden verwalten ihre Domains selbst

**Option C: Reseller-Modell**
- Kontaktieren Sie uns für Reseller-Vereinbarungen
- Verwaltung mehrerer API-Keys unter einem Account
- Zentrale Abrechnung und Statistiken

### Best Practices

1. **Caching**: Das Plugin cached Daten serverseitig. Vermeiden Sie clientseitiges Caching der iframe-Inhalte.

2. **Responsive Design**: Verwenden Sie `width="100%"` für responsive Layouts.

3. **Lazy Loading**: Nutzen Sie `loading="lazy"` für bessere Performance:
   ```html
   <iframe src="..." loading="lazy"></iframe>
   ```

4. **Fehlerbehandlung**: Implementieren Sie einen Fallback für den Fall, dass das Plugin nicht geladen werden kann:
   ```html
   <iframe src="..." onerror="showFallback()"></iframe>
   ```

5. **HTTPS**: Stellen Sie sicher, dass Ihre Anwendung über HTTPS läuft (erforderlich für Mixed-Content-Policy).

---

## Technische Anforderungen

### Browser-Unterstützung

| Browser | Mindestversion |
|---------|---------------|
| Chrome | 80+ |
| Firefox | 75+ |
| Safari | 13+ |
| Edge | 80+ |
| Opera | 67+ |

### WebView-Anforderungen

| Plattform | Anforderung |
|-----------|-------------|
| Android | WebView mit Chromium (API Level 21+) |
| iOS | WKWebView (iOS 11+) |
| Electron | Chromium 80+ |

### Netzwerk-Anforderungen

- HTTPS-Verbindung zu `global-travel-monitor.eu`
- WebSocket-Unterstützung (für Echtzeit-Updates)
- JavaScript aktiviert
- Cookies erlaubt (für Session-Management)

### Firewall/Proxy-Konfiguration

Falls Ihre Anwendung hinter einer Firewall läuft, stellen Sie sicher, dass folgende Verbindungen erlaubt sind:

```
# HTTPS (Port 443)
global-travel-monitor.eu

# WebSocket (wss://)
global-travel-monitor.eu/livewire
```

---

## Sicherheit & Datenschutz

### API-Key-Sicherheit

- Der API-Key ist **öffentlich sichtbar** (im iframe-src oder WebView-URL)
- Die Sicherheit basiert auf der **Domain-Validierung** (Web-Embed) bzw. **App-Zugang-Einstellung**
- Der API-Key kann jederzeit im Dashboard regeneriert werden

### Datenschutz

- Das Plugin erfasst keine personenbezogenen Daten der Endnutzer
- Es werden lediglich aggregierte Nutzungsstatistiken erhoben:
  - Anzahl der Aufrufe
  - Aufrufende Domain/App
  - Aufgerufene Ansicht
- Alle Daten werden DSGVO-konform verarbeitet

### Content Security Policy (CSP)

Falls Ihre Website eine strikte CSP verwendet, fügen Sie folgende Direktiven hinzu:

```
frame-src https://global-travel-monitor.eu;
```

---

## FAQ

### Allgemeine Fragen

**Q: Ist das Plugin kostenlos?**
A: Ja, das Plugin ist kostenlos nutzbar.

**Q: Gibt es Nutzungslimits?**
A: Derzeit gibt es keine harten Limits. Bei sehr hoher Nutzung kontaktieren wir Sie.

**Q: Kann ich das Plugin auf mehreren Websites verwenden?**
A: Ja, registrieren Sie einfach alle Domains in Ihrem Dashboard.

### Technische Fragen

**Q: Warum wird mein Plugin nicht geladen?**
A: Häufige Ursachen:
1. Domain nicht registriert (Web-Embed)
2. App-Zugang nicht aktiviert (App-Integration)
3. Falscher oder deaktivierter API-Key
4. HTTPS-Problem (Mixed Content)

**Q: Kann ich das Design anpassen?**
A: Derzeit sind keine individuellen Anpassungen möglich. Das Plugin verwendet ein neutrales Design.

**Q: Unterstützt das Plugin mehrere Sprachen?**
A: Derzeit ist das Plugin nur auf Deutsch verfügbar.

**Q: Wie oft werden die Daten aktualisiert?**
A: Die Daten werden in Echtzeit aktualisiert. Neue Ereignisse erscheinen automatisch.

### Fehlerbehebung

**Q: Fehlermeldung "Domain nicht autorisiert"**
A: Die Domain, von der das Plugin aufgerufen wird, ist nicht in Ihrem Dashboard registriert. Fügen Sie die Domain hinzu.

**Q: Fehlermeldung "App-Zugang nicht aktiviert"**
A: Sie versuchen, das Plugin ohne Referer-Header aufzurufen (z.B. in einer App), aber der App-Zugang ist nicht aktiviert. Aktivieren Sie ihn im Dashboard.

**Q: Fehlermeldung "Ungültiger API-Key"**
A: Prüfen Sie, ob der API-Key korrekt ist und ob er mit `pk_live_` beginnt.

---

## Support & Kontakt

Bei Fragen oder Problemen:

- **Plugin-Dashboard**: https://global-travel-monitor.eu/plugin/dashboard
- **Dokumentation**: https://global-travel-monitor.eu/doc-plugin
- **E-Mail**: support@passolution.de

---

*Letzte Aktualisierung: Januar 2026*
