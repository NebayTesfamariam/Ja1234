#!/bin/bash
#
# Force DNS to VPN DNS resolver only
# Blocks all other DNS servers (8.8.8.8, 1.1.1.1, ISP DNS, etc.)
# Run this on your VPN server (as root)
#

set -e

# Configuration
VPN_SUBNET="10.10.0.0/24"  # Adjust if your VPN subnet is different
VPN_DNS="10.10.0.1"         # VPN DNS resolver IP (adjust if different)

echo "🔒 Forcing DNS to VPN resolver only"
echo "===================================="
echo ""
echo "VPN Subnet: $VPN_SUBNET"
echo "VPN DNS: $VPN_DNS"
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

echo "✅ Adding DNS forcing rules..."
echo ""

# Step 1: Allow DNS ONLY to VPN DNS resolver (HIGH PRIORITY - INSERT at top)
echo "1. Allowing DNS to VPN DNS resolver ($VPN_DNS)..."
iptables -I FORWARD -s $VPN_SUBNET -d $VPN_DNS -p udp --dport 53 -j ACCEPT
iptables -I FORWARD -s $VPN_SUBNET -d $VPN_DNS -p tcp --dport 53 -j ACCEPT
echo "   ✅ DNS allowed to $VPN_DNS (UDP and TCP)"
echo ""

# Step 2: Block ALL other DNS queries (HIGH PRIORITY - INSERT at top)
echo "2. Blocking all other DNS queries..."
iptables -I FORWARD -s $VPN_SUBNET -p udp --dport 53 -j DROP
iptables -I FORWARD -s $VPN_SUBNET -p tcp --dport 53 -j DROP
echo "   ✅ All other DNS blocked (8.8.8.8, 1.1.1.1, ISP DNS, etc.)"
echo ""

# Verify rules are added
echo "📋 Verifying rules..."
echo ""
echo "DNS rules (should show ACCEPT for $VPN_DNS and DROP for others):"
iptables -S FORWARD | grep -E "dport.*53" | grep "$VPN_SUBNET" || echo "   ⚠️  No matching rules found"
echo ""

echo "===================================="
echo "✅ DNS forcing complete!"
echo ""
echo "🧪 Tests:"
echo "   1. On VPN client: nslookup google.com 8.8.8.8"
echo "      Should FAIL (external DNS blocked)"
echo "   2. On VPN client: nslookup wikipedia.org $VPN_DNS"
echo "      Should work ONLY if wikipedia.org is in whitelist"
echo "   3. With empty whitelist: nothing should work"
echo ""
echo "📋 Check rules with:"
echo "   sudo iptables -S FORWARD | grep 'dport 53'"
echo ""
