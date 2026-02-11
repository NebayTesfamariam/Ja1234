# 🚨 PROBLEEM: Pornografische Content is Zichtbaar

## ❌ HOOFDPROBLEEM

**De DNS server draait NIET!**

Zonder DNS server werkt het hele systeem NIET. Pornografische sites kunnen worden geladen omdat:
1. DNS queries gaan naar normale DNS servers (8.8.8.8, etc.)
2. Geen whitelist filtering
3. Geen porn blokkering

---

## 🔍 DIAGNOSE CHECKLIST

### ✅ CHECK 1: DNS Server Status

**Probleem:** DNS server draait niet

**Check:**
```bash
ps aux | grep dns_whitelist_server | grep -v grep
# Moet proces tonen - nu NIETS!
```

**Fix:**
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
sudo python3 dns_whitelist_server.py
```

---

### ✅ CHECK 2: Poort 53 Status

**Probleem:** Poort 53 is niet in gebruik

**Check:**
```bash
sudo lsof -i :53
# Moet dns_whitelist_server.py tonen - nu NIETS!
```

**Fix:**
Start DNS server met sudo (poort 53 vereist root)

---

### ✅ CHECK 3: VPN Verbinding

**Probleem:** VPN is niet verbonden

**Check:**
- Is WireGuard VPN AAN op je device?
- Check IP: `curl ifconfig.me` → Moet VPN server IP zijn (niet je eigen IP)

**Fix:**
- Verbind met WireGuard VPN
- Check WireGuard config: `DNS = 10.10.0.1` (moet VPN DNS zijn)

---

### ✅ CHECK 4: Whitelist Status

**Probleem:** Whitelist bevat pornografische domeinen

**Check:**
```bash
# Via API of database
curl http://localhost/44/api/get_whitelist.php?device_id=JOUW_DEVICE_ID
```

**Fix:**
- Verwijder pornografische domeinen uit whitelist
- API zou ze moeten blokkeren, maar check handmatig

---

### ✅ CHECK 5: Chrome DoH (DNS-over-HTTPS)

**Probleem:** Chrome gebruikt eigen DNS (omzeilt VPN DNS)

**Check:**
- Chrome → `chrome://settings/security`
- "Use secure DNS" → Moet UIT zijn

**Fix:**
- Zet "Use secure DNS" UIT
- Sluit Chrome volledig af
- Heropen Chrome

---

## 🎯 HOE HET SYSTEEM MOET WERKEN

```
1. User verbindt VPN (WireGuard)
   ↓
2. VPN geeft IP: 10.10.0.x
   ↓
3. DNS queries gaan naar: 10.10.0.1 (VPN DNS server)
   ↓
4. DNS server checkt whitelist
   ↓
5a. Domein in whitelist → RESOLVE (geef IP)
5b. Domein NIET in whitelist → NXDOMAIN (blokkeer)
5c. Pornografisch domein → ALTIJD NXDOMAIN (blokkeer)
   ↓
6. Browser/app kan website laden OF krijgt "domain not found"
```

---

## 🚨 WAAROM HET NU NIET WERKT

**Huidige situatie:**

```
1. User verbindt VPN (misschien)
   ↓
2. DNS queries gaan naar: 8.8.8.8 (Google DNS) ❌
   ↓
3. Geen whitelist check ❌
   ↓
4. Geen porn blokkering ❌
   ↓
5. ALLE websites werken (inclusief porn) ❌
```

**Oorzaak:** DNS server draait niet!

---

## ✅ OPLOSSING (STAP-VOOR-STAP)

### STAP 1: Start DNS Server

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44

# Check of Python werkt
python3 --version

# Check of requests library geïnstalleerd is
python3 -c "import requests"

# Start DNS server (vereist sudo voor poort 53)
sudo python3 dns_whitelist_server.py
```

**Verwacht output:**
```
DNS Whitelist Server started on port 53
API Base URL: http://localhost/44/api
Cache TTL: 15 seconds
Waiting for DNS queries...
```

---

### STAP 2: Check of DNS Server Draait

```bash
# In nieuwe terminal
ps aux | grep dns_whitelist_server | grep -v grep
# Moet proces tonen

sudo lsof -i :53
# Moet dns_whitelist_server.py tonen
```

---

### STAP 3: Test DNS Server

```bash
# Test DNS query (van VPN server)
dig @127.0.0.1 google.com

# Of van VPN client (10.10.0.x)
dig @10.10.0.1 google.com
```

**Verwacht:**
- Als google.com NIET in whitelist → `NXDOMAIN`
- Als google.com WEL in whitelist → IP adres

---

### STAP 4: Check VPN Verbinding

**Op je device:**
1. Check of WireGuard VPN AAN is
2. Check IP: `curl ifconfig.me` → Moet VPN server IP zijn
3. Check DNS: WireGuard config moet `DNS = 10.10.0.1` hebben

---

### STAP 5: Check Whitelist

**Via website:**
1. Log in op control panel
2. Check whitelist voor je device
3. Verwijder pornografische domeinen (als aanwezig)

**Via API:**
```bash
curl http://localhost/44/api/get_whitelist.php?device_id=JOUW_DEVICE_ID
```

---

### STAP 6: Test Porn Blokkering

**Test sites:**
- `pornhub.com` → Moet NIET laden (NXDOMAIN)
- `xvideos.com` → Moet NIET laden (NXDOMAIN)
- `google.com` → Alleen laden als in whitelist

---

## 🔧 AUTOMATISCH STARTEN (OPTIONEEL)

### macOS LaunchDaemon

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
./install_dns_launchdaemon.sh
```

Dit start DNS server automatisch bij boot.

---

## 📋 SAMENVATTING

**Probleem:** DNS server draait niet

**Oplossing:**
1. Start DNS server: `sudo python3 dns_whitelist_server.py`
2. Check VPN verbinding
3. Check whitelist
4. Test porn blokkering

**Belangrijk:**
- DNS server MOET draaien voor het systeem te werken
- VPN MOET verbonden zijn
- Whitelist bepaalt welke sites werken
- Pornografische sites zijn ALTIJD geblokkeerd (zelfs als in whitelist)

---

## 🆘 NOG STEEDS PROBLEMEN?

Check deze punten:
1. ✅ DNS server draait? (`ps aux | grep dns_whitelist_server`)
2. ✅ Poort 53 in gebruik? (`sudo lsof -i :53`)
3. ✅ VPN verbonden? (`curl ifconfig.me`)
4. ✅ Chrome DoH UIT? (`chrome://settings/security`)
5. ✅ Whitelist leeg? (check via API)
6. ✅ Device actief? (check via control panel)

**Als alles correct is maar porn nog steeds laadt:**
- Check DNS server logs voor errors
- Test DNS direct: `dig @10.10.0.1 pornhub.com` → Moet NXDOMAIN zijn
- Check firewall regels op VPN server
