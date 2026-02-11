# 🖥️ Server Informatie

## Server Details

**Servernaam:** `server1808`

---

## 📋 DNS Configuratie

Voor je DNS records bij je domain registrar, gebruik deze informatie:

### A Record (Verplicht)
```
Type: A
Name: @ (of ja1234.com)
Value: [JOUW SERVER IP]
TTL: 3600
```

### CNAME Record (Optioneel)
```
Type: CNAME
Name: www
Value: ja1234.com
TTL: 3600
```

---

## 🔍 Server IP Adres Vinden

### Methode 1: Via Terminal
```bash
curl ifconfig.me
# Of:
curl ipinfo.io/ip
```

### Methode 2: Via Hosting Provider
- Log in bij je hosting provider dashboard
- Ga naar **Server Management** of **Server Details**
- Zoek naar **IP Address** of **Public IP**

### Methode 3: Via Server
```bash
hostname -I
# Of:
ip addr show
```

---

## 📝 DNS Records Voorbeeld

Als je server IP bijvoorbeeld `185.123.45.67` is:

**A Record:**
```
@ → 185.123.45.67
```

**CNAME Record:**
```
www → ja1234.com
```

---

## 🌐 Na DNS Setup

Zodra DNS correct is ingesteld:

1. ✅ Website bereikbaar: `https://ja1234.com`
2. ✅ Admin panel: `https://ja1234.com/admin/index.html`
3. ✅ API endpoints: `https://ja1234.com/api/login.php`
4. ✅ SSL certificate werkt (als geconfigureerd)

---

## 🔧 Server Configuratie

### Hostname Instellen (optioneel)
Als je de hostname wilt instellen:
```bash
sudo hostnamectl set-hostname server1808
# Of op macOS:
sudo scutil --set HostName server1808
sudo scutil --set ComputerName server1808
sudo scutil --set LocalHostName server1808
```

### Check Huidige Hostname
```bash
hostname
# Of:
hostnamectl
```

---

## 📞 Hulp

Als je je server IP niet weet:
1. Check je hosting provider dashboard
2. Check je hosting provider email/documentatie
3. Contact je hosting provider support

---

## ✅ Checklist

- [ ] Server IP adres gevonden
- [ ] DNS A Record ingesteld (`@` → `[SERVER IP]`)
- [ ] DNS CNAME Record ingesteld (`www` → `ja1234.com`) - optioneel
- [ ] 30 minuten gewacht voor DNS propagation
- [ ] Website getest: `https://ja1234.com`
- [ ] SSL certificate werkt

---

**Servernaam:** `server1808`  
**Domein:** `ja1234.com`  
**Server IP:** [Vul hier je server IP in]
