@echo off
REM ===============================
REM STARTING PORNFREE SYSTEM (Windows)
REM ===============================
echo ===============================
echo STARTING PORNFREE SYSTEM
echo ===============================

REM Start XAMPP services
cd C:\xampp
start xampp_start.exe

REM Wait for Apache & MySQL to be ready
echo Waiting for XAMPP services to start...
timeout /t 15 /nobreak

REM Start DNS Whitelist Server (port 53) in new window
echo Starting DNS Whitelist Server...
cd C:\xampp\htdocs\44

REM Check if Python is available
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python not found!
    echo Please install Python and add it to PATH
    pause
    exit /b 1
)

REM Check if requests library is installed
python -c "import requests" >nul 2>&1
if errorlevel 1 (
    echo Installing requests library...
    python -m pip install requests
)

REM Start DNS server in new window (so it stays running)
start cmd /k python dns_whitelist_server.py

echo.
echo ===============================
echo SYSTEM STARTED!
echo ===============================
echo XAMPP: Running
echo DNS Server: Starting in new window...
echo.
echo NOTE: Keep DNS server window open!
echo.
pause
