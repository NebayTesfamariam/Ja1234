# 🌐 DNS Whitelist-Only Server Setup (STAP 4)

## 🎯 Doel
De **DNS-server wordt de ENIGE beslisser**:
- Niet in whitelist → **NXDOMAIN**
- Lege whitelist → **geen internet**

---

## ✅ 4.1 DNS Server Software

We gebruiken **Python DNS server** (`dns_whitelist_server.py`) omdat:
- ✅ Simpel te configureren
- ✅ Directe API integratie
- ✅ Makkelijk aan te passen
- ✅ Werkt op alle platforms

### Alternatief: CoreDNS
Zie `CoreDNS_whitelist.cfg` voor CoreDNS configuratie (forward naar Python server).

---

## ✅ 4.2 Installatie

### Vereisten

```bash
pip3 install requests
```

### Start DNS Server

```bash
# Must run as root (port 53 requires root)
sudo python3 dns_whitelist_server.py
```

### Configuratie Aanpassen

Edit `dns_whitelist_server.py`:

```python
API_BASE_URL = "http://localhost/44/api"  # Your API URL
CACHE_TTL = 15  # Cache whitelist for 15 seconds
DNS_PORT = 53
```

---

## ✅ 4.3 Hoe Het Werkt

### Voor ELKE DNS Query:

1. **Lees source IP** (bijv. `10.10.0.12`)
2. **Zoek device_id** via API:
   ```
   GET /api/get_device_by_ip.php?ip=10.10.0.12
   ```
3. **Vraag whitelist op**:
   ```
   GET /api/get_whitelist.php?device_id=DEVICE_ID
   ```
4. **Beslis:**
   - Response `[]` (lege whitelist) → **NXDOMAIN**
   - Domein in whitelist → **RESOLVE** (forward naar 8.8.8.8)
   - Domein niet in whitelist → **NXDOMAIN**

### ❗ Belangrijk

- **Geen uitzonderingen**
- **Geen blockpagina's**
- **Alleen NXDOMAIN** bij blokkeren

---

## ✅ 4.4 NXDOMAIN is Verplicht

Bij blokkeren moet DNS exact dit doen:

```
pornsite.com → NXDOMAIN
```

### ❌ NIET Toegestaan

- ❌ Geen redirect
- ❌ Geen HTML
- ❌ Geen "blocked" response
- ❌ Geen 0.0.0.0 IP

### ✅ Waarom NXDOMAIN?

- Apps & video players negeren blockpagina's
- NXDOMAIN stopt ALLES
- Werkt in alle browsers en apps

---

## ✅ 4.5 Caching

- Cache whitelist per device **15 seconden** (configureerbaar)
- Als API faalt → behandel als **lege whitelist** (fail-safe)
- Fail = block (NXDOMAIN)

### Cache Logica

```python
# Check cache first
if device_id in cache and cache_age < 15 seconds:
    return cached_whitelist

# Fetch from API
whitelist = api.get_whitelist(device_id)

# Update cache
cache[device_id] = whitelist
```

---

## ✅ 4.6 Test (VERPLICHT)

### Test A — Lege Whitelist

1. Zorg dat device **geen whitelist entries** heeft
2. Op VPN-client:

```bash
nslookup google.com 10.10.0.1
```

➡️ **Resultaat moet zijn:**

```
** server can't find google.com: NXDOMAIN
```

### Test B — 1 Domein in Whitelist

1. Voeg toe aan whitelist: `wikipedia.org`
2. Op VPN-client:

```bash
nslookup wikipedia.org 10.10.0.1
```

➡️ **Resultaat moet zijn:**

```
Name:    wikipedia.org
Address: 91.198.174.192
```

3. Test niet-whitelisted domein:

```bash
nslookup google.com 10.10.0.1
```

➡️ **Resultaat moet zijn:**

```
** server can't find google.com: NXDOMAIN
```

### Test C — Meerdere Domeinen

1. Voeg toe: `wikipedia.org`, `khanacademy.org`
2. Test beide:

```bash
nslookup wikipedia.org 10.10.0.1    # werkt
nslookup khanacademy.org 10.10.0.1  # werkt
nslookup google.com 10.10.0.1       # NXDOMAIN
```

---

## ✅ 4.7 Troubleshooting

### Probleem: DNS server start niet

**Oorzaak:** Port 53 vereist root privileges

**Oplossing:**
```bash
sudo python3 dns_whitelist_server.py
```

### Probleem: Alle queries geven NXDOMAIN

**Oorzaak:** API is niet bereikbaar of device_id niet gevonden

**Oplossing:**
1. Check API URL in config
2. Check of `get_device_by_ip.php` werkt
3. Check logs voor errors

### Probleem: Whitelisted domeinen werken niet

**Oorzaak:** Domain normalisatie mismatch

**Oplossing:**
1. Check whitelist in database (moet zijn: `wikipedia.org`, niet `www.wikipedia.org`)
2. Check domain normalisatie in DNS server
3. Check API response format

### Probleem: Cache werkt niet

**Oorzaak:** Cache TTL te kort of cache niet geïmplementeerd

**Oplossing:**
1. Check `CACHE_TTL` waarde
2. Check cache implementatie in code

---

## ✅ 4.8 Controlelijst

Voor je verder gaat, controleer:

- ❓ Geeft DNS NXDOMAIN bij block? → **JA**
- ❓ Beslist DNS op basis van whitelist? → **JA**
- ❓ Lege whitelist = niets resolveert? → **JA**
- ❓ Whitelisted domeinen worden opgelost? → **JA**
- ❓ Cache werkt (15 seconden)? → **JA**
- ❓ API fout = NXDOMAIN (fail-safe)? → **JA**

Als één antwoord **NEE** is → **STOP, fix eerst**.

---

## 🔧 Firewall Configuratie

### Allow DNS Traffic

```bash
# Allow DNS from VPN subnet
sudo ufw allow from 10.10.0.0/24 to any port 53
```

### Block External DNS

```bash
# Block all DNS except from VPN subnet (optional, for later)
# This prevents DNS leaks
```

---

## 📊 Logging

De DNS server logt:
- Elke DNS query (domain, client IP)
- Device ID lookup
- Whitelist check result
- Resolution result

Voorbeeld log:
```
[14:30:15] DNS query: google.com from 10.10.0.12
  → No device_id found for IP 10.10.0.12 - returning NXDOMAIN
[14:30:20] DNS query: wikipedia.org from 10.10.0.12
  → Domain wikipedia.org is in whitelist - resolving
```

---

## ⚠️ Belangrijk

**Zonder correcte DNS:**
- ❌ Domeinen kunnen worden opgelost zonder whitelist
- ❌ Apps kunnen direct internet gebruiken
- ❌ Whitelist wordt genegeerd

**Met correcte DNS:**
- ✅ Alleen whitelisted domeinen worden opgelost
- ✅ Alle andere domeinen → NXDOMAIN
- ✅ Lege whitelist = geen internet

---

**Volgende stap:** Firewall/kill-switch configureren (optioneel, voor extra beveiliging)
