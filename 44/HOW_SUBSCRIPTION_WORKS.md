# 📋 Hoe Abonnement Aansluiten Werkt

## Overzicht
Dit document legt uit hoe het systeem werkt wanneer een gebruiker een abonnement aansluit (Basic, Family, of Premium).

---

## 🚀 Volledige Flow (Stap voor Stap)

### Stap 1: Gebruiker Kiest Abonnement
**Locatie:** `subscribe.html`

Gebruiker klikt op "Abonnement Aansluiten" bij:
- **Basic** (€9.99/maand - 2 Devices)
- **Family** (€19.99/maand - 5 Devices)  
- **Premium** (€29.99/maand - 10 Devices)

### Stap 2: Registratie Formulier
**Wat gebeurt er:**
- Gebruiker vult in: Email, Wachtwoord, Bevestig Wachtwoord
- Systeem controleert of email al bestaat
- Als email bestaat → controleert wachtwoord
- Als email nieuw is → maakt nieuw account aan

### Stap 3: Stripe Checkout
**API:** `api/stripe_create_checkout.php`

**Wat gebeurt er:**
1. ✅ Gebruiker wordt aangemaakt (als nieuw)
2. ✅ Stripe Checkout Session wordt aangemaakt
3. ✅ Gebruiker wordt doorgestuurd naar Stripe betalingspagina
4. ✅ Gebruiker betaalt met creditcard

### Stap 4: Betaling Succesvol
**Pagina:** `stripe_success.php`

**Wat gebeurt er AUTOMATISCH:**
1. ✅ **Abonnement wordt aangemaakt** (status: `active`)
   - Plan: basic/family/premium
   - Start datum: Vandaag
   - Eind datum: Over 1 maand
   - Status: `active` (direct actief!)

2. ✅ **Device wordt AUTOMATISCH aangemaakt**
   - Device naam: Automatisch gedetecteerd (iPhone, Android, PC, etc.)
   - WireGuard key: Automatisch gegenereerd
   - IP adres: Automatisch toegewezen (10.10.0.x)
   - Status: `active` (direct actief!)
   - `auto_created = 1` (permanent, kan niet worden verwijderd)

3. ✅ **Alle bestaande devices worden geactiveerd**
   - Als gebruiker al devices had → worden direct geactiveerd
   - Devices werken direct na abonnement

4. ✅ **Login token wordt gegenereerd**
   - Gebruiker kan direct inloggen
   - Geen extra stappen nodig

### Stap 5: Direct Actief!
**Wat werkt direct:**
- ✅ Pornografische content wordt direct geblokkeerd
- ✅ Device is actief en beschermd
- ✅ Werkt op Wi-Fi, 4G, 5G
- ✅ Werkt in alle browsers (Chrome, Firefox, Safari, Edge)
- ✅ Browser filter is actief
- ✅ Geen extra configuratie nodig!

---

## 📊 Abonnement Details

### Basic Plan (€9.99/maand)
- **Max Devices:** 2
- **Features:**
  - 100% Pornografische Blokkering
  - Automatische Updates
  - 24/7 Bescherming
  - Werkt op Wi-Fi, 4G, 5G

### Family Plan (€19.99/maand)
- **Max Devices:** 5
- **Features:**
  - 100% Pornografische Blokkering
  - Automatische Updates
  - 24/7 Bescherming
  - Werkt op Wi-Fi, 4G, 5G
  - Per Device Beheer

### Premium Plan (€29.99/maand)
- **Max Devices:** 10
- **Features:**
  - 100% Pornografische Blokkering
  - Automatische Updates
  - 24/7 Bescherming
  - Werkt op Wi-Fi, 4G, 5G
  - Per Device Beheer
  - Prioriteit Support

---

## 🔄 Automatische Processen

### 1. Device Automatisch Aanmaken
**Wanneer:** Direct na succesvolle betaling

**Wat gebeurt er:**
```php
// Automatisch device naam detecteren
iPhone → "iPhone"
Android → "Android Device"
Windows → "Windows PC"
Mac → "Mac"
Linux → "Linux PC"

// Automatisch WireGuard key genereren
$wg_public_key = base64_encode(random_bytes(32));

// Automatisch IP adres toewijzen
$wg_ip = "10.10.0.{next_available}";

// Device aanmaken met status 'active'
INSERT INTO devices (user_id, name, wg_public_key, wg_ip, status, auto_created)
VALUES (?, ?, ?, ?, 'active', 1);
```

**Resultaat:**
- ✅ Device is direct actief
- ✅ Geen handmatige configuratie nodig
- ✅ Werkt direct na aanmelding

### 2. Bestaande Devices Activeren
**Wanneer:** Direct na abonnement aanmaken

**Wat gebeurt er:**
```php
// Alle geblokkeerde devices worden geactiveerd
UPDATE devices 
SET status = 'active' 
WHERE user_id = ? 
  AND status = 'blocked' 
  AND permanent_blocked = 0;
```

**Resultaat:**
- ✅ Alle devices werken direct
- ✅ Geen handmatige activatie nodig

### 3. Browser Filter Activeren
**Wanneer:** Direct na device aanmaken

**Wat gebeurt er:**
- Browser filter wordt automatisch geïnitialiseerd
- Blocklist wordt geladen
- Service Worker wordt geregistreerd
- Request interceptie wordt geactiveerd

**Resultaat:**
- ✅ Pornografische content wordt direct geblokkeerd
- ✅ Werkt in alle browsers
- ✅ Werkt zonder DNS/VPN server

---

## 🛡️ Wat Werkt Direct Na Aanmelding

### 1. Pornografische Blokkering
- ✅ **100% Automatisch:** Geen handmatige actie nodig
- ✅ **Direct Actief:** Werkt meteen na betaling
- ✅ **Permanent:** Kan niet worden uitgeschakeld
- ✅ **Overal:** Werkt op Wi-Fi, 4G, 5G
- ✅ **Alle Browsers:** Chrome, Firefox, Safari, Edge

### 2. Device Beheer
- ✅ **Automatisch Device:** Eerste device wordt automatisch aangemaakt
- ✅ **Device Limiet:** Respecteert plan limiet (2/5/10 devices)
- ✅ **Per Device Beheer:** Elk device kan apart worden beheerd
- ✅ **Whitelist:** Per device whitelist mogelijk

### 3. Automatische Updates
- ✅ **Blocklist Updates:** Automatisch bijgewerkt
- ✅ **Nieuwe Sites:** Automatisch gedetecteerd en geblokkeerd
- ✅ **Keyword Detection:** Automatische detectie van pornografische content
- ✅ **Google Search Filtering:** Blokkeert pornografische zoekresultaten

---

## 📱 Device Toevoegen (Na Eerste Device)

### Automatisch bij Login
**Wanneer:** Gebruiker logt in vanaf nieuw device

**Wat gebeurt er:**
1. ✅ Systeem detecteert nieuw device
2. ✅ Controleert device limiet
3. ✅ Als limiet niet bereikt → device wordt automatisch aangemaakt
4. ✅ Device is direct actief en beschermd

### Handmatig Toevoegen
**Wanneer:** Gebruiker voegt device toe via dashboard

**Wat gebeurt er:**
1. ✅ Gebruiker klikt "Device Toevoegen"
2. ✅ Systeem controleert device limiet
3. ✅ Als limiet niet bereikt → device wordt aangemaakt
4. ✅ WireGuard key en IP worden automatisch gegenereerd
5. ✅ Device is direct actief

### Via Registratie Link
**Wanneer:** Gebruiker genereert registratie link

**Wat gebeurt er:**
1. ✅ Gebruiker genereert link via dashboard
2. ✅ Link wordt gedeeld (bijv. met kind)
3. ✅ Link wordt geopend op nieuw device
4. ✅ Device wordt automatisch aangemaakt
5. ✅ Device is direct actief en beschermd

---

## 🔒 Beveiliging & Blokkering

### Automatische Blokkering
**Wat wordt geblokkeerd:**
- ✅ Pornografische websites (permanent blocklist)
- ✅ Pornografische content via normale sites (keyword detection)
- ✅ Google zoekresultaten met pornografische content
- ✅ Embedded content (images, videos, iframes)
- ✅ Links naar pornografische content

### Fail-Safe Systeem
**Bij problemen:**
- ❌ Device niet gevonden → **Alles geblokkeerd**
- ❌ Device niet actief → **Alles geblokkeerd**
- ❌ Abonnement verlopen → **Alles geblokkeerd**
- ❌ Database fout → **Alles geblokkeerd**
- ❌ API offline → **Alles geblokkeerd**

**Resultaat:** Bij twijfel → altijd blokkeren (fail-safe)

---

## 💳 Betaling & Abonnement

### Stripe Integratie
**Hoe het werkt:**
1. ✅ Gebruiker betaalt via Stripe Checkout
2. ✅ Betaling wordt verwerkt door Stripe
3. ✅ Webhook ontvangt bevestiging
4. ✅ Abonnement wordt automatisch aangemaakt
5. ✅ Devices worden automatisch geactiveerd

### Automatische Verlenging
**Hoe het werkt:**
- ✅ Stripe handelt automatische verlenging af
- ✅ Webhook ontvangt betalingsbevestiging
- ✅ Abonnement wordt automatisch verlengd
- ✅ Devices blijven actief

### Abonnement Verlopen
**Wat gebeurt er:**
- ❌ Abonnement status → `expired`
- ❌ Alle devices worden geblokkeerd (behalve admin-created)
- ❌ Pornografische content wordt geblokkeerd (fail-safe)
- ✅ Bij nieuwe betaling → devices worden automatisch geactiveerd

---

## 🎯 Samenvatting

### Wat Gebeurt Er Automatisch:
1. ✅ **Account Aanmaken** → Automatisch
2. ✅ **Abonnement Aanmaken** → Automatisch (na betaling)
3. ✅ **Device Aanmaken** → Automatisch (na abonnement)
4. ✅ **Device Activeren** → Automatisch (direct actief)
5. ✅ **Blokkering Activeren** → Automatisch (direct actief)
6. ✅ **Browser Filter** → Automatisch (direct actief)

### Wat Gebruiker Moet Doen:
1. ✅ Kies abonnement (Basic/Family/Premium)
2. ✅ Vul email en wachtwoord in
3. ✅ Betaal via Stripe
4. ✅ **KLAAR!** - Alles werkt automatisch!

### Resultaat:
- ✅ **Direct Beschermd:** Pornografische content wordt direct geblokkeerd
- ✅ **Geen Configuratie:** Alles werkt automatisch
- ✅ **24/7 Bescherming:** Werkt altijd, overal
- ✅ **Zero-Touch:** Geen handmatige acties nodig

---

## 🔧 Technische Details

### Database Tabellen
- **users:** Gebruiker account
- **subscriptions:** Abonnement informatie
- **devices:** Device informatie
- **whitelist:** Toegestane domeinen per device
- **blocklist_permanent:** Permanente blocklist
- **activity_logs:** Activiteit logs

### API Endpoints
- **stripe_create_checkout.php:** Maakt Stripe checkout aan
- **stripe_webhook.php:** Handelt Stripe events af
- **stripe_success.php:** Succes pagina na betaling
- **auto_register_device.php:** Registreert device automatisch
- **get_blocklist.php:** Haalt blocklist op voor device

### Automatische Processen
- **auto_check_expired_subscriptions():** Controleert verlopen abonnementen
- **Browser Filter:** Blokkeert pornografische content in browser
- **Service Worker:** Intercepteert network requests
- **Keyword Detection:** Detecteert pornografische content

---

**Het systeem is volledig automatisch - gebruiker hoeft alleen te betalen en alles werkt direct!** 🚀
