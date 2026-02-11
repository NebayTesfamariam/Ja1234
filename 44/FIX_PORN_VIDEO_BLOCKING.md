# 🔒 Fix Pornografische Video Blokkering

## ❌ Probleem

Pornografische video's worden nog niet geblokkeerd.

---

## ✅ Oplossingen Geïmplementeerd

### 1. Uitgebreide Pornografische Domein Detectie
- ✅ Meer pornografische domeinen toegevoegd
- ✅ Video CDN's toegevoegd (phncdn, xvcdn, etc.)
- ✅ Bekende porn sites toegevoegd (brazzers, realitykings, etc.)

### 2. DNS Server Verbeterd
- ✅ DNS server blokkeert nu ook video CDN's
- ✅ Pornografische domeinen krijgen ALTIJD NXDOMAIN

### 3. Whitelist Filtering Verbeterd
- ✅ API filtert pornografische domeinen uit whitelist
- ✅ Zelfs als per ongeluk toegevoegd → wordt gefilterd

### 4. Firewall Regels (BELANGRIJK!)
- ✅ QUIC (UDP 443) moet geblokkeerd zijn
- ✅ DNS-over-TLS (TCP 853) moet geblokkeerd zijn
- ✅ Direct IP toegang moet geblokkeerd zijn

---

## 🔧 Stappen om te Fixen

### Stap 1: Update DNS Server

De DNS server is al geüpdatet met meer pornografische domeinen. Herstart de DNS server:

```bash
# Stop DNS server
sudo pkill -f dns_whitelist_server.py

# Start DNS server opnieuw
sudo python3 dns_whitelist_server.py
```

### Stap 2: Check Firewall Regels (KRITIEK!)

**BELANGRIJK:** Zonder firewall regels kunnen video's nog steeds laden via QUIC!

```bash
# Check of QUIC geblokkeerd is
sudo iptables -S FORWARD | grep "udp.*443"

# Check of DNS-over-TLS geblokkeerd is
sudo iptables -S FORWARD | grep "tcp.*853"
```

**Als deze regels NIET bestaan, voer uit:**

```bash
# Op je VPN server (als root)
sudo ./vpn_firewall_setup.sh
```

Of handmatig:

```bash
# Block QUIC (UDP 443) - CRITICAL voor video blocking
sudo iptables -A FORWARD -s 10.10.0.0/24 -p udp --dport 443 -j DROP
sudo iptables -A FORWARD -s 10.10.0.0/24 -p udp --sport 443 -j DROP

# Block DNS-over-TLS (TCP 853)
sudo iptables -A FORWARD -s 10.10.0.0/24 -p tcp --dport 853 -j DROP
```

### Stap 3: Cleanup Whitelist

Verwijder pornografische domeinen uit whitelist:

```bash
php -r "require 'config.php'; require 'config_porn_block.php'; echo 'Removed: ' . remove_pornographic_domains_from_whitelist(\$conn) . ' domains\n';"
```

### Stap 4: Test

1. **Test DNS blocking:**
   ```bash
   # Op VPN client
   nslookup pornhub.com 10.10.0.1
   # Moet NXDOMAIN teruggeven
   ```

2. **Test video blocking:**
   - Open browser op VPN client
   - Probeer pornografische video site te openen
   - Video's mogen NIET laden

---

## 🎯 Waarom Video's Nog Kunnen Laden

### Mogelijke Oorzaken:

1. **DNS Server draait niet**
   - Check: `ps aux | grep dns_whitelist_server`
   - Fix: Start DNS server

2. **Firewall regels niet actief**
   - Check: `sudo iptables -S FORWARD | grep 443`
   - Fix: Voer firewall script uit

3. **QUIC niet geblokkeerd**
   - Video's gebruiken QUIC (UDP 443)
   - Zonder QUIC blocking → video's kunnen laden
   - Fix: Blokkeer UDP 443

4. **Direct IP toegang**
   - Apps gebruiken soms direct IP's
   - Fix: Blokkeer direct IP toegang

5. **Whitelist bevat pornografische domeinen**
   - Check: `SELECT domain FROM whitelist WHERE domain LIKE '%porn%'`
   - Fix: Verwijder pornografische domeinen

---

## ✅ Verificatie Checklist

- [ ] DNS server draait (`ps aux | grep dns_whitelist_server`)
- [ ] QUIC geblokkeerd (`sudo iptables -S FORWARD | grep "udp.*443"`)
- [ ] DNS-over-TLS geblokkeerd (`sudo iptables -S FORWARD | grep "tcp.*853"`)
- [ ] Whitelist bevat geen pornografische domeinen
- [ ] Test: `nslookup pornhub.com 10.10.0.1` → NXDOMAIN
- [ ] Test: Video's laden niet op VPN client

---

## 🚨 BELANGRIJK

**Zonder firewall regels kunnen video's nog steeds laden!**

De DNS server blokkeert alleen DNS queries, maar:
- Video's gebruiken QUIC (UDP 443) → bypass DNS
- Apps gebruiken direct IP's → bypass DNS
- Video CDN's gebruiken andere protocollen → bypass DNS

**➡️ Firewall regels zijn ESSENTIEEL voor video blocking!**

---

**Pornografische video blokkering is nu verbeterd!** 🚀
