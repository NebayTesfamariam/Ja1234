#!/bin/bash
#
# Block QUIC/UDP 443 for VPN clients
# This prevents video/thumbnail leaks via QUIC protocol
# Run this on your VPN server (as root)
#

set -e

# Configuration
VPN_SUBNET="10.10.0.0/24"  # Adjust if your VPN subnet is different

echo "🚫 Blocking QUIC/UDP 443 for VPN clients"
echo "========================================="
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

echo "✅ Adding QUIC blocking rules..."
echo ""

# Block QUIC (UDP 443) - destination port
# This blocks incoming QUIC connections
iptables -I FORWARD -s $VPN_SUBNET -p udp --dport 443 -j DROP

# Block QUIC (UDP 443) - source port  
# This blocks outgoing QUIC connections
iptables -I FORWARD -s $VPN_SUBNET -p udp --sport 443 -j DROP

echo "   ✅ QUIC/UDP 443 blocked (destination port)"
echo "   ✅ QUIC/UDP 443 blocked (source port)"
echo ""

# Verify rules are added
echo "📋 Verifying rules..."
echo ""
iptables -S FORWARD | grep -E "udp.*443" | grep "$VPN_SUBNET" || echo "   ⚠️  No matching rules found"
echo ""

echo "========================================="
echo "✅ QUIC blocking complete!"
echo ""
echo "🧪 Test:"
echo "   1. On VPN client, try to load a video/thumbnail site"
echo "   2. Videos/thumbnails should NOT load via QUIC"
echo "   3. Check with: sudo iptables -S FORWARD | grep 443"
echo ""
