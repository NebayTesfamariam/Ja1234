#!/bin/bash
# ===============================
# DOUBLE CLICK TO START DNS SERVER
# ===============================
# This script can be double-clicked in Finder

cd /Applications/XAMPP/xamppfiles/htdocs/44

# Open Terminal and run DNS server
osascript <<EOF
tell application "Terminal"
    activate
    do script "cd /Applications/XAMPP/xamppfiles/htdocs/44 && echo '🚨 STARTING DNS SERVER...' && echo '' && echo '⚠️  Je wordt gevraagd om je wachtwoord in te voeren' && echo '   (Type je wachtwoord en druk ENTER - je ziet niets typen, dat is normaal!)' && echo '' && sudo python3 dns_whitelist_server.py"
end tell
EOF
