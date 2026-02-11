# 🚀 Firewall Quick Start

## Installatie (2 minuten)

```bash
# 1. Edit configuratie
nano vpn_firewall_setup.sh
# Pas aan:
#   VPN_INTERFACE="wg0"           # Jouw WireGuard interface
#   VPN_SUBNET="10.10.0.0/24"     # Jouw VPN subnet  
#   VPN_DNS="10.10.0.1"           # Jouw DNS server
#   EXTERNAL_INTERFACE="eth0"      # Jouw externe interface

# 2. Run script (als root)
sudo ./vpn_firewall_setup.sh
```

## Test

```bash
# Op VPN client
./test_firewall.sh
```

## Verwijderen (als nodig)

```bash
sudo ./vpn_firewall_remove.sh
```

## Belangrijk

- ⚠️ Script verwijdert **alle** bestaande firewall regels
- ⚠️ Test eerst op test server
- ⚠️ Zorg dat je SSH toegang behoudt

Zie `KILL_SWITCH_SETUP.md` voor volledige documentatie.
