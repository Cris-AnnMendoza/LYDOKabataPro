@echo off
color 0C
echo.
echo ╔═══════════════════════════════════════╗
echo ║       NUCLEAR OPTION - LAST RESORT    ║
echo ╚═══════════════════════════════════════╝
echo.
echo This will DELETE everything and rebuild!
echo.
pause

set JAVA_HOME=C:\Program Files\Java\jdk-17
set PATH=%JAVA_HOME%\bin;%PATH%

color 0E
echo.
echo [1] Killing ALL processes...
taskkill /F /IM java.exe >nul 2>&1
taskkill /F /IM gradle.exe >nul 2>&1
taskkill /F /IM dart.exe >nul 2>&1
taskkill /F /IM adb.exe >nul 2>&1
echo Done!
timeout /t 3 /nobreak >nul

echo.
echo [2] Deleting LOCAL build folders...
if exist "build" rmdir /s /q "build"
if exist ".dart_tool" rmdir /s /q ".dart_tool"
if exist "android\build" rmdir /s /q "android\build"
if exist "android\app\build" rmdir /s /q "android\app\build"
if exist "android\.gradle" rmdir /s /q "android\.gradle"
if exist ".gradle" rmdir /s /q ".gradle"
echo Done!

echo.
echo [3] Deleting GLOBAL Gradle cache...
if exist "%USERPROFILE%\.gradle" (
    echo Deleting %USERPROFILE%\.gradle...
    rmdir /s /q "%USERPROFILE%\.gradle"
    echo Done!
)

echo.
echo [4] Deleting Flutter cache...
if exist "%LOCALAPPDATA%\Pub\Cache" (
    echo Deleting Pub cache...
    rmdir /s /q "%LOCALAPPDATA%\Pub\Cache"
)
echo Done!

echo.
echo [5] Wait 5 seconds...
timeout /t 5 /nobreak >nul

echo.
echo [6] Flutter clean...
call flutter clean

echo.
echo [7] Flutter pub get...
call flutter pub get

echo.
echo [8] Accept licenses...
call flutter doctor --android-licenses

echo.
echo ╔═══════════════════════════════════════╗
echo ║           TRYING TO RUN NOW           ║
echo ╚═══════════════════════════════════════╝
echo.

call flutter run

if errorlevel 1 (
    color 0C
    echo.
    echo ╔═══════════════════════════════════════╗
    echo ║            STILL FAILED?              ║
    echo ╚═══════════════════════════════════════╝
    echo.
    echo Last options:
    echo 1. RESTART YOUR PC
    echo 2. Update Flutter: flutter upgrade
    echo 3. Check doctor: flutter doctor -v
    echo.
) else (
    color 0A
    echo.
    echo ╔═══════════════════════════════════════╗
    echo ║           SUCCESS! ✓                  ║
    echo ╚═══════════════════════════════════════╝
)

pause
