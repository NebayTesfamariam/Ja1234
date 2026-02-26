#!/usr/bin/env bash
# Sync WireGuard peers from the PHP app to the local WireGuard server.
# Run on the VPN server (as root): sudo VPN_SYNC_KEY=xxx API_BASE_URL=https://ja1234.com/api ./sync_wireguard_peers.sh
# Requires: curl, jq, wg

set -e
WG_INTERFACE="${WG_INTERFACE:-wg0}"
API_BASE_URL="${API_BASE_URL:-}"
VPN_SYNC_KEY="${VPN_SYNC_KEY:-${DNS_INTERNAL_KEY}}"

if [ -z "$API_BASE_URL" ] || [ -z "$VPN_SYNC_KEY" ]; then
  echo "Usage: API_BASE_URL=https://your-site.com/api VPN_SYNC_KEY=your-secret [WG_INTERFACE=wg0] $0"
  echo "Or set DNS_INTERNAL_KEY instead of VPN_SYNC_KEY."
  exit 1
fi

# Trim trailing slash
API_BASE_URL="${API_BASE_URL%/}"
URL="${API_BASE_URL}/get_active_peers.php"

if ! command -v curl &>/dev/null; then
  echo "curl is required."
  exit 1
fi
if ! command -v jq &>/dev/null; then
  echo "jq is required (install: apt install jq)."
  exit 1
fi
if [ "$(id -u)" -ne 0 ]; then
  echo "Run as root (sudo) so 'wg set' works."
  exit 1
fi

RESP=$(curl -s -S -H "X-VPN-Sync-Key: $VPN_SYNC_KEY" "$URL") || exit 1
if ! echo "$RESP" | jq -e '.peers' &>/dev/null; then
  echo "API error or invalid response: $RESP"
  exit 1
fi

COUNT=0
while IFS= read -r line; do
  pubkey=$(echo "$line" | cut -f1)
  allowed_ips=$(echo "$line" | cut -f2)
  [ -z "$pubkey" ] && continue
  wg set "$WG_INTERFACE" peer "$pubkey" allowed-ips "$allowed_ips" 2>/dev/null || true
  ((COUNT++)) || true
done < <(echo "$RESP" | jq -r '.peers[] | "\(.public_key)\t\(.allowed_ips)"')

echo "Synced $COUNT peer(s) to $WG_INTERFACE."
