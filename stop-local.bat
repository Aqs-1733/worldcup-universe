@echo off
rem 文件作用：Windows 停止本地网站和数据库。
rem 作者：蒋林瀞_2413507
rem WorldCup Universe - stop the local website (and the project's own MariaDB unless -KeepDb is given).
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\local\stop-local.ps1" %*
set "RC=%ERRORLEVEL%"
pause
exit /b %RC%
