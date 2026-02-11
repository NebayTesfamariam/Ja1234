#!/bin/bash
# ===============================
# STOPPING PORNFREE SYSTEM (macOS/Linux)
# ===============================
echo "==============================="
echo "STOPPING PORNFREE SYSTEM"
echo "==============================="

# Stop DNS Server
echo "Stopping DNS Server..."
sudo pkill -f "dns_whitelist_server.py"
if [ $? -eq 0 ]; then
    echo "✅ DNS Server stopped"
else
    echo "⚠️  DNS Server was not running"
fi

# Stop XAMPP services (optional - comment out if you want to keep XAMPP running)
# echo "Stopping XAMPP services..."
# sudo /Applications/XAMPP/xamppfiles/bin/mysql.server stop
# sudo /Applications/XAMPP/xamppfiles/bin/httpd -k stop

echo ""
echo "✅ System stopped!"
