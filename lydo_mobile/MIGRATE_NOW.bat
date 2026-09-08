@echo off
echo ===================================
echo Running Flutter Migration
echo ===================================
echo.

set JAVA_HOME=C:\Program Files\Java\jdk-17
set PATH=%JAVA_HOME%\bin;%PATH%

echo Step 1: Killing processes...
taskkill /F /IM java.exe >nul 2>&1
timeout /t 2 /nobreak >nul

echo Step 2: Cleaning...
call flutter clean

echo Step 3: Running analyze with suggestions...
echo.
call flutter analyze --suggestions

echo.
echo Step 4: Try to run...
echo.
call flutter run

pause
