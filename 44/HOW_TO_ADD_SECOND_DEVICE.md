# 📱 Hoe Tweede Device Toevoegen

## Overzicht
Dit document legt uit hoe gebruikers een tweede (of derde, vierde, etc.) device kunnen toevoegen aan hun account.

---

## 🚀 Methoden om Device Toe te Voegen

Er zijn **3 eenvoudige manieren** om een device toe te voegen:

### 1️⃣ **Automatisch bij Login** (Meest Eenvoudig)
**Hoe het werkt:**
- Log in vanaf je nieuwe device (iPhone, laptop, tablet, etc.)
- Het systeem detecteert automatisch dat dit een nieuw device is
- Device wordt automatisch geregistreerd en toegevoegd
- **Geen extra stappen nodig!**

**Stappen:**
1. Open `public/index.html` op je nieuwe device
2. Log in met je email en wachtwoord
3. **KLAAR!** - Device wordt automatisch toegevoegd

**Voordelen:**
- ✅ Volledig automatisch
- ✅ Geen handmatige actie nodig
- ✅ Device naam wordt automatisch gedetecteerd
- ✅ WireGuard key en IP worden automatisch gegenereerd

---

### 2️⃣ **Via "Device Toevoegen" Knop** (1 Klik)
**Hoe het werkt:**
- Klik op de knop "Device Toevoegen (1 Klik!)" in je dashboard
- Het systeem detecteert automatisch je device type
- Device wordt direct toegevoegd en geactiveerd

**Stappen:**
1. Log in op je dashboard (`public/index.html`)
2. Klik op de knop **"➕ Device Toevoegen (1 Klik!)"**
3. **KLAAR!** - Device wordt automatisch toegevoegd

**Voordelen:**
- ✅ Super eenvoudig - 1 klik
- ✅ Automatische device detectie
- ✅ Direct actief en beschermd

**Waar te vinden:**
- In je dashboard onder "➕ Nieuw Device Toevoegen"
- Grote groene knop met "Device Toevoegen (1 Klik!)"

---

### 3️⃣ **Via Registratie Link** (Voor Delen)
**Hoe het werkt:**
- Genereer een speciale link in je dashboard
- Deel deze link met anderen of open op een ander device
- Device wordt automatisch toegevoegd zonder in te loggen

**Stappen:**
1. Log in op je dashboard (`public/index.html`)
2. Klik op **"🔗 Mijn Registratie Link Genereren"**
3. Kopieer de gegenereerde link
4. Open de link op je nieuwe device (of deel met anderen)
5. **KLAAR!** - Device wordt automatisch toegevoegd

**Voordelen:**
- ✅ Geen login nodig op nieuw device
- ✅ Perfect voor delen met anderen (bijv. kinderen)
- ✅ Link verloopt automatisch na 7 dagen
- ✅ Beperkt aantal gebruik (standaard 1x)

**Waar te vinden:**
- In je dashboard onder "🔗 Of gebruik een registratie link"
- Knop: "🔗 Mijn Registratie Link Genereren"

---

## 📋 Device Limieten per Abonnement

### Basic Plan (€9.99/maand)
- **Max Devices:** 2
- Je kunt maximaal 2 devices toevoegen

### Family Plan (€19.99/maand)
- **Max Devices:** 5
- Je kunt maximaal 5 devices toevoegen

### Premium Plan (€29.99/maand)
- **Max Devices:** 10
- Je kunt maximaal 10 devices toevoegen

**⚠️ Belangrijk:**
- Als je device limiet bereikt, kun je geen nieuwe devices meer toevoegen
- Upgrade je plan om meer devices toe te voegen
- Bestaande devices blijven actief

---

## 🔒 Duplicate Preventie

Het systeem voorkomt automatisch dat hetzelfde device meerdere keren wordt toegevoegd:

**Checks:**
1. ✅ **WireGuard Key:** Controleert of key al bestaat
2. ✅ **IP Adres:** Controleert of IP al in gebruik is
3. ✅ **Device Naam:** Controleert of naam recent is gebruikt (30 dagen)
4. ✅ **Device Type:** Controleert of gebruiker al 1 device van dit type heeft

**Resultaat:**
- Als device al bestaat → bestaand device wordt geretourneerd
- Geen duplicaten mogelijk
- Geen extra kosten voor hetzelfde device

---

## 🛡️ Wat Gebeurt Er Na Toevoegen?

### Direct Actief
- ✅ Device wordt direct geactiveerd
- ✅ Status: `active`
- ✅ Pornografische content wordt direct geblokkeerd
- ✅ Werkt op Wi-Fi, 4G, 5G
- ✅ Werkt in alle browsers (Chrome, Firefox, Safari, Edge)

### Automatische Configuratie
- ✅ Device naam wordt automatisch gedetecteerd
- ✅ WireGuard key wordt automatisch gegenereerd
- ✅ IP adres wordt automatisch toegewezen (10.10.0.x)
- ✅ Browser filter wordt automatisch geactiveerd

### Geen Extra Stappen
- ❌ Geen handmatige configuratie nodig
- ❌ Geen VPN instellingen nodig
- ❌ Geen DNS wijzigingen nodig
- ✅ Alles werkt automatisch!

---

## 💡 Tips & Tricks

### Tip 1: Automatische Device Detectie
Het systeem detecteert automatisch je device type:
- **iPhone** → "iPhone"
- **iPad** → "iPad"
- **Android** → "Android Device"
- **Windows** → "Windows PC"
- **Mac** → "Mac"
- **Linux** → "Linux PC"

### Tip 2: Meerdere Devices van Zelfde Type
Als je meerdere devices van hetzelfde type hebt (bijv. 2 iPhones):
- Eerste device: "iPhone"
- Tweede device: "iPhone 2"
- Derde device: "iPhone 3"
- etc.

### Tip 3: Registratie Link Delen
Registratie links zijn perfect voor:
- ✅ Kinderen toevoegen zonder login
- ✅ Familie toevoegen
- ✅ Vrienden toevoegen
- ✅ Gebruik op gedeelde devices

### Tip 4: Device Limiet Controleren
Controleer je device limiet voordat je een nieuw device toevoegt:
- Log in op je dashboard
- Bekijk je abonnement info
- Zie hoeveel devices je al hebt
- Zie hoeveel devices je nog kunt toevoegen

---

## ❓ Veelgestelde Vragen

### Q: Kan ik hetzelfde device twee keer toevoegen?
**A:** Nee, het systeem voorkomt automatisch duplicaten. Als je hetzelfde device probeert toe te voegen, wordt het bestaande device geretourneerd.

### Q: Wat als ik mijn device limiet bereik?
**A:** Je kunt geen nieuwe devices meer toevoegen. Upgrade je plan om meer devices toe te voegen, of verwijder een bestaand device (via admin).

### Q: Moet ik iets configureren na toevoegen?
**A:** Nee! Alles werkt automatisch. Geen handmatige configuratie nodig.

### Q: Werkt het op alle devices?
**A:** Ja! Werkt op iPhone, iPad, Android, Windows PC, Mac, Linux - alle devices.

### Q: Kan ik een device verwijderen?
**A:** Alleen administrators kunnen devices verwijderen. Normale gebruikers kunnen devices niet verwijderen.

### Q: Wat gebeurt er als mijn abonnement verloopt?
**A:** Alle devices worden automatisch geblokkeerd. Betaal opnieuw om devices te reactiveren.

---

## 📊 Overzicht: Alle Methoden

| Methode | Eenvoud | Snelheid | Delen Mogelijk |
|---------|---------|----------|----------------|
| **Automatisch bij Login** | ⭐⭐⭐⭐⭐ | ⚡⚡⚡ | ❌ |
| **Device Toevoegen Knop** | ⭐⭐⭐⭐⭐ | ⚡⚡⚡ | ❌ |
| **Registratie Link** | ⭐⭐⭐⭐ | ⚡⚡ | ✅ |

**Aanbeveling:**
- **Eerste device:** Automatisch bij login (bij abonnement)
- **Tweede device:** Automatisch bij login of "Device Toevoegen" knop
- **Delen met anderen:** Registratie link

---

## 🎯 Samenvatting

### Eenvoudigste Methode (Aanbevolen):
1. Log in op je nieuwe device
2. **KLAAR!** - Device wordt automatisch toegevoegd

### Alternatief:
1. Log in op je dashboard
2. Klik op "Device Toevoegen (1 Klik!)"
3. **KLAAR!** - Device wordt automatisch toegevoegd

### Voor Delen:
1. Genereer registratie link
2. Deel link of open op nieuw device
3. **KLAAR!** - Device wordt automatisch toegevoegd

**Het systeem is volledig automatisch - geen handmatige configuratie nodig!** 🚀
