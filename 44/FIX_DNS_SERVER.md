# 🔧 DNS Server Fix - Automatisch Starten

## ✅ Oplossing Geïmplementeerd

De DNS server kan nu automatisch worden gestart!

---

## 🪟 WINDOWS/XAMPP - Start DNS Server

### Optie 1: Via Start Script (Aanbevolen)
1. **Rechtsklik** op `start_dns_server.bat`
2. Klik **"Run as Administrator"**
3. Laat venster **OPEN**

### Optie 2: Via Complete System Start
1. **Rechtsklik** op `start_pornfree_system.bat`
2. Klik **"Run as Administrator"**
3. DNS start automatisch in nieuw venster

### Optie 3: Handmatig
Open **CMD als Administrator**:
```bat
cd C:\xampp\htdocs\44
python dns_whitelist_server.py
```

---

## 🍎 macOS/Linux - Start DNS Server

### Optie 1: Via Start Script
```bash
sudo ./start_dns_server.sh
```

### Optie 2: Via Complete System Start
```bash
sudo ./start_pornfree_system.sh
```

### Optie 3: Handmatig
```bash
sudo python3 dns_whitelist_server.py
```

---

## ✅ Verificatie

### Check of DNS Server Draait:
```bash
ps aux | grep dns_whitelist_server | grep -v grep
```

### Check Poort 53:
```bash
sudo lsof -i :53
```

### Test DNS Server:
```bash
dig @127.0.0.1 google.com
nslookup google.com 127.0.0.1
```

---

## 🔧 Veelgemaakte Problemen

### Probleem 1: Port 53 Al In Gebruik
**Oplossing:**
```bash
# Stop andere DNS services
sudo pkill -f dns_whitelist_server.py
sudo systemctl stop systemd-resolved  # Linux
```

### Probleem 2: Python Requests Library Ontbreekt
**Oplossing:**
```bash
pip3 install requests
# Of voor macOS:
pip3 install --user requests
```

### Probleem 3: Permission Denied
**Oplossing:**
```bash
# Gebruik sudo
sudo ./start_dns_server.sh
```

---

## 📋 Logs

DNS server logs worden opgeslagen in:
```
logs/dns_server.log
```

Bekijk logs:
```bash
tail -f logs/dns_server.log
```

---

## 🎯 Automatisch Starten Bij Boot

### macOS:
```bash
# Installeer LaunchAgent
cp com.nebay.pornfree.plist ~/Library/LaunchAgents/
launchctl load ~/Library/LaunchAgents/com.nebay.pornfree.plist
```

### Windows:
1. Zet `start_pornfree_system.bat` in Startup folder
2. Run as Administrator

---

## ✅ Status Check

Run complete test:
```bash
php TEST_SYSTEM.php
```

Of alleen DNS check:
```bash
ps aux | grep dns_whitelist_server | grep -v grep
```

---

**DNS Server is nu automatisch te starten!** 🚀
