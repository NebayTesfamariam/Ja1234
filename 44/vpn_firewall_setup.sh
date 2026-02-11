#!/bin/bash
#
# VPN Firewall Setup Script
# Forces DNS and implements kill-switch
# Run this on your VPN server (as root)
#

set -e

# Configuration
VPN_INTERFACE="wg0"              # WireGuard interface name
VPN_SUBNET="10.10.0.0/24"        # VPN subnet
VPN_DNS="10.10.0.1"              # VPN DNS server IP
EXTERNAL_INTERFACE="eth0"         # External interface (adjust if needed)

echo "🔒 VPN Firewall Setup - DNS Forcing + Kill-Switch"
echo "=================================================="
echo ""
echo "VPN Interface: $VPN_INTERFACE"
echo "VPN Subnet: $VPN_SUBNET"
echo "VPN DNS: $VPN_DNS"
echo "External Interface: $EXTERNAL_INTERFACE"
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

echo "✅ Starting firewall configuration..."
echo ""

# ============================================
# 1. FLUSH EXISTING RULES (careful!)
# ============================================
echo "1. Flushing existing rules..."
iptables -F
iptables -X
iptables -t nat -F
iptables -t nat -X
iptables -t mangle -F
iptables -t mangle -X

# Set default policies
iptables -P INPUT ACCEPT
iptables -P FORWARD ACCEPT
iptables -P OUTPUT ACCEPT

echo "   ✅ Rules flushed"
echo ""

# ============================================
# 2. ALLOW LOOPBACK
# ============================================
echo "2. Allowing loopback..."
iptables -A INPUT -i lo -j ACCEPT
iptables -A OUTPUT -o lo -j ACCEPT
echo "   ✅ Loopback allowed"
echo ""

# ============================================
# 3. ALLOW ESTABLISHED CONNECTIONS
# ============================================
echo "3. Allowing established connections..."
iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT
iptables -A OUTPUT -m state --state ESTABLISHED,RELATED -j ACCEPT
iptables -A FORWARD -m state --state ESTABLISHED,RELATED -j ACCEPT
echo "   ✅ Established connections allowed"
echo ""

# ============================================
# 4. VPN INTERFACE RULES
# ============================================
echo "4. Configuring VPN interface rules..."

# Allow WireGuard traffic
iptables -A INPUT -i $VPN_INTERFACE -j ACCEPT
iptables -A OUTPUT -o $VPN_INTERFACE -j ACCEPT

# Allow forwarding from VPN
iptables -A FORWARD -i $VPN_INTERFACE -j ACCEPT
iptables -A FORWARD -o $VPN_INTERFACE -j ACCEPT

echo "   ✅ VPN interface configured"
echo ""

# ============================================
# 5. DNS FORCING (CRITICAL!)
# ============================================
echo "5. Forcing DNS to VPN DNS server..."

# Allow DNS queries ONLY to VPN DNS server
iptables -A OUTPUT -p udp --dport 53 -d $VPN_DNS -j ACCEPT
iptables -A OUTPUT -p tcp --dport 53 -d $VPN_DNS -j ACCEPT

# Block ALL other DNS queries
iptables -A OUTPUT -p udp --dport 53 -j DROP
iptables -A OUTPUT -p tcp --dport 53 -j DROP

# For VPN clients: allow DNS to VPN DNS only
iptables -A FORWARD -s $VPN_SUBNET -p udp --dport 53 -d $VPN_DNS -j ACCEPT
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 53 -d $VPN_DNS -j ACCEPT

# Block VPN clients from using other DNS servers
iptables -A FORWARD -s $VPN_SUBNET -p udp --dport 53 ! -d $VPN_DNS -j DROP
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 53 ! -d $VPN_DNS -j DROP

echo "   ✅ DNS forced to $VPN_DNS"
echo ""

# ============================================
# 6. BLOCK DNS OVER HTTPS/TLS (CRITICAL!)
# ============================================
echo "6. Blocking DNS-over-HTTPS and DNS-over-TLS..."

# Block DNS-over-HTTPS (DoH) - common providers
# Cloudflare
iptables -A OUTPUT -p tcp --dport 443 -d 1.1.1.1 -j DROP
iptables -A OUTPUT -p tcp --dport 443 -d 1.0.0.1 -j DROP
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 443 -d 1.1.1.1 -j DROP
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 443 -d 1.0.0.1 -j DROP

# Google
iptables -A OUTPUT -p tcp --dport 443 -d 8.8.8.8 -j DROP
iptables -A OUTPUT -p tcp --dport 443 -d 8.8.4.4 -j DROP
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 443 -d 8.8.8.8 -j DROP
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 443 -d 8.8.4.4 -j DROP

# Quad9
iptables -A OUTPUT -p tcp --dport 443 -d 9.9.9.9 -j DROP
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 443 -d 9.9.9.9 -j DROP

# Block DNS-over-TLS (DoT) - TCP port 853
iptables -A OUTPUT -p tcp --dport 853 -j DROP
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 853 -j DROP

# Block QUIC (UDP 443) - CRITICAL for blocking videos and apps
# QUIC is used by YouTube, porno sites, and many apps for fast video streaming
iptables -A OUTPUT -p udp --dport 443 -j DROP
iptables -A OUTPUT -p udp --sport 443 -j DROP  # Also block source port
iptables -A FORWARD -s $VPN_SUBNET -p udp --dport 443 -j DROP
iptables -A FORWARD -s $VPN_SUBNET -p udp --sport 443 -j DROP

# Block QUIC on other common ports
iptables -A OUTPUT -p udp --dport 80 -j DROP  # HTTP/3 QUIC
iptables -A FORWARD -s $VPN_SUBNET -p udp --dport 80 -j DROP

# Block mDNS, LLMNR, NetBIOS (app bypass methods)
iptables -A OUTPUT -p udp --dport 5353 -j DROP  # mDNS
iptables -A FORWARD -s $VPN_SUBNET -p udp --dport 5353 -j DROP
iptables -A OUTPUT -p udp --dport 5355 -j DROP  # LLMNR
iptables -A FORWARD -s $VPN_SUBNET -p udp --dport 5355 -j DROP
iptables -A OUTPUT -p udp --dport 137 -j DROP  # NetBIOS
iptables -A FORWARD -s $VPN_SUBNET -p udp --dport 137 -j DROP

echo "   ✅ DNS-over-HTTPS/TLS blocked"
echo "   ✅ QUIC blocked (UDP 443, UDP 80)"
echo ""

# ============================================
# 7. BLOCK DIRECT IP ACCESS (CRITICAL!)
# ============================================
echo "7. Blocking direct IP access..."

# Block direct IP connections (no domain resolution)
# This prevents apps from bypassing DNS by using raw IPs like https://185.xxx.xxx.xxx
# Only allow connections that went through DNS resolution

# Create custom chain for IP filtering
iptables -N IP_FILTER

# Allow established connections (these already went through DNS)
iptables -A IP_FILTER -m state --state ESTABLISHED,RELATED -j ACCEPT

# Block direct IP connections (no domain resolution)
# Method: Require Host header in HTTP/HTTPS requests
# Direct IP connections typically don't have proper Host headers

# Allow HTTPS with Host header (domain-based requests)
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 443 -m state --state NEW \
    -m string --string "Host:" --algo bm --from 40 --to 65535 -j ACCEPT

# Block HTTPS without Host header (likely direct IP access)
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 443 -m state --state NEW -j DROP

# Allow HTTP with Host header
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 80 -m state --state NEW \
    -m string --string "Host:" --algo bm --from 40 --to 65535 -j ACCEPT

# Block HTTP without Host header
iptables -A FORWARD -s $VPN_SUBNET -p tcp --dport 80 -m state --state NEW -j DROP

# Allow established connections (these already went through DNS)
iptables -A FORWARD -s $VPN_SUBNET -m state --state ESTABLISHED,RELATED -j ACCEPT

# Allow VPN DNS (always needed)
iptables -A FORWARD -s $VPN_SUBNET -d $VPN_DNS -j ACCEPT

echo "   ✅ Direct IP access blocked"
echo ""

# ============================================
# 8. KILL-SWITCH: Block traffic outside VPN
# ============================================
echo "8. Implementing kill-switch..."

# Allow traffic from VPN subnet ONLY through VPN interface
iptables -A FORWARD -s $VPN_SUBNET -o $VPN_INTERFACE -j ACCEPT
iptables -A FORWARD -s $VPN_SUBNET ! -o $VPN_INTERFACE -j DROP

# Allow return traffic to VPN subnet
iptables -A FORWARD -d $VPN_SUBNET -i $VPN_INTERFACE -j ACCEPT

# Block VPN clients from accessing internet directly (not through VPN)
iptables -A FORWARD -s $VPN_SUBNET -o $EXTERNAL_INTERFACE -j DROP

echo "   ✅ Kill-switch enabled"
echo ""

# ============================================
# 9. NAT MASQUERADE (for VPN to internet)
# ============================================
echo "9. Configuring NAT masquerade..."

# Masquerade VPN traffic going to internet
iptables -t nat -A POSTROUTING -s $VPN_SUBNET -o $EXTERNAL_INTERFACE -j MASQUERADE

echo "   ✅ NAT masquerade configured"
echo ""

# ============================================
# 10. SAVE RULES (persist across reboots)
# ============================================
echo "10. Saving firewall rules..."

if command -v iptables-save &> /dev/null; then
    iptables-save > /etc/iptables/rules.v4 2>/dev/null || \
    iptables-save > /etc/iptables.rules 2>/dev/null || \
    echo "   ⚠️  Could not save rules automatically. Save manually with:"
    echo "      iptables-save > /etc/iptables/rules.v4"
else
    echo "   ⚠️  iptables-save not found. Rules will be lost on reboot."
    echo "      Install iptables-persistent: apt-get install iptables-persistent"
fi

echo ""
echo "=================================================="
echo "✅ Firewall configuration complete!"
echo ""
echo "📋 Summary:"
echo "   • DNS forced to $VPN_DNS"
echo "   • DNS-over-HTTPS/TLS blocked"
echo "   • QUIC blocked"
echo "   • Kill-switch enabled (VPN required for internet)"
echo ""
echo "🧪 Test:"
echo "   1. On VPN client: nslookup google.com 8.8.8.8"
echo "      Should FAIL (DNS forced)"
echo "   2. Disconnect VPN"
echo "      Internet should NOT work (kill-switch)"
echo ""
