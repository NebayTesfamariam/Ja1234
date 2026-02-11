#!/bin/bash
# ===============================
# CHECK AND FIX ALL ISSUES
# ===============================
cd /Applications/XAMPP/xamppfiles/htdocs/44

echo "🔍 CHECKING WEBSITE..."
echo "==============================="
echo ""

# Track issues
ISSUES=0
FIXES=0

# ===============================
# 1. CHECK SERVICES
# ===============================
echo "📋 Stap 1: Services Checken..."

# MySQL
if pgrep -x "mysqld" > /dev/null 2>&1; then
    echo "   ✅ MySQL: ACTIEF"
else
    echo "   ❌ MySQL: NIET ACTIEF"
    ISSUES=$((ISSUES + 1))
fi

# Apache
if pgrep -x "httpd" > /dev/null 2>&1; then
    echo "   ✅ Apache: ACTIEF"
else
    echo "   ❌ Apache: NIET ACTIEF"
    ISSUES=$((ISSUES + 1))
fi

# DNS Server
if pgrep -f "dns_whitelist_server.py" > /dev/null 2>&1; then
    echo "   ✅ DNS Server: ACTIEF"
else
    echo "   ❌ DNS Server: NIET ACTIEF"
    ISSUES=$((ISSUES + 1))
fi

# ===============================
# 2. CHECK DATABASE CONNECTION
# ===============================
echo ""
echo "📋 Stap 2: Database Checken..."

DB_CHECK=$(php -r "require 'config.php'; echo (\$conn && !\$conn->connect_error) ? 'OK' : 'FAILED';" 2>&1)
if [ "$DB_CHECK" = "OK" ]; then
    echo "   ✅ Database: VERBONDEN"
else
    echo "   ❌ Database: VERBINDING MISLUKT"
    echo "   Oorzaak: MySQL draait niet"
    ISSUES=$((ISSUES + 1))
fi

# ===============================
# 3. CHECK CRITICAL FILES
# ===============================
echo ""
echo "📋 Stap 3: Bestanden Checken..."

CRITICAL_FILES=(
    "config.php"
    "config_porn_block.php"
    "dns_whitelist_server.py"
    "api/login.php"
    "api/get_whitelist.php"
    "index.html"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✅ $file: Bestaat"
    else
        echo "   ❌ $file: ONTBREEKT"
        ISSUES=$((ISSUES + 1))
    fi
done

# ===============================
# 4. CHECK LOGS DIRECTORY
# ===============================
echo ""
echo "📋 Stap 4: Logs Directory Checken..."

if [ -d "logs" ]; then
    if [ -w "logs" ]; then
        echo "   ✅ logs/: Beschrijfbaar"
    else
        echo "   ⚠️  logs/: Niet beschrijfbaar"
        echo "   Fixing permissions..."
        chmod 755 logs 2>/dev/null
        if [ $? -eq 0 ]; then
            echo "   ✅ Permissions gefixed"
            FIXES=$((FIXES + 1))
        else
            echo "   ❌ Permissions fix mislukt (vereist sudo)"
            ISSUES=$((ISSUES + 1))
        fi
    fi
else
    echo "   ⚠️  logs/: Bestaat niet"
    echo "   Creating logs directory..."
    mkdir -p logs
    chmod 755 logs
    if [ $? -eq 0 ]; then
        echo "   ✅ logs/ directory aangemaakt"
        FIXES=$((FIXES + 1))
    else
        echo "   ❌ logs/ directory aanmaken mislukt"
        ISSUES=$((ISSUES + 1))
    fi
fi

# ===============================
# 5. SUMMARY
# ===============================
echo ""
echo "==============================="
echo "📊 SAMENVATTING"
echo "==============================="
echo "Gevonden problemen: $ISSUES"
echo "Automatisch gefixed: $FIXES"
echo ""

if [ $ISSUES -gt 0 ]; then
    echo "❌ PROBLEMEN GEVONDEN!"
    echo ""
    echo "📋 OPLOSSING:"
    echo "   Voer uit: ./FIX_EVERYTHING.command"
    echo "   Of double-click: FIX_EVERYTHING.command"
    echo ""
    echo "Dit start automatisch:"
    echo "   1. MySQL"
    echo "   2. Apache"
    echo "   3. DNS Server"
else
    echo "✅ GEEN PROBLEMEN GEVONDEN!"
    echo ""
    echo "Alles werkt correct!"
fi

echo ""
echo "==============================="
echo "📋 TEST"
echo "==============================="
echo "Website: http://localhost/44/"
echo "API: http://localhost/44/api/health.php"
echo ""
