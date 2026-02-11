#!/bin/bash
#
# Block DNS-over-TLS (DoT, TCP 853) for VPN clients
# This prevents apps/browsers from using their own DNS (DoT)
# which can bypass DNS filtering and allow media to load
# Run this on your VPN server (as root)
#

set -e

# Configuration
VPN_SUBNET="10.10.0.0/24"  # Adjust if your VPN subnet is different

echo "🚫 Blocking DNS-over-TLS (DoT, TCP 853) for VPN clients"
echo "======================================================="
echo ""
echo "VPN Subnet: $VPN_SUBNET"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ ERROR: This script must be run as root"
    echo "Run with: sudo $0"
    exit 1
fi

# Check if iptables is available
if ! command -v iptables &> /dev/null; then
    echo "❌ ERROR: iptables not found. Install with: apt-get install iptables"
    exit 1
fi

echo "✅ Adding DNS-over-TLS blocking rules..."
echo ""

# Block DNS-over-TLS (TCP 853) - destination port
# This blocks incoming DoT connections
iptables -I FORWARD -s $VPN_SUBNET -p tcp --dport 853 -j DROP

# Block DNS-over-TLS (TCP 853) - source port
# This blocks outgoing DoT connections
iptables -I FORWARD -s $VPN_SUBNET -p tcp --sport 853 -j DROP

echo "   ✅ DNS-over-TLS/TCP 853 blocked (destination port)"
echo "   ✅ DNS-over-TLS/TCP 853 blocked (source port)"
echo ""

# Verify rules are added
echo "📋 Verifying rules..."
echo ""
iptables -S FORWARD | grep -E "tcp.*853" | grep "$VPN_SUBNET" || echo "   ⚠️  No matching rules found"
echo ""

echo "======================================================="
echo "✅ DNS-over-TLS blocking complete!"
echo ""
echo "🧪 Test:"
echo "   1. On VPN client, try: nslookup google.com 8.8.8.8"
echo "      Should FAIL (DNS forced to VPN DNS)"
echo "   2. Apps should NOT be able to use DoT"
echo "   3. Check with: sudo iptables -S FORWARD | grep 853"
echo ""
