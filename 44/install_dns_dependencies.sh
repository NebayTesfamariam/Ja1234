#!/bin/bash
# ===============================
# INSTALL DNS SERVER DEPENDENCIES
# ===============================
echo "==============================="
echo "INSTALLING DNS SERVER DEPENDENCIES"
echo "==============================="

# Check Python
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 not found. Please install Python 3."
    exit 1
fi

echo "✅ Python3 found: $(python3 --version)"

# Check if requests is already installed
if python3 -c "import requests" 2>/dev/null; then
    echo "✅ requests library already installed"
    exit 0
fi

# Try to install requests
echo "Installing requests library..."

# Try --user first
if pip3 install --user requests 2>/dev/null; then
    echo "✅ requests library installed (--user)"
    exit 0
fi

# Try with --break-system-packages (macOS)
if pip3 install --break-system-packages requests 2>/dev/null; then
    echo "✅ requests library installed (--break-system-packages)"
    exit 0
fi

# Try sudo pip3 install
if sudo pip3 install requests 2>/dev/null; then
    echo "✅ requests library installed (sudo)"
    exit 0
fi

echo "❌ Failed to install requests library"
echo ""
echo "Please install manually:"
echo "  pip3 install --break-system-packages requests"
echo "  OR"
echo "  sudo pip3 install requests"
exit 1
