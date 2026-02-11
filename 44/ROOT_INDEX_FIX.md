# 🔧 Fix: Root index.html Werkt Niet

## Probleem
`app.js` gebruikte een vaste API path `../api/` die alleen werkt vanuit subdirectories zoals `public/`. Voor de root `index.html` moet het `api/` zijn.

## ✅ Oplossing
API path wordt nu automatisch gedetecteerd op basis van waar `app.js` wordt geladen:
- Root (`index.html`) → `api/`
- Subdirectory (`public/index.html`) → `../api/`

## 🧪 Test Nu

### Stap 1: Test Root
1. Ga naar: `http://localhost/1/`
2. Site zou nu moeten werken!

### Stap 2: Test Subdirectory
1. Ga naar: `http://localhost/1/public/index.html`
2. Dit zou ook moeten werken

## 🔍 Als Het Nog Steeds Niet Werkt

### Check 1: Browser Console
1. Open browser console (F12)
2. Kijk naar errors
3. Check of API calls werken

### Check 2: Network Tab
1. Open Network tab (F12)
2. Reload pagina
3. Check of `app.js` wordt geladen (status 200)
4. Check of API calls werken

### Check 3: Test API Direct
```javascript
// In browser console:
fetch('api/health.php')
  .then(r => r.json())
  .then(d => console.log('API works:', d))
  .catch(e => console.error('API error:', e));
```

---

**Root index.html zou nu moeten werken!** 🎉
