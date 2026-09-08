<?php
/**
 * STANDALONE DATABASE FIX - No dependencies
 */

// Direct database connection
$host = '127.0.0.1';
$port = '3306';
$user = 'root';
$pass = '';
$db   = 'local_youth_development_db';

echo "<html><head><title>Fix Database</title><style>body{font-family:Arial;padding:40px;max-width:800px;margin:0 auto}h1{color:#1565c0}pre{background:#f5f5f5;padding:15px;border-radius:5px;overflow:auto}.success{background:#e8f5e9;padding:20px;border-radius:8px;border:2px solid #4caf50;margin:20px 0}.error{background:#ffebee;padding:20px;border-radius:8px;border:2px solid #f44336;margin:20px 0}</style></head><body>";

echo "<h1>🔧 Database Fix Tool</h1>";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<p style='color:green'>✅ Connected to database: <strong>$db</strong></p>";
    
    echo "<h2>Checking accreditation_applications table...</h2>";
    
    // Check if description column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM accreditation_applications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasDescription = false;
    echo "<h3>Current columns:</h3>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li><strong>{$col['Field']}</strong> ({$col['Type']})</li>";
        if ($col['Field'] === 'description') {
            $hasDescription = true;
        }
    }
    echo "</ul>";
    
    if ($hasDescription) {
        echo "<div class='success'>";
        echo "<h3>✅ Already Fixed!</h3>";
        echo "<p>The <strong>description</strong> column already exists in the table.</p>";
        echo "</div>";
    } else {
        echo "<h2>Adding description column...</h2>";
        
        $pdo->exec("ALTER TABLE accreditation_applications ADD COLUMN description TEXT NULL AFTER contact_phone");
        
        echo "<div class='success'>";
        echo "<h3>✅ Fix Applied Successfully!</h3>";
        echo "<p>Added <strong>description</strong> column to accreditation_applications table.</p>";
        echo "</div>";
        
        // Show updated structure
        echo "<h3>Updated columns:</h3>";
        $stmt = $pdo->query("SHOW COLUMNS FROM accreditation_applications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<ul>";
        foreach ($columns as $col) {
            $highlight = $col['Field'] === 'description' ? ' style="color:#4caf50;font-weight:bold"' : '';
            echo "<li$highlight>{$col['Field']} ({$col['Type']})</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr style='margin:40px 0'>";
    echo "<h2>✅ Database is Ready!</h2>";
    echo "<p><a href='shared/youth/accreditation.php' style='display:inline-block;background:#1565c0;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold'>→ Go to Accreditation Page</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Database Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Solution:</strong> Make sure MySQL is running in XAMPP Control Panel.</p>";
    echo "</div>";
}

echo "</body></html>";
?>
