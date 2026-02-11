# 🔐 WAAR EN HOE WACHTWOORD IN TE VOEREN

## 📋 STAP-VOOR-STAP MET SCREENSHOT UITLEG

---

### **STAP 1: Open Terminal**
1. Druk op `Cmd + Space` (Spotlight)
2. Typ: `Terminal`
3. Druk op Enter

---

### **STAP 2: Ga naar directory**
Kopieer en plak:
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
```
Druk Enter.

---

### **STAP 3: Start install script**
Kopieer en plak:
```bash
./install_dns_launchdaemon.sh
```
Druk Enter.

---

## 🔐 WACHTWOORD PROMPT - WAT JE ZIET

Na het uitvoeren van het script zie je dit:

```
===============================
INSTALLEREN DNS SERVER LAUNCHDAEMON
===============================

📋 Stap 1: Kopiëren naar /Library/LaunchDaemons/...
Password:
```

**⬇️ HIER TYP JE JE WACHTWOORD ⬇️**

---

## ⚠️ BELANGRIJK - WACHTWOORD INVOEREN

### **Wat gebeurt er:**
1. Je ziet: `Password:`
2. **Je typt je macOS admin wachtwoord**
3. **Je ziet GEEN tekens** (geen sterretjes, geen punten, niets)
4. Dit is **NORMAAL** voor wachtwoorden in Terminal
5. Druk op **Enter** na het typen

### **Voorbeeld:**
```
Password: [hier typ je je wachtwoord - je ziet niets]
```

**Je typt bijvoorbeeld:** `mijnwachtwoord123`
**Maar je ziet:** `Password: ` (geen tekens)

**Dit is CORRECT!** Gewoon doortypen en Enter drukken.

---

## ✅ VERWACHT RESULTAAT

Na het invoeren van je wachtwoord zie je:

```
   ✅ Gekopieerd

📋 Stap 2: Permissies instellen...
   ✅ Permissies ingesteld
...
```

---

## 🆘 PROBLEMEN?

### **Probleem: "Sorry, try again"**
- Je hebt het verkeerde wachtwoord ingevoerd
- Probeer opnieuw
- Zorg dat je het juiste admin wachtwoord gebruikt

### **Probleem: "Password:" verschijnt niet**
- Het script vraagt mogelijk geen wachtwoord
- Check of je al sudo rechten hebt
- Of het script is al uitgevoerd

### **Probleem: "Permission denied"**
- Je hebt geen admin rechten
- Log in met een admin account
- Of vraag je administrator om hulp

---

## 📊 VISUELE GIDS

```
Terminal venster:
┌─────────────────────────────────────────┐
│ nebay@MacBook ~ %                       │
│ $ cd /Applications/XAMPP/.../44        │
│ $ ./install_dns_launchdaemon.sh         │
│                                         │
│ ===============================         │
│ INSTALLEREN DNS SERVER LAUNCHDAEMON    │
│ ===============================         │
│                                         │
│ 📋 Stap 1: Kopiëren...                  │
│ Password: [HIER TYP JE WACHTWOORD]     │ ← 🔐
│                                         │
└─────────────────────────────────────────┘
```

**⬆️ Bij "Password:" typ je je wachtwoord (je ziet geen tekens)**

---

## 💡 TIPS

1. **Zorg dat je het juiste wachtwoord hebt**
   - Dit is je macOS gebruikers wachtwoord
   - Het wachtwoord waarmee je inlogt op je Mac

2. **Type voorzichtig**
   - Je ziet geen feedback, dus type langzaam
   - Controleer Caps Lock (hoofdletters)

3. **Als je een fout maakt**
   - Druk op Enter (foutmelding verschijnt)
   - Probeer opnieuw met `./install_dns_launchdaemon.sh`

4. **Meerdere keren wachtwoord vragen**
   - Normaal: 1-2 keer
   - Elke sudo commando vraagt om wachtwoord
   - Dit is normaal

---

## ✅ KLAAR!

Na het invoeren van je wachtwoord:
- Script installeert automatisch alles
- DNS server start automatisch
- Geen verdere actie nodig

---

## 📞 HULP NODIG?

Als je problemen hebt:
1. Check of je admin rechten hebt
2. Check of je het juiste wachtwoord gebruikt
3. Probeer opnieuw met `./install_dns_launchdaemon.sh`
