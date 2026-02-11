#!/bin/bash
#
# Verify QUIC/UDP 443 blocking rules
# Run this on your VPN server (as root or with sudo)
#

VPN_SUBNET="10.10.0.0/24"  # Adjust if your VPN subnet is different

echo "🔍 Verifying QUIC/UDP 443 blocking rules"
echo "========================================="
echo ""
echo "VPN Subnet: $VPN_SUBNET"
echo ""

# Check for UDP 443 rules
echo "📋 Checking for UDP 443 blocking rules..."
echo ""

RULES_FOUND=0

# Check for destination port 443
if iptables -S FORWARD | grep -q "udp.*dport.*443.*$VPN_SUBNET"; then
    echo "   ✅ Found: UDP destination port 443 blocking"
    iptables -S FORWARD | grep "udp.*dport.*443.*$VPN_SUBNET"
    RULES_FOUND=$((RULES_FOUND + 1))
else
    echo "   ❌ NOT FOUND: UDP destination port 443 blocking"
fi

echo ""

# Check for source port 443
if iptables -S FORWARD | grep -q "udp.*sport.*443.*$VPN_SUBNET"; then
    echo "   ✅ Found: UDP source port 443 blocking"
    iptables -S FORWARD | grep "udp.*sport.*443.*$VPN_SUBNET"
    RULES_FOUND=$((RULES_FOUND + 1))
else
    echo "   ❌ NOT FOUND: UDP source port 443 blocking"
fi

echo ""
echo "========================================="

if [ $RULES_FOUND -eq 2 ]; then
    echo "✅ All QUIC blocking rules are active!"
    echo ""
    echo "📋 Full rule list:"
    iptables -S FORWARD | grep -E "udp.*443" | grep "$VPN_SUBNET"
else
    echo "⚠️  Some rules are missing!"
    echo ""
    echo "Run: sudo ./block_quic_udp443.sh"
fi

echo ""
