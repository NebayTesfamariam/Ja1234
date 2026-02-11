#!/bin/bash
# Test 4: DNS Bypass - All DNS bypass methods should be blocked

echo "🔒 Test 4: DNS Bypass Prevention"
echo "================================"
echo ""
echo "This test verifies that DNS bypass methods are blocked."
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
VPN_DNS="10.10.0.1"
PUBLIC_DNS=("8.8.8.8" "1.1.1.1" "9.9.9.9")
TEST_DOMAIN="google.com"

echo "📋 Test Configuration:"
echo "  VPN DNS: $VPN_DNS"
echo "  Public DNS Servers: ${PUBLIC_DNS[*]}"
echo "  Test Domain: $TEST_DOMAIN"
echo ""
echo "⚠️  IMPORTANT: This test must be run on VPN SERVER!"
echo "   Tests firewall rules blocking external DNS."
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${YELLOW}⚠️  Warning: Not running as root.${NC}"
    echo "   Run with sudo for accurate results."
    echo ""
fi

ALL_PASSED=true

# Test 1: External DNS should be blocked
echo "🔍 Test 1: External DNS Servers (should be blocked)"
echo ""

for dns_server in "${PUBLIC_DNS[@]}"; do
    echo -n "  Testing DNS query to $dns_server... "
    
    # Check if firewall rule exists
    if iptables -C FORWARD -s 10.10.0.0/24 -d "$dns_server" -p udp --dport 53 -j DROP 2>/dev/null; then
        echo -e "${GREEN}✅ PASS${NC} (Firewall rule exists)"
    else
        echo -e "${RED}❌ FAIL${NC} (No firewall rule - DNS bypass possible!)"
        ALL_PASSED=false
    fi
done

echo ""

# Test 2: DoT (TCP 853) should be blocked
echo "🔍 Test 2: DNS-over-TLS (DoT) - TCP 853 (should be blocked)"
echo ""

if iptables -C FORWARD -s 10.10.0.0/24 -p tcp --dport 853 -j DROP 2>/dev/null; then
    echo -e "  ${GREEN}✅ PASS${NC} (DoT blocking rule exists)"
else
    echo -e "  ${RED}❌ FAIL${NC} (No DoT blocking rule - DNS bypass possible!)"
    ALL_PASSED=false
fi

echo ""

# Test 3: DoH providers should be blocked
echo "🔍 Test 3: DNS-over-HTTPS (DoH) Providers (should be blocked)"
echo ""

DOH_PROVIDERS=("1.1.1.1" "8.8.8.8" "9.9.9.9")

for provider in "${DOH_PROVIDERS[@]}"; do
    echo -n "  Testing DoH blocking for $provider (TCP 443)... "
    
    if iptables -C FORWARD -s 10.10.0.0/24 -d "$provider" -p tcp --dport 443 -j DROP 2>/dev/null; then
        echo -e "${GREEN}✅ PASS${NC} (DoH blocking rule exists)"
    else
        echo -e "${RED}❌ FAIL${NC} (No DoH blocking rule - DNS bypass possible!)"
        ALL_PASSED=false
    fi
done

echo ""
echo "================================"
echo ""

if [ "$ALL_PASSED" = true ]; then
    echo -e "${GREEN}✅ TEST PASSED${NC}"
    echo "   All DNS bypass methods are correctly blocked."
    exit 0
else
    echo -e "${RED}❌ TEST FAILED${NC}"
    echo "   Some DNS bypass methods are NOT blocked!"
    echo "   Run firewall setup scripts to fix."
    exit 1
fi
