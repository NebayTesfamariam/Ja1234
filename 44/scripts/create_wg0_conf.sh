#!/bin/bash
# Create /etc/wireguard/wg0.conf from existing server_private.key
# Run: sudo ./scripts/create_wg0_conf.sh

set -e
WG_DIR="/etc/wireguard"
CONF="$WG_DIR/wg0.conf"
PRIVKEY="$WG_DIR/server_private.key"

if [ "$(id -u)" -ne 0 ]; then
  echo "Run with sudo: sudo $0"
  exit 1
fi

if [ ! -f "$PRIVKEY" ]; then
  echo "Error: $PRIVKEY not found. Create keys first (see RUN_AND_TEST_NOW.md step 1)."
  exit 1
fi

# Detect default network interface (e.g. eth0, enp0s3)
IFACE=$(ip route show default 2>/dev/null | awk '/default/ {print $5}')
if [ -z "$IFACE" ]; then
  IFACE=$(ip route 2>/dev/null | grep default | head -1 | sed 's/.*dev \([^ ]*\).*/\1/')
fi
if [ -z "$IFACE" ]; then
  IFACE="eth0"
  echo "Could not detect interface, using eth0. Edit $CONF if wrong."
fi

PRIV=$(cat "$PRIVKEY")
mkdir -p "$WG_DIR"
cat > "$CONF" << EOF
[Interface]
Address = 10.10.0.1/24
ListenPort = 51820
PrivateKey = $PRIV

PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o $IFACE -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o $IFACE -j MASQUERADE
EOF

chmod 600 "$CONF"
echo "Created $CONF (interface: $IFACE)"
echo "Start with: sudo wg-quick up wg0"
echo "Stop with:  sudo wg-quick down wg0"
