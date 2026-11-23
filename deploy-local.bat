@echo off
rem 文件作用：Windows 一键本地部署：双击即可建库、初始化数据并启动网站。
rem 作者：蒋林瀞_2413507
rem WorldCup Universe - one-click local deployment and start (Windows). Double-click this file.
rem Options are passed through, e.g.:  deploy-local.bat -Port 8090   /   deploy-local.bat -Php D:\php82\php.exe
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\local\deploy-local.ps1" %*
set "RC=%ERRORLEVEL%"
echo.
if not "%RC%"=="0" echo Deployment FAILED (exit code %RC%). Read the message above, fix it, then run deploy-local.bat again.
pause
exit /b %RC%
