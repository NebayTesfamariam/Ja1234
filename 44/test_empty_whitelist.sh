#!/bin/bash
# Test 1: Empty Whitelist - Nothing should work

echo "🔒 Test 1: Empty Whitelist"
echo "============================"
echo ""
echo "This test verifies that with an empty whitelist, NOTHING works."
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
VPN_DNS="10.10.0.1"
TEST_DOMAINS=("google.com" "wikipedia.org" "youtube.com" "example.com")

echo "📋 Test Configuration:"
echo "  VPN DNS: $VPN_DNS"
echo "  Test Domains: ${TEST_DOMAINS[*]}"
echo ""

# Check if running as root (for DNS queries)
if [ "$EUID" -ne 0 ]; then 
    echo -e "${YELLOW}⚠️  Warning: Not running as root. DNS queries may fail.${NC}"
    echo "   Run with sudo for accurate results."
    echo ""
fi

# Test DNS queries
echo "🔍 Testing DNS Queries (should all return NXDOMAIN):"
echo ""

ALL_PASSED=true

for domain in "${TEST_DOMAINS[@]}"; do
    echo -n "  Testing $domain... "
    
    # Query DNS
    result=$(nslookup "$domain" "$VPN_DNS" 2>&1)
    
    if echo "$result" | grep -q "NXDOMAIN\|can't find\|Non-existent domain"; then
        echo -e "${GREEN}✅ PASS${NC} (NXDOMAIN - correctly blocked)"
    else
        echo -e "${RED}❌ FAIL${NC} (Domain resolved - should be blocked!)"
        ALL_PASSED=false
    fi
done

echo ""
echo "============================"
echo ""

if [ "$ALL_PASSED" = true ]; then
    echo -e "${GREEN}✅ TEST PASSED${NC}"
    echo "   Empty whitelist correctly blocks all domains."
    exit 0
else
    echo -e "${RED}❌ TEST FAILED${NC}"
    echo "   Some domains are still resolving. System is NOT working correctly!"
    exit 1
fi
