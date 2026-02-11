# 🚨 FIX: Pornografische Content Blokkeren - DIRECTE OPLOSSING

## ❌ PROBLEEM
DNS server draait niet → Pornografische sites kunnen worden geladen

## ✅ OPLOSSING (Kies één methode)

---

## 🔧 METHODE 1: LaunchDaemon Installeren (AANBEVOLEN - Start automatisch bij boot)

### Stap 1: Open Terminal

### Stap 2: Voer dit commando uit:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
./install_dns_launchdaemon.sh
```

**Je wordt gevraagd om je wachtwoord in te voeren (voor sudo).**
- Type je wachtwoord (je ziet niets typen - dat is normaal!)
- Druk ENTER

### Stap 3: Wacht tot installatie klaar is

Je ziet:
```
✅ LaunchDaemon: Geïnstalleerd en geladen
✅ DNS Server: Actief
```

### ✅ KLAAR!
DNS server start nu automatisch bij elke boot.

---

## 🔧 METHODE 2: Handmatig Starten (Voor nu)

### Stap 1: Open Terminal

### Stap 2: Voer dit commando uit:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
sudo python3 dns_whitelist_server.py
```

**Je wordt gevraagd om je wachtwoord in te voeren.**
- Type je wachtwoord (je ziet niets typen - dat is normaal!)
- Druk ENTER

### Stap 3: Laat Terminal open staan

DNS server draait nu. **Laat deze terminal open staan!**

### ⚠️ BELANGRIJK:
- Als je terminal sluit, stopt DNS server
- Gebruik METHODE 1 voor automatisch starten

---

## 🔧 METHODE 3: Start Script Gebruiken

### Stap 1: Open Terminal

### Stap 2: Voer dit commando uit:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
./start_dns_server.sh
```

**Je wordt gevraagd om je wachtwoord in te voeren.**

---

## ✅ VERIFICATIE (Na starten)

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

### Test 1: Direct DNS test

```bash
dig @127.0.0.1 pornhub.com
```

**Verwacht:** `NXDOMAIN` (domain not found)

### Test 2: Via browser

1. Verbind met VPN (WireGuard)
2. Probeer `pornhub.com` te bezoeken
3. **Moet NIET laden** (domain not found)

---

## 📋 VOLGENDE STAPPEN

1. ✅ DNS server draait
2. ✅ VPN verbonden op je device
3. ✅ WireGuard config heeft `DNS = 10.10.0.1`
4. ✅ Whitelist bepaalt welke sites werken
5. ✅ Pornografische sites zijn ALTIJD geblokkeerd

---

## 🆘 NOG STEEDS PROBLEMEN?

### Check deze punten:

1. **DNS server draait?**
   ```bash
   ps aux | grep dns_whitelist_server | grep -v grep
   ```

2. **Poort 53 in gebruik?**
   ```bash
   sudo lsof -i :53
   ```

3. **VPN verbonden?**
   - Check IP: `curl ifconfig.me` → Moet VPN server IP zijn

4. **Chrome DoH UIT?**
   - Chrome → `chrome://settings/security`
   - "Use secure DNS" → UIT

5. **Whitelist leeg?**
   - Check via control panel
   - Lege whitelist = geen internet (zoals bedoeld)

---

## 📞 HULP NODIG?

Check logs:
```bash
tail -50 /Applications/XAMPP/xamppfiles/htdocs/44/logs/dns_server.log
```

Check errors:
```bash
tail -50 /Applications/XAMPP/xamppfiles/htdocs/44/logs/dns_server.error.log
```
