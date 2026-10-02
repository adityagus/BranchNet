@echo off
setlocal
cd /d "%~dp0"
php agent.php config.json
pause
