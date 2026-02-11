# 📚 COMPLETE SYSTEEM DOCUMENTATIE

## 🛡️ Porno-vrij Internet Platform

**Versie:** 1.0  
**Laatste Update:** 2026-01-29  
**Auteur:** Nebay  
**Platform:** macOS / Windows / Linux

---

## 📋 INHOUDSOPGAVE

1. [Inleiding](#inleiding)
2. [Systeem Overzicht](#systeem-overzicht)
3. [Architectuur](#architectuur)
4. [Installatie & Setup](#installatie--setup)
5. [Gebruikershandleiding](#gebruikershandleiding)
6. [Admin Handleiding](#admin-handleiding)
7. [Technische Documentatie](#technische-documentatie)
8. [API Documentatie](#api-documentatie)
9. [Troubleshooting](#troubleshooting)
10. [FAQ](#faq)

---

## 🎯 INLEIDING

### Wat is dit?

Het **Porno-vrij Internet Platform** is een volledig automatisch systeem dat **100% blokkering** van pornografische content biedt op alle devices. Het werkt op **netwerkniveau** via VPN en DNS filtering, niet alleen in de browser.

### Kernprincipe

**Whitelist-Only Filtering:**
- Alles is standaard **GEBLOKKEERD**
- Alleen expliciet toegestane domeinen werken
- Pornografische sites zijn **ALTIJD** geblokkeerd
- Lege whitelist = geen internet toegang

### Waarom Whitelist-Only?

- ✅ **100% betrouwbaar** - Geen bypass mogelijk
- ✅ **Geen false positives** - Alleen toegestane sites werken
- ✅ **Eenvoudig te beheren** - Duidelijke lijst van toegestane sites
- ✅ **Onomzeilbaar** - Werkt op netwerkniveau

---

## 🏗️ SYSTEEM OVERZICHT

### Componenten

```
┌─────────────────────────────────────────┐
│         GEBRUIKER DEVICE                │
│  (iPhone, Laptop, Tablet, etc.)        │
└────────────────┬────────────────────────┘
                 │
                 │ WireGuard VPN
                 │ (Full-tunnel)
                 │
┌────────────────▼────────────────────────┐
│         VPN SERVER                      │
│  • WireGuard Server                     │
│  • DNS Server (10.10.0.1)               │
│  • Firewall Rules                       │
└────────────────┬────────────────────────┘
                 │
                 │ API Calls
                 │
┌────────────────▼────────────────────────┐
│         WEBSITE SERVER                  │
│  • Apache (Port 80)                     │
│  • PHP Backend                          │
│  • MySQL Database                       │
└────────────────┬────────────────────────┘
                 │
                 │ SQL Queries
                 │
┌────────────────▼────────────────────────┐
│         DATABASE                        │
│  • users                                │
│  • devices                              │
│  • whitelist                            │
│  • subscriptions                        │
└─────────────────────────────────────────┘
```

### Data Flow

1. **Gebruiker** → Registreert → Account aangemaakt
2. **Gebruiker** → Logt in → Device geregistreerd
3. **Gebruiker** → Verbindt VPN → Alle traffic via VPN
4. **Gebruiker** → Voegt domeinen toe → Opgeslagen in whitelist
5. **Browser** → Vraagt DNS → DNS server checkt whitelist
6. **DNS Server** → Domein in whitelist? → Resolve of NXDOMAIN
7. **Browser** → Krijgt IP of "Domain not found"

---

## 🔧 ARCHITECTUUR

### Frontend

**Technologie:** HTML5, JavaScript (Vanilla), CSS3

**Bestanden:**
- `index.html` - Landingpagina
- `public/index.html` - Gebruikersdashboard
- `admin/index.html` - Admin panel
- `subscribe.html` - Registratiepagina
- `app.js` - Frontend logica
- `admin/admin.js` - Admin logica

**Features:**
- Responsive design
- Real-time updates
- JWT token authenticatie
- Automatische device registratie
- WireGuard config download

### Backend

**Technologie:** PHP 7.4+, MySQL/MariaDB

**Structuur:**
```
/api/
  ├── login.php              # Authenticatie
  ├── register.php           # Registratie
  ├── get_devices.php        # Devices ophalen
  ├── get_whitelist.php      # Whitelist ophalen
  ├── add_whitelist.php      # Domein toevoegen
  ├── delete_whitelist.php   # Domein verwijderen
  ├── get_wireguard_config.php  # VPN config
  └── ... (92 endpoints totaal)
```

**Security:**
- JWT token authenticatie
- Password hashing (bcrypt)
- Brute force protection
- SQL injection prevention
- CORS configuratie
- Input validatie

### Database

**MySQL/MariaDB Schema:**

```sql
-- Users tabel
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Devices tabel
CREATE TABLE devices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    wg_ip VARCHAR(15) UNIQUE NOT NULL,  -- VPN IP (10.10.0.x)
    wg_public_key VARCHAR(255) UNIQUE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Whitelist tabel
CREATE TABLE whitelist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id INT NOT NULL,
    domain VARCHAR(255) NOT NULL,
    enabled TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id),
    UNIQUE KEY unique_device_domain (device_id, domain)
);

-- Subscriptions tabel
CREATE TABLE subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### DNS Server

**Technologie:** Python 3.7+

**Bestand:** `dns_whitelist_server.py`

**Functionaliteit:**
- Luistert op poort 53 (vereist root)
- Ontvangt DNS queries van VPN clients
- Detecteert client IP (10.10.0.x)
- Vraagt device_id via API
- Vraagt whitelist via API
- Checkt of domein in whitelist staat
- Resolve of NXDOMAIN

**Pornografische Blokkering:**
- Checkt altijd of domein pornografisch is
- Retourneert altijd NXDOMAIN voor pornografische domeinen
- Zelfs als domein per ongeluk in whitelist staat

---

## 🚀 INSTALLATIE & SETUP

### Vereisten

**Server:**
- macOS / Linux / Windows
- Apache 2.4+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Python 3.7+
- WireGuard VPN Server

**Client:**
- WireGuard app (iOS/Android/Desktop)
- Moderne browser

### Installatie Stappen

#### 1. Download & Extract

```bash
# Download project
cd /Applications/XAMPP/xamppfiles/htdocs/44
```

#### 2. Database Setup

```bash
# Maak database aan
mysql -u root -p
CREATE DATABASE pornfree;
USE pornfree;

# Importeer schema
mysql -u root -p pornfree < setup_database.php
```

#### 3. Configuratie

**`config.php`** aanpassen:
```php
// Development
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "pornfree";
```

**`config_production.php`** voor productie:
```php
define('PROD_DB_HOST', 'localhost');
define('PROD_DB_USER', 'username');
define('PROD_DB_PASS', 'password');
define('PROD_DB_NAME', 'database');
```

#### 4. DNS Server Setup

```bash
# Installeer Python dependencies
pip3 install requests

# Start DNS server (vereist sudo)
sudo python3 dns_whitelist_server.py
```

#### 5. Services Starten

**macOS/Linux:**
```bash
./FIX_EVERYTHING.command
```

**Windows:**
```bat
FIX_EVERYTHING.bat
```

---

## 👤 GEBRUIKERSHANDLEIDING

### Registratie

1. Ga naar: `http://localhost/44/subscribe.html`
2. Kies abonnement plan
3. Vul email + wachtwoord in
4. Klik "Abonnement Aansluiten"
5. WireGuard config wordt automatisch gedownload

### Login

1. Ga naar: `http://localhost/44/public/index.html`
2. Vul email + wachtwoord in
3. Klik "Inloggen"
4. Device wordt automatisch geregistreerd

### Whitelist Beheer

1. Selecteer device in dashboard
2. Voeg domein toe (bijv. "wikipedia.org")
3. Klik "Toevoegen"
4. Domein wordt opgeslagen in whitelist
5. Website kan nu worden bezocht

### WireGuard Config Installeren

**iOS:**
1. Open WireGuard app
2. Tap "+" → "Create from file or archive"
3. Selecteer gedownloade .conf file
4. Tap "Add"
5. Activeer VPN

**Android:**
1. Open WireGuard app
2. Tap "+" → "Create from file"
3. Selecteer gedownloade .conf file
4. Tap "Add"
5. Activeer VPN

**Desktop:**
1. Open WireGuard app
2. Importeer .conf file
3. Activeer VPN

---

## 👨‍💼 ADMIN HANDLEIDING

### Admin Login

1. Ga naar: `http://localhost/44/admin/index.html`
2. Log in met admin account
3. Admin dashboard wordt getoond

### Gebruikersbeheer

**Gebruikers Overzicht:**
- Zie alle geregistreerde gebruikers
- Filter op status
- Bekijk gebruiker details

**Gebruiker Toevoegen:**
- Handmatig gebruiker aanmaken
- Abonnement toewijzen
- Device toevoegen

### Device Beheer

**Devices Overzicht:**
- Zie alle devices van alle gebruikers
- Filter op gebruiker
- Bekijk device status
- Bekijk VPN IP

**Device Link Genereren:**
- Genereer registratie link
- Deel link met gebruiker
- Gebruiker kan device registreren via link

### Statistieken

**Dashboard Statistieken:**
- Totaal aantal gebruikers
- Totaal aantal devices
- Actieve subscriptions
- Whitelist entries

### Database Backups

**Backup Maken:**
1. Ga naar Admin → Backups
2. Klik "Backup Database"
3. Download backup bestand

**Backup Restoren:**
1. Upload backup bestand
2. Klik "Restore"
3. Bevestig restore

---

## 🔧 TECHNISCHE DOCUMENTATIE

### Authenticatie Systeem

**JWT Tokens:**
- Tokens worden gegenereerd bij login
- Opgeslagen in `localStorage` (frontend)
- Verzonden in `Authorization: Bearer TOKEN` header
- Verlopen na bepaalde tijd
- Automatische logout bij 401

**Password Hashing:**
- Gebruikt `password_hash()` met `PASSWORD_DEFAULT`
- Verificatie met `password_verify()`
- Geen plaintext wachtwoorden

**Brute Force Protection:**
- Rate limiting op login attempts
- Blokkeert na X mislukte pogingen
- 15 minuten timeout

### Whitelist Systeem

**Whitelist Format:**
```json
["wikipedia.org", "google.com", "github.com"]
```

**Lege Whitelist:**
```json
[]
```
= Geen internet toegang

**Whitelist Toevoegen:**
- Domein wordt genormaliseerd (lowercase, geen www)
- Pornografische domeinen worden geblokkeerd
- Duplicate check
- Device ownership check

**Whitelist Ophalen:**
- Cache voor 10 seconden
- Filtert pornografische domeinen
- Retourneert alleen enabled entries

### DNS Filtering

**DNS Query Flow:**
1. Client vraagt DNS: "wikipedia.org"
2. Query gaat naar VPN DNS server (10.10.0.1:53)
3. DNS server detecteert client IP (10.10.0.12)
4. DNS server vraagt device_id via API
5. DNS server vraagt whitelist via API
6. DNS server checkt of domein in whitelist staat
7. Resolve (via 8.8.8.8) of NXDOMAIN

**Caching:**
- Whitelist wordt gecached per device (15 seconden)
- Vermindert API calls
- Snellere response tijd

**Fail-Safe:**
- Als API faalt → NXDOMAIN (block alles)
- Als device_id niet gevonden → NXDOMAIN
- Als whitelist leeg → NXDOMAIN

### Pornografische Blokkering

**Multi-Layer Beveiliging:**

**Layer 1: API Blokkering**
- `api/add_whitelist.php` blokkeert toevoegen
- Retourneert 403 Forbidden
- Foutmelding: "Pornografisch domein gedetecteerd"

**Layer 2: Whitelist Filtering**
- `api/get_whitelist.php` filtert pornografische domeinen
- Zelfs als in database → niet in whitelist

**Layer 3: DNS Blokkering**
- `dns_whitelist_server.py` blokkeert altijd
- Retourneert altijd NXDOMAIN
- Zelfs als in whitelist

**Layer 4: Automatische Cleanup**
- `api/cleanup_porn_domains.php` verwijdert automatisch
- Draait elke 5 minuten
- Verwijdert pornografische domeinen uit database

**Pornografische Detectie:**
- Pattern matching (porn, xxx, sex, etc.)
- TLD checking (.xxx, .adult, .sex, .porn)
- Meertalig (Nederlands, Engels, Duits, Frans, Spaans)
- Bekende pornografische sites

---

## 📡 API DOCUMENTATIE

### Authenticatie

**Login:**
```
POST /api/login.php
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

Response:
{
  "token": "jwt_token_here",
  "user": {
    "id": 1,
    "email": "user@example.com"
  }
}
```

**Register:**
```
POST /api/register.php
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "plan_id": 1
}

Response:
{
  "token": "jwt_token_here",
  "user": {
    "id": 1,
    "email": "user@example.com"
  },
  "device": {
    "id": 1,
    "wg_ip": "10.10.0.12"
  }
}
```

### Devices

**Get Devices:**
```
GET /api/get_devices.php
Authorization: Bearer TOKEN

Response:
[
  {
    "id": 1,
    "name": "iPhone",
    "wg_ip": "10.10.0.12",
    "status": "active"
  }
]
```

**Add Device:**
```
POST /api/add_device.php
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "name": "Laptop",
  "wg_public_key": "public_key_here",
  "wg_ip": "10.10.0.13"
}

Response:
{
  "id": 2,
  "name": "Laptop",
  "status": "active"
}
```

### Whitelist

**Get Whitelist:**
```
GET /api/get_whitelist.php?device_id=1
Authorization: Bearer TOKEN

Response:
["wikipedia.org", "google.com", "github.com"]
```

**Add Whitelist:**
```
POST /api/add_whitelist.php
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "device_id": 1,
  "domain": "wikipedia.org"
}

Response:
{
  "status": "added",
  "id": 1
}
```

**Delete Whitelist:**
```
DELETE /api/delete_whitelist.php?id=1
Authorization: Bearer TOKEN

Response:
{
  "status": "deleted"
}
```

### WireGuard

**Get WireGuard Config:**
```
GET /api/get_wireguard_config.php?device_id=1
Authorization: Bearer TOKEN

Response:
Content-Type: application/x-config
[WireGuard config file content]
```

---

## 🛠️ TROUBLESHOOTING

### Probleem: ERR_CONNECTION_REFUSED

**Oorzaak:** Apache draait niet

**Oplossing:**
```bash
sudo /Applications/XAMPP/xamppfiles/bin/httpd -k start
```

Of double-click: `START_APACHE_NOW.command`

### Probleem: Database Verbinding Mislukt

**Oorzaak:** MySQL draait niet

**Oplossing:**
```bash
sudo /Applications/XAMPP/xamppfiles/bin/mysql.server start
```

### Probleem: DNS Server Start Niet

**Oorzaak:** Poort 53 vereist root, of Python requests niet geïnstalleerd

**Oplossing:**
```bash
# Installeer requests
pip3 install requests

# Start DNS server
sudo python3 dns_whitelist_server.py
```

Of double-click: `START_DNS.command`

### Probleem: Pornografische Sites Kunnen Worden Geladen

**Oorzaak:** DNS server draait niet, of VPN niet verbonden

**Oplossing:**
1. Check of DNS server draait: `pgrep -f dns_whitelist_server`
2. Check of VPN verbonden is
3. Check WireGuard config: `DNS = 10.10.0.1`
4. Start DNS server: `sudo python3 dns_whitelist_server.py`

### Probleem: Website Laadt Niet (Ondanks Whitelist)

**Oorzaak:** Domein niet correct genormaliseerd, of niet in whitelist

**Oplossing:**
1. Check whitelist in dashboard
2. Check of domein correct is (geen www, geen http://)
3. Check device status (moet "active" zijn)
4. Check DNS server logs: `logs/dns_server.log`

### Probleem: Login Werkt Niet

**Oorzaak:** Database verbinding mislukt, of verkeerde credentials

**Oplossing:**
1. Check database verbinding
2. Check email/wachtwoord
3. Check browser console voor errors
4. Check API logs: `logs/error.log`

---

## ❓ FAQ

### V: Hoe werkt whitelist-only filtering?

**A:** Alles is standaard geblokkeerd. Alleen domeinen in de whitelist kunnen worden opgelost. Als een domein niet in de whitelist staat, krijgt de browser "Domain not found" (NXDOMAIN).

### V: Kan ik pornografische sites toevoegen aan whitelist?

**A:** Nee. Pornografische domeinen worden op 4 lagen geblokkeerd:
1. API blokkeert toevoegen
2. Whitelist API filtert ze eruit
3. DNS server blokkeert ze altijd
4. Automatische cleanup verwijdert ze

### V: Wat gebeurt er als whitelist leeg is?

**A:** Geen internet toegang. Alle DNS queries krijgen NXDOMAIN. Dit is het gewenste gedrag voor maximale beveiliging.

### V: Werkt het op alle devices?

**A:** Ja, zolang WireGuard VPN is geïnstalleerd en actief is. Het werkt op:
- iPhone/iPad
- Android telefoons/tablets
- Windows laptops
- macOS laptops
- Linux computers

### V: Kan ik het systeem omzeilen?

**A:** Nee. Het werkt op netwerkniveau via VPN. Zolang VPN actief is, is alle internet traffic gefilterd. Er is geen manier om het te omzeilen zonder VPN uit te schakelen.

### V: Hoe start ik alles automatisch bij boot?

**A:** 
- **macOS:** Gebruik `install_dns_launchdaemon.sh` voor DNS server
- **Windows:** Zet `start_pornfree_system.bat` in Startup folder
- **Linux:** Gebruik systemd service files

### V: Wat als ik een domein per ongeluk blokkeer?

**A:** Voeg het domein toe aan de whitelist via het dashboard. Het wordt direct actief (binnen 15 seconden door DNS cache).

### V: Hoe test ik of porn blokkering werkt?

**A:** Probeer een pornografische site te bezoeken (bijv. pornhub.com). Je zou "Domain not found" moeten krijgen.

---

## 📞 SUPPORT

### Logs Bekijken

**Apache Logs:**
```bash
tail -f /Applications/XAMPP/xamppfiles/logs/error_log
```

**MySQL Logs:**
```bash
tail -f /Applications/XAMPP/xamppfiles/logs/mysql_error.log
```

**DNS Server Logs:**
```bash
tail -f /Applications/XAMPP/xamppfiles/htdocs/44/logs/dns_server.log
```

### System Check

**Web Interface:**
```
http://localhost/44/CHECK_WEBSITE.php
```

**API Health Check:**
```
http://localhost/44/api/health.php
```

---

## 📝 CHANGELOG

### Versie 1.0 (2026-01-29)
- ✅ Whitelist-only filtering geïmplementeerd
- ✅ Multi-layer pornografische blokkering
- ✅ Automatische device registratie
- ✅ WireGuard config automatisch download
- ✅ Admin panel met volledige functionaliteit
- ✅ 92 API endpoints
- ✅ Complete documentatie

---

## 📄 LICENTIE

Dit project is ontwikkeld voor persoonlijk gebruik.

---

**Laatste Update:** 2026-01-29  
**Versie:** 1.0
