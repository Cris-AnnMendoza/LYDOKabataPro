@echo off
echo ===================================
echo Fixing Gradle JVM Configuration
echo ===================================
echo.

echo Step 1: Stopping Gradle daemon...
cd android
call gradlew --stop
cd ..
echo Done!
echo.

echo Step 2: Cleaning Flutter project...
call flutter clean
echo Done!
echo.

echo Step 3: Getting Flutter dependencies...
call flutter pub get
echo Done!
echo.

echo Step 4: Cleaning Android build...
cd android
call gradlew clean
cd ..
echo Done!
echo.

echo ===================================
echo Build fixed! Now you can run:
echo   flutter run
echo ===================================
pause
