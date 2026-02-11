#!/bin/bash
# Test 2: Single Domain - Only whitelisted domain should work

echo "🔒 Test 2: Single Domain Whitelist"
echo "===================================="
echo ""
echo "This test verifies that only the whitelisted domain works."
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
VPN_DNS="10.10.0.1"
WHITELISTED_DOMAIN="wikipedia.org"
BLOCKED_DOMAINS=("google.com" "youtube.com" "example.com" "bing.com")

echo "📋 Test Configuration:"
echo "  VPN DNS: $VPN_DNS"
echo "  Whitelisted Domain: $WHITELISTED_DOMAIN"
echo "  Blocked Domains: ${BLOCKED_DOMAINS[*]}"
echo ""
echo "⚠️  IMPORTANT: Make sure '$WHITELISTED_DOMAIN' is in the whitelist!"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${YELLOW}⚠️  Warning: Not running as root. DNS queries may fail.${NC}"
    echo "   Run with sudo for accurate results."
    echo ""
fi

ALL_PASSED=true

# Test whitelisted domain (should work)
echo "✅ Testing Whitelisted Domain:"
echo -n "  Testing $WHITELISTED_DOMAIN... "

result=$(nslookup "$WHITELISTED_DOMAIN" "$VPN_DNS" 2>&1)

if echo "$result" | grep -q "NXDOMAIN\|can't find\|Non-existent domain"; then
    echo -e "${RED}❌ FAIL${NC} (NXDOMAIN - should work!)"
    ALL_PASSED=false
else
    echo -e "${GREEN}✅ PASS${NC} (Domain resolved - correctly allowed)"
fi

echo ""

# Test blocked domains (should be blocked)
echo "❌ Testing Blocked Domains (should all return NXDOMAIN):"
echo ""

for domain in "${BLOCKED_DOMAINS[@]}"; do
    echo -n "  Testing $domain... "
    
    result=$(nslookup "$domain" "$VPN_DNS" 2>&1)
    
    if echo "$result" | grep -q "NXDOMAIN\|can't find\|Non-existent domain"; then
        echo -e "${GREEN}✅ PASS${NC} (NXDOMAIN - correctly blocked)"
    else
        echo -e "${RED}❌ FAIL${NC} (Domain resolved - should be blocked!)"
        ALL_PASSED=false
    fi
done

echo ""
echo "===================================="
echo ""

if [ "$ALL_PASSED" = true ]; then
    echo -e "${GREEN}✅ TEST PASSED${NC}"
    echo "   Only whitelisted domain works. All others correctly blocked."
    exit 0
else
    echo -e "${RED}❌ TEST FAILED${NC}"
    echo "   System is NOT working correctly!"
    exit 1
fi
