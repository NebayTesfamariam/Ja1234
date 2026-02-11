#!/bin/bash
#
# Verify DNS-over-TLS (DoT, TCP 853) blocking rules
# Run this on your VPN server (as root or with sudo)
#

VPN_SUBNET="10.10.0.0/24"  # Adjust if your VPN subnet is different

echo "🔍 Verifying DNS-over-TLS (DoT, TCP 853) blocking rules"
echo "======================================================="
echo ""
echo "VPN Subnet: $VPN_SUBNET"
echo ""

# Check for TCP 853 rules
echo "📋 Checking for TCP 853 blocking rules..."
echo ""

RULES_FOUND=0

# Check for destination port 853
if iptables -S FORWARD | grep -q "tcp.*dport.*853.*$VPN_SUBNET"; then
    echo "   ✅ Found: TCP destination port 853 blocking"
    iptables -S FORWARD | grep "tcp.*dport.*853.*$VPN_SUBNET"
    RULES_FOUND=$((RULES_FOUND + 1))
else
    echo "   ❌ NOT FOUND: TCP destination port 853 blocking"
fi

echo ""

# Check for source port 853
if iptables -S FORWARD | grep -q "tcp.*sport.*853.*$VPN_SUBNET"; then
    echo "   ✅ Found: TCP source port 853 blocking"
    iptables -S FORWARD | grep "tcp.*sport.*853.*$VPN_SUBNET"
    RULES_FOUND=$((RULES_FOUND + 1))
else
    echo "   ❌ NOT FOUND: TCP source port 853 blocking"
fi

echo ""
echo "======================================================="

if [ $RULES_FOUND -eq 2 ]; then
    echo "✅ All DNS-over-TLS blocking rules are active!"
    echo ""
    echo "📋 Full rule list:"
    iptables -S FORWARD | grep -E "tcp.*853" | grep "$VPN_SUBNET"
else
    echo "⚠️  Some rules are missing!"
    echo ""
    echo "Run: sudo ./block_dot_tcp853.sh"
fi

echo ""
