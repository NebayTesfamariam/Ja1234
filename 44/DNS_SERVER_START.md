# 🚀 DNS Server Starten - Stap voor Stap

## ⚠️ Belangrijk: Python Requests Library

Voordat je de DNS server start, moet je eerst de Python `requests` library installeren:

```bash
pip3 install requests
```

---

## 📋 Stap voor Stap

### Stap 1: Installeer Python Requests
```bash
pip3 install requests
```

### Stap 2: Start DNS Server
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
sudo python3 dns_whitelist_server.py
```

**Let op:**
- Vereist `sudo` (root toegang) voor poort 53
- Laat terminal open - DNS server moet blijven draaien
- Gebruik `screen` of `tmux` als je terminal wilt sluiten

---

## ✅ Verificatie

### Check of DNS Server Draait
```bash
# Check proces
ps aux | grep dns_whitelist_server | grep -v grep

# Check poort (vereist sudo)
sudo netstat -tuln | grep :53
```

### Of gebruik het check script:
```bash
./CHECK_DNS_SERVER.sh
```

---

## 🔧 Alternatief: Start Script Gebruiken

```bash
sudo ./start_dns_server.sh
```

Dit script:
- ✅ Checkt of Python geïnstalleerd is
- ✅ Installeert automatisch `requests` library
- ✅ Checkt of poort 53 beschikbaar is
- ✅ Start DNS server

---

## 📱 Screen/Tmux Gebruiken (Aanbevolen)

Als je terminal wilt sluiten maar DNS server moet blijven draaien:

### Met Screen:
```bash
# Start screen sessie
screen -S dns

# Start DNS server
sudo python3 dns_whitelist_server.py

# Detach: Ctrl+A, dan D
# Reattach: screen -r dns
```

### Met Tmux:
```bash
# Start tmux sessie
tmux new -s dns

# Start DNS server
sudo python3 dns_whitelist_server.py

# Detach: Ctrl+B, dan D
# Reattach: tmux attach -t dns
```

---

## 🆘 Problemen?

### "ModuleNotFoundError: No module named 'requests'"
```bash
pip3 install requests
```

### "Permission denied" op poort 53
```bash
# Gebruik sudo
sudo python3 dns_whitelist_server.py
```

### Poort 53 al in gebruik
```bash
# Check wat poort 53 gebruikt
sudo lsof -i :53

# Stop andere DNS server (bijv. systemd-resolved op Linux)
sudo systemctl stop systemd-resolved
```

---

## ✅ DNS Server is Actief Wanneer:

- ✅ Proces draait: `ps aux | grep dns_whitelist_server`
- ✅ Poort 53 luistert: `sudo netstat -tuln | grep :53`
- ✅ Geen errors in terminal output

---

**Start nu de DNS server en laat deze draaien!** 🚀
