🚨 DNS SERVER FIX - 3 EENVOUDIGE METHODEN
==========================================

PROBLEEM: DNS server draait niet → Pornografische sites kunnen worden geladen

OPLOSSING: Start DNS server met één van deze methoden:

---

METHODE 1: DOUBLE-CLICK (MEEST EENVOUDIG)
------------------------------------------
1. Open Finder
2. Ga naar: /Applications/XAMPP/xamppfiles/htdocs/44
3. Double-click op: START_DNS.command

Terminal opent automatisch en vraagt om je wachtwoord!

---

METHODE 2: TERMINAL COMMANDO (SNELSTE)
--------------------------------------
Open Terminal en voer uit:

cd /Applications/XAMPP/xamppfiles/htdocs/44 && sudo python3 dns_whitelist_server.py

Je wordt gevraagd om je wachtwoord:
- Type je wachtwoord (je ziet niets typen - dat is normaal!)
- Druk ENTER

Laat Terminal open staan!

---

METHODE 3: AUTOMATISCH BIJ BOOT (AANBEVOLEN)
--------------------------------------------
Open Terminal en voer uit:

cd /Applications/XAMPP/xamppfiles/htdocs/44
./install_dns_launchdaemon.sh

Je wordt gevraagd om je wachtwoord.

Na installatie start DNS server automatisch bij elke boot!

---

VERIFICATIE
-----------
Check of DNS server draait:

ps aux | grep dns_whitelist_server | grep -v grep

Moet proces tonen!

---

TEST PORN BLOKKERING
--------------------
1. Verbind met VPN (WireGuard)
2. Probeer pornhub.com te bezoeken
3. Moet NIET laden (domain not found)

---

BELANGRIJK
----------
✅ DNS server MOET blijven draaien
✅ VPN MOET verbonden zijn
✅ Whitelist bepaalt welke sites werken
✅ Pornografische sites zijn ALTIJD geblokkeerd
