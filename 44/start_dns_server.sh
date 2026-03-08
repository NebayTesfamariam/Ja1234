#!/bin/bash
# ===============================
# START DNS SERVER ONLY
# ===============================
echo "==============================="
echo "STARTING DNS SERVER"
echo "==============================="

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

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
    echo "Stopping existing DNS processes..."
    sudo pkill -f "dns_whitelist_server.py"
    sleep 2
fi

# Check Python and requests
echo "Checking Python..."
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 not found. Please install Python 3."
    exit 1
fi

# Use a venv to avoid PEP 668 / --break-system-packages on modern Linux
VENV_DIR="$SCRIPT_DIR/.venv"
if [ ! -d "$VENV_DIR" ]; then
    echo "Creating Python venv in $VENV_DIR..."
    python3 -m venv "$VENV_DIR"
    "$VENV_DIR/bin/pip" install -r "$SCRIPT_DIR/requirements.txt"
fi
PYTHON="$VENV_DIR/bin/python3"
echo "Using Python: $PYTHON"

if ! "$PYTHON" -c "import requests" 2>/dev/null; then
    echo "⚠️  requests library not found. Installing into venv..."
    "$VENV_DIR/bin/pip" install -r "$SCRIPT_DIR/requirements.txt"
fi

# Create logs directory
mkdir -p logs

# Start DNS Whitelist Server (port 53 - requires sudo)
echo "Starting DNS Whitelist Server..."
echo "⚠️  This requires sudo (admin) privileges for port 53"

# Start in background and log output
sudo nohup "$PYTHON" dns_whitelist_server.py > logs/dns_server.log 2>&1 &
DNS_PID=$!

# Wait a moment for server to start
sleep 3

# Check if it's running
if pgrep -f "dns_whitelist_server.py" > /dev/null; then
    echo "✅ DNS server started successfully (PID: $DNS_PID)"
    echo "📋 Logs: logs/dns_server.log"
    echo ""
    echo "Test DNS server:"
    echo "  dig @127.0.0.1 google.com"
    echo "  nslookup google.com 127.0.0.1"
else
    echo "❌ DNS server failed to start"
    echo "Check logs: logs/dns_server.log"
    echo ""
    echo "Common issues:"
    echo "  1. Port 53 already in use (check: sudo lsof -i :53)"
    echo "  2. Python requests library missing (install: pip3 install requests)"
    echo "  3. Permission denied (run with sudo)"
    exit 1
fi
