# GitHub Push-Einrichtung

## Aktueller Status
- ✅ Commit erstellt: `dc8b5f6`
- ⏳ Push ausstehend

## Option 1: SSH (empfohlen)

### SSH-Key zu GitHub hinzufügen:
1. Öffnen Sie: https://github.com/settings/ssh/new
2. Title: `Risk Management Server`
3. Key: `ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIBf+DFG5fYof77b1E6Ij2Eb6MmEroYcXW33TdjANxj45 henningd@passolution.de`
4. Klicken Sie auf "Add SSH key"

### Dann pushen:
```bash
git push origin main
```

## Option 2: HTTPS mit Personal Access Token

### Token erstellen:
1. Öffnen Sie: https://github.com/settings/tokens/new
2. Note: `Risk Management v2`
3. Expiration: `90 days` (oder länger)
4. Scopes: Aktivieren Sie `repo` (full control)
5. Klicken Sie auf "Generate token"
6. **WICHTIG:** Kopieren Sie das Token sofort!

### Git Credential Helper einrichten:
```bash
git config --global credential.helper store
```

### Zurück zu HTTPS wechseln und pushen:
```bash
git remote set-url origin https://github.com/DHE-DEV/riskmanagementv2.git
git push origin main
```

Beim Push werden Sie nach Username und Passwort gefragt:
- Username: `DHE-DEV`
- Password: `<Ihr Personal Access Token>`

Das Token wird dann gespeichert und Sie müssen es nicht erneut eingeben.

## Commit-Zusammenfassung

**Änderungen:**
- CustomEventObserver für automatische Icon-Aktualisierung
- Marker-Clustering auf allen Zoom-Stufen
- Auto-Spiderfy beim Klick auf Events
- Automatisches Popup-Schließen
- Playwright-Integration für Tests

**Dateien geändert:** 7 (+162, -20)
