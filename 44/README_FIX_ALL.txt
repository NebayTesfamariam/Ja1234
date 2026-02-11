🚀 FIX ALL - Apache, MySQL, DNS Server
========================================

PROBLEEM: Alles draait niet
- ❌ Apache → ERR_CONNECTION_REFUSED
- ❌ MySQL → Database werkt niet
- ❌ DNS Server → Porn blokkering werkt niet

OPLOSSING: Double-click FIX_EVERYTHING.command

---

METHODE 1: DOUBLE-CLICK (MEEST EENVOUDIG)
------------------------------------------
1. Open Finder
2. Ga naar: /Applications/XAMPP/xamppfiles/htdocs/44
3. Double-click op: FIX_EVERYTHING.command

Terminal opent automatisch en vraagt om je wachtwoord!

---

METHODE 2: TERMINAL COMMANDO
-----------------------------
Open Terminal en voer uit:

cd /Applications/XAMPP/xamppfiles/htdocs/44
./FIX_EVERYTHING.command

Je wordt meerdere keren gevraagd om je wachtwoord.

---

WAT HET DOET
------------
1. ✅ Start MySQL (als niet actief)
2. ✅ Start Apache (als niet actief)
3. ✅ Start DNS Server (als niet actief)
4. ✅ Verifieert dat alles draait

---

VERIFICATIE
-----------
Na starten, check:

pgrep -x mysqld && echo "✅ MySQL" || echo "❌ MySQL"
pgrep -x httpd && echo "✅ Apache" || echo "❌ Apache"
pgrep -f dns_whitelist_server && echo "✅ DNS" || echo "❌ DNS"

---

TEST
----
Website: http://localhost/44/
API: http://localhost/44/api/health.php

---

BELANGRIJK
----------
✅ Alle services MOETEN blijven draaien
✅ MySQL is nodig voor database
✅ Apache is nodig voor website
✅ DNS Server is nodig voor porn blokkering
