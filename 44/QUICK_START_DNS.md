# 🚀 DNS Server - Quick Start

## ⚠️ Belangrijk

DNS server gebruikt poort **53** en vereist **Administrator** rechten.

---

## 🪟 WINDOWS/XAMPP - Start DNS Server (3 Stappen)

### Stap 1: Installeer Python Requests Library
Open CMD en run:
```bat
python -m pip install requests
```

### Stap 2: Start DNS Server als Administrator
1. **Rechtsklik** op `start_dns_server.bat`
2. Klik **"Run as Administrator"**
3. Laat venster **OPEN**

### Stap 3: Verifieer
Open nieuwe CMD:
```bat
netstat -ano | find ":53"
```

---

## 🍎 macOS/Linux - Start DNS Server (3 Stappen)

### Stap 1: Installeer Python Requests Library
```bash
pip3 install --user requests
```

### Stap 2: Start DNS Server
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
sudo python3 dns_whitelist_server.py
```

### Stap 3: Laat Draaien
Laat de terminal open of start in background:
```bash
sudo nohup python3 dns_whitelist_server.py > logs/dns_server.log 2>&1 &
```

---

## ✅ Verificatie

### Check of DNS Server Draait:
```bash
ps aux | grep dns_whitelist_server | grep -v grep
```

### Test DNS Server:
```bash
dig @127.0.0.1 google.com
```

---

## 🔧 Automatisch Starten

### macOS LaunchAgent (Aanbevolen):

1. **Installeer LaunchAgent:**
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
cp com.nebay.pornfree.plist ~/Library/LaunchAgents/
launchctl load ~/Library/LaunchAgents/com.nebay.pornfree.plist
```

2. **Start Nu:**
```bash
launchctl start com.nebay.pornfree
```

3. **Check Status:**
```bash
launchctl list | grep pornfree
```

### Via Start Script:
```bash
sudo ./start_dns_server.sh
```

---

## 📋 Logs

DNS server logs:
```bash
tail -f logs/dns_server.log
```

---

## ✅ Status Check

Run complete test:
```bash
php TEST_SYSTEM.php
```

---

**DNS Server is nu klaar om te starten!** 🚀
