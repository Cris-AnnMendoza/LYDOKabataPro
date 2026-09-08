@echo off
echo ===================================
echo Installing Android SDK Components
echo ===================================
echo.

set ANDROID_SDK=C:\Users\User\AppData\Local\Android\sdk
set SDKMANAGER=%ANDROID_SDK%\cmdline-tools\latest\bin\sdkmanager.bat

echo Checking for SDK Manager...
if not exist "%SDKMANAGER%" (
    echo SDK Manager not found in cmdline-tools\latest
    echo Trying alternative location...
    set SDKMANAGER=%ANDROID_SDK%\tools\bin\sdkmanager.bat
)

if not exist "%SDKMANAGER%" (
    echo ERROR: SDK Manager not found!
    echo Please install Android Command Line Tools from Android Studio.
    echo.
    echo Or download from: https://developer.android.com/studio#command-tools
    pause
    exit /b 1
)

echo Found SDK Manager at: %SDKMANAGER%
echo.

echo Installing required packages...
echo This may take several minutes...
echo.

REM Accept licenses
echo y | call "%SDKMANAGER%" --licenses

REM Install required packages
call "%SDKMANAGER%" "ndk;28.2.13676358"
call "%SDKMANAGER%" "build-tools;34.0.0"
call "%SDKMANAGER%" "platform-tools"
call "%SDKMANAGER%" "platforms;android-34"

echo.
echo ===================================
echo Installation complete!
echo ===================================
echo.
echo Now you can run: flutter run
pause
