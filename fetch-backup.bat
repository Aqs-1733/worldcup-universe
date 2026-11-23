@echo off
rem 文件作用：Windows 下载服务器加密备份。
rem 作者：蒋林瀞_2413507
rem WorldCup Universe - see scripts\local\fetch-backup.ps1 for options.
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\local\fetch-backup.ps1" %*
set "RC=%ERRORLEVEL%"
pause
exit /b %RC%
