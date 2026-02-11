# 🚀 FIX ALL SERVICES - MySQL, Apache, DNS Server

## ❌ PROBLEEM
MySQL, Apache en DNS-server draaien niet

## ✅ OPLOSSING

---

## 🔧 METHODE 1: Double-Click (MEEST EENVOUDIG)

### Stap 1: Open Finder
### Stap 2: Ga naar: `/Applications/XAMPP/xamppfiles/htdocs/44`
### Stap 3: **Double-click** op `START_ALL_SERVICES.command`

**Terminal opent automatisch en vraagt om je wachtwoord!**

---

## 🔧 METHODE 2: Terminal Commando (SNELSTE)

### Open Terminal en voer uit:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
./START_ALL_SERVICES.sh
```

**Je wordt gevraagd om je wachtwoord:**
- Type je wachtwoord (je ziet niets typen - dat is normaal!)
- Druk ENTER

---

## ✅ WAT HET SCRIPT DOET

1. **Start MySQL** (als niet actief)
2. **Start Apache** (als niet actief)
3. **Start DNS Server** (als niet actief)
4. **Verifieert** dat alles draait

---

## ✅ VERIFICATIE

### Check of alles draait:

```bash
# Check MySQL
pgrep -x mysqld && echo "✅ MySQL draait" || echo "❌ MySQL draait niet"

# Check Apache
pgrep -x httpd && echo "✅ Apache draait" || echo "❌ Apache draait niet"

# Check DNS Server
pgrep -f dns_whitelist_server && echo "✅ DNS Server draait" || echo "❌ DNS Server draait niet"
```

---

## 🧪 TEST

### Test Website:
```
http://localhost/44/
```

### Test API:
```
http://localhost/44/api/health.php
```

### Test DNS Server:
```bash
dig @127.0.0.1 google.com
```

---

## 🆘 PROBLEMEN?

### MySQL start niet:
```bash
# Check MySQL logs
tail -50 /Applications/XAMPP/xamppfiles/logs/mysql_error.log

# Start handmatig
sudo /Applications/XAMPP/xamppfiles/bin/mysql.server start
```

### Apache start niet:
```bash
# Check Apache logs
tail -50 /Applications/XAMPP/xamppfiles/logs/error_log

# Start handmatig
sudo /Applications/XAMPP/xamppfiles/bin/httpd -k start
```

### DNS Server start niet:
```bash
# Check DNS logs
tail -50 /Applications/XAMPP/xamppfiles/htdocs/44/logs/dns_server.log

# Start handmatig
cd /Applications/XAMPP/xamppfiles/htdocs/44
sudo python3 dns_whitelist_server.py
```

---

## 📋 BELANGRIJK

- ✅ Alle services MOETEN blijven draaien
- ✅ MySQL is nodig voor database
- ✅ Apache is nodig voor website
- ✅ DNS Server is nodig voor porn blokkering

---

## 🔄 AUTOMATISCH STARTEN BIJ BOOT

Voor automatisch starten bij boot, gebruik:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
./install_dns_launchdaemon.sh
```

Dit installeert LaunchDaemon voor DNS server.

Voor MySQL en Apache, gebruik XAMPP's eigen auto-start functionaliteit.
