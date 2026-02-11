#!/bin/bash
# Check DNS Server Status

echo "🔍 DNS Server Status Check"
echo "=========================="
echo ""

# Check if DNS server process is running
if ps aux | grep -i "dns_whitelist_server" | grep -v grep > /dev/null; then
    echo "✅ DNS Server proces: RUNNING"
    ps aux | grep -i "dns_whitelist_server" | grep -v grep | head -1
else
    echo "❌ DNS Server proces: NOT RUNNING"
    echo "   Start met: sudo python3 dns_whitelist_server.py"
fi

echo ""

# Check if port 53 is listening
if sudo netstat -tuln 2>/dev/null | grep ":53 " > /dev/null; then
    echo "✅ Poort 53: LISTENING"
    sudo netstat -tuln 2>/dev/null | grep ":53 " | head -1
else
    echo "⚠️  Poort 53: NOT LISTENING (vereist sudo om te checken)"
fi

echo ""

# Check Python dependencies
if python3 -c "import requests" 2>/dev/null; then
    echo "✅ Python requests library: INSTALLED"
else
    echo "❌ Python requests library: NOT INSTALLED"
    echo "   Installeer met: pip3 install requests"
fi

echo ""

# Check DNS server file
if [ -f "dns_whitelist_server.py" ]; then
    echo "✅ DNS server script: FOUND"
else
    echo "❌ DNS server script: NOT FOUND"
fi

echo ""
echo "=========================="
echo ""
