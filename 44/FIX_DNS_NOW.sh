#!/bin/bash
# ===============================
# FIX DNS SERVER NOW
# ===============================
echo "🚨 FIXING DNS SERVER..."
echo ""

cd "$(dirname "$0")"

# Check Python
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 niet gevonden!"
    exit 1
fi

# Check requests library
if ! python3 -c "import requests" 2>/dev/null; then
    echo "⚠️  Installing requests library..."
    pip3 install --user requests 2>&1
fi

# Create logs directory
mkdir -p logs
chmod 755 logs

# Check if DNS server is already running
if pgrep -f "dns_whitelist_server.py" > /dev/null; then
    echo "✅ DNS server draait al!"
    ps aux | grep dns_whitelist_server | grep -v grep
    exit 0
fi

# Check if LaunchDaemon is installed
if [ -f "/Library/LaunchDaemons/com.nebay.pornfree.dns.plist" ]; then
    echo "📋 LaunchDaemon gevonden, proberen te starten..."
    
    # Check if loaded
    if sudo launchctl list 2>/dev/null | grep -q "pornfree.dns"; then
        echo "✅ LaunchDaemon is geladen"
        echo "   Herstarten..."
        sudo launchctl unload /Library/LaunchDaemons/com.nebay.pornfree.dns.plist 2>/dev/null
        sleep 1
    fi
    
    sudo launchctl load /Library/LaunchDaemons/com.nebay.pornfree.dns.plist 2>/dev/null
    sleep 3
    
    if pgrep -f "dns_whitelist_server.py" > /dev/null; then
        echo "✅ DNS server gestart via LaunchDaemon!"
        ps aux | grep dns_whitelist_server | grep -v grep
        exit 0
    else
        echo "⚠️  LaunchDaemon start mislukt, proberen direct starten..."
    fi
fi

# Direct start DNS server
echo "📋 Start DNS server direct..."
echo "⚠️  Vereist sudo voor poort 53"

# Try to start in background
sudo nohup python3 dns_whitelist_server.py > logs/dns_server.log 2>&1 &
DNS_PID=$!

# Wait for server to start
sleep 3

# Check if running
if pgrep -f "dns_whitelist_server.py" > /dev/null; then
    echo "✅ DNS server gestart! (PID: $DNS_PID)"
    echo ""
    ps aux | grep dns_whitelist_server | grep -v grep
    echo ""
    echo "📋 Logs: logs/dns_server.log"
    echo ""
    echo "✅ PROBLEEM OPGELOST!"
    echo ""
    echo "📋 VOLGENDE STAPPEN:"
    echo "1. Check VPN verbinding op je device"
    echo "2. Test porn blokkering: probeer pornhub.com"
    echo "3. Check whitelist via control panel"
else
    echo "❌ DNS server start mislukt!"
    echo ""
    echo "Check logs:"
    tail -20 logs/dns_server.log 2>/dev/null || echo "Geen logs beschikbaar"
    echo ""
    echo "⚠️  MOGELIJKE OORZAKEN:"
    echo "1. Poort 53 al in gebruik (check: sudo lsof -i :53)"
    echo "2. Permissies probleem (vereist sudo)"
    echo "3. Python pad niet correct"
    echo ""
    echo "Probeer handmatig:"
    echo "   sudo python3 dns_whitelist_server.py"
    exit 1
fi
