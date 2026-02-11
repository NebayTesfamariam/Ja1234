# 🚨 FIX DNS SERVER - 3 EENVOUDIGE METHODEN

## ❌ PROBLEEM
DNS server draait niet → Pornografische sites kunnen worden geladen

## ✅ OPLOSSING (Kies één methode)

---

## 🔧 METHODE 1: Double-Click Script (MEEST EENVOUDIG)

### Stap 1: Open Finder
### Stap 2: Ga naar: `/Applications/XAMPP/xamppfiles/htdocs/44`
### Stap 3: **Double-click** op `DOUBLE_CLICK_TO_START_DNS.sh`

**Terminal opent automatisch en vraagt om je wachtwoord!**

---

## 🔧 METHODE 2: Terminal Commando (SNELSTE)

### Open Terminal en voer uit:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44 && sudo python3 dns_whitelist_server.py
```

**Je wordt gevraagd om je wachtwoord:**
- Type je wachtwoord (je ziet niets typen - dat is normaal!)
- Druk ENTER

**Laat Terminal open staan!**

---

## 🔧 METHODE 3: LaunchDaemon Installeren (AUTOMATISCH BIJ BOOT)

### Open Terminal en voer uit:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
./install_dns_launchdaemon.sh
```

**Je wordt gevraagd om je wachtwoord.**

Na installatie start DNS server automatisch bij elke boot!

---

## ✅ VERIFICATIE

### Check of DNS server draait:

```bash
ps aux | grep dns_whitelist_server | grep -v grep
```

**Moet proces tonen!**

### Check poort 53:

```bash
sudo lsof -i :53
```

**Moet `dns_whitelist_server.py` tonen!**

---

## 🧪 TEST PORN BLOKKERING

1. Verbind met VPN (WireGuard)
2. Probeer `pornhub.com` te bezoeken
3. **Moet NIET laden** (domain not found)

---

## 📋 BELANGRIJK

- ✅ DNS server MOET blijven draaien
- ✅ VPN MOET verbonden zijn
- ✅ Whitelist bepaalt welke sites werken
- ✅ Pornografische sites zijn ALTIJD geblokkeerd

---

## 🆘 NOG STEEDS PROBLEMEN?

Check logs:
```bash
tail -50 /Applications/XAMPP/xamppfiles/htdocs/44/logs/dns_server.log
```
