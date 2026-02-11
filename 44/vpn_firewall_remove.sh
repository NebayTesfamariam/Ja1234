#!/bin/bash
#
# Remove VPN Firewall Rules
# Use this to remove all firewall rules if needed
#

set -e

echo "⚠️  Removing VPN firewall rules..."
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ ERROR: This script must be run as root"
    echo "Run with: sudo $0"
    exit 1
fi

# Flush all rules
iptables -F
iptables -X
iptables -t nat -F
iptables -t nat -X
iptables -t mangle -F
iptables -t mangle -X

# Set default policies to ACCEPT
iptables -P INPUT ACCEPT
iptables -P FORWARD ACCEPT
iptables -P OUTPUT ACCEPT

echo "✅ All firewall rules removed"
echo "⚠️  WARNING: System is now unprotected!"
echo ""
