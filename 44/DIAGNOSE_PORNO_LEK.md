# 🔍 Diagnose: Waarom Laadt Porno Video Nog?

## 🎯 Stap-voor-stap Diagnose

Volg deze checklist **in volgorde** om te vinden waar het lek zit.

---

## ✅ STAP 1: Check Whitelist (EERSTE CHECK)

### Op je device:

1. **Open admin panel** of check via API:
   ```
   http://localhost/44/api/get_whitelist.php?device_id=JOUW_DEVICE_ID
   ```

2. **Verwacht resultaat:**
   - Lege whitelist → `[]` → **NIETS zou moeten werken**
   - Met domeinen → `["wikipedia.org", ...]` → **Alleen die domeinen werken**

### ❓ Vraag:
- **Is je whitelist LEEG?** → Dan zou NIETS moeten werken
- **Als whitelist leeg is maar porno laadt** → **DNS/VPN probleem**

---

## ✅ STAP 2: Check VPN Verbinding

### Op je device:

1. **Check of VPN AAN staat**
2. **Check je IP:**
   - Ga naar: `https://whatismyipaddress.com/`
   - **Moet VPN server IP zijn** (niet je eigen ISP IP)

### ❓ Vraag:
- **Zie je je eigen ISP IP?** → VPN werkt NIET → **Dit is het probleem!**
- **Zie je VPN server IP?** → VPN werkt → Ga naar STAP 3

---

## ✅ STAP 3: Check DNS Server

### Op VPN server:

1. **Check of DNS server draait:**
   ```bash
   sudo netstat -tuln | grep :53
   # Moet iets tonen zoals: udp 0.0.0.0:53
   ```

2. **Check DNS server logs:**
   ```bash
   # Als dns_whitelist_server.py draait, zie je logs
   sudo python3 dns_whitelist_server.py
   ```

### ❓ Vraag:
- **Draait DNS server?** → NEE → **Dit is het probleem!**
- **Draait DNS server?** → JA → Ga naar STAP 4

---

## ✅ STAP 4: Test DNS Direct

### Op je device (via VPN):

```bash
# Test DNS query
nslookup google.com 10.10.0.1
```

### ❓ Verwacht:
- **Als google.com NIET in whitelist:** → `NXDOMAIN` (domain not found)
- **Als google.com WEL in whitelist:** → IP adres

### ❓ Vraag:
- **Geeft DNS IP adres voor niet-whitelisted domein?** → **DNS werkt niet correct**
- **Geeft DNS NXDOMAIN?** → DNS werkt → Ga naar STAP 5

---

## ✅ STAP 5: Check Chrome DoH (DNS-over-HTTPS)

### Op je device:

1. **Open Chrome**
2. **Ga naar:** `chrome://settings/security`
3. **Check "Use secure DNS"** → Moet **UIT** zijn

### ❓ Vraag:
- **Is DoH AAN?** → **Dit is het probleem!** → Zet UIT
- **Is DoH UIT?** → Ga naar STAP 6

---

## ✅ STAP 6: Check Firewall Rules

### Op VPN server:

```bash
# Check QUIC blocking
sudo iptables -S FORWARD | grep "udp.*443"

# Check DoT blocking
sudo iptables -S FORWARD | grep "tcp.*853"

# Check DNS forcing
sudo iptables -S FORWARD | grep "dport.*53"
```

### ❓ Vraag:
- **Zijn firewall regels actief?** → NEE → **Dit is het probleem!**
- **Zijn firewall regels actief?** → JA → Ga naar STAP 7

---

## ✅ STAP 7: Check Direct IP Access

### Test:

1. **Probeer direct IP:** `https://185.xxx.xxx.xxx` (porno site IP)
2. **Werkt dit?** → **Direct IP access is niet geblokkeerd**

### ❓ Vraag:
- **Werkt direct IP?** → **Firewall blokkeert direct IP niet**
- **Werkt direct IP niet?** → Goed → Ga naar STAP 8

---

## ✅ STAP 8: Check QUIC (UDP 443)

### Test:

1. **Open Chrome DevTools** (F12)
2. **Ga naar Network tab**
3. **Filter op:** `udp` of `quic`
4. **Probeer porno site te laden**

### ❓ Vraag:
- **Zie je UDP 443 verkeer?** → **QUIC is niet geblokkeerd**
- **Geen UDP 443 verkeer?** → Goed → Ga naar STAP 9

---

## ✅ STAP 9: Check App/Browser Bypass

### Test:

1. **Probeer porno in verschillende browsers:**
   - Chrome
   - Firefox
   - Safari
   - Edge

2. **Probeer porno in apps:**
   - YouTube app
   - Browser apps

### ❓ Vraag:
- **Werkt porno in ALLE browsers/apps?** → **VPN/DNS probleem**
- **Werkt porno alleen in Chrome?** → **Chrome DoH probleem**
- **Werkt porno alleen in apps?** → **App bypass probleem**

---

## 🔧 Meest Waarschijnlijke Oorzaken

### 1. VPN werkt niet (meest waarschijnlijk)
- **Symptoom:** Je ziet je eigen ISP IP
- **Fix:** Check WireGuard config, `AllowedIPs = 0.0.0.0/0`

### 2. DNS server draait niet
- **Symptoom:** DNS queries falen of geven verkeerde antwoorden
- **Fix:** Start `dns_whitelist_server.py`

### 3. Chrome DoH is aan
- **Symptoom:** Porno laadt alleen in Chrome
- **Fix:** Zet DoH UIT in Chrome settings

### 4. Firewall regels niet actief
- **Symptoom:** QUIC/DoT werkt nog
- **Fix:** Run firewall scripts

### 5. Whitelist is niet leeg
- **Symptoom:** Porno site staat in whitelist
- **Fix:** Check whitelist, verwijder porno domeinen

---

## 📋 Quick Diagnostic Script

Run dit op je device (via VPN):

```bash
# 1. Check VPN IP
curl ifconfig.me
# Moet VPN server IP zijn

# 2. Check DNS
nslookup google.com 10.10.0.1
# Moet NXDOMAIN zijn (als niet in whitelist)

# 3. Check DNS forcing
nslookup google.com 8.8.8.8
# Moet falen (DNS geforceerd)
```

---

## ✅ Wat Te Rapporteren

Als je nog steeds porno ziet, geef dan:

1. **Whitelist status:** Leeg of niet?
2. **VPN IP:** Je eigen IP of VPN IP?
3. **DNS test:** Werkt `nslookup google.com 10.10.0.1`?
4. **Chrome DoH:** UIT of AAN?
5. **Firewall:** Regels actief?
6. **Welke browser/app:** Chrome, Firefox, app?

---

## 🚨 Belangrijk

**Porno kan alleen laden als:**
- VPN werkt NIET (verkeer gaat niet via VPN)
- DNS werkt NIET (geeft IP voor niet-whitelisted domeinen)
- Chrome DoH is AAN (Chrome gebruikt eigen DNS)
- Firewall werkt NIET (QUIC/DoT niet geblokkeerd)
- Whitelist bevat porno domeinen

**Check elk punt hierboven!**
