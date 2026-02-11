#!/bin/bash
#
# Test Firewall Configuration
# Run this on VPN client to test if firewall is working
#

echo "🧪 Testing Firewall Configuration"
echo "=================================="
echo ""

# Test 1: DNS Forcing
echo "Test 1: DNS Forcing (should FAIL)"
echo "----------------------------------"
echo "Testing DNS query to 8.8.8.8..."
if timeout 3 nslookup google.com 8.8.8.8 2>&1 | grep -q "connection timed out\|no servers could be reached"; then
    echo "✅ PASS: DNS forcing works (8.8.8.8 blocked)"
else
    echo "❌ FAIL: DNS forcing NOT working (8.8.8.8 still accessible)"
fi
echo ""

# Test 2: VPN DNS
echo "Test 2: VPN DNS (should WORK)"
echo "------------------------------"
echo "Testing DNS query to 10.10.0.1..."
if timeout 3 nslookup wikipedia.org 10.10.0.1 2>&1 | grep -q "Name:\|Address:"; then
    echo "✅ PASS: VPN DNS works"
else
    echo "❌ FAIL: VPN DNS NOT working"
fi
echo ""

# Test 3: Kill-Switch (requires VPN disconnect)
echo "Test 3: Kill-Switch"
echo "-------------------"
echo "⚠️  MANUAL TEST REQUIRED:"
echo "   1. Disconnect VPN"
echo "   2. Try to visit a website"
echo "   3. Should NOT work"
echo ""

# Test 4: DNS-over-HTTPS
echo "Test 4: DNS-over-HTTPS Blocking (should FAIL)"
echo "-----------------------------------------------"
echo "Testing Cloudflare DoH..."
if timeout 3 curl -s -H "accept: application/dns-json" "https://1.1.1.1/dns-query?name=google.com" 2>&1 | grep -q "timeout\|Connection refused\|No route"; then
    echo "✅ PASS: DNS-over-HTTPS blocked"
else
    echo "❌ FAIL: DNS-over-HTTPS still works"
fi
echo ""

# Test 5: QUIC
echo "Test 5: QUIC Blocking"
echo "---------------------"
echo "⚠️  MANUAL TEST REQUIRED:"
echo "   1. Try to load YouTube video"
echo "   2. Should fail or be very slow (QUIC blocked)"
echo ""

echo "=================================="
echo "✅ Testing complete!"
echo ""
echo "If any test FAILED, check firewall configuration."
echo "See KILL_SWITCH_SETUP.md for troubleshooting."
