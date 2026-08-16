@echo off
TITLE Universe Civilization: Empire at Wars - Game Server
echo ========================================================
echo Starting Universe Civilization: Empire at Wars Server...
echo ========================================================
cd /d "%~dp0\.."
echo Server running on http://localhost:8080
php -S localhost:8080
pause
