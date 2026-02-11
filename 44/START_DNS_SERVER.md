# 🚀 DNS Server Starten

## ⚠️ BELANGRIJK
De DNS server is **VERPLICHT** voor het VPN filtering systeem. Zonder DNS server werkt de whitelist filtering **NIET**.

---

## 📋 Vereisten

1. **Python 3** geïnstalleerd
2. **Python requests library** geïnstalleerd
3. **Root toegang (sudo)** voor poort 53

---

## 🔍 Check Status

### Check of DNS server draait:
```bash
ps aux | grep dns_whitelist_server | grep -v grep
```

### Check of poort 53 in gebruik is:
```bash
sudo lsof -i :53
# Of op Windows:
netstat -ano | find ":53"
```

---

## 🚀 DNS Server Starten

### macOS / Linux:

#### Optie 1: Direct starten (tijdelijk)
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
sudo python3 dns_whitelist_server.py
```

#### Optie 2: Via start script
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
sudo ./start_dns_server.sh
```

#### Optie 3: Automatisch starten (permanent)
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
sudo ./start_pornfree_system.sh
```

### Windows:

#### Optie 1: Via Command Prompt (als Administrator)
```cmd
cd C:\xampp\htdocs\44
python dns_whitelist_server.py
```

#### Optie 2: Via batch script (als Administrator)
```cmd
cd C:\xampp\htdocs\44
start_dns_server.bat
```

#### Optie 3: Automatisch starten
```cmd
cd C:\xampp\htdocs\44
start_pornfree_system.bat
```

---

## 📦 Python Requests Library Installeren

Als je de fout krijgt: `ModuleNotFoundError: No module named 'requests'`

### macOS / Linux:
```bash
pip3 install --user requests
# Of met system packages:
pip3 install --break-system-packages requests
```

### Windows:
```cmd
pip install requests
```

### Via install script:
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
./install_dns_dependencies.sh
```

---

## ✅ Verificatie

Na het starten van de DNS server, check:

1. **Process check:**
   ```bash
   ps aux | grep dns_whitelist_server | grep -v grep
   ```
   Je zou een Python proces moeten zien.

2. **Port check:**
   ```bash
   sudo lsof -i :53
   ```
   Je zou moeten zien dat Python luistert op poort 53.

3. **Test DNS query:**
   ```bash
   dig @127.0.0.1 google.com
   # Of op Windows:
   nslookup google.com 127.0.0.1
   ```

---

## 🛑 DNS Server Stoppen

### macOS / Linux:
```bash
sudo pkill -f dns_whitelist_server.py
```

### Windows:
```cmd
taskkill /F /IM python.exe /FI "WINDOWTITLE eq dns_whitelist_server*"
```

Of gebruik het stop script:
```bash
./stop_dns_server.sh
# Of op Windows:
stop_dns_server.bat
```

---

## 🔄 Automatisch Starten bij Boot

### macOS:
1. Kopieer `com.nebay.pornfree.plist` naar `~/Library/LaunchAgents/`
2. Laad de LaunchAgent:
   ```bash
   launchctl load ~/Library/LaunchAgents/com.nebay.pornfree.plist
   ```

### Windows:
1. Kopieer `start_pornfree_system.bat` naar Startup folder:
   - Druk `Win + R`
   - Type: `shell:startup`
   - Kopieer het batch bestand daarheen

---

## ⚠️ Problemen Oplossen

### Probleem: "Permission denied" op poort 53
**Oplossing:** Start met `sudo` (macOS/Linux) of als Administrator (Windows)

### Probleem: "Address already in use"
**Oplossing:** Er draait al een DNS server. Stop deze eerst:
```bash
sudo pkill -f dns_whitelist_server.py
```

### Probleem: "ModuleNotFoundError: No module named 'requests'"
**Oplossing:** Installeer de requests library (zie boven)

### Probleem: DNS server start maar werkt niet
**Check:**
1. Is de database verbonden?
2. Zijn er actieve devices in de database?
3. Check de logs voor errors

---

## 📝 Notities

- **Poort 53** vereist root/administrator rechten
- DNS server moet **altijd draaien** voor VPN filtering
- Als DNS server stopt, werkt filtering **NIET**
- Test altijd na het starten of het werkt

---

## 🎯 Volgende Stappen

1. ✅ Start DNS server
2. ✅ Verifieer dat het werkt
3. ✅ Test VPN filtering
4. ✅ Zet automatisch starten aan (optioneel)
