# Fix Gradle JVM Configuration
Write-Host "===================================" -ForegroundColor Cyan
Write-Host "Fixing Gradle JVM Configuration" -ForegroundColor Cyan
Write-Host "===================================" -ForegroundColor Cyan
Write-Host ""

# Set JAVA_HOME temporarily for this session
$env:JAVA_HOME = "C:\Program Files\Java\jdk-17"
Write-Host "Setting JAVA_HOME to: $env:JAVA_HOME" -ForegroundColor Green
Write-Host ""

# Verify Java version
Write-Host "Step 0: Verifying Java version..." -ForegroundColor Yellow
& "$env:JAVA_HOME\bin\java.exe" -version
Write-Host ""

# Stop Gradle daemon
Write-Host "Step 1: Stopping Gradle daemon..." -ForegroundColor Yellow
Set-Location android
& .\gradlew.bat --stop
Set-Location ..
Write-Host "Done!" -ForegroundColor Green
Write-Host ""

# Clean Flutter project
Write-Host "Step 2: Cleaning Flutter project..." -ForegroundColor Yellow
flutter clean
Write-Host "Done!" -ForegroundColor Green
Write-Host ""

# Get Flutter dependencies
Write-Host "Step 3: Getting Flutter dependencies..." -ForegroundColor Yellow
flutter pub get
Write-Host "Done!" -ForegroundColor Green
Write-Host ""

# Clean Android build
Write-Host "Step 4: Cleaning Android build..." -ForegroundColor Yellow
Set-Location android
& .\gradlew.bat clean
Set-Location ..
Write-Host "Done!" -ForegroundColor Green
Write-Host ""

Write-Host "===================================" -ForegroundColor Cyan
Write-Host "Build fixed! Now you can run:" -ForegroundColor Green
Write-Host "  flutter run" -ForegroundColor White
Write-Host "===================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
