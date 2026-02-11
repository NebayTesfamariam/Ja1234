# 🚨 FIX: ERR_CONNECTION_REFUSED

## ❌ PROBLEEM
```
This site can't be reached
localhost refused to connect.
ERR_CONNECTION_REFUSED
```

**Oorzaak:** Apache draait niet!

---

## ✅ OPLOSSING

### METHODE 1: Double-Click (MEEST EENVOUDIG)

1. Open Finder
2. Ga naar: `/Applications/XAMPP/xamppfiles/htdocs/44`
3. **Double-click** op `START_APACHE_NOW.command`

Terminal opent automatisch en vraagt om je wachtwoord!

---

### METHODE 2: Terminal Commando (SNELSTE)

Open Terminal en voer uit:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
./START_APACHE_NOW.sh
```

Je wordt gevraagd om je wachtwoord:
- Type je wachtwoord (je ziet niets typen - dat is normaal!)
- Druk ENTER

---

### METHODE 3: Handmatig Starten

```bash
sudo /Applications/XAMPP/xamppfiles/bin/httpd -k start
```

---

## ✅ VERIFICATIE

### Check of Apache draait:

```bash
pgrep -x httpd && echo "✅ Apache draait" || echo "❌ Apache draait niet"
```

### Check poort 80:

```bash
sudo lsof -i :80
```

Moet `httpd` tonen!

---

## 🧪 TEST

Na starten, test:

```
http://localhost/44/
```

Moet nu werken!

---

## 🆘 NOG STEEDS PROBLEMEN?

### Check Apache logs:

```bash
tail -50 /Applications/XAMPP/xamppfiles/logs/error_log
```

### Check poort 80:

```bash
sudo lsof -i :80
```

### Mogelijke oorzaken:

1. **Poort 80 al in gebruik**
   - Stop andere webserver
   - Of gebruik andere poort

2. **Configuratie fout**
   - Check Apache config
   - Check error logs

3. **Permissies probleem**
   - Gebruik sudo
   - Check file permissions

---

## 📋 BELANGRIJK

- ✅ Apache MOET blijven draaien
- ✅ Poort 80 moet beschikbaar zijn
- ✅ Geen andere webserver op poort 80

