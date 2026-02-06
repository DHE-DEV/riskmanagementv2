# 🎬 CAMTASIA PRODUKTIONS-GUIDE: Risiko-Übersicht

## 📁 ORDNERSTRUKTUR

```
video-produktion/
├── voiceover-texte/        # 11 Textdateien für ElevenLabs
├── audio-exports/          # Hier speichern Sie die MP3s von ElevenLabs
├── screen-recordings/      # Ihre Camtasia Screen-Aufnahmen
└── final-export/           # Fertiges Video
```

---

## 🎙️ SCHRITT 1: VOICEOVER MIT ELEVENLABS ERSTELLEN

### Vorbereitung
1. Gehen Sie zu https://elevenlabs.io/
2. Wählen Sie eine professionelle deutsche Stimme:
   - **Empfohlen:** "Daniel" oder "Callum" (wenn deutsch verfügbar)
   - **Alternative:** Erstellen Sie einen Clone Ihrer eigenen Stimme

### Settings für beste Qualität
- **Stability:** 75%
- **Clarity + Similarity Enhancement:** 75%
- **Style Exaggeration:** 0% (für Business-Videos)

### Audio generieren (für jede Szene)
1. Öffnen Sie `voiceover-texte/01_INTRO.txt`
2. Kopieren Sie den Text in ElevenLabs
3. Klicken Sie auf "Generate"
4. Download als **MP3, 44.1kHz**
5. Speichern Sie als: `audio-exports/01_INTRO.mp3`
6. Wiederholen Sie für alle 11 Szenen

### ⚡ TIPP: Batch-Processing
- Generieren Sie alle Audios hintereinander
- Benennen Sie sie sofort korrekt (01-11)
- Hören Sie jede Audio einmal ab zur Qualitätskontrolle

---

## 🎥 SCHRITT 2: SCREEN-RECORDINGS AUFNEHMEN

### Camtasia Recorder Setup
1. **Aufnahme-Bereich:** Full Screen oder 1920x1080 Fenster
2. **Framerate:** 30 FPS
3. **Cursor:** Einblenden (mit Highlight-Effekt)
4. **Audio:** Ausschalten (Sie nutzen ElevenLabs)

### Aufnahme-Reihenfolge

#### Recording 1: INTRO + DASHBOARD (Szenen 1-2)
**Was aufnehmen:**
- Öffnen Sie `/risk-overview`
- Langsam einzoomen auf die Seite
- Statistik-Box oben zeigen
- 5 Sekunden halten

**Dauer:** ~20 Sekunden

---

#### Recording 2: FILTER (Szene 3)
**Was aufnehmen:**
- Filter-Sidebar öffnen (langsam)
- Zeitraum-Dropdown öffnen, Optionen zeigen
- Prioritäts-Filter anklicken
- "Nur mit Reisenden" Toggle aktivieren
- Sortierung ändern
- Jeden Schritt 2-3 Sekunden halten

**Dauer:** ~30 Sekunden

---

#### Recording 3: KARTEN- VS. KACHELANSICHT (Szene 4)
**Was aufnehmen:**
- Start bei Kartenansicht
- Verschiedene Marker zeigen (rot, orange, gelb)
- Auf ein Land klicken (kurz)
- Wechsel zu Kachelansicht (Button klicken)
- Durch Kacheln scrollen

**Dauer:** ~25 Sekunden

---

#### Recording 4: LAND AUSWÄHLEN + SIDEBAR (Szene 5)
**Was aufnehmen:**
- Ein Land anklicken (z.B. in Kartenansicht)
- Sidebar gleitet von rechts ein
- Zeigen Sie beide Bereiche: Ereignisse + Reisen
- Kurz in beiden Bereichen scrollen

**Dauer:** ~20 Sekunden

---

#### Recording 5: KACHEL- VS. LISTENANSICHT (Szene 6)
**Was aufnehmen:**
- In der Sidebar: Kachelansicht zeigen
- Toggle zu Listenansicht klicken
- Listenansicht zeigen
- Zurück zu Kacheln

**Dauer:** ~20 Sekunden

---

#### Recording 6: EREIGNIS ÖFFNEN (Szene 7)
**Was aufnehmen:**
- Auf ein Ereignis in der Kachel klicken
- Modal öffnet sich
- Alle Details sichtbar machen (ggf. scrollen)
- Modal schließen

**Dauer:** ~25 Sekunden

---

#### Recording 7: REISENDEN DETAILS (Szene 8)
**Was aufnehmen:**
- Scrollen Sie zu "Betroffene Reisen"
- Zeigen Sie verschiedene Reisen-Karten
- Klicken Sie eine Reise an
- Details-Modal zeigen
- Fortschrittsbalken hervorheben
- API vs GTM Badge zeigen

**Dauer:** ~25 Sekunden

---

#### Recording 8: MAXIMIEREN-FUNKTION (Szene 9)
**Was aufnehmen:**
- Expand-Icon bei "Ereignisse" klicken
- Bereich expandiert
- Icon wechselt zu "Compress"
- Compress-Icon klicken
- Normale Ansicht wiederhergestellt
- Dasselbe für "Betroffene Reisen"

**Dauer:** ~20 Sekunden

---

#### Recording 9: AKTUALISIERUNG (Szene 10)
**Was aufnehmen:**
- Refresh-Button in Statistik-Box klicken
- Lade-Animation zeigen
- Daten aktualisiert

**Dauer:** ~15 Sekunden

---

#### Recording 10: ABSCHLUSS (Szene 11)
**Was aufnehmen:**
- Gesamtansicht der Seite
- Langsam rauszoomen
- Oder: eleganter Fade-Out

**Dauer:** ~15 Sekunden

---

## 🎬 SCHRITT 3: VIDEO IN CAMTASIA ZUSAMMENBAUEN

### Projekt Setup
1. **Neues Projekt:** 1920x1080, 30 FPS
2. **Canvas:** Schwarz
3. **Dauer:** ~3:50 Minuten

### Timeline-Struktur

```
Track 1: Screen Recordings (Video)
Track 2: Voiceover (Audio)
Track 3: Hintergrundmusik (Audio - leise!)
Track 4: Callouts & Annotations
Track 5: Transitions
```

### Importieren
1. Importieren Sie alle Screen-Recordings
2. Importieren Sie alle Audio-Dateien (01-11)
3. Importieren Sie Hintergrundmusik (optional)

### Timeline aufbauen

#### Szene 1: INTRO (0:00 - 0:15)
- **Video:** Recording 1 (Intro Teil)
- **Audio:** `01_INTRO.mp3`
- **Effekt:** Fade-In (1 Sekunde)
- **Text:** Titel einblenden: "Risiko-Übersicht"

#### Szene 2: DASHBOARD (0:15 - 0:35)
- **Video:** Recording 1 (Dashboard Teil)
- **Audio:** `02_DASHBOARD.mp3`
- **Callout:** Arrow auf Statistik-Box zeigen
- **Highlight:** Box kurz highlighten

#### Szene 3: FILTER (0:35 - 1:05)
- **Video:** Recording 2
- **Audio:** `03_FILTER.mp3`
- **Callouts:**
  - Bei "Zeitraum" → Arrow
  - Bei "Priorität" → Arrow
  - Bei "Nur mit Reisenden" → Highlight

#### Szene 4: ANSICHTEN (1:05 - 1:30)
- **Video:** Recording 3
- **Audio:** `04_ANSICHTEN.mp3`
- **Text-Einblendung:**
  - "Kartenansicht" (wenn Karte sichtbar)
  - "Kachelansicht" (beim Wechsel)

#### Szene 5: LAND AUSWÄHLEN (1:30 - 1:50)
- **Video:** Recording 4
- **Audio:** `05_LAND_AUSWAEHLEN.mp3`
- **Effekt:** Zoom auf Sidebar beim Einblenden

#### Szene 6: KACHEL/LISTE (1:50 - 2:10)
- **Video:** Recording 5
- **Audio:** `06_KACHEL_LISTE.mp3`
- **Callout:** Arrow auf Toggle-Button

#### Szene 7: EREIGNISSE (2:10 - 2:35)
- **Video:** Recording 6
- **Audio:** `07_EREIGNISSE.mp3`
- **Highlight:** Prioritäts-Badge hervorheben

#### Szene 8: REISEN (2:35 - 3:00)
- **Video:** Recording 7
- **Audio:** `08_REISEN.mp3`
- **Callouts:**
  - Arrow auf Fortschrittsbalken
  - Highlight auf API/GTM Badge

#### Szene 9: MAXIMIEREN (3:00 - 3:20)
- **Video:** Recording 8
- **Audio:** `09_MAXIMIEREN.mp3`
- **Text:** "Maximieren" / "Wiederherstellen" einblenden

#### Szene 10: AKTUALISIERUNG (3:20 - 3:35)
- **Video:** Recording 9
- **Audio:** `10_AKTUALISIERUNG.mp3`
- **Callout:** Circle um Refresh-Button

#### Szene 11: ABSCHLUSS (3:35 - 3:50)
- **Video:** Recording 10
- **Audio:** `11_ABSCHLUSS.mp3`
- **Text:** Logo/Kontakt einblenden
- **Effekt:** Fade-Out (2 Sekunden)

---

## 🎨 CAMTASIA EFFEKTE & STYLING

### Cursor-Effekte
- **Cursor Highlighting:** Ein (gelber Kreis bei Klicks)
- **Cursor Smoothing:** 50%
- **Cursor Size:** 1.5x

### Callouts & Annotations
**Nutzen Sie:**
- **Arrow:** Für Hinweise auf Buttons/Bereiche
- **Blur:** Um sensible Daten zu verbergen
- **Spotlight:** Um Fokus auf bestimmte Bereiche zu lenken
- **Text:** Für Feature-Namen

**Styling:**
- Farbe: Blau (#0066CC) oder Grün (#10B981) passend zum System
- Schriftart: Sans-Serif (z.B. Arial, Helvetica)
- Animation: Fade-In 0.3s

### Transitions
**Zwischen Szenen:**
- **Fade:** 0.5 Sekunden (standard)
- **Keine harten Cuts** zwischen verwandten Szenen

### Zoom & Pan
**Nutzen bei:**
- Kleine UI-Elemente (Buttons, Icons)
- Wichtigen Details
- **Settings:** Smooth, 1 Sekunde Dauer

---

## 🎵 HINTERGRUNDMUSIK

### Empfehlungen
- **Lautstärke:** -30dB (sehr leise!)
- **Genre:** Corporate, Ambient, Inspiring
- **Tempo:** Ruhig, nicht zu schnell
- **Lizenz:** Royalty-free (z.B. Epidemic Sound, AudioJungle)

### In Camtasia einfügen
1. Musik auf Track 3 legen
2. Länge auf gesamtes Video anpassen
3. Fade-In am Anfang (2s), Fade-Out am Ende (3s)
4. **Audio-Ducking:** Bei Voiceover auf -15dB reduzieren

---

## ✅ EXPORT-EINSTELLUNGEN

### Camtasia Export
1. **Share → Local File**
2. **Preset:** MP4 - Smart Player (Web)
3. **Custom Settings:**
   - Video: H.264, 1920x1080, 30 FPS, 8000 kbps
   - Audio: AAC, 256 kbps, 44.1 kHz, Stereo
4. **Options:**
   - ✅ Produce video up to smart player size
   - ✅ Include captions (falls vorhanden)

### Dateiname
`Risiko-Uebersicht_Produktvideo_v1.mp4`

---

## 🔍 QUALITÄTSKONTROLLE

### Vor dem finalen Export checken:
- [ ] Alle Übergänge smooth?
- [ ] Audio überall gut verständlich?
- [ ] Keine versehentlich sichtbaren Tabs/Notifications?
- [ ] Cursor-Bewegungen nicht zu hektisch?
- [ ] Alle Callouts zur richtigen Zeit?
- [ ] Musik nicht zu laut?
- [ ] Video-Dauer stimmt (~3:50)?
- [ ] Kein Tearing oder Ruckeln?

### Test-Export
1. Exportieren Sie erst 30 Sekunden zum Testen
2. Prüfen Sie Qualität auf verschiedenen Geräten
3. Dann erst komplettes Video exportieren

---

## 💡 PROFI-TIPPS

### Recording
- **Schließen Sie alle unnötigen Programme** (Notifications!)
- **Verwenden Sie einen zweiten Monitor** für Camtasia Recorder
- **Machen Sie Testaufnahmen** zum Timing prüfen
- **Langsame, bewusste Mausbewegungen**

### Editing
- **Nutzen Sie Markers** in Camtasia für jede Szene
- **Ripple Delete** für saubere Cuts ohne Lücken
- **Speed-Adjustments:** Zu langsame Teile auf 1.2x
- **Freeze Frame:** Bei wichtigen Momenten 1-2s halten

### Audio
- **Silence** zwischen Voiceover-Clips löschen
- **Audio-Levels** prüfen (-3dB peak max)
- **Noise Removal** falls nötig (Camtasia Audio Effects)

---

## 📞 SUPPORT

Bei Fragen zu Camtasia:
- Offizielle Tutorials: https://www.techsmith.com/learn/
- Keyboard Shortcuts: `Ctrl+Shift+K` (Windows) / `Cmd+Shift+K` (Mac)

**Viel Erfolg! 🚀**
