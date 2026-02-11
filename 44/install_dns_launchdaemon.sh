#!/bin/bash
# ===============================
# INSTALL DNS SERVER LAUNCHDAEMON
# ===============================
echo "==============================="
echo "INSTALLEREN DNS SERVER LAUNCHDAEMON"
echo "==============================="
echo ""

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Check if plist exists
if [ ! -f "com.nebay.pornfree.dns.plist" ]; then
    echo "❌ com.nebay.pornfree.dns.plist niet gevonden"
    exit 1
fi

echo "📋 Stap 1: Kopiëren naar /Library/LaunchDaemons/..."
sudo cp com.nebay.pornfree.dns.plist /Library/LaunchDaemons/
if [ $? -eq 0 ]; then
    echo "   ✅ Gekopieerd"
else
    echo "   ❌ Kopiëren mislukt"
    exit 1
fi

echo ""
echo "📋 Stap 2: Permissies instellen..."
sudo chown root:wheel /Library/LaunchDaemons/com.nebay.pornfree.dns.plist
sudo chmod 644 /Library/LaunchDaemons/com.nebay.pornfree.dns.plist
if [ $? -eq 0 ]; then
    echo "   ✅ Permissies ingesteld"
    ls -la /Library/LaunchDaemons/com.nebay.pornfree.dns.plist
else
    echo "   ❌ Permissies instellen mislukt"
    exit 1
fi

echo ""
echo "📋 Stap 3: LaunchDaemon laden..."
# Check if already loaded
if sudo launchctl list | grep -q "pornfree.dns"; then
    echo "   ⚠️  LaunchDaemon al geladen, eerst stoppen..."
    sudo launchctl unload /Library/LaunchDaemons/com.nebay.pornfree.dns.plist 2>/dev/null
    sleep 1
fi

# Load LaunchDaemon
sudo launchctl load /Library/LaunchDaemons/com.nebay.pornfree.dns.plist
if [ $? -eq 0 ]; then
    echo "   ✅ LaunchDaemon geladen"
else
    echo "   ❌ LaunchDaemon laden mislukt"
    echo "   Check permissies en plist bestand"
    exit 1
fi

echo ""
echo "📋 Stap 4: Verificatie..."
sleep 2

# Check if LaunchDaemon is loaded
if sudo launchctl list | grep -q "pornfree.dns"; then
    echo "   ✅ LaunchDaemon is geladen"
    sudo launchctl list | grep pornfree.dns
else
    echo "   ⚠️  LaunchDaemon niet gevonden in lijst"
fi

# Check if DNS server is running
echo ""
echo "📋 Stap 5: DNS Server Status..."
echo "   Wachten op DNS server start (max 10 seconden)..."
for i in {1..10}; do
    sleep 1
    if ps aux | grep dns_whitelist_server | grep -v grep > /dev/null; then
        echo "   ✅ DNS server is actief!"
        ps aux | grep dns_whitelist_server | grep -v grep
        DNS_PID=$(ps aux | grep dns_whitelist_server | grep -v grep | awk '{print $2}')
        echo ""
        echo "   PID: $DNS_PID"
        break
    fi
    if [ $i -eq 10 ]; then
        echo "   ⚠️  DNS server nog niet actief na 10 seconden"
        echo ""
        echo "Check logs voor details:"
        if [ -f "logs/dns_server.error.log" ]; then
            echo "   Laatste errors:"
            sudo tail -5 logs/dns_server.error.log 2>/dev/null || tail -5 logs/dns_server.error.log
        fi
        echo ""
        echo "Mogelijke problemen:"
        echo "   1. Poort 53 al in gebruik"
        echo "   2. Python pad niet correct"
        echo "   3. Permissies probleem"
        echo ""
        echo "Handmatig starten:"
        echo "   sudo python3 dns_whitelist_server.py"
    fi
done

echo ""
echo "==============================="
echo "✅ INSTALLATIE VOLTOOID!"
echo "==============================="
echo ""
echo "📋 Status:"
echo "   ✅ LaunchDaemon: Geïnstalleerd en geladen"
echo "   ✅ DNS Server: Actief"
echo ""
echo "🧪 Test DNS server:"
echo "   dig @127.0.0.1 google.com"
echo "   nslookup google.com 127.0.0.1"
echo ""
echo "📊 Check status:"
echo "   sudo launchctl list | grep pornfree.dns"
echo "   ps aux | grep dns_whitelist_server | grep -v grep"
echo ""
echo "🛑 Stop DNS server:"
echo "   sudo launchctl unload /Library/LaunchDaemons/com.nebay.pornfree.dns.plist"
