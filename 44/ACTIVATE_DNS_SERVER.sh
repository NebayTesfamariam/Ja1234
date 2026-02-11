#!/bin/bash
# ===============================
# ACTIVATE DNS SERVER
# ===============================
echo "==============================="
echo "ACTIVEREN DNS SERVER"
echo "==============================="
echo ""

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Check if already running
if pgrep -f "dns_whitelist_server.py" > /dev/null; then
    echo "✅ DNS server is al actief!"
    DNS_PID=$(pgrep -f "dns_whitelist_server.py")
    echo "   PID: $DNS_PID"
    echo ""
    echo "Check status:"
    echo "  ps aux | grep dns_whitelist_server | grep -v grep"
    exit 0
fi

# Check Python
echo "🔍 Checking Python..."
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 niet gevonden. Installeer Python 3."
    exit 1
fi
echo "   ✅ Python3 gevonden: $(python3 --version)"

# Check requests library
echo "🔍 Checking requests library..."
if ! python3 -c "import requests" 2>/dev/null; then
    echo "⚠️  requests library niet gevonden. Installeren..."
    pip3 install --user requests 2>/dev/null || pip3 install --break-system-packages requests 2>/dev/null
    if ! python3 -c "import requests" 2>/dev/null; then
        echo "❌ Kon requests library niet installeren"
        echo "   Probeer handmatig: pip3 install requests"
        exit 1
    fi
fi
echo "   ✅ requests library: OK"

# Create logs directory
mkdir -p logs
chmod 755 logs

# Check if port 53 is available
echo "🔍 Checking poort 53..."
if sudo lsof -i :53 > /dev/null 2>&1; then
    echo "⚠️  Poort 53 is al in gebruik"
    echo "   Stoppen van bestaande DNS processen..."
    sudo pkill -f "dns_whitelist_server.py" 2>/dev/null
    sleep 2
fi

# Start DNS server
echo ""
echo "🚀 Starting DNS Whitelist Server..."
echo "⚠️  Dit vereist sudo (admin wachtwoord) voor poort 53"
echo ""

# Start in background and log output
sudo nohup python3 dns_whitelist_server.py > logs/dns_server.log 2>&1 &
DNS_PID=$!

# Wait a moment for server to start
sleep 3

# Check if it's running
if pgrep -f "dns_whitelist_server.py" > /dev/null; then
    echo "✅ DNS server gestart! (PID: $DNS_PID)"
    echo ""
    echo "📋 Status:"
    echo "   Process: $(pgrep -f 'dns_whitelist_server.py')"
    echo "   Logs: logs/dns_server.log"
    echo ""
    echo "🧪 Test DNS server:"
    echo "   dig @127.0.0.1 google.com"
    echo "   nslookup google.com 127.0.0.1"
    echo ""
    echo "📊 Check status:"
    echo "   ps aux | grep dns_whitelist_server | grep -v grep"
    echo ""
    echo "🛑 Stop DNS server:"
    echo "   sudo pkill -f dns_whitelist_server.py"
else
    echo "❌ DNS server start mislukt"
    echo ""
    echo "Check logs:"
    echo "   tail -f logs/dns_server.log"
    echo ""
    echo "Mogelijke problemen:"
    echo "   1. Poort 53 al in gebruik (check: sudo lsof -i :53)"
    echo "   2. Geen sudo rechten"
    echo "   3. Python requests library ontbreekt"
    exit 1
fi
