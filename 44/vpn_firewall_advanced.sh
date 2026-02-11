#!/bin/bash
#
# Advanced VPN Firewall Setup
# Enhanced blocking for QUIC, direct IP access, and video/image leaks
# This is a more aggressive version that blocks direct IP connections
#

set -e

# Configuration
VPN_INTERFACE="wg0"
VPN_SUBNET="10.10.0.0/24"
VPN_DNS="10.10.0.1"
EXTERNAL_INTERFACE="eth0"

echo "🔒 Advanced VPN Firewall Setup - Enhanced Video/Image Blocking"
echo "==============================================================="
echo ""

if [ "$EUID" -ne 0 ]; then 
    echo "❌ ERROR: This script must be run as root"
    exit 1
fi

# Load base firewall rules first
if [ -f "./vpn_firewall_setup.sh" ]; then
    echo "Loading base firewall rules..."
    source <(grep -v "^#" ./vpn_firewall_setup.sh | grep -v "^$")
else
    echo "⚠️  Warning: vpn_firewall_setup.sh not found. Run that first."
fi

echo ""
echo "Adding advanced blocking rules..."
echo ""

# ============================================
# ENHANCED QUIC BLOCKING
# ============================================
echo "1. Enhanced QUIC blocking..."

# Block QUIC on all common ports
for port in 443 80 8080 8443; do
    iptables -A FORWARD -s $VPN_SUBNET -p udp --dport $port -j DROP
    echo "   ✅ QUIC blocked on UDP $port"
done

# Block Google QUIC servers specifically
for ip in 8.8.8.8 8.8.4.4 1.1.1.1 1.0.0.1; do
    iptables -A FORWARD -s $VPN_SUBNET -d $ip -p udp --dport 443 -j DROP
    iptables -A FORWARD -s $VPN_SUBNET -d $ip -p udp --dport 80 -j DROP
done

echo "   ✅ Enhanced QUIC blocking complete"
echo ""

# ============================================
# BLOCK DIRECT IP CONNECTIONS
# ============================================
echo "2. Blocking direct IP connections..."

# Method 1: Block connections without Host header (direct IP access)
# This requires connection tracking and string matching

# Allow HTTPS only with Host header (domain-based)
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 443 -m state --state NEW \
    -m string --string "Host:" --algo bm --from 40 --to 65535 -j ACCEPT

# Block HTTPS without Host header (likely direct IP)
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 443 -m state --state NEW -j DROP

# Allow HTTP only with Host header
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 80 -m state --state NEW \
    -m string --string "Host:" --algo bm --from 40 --to 65535 -j ACCEPT

# Block HTTP without Host header
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 80 -m state --state NEW -j DROP

echo "   ✅ Direct IP connections blocked"
echo ""

# ============================================
# BLOCK COMMON CDN IP RANGES (Optional)
# ============================================
echo "3. Blocking common CDN IP ranges (optional, aggressive)..."

# Cloudflare CDN
iptables -A FORWARD -s $VPN_SUBNET -d 104.16.0.0/13 -j DROP
iptables -A FORWARD -s $VPN_SUBNET -d 172.64.0.0/13 -j DROP

# Google CDN
iptables -A FORWARD -s $VPN_SUBNET -d 172.217.0.0/16 -j DROP
iptables -A FORWARD -s $VPN_SUBNET -d 142.250.0.0/15 -j DROP

# AWS CDN (be careful - many legitimate sites use this)
# iptables -A FORWARD -s $VPN_SUBNET -d 13.0.0.0/8 -j DROP

echo "   ✅ CDN IP ranges blocked (optional)"
echo ""

# ============================================
# RATE LIMITING (Prevent brute force)
# ============================================
echo "4. Adding rate limiting..."

# Limit DNS queries per IP
iptables -A FORWARD -s $VPN_SUBNET -p udp --dport 53 -d $VPN_DNS \
    -m limit --limit 10/sec --limit-burst 20 -j ACCEPT
iptables -A FORWARD -s $VPN_SUBNET -p udp --dport 53 -d $VPN_DNS -j DROP

echo "   ✅ Rate limiting enabled"
echo ""

# ============================================
# LOGGING (Optional, for debugging)
# ============================================
echo "5. Adding logging rules (optional)..."

# Log blocked DNS queries
iptables -A FORWARD -s $VPN_SUBNET -p udp --dport 53 ! -d $VPN_DNS \
    -j LOG --log-prefix "BLOCKED_DNS: " --log-level 4

# Log blocked QUIC
iptables -A FORWARD -s $VPN_SUBNET -p udp --dport 443 \
    -j LOG --log-prefix "BLOCKED_QUIC: " --log-level 4

# Log blocked direct IP
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 443 -m state --state NEW \
    ! -m string --string "Host:" --algo bm --from 40 --to 65535 \
    -j LOG --log-prefix "BLOCKED_DIRECT_IP: " --log-level 4

echo "   ✅ Logging enabled (check /var/log/kern.log)"
echo ""

echo "==============================================================="
echo "✅ Advanced firewall configuration complete!"
echo ""
echo "📋 Summary:"
echo "   • Enhanced QUIC blocking"
echo "   • Direct IP access blocked"
echo "   • CDN IP ranges blocked (optional)"
echo "   • Rate limiting enabled"
echo "   • Logging enabled"
echo ""
echo "⚠️  WARNING: This is aggressive blocking."
echo "   Test thoroughly before production use."
echo ""
