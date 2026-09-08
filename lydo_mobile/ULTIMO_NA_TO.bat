@echo off
title LYDO Mobile - Ultimate Fix
color 0E
cls

echo.
echo ╔════════════════════════════════════════╗
echo ║   LYDO MOBILE - ULTIMATE CLEAN FIX    ║
echo ║        Groovy Build System v2         ║
echo ╚════════════════════════════════════════╝
echo.
echo ⚠️  WARNING: This will delete ALL build caches!
echo.
pause

REM Set environment
set JAVA_HOME=C:\Program Files\Java\jdk-17
set PATH=%JAVA_HOME%\bin;%PATH%
set ANDROID_SDK=C:\Users\User\AppData\Local\Android\sdk

cls
echo.
echo ════════════════════════════════════════
echo  STEP 1: Environment Setup
echo ════════════════════════════════════════
echo.
echo ✓ JAVA_HOME: %JAVA_HOME%
echo ✓ ANDROID_SDK: %ANDROID_SDK%
echo.
"%JAVA_HOME%\bin\java.exe" -version 2>&1 | findstr "version"
timeout /t 2 /nobreak >nul

echo.
echo ════════════════════════════════════════
echo  STEP 2: Killing ALL Processes
echo ════════════════════════════════════════
echo.
taskkill /F /IM java.exe 2>nul && echo ✓ Killed Java processes || echo ✓ No Java processes running
taskkill /F /IM gradle.exe 2>nul && echo ✓ Killed Gradle processes || echo ✓ No Gradle processes running
taskkill /F /IM dart.exe 2>nul && echo ✓ Killed Dart processes || echo ✓ No Dart processes running
echo.
echo Waiting 5 seconds...
timeout /t 5 /nobreak >nul

echo.
echo ════════════════════════════════════════
echo  STEP 3: Nuclear Clean (Local)
echo ════════════════════════════════════════
echo.
if exist "build" (rmdir /s /q "build" && echo ✓ Deleted build) else echo ✓ No build folder
if exist ".dart_tool" (rmdir /s /q ".dart_tool" && echo ✓ Deleted .dart_tool) else echo ✓ No .dart_tool
if exist "android\build" (rmdir /s /q "android\build" && echo ✓ Deleted android\build) else echo ✓ No android\build
if exist "android\app\build" (rmdir /s /q "android\app\build" && echo ✓ Deleted android\app\build) else echo ✓ No android\app\build
if exist "android\.gradle" (rmdir /s /q "android\.gradle" && echo ✓ Deleted android\.gradle) else echo ✓ No android\.gradle

echo.
echo ════════════════════════════════════════
echo  STEP 4: Nuclear Clean (Global Caches)
echo ════════════════════════════════════════
echo.
if exist "%USERPROFILE%\.gradle\caches" (
    echo Deleting global Gradle caches...
    rmdir /s /q "%USERPROFILE%\.gradle\caches" 2>nul
    echo ✓ Deleted global Gradle caches
) else (
    echo ✓ No global caches to delete
)

if exist "%USERPROFILE%\.gradle\daemon" (
    echo Deleting Gradle daemon...
    rmdir /s /q "%USERPROFILE%\.gradle\daemon" 2>nul
    echo ✓ Deleted Gradle daemon
) else (
    echo ✓ No daemon to delete
)

echo.
echo ════════════════════════════════════════
echo  STEP 5: Flutter Clean
echo ════════════════════════════════════════
echo.
call flutter clean
echo ✓ Flutter clean complete

echo.
echo ════════════════════════════════════════
echo  STEP 6: Flutter Pub Get
echo ════════════════════════════════════════
echo.
call flutter pub get
echo ✓ Dependencies downloaded

echo.
echo ════════════════════════════════════════
echo  STEP 7: Verify Setup
echo ════════════════════════════════════════
echo.
call flutter doctor --android-licenses 2>nul
echo.

echo.
echo ╔════════════════════════════════════════╗
echo ║         CLEANUP COMPLETE! ✓           ║
echo ╚════════════════════════════════════════╝
echo.
echo Now trying to run your app...
echo.
timeout /t 3 /nobreak >nul

echo ════════════════════════════════════════
echo  STEP 8: RUNNING FLUTTER
echo ════════════════════════════════════════
echo.

call flutter run

if errorlevel 1 (
    echo.
    echo ╔════════════════════════════════════════╗
    echo ║          BUILD FAILED ✗               ║
    echo ╚════════════════════════════════════════╝
    echo.
    echo Last resort:
    echo 1. RESTART your computer
    echo 2. Run this script again
    echo.
    echo Or check: flutter doctor -v
    echo.
) else (
    echo.
    echo ╔════════════════════════════════════════╗
    echo ║        BUILD SUCCESS! ✓               ║
    echo ╚════════════════════════════════════════╝
    echo.
)

pause
