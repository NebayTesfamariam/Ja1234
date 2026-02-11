# 🚀 FIX ALL - Apache, MySQL, DNS Server

## ❌ PROBLEEM
Alles draait niet:
- ❌ Apache → ERR_CONNECTION_REFUSED
- ❌ MySQL → Database werkt niet
- ❌ DNS Server → Porn blokkering werkt niet

## ✅ OPLOSSING

---

## 🔧 METHODE 1: Double-Click (MEEST EENVOUDIG)

### Stap 1: Open Finder
### Stap 2: Ga naar: `/Applications/XAMPP/xamppfiles/htdocs/44`
### Stap 3: **Double-click** op `FIX_EVERYTHING.command`

**Terminal opent automatisch en vraagt om je wachtwoord!**

Het script start automatisch:
1. ✅ MySQL
2. ✅ Apache
3. ✅ DNS Server

---

## 🔧 METHODE 2: Terminal Commando

Open Terminal en voer uit:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
./FIX_EVERYTHING.command
```

Je wordt meerdere keren gevraagd om je wachtwoord:
- Type je wachtwoord (je ziet niets typen - dat is normaal!)
- Druk ENTER

---

## ✅ WAT HET SCRIPT DOET

1. **Start MySQL** (als niet actief)
2. **Start Apache** (als niet actief)
3. **Start DNS Server** (als niet actief)
4. **Verifieert** dat alles draait
5. **Toont status** van alle services

---

## ✅ VERIFICATIE

Na starten, check of alles draait:

```bash
# Check MySQL
pgrep -x mysqld && echo "✅ MySQL" || echo "❌ MySQL"

# Check Apache
pgrep -x httpd && echo "✅ Apache" || echo "❌ Apache"

# Check DNS Server
pgrep -f dns_whitelist_server && echo "✅ DNS" || echo "❌ DNS"
```

---

## 🧪 TEST

Na starten, test:

- **Website:** `http://localhost/44/`
- **API:** `http://localhost/44/api/health.php`
- **System Check:** `http://localhost/44/CHECK_WEBSITE.php`

---

## 🆘 NOG STEEDS PROBLEMEN?

### Check logs:

```bash
# Apache logs
tail -50 /Applications/XAMPP/xamppfiles/logs/error_log

# MySQL logs
tail -50 /Applications/XAMPP/xamppfiles/logs/mysql_error.log

# DNS Server logs
tail -50 /Applications/XAMPP/xamppfiles/htdocs/44/logs/dns_server.log
```

### Handmatig starten:

```bash
# MySQL
sudo /Applications/XAMPP/xamppfiles/bin/mysql.server start

# Apache
sudo /Applications/XAMPP/xamppfiles/bin/httpd -k start

# DNS Server
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

Voor automatisch starten bij boot:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
./install_dns_launchdaemon.sh
```

Dit installeert LaunchDaemon voor DNS server.

Voor MySQL en Apache, gebruik XAMPP's eigen auto-start functionaliteit.
