# 🔧 Fix: MacBook Blokkeert Pornografische Content Niet

## Probleem
- MacBook is niet toegevoegd aan het systeem
- Pornografische content wordt niet geblokkeerd
- Hotspot van telefoon wordt gebruikt

---

## ✅ Snelle Oplossing (3 Stappen)

### Stap 1: Log In op MacBook
1. Open browser op je MacBook
2. Ga naar: `http://localhost/1/public/index.html`
3. Log in met je email en wachtwoord

### Stap 2: Device Wordt Automatisch Toegevoegd
- Na login wordt je MacBook **automatisch** toegevoegd
- Device naam: "Mac" (automatisch gedetecteerd)
- Status: Direct actief
- Filtering: Direct actief

### Stap 3: Test Filtering
- Probeer een pornografische site te bezoeken
- Moet nu geblokkeerd worden!

---

## 🔍 Als Het Nog Steeds Niet Werkt

### Check 1: Is Device Toegevoegd?
1. Log in op dashboard
2. Kijk onder "📱 Jouw Devices"
3. Zie je je MacBook? → Ja = goed, Nee = ga naar Check 2

### Check 2: Is Device Actief?
1. Kijk naar status van je MacBook
2. Moet zijn: **"active"** (groen)
3. Als **"blocked"** → Check je abonnement

### Check 3: Is Abonnement Actief?
1. Kijk onder "💳 Abonnement Info"
2. Moet zijn: **"active"**
3. Als **"expired"** → Betaal opnieuw of contacteer admin

### Check 4: Werkt Browser Filter?
1. Open browser console (F12 of Cmd+Option+I)
2. Zoek naar: `Browser filter: Active and ready`
3. Zoek naar: `Browser filter: Loaded X blocked domains`
4. Als je dit NIET ziet → Filter werkt niet

---

## 🛠️ Handmatige Fix

### Optie 1: Device Handmatig Toevoegen
1. Log in op dashboard
2. Klik op **"➕ Device Toevoegen (1 Klik!)"**
3. MacBook wordt automatisch toegevoegd
4. Test filtering opnieuw

### Optie 2: Herinitialiseer Filter
Open browser console (F12) en voer uit:
```javascript
// Check status
console.log('Device ID:', localStorage.getItem('device_id'));
console.log('Token:', localStorage.getItem('token'));

// Re-initialize filter
if (window.browserFilter) {
  window.browserFilter.deviceId = localStorage.getItem('device_id');
  window.browserFilter.token = localStorage.getItem('token');
  await window.browserFilter.loadBlocklist();
  window.browserFilter.setupInterception();
  console.log('Browser filter re-initialized');
} else {
  console.error('Browser filter not loaded!');
}
```

### Optie 3: Log Uit en Log Opnieuw In
1. Klik op "Uitloggen"
2. Log opnieuw in
3. Device wordt automatisch toegevoegd
4. Filter wordt automatisch geactiveerd

---

## 📱 Telefoon Toevoegen (Optioneel)

Als je ook je telefoon wilt toevoegen:

### Methode 1: Log In op Telefoon
1. Open browser op telefoon
2. Ga naar: `http://jouw-ip/1/public/index.html`
3. Log in met je email en wachtwoord
4. Telefoon wordt automatisch toegevoegd

### Methode 2: Registratie Link
1. Log in op MacBook dashboard
2. Klik op **"🔗 Mijn Registratie Link Genereren"**
3. Kopieer de link
4. Open link op telefoon
5. Telefoon wordt automatisch toegevoegd

---

## ⚠️ Belangrijke Checks

### Device Moet Actief Zijn
- Status moet zijn: **"active"** (niet "blocked" of "inactive")
- Check dit in dashboard onder "📱 Jouw Devices"

### Abonnement Moet Actief Zijn
- Abonnement status moet zijn: **"active"**
- Check dit in dashboard onder "💳 Abonnement Info"

### Browser Filter Moet Geladen Zijn
- Check browser console voor filter berichten
- Moet zien: `Browser filter: Active and ready`
- Moet zien: `Browser filter: Loaded X blocked domains`

### Device ID Moet Beschikbaar Zijn
- Check: `localStorage.getItem('device_id')`
- Moet een nummer zijn (bijv. "1", "2", etc.)
- Als `null` → Device niet toegevoegd

---

## 🚨 Als Niets Werkt

### Stap 1: Clear Alles en Start Opnieuw
```javascript
// In browser console
localStorage.clear();
location.reload();
```

### Stap 2: Log Opnieuw In
1. Log in met email en wachtwoord
2. Device wordt automatisch toegevoegd
3. Filter wordt automatisch geactiveerd

### Stap 3: Test Filtering
1. Probeer: `https://xhamster.com`
2. Moet geblokkeerd worden
3. Check console voor filter berichten

---

## 📊 Debug Checklist

- [ ] Gebruiker is ingelogd (token in localStorage)
- [ ] Device is toegevoegd (device_id in localStorage)
- [ ] Device status is "active" (check dashboard)
- [ ] Abonnement is actief (check dashboard)
- [ ] Browser filter is geladen (check console)
- [ ] Blocklist is geladen (check console: "Loaded X domains")
- [ ] Filter interceptie is actief (check console)

---

## 💡 Tips

### Tip 1: Altijd Dashboard Openen Na Login
- Open altijd dashboard na login
- Dit herinitialiseert de filter automatisch

### Tip 2: Check Console Bij Problemen
- Open altijd browser console (F12)
- Kijk naar filter berichten
- Zoek naar errors

### Tip 3: Hard Refresh Bij Problemen
- Mac: Cmd+Shift+R
- Windows: Ctrl+F5
- Dit herlaadt alles opnieuw

---

## 🎯 Samenvatting

**Probleem:** MacBook blokkeert pornografische content niet

**Oplossing:**
1. ✅ Log in op MacBook (`public/index.html`)
2. ✅ Device wordt automatisch toegevoegd
3. ✅ Filter wordt automatisch geactiveerd
4. ✅ Test filtering

**Als het nog steeds niet werkt:**
1. Check device status (moet "active" zijn)
2. Check abonnement status (moet "active" zijn)
3. Check browser console voor errors
4. Herinitialiseer filter handmatig

---

**Het systeem werkt automatisch - je hoeft alleen in te loggen!** 🚀
