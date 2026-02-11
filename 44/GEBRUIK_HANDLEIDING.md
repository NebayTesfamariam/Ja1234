# 📖 Gebruik Handleiding - Stap voor Stap

## 🚀 Quick Start (5 Minuten)

### Stap 1: Database Setup
```bash
# Open in browser:
http://localhost/44/setup_database.php
```
✅ Database wordt automatisch aangemaakt met alle tabellen

---

### Stap 2: Start DNS Server
```bash
# In terminal:
cd /Applications/XAMPP/xamppfiles/htdocs/44
python3 dns_whitelist_server.py
```
✅ DNS server draait nu op poort 53 (vereist root/sudo)

**Of gebruik het start script:**
```bash
chmod +x start_dns_server.sh
sudo ./start_dns_server.sh
```

---

### Stap 3: Setup Firewall (VPN Server)
```bash
# Op je VPN server (Linux):
chmod +x vpn_firewall_setup.sh
sudo ./vpn_firewall_setup.sh
```
✅ Firewall regels worden ingesteld voor DNS forcing en kill-switch

---

### Stap 4: Abonnement Aansluiten
1. Open: `http://localhost/44/subscribe.html`
2. Kies een plan (Basic, Family, Premium)
3. Vul je email en wachtwoord in
4. Klik op "Abonnement Aansluiten"

✅ **Direct na aanmelding:**
- Abonnement is direct actief
- Device wordt automatisch aangemaakt
- Pornografische content wordt meteen geblokkeerd

---

### Stap 5: WireGuard Config Downloaden
1. Log in: `http://localhost/44/public/index.html`
2. Ga naar "Devices"
3. Klik op "Download WireGuard Config" bij je device
4. Importeer config in WireGuard app

✅ **VPN verbinding is nu actief**

---

## 📱 Device Setup

### iPhone/iPad
1. Installeer WireGuard app uit App Store
2. Open WireGuard app
3. Klik op "+" → "Create from file or archive"
4. Selecteer de gedownloade `.conf` file
5. Activeer de VPN verbinding

✅ **Pornografische content wordt nu geblokkeerd**

### Android
1. Installeer WireGuard app uit Google Play
2. Open WireGuard app
3. Klik op "+" → "Create from file or archive"
4. Selecteer de gedownloade `.conf` file
5. Activeer de VPN verbinding

✅ **Pornografische content wordt nu geblokkeerd**

### Windows/Mac/Linux
1. Installeer WireGuard client
   - Windows: https://www.wireguard.com/install/
   - Mac: `brew install wireguard-tools`
   - Linux: `sudo apt install wireguard` of `sudo yum install wireguard-tools`
2. Importeer de `.conf` file
3. Activeer de VPN verbinding

✅ **Pornografische content wordt nu geblokkeerd**

---

## 🔧 Admin Setup (Optioneel)

### Super Admin Aanmaken
```bash
# Open in browser:
http://localhost/44/register_super_admin.html
```

### Admin Login
```bash
# Open in browser:
http://localhost/44/admin/index.html
```

---

## ✅ Verificatie

### Test 1: Abonnement Actief?
```bash
# Log in en check dashboard
http://localhost/44/public/index.html
```
✅ Zie je "Abonnement: Actief" → ✅ Werkt

### Test 2: Device Actief?
```bash
# In dashboard, check devices
```
✅ Zie je je device met status "Actief" → ✅ Werkt

### Test 3: Pornografische Content Geblokkeerd?
```bash
# Probeer pornografische site te bezoeken (bijv. pornhub.com)
# Met VPN verbinding actief
```
✅ Site laadt niet → ✅ Werkt

### Test 4: Whitelist Werkt?
```bash
# Voeg een domein toe aan whitelist (bijv. wikipedia.org)
# Probeer site te bezoeken
```
✅ Site laadt wel → ✅ Werkt

---

## 🛠️ Troubleshooting

### Probleem: DNS Server Start Niet
**Oplossing:**
```bash
# Check of poort 53 beschikbaar is
sudo lsof -i :53

# Start met sudo
sudo python3 dns_whitelist_server.py
```

### Probleem: VPN Verbindt Niet
**Oplossing:**
1. Check WireGuard config: `AllowedIPs = 0.0.0.0/0`
2. Check DNS: `DNS = 10.10.0.1`
3. Check firewall op VPN server

### Probleem: Pornografische Content Laadt Nog Steeds
**Oplossing:**
1. Check of VPN verbinding actief is
2. Check of device status "Actief" is
3. Check of abonnement actief is
4. Check DNS: `dig @10.10.0.1 pornhub.com` → moet NXDOMAIN geven

### Probleem: Geen Internet Toegang
**Oplossing:**
1. Check whitelist: Voeg domeinen toe (bijv. google.com, wikipedia.org)
2. Check DNS server: Moet draaien op 10.10.0.1
3. Check firewall: Moet DNS verkeer toestaan

---

## 📋 Checklist

- [ ] Database setup gedaan (`setup_database.php`)
- [ ] DNS server gestart (`dns_whitelist_server.py`)
- [ ] Firewall setup gedaan (`vpn_firewall_setup.sh`)
- [ ] Abonnement aangesloten (`subscribe.html`)
- [ ] WireGuard config gedownload
- [ ] WireGuard config geïmporteerd in app
- [ ] VPN verbinding actief
- [ ] Test: Pornografische content geblokkeerd ✅
- [ ] Test: Whitelist werkt ✅

---

## 🎯 Volgende Stappen

1. **Voeg domeinen toe aan whitelist**
   - Log in → Devices → Whitelist
   - Voeg toe: google.com, wikipedia.org, etc.

2. **Voeg meer devices toe**
   - Log in → Devices → "Device Toevoegen"
   - Download config voor elk device

3. **Monitor activiteit**
   - Log in → Dashboard
   - Check device status en activiteit

---

## 📞 Hulp Nodig?

- **Documentatie**: Zie `START_HERE.md`
- **Technische Details**: Zie `TECHNICAL_DOCUMENTATION.md`
- **System Check**: `http://localhost/44/system_check.html`

---

**Klaar! Het systeem is nu actief en beschermt tegen pornografische content.** 🛡️
