#!/bin/bash
# ===============================
# START APACHE NOW - FIX ERR_CONNECTION_REFUSED
# ===============================
echo "🚀 STARTING APACHE..."
echo ""

# Check if Apache is already running
if pgrep -x "httpd" > /dev/null 2>&1; then
    echo "✅ Apache draait al!"
    echo ""
    echo "Processen:"
    ps aux | grep httpd | grep -v grep | head -3
    echo ""
    echo "Test: http://localhost/44/"
    exit 0
fi

# Check if port 80 is in use
if lsof -i :80 2>/dev/null | grep -v "httpd" > /dev/null; then
    echo "⚠️  Poort 80 is al in gebruik door ander proces:"
    lsof -i :80
    echo ""
    echo "Wil je doorgaan? (j/n)"
    read -r answer
    if [ "$answer" != "j" ] && [ "$answer" != "J" ]; then
        exit 1
    fi
fi

echo "📋 Apache starten (vereist sudo)..."
echo "⚠️  Je wordt gevraagd om je wachtwoord in te voeren"
echo "   (Type je wachtwoord en druk ENTER - je ziet niets typen, dat is normaal!)"
echo ""

# Try to start Apache
if sudo /Applications/XAMPP/xamppfiles/bin/httpd -k start 2>&1; then
    sleep 2
    
    # Check if Apache is running
    if pgrep -x "httpd" > /dev/null 2>&1; then
        echo "✅ Apache gestart!"
        echo ""
        echo "Processen:"
        ps aux | grep httpd | grep -v grep | head -3
        echo ""
        echo "✅ TEST NU:"
        echo "   http://localhost/44/"
        echo ""
        echo "✅ Als het nog steeds niet werkt:"
        echo "   1. Check Apache logs: /Applications/XAMPP/xamppfiles/logs/error_log"
        echo "   2. Check poort 80: sudo lsof -i :80"
    else
        echo "❌ Apache start mislukt!"
        echo ""
        echo "Check logs:"
        tail -20 /Applications/XAMPP/xamppfiles/logs/error_log 2>/dev/null || echo "Geen logs beschikbaar"
    fi
else
    echo "❌ Apache start mislukt!"
    echo ""
    echo "Mogelijke oorzaken:"
    echo "1. Poort 80 al in gebruik"
    echo "2. Configuratie fout"
    echo "3. Permissies probleem"
    echo ""
    echo "Check logs:"
    tail -20 /Applications/XAMPP/xamppfiles/logs/error_log 2>/dev/null || echo "Geen logs beschikbaar"
fi
