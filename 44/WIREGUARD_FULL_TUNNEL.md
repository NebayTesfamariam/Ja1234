# 🔒 WireGuard Full-Tunnel Setup (STAP 3)

## 🎯 Doel
**AL het verkeer** (browser, apps, video's, afbeeldingen) **MOET door de VPN**.
Zonder dit blijven porno-video's en foto's altijd lekken.

---

## ✅ 3.1 WireGuard Client Configuratie (VERPLICHT)

Elke WireGuard client config **MOET exact** dit bevatten:

```ini
[Interface]
Address = 10.10.0.X/32
DNS = 10.10.0.1

[Peer]
AllowedIPs = 0.0.0.0/0
Endpoint = jouw-vpn-server:51820
PersistentKeepalive = 25
```

### ❗ Belangrijk

- ✅ `AllowedIPs = 0.0.0.0/0` → **VERPLICHT** (full-tunnel)
- ✅ `DNS = 10.10.0.1` → jouw DNS server
- ✅ `PersistentKeepalive = 25` → houdt verbinding actief

### ❌ NIET Toegestaan

- ❌ `AllowedIPs = 10.10.0.0/24` → split-tunnel (NIET toegestaan)
- ❌ `AllowedIPs = 192.168.0.0/16` → split-tunnel (NIET toegestaan)
- ❌ Geen `AllowedIPs` → standaard split-tunnel (NIET toegestaan)

---

## ✅ 3.2 Configuratie Downloaden

### Via API Endpoint

```
GET /api/get_wireguard_config.php?device_id=123
```

Dit genereert een WireGuard config met:
- ✅ `AllowedIPs = 0.0.0.0/0` (full-tunnel)
- ✅ `DNS = 10.10.0.1`
- ✅ Correcte endpoint en keys

### Handmatig Configureren

Als je handmatig configureert, zorg dat:

1. **AllowedIPs** is **exact** `0.0.0.0/0`
2. **DNS** is ingesteld op `10.10.0.1`
3. **Endpoint** is correct (jouw VPN server)

---

## ✅ 3.3 Configuratie Valideren

### Via API Endpoint

```
POST /api/validate_wireguard_config.php
Content-Type: text/plain

[Interface]
...
[Peer]
AllowedIPs = 0.0.0.0/0
...
```

Response:
```json
{
  "valid": true,
  "has_full_tunnel": true,
  "has_dns": true,
  "message": "✅ Config is valid - full-tunnel enabled"
}
```

### Handmatig Controleren

1. Open je WireGuard config bestand
2. Zoek naar `[Peer]` sectie
3. Controleer dat `AllowedIPs = 0.0.0.0/0` erin staat
4. Controleer dat `DNS = 10.10.0.1` in `[Interface]` staat

---

## ✅ 3.4 Test: Is Het Écht Full-Tunnel?

### Test 1: IP Check

1. Zet VPN **AAN**
2. Ga naar: https://whatismyipaddress.com/
3. Het IP moet dat van **je VPN-server** zijn

👉 Zie je nog je **eigen ISP IP**?
➡️ Dan is het **GEEN full-tunnel** → porno kan lekken.

### Test 2: Verkeer Routing

1. Zet VPN **AAN**
2. Open browser console (F12)
3. Ga naar een willekeurige website
4. Check Network tab → alle requests moeten via VPN IP gaan

### Test 3: DNS Check

1. Zet VPN **AAN**
2. Open terminal/command prompt
3. Voer uit: `nslookup google.com`
4. DNS server moet `10.10.0.1` zijn

---

## ✅ 3.5 Troubleshooting

### Probleem: VPN werkt maar IP is niet veranderd

**Oorzaak:** `AllowedIPs` is niet `0.0.0.0/0`

**Oplossing:**
1. Open WireGuard config
2. Zoek `[Peer]` sectie
3. Verander `AllowedIPs` naar `0.0.0.0/0`
4. Herstart WireGuard

### Probleem: Geen internet wanneer VPN aan staat

**Oorzaak:** VPN server is niet bereikbaar of config is incorrect

**Oplossing:**
1. Check `Endpoint` in config
2. Check of VPN server draait
3. Check firewall regels

### Probleem: DNS werkt niet

**Oorzaak:** DNS is niet ingesteld of incorrect

**Oplossing:**
1. Check `DNS = 10.10.0.1` in `[Interface]` sectie
2. Herstart WireGuard
3. Test met `nslookup google.com`

---

## ✅ 3.6 Controlelijst

Voor je verder gaat, controleer:

- ❓ Staat `AllowedIPs = 0.0.0.0/0` in config? → **JA**
- ❓ Gaat AL het verkeer via VPN? → **JA**
- ❓ Zie je VPN-IP bij "what is my ip"? → **JA**
- ❓ DNS is ingesteld op `10.10.0.1`? → **JA**

Als één antwoord **NEE** is → **STOP, fix eerst**.

---

## 🔧 VPN Server Configuratie

### Server-side (WireGuard Server)

De VPN server moet ook correct geconfigureerd zijn:

```ini
[Interface]
Address = 10.10.0.1/24
ListenPort = 51820
PrivateKey = SERVER_PRIVATE_KEY

# Forwarding enabled
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT
PostUp = iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT
PostDown = iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE

[Peer]
# Client 1
PublicKey = CLIENT_PUBLIC_KEY_1
AllowedIPs = 10.10.0.2/32

[Peer]
# Client 2
PublicKey = CLIENT_PUBLIC_KEY_2
AllowedIPs = 10.10.0.3/32
```

### DNS Server op VPN

De DNS server op `10.10.0.1` moet:
- Whitelist API aanroepen: `/api/get_whitelist.php?device_id=X`
- Alleen toegestane domeinen resolven
- Andere domeinen blokkeren (NXDOMAIN)

---

## ⚠️ Belangrijk

**Zonder full-tunnel (`AllowedIPs = 0.0.0.0/0`):**
- ❌ Porno-video's kunnen lekken
- ❌ Porno-foto's kunnen lekken
- ❌ Apps kunnen direct internet gebruiken
- ❌ Browser kan direct internet gebruiken

**Met full-tunnel (`AllowedIPs = 0.0.0.0/0`):**
- ✅ ALLE verkeer gaat via VPN
- ✅ ALLE verkeer wordt gefilterd
- ✅ Geen lekken mogelijk

---

**Volgende stap:** DNS server configureren met whitelist filtering
