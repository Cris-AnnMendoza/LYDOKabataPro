# Quick HTTPS Setup for XAMPP
# Run this as Administrator

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "XAMPP HTTPS Quick Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Create certificate directory
Write-Host "[1/5] Creating certificate directory..." -ForegroundColor Yellow
$certDir = "C:\xampp\apache\cert"
if (!(Test-Path $certDir)) {
    New-Item -ItemType Directory -Path $certDir | Out-Null
    Write-Host "✓ Certificate directory created" -ForegroundColor Green
} else {
    Write-Host "✓ Certificate directory exists" -ForegroundColor Green
}
Write-Host ""

# Generate certificate
Write-Host "[2/5] Generating SSL certificate..." -ForegroundColor Yellow
Write-Host "Using default values for certificate..." -ForegroundColor Gray

$opensslConfig = @"
[req]
default_bits = 2048
prompt = no
default_md = sha256
distinguished_name = dn

[dn]
C=PH
ST=Laguna
L=Sta. Cruz
O=LYDO
CN=192.168.1.3
"@

$opensslConfig | Out-File -FilePath "$certDir\openssl.cnf" -Encoding ASCII

Set-Location $certDir
& "C:\xampp\apache\bin\openssl.exe" req -x509 -nodes -days 365 -newkey rsa:2048 -keyout server.key -out server.crt -config openssl.cnf

if (Test-Path "$certDir\server.key") {
    Write-Host "✓ Certificate generated successfully!" -ForegroundColor Green
} else {
    Write-Host "✗ Certificate generation failed!" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Backup config
Write-Host "[3/5] Backing up Apache config..." -ForegroundColor Yellow
$httpdConf = "C:\xampp\apache\conf\httpd.conf"
$backupPath = "$httpdConf.backup"
if (!(Test-Path $backupPath)) {
    Copy-Item $httpdConf $backupPath
    Write-Host "✓ Backup created" -ForegroundColor Green
} else {
    Write-Host "✓ Backup already exists" -ForegroundColor Green
}
Write-Host ""

# Enable SSL module
Write-Host "[4/5] Enabling SSL module..." -ForegroundColor Yellow
$content = Get-Content $httpdConf
$content = $content -replace '#LoadModule ssl_module', 'LoadModule ssl_module'
$content = $content -replace '#Include conf/extra/httpd-ssl.conf', 'Include conf/extra/httpd-ssl.conf'
$content | Set-Content $httpdConf -Encoding ASCII
Write-Host "✓ SSL module enabled" -ForegroundColor Green
Write-Host ""

# Update SSL config
Write-Host "[5/5] Updating SSL configuration..." -ForegroundColor Yellow
$sslConf = "C:\xampp\apache\conf\extra\httpd-ssl.conf"
$sslContent = Get-Content $sslConf -Raw
$sslContent = $sslContent -replace 'SSLCertificateFile.*', 'SSLCertificateFile "C:/xampp/apache/cert/server.crt"'
$sslContent = $sslContent -replace 'SSLCertificateKeyFile.*', 'SSLCertificateKeyFile "C:/xampp/apache/cert/server.key"'
$sslContent = $sslContent -replace 'ServerName www.example.com:443', 'ServerName 192.168.1.3:443'
$sslContent = $sslContent -replace 'ServerAdmin you@example.com', 'ServerAdmin admin@lydo.gov.ph'
$sslContent | Set-Content $sslConf -Encoding ASCII
Write-Host "✓ SSL configuration updated" -ForegroundColor Green
Write-Host ""

# Add firewall rule
Write-Host "[BONUS] Adding firewall rule..." -ForegroundColor Yellow
try {
    New-NetFirewallRule -DisplayName "Apache HTTPS" -Direction Inbound -LocalPort 443 -Protocol TCP -Action Allow -ErrorAction SilentlyContinue | Out-Null
    Write-Host "✓ Firewall rule added" -ForegroundColor Green
} catch {
    Write-Host "! Firewall rule may already exist" -ForegroundColor Yellow
}
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✓ HTTPS Setup Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "NEXT STEPS:" -ForegroundColor Yellow
Write-Host "1. Open XAMPP Control Panel" -ForegroundColor White
Write-Host "2. Stop Apache (if running)" -ForegroundColor White
Write-Host "3. Start Apache again" -ForegroundColor White
Write-Host "4. Access: https://192.168.1.3/LYDO" -ForegroundColor White
Write-Host ""
Write-Host "On your phone, you'll see a certificate warning." -ForegroundColor Gray
Write-Host "Tap 'Advanced' -> 'Proceed to 192.168.1.3' to continue." -ForegroundColor Gray
Write-Host ""
Write-Host "Camera permissions will now work! 📸" -ForegroundColor Green
Write-Host ""

Read-Host "Press Enter to exit"
