# 🔒 Kill-Switch + DNS Forcing Setup (STAP 5)

## 🎯 Doel
Zorgen dat:
- **NIEMAND** een andere DNS kan gebruiken
- **NIETS** buiten de VPN om kan
- DNS-over-HTTPS / QUIC geen porno-lekken meer veroorzaken

Na deze stap:
> VPN uit = **geen internet**  
> Andere DNS = **werkt niet**

---

## ✅ 5.1 DNS Forceren (VERPLICHT)

### Wat Het Doet

Op je **VPN-server** wordt afgedwongen dat VPN-clients **alleen jouw DNS** (`10.10.0.1`) mogen gebruiken.

### Firewall Regels

```bash
# Allow DNS ONLY to VPN DNS server
iptables -A OUTPUT -p udp --dport 53 -d 10.10.0.1 -j ACCEPT
iptables -A OUTPUT -p tcp --dport 53 -d 10.10.0.1 -j ACCEPT

# Block ALL other DNS queries
iptables -A OUTPUT -p udp --dport 53 -j DROP
iptables -A OUTPUT -p tcp --dport 53 -j DROP

# For VPN clients: block other DNS servers
iptables -A FORWARD -s 10.10.0.0/24 -p udp --dport 53 ! -d 10.10.0.1 -j DROP
iptables -A FORWARD -s 10.10.0.0/24 -p tcp --dport 53 ! -d 10.10.0.1 -j DROP
```

### Test (VERPLICHT)

Op een VPN-client:

```bash
# Dit moet FALEN
nslookup google.com 8.8.8.8
```

➡️ **Resultaat moet zijn:**

```
;; connection timed out; no servers could be reached
```

Als dit nog werkt → DNS is **NIET geforceerd** ❌

---

## ✅ 5.2 Kill-Switch: Geen VPN = Geen Internet

### Wat Het Doet

Dit voorkomt **alle bypass**. Verkeer van VPN-clients mag **alleen via de VPN-interface**.

### Firewall Regels

```bash
# Allow traffic from VPN subnet ONLY through VPN interface
iptables -A FORWARD -s 10.10.0.0/24 -o wg0 -j ACCEPT
iptables -A FORWARD -s 10.10.0.0/24 ! -o wg0 -j DROP

# Block VPN clients from accessing internet directly
iptables -A FORWARD -s 10.10.0.0/24 -o eth0 -j DROP
```

### Resultaat

- VPN aan → verkeer volgens whitelist
- VPN uit → **0 internet**

### Test (VERPLICHT)

1. Zet VPN **UIT**
2. Probeer een website te bezoeken

➡️ **Niets mag laden**

Als websites nog laden zonder VPN → kill-switch faalt ❌

---

## ✅ 5.3 Blokkeer DNS-Omzeiling (Porno-Lekken!)

### Wat Moet Geblokkeerd Worden

Porno-video's en thumbnails gebruiken vaak:

- **QUIC** (UDP 443) - Google's protocol voor snelle video's
- **DNS-over-HTTPS** (DoH) - DNS via HTTPS
- **DNS-over-TLS** (DoT) - DNS via TLS (TCP 853)

### Firewall Regels

```bash
# Block DNS-over-HTTPS (DoH) - common providers
iptables -A OUTPUT -p tcp --dport 443 -d 1.1.1.1 -j DROP  # Cloudflare
iptables -A OUTPUT -p tcp --dport 443 -d 8.8.8.8 -j DROP  # Google
iptables -A FORWARD -s 10.10.0.0/24 -p tcp --dport 443 -d 1.1.1.1 -j DROP
iptables -A FORWARD -s 10.10.0.0/24 -p tcp --dport 443 -d 8.8.8.8 -j DROP

# Block DNS-over-TLS (DoT) - TCP port 853
iptables -A OUTPUT -p tcp --dport 853 -j DROP
iptables -A FORWARD -s 10.10.0.0/24 -p tcp --dport 853 -j DROP

# Block QUIC (UDP 443) - used for bypassing filters
iptables -A OUTPUT -p udp --dport 443 -j DROP
iptables -A FORWARD -s 10.10.0.0/24 -p udp --dport 443 -j DROP
```

### Test (VERPLICHT)

- Video's mogen niet "lekken"
- Afbeeldingen laden niet buiten whitelist
- Thumbnails werken niet zonder whitelist

Als thumbnails of video's nog laden → deze stap faalt ❌

---

## ✅ 5.4 Installatie

### Automatisch (Aanbevolen)

```bash
# 1. Download script
# (Script is al in je project: vpn_firewall_setup.sh)

# 2. Maak uitvoerbaar
chmod +x vpn_firewall_setup.sh

# 3. Edit configuratie (regels 9-12)
nano vpn_firewall_setup.sh
# Pas aan:
#   VPN_INTERFACE="wg0"           # Jouw WireGuard interface
#   VPN_SUBNET="10.10.0.0/24"     # Jouw VPN subnet
#   VPN_DNS="10.10.0.1"           # Jouw DNS server
#   EXTERNAL_INTERFACE="eth0"      # Jouw externe interface

# 4. Run script (als root)
sudo ./vpn_firewall_setup.sh
```

### Handmatig

Zie firewall regels hierboven en voer ze handmatig uit op je VPN server.

---

## ✅ 5.5 Testen (VERPLICHT)

### Test 1: DNS Forcing

```bash
# Op VPN client
nslookup google.com 8.8.8.8
```

➡️ **Moet falen** (timeout)

### Test 2: Kill-Switch

1. Zet VPN **UIT**
2. Probeer website te bezoeken

➡️ **Moet falen** (geen internet)

### Test 3: DNS-over-HTTPS

```bash
# Op VPN client
curl -H "accept: application/dns-json" "https://1.1.1.1/dns-query?name=google.com"
```

➡️ **Moet falen** (geblokkeerd)

### Test 4: QUIC

Probeer YouTube video te laden (gebruikt QUIC)

➡️ **Moet falen** (QUIC geblokkeerd)

---

## ✅ 5.6 Troubleshooting

### Probleem: Script faalt met "command not found"

**Oplossing:**
```bash
# Installeer iptables
apt-get update
apt-get install iptables
```

### Probleem: Interface niet gevonden

**Oplossing:**
1. Check interface naam: `ip addr` of `ifconfig`
2. Pas `VPN_INTERFACE` aan in script
3. Pas `EXTERNAL_INTERFACE` aan in script

### Probleem: Regels worden niet opgeslagen

**Oplossing:**
```bash
# Installeer iptables-persistent
apt-get install iptables-persistent

# Sla regels op
iptables-save > /etc/iptables/rules.v4
```

### Probleem: Geen internet na script

**Oplossing:**
```bash
# Verwijder alle regels
sudo ./vpn_firewall_remove.sh

# Of handmatig:
sudo iptables -F
sudo iptables -P INPUT ACCEPT
sudo iptables -P FORWARD ACCEPT
sudo iptables -P OUTPUT ACCEPT
```

---

## ✅ 5.7 Controlelijst

Voor je verder gaat, controleer:

- ❓ Werkt DNS naar 8.8.8.8 nog? → **NEE**
- ❓ Werkt internet zonder VPN? → **NEE**
- ❓ Kan een app DNS omzeilen? → **NEE**
- ❓ Werkt DNS-over-HTTPS? → **NEE**
- ❓ Werkt QUIC? → **NEE**
- ❓ Werkt DNS-over-TLS? → **NEE**

Als één antwoord **JA** is → **STOP**, eerst fixen.

---

## ⚠️ Belangrijk

### Voor Productie

1. **Test eerst** op test server
2. **Backup** bestaande firewall regels
3. **Monitor** logs na implementatie
4. **Test** alle functionaliteit

### Veiligheid

- Script verwijdert **alle** bestaande firewall regels
- Zorg dat je **SSH toegang** behoudt
- Test **lokaal** voordat je remote server configureert

### Persistente Regels

Regels worden **niet automatisch** opgeslagen. Zorg dat je:

```bash
# Installeer iptables-persistent
apt-get install iptables-persistent

# Sla regels op
iptables-save > /etc/iptables/rules.v4
```

---

## 🔧 Geavanceerde Configuratie

### Extra DNS Providers Blokkeren

Voeg toe aan script:

```bash
# Quad9
iptables -A OUTPUT -p tcp --dport 443 -d 9.9.9.9 -j DROP

# OpenDNS
iptables -A OUTPUT -p tcp --dport 443 -d 208.67.222.222 -j DROP
```

### Logging Toevoegen

```bash
# Log geblokkeerde DNS queries
iptables -A FORWARD -s 10.10.0.0/24 -p udp --dport 53 ! -d 10.10.0.1 -j LOG --log-prefix "BLOCKED_DNS: "
iptables -A FORWARD -s 10.10.0.0/24 -p udp --dport 53 ! -d 10.10.0.1 -j DROP
```

---

**Na deze stap:** Systeem is volledig beveiligd tegen bypass. Alleen whitelisted domeinen werken, en alleen via VPN.
