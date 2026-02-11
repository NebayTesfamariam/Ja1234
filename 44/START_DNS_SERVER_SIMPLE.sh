#!/bin/bash
# ===============================
# START DNS SERVER - SIMPLE VERSION
# ===============================

cd /Applications/XAMPP/xamppfiles/htdocs/44

echo "🚨 STARTING DNS SERVER..."
echo ""
echo "⚠️  Je wordt gevraagd om je wachtwoord in te voeren"
echo "   (Type je wachtwoord en druk ENTER - je ziet niets typen, dat is normaal!)"
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

# Start DNS server
echo "📋 Start DNS server..."
sudo python3 dns_whitelist_server.py
