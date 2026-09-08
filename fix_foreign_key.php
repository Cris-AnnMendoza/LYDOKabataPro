<?php
/**
 * FIX FOREIGN KEY - Make organization_id optional
 */

$host = '127.0.0.1';
$port = '3306';
$user = 'root';
$pass = '';
$db   = 'local_youth_development_db';

echo "<html><head><title>Fix Foreign Key</title><style>body{font-family:Arial;padding:40px;max-width:800px;margin:0 auto}h1{color:#1565c0}pre{background:#f5f5f5;padding:15px;border-radius:5px;overflow:auto}.success{background:#e8f5e9;padding:20px;border-radius:8px;border:2px solid #4caf50;margin:20px 0}.error{background:#ffebee;padding:20px;border-radius:8px;border:2px solid #f44336;margin:20px 0}</style></head><body>";

echo "<h1>🔧 Fix Foreign Key Constraint</h1>";
echo "<p>Making organization_id optional in accreditation_applications table...</p>";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "<p style='color:green'>✅ Connected to database</p>";
    
    echo "<h2>Step 1: Drop existing foreign key</h2>";
    
    // Get foreign key name
    $fkQuery = "SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = '$db' 
                AND TABLE_NAME = 'accreditation_applications' 
                AND COLUMN_NAME = 'organization_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL";
    
    $fkStmt = $pdo->query($fkQuery);
    $fk = $fkStmt->fetch();
    
    if ($fk) {
        $fkName = $fk['CONSTRAINT_NAME'];
        echo "<p>Found foreign key: <strong>{$fkName}</strong></p>";
        
        $pdo->exec("ALTER TABLE accreditation_applications DROP FOREIGN KEY `{$fkName}`");
        echo "<p style='color:green'>✅ Dropped foreign key constraint</p>";
    } else {
        echo "<p style='color:orange'>⚠️ No foreign key found (maybe already dropped)</p>";
    }
    
    echo "<h2>Step 2: Make organization_id nullable</h2>";
    
    $pdo->exec("ALTER TABLE accreditation_applications MODIFY COLUMN organization_id INT UNSIGNED NULL");
    echo "<p style='color:green'>✅ Changed organization_id to allow NULL values</p>";
    
    echo "<h2>Step 3: Re-add foreign key (optional)</h2>";
    
    $pdo->exec("ALTER TABLE accreditation_applications 
                ADD CONSTRAINT fk_accred_org 
                FOREIGN KEY (organization_id) REFERENCES organizations(id) 
                ON DELETE SET NULL");
    echo "<p style='color:green'>✅ Re-added foreign key with SET NULL on delete</p>";
    
    echo "<h2>Step 4: Verify changes</h2>";
    
    $columns = $pdo->query("DESCRIBE accreditation_applications")->fetchAll();
    
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;margin:20px 0'>";
    echo "<tr style='background:#1565c0;color:#fff'><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    
    foreach ($columns as $col) {
        $highlight = $col['Field'] === 'organization_id' ? ' style="background:#e8f5e9"' : '';
        echo "<tr$highlight>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<div class='success'>";
    echo "<h2 style='color:#2e7d32;margin-top:0'>✅ Fix Complete!</h2>";
    echo "<p><strong>Changes made:</strong></p>";
    echo "<ul>";
    echo "<li>Removed strict foreign key constraint</li>";
    echo "<li>Made organization_id nullable (allows NULL)</li>";
    echo "<li>Youth users can now apply without being linked to an organization</li>";
    echo "<li>Organization presidents can still be linked if needed</li>";
    echo "</ul>";
    echo "<p><a href='shared/youth/accreditation.php' style='display:inline-block;background:#1565c0;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;margin-top:10px'>→ Go to Accreditation Page</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2 style='color:#c62828'>❌ Database Error</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
