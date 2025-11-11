# RSA Key Generation for SSO System

## Deutsch

### Option 1: Vorhandene PASSPORT-Keys verwenden (empfohlen für pds-homepage)

**Für Service 1 (pds-homepage):**

Wenn in der `.env` Datei bereits `PASSPORT_PRIVATE_KEY` und `PASSPORT_PUBLIC_KEY` definiert sind, können diese direkt verwendet werden. Keine weitere Aktion erforderlich!

Das System wird automatisch diese Umgebungsvariablen verwenden.

**Für Service 2 (riskmanagementv2):**

Sie müssen den `PASSPORT_PUBLIC_KEY` Wert von Service 1 kopieren und in Service 2's `.env` als `SSO_PUBLIC_KEY` oder `PASSPORT_PUBLIC_KEY` einfügen:

```env
# In /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/.env
SSO_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
...Ihr Public Key hier...
-----END PUBLIC KEY-----"
```

### Option 2: Neue Schlüssel generieren (für lokale Entwicklung ohne Google App Engine)

Führen Sie folgende Befehle im Terminal aus:

```bash
# Private Key generieren (RS256)
openssl genrsa -out sso-private.key 4096

# Public Key aus Private Key extrahieren
openssl rsa -in sso-private.key -pubout -out sso-public.key

# Berechtigungen setzen (wichtig für Sicherheit!)
chmod 600 sso-private.key
chmod 644 sso-public.key
```

### Schlüssel platzieren (nur für Option 2 - neue Schlüssel)

**Service 1 (pds-homepage) - Identity Provider:**
```bash
# Option A: Als Dateien speichern
mkdir -p /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/storage/app/sso
cp sso-private.key /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/storage/app/sso/
cp sso-public.key /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/storage/app/sso/
chmod 600 /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/storage/app/sso/sso-private.key
chmod 644 /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/storage/app/sso/sso-public.key

# Option B: Als Umgebungsvariablen (empfohlen)
# Fügen Sie zur .env hinzu:
# SSO_USE_ENV_KEYS=false (um Dateien zu verwenden)
# ODER lassen Sie SSO_USE_ENV_KEYS=true und setzen Sie:
# PASSPORT_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----\n...\n-----END RSA PRIVATE KEY-----"
# PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
```

**Service 2 (riskmanagementv2) - Service Provider:**
```bash
# Option A: Als Datei speichern
mkdir -p /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/storage/app/sso
cp sso-public.key /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/storage/app/sso/
chmod 644 /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/storage/app/sso/sso-public.key

# Option B: Als Umgebungsvariable (empfohlen)
# Fügen Sie zur .env hinzu:
# SSO_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
# ODER
# PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
```

### Wichtige Sicherheitshinweise

- **NIEMALS** den Private Key (`sso-private.key`) in Git committen
- **NIEMALS** den Private Key außerhalb von Service 1 kopieren
- Fügen Sie `storage/app/sso/*.key` zu Ihrer `.gitignore` hinzu
- Der Private Key wird nur von Service 1 (IdP) zum Signieren verwendet
- Der Public Key wird von Service 2 (SP) zum Verifizieren verwendet
- Verwenden Sie in Produktion stärkere Keys (4096 Bit) und Key-Rotation

### .gitignore Einträge hinzufügen

Fügen Sie in beiden Projekten folgende Zeile zur `.gitignore` hinzu:
```
storage/app/sso/*.key
```

---

## English

### Generate Key Pair for Service 1 (pds-homepage)

Execute the following commands in your terminal:

```bash
# Generate private key (RS256)
openssl genrsa -out sso-private.key 4096

# Extract public key from private key
openssl rsa -in sso-private.key -pubout -out sso-public.key

# Set permissions (important for security!)
chmod 600 sso-private.key
chmod 644 sso-public.key
```

### Place Keys in Both Projects

**Service 1 (pds-homepage) - Identity Provider:**
```bash
# Create the directory
mkdir -p /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/storage/app/sso

# Copy BOTH keys to Service 1
cp sso-private.key /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/storage/app/sso/
cp sso-public.key /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/storage/app/sso/

# Set permissions
chmod 600 /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/storage/app/sso/sso-private.key
chmod 644 /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage/storage/app/sso/sso-public.key
```

**Service 2 (riskmanagementv2) - Service Provider:**
```bash
# Create the directory
mkdir -p /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/storage/app/sso

# Copy ONLY the PUBLIC KEY to Service 2
cp sso-public.key /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/storage/app/sso/

# Set permissions
chmod 644 /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2/storage/app/sso/sso-public.key
```

### Important Security Notes

- **NEVER** commit the private key (`sso-private.key`) to Git
- **NEVER** copy the private key outside of Service 1
- Add `storage/app/sso/*.key` to your `.gitignore`
- The private key is only used by Service 1 (IdP) for signing
- The public key is used by Service 2 (SP) for verification
- In production, use stronger keys (4096 bits) and implement key rotation

### Add .gitignore Entries

Add the following line to `.gitignore` in both projects:
```
storage/app/sso/*.key
```
