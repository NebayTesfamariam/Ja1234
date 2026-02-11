# 🚨 SNEL FIX: MacBook Blokkeert Niet

## ⚡ Directe Oplossing (3 Stappen)

### Stap 1: Open Browser Console
1. Open je browser op MacBook
2. Druk op **F12** of **Cmd+Option+I** (Mac)
3. Ga naar tab **"Console"**

### Stap 2: Voer Dit Uit
Kopieer en plak dit in de console:

```javascript
// Clear alles en start opnieuw
localStorage.clear();
location.reload();
```

### Stap 3: Log Opnieuw In
1. Na reload, log in met je email en wachtwoord
2. Device wordt automatisch toegevoegd
3. Filter wordt automatisch geactiveerd

---

## ✅ Test Of Het Werkt

Na login, test dit in console:

```javascript
// Test blocking
window.browserFilter.shouldBlock('https://xhamster.com');
// Moet: true (geblokkeerd)

// Test een normale site
window.browserFilter.shouldBlock('https://google.com');
// Moet: false (toegestaan)
```

---

## 🔍 Als Het Nog Steeds Niet Werkt

### Check 1: Is Filter Geladen?
In console, zoek naar:
- `✅ Browser filter: Active and ready`
- `Browser filter: Loaded X blocked domains`

### Check 2: Is Device ID Aanwezig?
In console, voer uit:
```javascript
console.log('Device ID:', localStorage.getItem('device_id'));
console.log('Token:', localStorage.getItem('token'));
```

**Moet zijn:**
- Device ID: een nummer (bijv. "1", "2")
- Token: een string (bijv. "MjokMnkkMTAkVHdCcFA=...")

### Check 3: Is Device Actief?
1. Ga naar dashboard
2. Kijk onder "📱 Jouw Devices"
3. Status moet zijn: **"active"** (groen)

---

## 🛠️ Handmatige Fix

Als automatische fix niet werkt:

```javascript
// 1. Clear alles
localStorage.clear();

// 2. Log opnieuw in (via normale login)

// 3. Na login, forceer filter activatie
if (window.browserFilter) {
  window.browserFilter.deviceId = localStorage.getItem('device_id');
  window.browserFilter.token = localStorage.getItem('token');
  await window.browserFilter.loadBlocklist();
  window.browserFilter.setupInterception();
  console.log('✅ Filter geforceerd geactiveerd');
}
```

---

## 📱 Belangrijk

**Het filter werkt NU met fail-safe mode:**
- ✅ Blokkeert bekende pornografische sites ZELFS zonder device ID
- ✅ Werkt automatisch na login
- ✅ Werkt op alle netwerken (hotspot, Wi-Fi, 4G, 5G)

**Test het nu:**
1. Probeer: `https://xhamster.com`
2. Moet geblokkeerd worden!

---

**Als het nog steeds niet werkt, stuur een screenshot van je browser console!**
