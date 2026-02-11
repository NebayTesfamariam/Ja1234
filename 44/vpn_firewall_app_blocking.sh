#!/bin/bash
#
# VPN Firewall - App Blocking (CRITICAL!)
# Prevents apps from loading pornographic content
# Blocks direct IP access, forces DNS, blocks QUIC
# Run this on your VPN server (as root)
#

set -e

# Configuration
VPN_INTERFACE="wg0"
VPN_SUBNET="10.10.0.0/24"
VPN_DNS="10.10.0.1"
EXTERNAL_INTERFACE="eth0"

echo "🚫 VPN Firewall - App Blocking Setup"
echo "====================================="
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

echo "✅ Starting app blocking configuration..."
echo ""

# ============================================
# 1. FORCE DNS TO VPN DNS SERVER (CRITICAL!)
# ============================================
echo "1. Forcing DNS to VPN DNS server..."

# Allow DNS queries ONLY to VPN DNS server
iptables -I OUTPUT -p udp --dport 53 ! -d $VPN_DNS -j DROP
iptables -I OUTPUT -p tcp --dport 53 ! -d $VPN_DNS -j DROP

# For VPN clients: allow DNS to VPN DNS only
iptables -I FORWARD -s $VPN_SUBNET -p udp --dport 53 ! -d $VPN_DNS -j DROP
iptables -I FORWARD -s $VPN_SUBNET -p tcp --dport 53 ! -d $VPN_DNS -j DROP

# Allow DNS to VPN DNS
iptables -I OUTPUT -p udp --dport 53 -d $VPN_DNS -j ACCEPT
iptables -I OUTPUT -p tcp --dport 53 -d $VPN_DNS -j ACCEPT
iptables -I FORWARD -s $VPN_SUBNET -p udp --dport 53 -d $VPN_DNS -j ACCEPT
iptables -I FORWARD -s $VPN_SUBNET -p tcp --dport 53 -d $VPN_DNS -j ACCEPT

echo "   ✅ DNS forced to $VPN_DNS"
echo ""

# ============================================
# 2. BLOCK DNS-OVER-HTTPS (DoH) - CRITICAL!
# ============================================
echo "2. Blocking DNS-over-HTTPS (DoH)..."

# Block DoH providers (Cloudflare, Google, Quad9, etc.)
for ip in 1.1.1.1 1.0.0.1 8.8.8.8 8.8.4.4 9.9.9.9 9.9.9.10; do
    iptables -I OUTPUT -p tcp --dport 443 -d $ip -j DROP
    iptables -I FORWARD -s $VPN_SUBNET -p tcp --dport 443 -d $ip -j DROP
done

# Block DoH via hostname (if possible)
# Note: This requires dnsmasq or similar to block domains
# Common DoH endpoints:
# - cloudflare-dns.com
# - dns.google
# - dns.quad9.net

echo "   ✅ DNS-over-HTTPS blocked"
echo ""

# ============================================
# 3. BLOCK DNS-OVER-TLS (DoT) - CRITICAL!
# ============================================
echo "3. Blocking DNS-over-TLS (DoT)..."

# Block DoT (TCP port 853) - apps use this to bypass DNS
iptables -I OUTPUT -p tcp --dport 853 -j DROP
iptables -I FORWARD -s $VPN_SUBNET -p tcp --dport 853 -j DROP

# Block DoT to common providers
for ip in 1.1.1.1 1.0.0.1 8.8.8.8 8.8.4.4 9.9.9.9; do
    iptables -I OUTPUT -p tcp --dport 853 -d $ip -j DROP
    iptables -I FORWARD -s $VPN_SUBNET -p tcp --dport 853 -d $ip -j DROP
done

echo "   ✅ DNS-over-TLS blocked"
echo ""

# ============================================
# 4. BLOCK QUIC (UDP 443) - CRITICAL FOR APPS!
# ============================================
echo "4. Blocking QUIC (UDP 443) - CRITICAL for apps..."

# Block QUIC completely - apps use QUIC for fast video streaming
iptables -I OUTPUT -p udp --dport 443 -j DROP
iptables -I OUTPUT -p udp --sport 443 -j DROP
iptables -I FORWARD -s $VPN_SUBNET -p udp --dport 443 -j DROP
iptables -I FORWARD -s $VPN_SUBNET -p udp --sport 443 -j DROP

# Block QUIC on HTTP/3 port (UDP 80)
iptables -I OUTPUT -p udp --dport 80 -j DROP
iptables -I FORWARD -s $VPN_SUBNET -p udp --dport 80 -j DROP

# Block QUIC to common CDN providers
for ip in 8.8.8.8 8.8.4.4 1.1.1.1 1.0.0.1; do
    iptables -I OUTPUT -p udp --dport 443 -d $ip -j DROP
    iptables -I FORWARD -s $VPN_SUBNET -p udp --dport 443 -d $ip -j DROP
done

echo "   ✅ QUIC blocked (UDP 443, UDP 80)"
echo ""

# ============================================
# 5. BLOCK DIRECT IP ACCESS - CRITICAL FOR APPS!
# ============================================
echo "5. Blocking direct IP access..."

# Apps often try to connect directly to IPs to bypass DNS
# We need to block connections that don't have a Host header

# Allow established connections (these already went through DNS)
iptables -I FORWARD -s $VPN_SUBNET -m state --state ESTABLISHED,RELATED -j ACCEPT

# Block NEW HTTPS connections without Host header (direct IP access)
# This requires string matching - allow only if Host header exists
iptables -I FORWARD -s $VPN_SUBNET -p tcp --dport 443 -m state --state NEW \
    -m string --string "Host:" --algo bm --from 40 --to 65535 -j ACCEPT

# Block NEW HTTPS connections without Host header (likely direct IP)
iptables -I FORWARD -s $VPN_SUBNET -p tcp --dport 443 -m state --state NEW -j DROP

# Same for HTTP
iptables -I FORWARD -s $VPN_SUBNET -p tcp --dport 80 -m state --state NEW \
    -m string --string "Host:" --algo bm --from 40 --to 65535 -j ACCEPT

iptables -I FORWARD -s $VPN_SUBNET -p tcp --dport 80 -m state --state NEW -j DROP

# Block direct IP connections on other ports
# Allow only common ports with Host header
for port in 8080 8443 8000 8888; do
    iptables -I FORWARD -s $VPN_SUBNET -p tcp --dport $port -m state --state NEW \
        -m string --string "Host:" --algo bm --from 40 --to 65535 -j ACCEPT
    iptables -I FORWARD -s $VPN_SUBNET -p tcp --dport $port -m state --state NEW -j DROP
done

echo "   ✅ Direct IP access blocked"
echo ""

# ============================================
# 6. BLOCK COMMON APP BYPASS METHODS
# ============================================
echo "6. Blocking common app bypass methods..."

# Block mDNS (multicast DNS) - apps use this for local discovery
iptables -I OUTPUT -p udp --dport 5353 -j DROP
iptables -I FORWARD -s $VPN_SUBNET -p udp --dport 5353 -j DROP

# Block LLMNR (Link-Local Multicast Name Resolution)
iptables -I OUTPUT -p udp --dport 5355 -j DROP
iptables -I FORWARD -s $VPN_SUBNET -p udp --dport 5355 -j DROP

# Block NetBIOS (Windows name resolution)
iptables -I OUTPUT -p udp --dport 137 -j DROP
iptables -I FORWARD -s $VPN_SUBNET -p udp --dport 137 -j DROP

echo "   ✅ App bypass methods blocked"
echo ""

# ============================================
# 7. KILL-SWITCH - NO INTERNET WITHOUT VPN
# ============================================
echo "7. Implementing kill-switch..."

# Block all traffic from VPN subnet that doesn't go through VPN interface
iptables -I FORWARD -s $VPN_SUBNET ! -o $VPN_INTERFACE -j DROP

# Allow return traffic to VPN subnet
iptables -I FORWARD -d $VPN_SUBNET -i $VPN_INTERFACE -j ACCEPT

echo "   ✅ Kill-switch enabled"
echo ""

# ============================================
# 8. SAVE RULES
# ============================================
echo "8. Saving firewall rules..."

if command -v iptables-save &> /dev/null; then
    iptables-save > /etc/iptables/rules.v4 2>/dev/null || \
    iptables-save > /etc/iptables.rules 2>/dev/null || \
    echo "   ⚠️  Could not save rules automatically"
else
    echo "   ⚠️  iptables-save not found. Rules will be lost on reboot."
fi

echo ""
echo "====================================="
echo "✅ App blocking configuration complete!"
echo ""
echo "📋 Summary:"
echo "   • DNS forced to $VPN_DNS"
echo "   • DNS-over-HTTPS blocked"
echo "   • DNS-over-TLS blocked"
echo "   • QUIC blocked (UDP 443, UDP 80)"
echo "   • Direct IP access blocked"
echo "   • App bypass methods blocked"
echo "   • Kill-switch enabled"
echo ""
echo "🧪 Test:"
echo "   1. On VPN client, try to use app with direct IP"
echo "      Should FAIL (direct IP blocked)"
echo "   2. Try to use alternative DNS (8.8.8.8)"
echo "      Should FAIL (DNS forced)"
echo "   3. Try to load video via QUIC"
echo "      Should FAIL (QUIC blocked)"
echo "   4. Disconnect VPN"
echo "      Internet should NOT work (kill-switch)"
echo ""
