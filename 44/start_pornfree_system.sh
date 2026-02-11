#!/bin/bash
# ===============================
# STARTING PORNFREE SYSTEM (macOS/Linux)
# ===============================
echo "==============================="
echo "STARTING PORNFREE SYSTEM"
echo "==============================="

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Start XAMPP MySQL (if not running)
echo "Starting MySQL..."
if ! pgrep -x "mysqld" > /dev/null; then
    sudo /Applications/XAMPP/xamppfiles/bin/mysql.server start
    echo "✅ MySQL started"
else
    echo "✅ MySQL already running"
fi

# Start XAMPP Apache (if not running)
echo "Starting Apache..."
if ! pgrep -x "httpd" > /dev/null; then
    sudo /Applications/XAMPP/xamppfiles/bin/httpd -k start
    echo "✅ Apache started"
else
    echo "✅ Apache already running"
fi

# Wait for services to be ready
echo "Waiting for services to be ready..."
sleep 5

# Check if DNS server is already running
if pgrep -f "dns_whitelist_server.py" > /dev/null; then
    echo "⚠️  DNS server already running"
    DNS_PID=$(pgrep -f "dns_whitelist_server.py")
    echo "✅ DNS server PID: $DNS_PID"
    exit 0
fi

# Check if port 53 is in use
if sudo lsof -i :53 > /dev/null 2>&1; then
    echo "⚠️  Port 53 is already in use"
    sudo lsof -i :53
    echo "Stopping existing DNS server..."
    sudo pkill -f "dns_whitelist_server.py"
    sleep 2
fi

# Start DNS Whitelist Server (port 53 - requires sudo)
echo "Starting DNS Whitelist Server..."
echo "⚠️  This requires sudo (admin) privileges for port 53"

# Start in background and log output
sudo nohup python3 dns_whitelist_server.py > logs/dns_server.log 2>&1 &
DNS_PID=$!

# Wait a moment for server to start
sleep 2

# Check if it's running
if pgrep -f "dns_whitelist_server.py" > /dev/null; then
    echo "✅ DNS server started successfully (PID: $DNS_PID)"
    echo "📋 Logs: logs/dns_server.log"
else
    echo "❌ DNS server failed to start"
    echo "Check logs: logs/dns_server.log"
    exit 1
fi
