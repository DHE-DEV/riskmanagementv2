# Anleitungen – Versionsübersicht

Diese Seite ist der **Abgleich für ausgedruckte oder weitergeleitete PDFs**. Jede Anleitung
trägt ihre Version auf der Titelseite und in der Fußzeile jeder Seite, im Format
`v1.0 · 11.08.2026`. Wer eine Fassung vorliegen hat, vergleicht diese Angabe mit der
Tabelle unten.

## Aktueller Stand

| Anleitung | Version | Datum | Datei |
|---|---|---|---|
| Ereignisse erfassen und pflegen | 1.0 | 17.08.2026 | [ereignisse-erfassen-anleitung.pdf](ereignisse-erfassen-anleitung.pdf) |
| Der Ablauf, Station für Station erklärt | 1.1 | 17.08.2026 | [ablauf-erklaert-anleitung.pdf](ablauf-erklaert-anleitung.pdf) |
| Benachrichtigungen – Was die Zeit-Einstellungen bewirken | 1.0 | 11.08.2026 | [benachrichtigungs-intervalle-anleitung.pdf](benachrichtigungs-intervalle-anleitung.pdf) |
| Travel Alert – Freischaltung | 1.0 | 11.08.2026 | [travel-alert-freischaltung-anleitung.pdf](travel-alert-freischaltung-anleitung.pdf) |
| Passolution APIs im Überblick | 1.0 | 11.08.2026 | [api-ueberblick.pdf](api-ueberblick.pdf) |

## Diagramme

| Diagramm | Version | Datum | Datei |
|---|---|---|---|
| Flowchart Risk Management | 1.1 | 17.08.2026 | [flowchart.pdf](flowchart.pdf) · [.png](flowchart.png) · [Quelle](flowchart.md) |
| ER-Diagramm Risk Management v2 | 1.1 | 17.08.2026 | [er-diagram.pdf](er-diagram.pdf) · [.png](er-diagram.png) · [Quelle](er-diagram.md) |

Bei den Diagrammen steht die Version als **Titelzeile im Diagramm selbst** – gesetzt über
`title:` im Frontmatter des Mermaid-Blocks. Sie erscheint dadurch automatisch in PDF und
PNG, ohne dass es eine Fußzeile bräuchte.

Alles startet bei 1.0: Das ist der Zeitpunkt, ab dem überhaupt versioniert wird.
Ältere Ausdrucke tragen keine Version und sind damit in jedem Fall veraltet.

## Wann welche Nummer

- **Zweite Stelle** (1.0 → 1.1) bei inhaltlichen Änderungen: neuer Abschnitt, korrigierte
  Angabe, ergänzter Hinweis. Der Normalfall.
- **Erste Stelle** (1.x → 2.0) bei Neuaufsetzung: Dokument neu strukturiert oder
  gestalterisch neu gebaut, sodass Seitenverweise aus der Vorfassung nicht mehr passen.
- Reine Tippfehlerkorrekturen ohne inhaltliche Folge brauchen keine neue Nummer.

## Beim Ändern eines Diagramms

1. In der `.md`-Datei die Version an **zwei** Stellen hochziehen: im Hinweis über dem
   Diagramm und im `title:` des Mermaid-Frontmatters.
2. PDF und PNG neu rendern (siehe unten).
3. Zeile in der Diagramm-Tabelle oben aktualisieren.

## Beim Ändern einer Anleitung

1. In der HTML-Datei die Version an **drei** Stellen hochziehen:
   - `<span>Version 1.0</span>` und `<span>11.08.2026</span>` im Block `cover-meta`
     (die Anleitung „Der Ablauf" hat davon zwei: Titel- und Rückseite)
   - `<span class="ver">v1.0 · 11.08.2026</span>` in **jeder** Fußzeile – am schnellsten
     per Suchen-und-Ersetzen über die ganze Datei
2. PDF neu rendern (siehe unten).
3. Zeile in der Tabelle oben aktualisieren.

Kontrolle, dass keine Fußzeile vergessen wurde – die Zahl muss der Seitenzahl ohne
Titelseite entsprechen:

```bash
grep -c 'v1.1 · 17.08.2026' docs/ablauf-erklaert-anleitung.html
```

Wird eine Seite **eingefügt**, sind zusaetzlich die Seitenzahlen in den Fußzeilen dahinter,
die Abschnittsnummern in den `kicker`-Zeilen und die Zeilen im Inhaltsverzeichnis der
Titelseite zu verschieben – am besten absteigend ersetzen, sonst ueberschreiben sich die
Nummern gegenseitig.

## PDF rendern

Die PDFs entstehen aus den HTML-Dateien über Chromium (Playwright ist im Projekt
vorhanden). Skript im Projektstamm ablegen, ausführen, wieder löschen:

```js
// topdf.cjs
const { chromium } = require('playwright');
(async () => {
  const [src, out] = process.argv.slice(2);
  const b = await chromium.launch();
  const p = await b.newPage();
  await p.goto('file://' + src, { waitUntil: 'networkidle' });
  await p.emulateMedia({ media: 'print' });
  await p.pdf({ path: out, format: 'A4', printBackground: true,
                margin: { top: 0, right: 0, bottom: 0, left: 0 } });
  await b.close();
})();
```

```bash
node topdf.cjs "$PWD/docs/ablauf-erklaert-anleitung.html" "$PWD/docs/ablauf-erklaert-anleitung.pdf"
```

Meldet Playwright einen fehlenden Browser, liegt das meist an einer abweichenden
Build-Nummer: Das npm-Paket erwartet einen bestimmten Chromium-Build, im Cache liegt aber
ein anderer. Statt neu zu installieren genuegt der Pfad zum vorhandenen Browser:

```bash
ls ~/.cache/ms-playwright/          # vorhandenen Build ermitteln
CHROME_PATH=~/.cache/ms-playwright/chromium-1217/chrome-linux64/chrome \
  node topdf.cjs "$PWD/docs/…​.html" "$PWD/docs/…​.pdf"
```

Dafuer im Skript `chromium.launch({ executablePath: process.env.CHROME_PATH || undefined })`
verwenden. Dieselbe Variable braucht mermaid-cli unter dem Namen
`PUPPETEER_EXECUTABLE_PATH`.

## Diagramme rendern

Die Diagramme entstehen aus dem Mermaid-Block der jeweiligen `.md`-Datei. Block
herauslösen, dann PDF und PNG erzeugen:

```bash
cd docs
sed -n '/^```mermaid$/,/^```$/p' flowchart.md | sed '1d;$d' > /tmp/fc.mmd

npx @mermaid-js/mermaid-cli@11 -i /tmp/fc.mmd -o flowchart.pdf -b white --pdfFit
npx @mermaid-js/mermaid-cli@11 -i /tmp/fc.mmd -o flowchart.png -b white -s 3
```

**Beim ER-Diagramm die Breite mitgeben** – es ist sehr breit, und ohne `-w` staucht
mermaid-cli es auf die Standardbreite zusammen, wodurch die Beschriftungen unlesbar werden:

```bash
sed -n '/^```mermaid$/,/^```$/p' er-diagram.md | sed '1d;$d' > /tmp/er.mmd

npx @mermaid-js/mermaid-cli@11 -i /tmp/er.mmd -o er-diagram.pdf -b white --pdfFit
npx @mermaid-js/mermaid-cli@11 -i /tmp/er.mmd -o er-diagram.png -b white -w 6640 -s 3
```

Kontrolle: `er-diagram.png` muss rund 19.900 Pixel breit und etwa 3 MB groß sein.
Landet sie bei ~2.400 Pixeln, wurde `-w` vergessen.

## Gestaltung

Neue Anleitungen orientieren sich an [travel-alert-freischaltung-anleitung.html](travel-alert-freischaltung-anleitung.html):
A4-Seiten als `section.page`, Passolution-Farben als CSS-Variablen, durchgehend geduzt.
Der einfachste Weg ist, den `<style>`-Block einer bestehenden Anleitung zu übernehmen –
dann stimmen Fußzeile und Versionsangabe automatisch.
