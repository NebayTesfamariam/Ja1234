-- Start DNS Server - Double Click to Run
-- This will prompt for your password and start the DNS server

set scriptPath to POSIX path of (path to me)
set projectPath to "/Applications/XAMPP/xamppfiles/htdocs/44"

tell application "Terminal"
    activate
    do script "cd " & quoted form of projectPath & " && echo '🚨 STARTING DNS SERVER...' && echo '' && echo '⚠️  Je wordt gevraagd om je wachtwoord in te voeren' && echo '   (Type je wachtwoord en druk ENTER - je ziet niets typen, dat is normaal!)' && echo '' && sudo python3 dns_whitelist_server.py"
end tell
