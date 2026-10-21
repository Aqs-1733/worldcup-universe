@echo off
setlocal
cd /d "%~dp0"
if defined PHP_BINARY (set "PHP=%PHP_BINARY%") else if exist "D:\XAMPP\php\php.exe" (set "PHP=D:\XAMPP\php\php.exe") else (set "PHP=php")
set "WCU_PHP=%PHP%"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\start_news_worker.ps1" -Interval 300
echo WorldCup Universe - Yii2 / MySQL
echo http://127.0.0.1:8080
"%PHP%" -d display_errors=0 -S 127.0.0.1:8080 -t public public/index.php
endlocal
