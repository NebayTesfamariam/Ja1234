# ✅ Automatisch Starten - 100% Automatisch

## 🎯 DOEL

Bij elke herstart van je computer:
- ✅ Apache start automatisch
- ✅ MySQL start automatisch
- ✅ DNS whitelist server start automatisch
- ✅ Jouw anti-porn systeem is actief
- ❌ Geen handmatige acties meer

---

## 🪟 WINDOWS - Automatisch Starten

### STAP 1: Maak Start Script

Het bestand `start_pornfree_system.bat` is al aangemaakt.

**Pas aan indien nodig:**
- `C:\xampp` → jouw XAMPP pad
- `C:\xampp\htdocs\44` → jouw project pad

### STAP 2: Zorg dat Python werkt

Controleer in CMD:
```bat
python --version
```

❌ Werkt dit niet → installeer Python en vink aan:
> ✅ **Add Python to PATH**

### STAP 3: Windows Automatisch Laten Starten

1. Druk op: `Win + R`
2. Typ: `shell:startup`
3. Druk ENTER
4. Zet hier een **snelkoppeling** naar `start_pornfree_system.bat`

### STAP 4: Adminrechten (BELANGRIJK)

DNS gebruikt poort **53**, dus:

1. Rechtsklik op `start_pornfree_system.bat`
2. Eigenschappen → Snelkoppeling → Geavanceerd
3. ✅ **Run as administrator**

### STAP 5: Test

Herstart je computer → alles start automatisch!

---

## 🍎 macOS - Automatisch Starten

### STAP 1: Maak Scripts Uitvoerbaar

```bash
chmod +x start_pornfree_system.sh
chmod +x stop_pornfree_system.sh
```

### STAP 2: Maak Logs Directory

```bash
mkdir -p logs
```

### STAP 3: Installeer LaunchAgent (Automatisch Starten)

```bash
# Kopieer plist naar LaunchAgents
cp com.nebay.pornfree.plist ~/Library/LaunchAgents/

# Laad de LaunchAgent
launchctl load ~/Library/LaunchAgents/com.nebay.pornfree.plist

# Start nu
launchctl start com.nebay.pornfree
```

### STAP 4: Verifieer

```bash
# Check status
launchctl list | grep pornfree

# Check logs
tail -f logs/pornfree.log
```

### STAP 5: Test

Herstart je Mac → alles start automatisch!

---

## 🚀 Handmatig Starten (Één Command)

### Windows:
```bat
# Als Administrator
start_pornfree_system.bat
```

### macOS/Linux:
```bash
# Met sudo voor DNS port 53
sudo ./start_pornfree_system.sh
```

---

## 🛑 Stoppen

### Windows:
```bat
stop_pornfree_system.bat
```

### macOS/Linux:
```bash
./stop_pornfree_system.sh
```

### macOS LaunchAgent:
```bash
launchctl stop com.nebay.pornfree
launchctl unload ~/Library/LaunchAgents/com.nebay.pornfree.plist
```

---

## 🔥 Veelgemaakte Fouten

| Probleem | Oplossing |
|----------|-----------|
| DNS start niet | Script niet als admin/sudo |
| FINAL_SYSTEM_CHECK faalt | MySQL niet gestart |
| Porn laadt | DNS server draait niet |
| Soms werkt het | Terminal gesloten (gebruik LaunchAgent) |
| Port 53 in gebruik | Stop andere DNS services |

---

## ✅ Status Check

### Windows:
```bat
# Check processen
tasklist | findstr python
tasklist | findstr mysqld
tasklist | findstr httpd
```

### macOS:
```bash
# Check processen
ps aux | grep dns_whitelist_server
ps aux | grep mysqld
ps aux | grep httpd

# Check poorten
sudo lsof -i :53
sudo lsof -i :80
sudo lsof -i :3306
```

---

## 🎉 Resultaat

Vanaf NU:
- ✅ Computer aan → systeem start automatisch
- ✅ Apache ✔
- ✅ MySQL ✔
- ✅ DNS server ✔
- ✅ Porn blokkering ✔
- ❌ Geen handmatige acties meer!

---

**Het systeem start nu 100% automatisch!** 🚀
