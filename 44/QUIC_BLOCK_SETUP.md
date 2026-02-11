# 🚫 QUIC/UDP 443 Blocking Setup (STAP 1)

## 🎯 Doel
Veel video's/thumbnails (ook porno) gebruiken **QUIC via UDP poort 443**.
Als QUIC nog aan staat, kan content soms "lekken" ondanks DNS.

➡️ We blokkeren QUIC **hard** voor VPN-clients.

---

## ✅ Installatie

### Op je VPN-server (Linux):

```bash
# 1. Upload het script naar je VPN server
# (of kopieer de inhoud van block_quic_udp443.sh)

# 2. Maak het script uitvoerbaar
chmod +x block_quic_udp443.sh

# 3. Run als root
sudo ./block_quic_udp443.sh
```

### Handmatig (als je geen script wilt):

```bash
# Vervang 10.10.0.0/24 met jouw VPN subnet als anders
sudo iptables -I FORWARD -s 10.10.0.0/24 -p udp --dport 443 -j DROP
sudo iptables -I FORWARD -s 10.10.0.0/24 -p udp --sport 443 -j DROP
```

---

## ✅ Verificatie

### Controleer of regels actief zijn:

```bash
sudo iptables -S FORWARD | grep 443
```

**Je moet zien:**
- Regels met `udp` en `443` en `DROP`
- Regels met `10.10.0.0/24` (of jouw VPN subnet)

### Of gebruik het verificatie script:

```bash
sudo ./verify_quic_block.sh
```

---

## 🧪 Test

### Op je device:

1. **VPN aan**
2. **Open een site met video/thumbnails** (bijv. YouTube of een willekeurige site)

**Wat je moet zien:**
- ✅ Video's laden niet (of veel trager)
- ✅ Thumbnails/streams lekken niet meer via UDP
- ✅ Alleen TCP/HTTPS werkt (als in whitelist)

---

## 📋 Wat wordt geblokkeerd

### Destination Port 443 (UDP)
- Blokkeert **inkomende** QUIC verbindingen
- Voorkomt dat video's/thumbnails via QUIC laden

### Source Port 443 (UDP)
- Blokkeert **uitgaande** QUIC verbindingen
- Voorkomt dat apps QUIC gebruiken om content te laden

---

## ⚠️ Belangrijk

- **Regels zijn tijdelijk** - ze verdwijnen na reboot
- **Om permanent te maken:** gebruik `iptables-persistent` of voeg toe aan `vpn_firewall_setup.sh`
- **Test altijd** na het toevoegen van regels

---

## 🔧 Troubleshooting

### Als regels niet werken:

1. **Check of regels actief zijn:**
   ```bash
   sudo iptables -S FORWARD | grep 443
   ```

2. **Check VPN subnet:**
   ```bash
   # Zorg dat VPN_SUBNET in script overeenkomt met jouw VPN
   # Standaard: 10.10.0.0/24
   ```

3. **Check regel volgorde:**
   ```bash
   # Regels met -I (INSERT) hebben prioriteit
   # Regels met -A (APPEND) komen later
   ```

### Als video's nog steeds laden:

1. **Check of QUIC echt geblokkeerd is:**
   ```bash
   # Op VPN client, test met tcpdump
   sudo tcpdump -i any udp port 443
   # Je zou GEEN UDP 443 verkeer moeten zien
   ```

2. **Check of content via TCP/HTTPS gaat:**
   - QUIC is UDP, HTTPS is TCP
   - Als content via TCP gaat, is dat normaal (als in whitelist)

---

## 📝 Notities

- QUIC blokkering is **kritisch** voor het stoppen van video-lekken
- Zonder deze blokkering kunnen video's/thumbnails soms "lekken" ondanks DNS filtering
- Combineer met DNS whitelist filtering voor volledige bescherming
