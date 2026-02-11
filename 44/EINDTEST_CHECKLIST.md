# 🧪 EINDTEST CHECKLIST - Whitelist-Only Systeem

## ⚠️ BELANGRIJK
**Deze test bepaalt of het systeem correct werkt.**
- ✅ Als alles faalt zoals verwacht → **SYSTEEM WERKT**
- ❌ Als iets werkt dat niet zou moeten → **SYSTEEM LEEKT**

---

## ✅ TEST A — LEGE WHITELIST (DE HARDSTE TEST)

### 📋 Voorbereiding

1. **Kies één device** (bijv. device ID: `____`)
2. **Controleer whitelist is LEEG:**
   ```bash
   # Check via API
   curl "http://localhost/44/api/get_whitelist.php?device_id=X" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```
   **Verwacht:** `[]` (lege array)

3. **Zet VPN AAN** op het device
4. **Controleer VPN IP:**
   - Device moet IP hebben zoals `10.10.0.X`
   - Check via: `http://localhost/44/api/get_device_by_ip.php?ip=10.10.0.X`

### 🧪 Test Cases

Test elk van deze en markeer resultaat:

| Test | URL/Site | Verwacht Resultaat | Werkelijk Resultaat | Status |
|------|----------|-------------------|---------------------|--------|
| 1 | `google.com` | ❌ NIET werkt (NXDOMAIN) | | |
| 2 | `wikipedia.org` | ❌ NIET werkt | | |
| 3 | `youtube.com` | ❌ NIET werkt | | |
| 4 | Porno-site (bijv. `pornhub.com`) | ❌ NIET werkt | | |
| 5 | Afbeelding laden (bijv. `i.imgur.com/image.jpg`) | ❌ NIET werkt | | |
| 6 | Video openen (bijv. `vimeo.com/video`) | ❌ NIET werkt | | |
| 7 | Willekeurige website | ❌ NIET werkt | | |

### ✅ Correct Resultaat TEST A

```
ALLE tests moeten FALEN
NIETS mag werken
```

**Als ÉÉN test werkt → STOP, rapporteer wat werkt**

---

## ✅ TEST B — ÉÉN DOMEIN (wikipedia.org)

### 📋 Voorbereiding

1. **Voeg toe aan whitelist:**
   ```bash
   # Via API of admin panel
   # Domain: wikipedia.org
   # Device ID: X
   ```

2. **Verifieer whitelist:**
   ```bash
   curl "http://localhost/44/api/get_whitelist.php?device_id=X" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```
   **Verwacht:** `["wikipedia.org"]`

3. **Wacht 15 seconden** (cache TTL)

### 🧪 Test Cases

| Test | URL/Site | Verwacht Resultaat | Werkelijk Resultaat | Status |
|------|----------|-------------------|---------------------|--------|
| 1 | `wikipedia.org` | ✅ WERKT | | |
| 2 | `www.wikipedia.org` | ✅ WERKT (subdomain) | | |
| 3 | `en.wikipedia.org` | ✅ WERKT (subdomain) | | |
| 4 | `google.com` | ❌ NIET werkt | | |
| 5 | `youtube.com` | ❌ NIET werkt | | |
| 6 | Porno-site | ❌ NIET werkt | | |
| 7 | Afbeelding van andere site | ❌ NIET werkt | | |
| 8 | Video van andere site | ❌ NIET werkt | | |
| 9 | Thumbnail van andere site | ❌ NIET werkt | | |

### ✅ Correct Resultaat TEST B

```
Alleen Wikipedia werkt
NIETS anders werkt
```

**Als ÉÉN andere site werkt → STOP, rapporteer wat werkt**

---

## 🔍 DNS Server Check

### Controleer DNS Server Draait

```bash
# Check of DNS server draait
sudo netstat -tuln | grep :53

# Check DNS server logs
# (als dns_whitelist_server.py draait, zie je logs)
```

### Test DNS Direct

```bash
# Van VPN client, test DNS:
nslookup google.com 10.10.0.1
# Verwacht: NXDOMAIN (als niet in whitelist)

nslookup wikipedia.org 10.10.0.1
# Verwacht: IP adres (als in whitelist)
```

---

## 🚨 Wat Te Rapporteren

### Als TEST A Faalt (iets werkt dat niet zou moeten):

```
❌ TEST A GEFAALD
- Device ID: X
- Whitelist: [] (leeg)
- Wat werkt: [bijv. google.com laadt]
- DNS response: [bijv. IP adres in plaats van NXDOMAIN]
```

### Als TEST B Faalt (iets anders werkt):

```
❌ TEST B GEFAALD
- Device ID: X
- Whitelist: ["wikipedia.org"]
- Wat werkt: [bijv. youtube.com laadt]
- DNS response: [bijv. IP adres in plaats van NXDOMAIN]
```

### Als Alles Correct Werkt:

```
✅ TEST A GESLAAGD - Niets werkt met lege whitelist
✅ TEST B GESLAAGD - Alleen Wikipedia werkt
✅ SYSTEEM WERKT CORRECT
```

---

## 📝 Test Log

### TEST A Log

```
Datum: __________
Device ID: __________
VPN IP: __________
Whitelist: [] (leeg)

Test 1 - google.com: [ ] Werkt / [ ] Faalt
Test 2 - wikipedia.org: [ ] Werkt / [ ] Faalt
Test 3 - youtube.com: [ ] Werkt / [ ] Faalt
Test 4 - porno-site: [ ] Werkt / [ ] Faalt
Test 5 - afbeelding: [ ] Werkt / [ ] Faalt
Test 6 - video: [ ] Werkt / [ ] Faalt
Test 7 - willekeurige site: [ ] Werkt / [ ] Faalt

Resultaat: [ ] GESLAAGD / [ ] GEFAALD
```

### TEST B Log

```
Datum: __________
Device ID: __________
VPN IP: __________
Whitelist: ["wikipedia.org"]

Test 1 - wikipedia.org: [ ] Werkt / [ ] Faalt
Test 2 - www.wikipedia.org: [ ] Werkt / [ ] Faalt
Test 3 - en.wikipedia.org: [ ] Werkt / [ ] Faalt
Test 4 - google.com: [ ] Werkt / [ ] Faalt
Test 5 - youtube.com: [ ] Werkt / [ ] Faalt
Test 6 - porno-site: [ ] Werkt / [ ] Faalt
Test 7 - afbeelding andere site: [ ] Werkt / [ ] Faalt
Test 8 - video andere site: [ ] Werkt / [ ] Faalt
Test 9 - thumbnail andere site: [ ] Werkt / [ ] Faalt

Resultaat: [ ] GESLAAGD / [ ] GEFAALD
```

---

## ⚙️ Troubleshooting

### Als DNS niet werkt:

1. **Check DNS server draait:**
   ```bash
   sudo python3 dns_whitelist_server.py
   ```

2. **Check firewall rules:**
   ```bash
   sudo iptables -L -n | grep 53
   ```

3. **Check API bereikbaar:**
   ```bash
   curl "http://localhost/44/api/get_whitelist.php?device_id=X"
   ```

### Als VPN niet werkt:

1. **Check WireGuard config:**
   - `AllowedIPs = 0.0.0.0/0` (full-tunnel)
   - `DNS = 10.10.0.1` (VPN DNS)

2. **Check VPN verbinding:**
   ```bash
   # Op VPN client
   wg show
   ```

---

## ✅ EINDRESULTAAT

- [ ] TEST A GESLAAGD
- [ ] TEST B GESLAAGD
- [ ] SYSTEEM WERKT CORRECT

**Of:**

- [ ] TEST A GEFAALD - Rapporteer: __________
- [ ] TEST B GEFAALD - Rapporteer: __________
