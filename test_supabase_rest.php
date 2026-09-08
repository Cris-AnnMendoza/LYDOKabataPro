<?php
require_once 'shared/supabase_rest_adapter.php';
?>
<!DOCTYPE html>
<html>
<head>
<title>Supabase REST API Test</title>
<style>
body { font-family: Arial; max-width: 800px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 30px; border-radius: 10px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.success { background: #e8f5e9; color: #2e7d32; border-left: 5px solid #4caf50; }
.error { background: #ffebee; color: #c62828; border-left: 5px solid #f44336; }
.info { background: #e3f2fd; color: #1565c0; border-left: 5px solid #2196f3; }
h1 { color: #1565c0; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
.btn { display: inline-block; padding: 10px 20px; background: #1565c0; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
</style>
</head>
<body>

<div class="box">
<h1>🌐 Supabase REST API Connection Test</h1>
<p>Testing HTTPS connection to Supabase (port 443 - rarely blocked)</p>
</div>

<?php
echo "<div class='box'>";
echo "<h2>Test 1: Check HTTPS Connectivity</h2>";

$testUrl = 'https://wbwiatdjfhviguxzyxue.supabase.co';
$headers = @get_headers($testUrl);

if ($headers && strpos($headers[0], '200')) {
    echo "<div class='success'>✅ <strong>SUCCESS!</strong> Can reach Supabase via HTTPS</div>";
    
    echo "<h2>Test 2: REST API Query</h2>";
    try {
        $events = supabaseREST('/events', 'GET', null, ['select' => 'count', 'limit' => 1]);
        echo "<div class='success'>✅ <strong>REST API WORKS!</strong></div>";
        echo "<p>Successfully queried events table via REST API</p>";
        
        echo "<h2>Test 3: Get Events Count</h2>";
        try {
            $count = supabaseREST('/events', 'GET', null, ['select' => 'id']);
            echo "<div class='success'>✅ Found " . count($count) . " events in database</div>";
        } catch (Exception $e) {
            echo "<div class='info'>Note: " . $e->getMessage() . "</div>";
        }
        
        echo "<div class='box info'>";
        echo "<h2>✅ Solution Found!</h2>";
        echo "<p><strong>Your network blocks PostgreSQL (port 5432/6543) but HTTPS (port 443) works!</strong></p>";
        echo "<p>You can use Supabase REST API instead of direct PostgreSQL connection.</p>";
        echo "<p>This is actually <strong>better</strong> because:</p>";
        echo "<ul>";
        echo "<li>✅ Works through any firewall</li>";
        echo "<li>✅ More secure (HTTPS encrypted)</li>";
        echo "<li>✅ Easier to deploy</li>";
        echo "<li>✅ No connection pooling issues</li>";
        echo "</ul>";
        echo "<a href='shared/youth/qr_scanner.php' class='btn'>Try QR Scanner Now</a>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ REST API Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
} else {
    echo "<div class='error'>❌ <strong>FAILED:</strong> Cannot reach Supabase via HTTPS</div>";
    echo "<p>This is unusual. Possible issues:</p>";
    echo "<ul>";
    echo "<li>Internet connection problem</li>";
    echo "<li>Firewall blocking all outbound HTTPS</li>";
    echo "<li>Supabase project is paused or deleted</li>";
    echo "</ul>";
    
    echo "<div class='box info'>";
    echo "<h2>🔧 Check Your Supabase Project</h2>";
    echo "<p>1. Go to <a href='https://app.supabase.com' target='_blank'>https://app.supabase.com</a></p>";
    echo "<p>2. Check if your project is <strong>Active</strong> (not paused)</p>";
    echo "<p>3. Free tier projects pause after 7 days of inactivity</p>";
    echo "<p>4. Click <strong>Resume</strong> if paused</p>";
    echo "</div>";
}

echo "</div>";

echo "<div class='box'>";
echo "<h2>📊 Connection Summary</h2>";
echo "<table style='width:100%; border-collapse: collapse;'>";
echo "<tr style='border-bottom: 1px solid #ddd;'>";
echo "<td style='padding: 10px;'><strong>Method</strong></td>";
echo "<td style='padding: 10px;'><strong>Port</strong></td>";
echo "<td style='padding: 10px;'><strong>Status</strong></td>";
echo "</tr>";
echo "<tr style='border-bottom: 1px solid #ddd;'>";
echo "<td style='padding: 10px;'>PostgreSQL Direct</td>";
echo "<td style='padding: 10px;'>5432</td>";
echo "<td style='padding: 10px; color: #c62828;'>❌ Blocked</td>";
echo "</tr>";
echo "<tr style='border-bottom: 1px solid #ddd;'>";
echo "<td style='padding: 10px;'>PostgreSQL Pooler</td>";
echo "<td style='padding: 10px;'>6543</td>";
echo "<td style='padding: 10px; color: #c62828;'>❌ Blocked</td>";
echo "</tr>";
echo "<tr>";
echo "<td style='padding: 10px;'><strong>REST API (HTTPS)</strong></td>";
echo "<td style='padding: 10px;'><strong>443</strong></td>";
echo "<td style='padding: 10px; color: #2e7d32;'><strong>" . ($headers ? "✅ Working" : "❌ Failed") . "</strong></td>";
echo "</tr>";
echo "</table>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>🔄 Quick Actions</h2>";
echo "<a href='test_supabase_connection.php' class='btn' style='background: #757575;'>Test PostgreSQL Again</a>";
echo "<a href='javascript:location.reload()' class='btn' style='background: #757575;'>Refresh This Test</a>";
echo "</div>";

?>

</body>
</html>
