@echo off
echo ===================================
echo Quick Fix for NDK Issue
echo ===================================
echo.

echo Option 1: Install NDK via Android Studio
echo ------------------------------------------
echo 1. Open Android Studio
echo 2. Go to: Tools ^> SDK Manager
echo 3. Select "SDK Tools" tab
echo 4. Check "NDK (Side by side)" version 28.2.13676358
echo 5. Click "Apply" to install
echo.

echo Option 2: Use Flutter Doctor to fix
echo -------------------------------------
echo.

set JAVA_HOME=C:\Program Files\Java\jdk-17
set PATH=%JAVA_HOME%\bin;%PATH%

flutter doctor --android-licenses
echo.

echo Option 3: Disable NDK requirement (Quick workaround)
echo -----------------------------------------------------
echo The build.gradle.kts has been updated to skip NDK version check.
echo.

echo ===================================
echo Choose what to do:
echo ===================================
echo.
echo Press 1: Try to run flutter (NDK commented out)
echo Press 2: Open Android Studio SDK Manager
echo Press 3: Exit
echo.

choice /c 123 /n /m "Your choice: "

if errorlevel 3 goto :end
if errorlevel 2 goto :studio
if errorlevel 1 goto :run

:run
echo.
echo Running Flutter...
flutter clean
flutter pub get
flutter run
goto :end

:studio
echo Opening Android Studio...
echo Please install NDK 28.2.13676358 from SDK Tools
start "" "C:\Program Files\Android\Android Studio\bin\studio64.exe" 2>nul
if errorlevel 1 (
    echo Android Studio not found in default location.
    echo Please open it manually and install NDK.
)
goto :end

:end
echo.
pause
