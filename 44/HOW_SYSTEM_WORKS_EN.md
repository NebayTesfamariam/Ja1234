# 🌐 HOW THE SYSTEM WORKS - STEP BY STEP

## 📋 TABLE OF CONTENTS

1. [Overview](#overview)
2. [Step 1: User Registers](#step-1-user-registers)
3. [Step 2: Device Registration](#step-2-device-registration)
4. [Step 3: VPN Connection](#step-3-vpn-connection)
5. [Step 4: Whitelist Management](#step-4-whitelist-management)
6. [Step 5: DNS Filtering](#step-5-dns-filtering)
7. [Step 6: Porn Blocking](#step-6-porn-blocking)
8. [Complete Flow Diagram](#complete-flow-diagram)

---

## 🎯 OVERVIEW

The system works as follows:

```
User → Website → Database → DNS Server → Internet
                ↓
         Whitelist Check
                ↓
    ✅ Allowed → Website loads
    ❌ Blocked → NXDOMAIN (no access)
```

**Core Principle:** Only domains in the whitelist can be resolved. Everything else is blocked.

---

## 📝 STEP 1: USER REGISTERS

### What Happens?

1. **User goes to website**
   - URL: `http://localhost/44/subscribe.html`
   - Sees subscription plans

2. **User chooses plan and registers**
   - Enters email + password
   - Clicks "Subscribe"

3. **Backend processes registration**
   ```
   Frontend → POST /api/register.php
              ↓
   Backend:
   - Validates email
   - Hashes password (password_hash)
   - Creates user in database (users table)
   - Creates subscription (subscriptions table)
   - Generates JWT token
              ↓
   Frontend receives token
   ```

4. **Automatic Device Registration**
   - On registration, device automatically created
   - Device gets VPN IP (e.g., `10.10.0.12`)
   - Device gets WireGuard public key
   - Device status = "active"

5. **WireGuard Config Download**
   - WireGuard config automatically downloaded
   - Config contains:
     - VPN server endpoint
     - Client IP (10.10.0.x)
     - DNS = 10.10.0.1 (VPN DNS server)
     - AllowedIPs = 0.0.0.0/0 (full-tunnel)

**Result:** User has account + device + VPN config

---

## 📱 STEP 2: DEVICE REGISTRATION

### What Happens?

1. **User logs in**
   - URL: `http://localhost/44/public/index.html`
   - Enters email + password

2. **Backend authentication**
   ```
   Frontend → POST /api/login.php
              ↓
   Backend:
   - Validates email format
   - Checks brute force protection
   - Verifies password (password_verify)
   - Generates JWT token
              ↓
   Frontend receives token + user info
   ```

3. **Automatic Device Detection**
   - Frontend detects device fingerprint
   - Checks if device already exists
   - If new → automatically register
   - If existing → use existing device

4. **Get Device Info**
   ```
   Frontend → GET /api/get_devices.php
              ↓
   Backend:
   - Gets all devices for user
   - Includes subscription info
   - Includes device status
              ↓
   Frontend displays devices
   ```

**Result:** User sees their devices in dashboard

---

## 🔐 STEP 3: VPN CONNECTION

### What Happens?

1. **User installs WireGuard**
   - Downloads WireGuard app (iOS/Android/Desktop)
   - Imports config file (.conf)

2. **VPN Connection Setup**
   ```
   Device → WireGuard App
            ↓
   Connect to VPN Server
            ↓
   VPN Server:
   - Accepts connection
   - Assigns IP: 10.10.0.12
   - Forces DNS: 10.10.0.1
            ↓
   Device gets VPN IP
   ```

3. **Traffic Routing**
   - **All** internet traffic goes via VPN
   - `AllowedIPs = 0.0.0.0/0` = full-tunnel
   - No direct internet access possible
   - DNS queries go to: `10.10.0.1` (VPN DNS server)

**Result:** All internet traffic goes via VPN

---

## 📋 STEP 4: WHITELIST MANAGEMENT

### What Happens?

1. **User adds domain**
   - In dashboard: enters domain (e.g., "wikipedia.org")
   - Clicks "Add"

2. **Backend Validation**
   ```
   Frontend → POST /api/add_whitelist.php
              {
                "device_id": 123,
                "domain": "wikipedia.org"
              }
              ↓
   Backend:
   - Normalizes domain (lowercase, no www)
   - Checks pornographic domain → BLOCK if porn
   - Checks duplicate
   - Checks device ownership
   - Saves to database (whitelist table)
              ↓
   Frontend receives success
   ```

3. **Get Whitelist**
   ```
   Frontend → GET /api/get_whitelist.php?device_id=123
              ↓
   Backend:
   - Gets whitelist from database
   - Filters pornographic domains (even if in whitelist)
   - Returns array: ["wikipedia.org", "google.com"]
              ↓
   Frontend displays whitelist
   ```

**Result:** Domains in whitelist

---

## 🌐 STEP 5: DNS FILTERING

### What Happens?

1. **User visits website**
   - Types in browser: `wikipedia.org`
   - Browser requests DNS: "What is the IP of wikipedia.org?"

2. **DNS Query to VPN DNS Server**
   ```
   Browser → DNS Query: "wikipedia.org"
            ↓
   Query goes to: 10.10.0.1 (VPN DNS server)
            ↓
   DNS Server receives query on port 53
   ```

3. **DNS Server Detects Client**
   ```
   DNS Server:
   - Reads source IP: 10.10.0.12 (VPN client IP)
   - Finds device_id via API:
     GET /api/get_device_by_ip.php?ip=10.10.0.12
            ↓
   Backend:
   - Finds device with wg_ip = 10.10.0.12
   - Returns: {"found": true, "device_id": 123}
            ↓
   DNS Server gets device_id: 123
   ```

4. **DNS Server Gets Whitelist**
   ```
   DNS Server:
   - Requests whitelist:
     GET /api/get_whitelist.php?device_id=123
            ↓
   Backend:
   - Gets whitelist from database
   - Filters pornographic domains
   - Returns: ["wikipedia.org", "google.com"]
            ↓
   DNS Server gets whitelist
   ```

5. **DNS Server Decides**
   ```
   DNS Server checks:
   - Is "wikipedia.org" in whitelist? → YES
   - Is "wikipedia.org" pornographic? → NO
            ↓
   RESOLVE: Forward query to 8.8.8.8
            ↓
   Gets IP address: 91.198.174.192
            ↓
   Returns IP to browser
            ↓
   Browser can load website ✅
   ```

**Result:** Website loads if domain in whitelist

---

## 🚫 STEP 6: PORNOGRAPHIC BLOCKING

### What Happens?

1. **User tries pornographic site**
   - Types in browser: `pornhub.com`
   - Browser requests DNS: "What is the IP of pornhub.com?"

2. **DNS Query to VPN DNS Server**
   ```
   Browser → DNS Query: "pornhub.com"
            ↓
   Query goes to: 10.10.0.1 (VPN DNS server)
            ↓
   DNS Server receives query
   ```

3. **DNS Server Detects Pornographic Domain**
   ```
   DNS Server:
   - Checks: is_pornographic_domain("pornhub.com")
   - Pattern match: "porn" in "pornhub.com" → YES
            ↓
   PERMANENT BLOCK: Returns NXDOMAIN
            ↓
   Browser gets: "Domain not found"
            ↓
   Website CANNOT be loaded ❌
   ```

4. **Even If In Whitelist**
   ```
   If user accidentally adds "pornhub.com":
   - API blocks adding (add_whitelist.php)
   - If still in database → DNS server always blocks
   - Automatic cleanup removes from whitelist
            ↓
   Pornographic sites are ALWAYS blocked ✅
   ```

**Result:** Pornographic sites can NEVER be loaded

---

## 🔄 COMPLETE FLOW DIAGRAM

### Scenario 1: Allowed Website

```
1. User types: "wikipedia.org"
   ↓
2. Browser → DNS Query: "wikipedia.org"
   ↓
3. Query to VPN DNS Server (10.10.0.1:53)
   ↓
4. DNS Server detects client IP: 10.10.0.12
   ↓
5. DNS Server requests device_id:
   GET /api/get_device_by_ip.php?ip=10.10.0.12
   → {"found": true, "device_id": 123}
   ↓
6. DNS Server requests whitelist:
   GET /api/get_whitelist.php?device_id=123
   → ["wikipedia.org", "google.com"]
   ↓
7. DNS Server checks:
   - "wikipedia.org" in whitelist? → YES
   - Pornographic? → NO
   ↓
8. DNS Server resolves via 8.8.8.8
   → IP: 91.198.174.192
   ↓
9. Browser gets IP address
   ↓
10. Browser loads website ✅
```

### Scenario 2: Blocked Website (Not in Whitelist)

```
1. User types: "example.com"
   ↓
2. Browser → DNS Query: "example.com"
   ↓
3. Query to VPN DNS Server (10.10.0.1:53)
   ↓
4. DNS Server detects client IP: 10.10.0.12
   ↓
5. DNS Server requests device_id:
   GET /api/get_device_by_ip.php?ip=10.10.0.12
   → {"found": true, "device_id": 123}
   ↓
6. DNS Server requests whitelist:
   GET /api/get_whitelist.php?device_id=123
   → ["wikipedia.org", "google.com"]
   ↓
7. DNS Server checks:
   - "example.com" in whitelist? → NO
   ↓
8. DNS Server returns NXDOMAIN
   ↓
9. Browser gets: "Domain not found"
   ↓
10. Website CANNOT be loaded ❌
```

### Scenario 3: Pornographic Website

```
1. User types: "pornhub.com"
   ↓
2. Browser → DNS Query: "pornhub.com"
   ↓
3. Query to VPN DNS Server (10.10.0.1:53)
   ↓
4. DNS Server detects client IP: 10.10.0.12
   ↓
5. DNS Server checks:
   - is_pornographic_domain("pornhub.com")? → YES
   ↓
6. PERMANENT BLOCK: Returns NXDOMAIN
   (Even if in whitelist!)
   ↓
7. Browser gets: "Domain not found"
   ↓
8. Website CANNOT be loaded ❌
```

---

## 🔐 SECURITY LAYERS

### Layer 1: API Blocking
- **Where:** `api/add_whitelist.php`
- **What:** Blocks adding pornographic domains
- **Result:** Cannot be added to whitelist

### Layer 2: Whitelist Filtering
- **Where:** `api/get_whitelist.php`
- **What:** Filters pornographic domains from whitelist
- **Result:** Even if in database → not in whitelist

### Layer 3: DNS Blocking
- **Where:** `dns_whitelist_server.py`
- **What:** Always blocks pornographic domains
- **Result:** Always NXDOMAIN for pornographic sites

### Layer 4: Automatic Cleanup
- **Where:** `api/cleanup_porn_domains.php`
- **What:** Removes pornographic domains from database
- **Result:** Database stays clean

---

## 📊 DATABASE STRUCTURE

### Table: `users`
```
- id (INT, PRIMARY KEY)
- email (VARCHAR, UNIQUE)
- password_hash (VARCHAR)
- is_admin (TINYINT)
- created_at (DATETIME)
```

### Table: `devices`
```
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY → users.id)
- name (VARCHAR)
- wg_ip (VARCHAR) → VPN IP (10.10.0.x)
- wg_public_key (VARCHAR)
- status (ENUM: 'active', 'inactive')
- created_at (DATETIME)
```

### Table: `whitelist`
```
- id (INT, PRIMARY KEY)
- device_id (INT, FOREIGN KEY → devices.id)
- domain (VARCHAR)
- enabled (TINYINT)
- created_at (DATETIME)
```

### Table: `subscriptions`
```
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY → users.id)
- plan_id (INT)
- status (ENUM: 'active', 'expired', 'cancelled')
- expires_at (DATETIME)
```

---

## 🎯 KEY CONCEPTS

### Whitelist-Only Filtering
- **Principle:** Everything blocked by default
- **Only allowed:** Domains in whitelist
- **Empty whitelist:** No internet access
- **Why:** Only method that is 100% reliable

### VPN Requirement
- **Why:** Control at network level
- **Full-tunnel:** All traffic via VPN
- **DNS Forcing:** DNS queries to VPN DNS server
- **Kill-switch:** No internet without VPN

### Pornographic Blocking
- **Permanent:** Cannot be disabled
- **Multi-layer:** 4 layers of security
- **Multi-language:** Works in all languages
- **Automatic:** No manual action needed

---

## ✅ CONCLUSION

The system works as follows:

1. **User registers** → Account + Device + VPN Config
2. **User connects VPN** → All traffic via VPN
3. **User adds domains** → Saved in whitelist
4. **Browser requests DNS** → DNS server checks whitelist
5. **Domain in whitelist** → Website loads ✅
6. **Domain not in whitelist** → NXDOMAIN ❌
7. **Pornographic domain** → ALWAYS NXDOMAIN ❌

**Result:** Only allowed domains can be visited. Everything else is blocked.
