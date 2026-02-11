@echo off
REM ===============================
REM STARTING DNS WHITELIST SERVER (Windows)
REM ===============================
echo ===============================
echo STARTING DNS WHITELIST SERVER
echo ===============================

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

REM Start DNS Whitelist Server
echo Starting DNS Whitelist Server on port 53...
echo NOTE: This requires Administrator privileges!
echo.
python dns_whitelist_server.py

pause
