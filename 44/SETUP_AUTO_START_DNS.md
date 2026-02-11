# 🚀 DNS Server Automatisch Starten bij Boot

## ⚠️ Belangrijk
DNS server start vereist **root rechten** voor poort 53. Er zijn twee opties:

---

## Optie 1: LaunchDaemon (Aanbevolen - Start als Root)

### Stap 1: Kopieer LaunchDaemon naar system directory
```bash
sudo cp /Applications/XAMPP/xamppfiles/htdocs/44/com.nebay.pornfree.dns.plist /Library/LaunchDaemons/
```

### Stap 2: Stel correcte permissies in
```bash
sudo chown root:wheel /Library/LaunchDaemons/com.nebay.pornfree.dns.plist
sudo chmod 644 /Library/LaunchDaemons/com.nebay.pornfree.dns.plist
```

### Stap 3: Laad de LaunchDaemon
```bash
sudo launchctl load /Library/LaunchDaemons/com.nebay.pornfree.dns.plist
```

### Stap 4: Verifieer
```bash
sudo launchctl list | grep pornfree.dns
ps aux | grep dns_whitelist_server | grep -v grep
```

**✅ Voordeel:** Start automatisch als root, geen sudo wachtwoord nodig

---

## Optie 2: Sudo zonder Wachtwoord (Alternatief)

### Stap 1: Configureer sudo zonder wachtwoord voor DNS server
```bash
sudo visudo
```

### Stap 2: Voeg deze regel toe aan het einde van het bestand:
```
nebay ALL=(ALL) NOPASSWD: /usr/bin/python3 /Applications/XAMPP/xamppfiles/htdocs/44/dns_whitelist_server.py
```

**⚠️ Let op:** Vervang `nebay` met je gebruikersnaam!

### Stap 3: Sla op en sluit af (in visudo: `:wq`)

### Stap 4: Test
```bash
sudo python3 /Applications/XAMPP/xamppfiles/htdocs/44/dns_whitelist_server.py
```

**✅ Voordeel:** Werkt met bestaande LaunchAgent

---

## Optie 3: Handmatig Starten (Eenvoudigste)

Als je de DNS server niet automatisch wilt starten bij boot:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
./ACTIVATE_DNS_SERVER.sh
```

Of:
```bash
sudo python3 dns_whitelist_server.py
```

---

## 📋 Verificatie

Na setup, check of DNS server draait:

```bash
# Check process
ps aux | grep dns_whitelist_server | grep -v grep

# Check poort 53
sudo lsof -i :53

# Test DNS
dig @127.0.0.1 google.com
```

---

## 🛑 Stoppen

### LaunchDaemon stoppen:
```bash
sudo launchctl unload /Library/LaunchDaemons/com.nebay.pornfree.dns.plist
```

### LaunchAgent stoppen:
```bash
launchctl unload ~/Library/LaunchAgents/com.nebay.pornfree.plist
```

### DNS server stoppen:
```bash
sudo pkill -f dns_whitelist_server.py
```

---

## 📝 Notities

- **LaunchDaemon** draait als root (veiliger voor system services)
- **LaunchAgent** draait als gebruiker (beter voor user services)
- DNS server **moet** als root draaien voor poort 53
- LaunchDaemon start automatisch bij boot, zelfs zonder ingelogde gebruiker

---

## ✅ Aanbevolen Setup

1. ✅ **LaunchAgent** voor XAMPP services (MySQL, Apache)
2. ✅ **LaunchDaemon** voor DNS server (vereist root)
3. ✅ Beide starten automatisch bij boot

---

## 🔧 Troubleshooting

### Probleem: DNS server start niet bij boot
**Oplossing:** Gebruik LaunchDaemon (Optie 1) in plaats van LaunchAgent

### Probleem: "Permission denied" op poort 53
**Oplossing:** DNS server moet als root draaien (gebruik LaunchDaemon)

### Probleem: LaunchDaemon laadt niet
**Check:**
```bash
sudo launchctl list | grep pornfree.dns
sudo tail -f /Applications/XAMPP/xamppfiles/htdocs/44/logs/dns_server.error.log
```

---

**Aanbevolen:** Gebruik **Optie 1 (LaunchDaemon)** voor automatisch starten bij boot.
