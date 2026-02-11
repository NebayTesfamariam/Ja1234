# 🗑️ Remove Blocklist Tables Guide

## 🎯 Doel
Verwijder alle blocklist tabellen uit de database (whitelist-only systeem).

---

## ⚠️ BELANGRIJK: Backup Eerst!

**VOOR je de tabellen verwijdert, maak een backup:**

```bash
# Backup database
mysqldump -u root -p pornfree > backup_before_remove_blocklist.sql
```

Of via phpMyAdmin:
1. Selecteer database `pornfree`
2. Klik op "Export"
3. Kies "Quick" of "Custom"
4. Download SQL file

---

## ✅ Optie 1: Via Web Interface (Aanbevolen)

1. Open in browser:
   ```
   http://localhost/44/remove_blocklist_tables.html
   ```

2. Klik op **"👁️ Preview"** om te zien welke tabellen verwijderd zullen worden

3. Klik op **"🗑️ Verwijder Blocklist Tabellen"**

4. Bevestig **2x** (veiligheid)

5. Bekijk resultaten

---

## ✅ Optie 2: Via API Direct

### Preview (geen verwijdering):
```bash
curl http://localhost/44/remove_blocklist_tables.php
```

### Verwijderen (met bevestiging):
```bash
curl "http://localhost/44/remove_blocklist_tables.php?confirm=yes"
```

---

## ✅ Optie 3: Via SQL Direct

**⚠️ Alleen als je zeker weet wat je doet!**

```sql
-- Verwijder blocklist tabellen
DROP TABLE IF EXISTS blocklist_global;
DROP TABLE IF EXISTS blocklist_device;
DROP TABLE IF EXISTS blocklist_permanent;
DROP TABLE IF EXISTS blocklist_subscription;
```

---

## 📋 Tabellen die Verwijderd Worden

1. **`blocklist_global`** - Global blocklist
2. **`blocklist_device`** - Device-specific blocklist
3. **`blocklist_permanent`** - Permanent blocklist
4. **`blocklist_subscription`** - Subscription blocklist

---

## ✅ Verificatie Na Verwijdering

### Check via System Check:
```
http://localhost/44/system_check.html
```

**Verwacht:** "Geen blocklist tabellen (whitelist-only)" ✅

### Check via SQL:
```sql
SHOW TABLES LIKE 'blocklist%';
```

**Verwacht:** Lege resultaten (geen tabellen)

---

## ⚠️ Belangrijk

- **Backup eerst!** Deze actie kan niet ongedaan gemaakt worden
- **Tabellen worden permanent verwijderd**
- **Alle data in deze tabellen gaat verloren**
- **In whitelist-only systeem worden deze tabellen niet gebruikt**

---

## 🔧 Troubleshooting

### Als verwijderen faalt:

1. **Check database permissions:**
   ```sql
   SHOW GRANTS;
   ```
   Je hebt `DROP` rechten nodig

2. **Check of tabellen in gebruik zijn:**
   ```sql
   SHOW PROCESSLIST;
   ```
   Geen actieve queries op deze tabellen

3. **Check foreign keys:**
   ```sql
   SHOW CREATE TABLE blocklist_device;
   ```
   Verwijder eerst foreign keys als die er zijn

---

## 📝 Notities

- Deze tabellen worden **niet gebruikt** in whitelist-only systeem
- Verwijderen is **optioneel** - systeem werkt ook met tabellen aanwezig
- Verwijderen maakt de database **schoner** en **duidelijker**
- Na verwijdering: system check zou geen waarschuwing meer moeten geven

---

## ✅ Checklist

- [ ] Database backup gemaakt
- [ ] Preview uitgevoerd (gezien welke tabellen verwijderd worden)
- [ ] Tabellen verwijderd
- [ ] Verificatie uitgevoerd (system check)
- [ ] Geen errors in system check

---

## 🔗 Gerelateerd

- **System Check:** `system_check.html`
- **Database Backup:** Zie MySQL/phpMyAdmin documentatie
