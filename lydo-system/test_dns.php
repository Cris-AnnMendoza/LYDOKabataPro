<?php
/**
 * Test DNS Resolution and Network Connectivity to Supabase
 */
?>
<!DOCTYPE html>
<html>
<head>
<title>DNS & Network Test</title>
<style>
body { font-family: Arial; max-width: 800px; margin: 40px auto; padding: 20px; }
.test { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
.success { background: #e8f5e9; color: #2e7d32; }
.error { background: #ffebee; color: #c62828; }
.info { background: #e3f2fd; color: #1565c0; }
h2 { color: #1565c0; }
code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
</style>
</head>
<body>

<h1>🔍 Supabase DNS & Network Test</h1>

<?php
$host = 'db.wbwiatdjfhviguxzyxue.supabase.co';
$port = 5432;

echo "<h2>Test 1: DNS Resolution</h2>";
$ip = gethostbyname($host);
if ($ip === $host) {
    echo "<div class='test error'>❌ <strong>FAILED:</strong> Cannot resolve hostname <code>$host</code><br>";
    echo "This means your computer cannot find the IP address for this domain.</div>";
    
    echo "<h3>Possible Solutions:</h3>";
    echo "<div class='test info'>";
    echo "<strong>1. Check Internet Connection:</strong><br>";
    echo "- Make sure you're connected to the internet<br>";
    echo "- Try opening https://google.com in your browser<br><br>";
    
    echo "<strong>2. Try Google DNS:</strong><br>";
    echo "- Open Command Prompt as Administrator<br>";
    echo "- Run: <code>netsh interface ip set dns \"Wi-Fi\" static 8.8.8.8</code><br>";
    echo "- Run: <code>netsh interface ip add dns \"Wi-Fi\" 8.8.4.4 index=2</code><br>";
    echo "- Replace \"Wi-Fi\" with your network adapter name<br><br>";
    
    echo "<strong>3. Flush DNS Cache:</strong><br>";
    echo "- Open Command Prompt as Administrator<br>";
    echo "- Run: <code>ipconfig /flushdns</code><br><br>";
    
    echo "<strong>4. Check Hosts File:</strong><br>";
    echo "- Open: <code>C:\\Windows\\System32\\drivers\\etc\\hosts</code><br>";
    echo "- Make sure there's no entry blocking supabase.co<br><br>";
    
    echo "<strong>5. Disable VPN/Proxy:</strong><br>";
    echo "- If you're using a VPN or proxy, try disabling it<br>";
    echo "</div>";
    
} else {
    echo "<div class='test success'>✅ <strong>SUCCESS:</strong> Resolved <code>$host</code> to <code>$ip</code></div>";
    
    echo "<h2>Test 2: Port Connectivity</h2>";
    $connection = @fsockopen($host, $port, $errno, $errstr, 5);
    
    if ($connection) {
        echo "<div class='test success'>✅ <strong>SUCCESS:</strong> Can connect to $host:$port</div>";
        fclose($connection);
        
        echo "<h2>Test 3: PostgreSQL Connection</h2>";
        require_once 'shared/supabase_config.php';
        
        try {
            $pdo = db();
            $version = $pdo->query("SELECT version()")->fetchColumn();
            echo "<div class='test success'>✅ <strong>SUCCESS:</strong> Connected to PostgreSQL!<br>";
            echo "<small>Version: " . substr($version, 0, 100) . "...</small></div>";
            
            echo "<h2>✅ All Tests Passed!</h2>";
            echo "<p><a href='shared/youth/qr_scanner.php' style='display:inline-block;padding:10px 20px;background:#1565c0;color:white;text-decoration:none;border-radius:5px;'>Go to QR Scanner</a></p>";
            
        } catch (PDOException $e) {
            echo "<div class='test error'>❌ <strong>FAILED:</strong> " . $e->getMessage() . "</div>";
            echo "<div class='test info'>";
            echo "<strong>The connection works but authentication failed.</strong><br><br>";
            echo "Check your password in <code>shared/supabase_config.php</code><br>";
            echo "Current password: <code>" . str_repeat('*', strlen(SUPABASE_DB_PASS)) . "</code><br><br>";
            echo "To reset your password:<br>";
            echo "1. Go to https://app.supabase.com<br>";
            echo "2. Settings → Database<br>";
            echo "3. Click 'Reset database password'<br>";
            echo "</div>";
        }
        
    } else {
        echo "<div class='test error'>❌ <strong>FAILED:</strong> Cannot connect to port $port<br>";
        echo "Error: $errstr ($errno)</div>";
        
        echo "<div class='test info'>";
        echo "<strong>Possible Solutions:</strong><br>";
        echo "1. <strong>Firewall Blocking:</strong> Check Windows Firewall or antivirus<br>";
        echo "2. <strong>Network Restrictions:</strong> Your network might block PostgreSQL port 5432<br>";
        echo "3. <strong>ISP Restrictions:</strong> Some ISPs block database ports<br><br>";
        
        echo "<strong>Try Alternative Port (Connection Pooler):</strong><br>";
        echo "Port 6543 might work better. Update in supabase_config.php:<br>";
        echo "<code>define('SUPABASE_DB_PORT', '6543');</code><br>";
        echo "<code>define('SUPABASE_DB_USER', 'postgres.wbwiatdjfhviguxzyxue');</code><br>";
        echo "</div>";
    }
}

echo "<h2>📊 System Information</h2>";
echo "<div class='test'>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
echo "<strong>PDO PostgreSQL:</strong> " . (extension_loaded('pdo_pgsql') ? '✅ Installed' : '❌ Not Installed') . "<br>";
echo "<strong>OpenSSL:</strong> " . (extension_loaded('openssl') ? '✅ Enabled' : '❌ Disabled') . "<br>";
echo "<strong>DNS Server:</strong> " . (function_exists('dns_get_record') ? 'Available' : 'Not Available') . "<br>";
echo "</div>";

echo "<h2>🔄 Quick Actions</h2>";
echo "<p>";
echo "<a href='test_supabase_connection.php' style='display:inline-block;padding:10px 20px;background:#1565c0;color:white;text-decoration:none;border-radius:5px;margin:5px;'>Test Full Connection</a> ";
echo "<a href='javascript:location.reload()' style='display:inline-block;padding:10px 20px;background:#757575;color:white;text-decoration:none;border-radius:5px;margin:5px;'>Refresh Test</a>";
echo "</p>";

?>

</body>
</html>
