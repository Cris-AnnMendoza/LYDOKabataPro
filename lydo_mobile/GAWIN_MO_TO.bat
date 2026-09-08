@echo off
color 0A
echo.
echo ========================================
echo   LYDO MOBILE - FINAL BUILD FIX v2
echo   Converted to Groovy (Simpler!)
echo ========================================
echo.

REM Set Java 17
set JAVA_HOME=C:\Program Files\Java\jdk-17
set PATH=%JAVA_HOME%\bin;%PATH%

echo [1/7] Setting Java 17...
"%JAVA_HOME%\bin\java.exe" -version
echo.

echo [2/7] Killing ALL Java/Gradle processes...
taskkill /F /IM java.exe >nul 2>&1
taskkill /F /IM gradle.exe >nul 2>&1
timeout /t 3 /nobreak >nul
echo Done!
echo.

echo [3/7] Deleting ALL build folders...
if exist "build" rmdir /s /q "build" 2>nul
if exist ".dart_tool" rmdir /s /q ".dart_tool" 2>nul
if exist "android\build" rmdir /s /q "android\build" 2>nul
if exist "android\app\build" rmdir /s /q "android\app\build" 2>nul
if exist "android\.gradle" rmdir /s /q "android\.gradle" 2>nul
if exist "%USERPROFILE%\.gradle\caches" rmdir /s /q "%USERPROFILE%\.gradle\caches" 2>nul
echo Done!
echo.

echo [4/7] Stopping Gradle daemon...
cd android
call gradlew --stop 2>nul
cd ..
echo Done!
echo.

echo [5/7] Running flutter clean...
call flutter clean
echo.

echo [6/7] Getting dependencies...
call flutter pub get
echo.

echo [7/7] Running Flutter app...
echo.
echo ========================================
echo Starting your app...
echo ========================================
echo.

call flutter run

echo.
if errorlevel 1 (
    echo ========================================
    echo BUILD FAILED
    echo ========================================
    echo.
    echo Kung may error pa, subukan:
    echo 1. Isara lahat ng terminal/VS Code
    echo 2. Restart computer
    echo 3. Ulitin: GAWIN_MO_TO.bat
    echo.
) else (
    echo ========================================
    echo BUILD SUCCESS!
    echo ========================================
)

pause
