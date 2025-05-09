@echo off
REM Change to the directory where the PHP files are stored
cd /d "%~dp0\PHP"

REM Start PHP's built-in server on port 8080 in the background
start "" /min php -S localhost:8080 -t .