<?php
/**
 * FIX ACCREDITATION TABLE - Add missing description column
 */

require_once __DIR__ . '/shared/config.php';

echo "<html><head><title>Fix Accreditation Table</title></head><body style='font-family:sans-serif;padding:40px;max-width:800px;margin:0 auto'>";
echo "<h1>🔧 Fix Accreditation Table</h1>";

try {
    $pdo = db();
    
    echo "<h2>Step 1: Check current table structure</h2>";
    
    // Check if description column exists
    $check = $pdo->query("SHOW COLUMNS FROM accreditation_applications LIKE 'description'");
    $exists = $check->fetch();
    
    if ($exists) {
        echo "<p style='color:green'>✅ <strong>description</strong> column already exists!</p>";
    } else {
        echo "<p style='color:orange'>⚠️ <strong>description</strong> column is MISSING</p>";
        
        echo "<h2>Step 2: Adding description column...</h2>";
        
        $pdo->exec("ALTER TABLE accreditation_applications ADD COLUMN description TEXT NULL AFTER contact_phone");
        
        echo "<p style='color:green'>✅ <strong>description</strong> column added successfully!</p>";
    }
    
    echo "<h2>Step 3: Verify table structure</h2>";
    
    $columns = $pdo->query("DESCRIBE accreditation_applications")->fetchAll();
    
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;margin:20px 0'>";
    echo "<tr style='background:#1565c0;color:#fff'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<div style='background:#e8f5e9;padding:20px;border-radius:8px;border:2px solid #4caf50;margin:30px 0'>";
    echo "<h2 style='color:#2e7d32;margin-top:0'>✅ Fix Complete!</h2>";
    echo "<p>The accreditation table has been fixed. You can now submit accreditation applications!</p>";
    echo "<p><a href='shared/youth/accreditation.php' style='color:#1565c0;font-weight:bold'>→ Go to Accreditation Page</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background:#ffebee;padding:20px;border-radius:8px;border:2px solid #f44336'>";
    echo "<h2 style='color:#c62828'>❌ Error</h2>";
    echo "<p><strong>Error message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
