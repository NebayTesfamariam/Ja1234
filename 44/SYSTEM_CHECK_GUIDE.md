# 🔍 System Health Check Guide

## 🎯 Doel
Controleer of alle componenten van het whitelist-only filtering systeem correct werken.

---

## ✅ Hoe te gebruiken

### Optie 1: Via Web Interface

1. Open in browser:
   ```
   http://localhost/44/system_check.html
   ```

2. Klik op **"🔄 Run System Check"**

3. Bekijk de resultaten:
   - ✅ **Groen** = OK
   - ⚠️ **Geel** = Waarschuwing
   - ❌ **Rood** = Fout

### Optie 2: Via API Direct

```bash
curl http://localhost/44/api/system_check.php
```

---

## 📋 Wat wordt gecontroleerd

### 1. PHP Configuration
- ✅ PHP versie (min 7.4.0)
- ✅ PHP extensies

### 2. Database
- ✅ Database verbinding
- ✅ Vereiste tabellen: `users`, `devices`, `whitelist`
- ✅ Geen blocklist tabellen (whitelist-only systeem)

### 3. API Endpoints
- ✅ `get_whitelist.php` - Whitelist-only API
- ✅ `get_device_by_ip.php` - Device lookup
- ✅ `add_whitelist.php` - Whitelist toevoegen
- ✅ `get_devices.php` - Devices ophalen
- ✅ `login.php` - Authenticatie
- ✅ `auto_register_device.php` - Auto device registratie

### 4. get_whitelist.php Format
- ✅ Returns array format (niet object)
- ✅ Geen blocklist referenties
- ✅ Fail-safe: returns `[]` bij errors

### 5. DNS Server
- ✅ `dns_whitelist_server.py` bestaat
- ✅ Heeft whitelist logica
- ✅ Returns NXDOMAIN voor niet-whitelisted domains

### 6. Firewall Scripts
- ✅ `block_quic_udp443.sh` - QUIC blocking
- ✅ `block_dot_tcp853.sh` - DoT blocking
- ✅ `force_dns_only.sh` - DNS forcing
- ✅ Scripts zijn uitvoerbaar

### 7. Frontend Files
- ✅ `app.js` - Main frontend
- ✅ `public/index.html` - User interface
- ✅ `admin/admin.js` - Admin interface
- ✅ `autoAddDevice` heeft API call

### 8. Frontend Blocklist References
- ✅ Geen blocklist API calls
- ✅ Geen blocklist referenties

### 9. Documentatie
- ✅ Setup guides aanwezig
- ✅ Troubleshooting guides

---

## ✅ Correct Resultaat

### Alles OK:
```
Status: OK
- Alle checks groen ✅
- Geen errors ❌
- Geen warnings ⚠️
```

### Met Warnings:
```
Status: WARNING
- Meeste checks OK ✅
- Enkele warnings ⚠️ (niet kritisch)
- Geen errors ❌
```

### Met Errors:
```
Status: ERROR
- Een of meer checks falen ❌
- Systeem werkt mogelijk niet correct
- Fix errors voordat je verder gaat
```

---

## 🔧 Troubleshooting

### Database Connection Error

**Als je database error ziet:**
- Check of MySQL/MariaDB draait
- Check `config.php` database credentials
- Check of database bestaat

**Normaal in CLI context:**
- Database errors in CLI zijn normaal
- Check via web interface voor echte status

### Missing API Endpoints

**Als API endpoints ontbreken:**
- Check of bestanden in `/api/` directory staan
- Check file permissions
- Check of bestanden niet per ongeluk verwijderd zijn

### Frontend Blocklist References

**Als blocklist referenties gevonden worden:**
- Check `app.js` en `admin/admin.js`
- Verwijder alle `admin_blocklist` API calls
- Verwijder alle blocklist functies

### DNS Server Script Missing

**Als DNS script ontbreekt:**
- Check of `dns_whitelist_server.py` bestaat
- Check of script uitvoerbaar is: `chmod +x dns_whitelist_server.py`

---

## 📝 Checklist

Na system check, controleer:

- [ ] Alle API endpoints bestaan
- [ ] Database tabellen bestaan
- [ ] Geen blocklist referenties
- [ ] DNS server script aanwezig
- [ ] Firewall scripts aanwezig
- [ ] Frontend heeft geen blocklist calls
- [ ] `get_whitelist.php` returns array format

---

## 🔗 Gerelateerd

- **System Check Tool:** `system_check.html`
- **API Endpoint:** `api/system_check.php`
- **Setup Guides:** Zie `*_SETUP.md` bestanden
