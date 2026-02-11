#!/bin/bash
#
# Test Video/Image Blocking
# Run this on VPN client to verify video/image blocking works
#

echo "🎥 Testing Video/Image Blocking"
echo "================================"
echo ""

# Test 1: QUIC Blocking
echo "Test 1: QUIC Blocking (should FAIL)"
echo "------------------------------------"
echo "Testing QUIC connection..."
if timeout 3 nc -u -v 8.8.8.8 443 2>&1 | grep -q "timeout\|Connection refused\|No route"; then
    echo "✅ PASS: QUIC blocked (UDP 443)"
else
    echo "❌ FAIL: QUIC still works"
fi
echo ""

# Test 2: Direct IP Access
echo "Test 2: Direct IP Access (should FAIL)"
echo "--------------------------------------"
echo "Testing direct IP connection to Google..."
GOOGLE_IP="172.217.16.14"
if timeout 3 curl -s -k "https://$GOOGLE_IP" 2>&1 | grep -q "timeout\|Connection refused\|No route\|SSL\|certificate"; then
    echo "✅ PASS: Direct IP access blocked"
else
    echo "❌ FAIL: Direct IP access still works"
    echo "   Response received (should be blocked)"
fi
echo ""

# Test 3: DNS-over-TLS
echo "Test 3: DNS-over-TLS Blocking (should FAIL)"
echo "-------------------------------------------"
echo "Testing DNS-over-TLS connection..."
if timeout 3 nc -v 1.1.1.1 853 2>&1 | grep -q "timeout\|Connection refused\|No route"; then
    echo "✅ PASS: DNS-over-TLS blocked (TCP 853)"
else
    echo "❌ FAIL: DNS-over-TLS still works"
fi
echo ""

# Test 4: Video Site (should fail if not whitelisted)
echo "Test 4: Video Site Access"
echo "-------------------------"
echo "Testing YouTube (should fail if not whitelisted)..."
if timeout 5 curl -s "https://www.youtube.com" 2>&1 | grep -q "timeout\|Connection refused\|NXDOMAIN"; then
    echo "✅ PASS: YouTube blocked (not in whitelist)"
else
    echo "⚠️  WARNING: YouTube accessible (check if in whitelist)"
fi
echo ""

# Test 5: Image CDN (should fail if not whitelisted)
echo "Test 5: Image CDN Access"
echo "-----------------------"
echo "Testing Google Images CDN (should fail if not whitelisted)..."
if timeout 5 curl -s "https://lh3.googleusercontent.com" 2>&1 | grep -q "timeout\|Connection refused\|NXDOMAIN"; then
    echo "✅ PASS: Image CDN blocked (not in whitelist)"
else
    echo "⚠️  WARNING: Image CDN accessible (check if in whitelist)"
fi
echo ""

# Test 6: Whitelisted Domain (should work)
echo "Test 6: Whitelisted Domain (should WORK)"
echo "-----------------------------------------"
echo "Testing whitelisted domain (wikipedia.org)..."
if timeout 5 curl -s "https://wikipedia.org" 2>&1 | grep -q "Wikipedia\|<!DOCTYPE"; then
    echo "✅ PASS: Whitelisted domain works"
else
    echo "❌ FAIL: Whitelisted domain not working"
    echo "   Check DNS and whitelist configuration"
fi
echo ""

echo "================================"
echo "✅ Testing complete!"
echo ""
echo "📋 Summary:"
echo "   • QUIC should be blocked"
echo "   • Direct IP access should be blocked"
echo "   • DNS-over-TLS should be blocked"
echo "   • Non-whitelisted sites should be blocked"
echo "   • Whitelisted sites should work"
echo ""
echo "If any test FAILED, check firewall configuration."
echo "See VIDEO_IMAGE_BLOCKING.md for troubleshooting."
