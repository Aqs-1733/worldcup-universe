@echo off
rem WorldCup Universe - see scripts\local\restore-local.ps1 for options.
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\local\restore-local.ps1" %*
set "RC=%ERRORLEVEL%"
pause
exit /b %RC%
