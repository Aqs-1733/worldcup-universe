@echo off
rem WorldCup Universe - see scripts\local\fetch-backup.ps1 for options.
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\local\fetch-backup.ps1" %*
set "RC=%ERRORLEVEL%"
pause
exit /b %RC%
