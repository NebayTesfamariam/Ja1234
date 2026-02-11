# 🚫 DNS-over-TLS (DoT, TCP 853) Blocking Setup (STAP 2)

## 🎯 Doel
Voorkomen dat apps/browsers **eigen DNS** gebruiken (DoT), wat DNS-filtering kan omzeilen en weer media kan laten laden.

➡️ We blokkeren DNS-over-TLS **hard** voor VPN-clients.

---

## ✅ Installatie

### Op je VPN-server (Linux):

```bash
# 1. Upload het script naar je VPN server
# (of kopieer de inhoud van block_dot_tcp853.sh)

# 2. Maak het script uitvoerbaar
chmod +x block_dot_tcp853.sh

# 3. Run als root
sudo ./block_dot_tcp853.sh
```

### Handmatig (als je geen script wilt):

```bash
# Vervang 10.10.0.0/24 met jouw VPN subnet als anders
sudo iptables -I FORWARD -s 10.10.0.0/24 -p tcp --dport 853 -j DROP
sudo iptables -I FORWARD -s 10.10.0.0/24 -p tcp --sport 853 -j DROP
```

---

## ✅ Verificatie

### Controleer of regels actief zijn:

```bash
sudo iptables -S FORWARD | grep 853
```

**Je moet zien:**
- Regels met `tcp` en `853` en `DROP`
- Regels met `10.10.0.0/24` (of jouw VPN subnet)

### Of gebruik het verificatie script:

```bash
sudo ./verify_dot_block.sh
```

---

## 🧪 Test

### Op VPN-client:

1. **VPN aan**
2. **Probeer DNS query naar externe DNS:**
   ```bash
   nslookup google.com 8.8.8.8
   ```
   **Moet falen** (DNS geforceerd naar VPN DNS)

3. **Probeer app/site die eerder nog "lek" had:**
   - Apps kunnen geen DoT meer gebruiken
   - Alle DNS moet via VPN DNS gaan

---

## 📋 Wat wordt geblokkeerd

### Destination Port 853 (TCP)
- Blokkeert **inkomende** DNS-over-TLS verbindingen
- Voorkomt dat apps DoT gebruiken om DNS filtering te omzeilen

### Source Port 853 (TCP)
- Blokkeert **uitgaande** DNS-over-TLS verbindingen
- Voorkomt dat apps DoT gebruiken om eigen DNS te gebruiken

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
   sudo iptables -S FORWARD | grep 853
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

### Als apps nog steeds DoT gebruiken:

1. **Check of DoT echt geblokkeerd is:**
   ```bash
   # Op VPN client, test met tcpdump
   sudo tcpdump -i any tcp port 853
   # Je zou GEEN TCP 853 verkeer moeten zien
   ```

2. **Check of DNS geforceerd is:**
   ```bash
   # Op VPN client
   nslookup google.com 8.8.8.8
   # Moet falen (DNS geforceerd naar VPN DNS)
   ```

---

## 📝 Notities

- DNS-over-TLS blokkering is **kritisch** voor het voorkomen van DNS-omzeiling
- Zonder deze blokkering kunnen apps DoT gebruiken om DNS filtering te omzeilen
- Combineer met DNS forcing en QUIC blocking voor volledige bescherming

---

## 🔗 Gerelateerd

- **STAP 1:** QUIC/UDP 443 blocking (`block_quic_udp443.sh`)
- **STAP 3:** (volgt)
- **Firewall Setup:** Volledige configuratie (`vpn_firewall_setup.sh`)
