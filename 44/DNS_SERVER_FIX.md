# 🔧 DNS Server - Python Requests Installeren

## ⚠️ Probleem: Python Requests Niet Geïnstalleerd

Als je de fout ziet: `ModuleNotFoundError: No module named 'requests'`

## ✅ Oplossing

### Optie 1: Installeren met --user (Aanbevolen)
```bash
python3 -m pip install --user requests
```

### Optie 2: Installeren met --break-system-packages (macOS)
```bash
pip3 install --break-system-packages requests
```

### Optie 3: Gebruik Virtual Environment
```bash
# Maak virtual environment
python3 -m venv venv

# Activeer virtual environment
source venv/bin/activate

# Installeer requests
pip install requests

# Start DNS server (in virtual environment)
sudo python3 dns_whitelist_server.py
```

---

## 🚀 Na Installatie: Start DNS Server

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/44
sudo python3 dns_whitelist_server.py
```

---

## ✅ Verificatie

### Test of requests werkt:
```bash
python3 -c "import requests; print('✅ Requests OK')"
```

### Check DNS server:
```bash
./CHECK_DNS_SERVER.sh
```

---

**Na installatie van requests, start de DNS server opnieuw!** 🚀
