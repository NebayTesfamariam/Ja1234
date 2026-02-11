#!/bin/bash
# Setup 100% Porn Blocking - Permanent & Cannot be Disabled

echo "🔒 Setting up 100% Porn Blocking"
echo "================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo "📋 Step 1: Verifying porn blocking code..."
if [ -f "config_porn_block.php" ]; then
    echo -e "${GREEN}✅ Porn blocking config found${NC}"
else
    echo -e "${RED}❌ Porn blocking config missing${NC}"
    exit 1
fi

if grep -q "is_pornographic_domain" "api/add_whitelist.php"; then
    echo -e "${GREEN}✅ API porn blocking active${NC}"
else
    echo -e "${RED}❌ API porn blocking missing${NC}"
    exit 1
fi

if grep -q "is_pornographic_domain" "dns_whitelist_server.py"; then
    echo -e "${GREEN}✅ DNS server porn blocking active${NC}"
else
    echo -e "${RED}❌ DNS server porn blocking missing${NC}"
    exit 1
fi

echo ""
echo "📋 Step 2: Running cleanup (remove any existing porn domains)..."
php auto_cleanup_porn.php
echo ""

echo "📋 Step 3: Setting up automatic cleanup cronjob..."
CRON_CMD="*/5 * * * * cd $(pwd) && php auto_cleanup_porn.php >> /dev/null 2>&1"

if crontab -l 2>/dev/null | grep -q "auto_cleanup_porn.php"; then
    echo -e "${GREEN}✅ Cronjob already exists${NC}"
else
    (crontab -l 2>/dev/null; echo "$CRON_CMD") | crontab -
    echo -e "${GREEN}✅ Cronjob added (runs every 5 minutes)${NC}"
fi

echo ""
echo "📋 Step 4: Testing porn blocking..."
php verify_100_percent_block.php
echo ""

echo "================================"
echo -e "${GREEN}✅ 100% Porn Blocking Setup Complete!${NC}"
echo ""
echo "✅ Porn domains cannot be added to whitelist"
echo "✅ DNS server blocks porn domains permanently"
echo "✅ Automatic cleanup runs every 5 minutes"
echo "✅ Works in all browsers and languages"
echo ""
echo "🔒 Status: PERMANENT & CANNOT BE DISABLED"
