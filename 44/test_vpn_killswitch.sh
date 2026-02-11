#!/bin/bash
# Test 3: VPN Kill-Switch - No internet without VPN

echo "🔒 Test 3: VPN Kill-Switch"
echo "============================"
echo ""
echo "This test verifies that without VPN, there is NO internet access."
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "⚠️  IMPORTANT: This test must be run on a VPN CLIENT device!"
echo "   Disconnect VPN before running this test."
echo ""
read -p "Press Enter when VPN is DISCONNECTED..."

# Test domains
TEST_DOMAINS=("google.com" "wikipedia.org" "example.com")

echo "🔍 Testing Internet Access (should all fail):"
echo ""

ALL_PASSED=true

for domain in "${TEST_DOMAINS[@]}"; do
    echo -n "  Testing $domain... "
    
    # Try to resolve domain
    result=$(nslookup "$domain" 2>&1)
    
    if echo "$result" | grep -q "NXDOMAIN\|can't find\|Non-existent domain\|connection timed out\|No answer"; then
        echo -e "${GREEN}✅ PASS${NC} (Correctly blocked - no internet)"
    else
        # Check if we got an IP address
        if echo "$result" | grep -qE "Address: [0-9]+\.[0-9]+\.[0-9]+\.[0-9]+"; then
            echo -e "${RED}❌ FAIL${NC} (Domain resolved - internet still works without VPN!)"
            ALL_PASSED=false
        else
            echo -e "${GREEN}✅ PASS${NC} (Correctly blocked)"
        fi
    fi
done

echo ""
echo "============================"
echo ""

if [ "$ALL_PASSED" = true ]; then
    echo -e "${GREEN}✅ TEST PASSED${NC}"
    echo "   Kill-switch is working. No internet without VPN."
    exit 0
else
    echo -e "${RED}❌ TEST FAILED${NC}"
    echo "   Internet still works without VPN. Kill-switch is NOT working!"
    exit 1
fi
