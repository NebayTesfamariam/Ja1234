-- Start All Services: MySQL, Apache, DNS Server
-- This will prompt for your password and start all services

set projectPath to "/Applications/XAMPP/xamppfiles/htdocs/44"

tell application "Terminal"
    activate
    do script "cd " & quoted form of projectPath & " && echo '🚀 STARTING ALL SERVICES...' && echo '' && echo '⚠️  Je wordt gevraagd om je wachtwoord in te voeren' && echo '   (Type je wachtwoord en druk ENTER - je ziet niets typen, dat is normaal!)' && echo '' && ./START_ALL_SERVICES.sh"
end tell
