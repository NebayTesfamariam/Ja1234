# 🚀 DNS Whitelist Server - Quick Start

## Installatie (1 minuut)

```bash
# 1. Installeer Python dependencies
pip3 install requests

# 2. Start DNS server (als root, port 53 vereist root)
sudo python3 dns_whitelist_server.py
```

## Configuratie

Edit `dns_whitelist_server.py` regel 18:

```python
API_BASE_URL = "http://localhost/44/api"  # Pas aan naar jouw API URL
```

## Test

### Test 1: Lege Whitelist

```bash
# Op VPN client
nslookup google.com 10.10.0.1
# Moet zijn: NXDOMAIN
```

### Test 2: Whitelisted Domein

1. Voeg `wikipedia.org` toe aan whitelist
2. Test:

```bash
nslookup wikipedia.org 10.10.0.1
# Moet zijn: IP adres
```

## Firewall (Optioneel)

```bash
# Allow DNS from VPN subnet
sudo ufw allow from 10.10.0.0/24 to any port 53
```

## Logs

De server logt elke query:
```
[14:30:15] DNS query: google.com from 10.10.0.12
  → Domain google.com NOT in whitelist - returning NXDOMAIN
```

## Problemen?

- **Permission denied**: Run met `sudo`
- **Alle queries NXDOMAIN**: Check API URL en device_id mapping
- **Whitelisted domeinen werken niet**: Check domain normalisatie

Zie `DNS_WHITELIST_SETUP.md` voor volledige documentatie.
