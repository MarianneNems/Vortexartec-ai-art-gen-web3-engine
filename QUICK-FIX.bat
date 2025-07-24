@echo off
echo 🔧 Fixing WordPress Config...
echo.

REM Create backup
copy "..\wp-config.php" "..\wp-config-backup-%date:~-4,4%-%date:~-10,2%-%date:~-7,2%-%time:~0,2%-%time:~3,2%-%time:~6,2%.php"
echo ✅ Backup created

REM Copy fixed content
copy "wp-config-FIXED.txt" "..\wp-config.php"
echo ✅ wp-config.php fixed!

echo.
echo 🎉 WordPress staging site should now work without Redis errors!
echo.
pause 