@echo off
echo ===================================
echo Restarting Gradle with Java 17
echo ===================================
echo.

echo Killing all Java/Gradle processes...
taskkill /F /IM java.exe 2>nul
echo Done!
echo.

echo Waiting 3 seconds...
timeout /t 3 /nobreak >nul

echo Now try running: flutter run
echo.
pause
