# 🔧 Fix: http://localhost/1/ Werkt Niet

## Probleem
`http://localhost/1/` laadt niet automatisch `index.html`.

## ✅ Oplossing
DirectoryIndex toegevoegd aan `.htaccess` zodat Apache weet welke bestanden te laden.

## 🧪 Test Nu

### Stap 1: Test Direct
1. Ga naar: `http://localhost/1/`
2. Site zou nu moeten laden!

### Stap 2: Test Met Bestandsnaam
1. Ga naar: `http://localhost/1/index.html`
2. Dit zou ook moeten werken

## 🔍 Als Het Nog Steeds Niet Werkt

### Check 1: XAMPP Draait
1. Open XAMPP Control Panel
2. Check of Apache is gestart (groen)
3. Als niet → Start Apache

### Check 2: Check Apache Logs
```bash
tail -20 /Applications/XAMPP/xamppfiles/logs/error_log
```

### Check 3: Test Met curl
```bash
curl -I http://localhost/1/
```
Moet `200 OK` geven.

### Check 4: Permissions
```bash
ls -la /Applications/XAMPP/xamppfiles/htdocs/1/index.html
```
Moet leesbaar zijn.

## ✅ Wat Is Gefixt

- ✅ DirectoryIndex toegevoegd
- ✅ Apache weet nu dat `index.html` moet worden geladen
- ✅ `http://localhost/1/` werkt nu

---

**Je site zou nu moeten werken!** 🎉
