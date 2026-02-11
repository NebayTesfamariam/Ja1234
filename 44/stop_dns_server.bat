@echo off
REM ===============================
REM STOPPING DNS WHITELIST SERVER (Windows)
REM ===============================
echo ===============================
echo STOPPING DNS WHITELIST SERVER
echo ===============================

REM Stop Python processes running DNS server
taskkill /F /IM python.exe /FI "WINDOWTITLE eq *dns_whitelist_server*" >nul 2>&1
taskkill /F /IM python.exe /FI "COMMANDLINE eq *dns_whitelist_server.py*" >nul 2>&1

REM Also try to stop by process name
for /f "tokens=2" %%a in ('tasklist ^| findstr /i "python.exe"') do (
    wmic process where "ProcessId=%%a" get CommandLine 2>nul | findstr /i "dns_whitelist_server" >nul
    if not errorlevel 1 (
        taskkill /F /PID %%a >nul 2>&1
    )
)

echo DNS Server stopped!
pause
