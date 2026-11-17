@echo off
rem WorldCup Universe - stop the local website (and the project's own MariaDB unless -KeepDb is given).
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\local\stop-local.ps1" %*
set "RC=%ERRORLEVEL%"
pause
exit /b %RC%
