@echo off
REM Genera deploy\public_html.zip y deploy\royal-vault.zip para subir a Hostinger.
cd /d "%~dp0"
php tools\build-deploy.php
echo.
pause
