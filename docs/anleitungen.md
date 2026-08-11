# Anleitungen – Versionsübersicht

Diese Seite ist der **Abgleich für ausgedruckte oder weitergeleitete PDFs**. Jede Anleitung
trägt ihre Version auf der Titelseite und in der Fußzeile jeder Seite, im Format
`v1.0 · 11.08.2026`. Wer eine Fassung vorliegen hat, vergleicht diese Angabe mit der
Tabelle unten.

## Aktueller Stand

| Anleitung | Version | Datum | Datei |
|---|---|---|---|
| Der Ablauf, Station für Station erklärt | 1.0 | 11.08.2026 | [ablauf-erklaert-anleitung.pdf](ablauf-erklaert-anleitung.pdf) |
| Benachrichtigungen – Was die Zeit-Einstellungen bewirken | 1.0 | 11.08.2026 | [benachrichtigungs-intervalle-anleitung.pdf](benachrichtigungs-intervalle-anleitung.pdf) |
| Travel Alert – Freischaltung | 1.0 | 11.08.2026 | [travel-alert-freischaltung-anleitung.pdf](travel-alert-freischaltung-anleitung.pdf) |
| Passolution APIs im Überblick | 1.0 | 11.08.2026 | [api-ueberblick.pdf](api-ueberblick.pdf) |

Alle vier starten bei 1.0: Das ist der Zeitpunkt, ab dem überhaupt versioniert wird.
Ältere Ausdrucke tragen keine Version und sind damit in jedem Fall veraltet.

Nicht in dieser Übersicht: [flowchart.pdf](flowchart.pdf) und [er-diagram.pdf](er-diagram.pdf).
Das sind generierte Diagramme, keine Anleitungen – sie werden aus ihrer jeweiligen
`.md`-Datei neu gerendert.

## Wann welche Nummer

- **Zweite Stelle** (1.0 → 1.1) bei inhaltlichen Änderungen: neuer Abschnitt, korrigierte
  Angabe, ergänzter Hinweis. Der Normalfall.
- **Erste Stelle** (1.x → 2.0) bei Neuaufsetzung: Dokument neu strukturiert oder
  gestalterisch neu gebaut, sodass Seitenverweise aus der Vorfassung nicht mehr passen.
- Reine Tippfehlerkorrekturen ohne inhaltliche Folge brauchen keine neue Nummer.

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
grep -c 'v1.1 · 12.08.2026' docs/ablauf-erklaert-anleitung.html
```

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

Meldet Playwright einen fehlenden Browser, hilft `npx playwright install chromium` –
oder der Pfad zu einem vorhandenen Chromium per
`chromium.launch({ executablePath: '…' })`.

## Gestaltung

Neue Anleitungen orientieren sich an [travel-alert-freischaltung-anleitung.html](travel-alert-freischaltung-anleitung.html):
A4-Seiten als `section.page`, Passolution-Farben als CSS-Variablen, durchgehend geduzt.
Der einfachste Weg ist, den `<style>`-Block einer bestehenden Anleitung zu übernehmen –
dann stimmen Fußzeile und Versionsangabe automatisch.
