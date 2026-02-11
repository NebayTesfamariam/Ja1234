#!/bin/bash
# ===============================
# START DNS SERVER SERVICE (macOS)
# ===============================
echo "==============================="
echo "START DNS SERVER SERVICE"
echo "==============================="
echo ""

# Check if LaunchDaemon is installed
if [ ! -f "/Library/LaunchDaemons/com.nebay.pornfree.dns.plist" ]; then
    echo "❌ LaunchDaemon niet gevonden!"
    echo ""
    echo "Installeer eerst met:"
    echo "  ./install_dns_launchdaemon.sh"
    exit 1
fi

# Check if already loaded
if sudo launchctl list 2>/dev/null | grep -q "pornfree.dns"; then
    echo "⚠️  LaunchDaemon al geladen"
    echo ""
    echo "Check status:"
    sudo launchctl list | grep pornfree.dns
    echo ""
    
    # Check if DNS server is running
    if ps aux | grep dns_whitelist_server | grep -v grep > /dev/null; then
        echo "✅ DNS server is al actief!"
        ps aux | grep dns_whitelist_server | grep -v grep
        exit 0
    else
        echo "⚠️  LaunchDaemon geladen maar DNS server niet actief"
        echo "   Herstarten..."
        sudo launchctl unload /Library/LaunchDaemons/com.nebay.pornfree.dns.plist
        sleep 1
    fi
fi

# Load LaunchDaemon
echo "📋 LaunchDaemon laden..."
sudo launchctl load /Library/LaunchDaemons/com.nebay.pornfree.dns.plist

if [ $? -eq 0 ]; then
    echo "   ✅ LaunchDaemon geladen"
else
    echo "   ❌ LaunchDaemon laden mislukt"
    exit 1
fi

# Wait for DNS server to start
echo ""
echo "📋 Wachten op DNS server start..."
for i in {1..10}; do
    sleep 1
    if ps aux | grep dns_whitelist_server | grep -v grep > /dev/null; then
        echo "   ✅ DNS server is actief!"
        ps aux | grep dns_whitelist_server | grep -v grep
        DNS_PID=$(ps aux | grep dns_whitelist_server | grep -v grep | awk '{print $2}')
        echo ""
        echo "   PID: $DNS_PID"
        echo ""
        echo "✅ DNS SERVER GESTART!"
        exit 0
    fi
done

echo "   ⚠️  DNS server nog niet actief na 10 seconden"
echo ""
echo "Check logs:"
if [ -f "logs/dns_server.error.log" ]; then
    echo "   Laatste errors:"
    sudo tail -5 logs/dns_server.error.log 2>/dev/null || tail -5 logs/dns_server.error.log
fi

exit 1
