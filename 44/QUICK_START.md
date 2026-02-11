# ⚡ Quick Start - 5 Minuten Setup

## 🎯 Wat Je Nu Moet Doen

### 1️⃣ Database Setup (1 minuut)
```bash
# Open in browser:
http://localhost/44/setup_database.php
```
✅ Klik op "Setup Database" → Database is klaar!

---

### 2️⃣ DNS Server Starten (1 minuut)
```bash
# Open terminal:
cd /Applications/XAMPP/xamppfiles/htdocs/44
sudo python3 dns_whitelist_server.py
```
✅ DNS server draait nu!

**Let op:** Vereist `sudo` (root toegang) voor poort 53

---

### 3️⃣ Abonnement Aansluiten (2 minuten)
```bash
# Open in browser:
http://localhost/44/subscribe.html
```
1. Kies een plan (Basic/Family/Premium)
2. Vul email + wachtwoord in
3. Klik "Abonnement Aansluiten"

✅ **Direct na aanmelding:**
- ✅ Abonnement actief
- ✅ Device automatisch aangemaakt
- ✅ Pornografische content geblokkeerd

---

### 4️⃣ WireGuard Config Downloaden (1 minuut)
```bash
# Log in:
http://localhost/44/public/index.html
```
1. Ga naar "Devices"
2. Klik "Download WireGuard Config"
3. Importeer in WireGuard app
4. Activeer VPN verbinding

✅ **VPN is nu actief!**

---

## ✅ Klaar!

**Het systeem werkt nu:**
- ✅ Pornografische content wordt geblokkeerd
- ✅ Alleen whitelisted domeinen werken
- ✅ Werkt op alle devices met VPN

---

## 📱 Device Setup

### iPhone/iPad
1. Installeer **WireGuard** app (App Store)
2. Open app → "+" → "Create from file"
3. Selecteer gedownloade `.conf` file
4. Activeer VPN

### Android
1. Installeer **WireGuard** app (Google Play)
2. Open app → "+" → "Create from file"
3. Selecteer gedownloade `.conf` file
4. Activeer VPN

### Windows/Mac/Linux
1. Installeer WireGuard client
2. Importeer `.conf` file
3. Activeer VPN

---

## 🧪 Test

### Test 1: Pornografische Content Geblokkeerd?
```bash
# Probeer te bezoeken: pornhub.com
# Met VPN actief
```
✅ Site laadt niet → ✅ Werkt!

### Test 2: Whitelist Werkt?
```bash
# Voeg toe: wikipedia.org
# Probeer te bezoeken
```
✅ Site laadt wel → ✅ Werkt!

---

## 🆘 Problemen?

### DNS Server Start Niet?
```bash
sudo python3 dns_whitelist_server.py
```

### VPN Verbindt Niet?
- Check WireGuard config: `AllowedIPs = 0.0.0.0/0`
- Check DNS: `DNS = 10.10.0.1`

### Geen Internet?
- Voeg domeinen toe aan whitelist (google.com, etc.)

---

**Klaar! Het systeem beschermt nu tegen pornografische content.** 🛡️
