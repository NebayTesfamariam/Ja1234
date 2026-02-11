#!/bin/bash
# ===============================
# START ALL SERVICES: MySQL, Apache, DNS Server
# ===============================
echo "🚀 STARTING ALL SERVICES..."
echo "==============================="
echo ""

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Create logs directory
mkdir -p logs
chmod 755 logs

# ===============================
# 1. START MYSQL
# ===============================
echo "📋 Stap 1: MySQL starten..."
if pgrep -x "mysqld" > /dev/null 2>&1; then
    echo "   ✅ MySQL draait al"
else
    echo "   ⚠️  MySQL starten (vereist sudo)..."
    if sudo /Applications/XAMPP/xamppfiles/bin/mysql.server start 2>&1; then
        sleep 2
        if pgrep -x "mysqld" > /dev/null 2>&1; then
            echo "   ✅ MySQL gestart"
        else
            echo "   ❌ MySQL start mislukt"
        fi
    else
        echo "   ❌ MySQL start mislukt (check sudo permissions)"
    fi
fi

# ===============================
# 2. START APACHE
# ===============================
echo ""
echo "📋 Stap 2: Apache starten..."
if pgrep -x "httpd" > /dev/null 2>&1; then
    echo "   ✅ Apache draait al"
else
    echo "   ⚠️  Apache starten (vereist sudo)..."
    if sudo /Applications/XAMPP/xamppfiles/bin/httpd -k start 2>&1; then
        sleep 2
        if pgrep -x "httpd" > /dev/null 2>&1; then
            echo "   ✅ Apache gestart"
        else
            echo "   ❌ Apache start mislukt"
        fi
    else
        echo "   ❌ Apache start mislukt (check sudo permissions)"
    fi
fi

# ===============================
# 3. START DNS SERVER
# ===============================
echo ""
echo "📋 Stap 3: DNS Server starten..."

# Check if DNS server is already running
if pgrep -f "dns_whitelist_server.py" > /dev/null 2>&1; then
    echo "   ✅ DNS server draait al"
    DNS_PID=$(pgrep -f "dns_whitelist_server.py" | head -1)
    echo "   PID: $DNS_PID"
else
    # Check Python
    if ! command -v python3 &> /dev/null; then
        echo "   ❌ Python3 niet gevonden!"
        exit 1
    fi
    
    # Check requests library
    if ! python3 -c "import requests" 2>/dev/null; then
        echo "   ⚠️  Installing requests library..."
        pip3 install --user requests 2>&1
    fi
    
    # Check if port 53 is in use
    if sudo lsof -i :53 > /dev/null 2>&1; then
        echo "   ⚠️  Poort 53 al in gebruik, stoppen oude DNS server..."
        sudo pkill -f "dns_whitelist_server.py" 2>/dev/null
        sleep 2
    fi
    
    echo "   ⚠️  DNS server starten (vereist sudo voor poort 53)..."
    
    # Start DNS server in background
    sudo nohup python3 dns_whitelist_server.py > logs/dns_server.log 2>&1 &
    DNS_PID=$!
    
    # Wait for server to start
    sleep 3
    
    # Check if running
    if pgrep -f "dns_whitelist_server.py" > /dev/null 2>&1; then
        echo "   ✅ DNS server gestart (PID: $DNS_PID)"
    else
        echo "   ❌ DNS server start mislukt"
        echo "   Check logs: logs/dns_server.log"
    fi
fi

# ===============================
# VERIFICATION
# ===============================
echo ""
echo "==============================="
echo "✅ VERIFICATIE"
echo "==============================="

# Check MySQL
if pgrep -x "mysqld" > /dev/null 2>&1; then
    echo "✅ MySQL: ACTIEF"
else
    echo "❌ MySQL: NIET ACTIEF"
fi

# Check Apache
if pgrep -x "httpd" > /dev/null 2>&1; then
    echo "✅ Apache: ACTIEF"
else
    echo "❌ Apache: NIET ACTIEF"
fi

# Check DNS Server
if pgrep -f "dns_whitelist_server.py" > /dev/null 2>&1; then
    echo "✅ DNS Server: ACTIEF"
else
    echo "❌ DNS Server: NIET ACTIEF"
fi

echo ""
echo "==============================="
echo "📋 VOLGENDE STAPPEN:"
echo "==============================="
echo "1. Test website: http://localhost/44/"
echo "2. Test API: http://localhost/44/api/health.php"
echo "3. Check DNS: dig @127.0.0.1 google.com"
echo ""
echo "📋 Logs:"
echo "   - DNS Server: logs/dns_server.log"
echo "   - Apache: /Applications/XAMPP/xamppfiles/logs/"
echo "   - MySQL: /Applications/XAMPP/xamppfiles/logs/"
echo ""
