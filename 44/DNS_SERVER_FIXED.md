# ✅ DNS Server Fix - Compleet

## 🎯 Status

DNS server kan nu automatisch worden gestart!

---

## 🪟 WINDOWS/XAMPP - Quick Start (3 Stappen)

### Stap 1: Installeer Dependencies
Open CMD en run:
```bat
python -m pip install requests
```

### Stap 2: Start DNS Server als Administrator
1. **Rechtsklik** op `start_dns_server.bat`
2. Klik **"Run as Administrator"**
3. Laat venster **OPEN**

Of handmatig (CMD als Administrator):
```bat
cd C:\xampp\htdocs\44
python dns_whitelist_server.py
```

### Stap 3: Verifieer
```bat
netstat -ano | find ":53"
```

---

## 🍎 macOS/Linux - Quick Start (3 Stappen)

### Stap 1: Installeer Dependencies
```bash
./install_dns_dependencies.sh
```

Of handmatig:
```bash
pip3 install --break-system-packages requests
```

### Stap 2: Start DNS Server
```bash
sudo ./start_dns_server.sh
```

Of handmatig:
```bash
sudo python3 dns_whitelist_server.py
```

### Stap 3: Verifieer
```bash
ps aux | grep dns_whitelist_server | grep -v grep
```

---

## ✅ Automatisch Starten Bij Boot

### macOS LaunchAgent:

```bash
# 1. Installeer LaunchAgent
cp com.nebay.pornfree.plist ~/Library/LaunchAgents/

# 2. Laad LaunchAgent
launchctl load ~/Library/LaunchAgents/com.nebay.pornfree.plist

# 3. Start nu
launchctl start com.nebay.pornfree

# 4. Check status
launchctl list | grep pornfree
```

---

## 📋 Bestanden

- ✅ `start_dns_server.sh` - Start DNS server alleen
- ✅ `start_pornfree_system.sh` - Start alles (MySQL, Apache, DNS)
- ✅ `install_dns_dependencies.sh` - Installeer Python dependencies
- ✅ `com.nebay.pornfree.plist` - macOS LaunchAgent
- ✅ `QUICK_START_DNS.md` - Quick start guide

---

## 🔧 Troubleshooting

### Probleem: Port 53 In Gebruik
```bash
# Stop andere DNS services
sudo pkill -f dns_whitelist_server.py
sudo lsof -i :53
```

### Probleem: Requests Library Ontbreekt
```bash
# Installeer automatisch
./install_dns_dependencies.sh

# Of handmatig
pip3 install --break-system-packages requests
```

### Probleem: Permission Denied
```bash
# Gebruik sudo
sudo ./start_dns_server.sh
```

---

## ✅ Test

Run complete system test:
```bash
php TEST_SYSTEM.php
```

Check DNS server:
```bash
ps aux | grep dns_whitelist_server | grep -v grep
```

---

**DNS Server is nu gefixed en klaar om te starten!** 🚀
