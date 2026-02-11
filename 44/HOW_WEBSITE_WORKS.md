# 🌐 HOE DE WEBSITE WERKT - COMPLETE DOCUMENTATIE

## 📋 INHOUDSOPGAVE

1. [Architectuur Overzicht](#architectuur-overzicht)
2. [Frontend (Gebruikersinterface)](#frontend-gebruikersinterface)
3. [Backend API (PHP)](#backend-api-php)
4. [Database Structuur](#database-structuur)
5. [DNS Server (Python)](#dns-server-python)
6. [VPN/WireGuard Integratie](#vpnwireguard-integratie)
7. [Whitelist-Only Filtering](#whitelist-only-filtering)
8. [Pornografische Content Blokkering](#pornografische-content-blokkering)
9. [Security Features](#security-features)
10. [Data Flow Diagram](#data-flow-diagram)
11. [API Endpoints](#api-endpoints)
12. [Troubleshooting](#troubleshooting)

---

## 🏗️ ARCHITECTUUR OVERZICHT

### **Systeem Componenten**

```
┌─────────────────────────────────────────────────────────────┐
│                    GEBRUIKER BROWSER                         │
│  (Chrome, Firefox, Safari, Edge)                            │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ HTTPS/HTTP
                     │
┌────────────────────▼────────────────────────────────────────┐
│              FRONTEND (HTML/JS)                              │
│  • index.html, app.js                                       │
│  • admin/index.html, admin/admin.js                         │
│  • subscribe.html                                            │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ API Calls (JSON)
                     │
┌────────────────────▼────────────────────────────────────────┐
│              BACKEND API (PHP)                              │
│  • api/login.php                                            │
│  • api/get_whitelist.php                                    │
│  • api/add_whitelist.php                                    │
│  • api/get_devices.php                                      │
│  • etc...                                                    │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ SQL Queries
                     │
┌────────────────────▼────────────────────────────────────────┐
│              DATABASE (MySQL)                                │
│  • users                                                     │
│  • devices                                                   │
│  • whitelist                                                │
│  • subscriptions                                            │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ DNS Queries
                     │
┌────────────────────▼────────────────────────────────────────┐
│              DNS SERVER (Python)                            │
│  • dns_whitelist_server.py                                  │
│  • Port 53                                                  │
│  • Whitelist-only filtering                                 │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ VPN Traffic
                     │
┌────────────────────▼────────────────────────────────────────┐
│              WIREGUARD VPN                                   │
│  • Full-tunnel (0.0.0.0/0)                                  │
│  • DNS = 10.10.0.1                                          │
│  • Kill-switch enabled                                      │
└──────────────────────────────────────────────────────────────┘
```

---

## 🎨 FRONTEND (GEBRUIKERSINTERFACE)

### **Hoofdcomponenten**

#### **1. Login Systeem**
- **Bestand:** `app.js` → `login()`
- **API:** `POST /api/login.php`
- **Flow:**
  1. Gebruiker voert email + wachtwoord in
  2. Frontend stuurt JSON naar API
  3. API valideert en retourneert JWT token
  4. Token wordt opgeslagen in `localStorage`
  5. Gebruiker wordt doorgestuurd naar dashboard

#### **2. Device Management**
- **Bestand:** `app.js` → `loadDevices()`, `selectDevice()`
- **API:** `GET /api/get_devices.php`
- **Features:**
  - Toont alle devices van gebruiker
  - Automatische device registratie bij login
  - WireGuard config automatisch downloaden
  - Device status (actief/inactief)

#### **3. Whitelist Beheer**
- **Bestand:** `app.js` → `loadWhitelist()`, `addWhitelist()`, `deleteWhitelist()`
- **API:** 
  - `GET /api/get_whitelist.php?device_id=X`
  - `POST /api/add_whitelist.php`
  - `DELETE /api/delete_whitelist.php?id=X`
- **Features:**
  - Toevoegen/verwijderen van domeinen
  - Real-time updates
  - Validatie (geen pornografische domeinen)

#### **4. Admin Panel**
- **Bestand:** `admin/index.html`, `admin/admin.js`
- **Features:**
  - Gebruikersbeheer
  - Device overzicht (alle gebruikers)
  - Statistieken
  - Device registratie link genereren
  - Database backups

### **JavaScript Utilities**

#### **`apiFetch(path, opts)`**
- Maakt geauthenticeerde API calls
- Voegt automatisch Bearer token toe
- Handelt 401 (session expired) af
- Retourneert JSON data

#### **`API(path)`**
- Genereert correcte API URL
- Detecteert automatisch base path
- Werkt in alle directories (`/44/`, `/44/public/`, `/44/admin/`)

---

## 🔧 BACKEND API (PHP)

### **Configuratie**

#### **`config.php`**
- Centraal configuratiebestand
- Database connectie (MySQL)
- Environment detectie (production/development)
- Security headers
- CORS configuratie
- Helper functies (`json_out()`, `require_user()`, etc.)

#### **Database Connectie**
```php
// Production: TCP/IP connectie
// Development: Socket connectie (XAMPP)
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
```

### **Authenticatie Systeem**

#### **Login Flow (`api/login.php`)**
1. Ontvangt email + wachtwoord (JSON)
2. Valideert email format
3. Check brute force protection
4. Verifieert wachtwoord hash
5. Genereert JWT token
6. Retourneert token + user info

#### **Token-based Authenticatie**
- JWT tokens in `Authorization: Bearer TOKEN` header
- Tokens opgeslagen in `localStorage` (frontend)
- Tokens gevalideerd via `require_user($conn)` helper

#### **`require_user($conn)`**
- Leest token uit Authorization header
- Valideert token
- Retourneert user data
- Gooit 401 error als token ongeldig

### **Whitelist API**

#### **`api/get_whitelist.php`**
- **Input:** `device_id` (GET parameter)
- **Output:** JSON array van domeinen: `["google.com", "wikipedia.org"]`
- **Logica:**
  1. Check device ownership
  2. Check device status (moet "active" zijn)
  3. Haal whitelist op uit database
  4. Filter pornografische domeinen
  5. Normaliseer domeinen (lowercase, geen www)
  6. Retourneer array

**Belangrijk:** Lege array `[]` = GEEN INTERNET TOEGANG

#### **`api/add_whitelist.php`**
- **Input:** `device_id`, `domain` (JSON body)
- **Validatie:**
  - Domain format check
  - Pornografische domeinen worden geblokkeerd
  - Duplicate check
- **Output:** Success/error message

#### **`api/delete_whitelist.php`**
- **Input:** `id` (whitelist entry ID)
- **Check:** Device ownership
- **Output:** Success/error message

### **Device API**

#### **`api/get_devices.php`**
- Retourneert alle devices van ingelogde gebruiker
- Inclusief subscription info
- Inclusief device status

#### **`api/add_device.php`**
- Voegt nieuw device toe
- Check subscription limits
- Genereert device fingerprint

#### **`api/get_device_by_ip.php`**
- **Input:** `ip` (VPN client IP, bijv. `10.10.0.12`)
- **Output:** `{"found": true, "device_id": 123}`
- **Gebruikt door:** DNS server om device_id te vinden

### **WireGuard Config API**

#### **`api/get_wireguard_config.php`**
- Genereert WireGuard `.conf` bestand
- **Instellingen:**
  - `AllowedIPs = 0.0.0.0/0` (full-tunnel)
  - `DNS = 10.10.0.1` (VPN DNS server)
  - `PersistentKeepalive = 25`
- **Toegang:** Token-based of authenticated

---

## 🗄️ DATABASE STRUCTUUR

### **Tabellen**

#### **`users`**
```sql
- id (INT, PRIMARY KEY)
- email (VARCHAR, UNIQUE)
- password_hash (VARCHAR)
- is_admin (TINYINT)
- created_at (DATETIME)
```

#### **`devices`**
```sql
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY → users.id)
- name (VARCHAR)
- fingerprint (VARCHAR, UNIQUE)
- vpn_ip (VARCHAR) -- VPN client IP (10.10.0.x)
- status (ENUM: 'active', 'inactive')
- created_at (DATETIME)
```

#### **`whitelist`**
```sql
- id (INT, PRIMARY KEY)
- device_id (INT, FOREIGN KEY → devices.id)
- domain (VARCHAR)
- enabled (TINYINT)
- created_at (DATETIME)
```

#### **`subscriptions`**
```sql
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY → users.id)
- plan_id (INT)
- status (ENUM: 'active', 'expired', 'cancelled')
- expires_at (DATETIME)
- created_at (DATETIME)
```

#### **`subscription_plans`**
```sql
- id (INT, PRIMARY KEY)
- name (VARCHAR)
- max_devices (INT)
- price (DECIMAL)
```

---

## 🐍 DNS SERVER (PYTHON)

### **Bestand:** `dns_whitelist_server.py`

### **Hoe Het Werkt**

#### **1. DNS Query Ontvangst**
```
Client vraagt: "google.com"
DNS Server ontvangt query op poort 53
```

#### **2. Client IP Detectie**
```python
client_ip = client_addr[0]  # Bijv. "10.10.0.12"
```

#### **3. Device ID Ophalen**
```python
device_id = get_device_id_from_ip(client_ip)
# API call: GET /api/get_device_by_ip.php?ip=10.10.0.12
# Response: {"found": true, "device_id": 123}
```

#### **4. Whitelist Ophalen**
```python
whitelist = get_whitelist_for_device(device_id)
# API call: GET /api/get_whitelist.php?device_id=123
# Response: ["google.com", "wikipedia.org"]
```

#### **5. Domain Check**
```python
if domain in whitelist:
    # RESOLVE (forward naar 8.8.8.8)
    return resolve_domain_upstream(query_data, domain)
else:
    # BLOCK (return NXDOMAIN)
    return create_nxdomain_response(query_data, query_id)
```

### **Caching**
- Whitelist wordt gecached per device (15 seconden)
- Vermindert API calls
- Snellere response tijd

### **Pornografische Content Blokkering**
```python
# PERMANENT BLOCK: Pornografische domeinen zijn ALTIJD geblokkeerd
if is_pornographic_domain(domain):
    return create_nxdomain_response(query_data, query_id)
```

### **Fail-Safe Logica**
- Als API faalt → return NXDOMAIN (block alles)
- Als device_id niet gevonden → return NXDOMAIN
- Als whitelist leeg → return NXDOMAIN

---

## 🔐 VPN/WIREGUARD INTEGRATIE

### **WireGuard Configuratie**

#### **Full-Tunnel Setup**
```
[Interface]
PrivateKey = ...
Address = 10.10.0.x/24
DNS = 10.10.0.1

[Peer]
PublicKey = ...
Endpoint = vpn.server.com:51820
AllowedIPs = 0.0.0.0/0  ← ALLE traffic via VPN
PersistentKeepalive = 25
```

**Belangrijk:**
- `AllowedIPs = 0.0.0.0/0` = ALLE internet traffic via VPN
- `DNS = 10.10.0.1` = Gebruik VPN DNS server
- Geen directe internet toegang mogelijk

### **VPN Server Firewall**

#### **DNS Forcing**
```bash
# Blokkeer alle DNS queries NIET naar 10.10.0.1
iptables -A FORWARD -s 10.10.0.0/24 -p udp --dport 53 ! -d 10.10.0.1 -j DROP
```

#### **QUIC Blocking**
```bash
# Blokkeer QUIC (UDP 443) - voorkomt video leaks
iptables -A FORWARD -s 10.10.0.0/24 -p udp --dport 443 -j DROP
```

#### **DoT Blocking**
```bash
# Blokkeer DNS-over-TLS (TCP 853)
iptables -A FORWARD -s 10.10.0.0/24 -p tcp --dport 853 -j DROP
```

#### **Kill-Switch**
```bash
# Blokkeer alle traffic NIET via VPN interface
iptables -A FORWARD -s 10.10.0.0/24 ! -o wg0 -j DROP
```

---

## ✅ WHITELIST-ONLY FILTERING

### **Principe**

**ALLEEN** domeinen in de whitelist kunnen worden opgelost. Alles anders krijgt `NXDOMAIN`.

### **Data Flow**

```
1. Gebruiker bezoekt website
   ↓
2. Browser vraagt DNS: "example.com"
   ↓
3. DNS query gaat naar VPN DNS server (10.10.0.1)
   ↓
4. DNS server checkt whitelist
   ↓
5a. In whitelist → RESOLVE (return IP)
5b. NIET in whitelist → NXDOMAIN (geen IP)
   ↓
6. Browser kan website laden OF krijgt "domain not found"
```

### **Lege Whitelist = Geen Internet**

Als whitelist leeg is:
- API retourneert: `[]`
- DNS server krijgt lege array
- ALLE queries krijgen NXDOMAIN
- Geen enkele website kan worden geladen

### **Whitelist Toevoegen**

1. Gebruiker logt in op website
2. Selecteert device
3. Voegt domein toe via interface
4. Frontend stuurt `POST /api/add_whitelist.php`
5. Backend valideert en slaat op in database
6. DNS server haalt nieuwe whitelist op (binnen 15 seconden)
7. Domein kan nu worden opgelost

---

## 🚫 PORNOGRAFISCHE CONTENT BLOKKERING

### **Multi-Layer Blokkering**

#### **Layer 1: API (`api/add_whitelist.php`)**
```php
if (is_pornographic_domain($domain)) {
    json_out(['message' => 'Pornografische domeinen kunnen niet worden toegevoegd'], 403);
}
```

#### **Layer 2: Whitelist API (`api/get_whitelist.php`)**
```php
// Filter pornografische domeinen uit whitelist
foreach ($domains as $domain) {
    if (!is_pornographic_domain($domain)) {
        $filtered_domains[] = $domain;
    }
}
```

#### **Layer 3: DNS Server (`dns_whitelist_server.py`)**
```python
# PERMANENT BLOCK: Pornografische domeinen zijn ALTIJD geblokkeerd
if is_pornographic_domain(domain):
    return create_nxdomain_response(query_data, query_id)
```

### **Pornografische Domain Detectie**

#### **Patterns**
- `porn`, `xxx`, `sex`, `adult`, `nsfw`
- Video CDNs: `phncdn`, `xvcdn`, `xhamsterlive`
- TLDs: `.xxx`, `.adult`, `.sex`, `.porn`

#### **Configuratie**
- **Bestand:** `config_porn_block.php`
- **Functie:** `is_pornographic_domain($domain)`
- **Gebruikt door:** API en DNS server

### **Firewall Rules**

#### **QUIC Blocking**
- Blokkeert UDP 443 (QUIC protocol)
- Voorkomt video/thumbnail leaks
- Script: `block_quic_udp443.sh`

#### **DoT Blocking**
- Blokkeert TCP 853 (DNS-over-TLS)
- Voorkomt DNS bypass
- Script: `block_dot_tcp853.sh`

---

## 🔒 SECURITY FEATURES

### **Authenticatie & Autoriteit**

#### **JWT Tokens**
- Tokens gegenereerd bij login
- Opgeslagen in `localStorage`
- Verlopen na bepaalde tijd
- Automatische logout bij 401

#### **Password Hashing**
- `password_hash()` met `PASSWORD_DEFAULT`
- `password_verify()` voor verificatie
- Geen plaintext wachtwoorden

#### **Brute Force Protection**
- Rate limiting op login attempts
- Blokkeert na X mislukte pogingen
- 15 minuten timeout

### **Input Validatie**

#### **Email Validatie**
```php
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(['message' => 'Ongeldig email adres'], 422);
}
```

#### **Domain Validatie**
```php
function normalize_domain($domain) {
    $domain = strtolower(trim($domain));
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = explode('/', $domain)[0];
    $domain = ltrim($domain, 'www.');
    return $domain;
}
```

### **SQL Injection Prevention**
- Prepared statements gebruikt overal
- `bind_param()` voor parameters
- Geen directe SQL queries met user input

### **CORS Configuratie**
```php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
```

### **Security Headers**
```php
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Strict-Transport-Security: max-age=31536000");
```

---

## 📊 DATA FLOW DIAGRAM

### **Login Flow**
```
User → Frontend (login form)
  ↓
POST /api/login.php (email, password)
  ↓
Backend validates → Database check
  ↓
JWT token generated
  ↓
Token returned to frontend
  ↓
Token stored in localStorage
  ↓
User redirected to dashboard
```

### **Whitelist Add Flow**
```
User → Frontend (add domain form)
  ↓
POST /api/add_whitelist.php (device_id, domain)
  ↓
Backend validates domain
  ↓
Check pornographic domain → BLOCK if porn
  ↓
Save to database (whitelist table)
  ↓
Return success
  ↓
Frontend refreshes whitelist
```

### **DNS Resolution Flow**
```
Browser → DNS query: "google.com"
  ↓
Query naar VPN DNS server (10.10.0.1:53)
  ↓
DNS server detecteert client IP (10.10.0.12)
  ↓
API call: GET /api/get_device_by_ip.php?ip=10.10.0.12
  ↓
Device ID gevonden: 123
  ↓
API call: GET /api/get_whitelist.php?device_id=123
  ↓
Whitelist: ["google.com", "wikipedia.org"]
  ↓
Check: "google.com" in whitelist? → YES
  ↓
Resolve via upstream DNS (8.8.8.8)
  ↓
Return IP address to browser
  ↓
Browser kan website laden
```

### **Blocked Domain Flow**
```
Browser → DNS query: "pornsite.com"
  ↓
Query naar VPN DNS server (10.10.0.1:53)
  ↓
DNS server detecteert client IP
  ↓
Device ID gevonden
  ↓
Whitelist opgehaald
  ↓
Check: "pornsite.com" in whitelist? → NO
  ↓
Check: is_pornographic_domain("pornsite.com")? → YES
  ↓
Return NXDOMAIN
  ↓
Browser krijgt "domain not found"
  ↓
Website kan NIET worden geladen
```

---

## 🔌 API ENDPOINTS

### **Authenticatie**
- `POST /api/login.php` - Login
- `GET /api/me.php` - Huidige gebruiker info
- `POST /api/register.php` - Registratie

### **Devices**
- `GET /api/get_devices.php` - Alle devices
- `POST /api/add_device.php` - Device toevoegen
- `DELETE /api/delete_device.php` - Device verwijderen
- `GET /api/get_device_by_ip.php?ip=X` - Device vinden via IP

### **Whitelist**
- `GET /api/get_whitelist.php?device_id=X` - Whitelist ophalen
- `POST /api/add_whitelist.php` - Domein toevoegen
- `DELETE /api/delete_whitelist.php?id=X` - Domein verwijderen

### **WireGuard**
- `GET /api/get_wireguard_config.php?device_id=X` - Config downloaden
- `POST /api/validate_wireguard_config.php` - Config valideren

### **Admin**
- `GET /api/admin_check.php` - Admin status check
- `GET /api/admin_stats.php` - Statistieken
- `GET /api/admin_users.php` - Gebruikers overzicht
- `GET /api/admin_devices.php` - Devices overzicht

### **Subscription**
- `GET /api/get_subscription.php` - Huidige subscription
- `POST /api/stripe_create_checkout.php` - Stripe checkout

---

## 🛠️ TROUBLESHOOTING

### **DNS Server Start Niet**
```bash
# Check of poort 53 beschikbaar is
sudo lsof -i :53

# Check Python
python3 --version

# Check requests library
python3 -c "import requests"

# Start handmatig
sudo python3 dns_whitelist_server.py
```

### **Whitelist Werkt Niet**
1. Check device status (moet "active" zijn)
2. Check whitelist in database
3. Check DNS server logs: `logs/dns_server.log`
4. Test DNS: `dig @127.0.0.1 google.com`

### **Login Werkt Niet**
1. Check database connectie
2. Check email/wachtwoord
3. Check browser console voor errors
4. Check API logs: `logs/error.log`

### **VPN Connectie Problemen**
1. Check WireGuard config (`AllowedIPs = 0.0.0.0/0`)
2. Check DNS setting (`DNS = 10.10.0.1`)
3. Check VPN server firewall rules
4. Test DNS: `dig @10.10.0.1 google.com`

---

## 📚 BELANGRIJKE BESTANDEN

### **Frontend**
- `index.html` - Hoofdpagina
- `app.js` - Frontend logica
- `admin/index.html` - Admin panel
- `admin/admin.js` - Admin logica
- `subscribe.html` - Registratie pagina

### **Backend**
- `config.php` - Configuratie
- `api/login.php` - Login endpoint
- `api/get_whitelist.php` - Whitelist API
- `api/add_whitelist.php` - Whitelist toevoegen
- `api/get_devices.php` - Devices API

### **DNS Server**
- `dns_whitelist_server.py` - DNS server
- `config_porn_block.php` - Porn blocking config

### **VPN/Firewall**
- `vpn_firewall_setup.sh` - Firewall setup
- `vpn_firewall_app_blocking.sh` - App blocking
- `block_quic_udp443.sh` - QUIC blocking
- `block_dot_tcp853.sh` - DoT blocking

---

## ✅ CONCLUSIE

Het systeem werkt als volgt:

1. **Gebruiker logt in** → krijgt JWT token
2. **Gebruiker voegt domeinen toe** → opgeslagen in database
3. **Gebruiker verbindt VPN** → alle traffic via VPN
4. **Browser vraagt DNS** → DNS server checkt whitelist
5. **Domein in whitelist** → website kan worden geladen
6. **Domein NIET in whitelist** → NXDOMAIN (geen toegang)
7. **Pornografische domeinen** → ALTIJD geblokkeerd (3 lagen)

**Resultaat:** Alleen toegestane domeinen kunnen worden bezocht. Alles anders is geblokkeerd.

---

## 📞 HULP NODIG?

- Check logs: `logs/dns_server.log`, `logs/error.log`
- Test API: `curl http://localhost/44/api/get_whitelist.php?device_id=1`
- Test DNS: `dig @127.0.0.1 google.com`
- Check database: `SELECT * FROM whitelist WHERE device_id=1`
