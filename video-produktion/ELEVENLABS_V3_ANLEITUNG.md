# 🎙️ ElevenLabs v3 Anleitung - Optimierte Voiceover-Erstellung

## ✨ Was ist neu in Eleven v3?

Eleven v3 ist das bisher ausdrucksstärkste Text-to-Speech-Modell von ElevenLabs und bietet:

- **🎭 Audio-Tags**: Emotionen und Lieferung steuern
- **👥 Mehrsprecher-Dialog**: Mehrere Stimmen in einem Audio
- **⏸️ Präzise Pausen**: Mit `[pause:0.3s]` Syntax
- **😊 Emotionen**: Natürliche Ausdrucksweise

## 🚀 QUICK START

### 1. Modell auswählen
- Gehen Sie zu https://elevenlabs.io/
- Wählen Sie **"Eleven v3"** als Modell

### 2. Stimme auswählen
Empfohlene deutsche Stimmen:
- **Daniel** (männlich, professionell)
- **Bella** (weiblich, freundlich)
- **Callum** (männlich, enthusiastisch)

### 3. Text einfügen
- Öffnen Sie `voiceover-texte/01_INTRO.txt`
- Kopieren Sie den **gesamten Text** (inklusive Tags!)
- Fügen Sie ihn in ElevenLabs ein

### 4. Generieren
- Klicken Sie auf **"Generate"**
- Hören Sie sich das Ergebnis an
- Bei Bedarf: Klicken Sie auf **"Verbessern"** für automatische Tag-Vorschläge

### 5. Download
- Download als **MP3, 44.1kHz**
- Speichern als `audio-exports/01_INTRO.mp3`

---

## 🏷️ AUDIO-TAGS ERKLÄRT

### Emotionen & Stimmung

Unsere Texte verwenden folgende Tags:

| Tag | Bedeutung | Verwendung |
|-----|-----------|------------|
| `[freundlich]` | Warme, einladende Stimme | Begrüßungen, Abschluss |
| `[professionell]` | Sachlich, kompetent | Technische Erklärungen |
| `[enthusiastisch]` | Begeistert, energiegeladen | Features hervorheben |
| `[erklärend]` | Didaktisch, verständlich | Anleitungen |
| `[ruhig]` | Entspannt, gelassen | Beruhigende Informationen |
| `[betont]` | Wichtige Punkte hervorheben | Schlüsselinformationen |
| `[zufrieden]` | Positive Bestätigung | Erfolgreiche Aktionen |
| `[fragend]` | Leicht fragende Intonation | Rhetorische Fragen |
| `[motivierend]` | Ermutigend, inspirierend | Call-to-Action |
| `[stolz]` | Selbstbewusst präsentieren | Produktvorstellung |
| `[anweisend]` | Klare Anweisung | Handlungsaufforderungen |
| `[Aufzählung]` | Listen-Tonfall | Mehrere Punkte auflisten |

### Pausen

```
[pause:0.2s]  → Kurze Pause (beim Atmen)
[pause:0.3s]  → Normale Pause (zwischen Sätzen)
[pause:0.4s]  → Längere Pause (zwischen Abschnitten)
```

---

## 📝 BEISPIEL: Text mit Tags

**Original:**
```
Willkommen zur Risiko-Übersicht.
```

**Optimiert für v3:**
```
[freundlich] Willkommen zur Risiko-Übersicht [pause:0.3s] Ihrer zentralen Anlaufstelle für weltweite Reisesicherheit.
```

**Ergebnis:**
- Freundlicher Tonfall
- Natürliche Pause in der Mitte
- Professioneller Gesamteindruck

---

## 🎯 SETTINGS IN ELEVENLABS

### Für optimale Qualität:

**Voice Settings:**
- **Stability:** 70-80%
  - Höher = konsistenter, aber weniger emotional
  - Niedriger = ausdrucksstärker, aber variabler
  - **Empfehlung: 75%**

- **Similarity Enhancement:** 75-85%
  - Wie nah am Original-Voice-Charakter
  - **Empfehlung: 80%**

- **Style Exaggeration:** 0-20%
  - Wie stark die Emotionen ausgeprägt sind
  - **Für Business-Videos: 10%**

### Output Settings:
- **Format:** MP3
- **Sample Rate:** 44.1 kHz
- **Quality:** Highest

---

## ⚡ WORKFLOW FÜR 11 SZENEN

### Effiziente Batch-Produktion:

1. **Alle Texte vorbereiten** (bereits erledigt! ✅)
2. **ElevenLabs öffnen**, v3 wählen
3. **Stimme auswählen** (einmal, bleibt für alle)

**Dann für jede Szene:**

```
4. Text aus 01_INTRO.txt kopieren
5. In ElevenLabs einfügen
6. Generate klicken
7. Anhören (Qualitätskontrolle!)
8. Download → Speichern als 01_INTRO.mp3
9. Weiter mit 02_DASHBOARD.txt
```

**⏱️ Geschätzte Zeit: 30-45 Minuten**

---

## 🎨 ERWEITERTE FEATURES (Optional)

### 1. Verbessern-Button nutzen

ElevenLabs kann automatisch weitere Tags vorschlagen:

- Klicken Sie auf **"Verbessern"**
- System fügt Tags wie `[lachen]`, `[seufzen]` hinzu
- Prüfen Sie die Vorschläge
- Behalten Sie nur passende Tags

### 2. Mehrsprecher-Dialog (Advanced)

Falls Sie später verschiedene Sprecher nutzen möchten:

```
+ Sprecher hinzufügen
```

Beispiel:
```
Sprecher 1 (männlich): [professionell] Die Risiko-Übersicht bietet...
Sprecher 2 (weiblich): [enthusiastisch] Und das Beste ist...
```

**Für dieses Projekt:** Ein Sprecher reicht!

### 3. Custom Pronunciations

Falls Namen/Begriffe falsch ausgesprochen werden:

```
VisumPoint → Visum-Point
API → A-P-I (einzeln buchstabiert)
```

In ElevenLabs: Settings → Pronunciation Dictionary

---

## ✅ QUALITÄTSKONTROLLE

### Nach jedem generierten Audio prüfen:

- [ ] **Emotionen passen?** Klingt freundlich/professionell wie gewünscht?
- [ ] **Pausen korrekt?** Nicht zu kurz, nicht zu lang?
- [ ] **Deutliche Aussprache?** Alle Wörter verständlich?
- [ ] **Lautstärke konsistent?** Keine plötzlichen Lautstärke-Sprünge?
- [ ] **Timing passt?** Länge wie im Drehbuch geplant?

### Bei Problemen:

**Problem:** Zu roboterhaft
- **Lösung:** Style Exaggeration erhöhen (auf 15-20%)

**Problem:** Zu übertrieben emotional
- **Lösung:** Style Exaggeration senken (auf 5%)

**Problem:** Pausen zu kurz
- **Lösung:** Pausen-Zeit erhöhen (`[pause:0.5s]`)

**Problem:** Falsche Betonung
- **Lösung:** `[betont]` Tag vor wichtiges Wort setzen

---

## 📊 VERGLEICH: Ohne vs. Mit Tags

### ❌ OHNE TAGS (Basic TTS):
```
Willkommen zur Risiko-Übersicht. Ihrer zentralen Anlaufstelle
für weltweite Reisesicherheit. Mit dieser Funktion behalten
Sie jederzeit den Überblick.
```
**Ergebnis:** Monoton, roboterhaft, keine Pausen

### ✅ MIT ELEVEN V3 TAGS:
```
[freundlich] Willkommen zur Risiko-Übersicht [pause:0.3s]
Ihrer zentralen Anlaufstelle für weltweite Reisesicherheit.

[enthusiastisch] Mit dieser Funktion behalten Sie jederzeit
den Überblick [pause:0.2s] über aktuelle Ereignisse
[pause:0.2s] und betroffene Reisende.
```
**Ergebnis:** Natürlich, ausdrucksstark, professionell!

---

## 💡 PROFI-TIPPS

### 1. Konsistenz wahren
- Nutzen Sie **dieselbe Stimme** für alle 11 Szenen
- Behalten Sie die **gleichen Settings** bei
- Generieren Sie alle Audios **am selben Tag**

### 2. Test-Generierung
- Generieren Sie **Szene 1 zuerst** komplett
- Hören Sie sie mehrmals an
- Passen Sie Settings an, falls nötig
- **Dann** erst alle anderen generieren

### 3. Backup erstellen
- Speichern Sie alle MP3s zusätzlich in der Cloud
- Notieren Sie sich die verwendete Stimme
- Dokumentieren Sie Ihre Settings

### 4. Feintuning nach Bedarf
Sie können einzelne Wörter betonen:
```
[betont] wichtigsten [/betont] Kennzahlen
```

Oder Geschwindigkeit anpassen:
```
[schneller] für eine schnelle Übersicht [/schneller]
```

---

## 🎓 LERNRESSOURCEN

**ElevenLabs Dokumentation:**
- Audio-Tags Guide: https://elevenlabs.io/docs/speech-synthesis/prompting
- Best Practices: https://elevenlabs.io/docs/speech-synthesis/best-practices

**Video-Tutorials:**
- ElevenLabs YouTube Kanal: https://www.youtube.com/@elevenlabs

---

## 📞 SUPPORT

**Bei Problemen mit ElevenLabs:**
- Help Center: https://help.elevenlabs.io/
- Community Forum: https://discord.gg/elevenlabs
- Email: support@elevenlabs.io

**Bei Fragen zu den Texten:**
- Alle Texte sind bereits optimiert
- Bei Bedarf können Tags angepasst werden
- Experimentieren Sie ruhig!

---

## ✨ ZUSAMMENFASSUNG

**Was Sie tun:**
1. ✅ Eleven v3 Modell wählen
2. ✅ Deutsche Stimme auswählen (z.B. Daniel)
3. ✅ Text aus .txt Datei kopieren (MIT allen Tags!)
4. ✅ Generate klicken
5. ✅ Als MP3 speichern

**Was Sie NICHT tun müssen:**
- ❌ Texte umschreiben (bereits optimiert!)
- ❌ Tags manuell hinzufügen (schon drin!)
- ❌ Pausen anpassen (bereits eingebaut!)

**Die Texte sind fertig optimiert für Eleven v3! 🎉**

Einfach kopieren, generieren, speichern → Fertig!

---

**Viel Erfolg!** 🚀
