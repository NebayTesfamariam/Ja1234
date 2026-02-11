#!/bin/bash
#
# Verify DNS forcing rules
# Run this on your VPN server (as root or with sudo)
#

VPN_SUBNET="10.10.0.0/24"  # Adjust if your VPN subnet is different
VPN_DNS="10.10.0.1"         # VPN DNS resolver IP (adjust if different)

echo "🔍 Verifying DNS forcing rules"
echo "==============================="
echo ""
echo "VPN Subnet: $VPN_SUBNET"
echo "VPN DNS: $VPN_DNS"
echo ""

# Check for DNS rules
echo "📋 Checking DNS rules..."
echo ""

ACCEPT_FOUND=0
DROP_FOUND=0

# Check for ACCEPT rules to VPN DNS
if iptables -S FORWARD | grep -q "ACCEPT.*$VPN_DNS.*dport.*53.*$VPN_SUBNET"; then
    echo "   ✅ Found: DNS allowed to $VPN_DNS (UDP)"
    iptables -S FORWARD | grep "ACCEPT.*$VPN_DNS.*dport.*53.*$VPN_SUBNET" | grep udp
    ACCEPT_FOUND=$((ACCEPT_FOUND + 1))
else
    echo "   ❌ NOT FOUND: DNS allowed to $VPN_DNS (UDP)"
fi

if iptables -S FORWARD | grep -q "ACCEPT.*$VPN_DNS.*dport.*53.*$VPN_SUBNET.*tcp"; then
    echo "   ✅ Found: DNS allowed to $VPN_DNS (TCP)"
    iptables -S FORWARD | grep "ACCEPT.*$VPN_DNS.*dport.*53.*$VPN_SUBNET" | grep tcp
    ACCEPT_FOUND=$((ACCEPT_FOUND + 1))
else
    echo "   ❌ NOT FOUND: DNS allowed to $VPN_DNS (TCP)"
fi

echo ""

# Check for DROP rules for other DNS
if iptables -S FORWARD | grep -q "DROP.*$VPN_SUBNET.*dport.*53.*udp"; then
    echo "   ✅ Found: Other DNS blocked (UDP)"
    iptables -S FORWARD | grep "DROP.*$VPN_SUBNET.*dport.*53" | grep udp | head -1
    DROP_FOUND=$((DROP_FOUND + 1))
else
    echo "   ❌ NOT FOUND: Other DNS blocked (UDP)"
fi

if iptables -S FORWARD | grep -q "DROP.*$VPN_SUBNET.*dport.*53.*tcp"; then
    echo "   ✅ Found: Other DNS blocked (TCP)"
    iptables -S FORWARD | grep "DROP.*$VPN_SUBNET.*dport.*53" | grep tcp | head -1
    DROP_FOUND=$((DROP_FOUND + 1))
else
    echo "   ❌ NOT FOUND: Other DNS blocked (TCP)"
fi

echo ""
echo "==============================="

if [ $ACCEPT_FOUND -eq 2 ] && [ $DROP_FOUND -eq 2 ]; then
    echo "✅ All DNS forcing rules are active!"
    echo ""
    echo "📋 Full DNS rule list:"
    iptables -S FORWARD | grep -E "dport.*53" | grep "$VPN_SUBNET"
else
    echo "⚠️  Some rules are missing!"
    echo ""
    echo "Expected:"
    echo "  - 2x ACCEPT rules for $VPN_DNS (UDP + TCP)"
    echo "  - 2x DROP rules for other DNS (UDP + TCP)"
    echo ""
    echo "Found:"
    echo "  - $ACCEPT_FOUND ACCEPT rules"
    echo "  - $DROP_FOUND DROP rules"
    echo ""
    echo "Run: sudo ./force_dns_only.sh"
fi

echo ""
