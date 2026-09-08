<?php
require_once 'shared/supabase_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supabase Connection Test</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}
.container {
    background: white;
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}
h1 {
    color: #1565c0;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.status {
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    font-size: 16px;
    font-weight: 600;
}
.status.success {
    background: #e8f5e9;
    color: #2e7d32;
    border: 2px solid #4caf50;
}
.status.error {
    background: #ffebee;
    color: #c62828;
    border: 2px solid #f44336;
}
.info-box {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
    border-left: 4px solid #1565c0;
}
.info-box strong {
    display: inline-block;
    width: 120px;
    color: #1565c0;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}
th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}
th {
    background: #f5f5f5;
    font-weight: 700;
    color: #1565c0;
}
.btn {
    display: inline-block;
    padding: 12px 24px;
    background: #1565c0;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    margin: 10px 5px;
}
.btn:hover {
    background: #0d47a1;
}
.btn-secondary {
    background: #757575;
}
.btn-secondary:hover {
    background: #616161;
}
.icon {
    font-size: 24px;
}
</style>
</head>
<body>

<div class="container">
    <h1><span class="icon">🔌</span> Supabase Connection Test</h1>
    <p style="color: #666; margin-bottom: 30px;">Testing connection to your Supabase PostgreSQL database</p>
    
    <?php
    $connected = false;
    $errorMsg = '';
    $tables = [];
    $stats = [];
    
    try {
        // Test connection
        $pdo = db();
        $connected = true;
        
        // Get database version
        $version = $pdo->query("SELECT version()")->fetchColumn();
        
        // Get table count
        $tableCountResult = $pdo->query("
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = 'public'
        ");
        $tableCount = $tableCountResult->fetchColumn();
        
        // Get some stats
        $stats = [
            'events' => $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn(),
            'youth_users' => $pdo->query("SELECT COUNT(*) FROM youth_users")->fetchColumn(),
            'organizations' => $pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn(),
            'event_checkins' => $pdo->query("SELECT COUNT(*) FROM event_checkins")->fetchColumn(),
        ];
        
        // List some tables
        $tablesResult = $pdo->query("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            ORDER BY table_name 
            LIMIT 10
        ");
        $tables = $tablesResult->fetchAll(PDO::FETCH_COLUMN);
        
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
    }
    
    if ($connected):
    ?>
        <div class="status success">
            <span style="font-size: 30px;">✅</span>
            <strong>SUCCESS!</strong> Connected to Supabase PostgreSQL database
        </div>
        
        <h2>📊 Database Statistics</h2>
        <table>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
            <tr>
                <td><strong>Total Tables</strong></td>
                <td><?= $tableCount ?></td>
            </tr>
            <tr>
                <td><strong>Events</strong></td>
                <td><?= number_format($stats['events']) ?></td>
            </tr>
            <tr>
                <td><strong>Youth Users</strong></td>
                <td><?= number_format($stats['youth_users']) ?></td>
            </tr>
            <tr>
                <td><strong>Organizations</strong></td>
                <td><?= number_format($stats['organizations']) ?></td>
            </tr>
            <tr>
                <td><strong>Event Check-ins</strong></td>
                <td><?= number_format($stats['event_checkins']) ?></td>
            </tr>
        </table>
        
        <h2>🗂️ Sample Tables (First 10)</h2>
        <div class="info-box">
            <?php foreach($tables as $table): ?>
                <div style="padding: 5px 0;">📄 <?= htmlspecialchars($table) ?></div>
            <?php endforeach; ?>
        </div>
        
        <h2>⚙️ Connection Details</h2>
        <div class="info-box">
            <div><strong>Host:</strong> <?= SUPABASE_DB_HOST ?></div>
            <div><strong>Port:</strong> <?= SUPABASE_DB_PORT ?></div>
            <div><strong>Database:</strong> <?= SUPABASE_DB_NAME ?></div>
            <div><strong>User:</strong> <?= SUPABASE_DB_USER ?></div>
            <div><strong>Connection Type:</strong> <?= SUPABASE_DB_PORT == '6543' ? 'Connection Pooler (Recommended)' : 'Direct Connection' ?></div>
        </div>
        
        <h2>🔧 Database Version</h2>
        <div class="info-box" style="font-size: 12px; font-family: monospace;">
            <?= htmlspecialchars(substr($version, 0, 200)) ?>...
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="shared/youth/qr_scanner.php" class="btn">✨ Try QR Scanner</a>
            <a href="shared/youth/events.php" class="btn">📅 View Events</a>
            <a href="admin2/" class="btn btn-secondary">👨‍💼 Admin Panel</a>
        </div>
        
    <?php else: ?>
        
        <div class="status error">
            <span style="font-size: 30px;">❌</span>
            <strong>CONNECTION FAILED!</strong> Could not connect to Supabase
        </div>
        
        <h2>🔍 Error Details</h2>
        <div class="info-box" style="border-left-color: #c62828; background: #ffebee;">
            <strong style="color: #c62828;">Error:</strong><br>
            <div style="margin-top: 10px; font-family: monospace; font-size: 14px; color: #c62828;">
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        </div>
        
        <h2>⚙️ Current Configuration</h2>
        <div class="info-box">
            <div><strong>Host:</strong> <?= SUPABASE_DB_HOST ?></div>
            <div><strong>Port:</strong> <?= SUPABASE_DB_PORT ?></div>
            <div><strong>Database:</strong> <?= SUPABASE_DB_NAME ?></div>
            <div><strong>User:</strong> <?= SUPABASE_DB_USER ?></div>
        </div>
        
        <h2>🔧 How to Fix</h2>
        <div class="info-box">
            <h3>Step 1: Go to Supabase Dashboard</h3>
            <ol style="line-height: 2;">
                <li>Open <a href="https://app.supabase.com" target="_blank">https://app.supabase.com</a></li>
                <li>Select your project</li>
                <li>Click <strong>Settings</strong> → <strong>Database</strong></li>
            </ol>
            
            <h3>Step 2: Get Connection Details</h3>
            <p>Look for the <strong>"Connection string"</strong> or <strong>"Connection pooling"</strong> section</p>
            <p>Copy the values for:</p>
            <ul>
                <li><strong>Host</strong> - e.g., aws-0-ap-southeast-1.pooler.supabase.com</li>
                <li><strong>Port</strong> - 6543 (pooler) or 5432 (direct)</li>
                <li><strong>User</strong> - postgres.YOUR_PROJECT_REF (for pooler) or postgres (for direct)</li>
                <li><strong>Password</strong> - Your database password</li>
            </ul>
            
            <h3>Step 3: Update supabase_config.php</h3>
            <p>Edit: <code>shared/supabase_config.php</code></p>
            <p>Update these lines:</p>
            <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;">
define('SUPABASE_DB_HOST', 'YOUR_HOST_HERE');
define('SUPABASE_DB_PORT', 'YOUR_PORT_HERE');
define('SUPABASE_DB_USER', 'YOUR_USER_HERE');
define('SUPABASE_DB_PASS', 'YOUR_PASSWORD_HERE');</pre>
            
            <h3>Step 4: Test Again</h3>
            <p>Refresh this page to test the connection!</p>
        </div>
        
        <h2>📚 Common Issues</h2>
        <table>
            <tr>
                <th>Error</th>
                <th>Solution</th>
            </tr>
            <tr>
                <td>Unknown host</td>
                <td>Wrong hostname - copy exact value from Supabase dashboard</td>
            </tr>
            <tr>
                <td>Authentication failed</td>
                <td>Wrong username or password - check credentials</td>
            </tr>
            <tr>
                <td>Connection timeout</td>
                <td>Network issue - check internet connection, try different port</td>
            </tr>
        </table>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="javascript:location.reload()" class="btn">🔄 Test Again</a>
            <a href="SUPABASE_CONNECTION_FIX.md" class="btn btn-secondary">📖 Read Full Guide</a>
        </div>
        
    <?php endif; ?>
    
    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e0e0e0; text-align: center; color: #999; font-size: 14px;">
        <p>LYDO System - Supabase Connection Test</p>
    </div>
</div>

</body>
</html>
