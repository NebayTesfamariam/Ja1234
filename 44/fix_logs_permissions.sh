#!/bin/bash
# ===============================
# Fix Logs Directory Permissions
# ===============================
echo "==============================="
echo "FIXING LOGS DIRECTORY PERMISSIONS"
echo "==============================="

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Create logs directory if it doesn't exist
if [ ! -d "logs" ]; then
    echo "📁 Creating logs directory..."
    mkdir -p logs
fi

# Get current user
CURRENT_USER=$(whoami)
CURRENT_GROUP=$(id -gn)

echo "👤 Current user: $CURRENT_USER"
echo "👥 Current group: $CURRENT_GROUP"
echo ""

# Fix permissions
echo "🔧 Setting permissions..."
sudo chmod 755 logs
sudo chown $CURRENT_USER:$CURRENT_GROUP logs

# Make sure it's writable
if [ -w "logs" ]; then
    echo "✅ logs directory is writable"
else
    echo "⚠️  logs directory still not writable, trying 777..."
    sudo chmod 777 logs
fi

# Test write access
echo ""
echo "🧪 Testing write access..."
if touch logs/test_write.txt 2>/dev/null; then
    rm -f logs/test_write.txt
    echo "✅ Write test successful!"
else
    echo "❌ Write test failed"
    echo "Trying with sudo..."
    sudo touch logs/test_write.txt
    sudo rm -f logs/test_write.txt
    sudo chmod 777 logs
    echo "✅ Permissions set to 777"
fi

# Show final permissions
echo ""
echo "📋 Final permissions:"
ls -ld logs

echo ""
echo "✅ Done!"
