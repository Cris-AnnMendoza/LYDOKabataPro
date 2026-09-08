@echo off
echo ================================================
echo XAMPP HTTPS Setup for QR Scanner Camera Access
echo ================================================
echo.
echo This script will help you enable HTTPS on XAMPP
echo so the camera works in the QR scanner.
echo.
echo PREREQUISITES:
echo - XAMPP must be installed at C:\xampp
echo - Run this script as Administrator
echo.
pause

echo.
echo Step 1: Checking XAMPP installation...
if not exist "C:\xampp\apache\bin\openssl.exe" (
    echo ERROR: XAMPP not found at C:\xampp
    echo Please install XAMPP or update the path in this script
    pause
    exit /b 1
)
echo XAMPP found!

echo.
echo Step 2: Generating SSL certificate...
echo.
echo You will be asked several questions. Use these answers:
echo - Country: PH
echo - State: Laguna  
echo - City: Sta. Cruz
echo - Organization: LYDO
echo - Common Name: 192.168.1.3 (or your local IP)
echo - Email: (your email)
echo.
pause

cd C:\xampp\apache\bin

openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout server.key -out server.crt

if errorlevel 1 (
    echo ERROR: Failed to generate certificate
    pause
    exit /b 1
)

echo.
echo Step 3: Moving certificates to XAMPP folders...
move /Y server.key C:\xampp\apache\conf\ssl.key\server.key
move /Y server.crt C:\xampp\apache\conf\ssl.crt\server.crt

echo.
echo Step 4: Certificates created successfully!
echo.
echo NEXT STEPS (MANUAL):
echo.
echo 1. Open C:\xampp\apache\conf\httpd.conf
echo    - Find and uncomment (remove #):
echo      LoadModule ssl_module modules/mod_ssl.so
echo      Include conf/extra/httpd-ssl.conf
echo.
echo 2. Open C:\xampp\apache\conf\extra\httpd-ssl.conf
echo    - Update ServerName to: 192.168.1.3:443
echo    - Verify certificate paths are correct
echo.
echo 3. Restart Apache in XAMPP Control Panel
echo.
echo 4. Access your site at: https://192.168.1.3/LYDO/lydo-system/
echo    (Click 'Advanced' and 'Proceed' when you see security warning)
echo.
echo 5. Test QR Scanner - camera should now work!
echo.
pause
