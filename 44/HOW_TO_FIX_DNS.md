# 🔧 HOE DNS SERVER TE FIXEN - STAP VOOR STAP

## 📋 PROBLEEM
- DNS server draait niet
- LaunchDaemon niet geïnstalleerd
- `systemctl` werkt niet op macOS (gebruik `launchctl`)

---

## ✅ OPLOSSING - STAP VOOR STAP

### **STAP 1: Open Terminal**
1. Druk op `Cmd + Space` (of klik op Spotlight)
2. Typ: `Terminal`
3. Druk op Enter

---

### **STAP 2: Ga naar de juiste directory**
Kopieer en plak dit commando in Terminal:
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
```

Druk op Enter.

---

### **STAP 3: Installeer LaunchDaemon**
Kopieer en plak dit commando:
```bash
./install_dns_launchdaemon.sh
```

Druk op Enter.

**⚠️ Je wordt gevraagd om je admin wachtwoord:**
- Typ je macOS admin wachtwoord
- Druk op Enter
- **Let op:** Je ziet geen tekens terwijl je typt (normaal voor wachtwoorden)

---

### **STAP 4: Wacht op installatie**
Het script voert automatisch uit:
- ✅ Kopieert plist naar `/Library/LaunchDaemons/`
- ✅ Stelt permissies in
- ✅ Laadt LaunchDaemon
- ✅ Start DNS server
- ✅ Verifieert status

**Dit duurt ongeveer 10-15 seconden.**

---

### **STAP 5: Verifieer dat het werkt**
Kopieer en plak deze commando's één voor één:

**Check LaunchDaemon:**
```bash
sudo launchctl list | grep pornfree.dns
```

**Check DNS server proces:**
```bash
ps aux | grep dns_whitelist_server | grep -v grep
```

**Test DNS:**
```bash
dig @127.0.0.1 google.com
```

---

## 🎯 VERWACHT RESULTAAT

Na installatie zie je:
```
✅ LaunchDaemon: Geïnstalleerd en geladen
✅ DNS Server: Actief
```

---

## 🆘 ALS HET NIET WERKT

### **Probleem: "Permission denied"**
```bash
chmod +x install_dns_launchdaemon.sh
./install_dns_launchdaemon.sh
```

### **Probleem: "Poort 53 al in gebruik"**
```bash
# Check wat poort 53 gebruikt
sudo lsof -i :53

# Stop andere DNS service (als nodig)
sudo launchctl unload /System/Library/LaunchDaemons/com.apple.mDNSResponder.plist
```

### **Probleem: "Python niet gevonden"**
```bash
# Check Python pad
which python3

# Als nodig, update plist met juiste pad
# Open: com.nebay.pornfree.dns.plist
# Pas aan: /opt/homebrew/bin/python3 (of jouw python3 pad)
```

### **Check logs voor errors:**
```bash
# Error log
cat logs/dns_server.error.log

# Output log
cat logs/dns_server.log
```

---

## 📊 HANDIGE COMMANDO'S

### **Start DNS service (als al geïnstalleerd):**
```bash
./start_dns_service.sh
```

### **Stop DNS service:**
```bash
sudo launchctl unload /Library/LaunchDaemons/com.nebay.pornfree.dns.plist
```

### **Herstart DNS service:**
```bash
sudo launchctl unload /Library/LaunchDaemons/com.nebay.pornfree.dns.plist
sleep 2
sudo launchctl load /Library/LaunchDaemons/com.nebay.pornfree.dns.plist
```

### **Check status:**
```bash
# LaunchDaemon status
sudo launchctl list | grep pornfree.dns

# DNS server proces
ps aux | grep dns_whitelist_server | grep -v grep

# Test DNS resolutie
dig @127.0.0.1 google.com
nslookup google.com 127.0.0.1
```

---

## ✅ KLAAR!

Na installatie:
- ✅ DNS server start automatisch bij boot
- ✅ Geen handmatige actie meer nodig
- ✅ Werkt ook zonder ingelogde gebruiker

---

## 📞 HULP NODIG?

Als het nog steeds niet werkt:
1. Check logs: `cat logs/dns_server.error.log`
2. Check Python: `python3 --version`
3. Check permissies: `ls -la install_dns_launchdaemon.sh`
