#!/bin/bash
# Quick Fix: Start DNS Server om pornografische content te blokkeren

echo "🚨 PROBLEEM: Pornografische content is zichtbaar"
echo "✅ OPLOSSING: Start DNS server"
echo ""

cd "$(dirname "$0")"

# Check Python
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 niet gevonden!"
    exit 1
fi

# Check requests library
if ! python3 -c "import requests" 2>/dev/null; then
    echo "❌ requests library niet geïnstalleerd!"
    echo "Installeer met: pip3 install requests"
    exit 1
fi

# Check of DNS server al draait
if pgrep -f "dns_whitelist_server.py" > /dev/null; then
    echo "⚠️  DNS server draait al!"
    echo "Proces:"
    ps aux | grep dns_whitelist_server | grep -v grep
    echo ""
    echo "Wil je de DNS server herstarten? (j/n)"
    read -r answer
    if [ "$answer" != "j" ] && [ "$answer" != "J" ]; then
        echo "DNS server blijft draaien."
        exit 0
    fi
    echo "Stoppen DNS server..."
    pkill -f "dns_whitelist_server.py"
    sleep 2
fi

# Check poort 53
if lsof -i :53 2>/dev/null | grep -v "dns_whitelist_server" > /dev/null; then
    echo "⚠️  Poort 53 is al in gebruik door ander proces!"
    echo "Processen op poort 53:"
    sudo lsof -i :53
    echo ""
    echo "Wil je doorgaan? (j/n)"
    read -r answer
    if [ "$answer" != "j" ] && [ "$answer" != "J" ]; then
        exit 1
    fi
fi

echo "✅ Start DNS server..."
echo ""

# Start DNS server in achtergrond
sudo python3 dns_whitelist_server.py > logs/dns_server.log 2>&1 &

# Wacht even
sleep 2

# Check of DNS server draait
if pgrep -f "dns_whitelist_server.py" > /dev/null; then
    echo "✅ DNS server gestart!"
    echo ""
    echo "Proces:"
    ps aux | grep dns_whitelist_server | grep -v grep
    echo ""
    echo "Logs:"
    tail -5 logs/dns_server.log 2>/dev/null || echo "Geen logs beschikbaar"
    echo ""
    echo "📋 VOLGENDE STAPPEN:"
    echo "1. Check of VPN verbonden is op je device"
    echo "2. Check of WireGuard config DNS = 10.10.0.1 heeft"
    echo "3. Test porn blokkering: probeer pornhub.com te bezoeken"
    echo ""
    echo "⚠️  BELANGRIJK:"
    echo "- DNS server MOET blijven draaien"
    echo "- VPN MOET verbonden zijn"
    echo "- Whitelist bepaalt welke sites werken"
else
    echo "❌ DNS server start mislukt!"
    echo ""
    echo "Check logs:"
    cat logs/dns_server.log 2>/dev/null || echo "Geen logs beschikbaar"
    echo ""
    echo "Probeer handmatig:"
    echo "sudo python3 dns_whitelist_server.py"
fi
