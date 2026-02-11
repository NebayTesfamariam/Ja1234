# 🌐 DNS Setup Guide voor ja1234.com

## ⚠️ Huidige Status

Je domein wijst momenteel naar **DNS Parking**:
- `ns1.dns-parking.com`
- `ns2.dns-parking.com`

Dit betekent dat `ja1234.com` **niet** naar jouw server wijst, maar naar een parking pagina.

---

## ✅ Oplossing: DNS Records Instellen

### Stap 1: Vind je Server IP Adres

Je hebt het IP-adres van je server nodig. Dit krijg je van je hosting provider.

**Check je server IP:**
```bash
# Op je server:
curl ifconfig.me
# Of:
hostname -I
```

**Voorbeelden:**
- Shared hosting: Meestal `185.x.x.x` of `198.x.x.x`
- VPS: Het IP dat je hosting provider heeft gegeven
- Cloud hosting: Check in je hosting dashboard

---

### Stap 2: Log in bij je Domain Registrar

Ga naar de website waar je het domein `ja1234.com` hebt geregistreerd (bijvoorbeeld:
- Namecheap
- GoDaddy
- TransIP
- Hostinger
- etc.)

---

### Stap 3: Wijzig DNS Nameservers (OPTIE 1 - Aanbevolen)

Als je hosting provider eigen nameservers heeft:

1. Ga naar **DNS Management** of **Nameservers** in je domain registrar
2. Wijzig de nameservers naar die van je hosting provider

**Voorbeelden:**
- **cPanel hosting:** Meestal `ns1.jouwhosting.com` en `ns2.jouwhosting.com`
- **Cloudflare:** `ns1.cloudflare.com` en `ns2.cloudflare.com`
- **VPS:** Check bij je hosting provider

---

### Stap 4: Stel DNS Records In (OPTIE 2 - Als je nameservers houdt)

Als je de parking nameservers wilt houden, moet je DNS records instellen bij je domain registrar:

#### A Record (Verplicht)
```
Type: A
Name: @ (of ja1234.com)
Value: [JOUW SERVER IP]
TTL: 3600 (of Auto)
```

#### CNAME Record (Optioneel - voor www)
```
Type: CNAME
Name: www
Value: ja1234.com
TTL: 3600 (of Auto)
```

---

## 📋 Stap-voor-Stap Instructies per Provider

### Namecheap
1. Log in → **Domain List** → Klik op **Manage** naast `ja1234.com`
2. Ga naar **Advanced DNS** tab
3. Klik **Add New Record**
4. Select **A Record**
5. Host: `@` (of leeg laten)
6. Value: `[JOUW SERVER IP]`
7. TTL: `Automatic`
8. Klik **Save**

### GoDaddy
1. Log in → **My Products** → **DNS** naast `ja1234.com`
2. Scroll naar **Records** sectie
3. Klik **Add** → Select **A**
4. Name: `@`
5. Value: `[JOUW SERVER IP]`
6. TTL: `600 seconds`
7. Klik **Save**

### TransIP
1. Log in → **Domeinen** → Klik op `ja1234.com`
2. Ga naar **DNS** tab
3. Klik **Record toevoegen**
4. Type: `A`
5. Naam: `@`
6. Waarde: `[JOUW SERVER IP]`
7. TTL: `3600`
8. Klik **Toevoegen**

### Hostinger
1. Log in → **Domains** → Klik op `ja1234.com`
2. Ga naar **DNS / Nameservers** tab
3. Scroll naar **DNS Records**
4. Klik **Add Record**
5. Type: `A`
6. Name: `@`
7. Points to: `[JOUW SERVER IP]`
8. TTL: `14400`
9. Klik **Add Record**

---

## 🔍 Verificatie

Na het instellen van de DNS records, wacht 5-30 minuten en test:

### Test 1: Check DNS Records
```bash
# Check A record
dig ja1234.com A
# Of:
nslookup ja1234.com

# Je zou je server IP moeten zien
```

### Test 2: Check Website
Open in browser:
```
https://ja1234.com
```

Je zou je website moeten zien, niet een parking pagina.

### Test 3: Check SSL Certificate
Als je SSL hebt:
```
https://ja1234.com
```

De browser zou geen SSL warning moeten geven.

---

## ⚠️ Belangrijke Notities

1. **DNS Propagation:** Het kan 5 minuten tot 48 uur duren voordat DNS wijzigingen wereldwijd actief zijn. Meestal is het binnen 30 minuten.

2. **TTL Waarde:** 
   - Laag (300-600): Snellere updates, maar meer DNS queries
   - Hoog (3600-86400): Langzamere updates, maar minder DNS queries
   - Aanbevolen: 3600 (1 uur)

3. **Nameservers vs DNS Records:**
   - **Nameservers wijzigen:** Meestal beter, geeft volledige controle
   - **DNS Records instellen:** Werkt ook, maar beperkt tot wat je registrar toestaat

4. **Cloudflare (Aanbevolen):**
   - Gratis SSL certificate
   - DDoS bescherming
   - Snellere laadtijden
   - Eenvoudige DNS management
   - **Setup:** Gebruik Cloudflare nameservers en stel A record in via Cloudflare dashboard

---

## 🚀 Snelle Setup met Cloudflare (Aanbevolen)

1. Maak gratis account op [cloudflare.com](https://cloudflare.com)
2. Voeg domein `ja1234.com` toe
3. Cloudflare geeft je 2 nameservers (bijv. `ns1.cloudflare.com` en `ns2.cloudflare.com`)
4. Wijzig nameservers bij je domain registrar naar Cloudflare nameservers
5. Wacht 5-30 minuten
6. Ga naar Cloudflare dashboard → **DNS** → **Records**
7. Voeg A record toe:
   - Type: `A`
   - Name: `@`
   - IPv4 address: `[JOUW SERVER IP]`
   - Proxy: ✅ (aanbevolen voor DDoS bescherming)
8. Klaar! Cloudflare regelt SSL automatisch.

---

## 📞 Hulp Nodig?

Als je niet weet wat je server IP is:
1. Check je hosting provider dashboard
2. Check je hosting provider support/email
3. Vraag je hosting provider om hulp

Als je niet weet waar je domein is geregistreerd:
1. Check je email voor registratie bevestiging
2. Gebruik WHOIS lookup: `whois ja1234.com`
3. Check je creditcard statements voor hosting/domain kosten

---

## ✅ Checklist

- [ ] Server IP adres gevonden
- [ ] Ingelogd bij domain registrar
- [ ] A Record ingesteld (`@` → `[SERVER IP]`)
- [ ] CNAME Record ingesteld (`www` → `ja1234.com`) - optioneel
- [ ] 30 minuten gewacht voor DNS propagation
- [ ] Website getest: `https://ja1234.com`
- [ ] SSL certificate werkt (als van toepassing)

---

## 🎯 Na DNS Setup

Zodra DNS correct is ingesteld:
1. ✅ Website is bereikbaar op `https://ja1234.com`
2. ✅ SSL certificate werkt (als geconfigureerd)
3. ✅ Admin panel werkt: `https://ja1234.com/admin/index.html`
4. ✅ API endpoints werken: `https://ja1234.com/api/login.php`

Test alles met: `https://ja1234.com/CHECK_WEBSITE.php`
