#!/bin/bash
# ===============================
# FIX EVERYTHING: Apache, MySQL, DNS Server
# ===============================
cd /Applications/XAMPP/xamppfiles/htdocs/44

echo "🚀 FIXING EVERYTHING..."
echo "==============================="
echo ""
echo "Dit script start:"
echo "  1. MySQL"
echo "  2. Apache"
echo "  3. DNS Server"
echo ""
echo "⚠️  Je wordt meerdere keren gevraagd om je wachtwoord in te voeren"
echo "   (Type je wachtwoord en druk ENTER - je ziet niets typen, dat is normaal!)"
echo ""

# Start MySQL
echo "📋 Stap 1: MySQL starten..."
if pgrep -x "mysqld" > /dev/null 2>&1; then
    echo "   ✅ MySQL draait al"
else
    echo "   ⚠️  MySQL starten..."
    sudo /Applications/XAMPP/xamppfiles/bin/mysql.server start
    sleep 2
    if pgrep -x "mysqld" > /dev/null 2>&1; then
        echo "   ✅ MySQL gestart"
    else
        echo "   ❌ MySQL start mislukt"
    fi
fi

# Start Apache
echo ""
echo "📋 Stap 2: Apache starten..."
if pgrep -x "httpd" > /dev/null 2>&1; then
    echo "   ✅ Apache draait al"
else
    echo "   ⚠️  Apache starten..."
    sudo /Applications/XAMPP/xamppfiles/bin/httpd -k start
    sleep 2
    if pgrep -x "httpd" > /dev/null 2>&1; then
        echo "   ✅ Apache gestart"
    else
        echo "   ❌ Apache start mislukt"
    fi
fi

# Start DNS Server
echo ""
echo "📋 Stap 3: DNS Server starten..."
if pgrep -f "dns_whitelist_server.py" > /dev/null 2>&1; then
    echo "   ✅ DNS server draait al"
else
    echo "   ⚠️  DNS server starten..."
    
    # Check Python
    if ! command -v python3 &> /dev/null; then
        echo "   ❌ Python3 niet gevonden!"
    else
        # Check requests library
        if ! python3 -c "import requests" 2>/dev/null; then
            echo "   ⚠️  Installing requests library..."
            pip3 install --user requests 2>&1
        fi
        
        # Create logs directory
        mkdir -p logs
        chmod 755 logs
        
        # Start DNS server
        sudo nohup python3 dns_whitelist_server.py > logs/dns_server.log 2>&1 &
        sleep 3
        
        if pgrep -f "dns_whitelist_server.py" > /dev/null 2>&1; then
            echo "   ✅ DNS server gestart"
        else
            echo "   ❌ DNS server start mislukt"
        fi
    fi
fi

# Verification
echo ""
echo "==============================="
echo "✅ VERIFICATIE"
echo "==============================="

if pgrep -x "mysqld" > /dev/null 2>&1; then
    echo "✅ MySQL: ACTIEF"
else
    echo "❌ MySQL: NIET ACTIEF"
fi

if pgrep -x "httpd" > /dev/null 2>&1; then
    echo "✅ Apache: ACTIEF"
else
    echo "❌ Apache: NIET ACTIEF"
fi

if pgrep -f "dns_whitelist_server.py" > /dev/null 2>&1; then
    echo "✅ DNS Server: ACTIEF"
else
    echo "❌ DNS Server: NIET ACTIEF"
fi

echo ""
echo "==============================="
echo "📋 TEST NU:"
echo "==============================="
echo "Website: http://localhost/44/"
echo "API: http://localhost/44/api/health.php"
echo ""
echo "✅ Als alles actief is, zou de website nu moeten werken!"
echo ""
