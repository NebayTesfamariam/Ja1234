# 🛡️ SafeSearch Enforcement - Complete Implementatie

## Overzicht

SafeSearch wordt **automatisch afgedwongen** op netwerkniveau en kan **NIET worden uitgeschakeld** door gebruikers. Dit is een **extra beschermingslaag** naast de bestaande 100% blokkering.

---

## 🎯 Doel van SafeSearch

SafeSearch is **GEEN vervanging** voor de bestaande filtering, maar een **extra laag** voor:

- ✅ Zoekresultaten filtering (tekst, afbeeldingen, video)
- ✅ Expliciete thumbnails blokkering
- ✅ Grafisch geweld filtering
- ✅ Google Image Search filtering
- ✅ YouTube Restricted Mode

**Kernbeveiliging blijft:**
> **Whitelist + VPN + DNS = 100% blokkering**

---

## 🔒 Belangrijk Gedrag (Verplicht)

SafeSearch moet:

- ✅ **Automatisch AAN staan** (altijd)
- ✅ **Niet uitzetbaar zijn** door de gebruiker
- ✅ **Afgedwongen worden** op netwerkniveau
- ✅ **Browser melding tonen**: "You can't change your SafeSearch setting because it's controlled by your network administrator"

Dit is **bewust gewenst gedrag** - geen bug, maar een feature!

---

## 🔧 Technische Implementatie

### 1. DNS SafeSearch Forcing

Voor zoekmachines moet DNS worden geforceerd naar SafeSearch-servers.

#### Google SafeSearch
```
www.google.com          → forcesafesearch.google.com
www.google.nl           → forcesafesearch.google.com
www.google.de           → forcesafesearch.google.com
images.google.com       → forcesafesearch.google.com
```

#### Bing SafeSearch
```
www.bing.com            → strict.bing.com
www.bing.nl             → strict.bing.com
```

#### YouTube Restricted Mode
```
youtube.com             → restrict.youtube.com
www.youtube.com         → restrict.youtube.com
m.youtube.com           → restrict.youtube.com
```

#### DuckDuckGo SafeSearch
```
duckduckgo.com          → safe.duckduckgo.com
```

#### Yandex SafeSearch
```
yandex.com              → family.yandex.com
```

---

### 2. Browser-Level SafeSearch

Naast DNS-forcing wordt SafeSearch ook afgedwongen in de browser:

#### Google Search
- URL parameter: `&safe=active` wordt automatisch toegevoegd
- Cookie wordt gezet: `PREF=f2=8000000` (SafeSearch enabled)
- JavaScript controleert en forceert SafeSearch

#### YouTube
- URL parameter: `&restrict=1` wordt automatisch toegevoegd
- Cookie wordt gezet: `VISITOR_INFO1_LIVE=...&f1=50000000` (Restricted Mode)

---

### 3. HTTPS Blijft Intact

- ✅ Geen SSL-breaking
- ✅ Geen MITM (Man-In-The-Middle)
- ✅ Alleen DNS-resolutie wordt gestuurd
- ✅ Veilig en compliant
- ✅ App-compatibel

---

## 📋 DNS Server Configuratie

### Voor DNS Servers (BIND, dnsmasq, etc.)

#### BIND (named.conf)
```bind
// Google SafeSearch
zone "google.com" {
    type forward;
    forwarders { 8.8.8.8; };
};

// Force SafeSearch
zone "forcesafesearch.google.com" {
    type master;
    file "/etc/bind/google-safesearch.zone";
};
```

#### dnsmasq (dnsmasq.conf)
```conf
# Google SafeSearch
address=/www.google.com/216.239.38.120
address=/www.google.nl/216.239.38.120
address=/images.google.com/216.239.38.120

# Bing SafeSearch
address=/www.bing.com/204.79.197.220

# YouTube Restricted Mode
address=/youtube.com/216.58.208.46
address=/www.youtube.com/216.58.208.46
```

---

## 🌐 VPN Server Integratie

### Python VPN Server Example

```python
import dns.resolver
import requests

# SafeSearch DNS mappings
SAFESEARCH_DNS = {
    'www.google.com': 'forcesafesearch.google.com',
    'www.google.nl': 'forcesafesearch.google.com',
    'images.google.com': 'forcesafesearch.google.com',
    'www.bing.com': 'strict.bing.com',
    'youtube.com': 'restrict.youtube.com',
    'www.youtube.com': 'restrict.youtube.com',
}

def resolve_with_safesearch(domain):
    """Resolve domain with SafeSearch enforcement"""
    # Check if domain needs SafeSearch
    if domain in SAFESEARCH_DNS:
        safesearch_domain = SAFESEARCH_DNS[domain]
        # Resolve SafeSearch domain instead
        answers = dns.resolver.resolve(safesearch_domain, 'A')
        return [str(rdata) for rdata in answers]
    
    # Normal DNS resolution
    answers = dns.resolver.resolve(domain, 'A')
    return [str(rdata) for rdata in answers]
```

---

## 🔍 Browser-Level Implementatie

### JavaScript SafeSearch Enforcement

Het systeem forceert SafeSearch automatisch in de browser:

```javascript
// Force Google SafeSearch
if (window.location.hostname.includes('google.')) {
  const url = new URL(window.location.href);
  url.searchParams.set('safe', 'active');
  url.searchParams.set('safeui', 'on');
  
  // Set cookie
  document.cookie = 'PREF=f2=8000000; path=/; domain=.google.com; max-age=31536000';
  
  // Redirect if SafeSearch not active
  if (!url.searchParams.has('safe') || url.searchParams.get('safe') !== 'active') {
    window.location.href = url.toString();
  }
}

// Force YouTube Restricted Mode
if (window.location.hostname.includes('youtube.')) {
  const url = new URL(window.location.href);
  url.searchParams.set('restrict', '1');
  
  // Set cookie
  document.cookie = 'VISITOR_INFO1_LIVE=...&f1=50000000; path=/; domain=.youtube.com; max-age=31536000';
  
  // Redirect if Restricted Mode not active
  if (!url.searchParams.has('restrict')) {
    window.location.href = url.toString();
  }
}
```

---

## 🚫 Waarom Gebruikers SafeSearch Niet Kunnen Uitzetten

Als een gebruiker dit ziet:

> "You can't change your SafeSearch setting at the moment because someone else controls settings on the network."

Dan betekent dit:

✅ Het systeem werkt **correct**
✅ De gebruiker heeft **géén controle**
✅ Content filtering is **afgedwongen**
✅ Er is **geen bypass mogelijk**

Dit is **exact de bedoeling** - geen bug, maar een feature!

---

## ⚠️ Wat SafeSearch NIET Doet

SafeSearch:

❌ Blokkeert geen websites direct
❌ Vervangt geen whitelist filtering
❌ Is niet 100% betrouwbaar
❌ Is geen porn-filter op zich

Daarom:

> **SafeSearch = extra laag, nooit de basis**

---

## 🔄 Volgorde van Handhaving (Zeer Belangrijk)

1. **VPN afdwingen** (verkeer moet via VPN)
2. **DNS whitelist-only** (alleen toegestane sites)
3. **Firewall lock** (geen verkeer buiten VPN)
4. **Pas daarna: SafeSearch** (extra filtering)

Als SafeSearch faalt:
➡️ Whitelist blijft blokkeren
➡️ Porno blijft onbereikbaar
➡️ Fail-safe blijft actief

---

## 📊 Fase-indeling

### Fase 1 (Nu - Verplicht)

- ✅ Whitelist-only filtering
- ✅ VPN + DNS afdwingen
- ✅ 100% blokkering pornografische sites
- ✅ Browser-level filtering

### Fase 2 (Later - Extra Laag)

- ✅ SafeSearch automatisch AAN
- ✅ Niet uitzetbaar
- ✅ Alleen aanvullende bescherming
- ✅ DNS-level enforcement

---

## ✅ Acceptatiecriterium

SafeSearch is correct geïmplementeerd als:

- ✅ Gebruiker SafeSearch niet kan uitschakelen
- ✅ Browser toont "beheer door netwerk"
- ✅ Zoekresultaten zijn gefilterd
- ✅ Porno-sites blijven volledig onbereikbaar
- ✅ DNS-forcing werkt correct
- ✅ Browser-level enforcement werkt

---

## 🎯 Samenvatting

✔ Basis blijft **keihard blokkeren**
✔ SafeSearch komt **later, gecontroleerd**
✔ De melding is **goed en gewenst**
✔ Dit maakt het systeem **sterker en professioneler**

---

## 📚 Referenties

- [Google SafeSearch API](https://developers.google.com/custom-search/docs/xml_results#safeSearch)
- [Bing SafeSearch](https://www.bing.com/webmasters/help/which-safe-search-options-are-available-ed0c074a)
- [YouTube Restricted Mode](https://support.google.com/youtube/answer/174084)
