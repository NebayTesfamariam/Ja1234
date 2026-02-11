# 🚫 App Blocking Setup - Pornografische Content via Apps Blokkeren

## 🎯 Doel

**Apps mogen GEEN pornografische content laden:**
- ✅ Geen direct IP toegang
- ✅ Geen alternatieve DNS
- ✅ Geen QUIC protocol
- ✅ Geen bypass methoden
- ✅ Alleen via VPN DNS → whitelist-only

---

## ✅ Installatie

### Op je VPN-server (Linux):

```bash
# 1. Upload het script naar je VPN server
# (of kopieer de inhoud van vpn_firewall_app_blocking.sh)

# 2. Maak het script uitvoerbaar
chmod +x vpn_firewall_app_blocking.sh

# 3. Run als root
sudo ./vpn_firewall_app_blocking.sh
```

---

## 🔒 Wat Wordt Geblokkeerd

### 1. DNS Forcing
- **Apps kunnen GEEN alternatieve DNS gebruiken**
- Alle DNS queries gaan naar VPN DNS (10.10.0.1)
- DoH (DNS-over-HTTPS) geblokkeerd
- DoT (DNS-over-TLS) geblokkeerd

### 2. QUIC Blocking
- **Apps kunnen GEEN QUIC gebruiken**
- QUIC (UDP 443) volledig geblokkeerd
- HTTP/3 (UDP 80) geblokkeerd
- Video streaming via QUIC geblokkeerd

### 3. Direct IP Access Blocking
- **Apps kunnen GEEN direct IP's gebruiken**
- Alleen verbindingen met Host header toegestaan
- Direct IP verbindingen geblokkeerd
- Bypass DNS via IP's onmogelijk

### 4. App Bypass Methods
- **mDNS (multicast DNS) geblokkeerd**
- **LLMNR (Link-Local) geblokkeerd**
- **NetBIOS geblokkeerd**
- Apps kunnen geen lokale DNS gebruiken

### 5. Kill-Switch
- **Geen internet zonder VPN**
- Als VPN disconnect → geen internet
- Apps kunnen niet om VPN heen

---

## 🧪 Test

### Test 1: Direct IP Access
```bash
# Op VPN client
curl https://185.xxx.xxx.xxx
# Moet FAILEN (direct IP geblokkeerd)
```

### Test 2: Alternative DNS
```bash
# Op VPN client
nslookup google.com 8.8.8.8
# Moet FAILEN (DNS forced)
```

### Test 3: QUIC Protocol
```bash
# Apps die QUIC gebruiken moeten FAILEN
# Video apps mogen niet laden via QUIC
```

### Test 4: Kill-Switch
```bash
# Disconnect VPN
# Internet moet NIET werken
```

---

## 📋 Verificatie

### Check Firewall Rules:

```bash
# Check DNS forcing
sudo iptables -S FORWARD | grep "dport 53"

# Check QUIC blocking
sudo iptables -S FORWARD | grep "udp.*443"

# Check DoT blocking
sudo iptables -S FORWARD | grep "tcp.*853"

# Check direct IP blocking
sudo iptables -S FORWARD | grep "Host:"
```

---

## ⚠️ Belangrijk

### Apps Kunnen Niet Bypassen:

1. **Direct IP toegang** → Geblokkeerd (geen Host header)
2. **Alternatieve DNS** → Geblokkeerd (alleen VPN DNS)
3. **QUIC protocol** → Geblokkeerd (UDP 443)
4. **DoH/DoT** → Geblokkeerd (TCP 443/853)
5. **Lokale DNS** → Geblokkeerd (mDNS, LLMNR, NetBIOS)
6. **Zonder VPN** → Geen internet (kill-switch)

---

## 🔧 Troubleshooting

### Als apps nog steeds content kunnen laden:

1. **Check firewall rules:**
   ```bash
   sudo iptables -S FORWARD | grep -E "443|853|53"
   ```

2. **Check DNS server:**
   ```bash
   ps aux | grep dns_whitelist_server
   ```

3. **Check VPN connectie:**
   ```bash
   wg show
   ```

4. **Test direct:**
   ```bash
   # Op VPN client
   curl -v https://pornhub.com
   # Moet FAILEN
   ```

---

## ✅ Resultaat

Na deze setup:
- ✅ Apps kunnen GEEN pornografische content laden
- ✅ Apps kunnen GEEN direct IP's gebruiken
- ✅ Apps kunnen GEEN alternatieve DNS gebruiken
- ✅ Apps kunnen GEEN QUIC gebruiken
- ✅ Alleen whitelisted domeinen werken
- ✅ Pornografische content is 100% geblokkeerd

---

**App blocking is nu actief!** 🚀
