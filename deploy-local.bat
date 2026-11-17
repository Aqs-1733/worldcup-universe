@echo off
rem WorldCup Universe - one-click local deployment and start (Windows). Double-click this file.
rem Options are passed through, e.g.:  deploy-local.bat -Port 8090   /   deploy-local.bat -Php D:\php82\php.exe
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\local\deploy-local.ps1" %*
set "RC=%ERRORLEVEL%"
echo.
if not "%RC%"=="0" echo Deployment FAILED (exit code %RC%). Read the message above, fix it, then run deploy-local.bat again.
pause
exit /b %RC%
