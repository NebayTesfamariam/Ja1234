#!/bin/bash
# Fix Compliance Issues

echo "🔧 Fixing Compliance Issues"
echo "==========================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# 1. Remove config_keywords.php (content detection)
if [ -f "config_keywords.php" ]; then
    echo "📋 Removing config_keywords.php (content detection)..."
    rm config_keywords.php
    echo -e "${GREEN}✅ Removed${NC}"
else
    echo -e "${GREEN}✅ config_keywords.php already removed${NC}"
fi

# 2. Check for blocklist API calls in frontend
echo ""
echo "📋 Checking frontend for blocklist API calls..."
if grep -rE "admin_blocklist|get_blocklist|add_blocklist|delete_blocklist|blocklist\.php" app.js admin/admin.js 2>/dev/null | grep -v "//" | grep -v "removed" | grep -v "comment"; then
    echo -e "${YELLOW}⚠️  Found blocklist references (may be comments)${NC}"
else
    echo -e "${GREEN}✅ No blocklist API calls found${NC}"
fi

# 3. Check for blocklist table usage in API
echo ""
echo "📋 Checking API for blocklist table usage..."
if grep -rE "FROM blocklist|INSERT INTO blocklist|UPDATE blocklist" api/*.php 2>/dev/null; then
    echo -e "${RED}❌ Blocklist tables are used in API${NC}"
else
    echo -e "${GREEN}✅ No blocklist table usage in API${NC}"
fi

# 4. Verify whitelist API returns array
echo ""
echo "📋 Verifying whitelist API returns array..."
if grep -q "json_out(\$domains" api/get_whitelist.php || grep -q "json_out(\[\]," api/get_whitelist.php; then
    echo -e "${GREEN}✅ Whitelist API returns array${NC}"
else
    echo -e "${RED}❌ Whitelist API may not return array${NC}"
fi

echo ""
echo "================================"
echo -e "${GREEN}✅ Compliance fix complete!${NC}"
echo ""
echo "Run: php verify_system_compliance.php"
