# Passolution Keycloak Theme

Custom Login-Theme für Keycloak, passend zum Design der Passolution Travel Information Platform.

## Installation

1. Theme-Ordner auf den Keycloak-Server kopieren:

```bash
scp -r passolution/ user@keycloak-server:/opt/keycloak/themes/
```

2. In der Keycloak Admin-Konsole:
   - **Realm Settings** > **Themes** > **Login Theme** > `passolution` auswählen
   - Speichern

3. Keycloak-Cache leeren (falls nötig):

```bash
/opt/keycloak/bin/kc.sh build
```

## Struktur

```
passolution/
  login/
    theme.properties          # Theme-Konfiguration (erbt von keycloak)
    resources/
      css/styles.css          # Custom Styles (Dark Theme)
      img/logo.png            # Passolution Logo
    messages/
      messages_de.properties  # Deutsche Übersetzungen
      messages_en.properties  # Englische Übersetzungen
```
