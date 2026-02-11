# ✅ Automatische WireGuard Download - Actief Device

## 🎉 Wat is Nieuw

Wanneer je een **actief device selecteert**, wordt de WireGuard config **AUTOMATISCH** gedownload!

### ✅ Wat Gebeurt Er Nu:

1. **Device Selecteren** → Config wordt automatisch gedownload
2. **Eerste Keer Inloggen** → Config van eerste actieve device wordt gedownload
3. **Download Knop** → Handmatig downloaden mogelijk via knop

---

## 🔧 Hoe Het Werkt

### Automatische Download Triggers:

1. **Bij Selecteren van Device**
   - Klik op "Selecteer" bij een actief device
   - Config wordt automatisch gedownload
   - Alleen eerste keer (voorkomt dubbele downloads)

2. **Bij Eerste Keer Inloggen**
   - Eerste actieve device wordt automatisch geselecteerd
   - Config wordt automatisch gedownload
   - Alleen eerste keer per sessie

3. **Handmatige Download**
   - Klik op "📥 Download WG Config" knop
   - Download werkt altijd (ook meerdere keren)

---

## 📱 Wat Je Ziet

### Device Lijst:
```
ac 17
✓ Actief
WG IP: 10.10.0.36
✓ Actief - Device blijft actief
[Selecteer] [📥 Download WG Config]
```

### Na Selecteren:
- Config wordt automatisch gedownload
- Bestandsnaam: `wireguard-ac.conf`
- Toast melding: "✅ WireGuard config gedownload"

---

## ✅ Voordelen

- ✅ **Geen handmatige stappen** - Alles automatisch
- ✅ **Direct klaar** - Config wordt direct gedownload
- ✅ **Voorkomt dubbele downloads** - SessionStorage tracking
- ✅ **Handmatige optie** - Download knop voor herhaalde downloads

---

## 🧪 Test

1. Log in: `http://localhost/44/public/index.html`
2. Selecteer een actief device
3. Config wordt automatisch gedownload
4. Of klik op "📥 Download WG Config" knop

---

**Het systeem downloadt nu automatisch WireGuard config voor actieve devices!** 🚀
