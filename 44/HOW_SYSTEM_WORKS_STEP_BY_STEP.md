# 🌐 HOE HET SYSTEEM WERKT - STAP VOOR STAP

## 📋 INHOUDSOPGAVE

1. [Overzicht](#overzicht)
2. [Stap 1: Gebruiker Registreert](#stap-1-gebruiker-registreert)
3. [Stap 2: Device Registratie](#stap-2-device-registratie)
4. [Stap 3: VPN Verbinding](#stap-3-vpn-verbinding)
5. [Stap 4: Whitelist Beheer](#stap-4-whitelist-beheer)
6. [Stap 5: DNS Filtering](#stap-5-dns-filtering)
7. [Stap 6: Porn Blokkering](#stap-6-porn-blokkering)
8. [Complete Flow Diagram](#complete-flow-diagram)

---

## 🎯 OVERZICHT

Het systeem werkt als volgt:

```
Gebruiker → Website → Database → DNS Server → Internet
                ↓
         Whitelist Check
                ↓
    ✅ Toegestaan → Website laadt
    ❌ Geblokkeerd → NXDOMAIN (geen toegang)
```

**Kernprincipe:** Alleen domeinen in de whitelist kunnen worden opgelost. Alles anders is geblokkeerd.

---

## 📝 STAP 1: GEBRUIKER REGISTREERT

### Wat gebeurt er?

1. **Gebruiker gaat naar website**
   - URL: `http://localhost/44/subscribe.html`
   - Ziet abonnement plannen

2. **Gebruiker kiest plan en registreert**
   - Vult email + wachtwoord in
   - Klikt "Abonnement Aansluiten"

3. **Backend verwerkt registratie**
   ```
   Frontend → POST /api/register.php
              ↓
   Backend:
   - Valideert email
   - Hasht wachtwoord (password_hash)
   - Maakt user aan in database (users tabel)
   - Maakt subscription aan (subscriptions tabel)
   - Genereert JWT token
              ↓
   Frontend ontvangt token
   ```

4. **Automatische Device Registratie**
   - Bij registratie wordt automatisch een device aangemaakt
   - Device krijgt VPN IP (bijv. `10.10.0.12`)
   - Device krijgt WireGuard public key
   - Device status = "active"

5. **WireGuard Config Download**
   - Automatisch WireGuard config gedownload
   - Config bevat:
     - VPN server endpoint
     - Client IP (10.10.0.x)
     - DNS = 10.10.0.1 (VPN DNS server)
     - AllowedIPs = 0.0.0.0/0 (full-tunnel)

**Resultaat:** Gebruiker heeft account + device + VPN config

---

## 📱 STAP 2: DEVICE REGISTRATIE

### Wat gebeurt er?

1. **Gebruiker logt in**
   - URL: `http://localhost/44/public/index.html`
   - Vult email + wachtwoord in

2. **Backend authenticatie**
   ```
   Frontend → POST /api/login.php
              ↓
   Backend:
   - Valideert email format
   - Check brute force protection
   - Verifieert wachtwoord (password_verify)
   - Genereert JWT token
              ↓
   Frontend ontvangt token + user info
   ```

3. **Automatische Device Detectie**
   - Frontend detecteert device fingerprint
   - Checkt of device al bestaat
   - Als nieuw → automatisch registreren
   - Als bestaand → bestaand device gebruiken

4. **Device Info Ophalen**
   ```
   Frontend → GET /api/get_devices.php
              ↓
   Backend:
   - Haalt alle devices op voor gebruiker
   - Inclusief subscription info
   - Inclusief device status
              ↓
   Frontend toont devices
   ```

**Resultaat:** Gebruiker ziet zijn devices in dashboard

---

## 🔐 STAP 3: VPN VERBINDING

### Wat gebeurt er?

1. **Gebruiker installeert WireGuard**
   - Download WireGuard app (iOS/Android/Desktop)
   - Importeert config bestand (.conf)

2. **VPN Verbinding Opzetten**
   ```
   Device → WireGuard App
            ↓
   Connect naar VPN Server
            ↓
   VPN Server:
   - Accepteert verbinding
   - Wijs IP toe: 10.10.0.12
   - Forceer DNS: 10.10.0.1
            ↓
   Device krijgt VPN IP
   ```

3. **Traffic Routing**
   - **Alle** internet traffic gaat via VPN
   - `AllowedIPs = 0.0.0.0/0` = full-tunnel
   - Geen directe internet toegang mogelijk
   - DNS queries gaan naar: `10.10.0.1` (VPN DNS server)

**Resultaat:** Alle internet traffic gaat via VPN

---

## 📋 STAP 4: WHITELIST BEHEER

### Wat gebeurt er?

1. **Gebruiker voegt domein toe**
   - In dashboard: voert domein in (bijv. "wikipedia.org")
   - Klikt "Toevoegen"

2. **Backend Validatie**
   ```
   Frontend → POST /api/add_whitelist.php
              {
                "device_id": 123,
                "domain": "wikipedia.org"
              }
              ↓
   Backend:
   - Normaliseert domein (lowercase, geen www)
   - Check pornografische domein → BLOCK als porn
   - Check duplicate
   - Check device ownership
   - Slaat op in database (whitelist tabel)
              ↓
   Frontend ontvangt success
   ```

3. **Whitelist Ophalen**
   ```
   Frontend → GET /api/get_whitelist.php?device_id=123
              ↓
   Backend:
   - Haalt whitelist op uit database
   - Filtert pornografische domeinen (zelfs als in whitelist)
   - Retourneert array: ["wikipedia.org", "google.com"]
              ↓
   Frontend toont whitelist
   ```

**Resultaat:** Domeinen staan in whitelist

---

## 🌐 STAP 5: DNS FILTERING

### Wat gebeurt er?

1. **Gebruiker bezoekt website**
   - Typ in browser: `wikipedia.org`
   - Browser vraagt DNS: "Wat is het IP van wikipedia.org?"

2. **DNS Query naar VPN DNS Server**
   ```
   Browser → DNS Query: "wikipedia.org"
            ↓
   Query gaat naar: 10.10.0.1 (VPN DNS server)
            ↓
   DNS Server ontvangt query op poort 53
   ```

3. **DNS Server Detecteert Client**
   ```
   DNS Server:
   - Leest source IP: 10.10.0.12 (VPN client IP)
   - Zoekt device_id via API:
     GET /api/get_device_by_ip.php?ip=10.10.0.12
            ↓
   Backend:
   - Zoekt device met wg_ip = 10.10.0.12
   - Retourneert: {"found": true, "device_id": 123}
            ↓
   DNS Server krijgt device_id: 123
   ```

4. **DNS Server Haalt Whitelist Op**
   ```
   DNS Server:
   - Vraagt whitelist op:
     GET /api/get_whitelist.php?device_id=123
            ↓
   Backend:
   - Haalt whitelist op uit database
   - Filtert pornografische domeinen
   - Retourneert: ["wikipedia.org", "google.com"]
            ↓
   DNS Server krijgt whitelist
   ```

5. **DNS Server Beslist**
   ```
   DNS Server checkt:
   - Is "wikipedia.org" in whitelist? → YES
   - Is "wikipedia.org" pornografisch? → NO
            ↓
   RESOLVE: Forward query naar 8.8.8.8
            ↓
   Krijgt IP adres: 91.198.174.192
            ↓
   Retourneert IP naar browser
            ↓
   Browser kan website laden ✅
   ```

**Resultaat:** Website laadt als domein in whitelist staat

---

## 🚫 STAP 6: PORNO BLOKKERING

### Wat gebeurt er?

1. **Gebruiker probeert pornografische site**
   - Typ in browser: `pornhub.com`
   - Browser vraagt DNS: "Wat is het IP van pornhub.com?"

2. **DNS Query naar VPN DNS Server**
   ```
   Browser → DNS Query: "pornhub.com"
            ↓
   Query gaat naar: 10.10.0.1 (VPN DNS server)
            ↓
   DNS Server ontvangt query
   ```

3. **DNS Server Detecteert Pornografisch Domein**
   ```
   DNS Server:
   - Check: is_pornographic_domain("pornhub.com")
   - Pattern match: "porn" in "pornhub.com" → YES
            ↓
   PERMANENT BLOCK: Retourneer NXDOMAIN
            ↓
   Browser krijgt: "Domain not found"
            ↓
   Website kan NIET worden geladen ❌
   ```

4. **Zelfs Als In Whitelist**
   ```
   Als gebruiker per ongeluk "pornhub.com" toevoegt:
   - API blokkeert toevoegen (add_whitelist.php)
   - Als toch in database → DNS server blokkeert altijd
   - Automatische cleanup verwijdert uit whitelist
            ↓
   Pornografische sites zijn ALTIJD geblokkeerd ✅
   ```

**Resultaat:** Pornografische sites kunnen NOOIT worden geladen

---

## 🔄 COMPLETE FLOW DIAGRAM

### Scenario 1: Toegestane Website

```
1. Gebruiker typt: "wikipedia.org"
   ↓
2. Browser → DNS Query: "wikipedia.org"
   ↓
3. Query naar VPN DNS Server (10.10.0.1:53)
   ↓
4. DNS Server detecteert client IP: 10.10.0.12
   ↓
5. DNS Server vraagt device_id:
   GET /api/get_device_by_ip.php?ip=10.10.0.12
   → {"found": true, "device_id": 123}
   ↓
6. DNS Server vraagt whitelist:
   GET /api/get_whitelist.php?device_id=123
   → ["wikipedia.org", "google.com"]
   ↓
7. DNS Server checkt:
   - "wikipedia.org" in whitelist? → YES
   - Pornografisch? → NO
   ↓
8. DNS Server resolve via 8.8.8.8
   → IP: 91.198.174.192
   ↓
9. Browser krijgt IP adres
   ↓
10. Browser laadt website ✅
```

### Scenario 2: Geblokkeerde Website (Niet in Whitelist)

```
1. Gebruiker typt: "example.com"
   ↓
2. Browser → DNS Query: "example.com"
   ↓
3. Query naar VPN DNS Server (10.10.0.1:53)
   ↓
4. DNS Server detecteert client IP: 10.10.0.12
   ↓
5. DNS Server vraagt device_id:
   GET /api/get_device_by_ip.php?ip=10.10.0.12
   → {"found": true, "device_id": 123}
   ↓
6. DNS Server vraagt whitelist:
   GET /api/get_whitelist.php?device_id=123
   → ["wikipedia.org", "google.com"]
   ↓
7. DNS Server checkt:
   - "example.com" in whitelist? → NO
   ↓
8. DNS Server retourneert NXDOMAIN
   ↓
9. Browser krijgt: "Domain not found"
   ↓
10. Website kan NIET worden geladen ❌
```

### Scenario 3: Pornografische Website

```
1. Gebruiker typt: "pornhub.com"
   ↓
2. Browser → DNS Query: "pornhub.com"
   ↓
3. Query naar VPN DNS Server (10.10.0.1:53)
   ↓
4. DNS Server detecteert client IP: 10.10.0.12
   ↓
5. DNS Server checkt:
   - is_pornographic_domain("pornhub.com")? → YES
   ↓
6. PERMANENT BLOCK: Retourneer NXDOMAIN
   (Zelfs als in whitelist!)
   ↓
7. Browser krijgt: "Domain not found"
   ↓
8. Website kan NIET worden geladen ❌
```

---

## 🔐 SECURITY LAYERS

### Layer 1: API Blokkering
- **Waar:** `api/add_whitelist.php`
- **Wat:** Blokkeert toevoegen van pornografische domeinen
- **Resultaat:** Kan niet worden toegevoegd aan whitelist

### Layer 2: Whitelist Filtering
- **Waar:** `api/get_whitelist.php`
- **Wat:** Filtert pornografische domeinen uit whitelist
- **Resultaat:** Zelfs als in database → niet in whitelist

### Layer 3: DNS Blokkering
- **Waar:** `dns_whitelist_server.py`
- **Wat:** Blokkeert altijd pornografische domeinen
- **Resultaat:** Altijd NXDOMAIN voor pornografische sites

### Layer 4: Automatische Cleanup
- **Waar:** `api/cleanup_porn_domains.php`
- **Wat:** Verwijdert pornografische domeinen uit database
- **Resultaat:** Database blijft schoon

---

## 📊 DATABASE STRUCTUUR

### Tabel: `users`
```
- id (INT, PRIMARY KEY)
- email (VARCHAR, UNIQUE)
- password_hash (VARCHAR)
- is_admin (TINYINT)
- created_at (DATETIME)
```

### Tabel: `devices`
```
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY → users.id)
- name (VARCHAR)
- wg_ip (VARCHAR) → VPN IP (10.10.0.x)
- wg_public_key (VARCHAR)
- status (ENUM: 'active', 'inactive')
- created_at (DATETIME)
```

### Tabel: `whitelist`
```
- id (INT, PRIMARY KEY)
- device_id (INT, FOREIGN KEY → devices.id)
- domain (VARCHAR)
- enabled (TINYINT)
- created_at (DATETIME)
```

### Tabel: `subscriptions`
```
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY → users.id)
- plan_id (INT)
- status (ENUM: 'active', 'expired', 'cancelled')
- expires_at (DATETIME)
```

---

## 🎯 BELANGRIJKE CONCEPTEN

### Whitelist-Only Filtering
- **Principe:** Alles is standaard geblokkeerd
- **Alleen toegestaan:** Domeinen in whitelist
- **Lege whitelist:** Geen internet toegang
- **Waarom:** Enige methode die 100% betrouwbaar is

### VPN Verplichting
- **Waarom:** Controle op netwerkniveau
- **Full-tunnel:** Alle traffic via VPN
- **DNS Forcing:** DNS queries naar VPN DNS server
- **Kill-switch:** Geen internet zonder VPN

### Pornografische Blokkering
- **Permanent:** Kan niet worden uitgeschakeld
- **Multi-layer:** 4 lagen beveiliging
- **Meertalig:** Werkt in alle talen
- **Automatisch:** Geen handmatige actie nodig

---

## ✅ CONCLUSIE

Het systeem werkt als volgt:

1. **Gebruiker registreert** → Account + Device + VPN Config
2. **Gebruiker verbindt VPN** → Alle traffic via VPN
3. **Gebruiker voegt domeinen toe** → Opgeslagen in whitelist
4. **Browser vraagt DNS** → DNS server checkt whitelist
5. **Domein in whitelist** → Website laadt ✅
6. **Domein niet in whitelist** → NXDOMAIN ❌
7. **Pornografisch domein** → ALTIJD NXDOMAIN ❌

**Resultaat:** Alleen toegestane domeinen kunnen worden bezocht. Alles anders is geblokkeerd.
