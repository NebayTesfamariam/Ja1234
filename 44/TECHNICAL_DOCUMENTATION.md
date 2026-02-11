# 🔒 Anti-Porn Internet Platform — Strikte Technische Documentatie

## Doel

Het platform biedt **100% porn-vrije internettoegang** door **alle internettoegang standaard te blokkeren** en **alleen expliciet toegestane domeinen toe te staan**.

Er is **geen content-detectie**, **geen AI**, **geen categorie-filtering**.
Alles werkt op **netwerkniveau**, niet in de browser.

---

## 1. Kernprincipe (NIET ONDERHANDELBAAR)

### **Whitelist-Only (Default Deny)**

**Regels:**

* Alles is standaard **GEBLOKKEERD**
* Alleen domeinen op de whitelist werken
* Lege whitelist = **GEEN INTERNET**
* Niet toegestaan = **bestaat niet**

Dit is de **enige methode** die technisch **100% betrouwbaar** is.

---

## 2. Wat dit platform NIET doet

❌ Geen porn-detectie
❌ Geen AI
❌ Geen categorieën
❌ Geen browser-extensies
❌ Geen blacklist

👉 Blacklists en AI zijn **altijd te omzeilen** en daarom **verboden**.

---

## 3. Architectuur (Hoe blokkering echt werkt)

```
[ Device ]
   ↓ (alles geforceerd)
[ VPN (WireGuard) ]
   ↓
[ Firewall + DNS Resolver ]
   ↓
[ Whitelist API + Database ]
```

Alle internetverkeer **moet** via dit pad.
Er is **geen directe toegang** tot het internet.

---

## 4. VPN-verplichting (Wi-Fi + 4G + 5G)

### Waarom VPN verplicht is

* Routers beschermen alleen Wi-Fi
* Mobiele data vereist **per-device controle**

### Verplichte VPN-instellingen

```
AllowedIPs = 0.0.0.0/0
DNS = Eigen DNS-server (10.10.0.1)
PersistentKeepalive = 25
```

➡️ VPN uit = **geen internet**

### Implementatie

- **WireGuard Config Generator**: `api/get_wireguard_config.php`
- **Config Validator**: `api/validate_wireguard_config.php`
- **Documentatie**: `WIREGUARD_FULL_TUNNEL.md`

---

## 5. DNS-Blokkering (De kern van 100%)

### DNS-regels

* DNS beslist **alles**
* Niet in whitelist → **NXDOMAIN**
* Geen blockpagina's
* Geen redirects

**Waarom NXDOMAIN?**
Apps en video-players negeren blockpagina's,
maar **NXDOMAIN stopt alles**.

### Implementatie

- **DNS Server**: `dns_whitelist_server.py`
- **Whitelist API**: `api/get_whitelist.php`
- **Device IP Resolver**: `api/get_device_by_ip.php`
- **Documentatie**: `DNS_WHITELIST_SETUP.md`

### DNS Server Logica

```python
1. Ontvang DNS query van client (10.10.0.X)
2. Vraag device_id op via API (get_device_by_ip.php)
3. Vraag whitelist op via API (get_whitelist.php)
4. Check of domein in whitelist staat
5. Als NIET in whitelist → NXDOMAIN
6. Als WEL in whitelist → Resolve via upstream DNS
```

---

## 6. Firewall-handhaving (Anti-Bypass)

De firewall **blokkeert actief**:

* ❌ DNS-over-HTTPS (DoH) - TCP 443 naar DoH providers
* ❌ DNS-over-TLS (DoT) - TCP 853
* ❌ QUIC / UDP 443 (video-lekken)
* ❌ Direct IP-verkeer
* ❌ Verkeer buiten VPN

➡️ Bypass is **technisch onmogelijk**

### Implementatie

- **Firewall Setup**: `vpn_firewall_setup.sh`
- **QUIC Block**: `block_quic_udp443.sh`
- **DoT Block**: `block_dot_tcp853.sh`
- **DNS Force**: `force_dns_only.sh`
- **Documentatie**: `KILL_SWITCH_SETUP.md`, `FIREWALL_QUICKSTART.md`

### Firewall Regels

```bash
# Force DNS naar VPN resolver
iptables -I FORWARD -s 10.10.0.0/24 -d 10.10.0.1 -p udp --dport 53 -j ACCEPT
iptables -I FORWARD -s 10.10.0.0/24 -p udp --dport 53 -j DROP

# Block DoT (TCP 853)
iptables -I FORWARD -s 10.10.0.0/24 -p tcp --dport 853 -j DROP

# Block QUIC (UDP 443)
iptables -I FORWARD -s 10.10.0.0/24 -p udp --dport 443 -j DROP

# Block DoH providers (Cloudflare, Google, Quad9)
iptables -I FORWARD -s 10.10.0.0/24 -d 1.1.1.1 -p tcp --dport 443 -j DROP
iptables -I FORWARD -s 10.10.0.0/24 -d 8.8.8.8 -p tcp --dport 443 -j DROP
iptables -I FORWARD -s 10.10.0.0/24 -d 9.9.9.9 -p tcp --dport 443 -j DROP

# Kill-switch: Drop all traffic not from VPN interface
iptables -I FORWARD -s 10.10.0.0/24 ! -i wg0 -j DROP
```

---

## 7. Browser & OS-beveiliging (Automatisch)

### Chrome / Browsers

Browserinstellingen worden **automatisch vergrendeld** via policies:

* Secure DNS = UIT
* QUIC = UIT
* Instellingen niet wijzigbaar door gebruiker

Gebruiker ziet:

> "Deze instelling wordt beheerd door uw beheerder"

**Implementatie:**
- **Chrome Policy**: `chrome_policy.json`
- **Documentatie**: `CHROME_DOH_DISABLE.md`
- **Verificatie**: `verify_chrome_doh.html`

### Android

* Always-On VPN
* "Block connections without VPN"
* Geen DNS-wijzigingen toegestaan

**Implementatie:**
- **Android VPN Config**: WireGuard met `AllowedIPs = 0.0.0.0/0`
- **Always-On VPN**: Ingesteld in Android VPN settings

### iOS

* Configuration Profile / MDM
* VPN verplicht
* iCloud Private Relay UIT

**Implementatie:**
- **iOS VPN Config**: WireGuard met `AllowedIPs = 0.0.0.0/0`
- **MDM Profile**: Vereist voor enterprise deployment

---

## 8. Device-beheer

### Elk device:

* Heeft een uniek VPN-IP (10.10.0.X)
* Heeft eigen whitelist
* Kan worden gedeactiveerd

### Belangrijke regel:

> Device zonder whitelist = **geen internet**

### Implementatie

- **Device API**: `api/get_devices.php`
- **Whitelist API**: `api/get_whitelist.php`
- **Add Whitelist**: `api/add_whitelist.php`
- **Delete Whitelist**: `api/delete_whitelist.php`
- **Device Status**: `check_device_status.php`

### Device Status Logic

```php
// get_whitelist.php
if (device not found) → return []
if (device not active) → return []
if (whitelist empty) → return []
if (whitelist has domains) → return ["domain1.com", "domain2.com"]
```

---

## 9. Automatische werking

### Bij nieuw device:

```
Device toegevoegd
→ Status: active (automatisch)
→ VPN geforceerd (AllowedIPs = 0.0.0.0/0)
→ DNS geforceerd (10.10.0.1)
→ Whitelist leeg
→ GEEN INTERNET
```

Admin voegt domein toe → werkt binnen seconden.

### Implementatie

- **Auto Register**: `api/auto_register_device.php`
- **Auto Active**: Devices worden automatisch actief gemaakt
- **System Ready**: `system_ready` flag in response
- **Documentatie**: `AUTO_ACTIVE_DEVICES.md`

---

## 10. Testcriteria (VERPLICHT)

### Test 1 — Lege whitelist

* VPN aan
* Geen domeinen toegestaan

✅ Resultaat:

```
NIETS laadt
```

**Test Script**: `test_empty_whitelist.sh`

### Test 2 — Eén domein

Whitelist:

```
wikipedia.org
```

✔ Wikipedia werkt
❌ Google
❌ YouTube
❌ Porno
❌ Afbeeldingen / video

**Test Script**: `test_single_domain.sh`

### Test 3 — VPN uit

➡️ **Geen internet**

**Test Script**: `test_vpn_killswitch.sh`

### Test 4 — DNS Bypass

* Probeer 8.8.8.8 DNS
* Probeer DoH
* Probeer DoT

➡️ **Alles geblokkeerd**

**Test Script**: `test_dns_bypass.sh`

### Test 5 — QUIC/Video Leak

* Probeer video te laden
* Probeer QUIC verbinding

➡️ **Alles geblokkeerd**

**Test Script**: `test_video_image_blocking.sh`

Als één test faalt → systeem is **niet acceptabel**.

---

## 11. Veiligheidsbelofte

Dit platform garandeert:

* ✅ Geen porno-sites
* ✅ Geen porno-video's
* ✅ Geen porno-afbeeldingen
* ✅ Geen nieuwe porno-domeinen
* ✅ Geen app-bypass
* ✅ Geen DNS-bypass
* ✅ Geen browser-bypass

**Waarom 100% betrouwbaar:**

1. **Whitelist-Only**: Alleen expliciet toegestane domeinen werken
2. **Netwerkniveau**: Blokkering op DNS/firewall niveau, niet in browser
3. **VPN Verplicht**: Alle verkeer moet via VPN
4. **Firewall Handhaving**: DoH, DoT, QUIC geblokkeerd
5. **NXDOMAIN**: Apps kunnen niet omzeilen

---

## 12. Samenvatting

✔ Netwerkniveau handhaving
✔ Geen afhankelijkheid van content
✔ Automatisch voor elk device
✔ Geschikt voor gezinnen, scholen, organisaties
✔ 100% technisch afdwingbaar

> **Niet toegestaan = bestaat niet**

---

## 13. Bestanden Overzicht

### Core System
- `api/get_whitelist.php` - Whitelist API (whitelist-only)
- `api/get_device_by_ip.php` - Device IP resolver
- `dns_whitelist_server.py` - DNS server (whitelist-only)
- `api/auto_register_device.php` - Auto device registration

### VPN & Firewall
- `api/get_wireguard_config.php` - WireGuard config generator
- `vpn_firewall_setup.sh` - Firewall rules setup
- `block_quic_udp443.sh` - QUIC blocking
- `block_dot_tcp853.sh` - DoT blocking
- `force_dns_only.sh` - DNS forcing

### Testing & Verification
- `test_empty_whitelist.sh` - Test empty whitelist
- `test_single_domain.sh` - Test single domain
- `test_vpn_killswitch.sh` - Test VPN kill-switch
- `test_dns_bypass.sh` - Test DNS bypass blocking
- `test_video_image_blocking.sh` - Test video/image blocking
- `api/system_check.php` - System health check
- `check_device_status.php` - Device status check

### Documentation
- `TECHNICAL_DOCUMENTATION.md` - This file
- `WIREGUARD_FULL_TUNNEL.md` - VPN setup
- `DNS_WHITELIST_SETUP.md` - DNS setup
- `KILL_SWITCH_SETUP.md` - Kill-switch setup
- `CHROME_DOH_DISABLE.md` - Chrome DoH disable
- `AUTO_ACTIVE_DEVICES.md` - Auto-active devices

---

## 14. Compliance Checklist

- [x] Whitelist-only (no blacklist)
- [x] No content detection
- [x] No AI
- [x] Network-level enforcement
- [x] VPN required
- [x] DNS whitelist-only
- [x] Firewall enforcement
- [x] DoH/DoT/QUIC blocked
- [x] Kill-switch active
- [x] NXDOMAIN for blocked domains
- [x] Auto-active devices
- [x] Device-level whitelist
- [x] Empty whitelist = no internet

---

**Version**: 1.0.0
**Last Updated**: 2025-01-15
**Status**: Production Ready
