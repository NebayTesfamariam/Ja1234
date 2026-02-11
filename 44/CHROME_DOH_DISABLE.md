# 🚫 Chrome DNS-over-HTTPS (DoH) Uitschakelen (STAP 1)

## 🎯 Doel
Zorgen dat **Chrome GEEN eigen DNS meer gebruikt**, maar **ALLEEN jouw VPN + DNS**.

Zolang DoH aan staat, kan porno-video blijven laden ❌

---

## ✅ STAP 1 — Chrome DoH Uitzetten

### 1️⃣ Open Chrome

Typ in de adresbalk:

```
chrome://settings/security
```

**Of:**
- Klik op het menu (3 puntjes rechtsboven)
- Ga naar **Instellingen** → **Privacy en beveiliging** → **Beveiliging**

---

### 2️⃣ Zoek **"Use secure DNS"**

Scroll naar beneden tot je **"Use secure DNS"** ziet.

**Nederlandse versie:** *"Gebruik beveiligde DNS"*

---

### 3️⃣ Zet dit UIT ❌

**Optie A: Uitgeschakeld**
- Zet de schakelaar op **UIT** / **OFF**

**Optie B: Met je huidige provider**
- Kies: **"With your current service provider"**
- **Nederlands:** *"Met je huidige serviceprovider"*

### ❌ NIET Toegestaan:

- ❌ **Google** (Public DNS)
- ❌ **Cloudflare** (1.1.1.1)
- ❌ **Custom** (aangepast)
- ❌ **Quad9** (9.9.9.9)
- ❌ **Any other provider**

---

### 4️⃣ Sluit Chrome volledig

**Belangrijk:** Chrome moet volledig afgesloten worden om de instellingen toe te passen.

**Windows:**
- Klik rechtsonder op Chrome in de taakbalk
- Kies "Venster sluiten"
- Of: `Alt + F4` op Chrome venster
- Check Task Manager: geen Chrome processen meer

**Mac:**
- `Cmd + Q` om Chrome volledig af te sluiten
- Of: Chrome menu → "Google Chrome afsluiten"
- Check Activity Monitor: geen Chrome processen meer

**Linux:**
- Sluit alle Chrome vensters
- Check: `ps aux | grep chrome` (geen processen)

---

## ✅ Test na STAP 1 (Verplicht)

### Test Setup:

1. **VPN AAN** op het device
2. **Whitelist LEEG** voor het device
3. **Chrome opnieuw openen** (volledig afgesloten en heropend)
4. **Test deze sites:**

### Test Sites:

| Site | Verwacht Resultaat |
|------|-------------------|
| `google.com` | ❌ NIET laden |
| `wikipedia.org` | ❌ NIET laden |
| Porno-site | ❌ NIET laden |
| Willekeurige site | ❌ NIET laden |

### ✅ Correct Resultaat:

```
NIETS laadt
```

**Als porno nu nog steeds laadt**, dan is dit **QUIC** (STAP 2).

---

## 🔍 Verificatie: Is DoH Uit?

### Methode 1: Chrome Settings Check

1. Ga naar: `chrome://settings/security`
2. Check "Use secure DNS" → moet **UIT** zijn of **"With your current service provider"**

### Methode 2: Chrome Netwerk Logs

1. Open Chrome DevTools: `F12` of `Cmd+Option+I` (Mac) / `Ctrl+Shift+I` (Windows)
2. Ga naar **Network** tab
3. Filter op: `doh` of `dns`
4. Probeer een site te laden
5. **Je zou GEEN DoH requests moeten zien**

### Methode 3: DNS Query Check

1. Open Chrome DevTools: `F12`
2. Ga naar **Network** tab
3. Filter op: `dns`
4. Probeer een site te laden
5. **Alle DNS queries moeten naar jouw VPN DNS gaan** (10.10.0.1)

---

## ⚠️ Belangrijk

- **DoH moet UIT** voor alle Chrome profielen
- **Chrome moet volledig afgesloten** worden na wijziging
- **Test altijd** na het uitschakelen
- **Als DoH nog aan staat**, kan content nog steeds lekken

---

## 🔧 Troubleshooting

### Als DoH niet uit kan:

1. **Check Chrome versie:**
   - Oudere versies hebben mogelijk geen DoH optie
   - Update Chrome naar nieuwste versie

2. **Check Enterprise policies:**
   - Bedrijfs-Chrome kan DoH geforceerd hebben
   - Check: `chrome://policy`

3. **Check Extensions:**
   - Sommige extensies kunnen DoH forceren
   - Test in Incognito mode (extensions uit)

### Als sites nog steeds laden:

1. **Check VPN verbinding:**
   ```bash
   # Op device
   curl ifconfig.me
   # Moet VPN server IP zijn, niet je eigen IP
   ```

2. **Check DNS:**
   ```bash
   # Op device
   nslookup google.com
   # Moet VPN DNS zijn (10.10.0.1)
   ```

3. **Check QUIC:**
   - Als DoH uit is maar content nog laadt → QUIC probleem
   - Zie STAP 2 voor QUIC blocking

---

## 📝 Notities

- DoH uitschakelen is **kritisch** voor DNS filtering
- Zonder dit kan Chrome eigen DNS gebruiken en filtering omzeilen
- Combineer met VPN DNS forcing en QUIC blocking voor volledige bescherming

---

## 🔗 Volgende Stappen

- **STAP 2:** QUIC blocking (als content nog steeds laadt)
- **STAP 3:** DNS forcing verificatie
- **Firewall Setup:** Volledige configuratie

---

## ✅ Checklist

- [ ] Chrome DoH uitgeschakeld
- [ ] Chrome volledig afgesloten
- [ ] Chrome opnieuw geopend
- [ ] Test uitgevoerd: niets laadt met lege whitelist
- [ ] DoH verificatie gedaan (DevTools check)
