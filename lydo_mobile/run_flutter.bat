@echo off
echo ===================================
echo Setting up Java 17 Environment
echo ===================================
echo.

REM Set JAVA_HOME for this session
set JAVA_HOME=C:\Program Files\Java\jdk-17
set PATH=%JAVA_HOME%\bin;%PATH%

echo JAVA_HOME set to: %JAVA_HOME%
echo.

REM Verify Java version
echo Checking Java version...
"%JAVA_HOME%\bin\java.exe" -version
echo.

echo ===================================
echo Killing all Gradle processes...
echo ===================================
taskkill /F /IM java.exe 2>nul
timeout /t 2 /nobreak >nul
echo Done!
echo.

echo ===================================
echo Stopping Gradle daemon...
echo ===================================
cd android
call gradlew.bat --stop
timeout /t 2 /nobreak >nul
cd ..
echo Done!
echo.

echo ===================================
echo Cleaning project...
echo ===================================
call flutter clean
echo Done!
echo.

echo ===================================
echo Getting dependencies...
echo ===================================
call flutter pub get
echo Done!
echo.

echo ===================================
echo Starting Flutter app...
echo ===================================
call flutter run
