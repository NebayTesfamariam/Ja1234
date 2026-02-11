#!/bin/bash
# ===============================
# START DNS SERVER NOW
# ===============================
echo "==============================="
echo "STARTING DNS SERVER"
echo "==============================="

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Check if already running
if pgrep -f "dns_whitelist_server.py" > /dev/null; then
    echo "✅ DNS server is al actief!"
    DNS_PID=$(pgrep -f "dns_whitelist_server.py")
    echo "   PID: $DNS_PID"
    exit 0
fi

# Check Python
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 niet gevonden. Installeer Python 3."
    exit 1
fi

# Check requests library
if ! python3 -c "import requests" 2>/dev/null; then
    echo "⚠️  requests library niet gevonden. Installeren..."
    pip3 install --user requests || pip3 install --break-system-packages requests
fi

# Create logs directory
mkdir -p logs

# Start DNS server
echo "🚀 Starting DNS Whitelist Server..."
echo "⚠️  Vereist sudo voor poort 53"

# Try to start with sudo
if sudo -n true 2>/dev/null; then
    # Sudo password already cached
    sudo nohup python3 dns_whitelist_server.py > logs/dns_server.log 2>&1 &
    DNS_PID=$!
    sleep 2
else
    # Need password
    echo ""
    echo "Voer je admin wachtwoord in:"
    sudo nohup python3 dns_whitelist_server.py > logs/dns_server.log 2>&1 &
    DNS_PID=$!
    sleep 2
fi

# Check if started
if pgrep -f "dns_whitelist_server.py" > /dev/null; then
    echo "✅ DNS server gestart! (PID: $DNS_PID)"
    echo "📋 Logs: logs/dns_server.log"
    echo ""
    echo "Test DNS server:"
    echo "  dig @127.0.0.1 google.com"
    echo "  ps aux | grep dns_whitelist_server | grep -v grep"
else
    echo "❌ DNS server start mislukt"
    echo "Check logs: logs/dns_server.log"
    echo ""
    echo "Mogelijke problemen:"
    echo "  1. Poort 53 al in gebruik"
    echo "  2. Geen sudo rechten"
    echo "  3. Python requests library ontbreekt"
    exit 1
fi
