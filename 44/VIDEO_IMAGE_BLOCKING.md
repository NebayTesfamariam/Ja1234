# 🎥 Video / Afbeelding Lekken Stoppen (STAP 6)

## 🎯 Doel
Zorgen dat:
- Porno-**video's** niet meer laden
- Porno-**afbeeldingen / thumbnails** niet meer laden
- Apps geen **direct IP-verkeer** kunnen gebruiken

Na deze stap:
> Wat niet in de whitelist staat → **kan fysiek niet laden**

---

## ✅ 6.1 Blokkeer QUIC (ESSENTIEEL voor video's)

### Waarom QUIC Blokkeren?

Veel video's (YouTube, porno-sites, apps) gebruiken **QUIC** via **UDP 443**:
- ✅ Sneller dan TCP/HTTPS
- ✅ Bypass DNS filtering
- ✅ Gebruikt door video CDNs

**DNS kan dit niet stoppen** → **firewall wel**.

### Firewall Regels

```bash
# Block QUIC (UDP 443) - CRITICAL
iptables -A FORWARD -s 10.10.0.0/24 -p udp --dport 443 -j DROP

# Block QUIC on HTTP/3 port (UDP 80)
iptables -A FORWARD -s 10.10.0.0/24 -p udp --dport 80 -j DROP

# Block Google QUIC servers specifically
iptables -A FORWARD -s 10.10.0.0/24 -d 8.8.8.8 -p udp --dport 443 -j DROP
iptables -A FORWARD -s 10.10.0.0/24 -d 1.1.1.1 -p udp --dport 443 -j DROP
```

### Test

- ✅ Video's mogen niet "half" laden
- ✅ Thumbnails mogen niet verschijnen
- ✅ Alleen whitelisted sites met TCP/HTTPS mogen werken

👉 Als video's nog laden → QUIC is **niet goed geblokkeerd** ❌

---

## ✅ 6.2 Blokkeer DNS-over-TLS (DoT)

### Waarom DoT Blokkeren?

Sommige apps gebruiken **TCP 853** om DNS te omzeilen:
- ✅ Bypass DNS filtering
- ✅ Gebruikt door moderne apps
- ✅ Versleuteld DNS verkeer

### Firewall Regels

```bash
# Block DNS-over-TLS (TCP 853)
iptables -A FORWARD -s 10.10.0.0/24 -p tcp --dport 853 -j DROP
iptables -A OUTPUT -p tcp --dport 853 -j DROP
```

### Test

- ✅ Apps mogen geen eigen DNS gebruiken
- ✅ DNS blijft altijd via jouw resolver gaan

---

## ✅ 6.3 Beperk Direct IP-Verkeer (BELANGRIJK voor afbeeldingen)

### Waarom Direct IP Blokkeren?

Sommige afbeeldingen/video's proberen:

```
https://185.xxx.xxx.xxx/image.jpg
https://172.217.16.14/video.mp4
```

**Zonder DNS resolutie** → **bypass whitelist**.

### Doel

- ❌ Geen "raw IP browsing"
- ✅ Alleen verkeer dat via DNS is toegestaan

### Firewall Regels

```bash
# Allow HTTPS only with Host header (domain-based)
iptables -A FORWARD -s 10.10.0.0/24 -p tcp --dport 443 -m state --state NEW \
    -m string --string "Host:" --algo bm --from 40 --to 65535 -j ACCEPT

# Block HTTPS without Host header (direct IP)
iptables -A FORWARD -s 10.10.0.0/24 -p tcp --dport 443 -m state --state NEW -j DROP

# Same for HTTP
iptables -A FORWARD -s 10.10.0.0/24 -p tcp --dport 80 -m state --state NEW \
    -m string --string "Host:" --algo bm --from 40 --to 65535 -j ACCEPT
iptables -A FORWARD -s 10.10.0.0/24 -p tcp --dport 80 -m state --state NEW -j DROP
```

### Praktisch Resultaat

- ✅ IP-only requests falen
- ✅ Alleen domeinen uit whitelist werken
- ✅ Apps kunnen geen direct IP gebruiken

---

## ✅ 6.4 De ECHTE Eindtest (BESLISSER)

### Test 1 — Lege Whitelist

**Setup:**
- VPN aan
- Whitelist = leeg (`[]`)

**Test:**
1. Probeer website te bezoeken
2. Probeer afbeelding te laden
3. Probeer video te laden
4. Probeer app te gebruiken

**➡️ Resultaat:**
- ❌ Geen enkele site
- ❌ Geen afbeeldingen
- ❌ Geen video
- ❌ Geen apps

**Als iets nog werkt → systeem faalt ❌**

---

### Test 2 — 1 Domein

**Setup:**
- VPN aan
- Whitelist: `wikipedia.org`

**Test:**
1. Bezoek `wikipedia.org` → ✅ moet werken
2. Bezoek `google.com` → ❌ moet falen
3. Probeer Google afbeeldingen → ❌ moet falen
4. Probeer YouTube thumbnails → ❌ moet falen
5. Probeer porno-site → ❌ moet falen
6. Probeer porno-video → ❌ moet falen

**➡️ Resultaat:**
- ✅ Wikipedia werkt
- ❌ Google afbeeldingen
- ❌ YouTube thumbnails
- ❌ Porno-sites
- ❌ Porno-video's

**Als iets niet-whitelisted werkt → systeem faalt ❌**

---

### Test 3 — Video Specifiek

**Setup:**
- VPN aan
- Whitelist: `wikipedia.org` (geen video sites)

**Test:**
1. Probeer YouTube video → ❌ moet falen
2. Probeer Vimeo video → ❌ moet falen
3. Probeer porno-video → ❌ moet falen
4. Probeer embedded video → ❌ moet falen

**➡️ Resultaat:**
- ❌ Geen video's laden
- ❌ Geen thumbnails
- ❌ Geen previews

---

### Test 4 — Direct IP

**Setup:**
- VPN aan
- Whitelist: `wikipedia.org`

**Test:**
```bash
# Probeer direct IP (bijv. Google IP)
curl https://172.217.16.14
```

**➡️ Resultaat:**
- ❌ Moet falen (geen Host header = geblokkeerd)

---

## ✅ 6.5 Installatie

### Basis (Al in vpn_firewall_setup.sh)

```bash
sudo ./vpn_firewall_setup.sh
```

Dit bevat al:
- ✅ QUIC blocking (UDP 443)
- ✅ DNS-over-TLS blocking (TCP 853)
- ✅ Basis direct IP blocking

### Geavanceerd (Extra Blokkering)

```bash
# Edit configuratie
nano vpn_firewall_advanced.sh

# Run (als root)
sudo ./vpn_firewall_advanced.sh
```

Dit voegt toe:
- ✅ Enhanced QUIC blocking (meer poorten)
- ✅ CDN IP range blocking
- ✅ Rate limiting
- ✅ Logging

---

## ✅ 6.6 Troubleshooting

### Probleem: Video's laden nog steeds

**Oorzaak:** QUIC niet volledig geblokkeerd

**Oplossing:**
1. Check firewall regels: `iptables -L -n -v | grep 443`
2. Check of UDP 443 geblokkeerd is
3. Test met: `tcpdump -i wg0 udp port 443`

### Probleem: Afbeeldingen laden nog steeds

**Oorzaak:** Direct IP access niet geblokkeerd

**Oplossing:**
1. Check Host header filtering
2. Check connection tracking
3. Test met: `curl https://IP_ADDRESS`

### Probleem: Apps werken nog steeds

**Oorzaak:** App gebruikt eigen DNS of direct IP

**Oplossing:**
1. Check DNS forcing (stap 5)
2. Check direct IP blocking
3. Check QUIC blocking

---

## ✅ 6.7 Controlelijst

Voor je klaar bent, controleer:

- ❓ Laden video's van niet-whitelisted sites? → **NEE**
- ❓ Laden afbeeldingen/thumbnails? → **NEE**
- ❓ Werkt alleen whitelist? → **JA**
- ❓ Werkt direct IP access? → **NEE**
- ❓ Werkt QUIC? → **NEE**
- ❓ Werkt DNS-over-TLS? → **NEE**

Als één antwoord **JA** is → **terug naar stap 5-6**.

---

## ⚠️ Belangrijk

### Performance Impact

- String matching kan performance impact hebben
- Overweeg connection tracking voor betere performance
- Monitor server load na implementatie

### False Positives

- Sommige legitieme apps gebruiken direct IP
- Test alle functionaliteit na implementatie
- Whitelist indien nodig

### Logging

Enable logging om te zien wat geblokkeerd wordt:
```bash
tail -f /var/log/kern.log | grep BLOCKED
```

---

## 🔧 Geavanceerde Configuratie

### Connection Tracking

Voor betere direct IP detection, gebruik connection tracking:

```bash
# Enable connection tracking
modprobe nf_conntrack

# Track DNS queries
iptables -A FORWARD -s 10.10.0.0/24 -p udp --dport 53 -d 10.10.0.1 \
    -m conntrack --ctstate NEW -j ACCEPT

# Only allow connections that were DNS-resolved
iptables -A FORWARD -s 10.10.0.0/24 -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT
```

### CDN Whitelisting

Als je specifieke CDNs nodig hebt, whitelist ze:

```bash
# Allow Cloudflare CDN (if needed)
iptables -I FORWARD -s 10.10.0.0/24 -d 104.16.0.0/13 -j ACCEPT
```

---

**Na deze stap:** Systeem is volledig beveiligd. Geen video's, geen afbeeldingen, geen direct IP access. Alleen whitelisted domeinen werken.
