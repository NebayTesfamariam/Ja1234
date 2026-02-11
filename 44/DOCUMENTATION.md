# 📚 COMPLETE SYSTEM DOCUMENTATION

## 🛡️ Porn-Free Internet Platform

**Version:** 1.0  
**Last Update:** 2026-01-29  
**Author:** Nebay  
**Platform:** macOS / Windows / Linux

---

## 📋 TABLE OF CONTENTS

1. [Introduction](#introduction)
2. [System Overview](#system-overview)
3. [Architecture](#architecture)
4. [Installation & Setup](#installation--setup)
5. [User Guide](#user-guide)
6. [Admin Guide](#admin-guide)
7. [Technical Documentation](#technical-documentation)
8. [API Documentation](#api-documentation)
9. [Troubleshooting](#troubleshooting)
10. [FAQ](#faq)

---

## 🎯 INTRODUCTION

### What is this?

The **Porn-Free Internet Platform** is a fully automatic system that provides **100% blocking** of pornographic content on all devices. It works at the **network level** via VPN and DNS filtering, not just in the browser.

### Core Principle

**Whitelist-Only Filtering:**
- Everything is **BLOCKED** by default
- Only explicitly allowed domains work
- Pornographic sites are **ALWAYS** blocked
- Empty whitelist = no internet access

### Why Whitelist-Only?

- ✅ **100% reliable** - No bypass possible
- ✅ **No false positives** - Only allowed sites work
- ✅ **Easy to manage** - Clear list of allowed sites
- ✅ **Unbypassable** - Works at network level

---

## 🏗️ SYSTEM OVERVIEW

### Components

```
┌─────────────────────────────────────────┐
│         USER DEVICE                     │
│  (iPhone, Laptop, Tablet, etc.)        │
└────────────────┬────────────────────────┘
                 │
                 │ WireGuard VPN
                 │ (Full-tunnel)
                 │
┌────────────────▼────────────────────────┐
│         VPN SERVER                       │
│  • WireGuard Server                      │
│  • DNS Server (10.10.0.1)               │
│  • Firewall Rules                        │
└────────────────┬────────────────────────┘
                 │
                 │ API Calls
                 │
┌────────────────▼────────────────────────┐
│         WEBSITE SERVER                   │
│  • Apache (Port 80)                      │
│  • PHP Backend                           │
│  • MySQL Database                        │
└────────────────┬────────────────────────┘
                 │
                 │ SQL Queries
                 │
┌────────────────▼────────────────────────┐
│         DATABASE                         │
│  • users                                 │
│  • devices                               │
│  • whitelist                             │
│  • subscriptions                         │
└─────────────────────────────────────────┘
```

### Data Flow

1. **User** → Registers → Account created
2. **User** → Logs in → Device registered
3. **User** → Connects VPN → All traffic via VPN
4. **User** → Adds domains → Saved in whitelist
5. **Browser** → Requests DNS → DNS server checks whitelist
6. **DNS Server** → Domain in whitelist? → Resolve or NXDOMAIN
7. **Browser** → Gets IP or "Domain not found"

---

## 🔧 ARCHITECTURE

### Frontend

**Technology:** HTML5, JavaScript (Vanilla), CSS3

**Files:**
- `index.html` - Landing page
- `public/index.html` - User dashboard
- `admin/index.html` - Admin panel
- `subscribe.html` - Registration page
- `app.js` - Frontend logic
- `admin/admin.js` - Admin logic

**Features:**
- Responsive design
- Real-time updates
- JWT token authentication
- Automatic device registration
- WireGuard config download

### Backend

**Technology:** PHP 7.4+, MySQL/MariaDB

**Structure:**
```
/api/
  ├── login.php              # Authentication
  ├── register.php           # Registration
  ├── get_devices.php        # Get devices
  ├── get_whitelist.php      # Get whitelist
  ├── add_whitelist.php      # Add domain
  ├── delete_whitelist.php   # Delete domain
  ├── get_wireguard_config.php  # VPN config
  └── ... (92 endpoints total)
```

**Security:**
- JWT token authentication
- Password hashing (bcrypt)
- Brute force protection
- SQL injection prevention
- CORS configuration
- Input validation

### Database

**MySQL/MariaDB Schema:**

```sql
-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Devices table
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

-- Whitelist table
CREATE TABLE whitelist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id INT NOT NULL,
    domain VARCHAR(255) NOT NULL,
    enabled TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id),
    UNIQUE KEY unique_device_domain (device_id, domain)
);

-- Subscriptions table
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

**Technology:** Python 3.7+

**File:** `dns_whitelist_server.py`

**Functionality:**
- Listens on port 53 (requires root)
- Receives DNS queries from VPN clients
- Detects client IP (10.10.0.x)
- Requests device_id via API
- Requests whitelist via API
- Checks if domain is in whitelist
- Resolve or NXDOMAIN

**Pornographic Blocking:**
- Always checks if domain is pornographic
- Always returns NXDOMAIN for pornographic domains
- Even if domain accidentally in whitelist

---

## 🚀 INSTALLATION & SETUP

### Requirements

**Server:**
- macOS / Linux / Windows
- Apache 2.4+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Python 3.7+
- WireGuard VPN Server

**Client:**
- WireGuard app (iOS/Android/Desktop)
- Modern browser

### Installation Steps

#### 1. Download & Extract

```bash
# Download project
cd /Applications/XAMPP/xamppfiles/htdocs/44
```

#### 2. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE pornfree;
USE pornfree;

# Import schema
mysql -u root -p pornfree < setup_database.php
```

#### 3. Configuration

**`config.php`** adjust:
```php
// Development
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "pornfree";
```

**`config_production.php`** for production:
```php
define('PROD_DB_HOST', 'localhost');
define('PROD_DB_USER', 'username');
define('PROD_DB_PASS', 'password');
define('PROD_DB_NAME', 'database');
```

#### 4. DNS Server Setup

```bash
# Install Python dependencies
pip3 install requests

# Start DNS server (requires sudo)
sudo python3 dns_whitelist_server.py
```

#### 5. Start Services

**macOS/Linux:**
```bash
./FIX_EVERYTHING.command
```

**Windows:**
```bat
FIX_EVERYTHING.bat
```

---

## 👤 USER GUIDE

### Registration

1. Go to: `http://localhost/44/subscribe.html`
2. Choose subscription plan
3. Enter email + password
4. Click "Subscribe"
5. WireGuard config automatically downloaded

### Login

1. Go to: `http://localhost/44/public/index.html`
2. Enter email + password
3. Click "Login"
4. Device automatically registered

### Whitelist Management

1. Select device in dashboard
2. Add domain (e.g., "wikipedia.org")
3. Click "Add"
4. Domain saved in whitelist
5. Website can now be visited

### WireGuard Config Installation

**iOS:**
1. Open WireGuard app
2. Tap "+" → "Create from file or archive"
3. Select downloaded .conf file
4. Tap "Add"
5. Activate VPN

**Android:**
1. Open WireGuard app
2. Tap "+" → "Create from file"
3. Select downloaded .conf file
4. Tap "Add"
5. Activate VPN

**Desktop:**
1. Open WireGuard app
2. Import .conf file
3. Activate VPN

---

## 👨‍💼 ADMIN GUIDE

### Admin Login

1. Go to: `http://localhost/44/admin/index.html`
2. Login with admin account
3. Admin dashboard displayed

### User Management

**User Overview:**
- See all registered users
- Filter by status
- View user details

**Add User:**
- Manually create user
- Assign subscription
- Add device

### Device Management

**Device Overview:**
- See all devices from all users
- Filter by user
- View device status
- View VPN IP

**Generate Device Link:**
- Generate registration link
- Share link with user
- User can register device via link

### Statistics

**Dashboard Statistics:**
- Total users
- Total devices
- Active subscriptions
- Whitelist entries

### Database Backups

**Create Backup:**
1. Go to Admin → Backups
2. Click "Backup Database"
3. Download backup file

**Restore Backup:**
1. Upload backup file
2. Click "Restore"
3. Confirm restore

---

## 🔧 TECHNICAL DOCUMENTATION

### Authentication System

**JWT Tokens:**
- Tokens generated on login
- Stored in `localStorage` (frontend)
- Sent in `Authorization: Bearer TOKEN` header
- Expire after certain time
- Automatic logout on 401

**Password Hashing:**
- Uses `password_hash()` with `PASSWORD_DEFAULT`
- Verification with `password_verify()`
- No plaintext passwords

**Brute Force Protection:**
- Rate limiting on login attempts
- Blocks after X failed attempts
- 15 minute timeout

### Whitelist System

**Whitelist Format:**
```json
["wikipedia.org", "google.com", "github.com"]
```

**Empty Whitelist:**
```json
[]
```
= No internet access

**Add Whitelist:**
- Domain normalized (lowercase, no www)
- Pornographic domains blocked
- Duplicate check
- Device ownership check

**Get Whitelist:**
- Cached for 10 seconds
- Filters pornographic domains
- Returns only enabled entries

### DNS Filtering

**DNS Query Flow:**
1. Client requests DNS: "wikipedia.org"
2. Query goes to VPN DNS server (10.10.0.1:53)
3. DNS server detects client IP (10.10.0.12)
4. DNS server requests device_id via API
5. DNS server requests whitelist via API
6. DNS server checks if domain in whitelist
7. Resolve (via 8.8.8.8) or NXDOMAIN

**Caching:**
- Whitelist cached per device (15 seconds)
- Reduces API calls
- Faster response time

**Fail-Safe:**
- If API fails → NXDOMAIN (block everything)
- If device_id not found → NXDOMAIN
- If whitelist empty → NXDOMAIN

### Pornographic Blocking

**Multi-Layer Security:**

**Layer 1: API Blocking**
- `api/add_whitelist.php` blocks adding
- Returns 403 Forbidden
- Error message: "Pornographic domain detected"

**Layer 2: Whitelist Filtering**
- `api/get_whitelist.php` filters pornographic domains
- Even if in database → not in whitelist

**Layer 3: DNS Blocking**
- `dns_whitelist_server.py` always blocks
- Always returns NXDOMAIN
- Even if in whitelist

**Layer 4: Automatic Cleanup**
- `api/cleanup_porn_domains.php` automatically removes
- Runs every 5 minutes
- Removes pornographic domains from database

**Pornographic Detection:**
- Pattern matching (porn, xxx, sex, etc.)
- TLD checking (.xxx, .adult, .sex, .porn)
- Multi-language (Dutch, English, German, French, Spanish)
- Known pornographic sites

---

## 📡 API DOCUMENTATION

### Authentication

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

### Problem: ERR_CONNECTION_REFUSED

**Cause:** Apache not running

**Solution:**
```bash
sudo /Applications/XAMPP/xamppfiles/bin/httpd -k start
```

Or double-click: `START_APACHE_NOW.command`

### Problem: Database Connection Failed

**Cause:** MySQL not running

**Solution:**
```bash
sudo /Applications/XAMPP/xamppfiles/bin/mysql.server start
```

### Problem: DNS Server Won't Start

**Cause:** Port 53 requires root, or Python requests not installed

**Solution:**
```bash
# Install requests
pip3 install requests

# Start DNS server
sudo python3 dns_whitelist_server.py
```

Or double-click: `START_DNS.command`

### Problem: Pornographic Sites Can Be Loaded

**Cause:** DNS server not running, or VPN not connected

**Solution:**
1. Check if DNS server running: `pgrep -f dns_whitelist_server`
2. Check if VPN connected
3. Check WireGuard config: `DNS = 10.10.0.1`
4. Start DNS server: `sudo python3 dns_whitelist_server.py`

### Problem: Website Won't Load (Despite Whitelist)

**Cause:** Domain not correctly normalized, or not in whitelist

**Solution:**
1. Check whitelist in dashboard
2. Check if domain correct (no www, no http://)
3. Check device status (must be "active")
4. Check DNS server logs: `logs/dns_server.log`

### Problem: Login Doesn't Work

**Cause:** Database connection failed, or wrong credentials

**Solution:**
1. Check database connection
2. Check email/password
3. Check browser console for errors
4. Check API logs: `logs/error.log`

---

## ❓ FAQ

### Q: How does whitelist-only filtering work?

**A:** Everything is blocked by default. Only domains in the whitelist can be resolved. If a domain is not in the whitelist, the browser gets "Domain not found" (NXDOMAIN).

### Q: Can I add pornographic sites to whitelist?

**A:** No. Pornographic domains are blocked on 4 layers:
1. API blocks adding
2. Whitelist API filters them out
3. DNS server always blocks them
4. Automatic cleanup removes them

### Q: What happens if whitelist is empty?

**A:** No internet access. All DNS queries get NXDOMAIN. This is the desired behavior for maximum security.

### Q: Does it work on all devices?

**A:** Yes, as long as WireGuard VPN is installed and active. It works on:
- iPhone/iPad
- Android phones/tablets
- Windows laptops
- macOS laptops
- Linux computers

### Q: Can I bypass the system?

**A:** No. It works at network level via VPN. As long as VPN is active, all internet traffic is filtered. There is no way to bypass it without disabling VPN.

### Q: How do I start everything automatically on boot?

**A:** 
- **macOS:** Use `install_dns_launchdaemon.sh` for DNS server
- **Windows:** Put `start_pornfree_system.bat` in Startup folder
- **Linux:** Use systemd service files

### Q: What if I accidentally block a domain?

**A:** Add the domain to the whitelist via the dashboard. It becomes active immediately (within 15 seconds due to DNS cache).

### Q: How do I test if porn blocking works?

**A:** Try visiting a pornographic site (e.g., pornhub.com). You should get "Domain not found".

---

## 📞 SUPPORT

### View Logs

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

### Version 1.0 (2026-01-29)
- ✅ Whitelist-only filtering implemented
- ✅ Multi-layer pornographic blocking
- ✅ Automatic device registration
- ✅ WireGuard config automatic download
- ✅ Admin panel with full functionality
- ✅ 92 API endpoints
- ✅ Complete documentation

---

## 📄 LICENSE

This project is developed for personal use.

---

**Last Update:** 2026-01-29  
**Version:** 1.0
