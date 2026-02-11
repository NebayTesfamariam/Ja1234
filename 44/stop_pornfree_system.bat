@echo off
REM ===============================
REM STOPPING PORNFREE SYSTEM
REM ===============================
echo ===============================
echo STOPPING PORNFREE SYSTEM
echo ===============================

REM Stop DNS Server
echo Stopping DNS Server...
taskkill /F /IM python.exe /FI "WINDOWTITLE eq dns_whitelist_server.py" 2>nul
taskkill /F /IM python.exe /FI "COMMANDLINE eq *dns_whitelist_server.py*" 2>nul

REM Stop XAMPP services
echo Stopping XAMPP services...
cd C:\xampp
xampp_stop.exe

echo.
echo System stopped!
pause
