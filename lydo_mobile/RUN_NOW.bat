@echo off
echo ===================================
echo Quick Run - AGP 9+ Fixed!
echo ===================================
echo.

set JAVA_HOME=C:\Program Files\Java\jdk-17
set PATH=%JAVA_HOME%\bin;%PATH%

echo Killing Gradle...
taskkill /F /IM java.exe >nul 2>&1
timeout /t 2 /nobreak >nul

echo Cleaning...
call flutter clean

echo Getting dependencies...
call flutter pub get

echo.
echo Running app...
echo.
call flutter run

pause
