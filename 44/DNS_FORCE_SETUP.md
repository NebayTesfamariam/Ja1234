# 🔒 DNS Forcing Setup (STAP 3)

## 🎯 Doel
Zorgen dat **VPN-clients uitsluitend jouw DNS** gebruiken.
Alle andere DNS (8.8.8.8, 1.1.1.1, DoH/DoT, ISP DNS) moet **falen**.

➡️ Dit is de **laatste verplichte stap** om **DNS écht te forceren** en het systeem definitief te sluiten.

---

## ✅ Installatie

### Op je VPN-server (Linux):

```bash
# 1. Upload het script naar je VPN server
# (of kopieer de inhoud van force_dns_only.sh)

# 2. Maak het script uitvoerbaar
chmod +x force_dns_only.sh

# 3. Run als root
sudo ./force_dns_only.sh
```

### Handmatig (als je geen script wilt):

```bash
# Vervang 10.10.0.0/24 met jouw VPN subnet als anders
# Vervang 10.10.0.1 met jouw VPN DNS resolver IP als anders

# Step 1: Allow DNS ONLY to VPN DNS resolver
sudo iptables -I FORWARD -s 10.10.0.0/24 -d 10.10.0.1 -p udp --dport 53 -j ACCEPT
sudo iptables -I FORWARD -s 10.10.0.0/24 -d 10.10.0.1 -p tcp --dport 53 -j ACCEPT

# Step 2: Block ALL other DNS queries
sudo iptables -I FORWARD -s 10.10.0.0/24 -p udp --dport 53 -j DROP
sudo iptables -I FORWARD -s 10.10.0.0/24 -p tcp --dport 53 -j DROP
```

---

## ✅ Verificatie

### Controleer of regels actief zijn:

```bash
sudo iptables -S FORWARD | grep "dport 53"
```

**Je moet zien:**
- **ACCEPT** regels voor jouw VPN DNS (10.10.0.1)
- **DROP** regels voor alle andere DNS

### Of gebruik het verificatie script:

```bash
sudo ./verify_dns_force.sh
```

---

## 🧪 Tests (Verplicht)

### Test A — Externe DNS (moet falen)

Op VPN-client:

```bash
nslookup google.com 8.8.8.8
```

**Verwacht:** Moet falen (timeout of connection refused)

---

### Test B — Jouw DNS (volgens whitelist)

Op VPN-client:

```bash
# Als wikipedia.org in whitelist staat:
nslookup wikipedia.org 10.10.0.1
```

**Verwacht:** Werkt (geeft IP adres)

```bash
# Als google.com NIET in whitelist staat:
nslookup google.com 10.10.0.1
```

**Verwacht:** NXDOMAIN (domain not found)

---

### Test C — Lege whitelist

1. **Zorg dat whitelist LEEG is** voor het device
2. **Probeer willekeurige site** in browser
3. **Probeer DNS query:**
   ```bash
   nslookup google.com 10.10.0.1
   ```

**Verwacht:** 
- NXDOMAIN voor alle queries
- Geen enkele site werkt

---

## 📋 Wat wordt geblokkeerd

### Toegestaan:
- ✅ DNS queries naar VPN DNS resolver (10.10.0.1)
- ✅ UDP port 53 naar VPN DNS
- ✅ TCP port 53 naar VPN DNS

### Geblokkeerd:
- ❌ DNS queries naar 8.8.8.8 (Google DNS)
- ❌ DNS queries naar 1.1.1.1 (Cloudflare DNS)
- ❌ DNS queries naar ISP DNS
- ❌ DNS queries naar andere DNS servers
- ❌ Alle UDP port 53 naar andere servers
- ❌ Alle TCP port 53 naar andere servers

---

## ⚠️ Belangrijk

- **Regels zijn tijdelijk** - ze verdwijnen na reboot
- **Om permanent te maken:** gebruik `iptables-persistent` of voeg toe aan `vpn_firewall_setup.sh`
- **Test altijd** na het toevoegen van regels
- **Regel volgorde is kritisch:** ACCEPT regels moeten VÓÓR DROP regels komen (gebruik `-I` voor INSERT)

---

## 🔧 Troubleshooting

### Als externe DNS nog werkt:

1. **Check regel volgorde:**
   ```bash
   sudo iptables -S FORWARD | grep "dport 53"
   ```
   ACCEPT regels moeten **bovenaan** staan (eerder in de lijst)

2. **Check of DROP regels actief zijn:**
   ```bash
   # Moet DROP regels tonen
   sudo iptables -S FORWARD | grep "DROP.*dport.*53"
   ```

3. **Check VPN subnet:**
   ```bash
   # Zorg dat VPN_SUBNET in script overeenkomt met jouw VPN
   # Standaard: 10.10.0.0/24
   ```

### Als VPN DNS niet werkt:

1. **Check ACCEPT regels:**
   ```bash
   sudo iptables -S FORWARD | grep "ACCEPT.*10.10.0.1.*53"
   ```

2. **Check VPN DNS IP:**
   ```bash
   # Zorg dat VPN_DNS in script overeenkomt met jouw DNS resolver
   # Standaard: 10.10.0.1
   ```

3. **Test DNS server direct:**
   ```bash
   # Op VPN server
   nslookup wikipedia.org 10.10.0.1
   # Moet werken als DNS server draait
   ```

---

## 📝 Notities

- DNS forcing is **kritisch** voor whitelist-only filtering
- Zonder deze forcing kunnen apps externe DNS gebruiken om filtering te omzeilen
- Combineer met QUIC blocking en DoT blocking voor volledige bescherming
- Regel volgorde is belangrijk: `-I` (INSERT) zet regels bovenaan voor hoge prioriteit

---

## 🔗 Gerelateerd

- **STAP 1:** QUIC/UDP 443 blocking (`block_quic_udp443.sh`)
- **STAP 2:** DNS-over-TLS blocking (`block_dot_tcp853.sh`)
- **Firewall Setup:** Volledige configuratie (`vpn_firewall_setup.sh`)

---

## ✅ Checklist

- [ ] Script uitgevoerd op VPN server
- [ ] ACCEPT regels actief voor VPN DNS
- [ ] DROP regels actief voor andere DNS
- [ ] Test A geslaagd: externe DNS faalt
- [ ] Test B geslaagd: VPN DNS werkt volgens whitelist
- [ ] Test C geslaagd: lege whitelist = niets werkt
