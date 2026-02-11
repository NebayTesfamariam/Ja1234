# 🪟 DNS Server Setup - Windows/XAMPP

## ✅ Correcte Oplossing voor Windows

**BELANGRIJK:** Windows heeft **GEEN sudo**! Gebruik **Administrator CMD** en **.bat scripts**.

---

## 🚀 Quick Start (3 Stappen)

### STAP 1: Start DNS Server als Administrator

1. **Rechtsklik** op `start_dns_server.bat`
2. Klik **"Run as Administrator"**
3. Laat het venster **OPEN** (sluit je het → DNS stopt)

### STAP 2: Verifieer DNS Draait

Open **nieuwe CMD** en run:
```bat
netstat -ano | find ":53"
```

Je moet een Python-proces zien luisteren op poort 53.

### STAP 3: Test Blokkering

Whitelist leeg → open Chrome:
- **NIETS mag laden** (alles geblokkeerd)

---

## 🟢 Automatisch Starten (Geen Handwerk Meer)

### Optie 1: Via Complete System Start

1. **Rechtsklik** op `start_pornfree_system.bat`
2. Klik **"Run as Administrator"**
3. DNS start **automatisch** in nieuw venster

### Optie 2: Via Windows Startup

1. Druk `Win + R`
2. Typ: `shell:startup`
3. Druk ENTER
4. Zet **snelkoppeling** naar `start_pornfree_system.bat`
5. **Rechtsklik** snelkoppeling → Eigenschappen → Geavanceerd → ✅ **Run as administrator**

---

## 📋 Bestanden

- ✅ `start_dns_server.bat` - Start DNS server alleen
- ✅ `start_pornfree_system.bat` - Start alles (XAMPP + DNS)
- ✅ `stop_dns_server.bat` - Stop DNS server

---

## 🔧 Troubleshooting

### Probleem: Python Not Found
**Oplossing:**
1. Installeer Python van python.org
2. Bij installatie: ✅ **Add Python to PATH**
3. Herstart computer

### Probleem: Port 53 Already In Use
**Oplossing:**
```bat
netstat -ano | find ":53"
taskkill /F /PID [PID_NUMBER]
```

### Probleem: Permission Denied
**Oplossing:**
- Start altijd als **Administrator**
- Rechtsklik → "Run as Administrator"

### Probleem: Requests Library Missing
**Oplossing:**
```bat
python -m pip install requests
```

---

## ✅ Verificatie

### Check DNS Draait:
```bat
netstat -ano | find ":53"
tasklist | findstr python
```

### Test DNS Server:
```bat
nslookup google.com 127.0.0.1
```

---

## ⚠️ Belangrijk

- ❌ **GEEN sudo** op Windows (dat is Linux/macOS)
- ✅ Gebruik **Administrator CMD**
- ✅ Gebruik **.bat scripts** (niet .sh)
- ✅ Gebruik **python** (niet python3)
- ✅ Laat DNS venster **OPEN**

---

## 🎯 Resultaat

Vanaf NU:
- ✅ XAMPP start automatisch
- ✅ DNS server start automatisch
- ✅ Porn blokkering werkt
- ❌ Geen handmatige acties meer!

---

**DNS Server werkt nu correct op Windows!** 🚀
