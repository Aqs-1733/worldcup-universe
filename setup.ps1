# WorldCup Universe: kept for older instructions. The local setup is now deploy-local.bat (scripts/local/deploy-local.ps1),
# which checks PHP/MariaDB, creates .env and the database, runs the Yii2 installer and starts the site. Same options apply.
& (Join-Path $PSScriptRoot 'scripts\local\deploy-local.ps1') @args
exit $LASTEXITCODE
