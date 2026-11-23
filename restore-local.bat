@echo off
rem 文件作用：Windows 把备份恢复到本地数据库。
rem 作者：蒋林瀞_2413507
rem WorldCup Universe - see scripts\local\restore-local.ps1 for options.
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\local\restore-local.ps1" %*
set "RC=%ERRORLEVEL%"
pause
exit /b %RC%
