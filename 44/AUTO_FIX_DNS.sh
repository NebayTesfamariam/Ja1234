#!/bin/bash
# ===============================
# AUTO FIX DNS SERVER
# ===============================

cd /Applications/XAMPP/xamppfiles/htdocs/44

echo "🚨 AUTOMATISCH FIXEN DNS SERVER..."
echo ""

# Check if already running
if pgrep -f "dns_whitelist_server.py" > /dev/null 2>&1; then
    echo "✅ DNS server draait al!"
    ps aux | grep dns_whitelist_server | grep -v grep
    exit 0
fi

# Create logs directory
mkdir -p logs
chmod 755 logs

# Check if LaunchDaemon is installed
if [ -f "/Library/LaunchDaemons/com.nebay.pornfree.dns.plist" ]; then
    echo "📋 LaunchDaemon gevonden, proberen te starten..."
    
    # Try to load LaunchDaemon (might work if already has permissions)
    if sudo launchctl load /Library/LaunchDaemons/com.nebay.pornfree.dns.plist 2>/dev/null; then
        sleep 3
        if pgrep -f "dns_whitelist_server.py" > /dev/null; then
            echo "✅ DNS server gestart via LaunchDaemon!"
            ps aux | grep dns_whitelist_server | grep -v grep
            exit 0
        fi
    fi
fi

# Try to install LaunchDaemon using osascript for password prompt
echo "📋 Installeren LaunchDaemon..."

# Copy plist first (might work without sudo if we're owner)
if [ ! -f "/Library/LaunchDaemons/com.nebay.pornfree.dns.plist" ]; then
    echo "   Kopiëren plist..."
    osascript -e "do shell script \"cp '$PWD/com.nebay.pornfree.dns.plist /Library/LaunchDaemons/ && chown root:wheel /Library/LaunchDaemons/com.nebay.pornfree.dns.plist && chmod 644 /Library/LaunchDaemons/com.nebay.pornfree.dns.plist\" with administrator privileges" 2>/dev/null
    
    if [ -f "/Library/LaunchDaemons/com.nebay.pornfree.dns.plist" ]; then
        echo "   ✅ Plist gekopieerd"
    else
        echo "   ⚠️  Kopiëren mislukt, proberen direct starten..."
    fi
fi

# Try to load LaunchDaemon
if [ -f "/Library/LaunchDaemons/com.nebay.pornfree.dns.plist" ]; then
    echo "   Laden LaunchDaemon..."
    osascript -e "do shell script \"launchctl load /Library/LaunchDaemons/com.nebay.pornfree.dns.plist\" with administrator privileges" 2>/dev/null
    
    sleep 3
    
    if pgrep -f "dns_whitelist_server.py" > /dev/null; then
        echo "✅ DNS server gestart via LaunchDaemon!"
        ps aux | grep dns_whitelist_server | grep -v grep
        exit 0
    fi
fi

# Fallback: Try direct start with osascript
echo "📋 Direct starten DNS server..."
osascript -e "do shell script \"cd '$PWD' && nohup python3 dns_whitelist_server.py > logs/dns_server.log 2>&1 &\" with administrator privileges" 2>/dev/null

sleep 3

# Check if running
if pgrep -f "dns_whitelist_server.py" > /dev/null; then
    echo "✅ DNS server gestart!"
    ps aux | grep dns_whitelist_server | grep -v grep
    echo ""
    echo "📋 Logs: logs/dns_server.log"
    echo ""
    echo "✅ PROBLEEM OPGELOST!"
else
    echo "❌ Automatisch starten mislukt"
    echo ""
    echo "⚠️  HANDMATIG STARTEN NODIG:"
    echo ""
    echo "Voer dit commando uit in Terminal:"
    echo "   cd /Applications/XAMPP/xamppfiles/htdocs/44"
    echo "   sudo python3 dns_whitelist_server.py"
    echo ""
    echo "Of installeer LaunchDaemon:"
    echo "   ./install_dns_launchdaemon.sh"
    exit 1
fi
