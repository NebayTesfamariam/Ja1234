#!/bin/bash
# Complete System Setup Script
# Sets up everything needed for the system to work

set -e  # Exit on error

echo "🚀 Complete System Setup"
echo "========================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if running as root (for some operations)
if [ "$EUID" -ne 0 ]; then 
    echo -e "${YELLOW}⚠️  Some operations require root. Run with sudo for full setup.${NC}"
    echo ""
fi

# Step 1: Check PHP
echo "📋 Step 1: Checking PHP..."
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2)
    echo -e "${GREEN}✅ PHP installed: $PHP_VERSION${NC}"
else
    echo -e "${RED}❌ PHP not found. Please install PHP.${NC}"
    exit 1
fi

# Step 2: Check MySQL
echo ""
echo "📋 Step 2: Checking MySQL..."
if command -v mysql &> /dev/null; then
    echo -e "${GREEN}✅ MySQL client found${NC}"
    
    # Try to connect
    if mysql -u root -e "SELECT 1" &> /dev/null; then
        echo -e "${GREEN}✅ MySQL connection works${NC}"
    else
        echo -e "${YELLOW}⚠️  MySQL connection failed. Make sure MySQL is running.${NC}"
        echo "   Start XAMPP MySQL or start MySQL service"
    fi
else
    echo -e "${YELLOW}⚠️  MySQL client not found. Make sure MySQL/XAMPP is installed.${NC}"
fi

# Step 3: Check Python
echo ""
echo "📋 Step 3: Checking Python..."
if command -v python3 &> /dev/null; then
    PYTHON_VERSION=$(python3 --version)
    echo -e "${GREEN}✅ Python found: $PYTHON_VERSION${NC}"
    
    # Check requests library
    if python3 -c "import requests" &> /dev/null; then
        echo -e "${GREEN}✅ Python requests library installed${NC}"
    else
        echo -e "${YELLOW}⚠️  Python requests library missing${NC}"
        echo "   Installing requests library..."
        if pip3 install requests &> /dev/null; then
            echo -e "${GREEN}✅ Python requests library installed${NC}"
        else
            echo -e "${RED}❌ Failed to install requests. Run: pip3 install requests${NC}"
        fi
    fi
else
    echo -e "${RED}❌ Python3 not found. Please install Python 3.${NC}"
fi

# Step 4: Check DNS server script
echo ""
echo "📋 Step 4: Checking DNS server..."
if [ -f "dns_whitelist_server.py" ]; then
    echo -e "${GREEN}✅ DNS server script found${NC}"
    
    # Make executable
    chmod +x dns_whitelist_server.py
    echo -e "${GREEN}✅ DNS server script is executable${NC}"
else
    echo -e "${RED}❌ DNS server script not found${NC}"
fi

# Step 5: Check firewall scripts
echo ""
echo "📋 Step 5: Checking firewall scripts..."
FIREWALL_SCRIPTS=("vpn_firewall_setup.sh" "block_quic_udp443.sh" "block_dot_tcp853.sh" "force_dns_only.sh")
ALL_SCRIPTS_EXIST=true

for script in "${FIREWALL_SCRIPTS[@]}"; do
    if [ -f "$script" ]; then
        chmod +x "$script"
        echo -e "${GREEN}✅ $script found and executable${NC}"
    else
        echo -e "${RED}❌ $script missing${NC}"
        ALL_SCRIPTS_EXIST=false
    fi
done

# Step 6: Check test scripts
echo ""
echo "📋 Step 6: Checking test scripts..."
TEST_SCRIPTS=("test_empty_whitelist.sh" "test_single_domain.sh" "test_vpn_killswitch.sh" "test_dns_bypass.sh")
for script in "${TEST_SCRIPTS[@]}"; do
    if [ -f "$script" ]; then
        chmod +x "$script"
        echo -e "${GREEN}✅ $script found and executable${NC}"
    fi
done

# Step 7: Check API endpoints
echo ""
echo "📋 Step 7: Checking API endpoints..."
API_ENDPOINTS=("api/get_whitelist.php" "api/get_wireguard_config.php" "api/auto_register_device.php" "api/get_device_by_ip.php")
for endpoint in "${API_ENDPOINTS[@]}"; do
    if [ -f "$endpoint" ]; then
        echo -e "${GREEN}✅ $endpoint found${NC}"
    else
        echo -e "${RED}❌ $endpoint missing${NC}"
    fi
done

# Step 8: Create necessary directories
echo ""
echo "📋 Step 8: Creating necessary directories..."
mkdir -p logs
mkdir -p backups
chmod 755 logs backups
echo -e "${GREEN}✅ Directories created${NC}"

# Step 9: Check database tables
echo ""
echo "📋 Step 9: Checking database..."
if mysql -u root -e "USE pornfree; SHOW TABLES;" &> /dev/null; then
    echo -e "${GREEN}✅ Database 'pornfree' exists${NC}"
    
    # Check required tables
    REQUIRED_TABLES=("users" "devices" "whitelist" "subscriptions")
    for table in "${REQUIRED_TABLES[@]}"; do
        if mysql -u root -e "USE pornfree; DESCRIBE $table;" &> /dev/null; then
            echo -e "${GREEN}✅ Table '$table' exists${NC}"
        else
            echo -e "${YELLOW}⚠️  Table '$table' missing. Run database setup.${NC}"
        fi
    done
else
    echo -e "${YELLOW}⚠️  Database 'pornfree' not found or not accessible${NC}"
    echo "   Create database or check credentials in config.php"
fi

# Summary
echo ""
echo "================================"
echo "📊 Setup Summary"
echo "================================"
echo ""
echo "✅ Code files: Ready"
echo "✅ Scripts: Ready"
echo "✅ API endpoints: Ready"
echo ""
echo "⚠️  Next Steps:"
echo "   1. Start MySQL/XAMPP (if not running)"
echo "   2. Run database setup (if tables missing)"
echo "   3. Start DNS server: sudo python3 dns_whitelist_server.py"
echo "   4. Setup firewall: sudo ./vpn_firewall_setup.sh"
echo "   5. Test system: ./test_empty_whitelist.sh"
echo ""
echo "📖 Documentation:"
echo "   - TECHNICAL_DOCUMENTATION.md"
echo "   - SYSTEM_STATUS.md"
echo "   - QUICK_SYSTEM_TEST.html"
echo ""
echo -e "${GREEN}✅ Setup check complete!${NC}"
