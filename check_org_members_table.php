<?php
require_once 'lydo-system/shared/config.php';

$pdo = db();

echo "<h2>Checking organization_members table structure</h2>";

try {
    $stmt = $pdo->query('DESCRIBE organization_members');
    echo "<h3>Current Columns:</h3><pre>";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Table doesn't exist or error: " . $e->getMessage() . "</p>";
    
    echo "<h3>Creating organization_members table...</h3>";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS organization_members (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organization_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            position VARCHAR(100) DEFAULT 'Member',
            is_active BOOLEAN DEFAULT TRUE,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_org_user (organization_id, user_id),
            INDEX idx_organization (organization_id),
            INDEX idx_user (user_id),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    echo "<p style='color:green'>Table created successfully!</p>";
    
    $stmt = $pdo->query('DESCRIBE organization_members');
    echo "<h3>New Columns:</h3><pre>";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
}

echo "<h3>Checking for missing columns...</h3>";

$requiredColumns = ['id', 'organization_id', 'user_id', 'position', 'is_active', 'joined_at', 'updated_at'];
$existingColumns = [];

$stmt = $pdo->query('DESCRIBE organization_members');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $existingColumns[] = $row['Field'];
}

$missingColumns = array_diff($requiredColumns, $existingColumns);

if (!empty($missingColumns)) {
    echo "<p style='color:orange'>Missing columns: " . implode(', ', $missingColumns) . "</p>";
    echo "<h3>Adding missing columns...</h3>";
    
    foreach ($missingColumns as $col) {
        try {
            if ($col === 'position') {
                $pdo->exec("ALTER TABLE organization_members ADD COLUMN position VARCHAR(100) DEFAULT 'Member'");
                echo "<p style='color:green'>Added column: position</p>";
            } elseif ($col === 'is_active') {
                $pdo->exec("ALTER TABLE organization_members ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
                echo "<p style='color:green'>Added column: is_active</p>";
            } elseif ($col === 'joined_at') {
                $pdo->exec("ALTER TABLE organization_members ADD COLUMN joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                echo "<p style='color:green'>Added column: joined_at</p>";
            } elseif ($col === 'updated_at') {
                $pdo->exec("ALTER TABLE organization_members ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                echo "<p style='color:green'>Added column: updated_at</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error adding $col: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>Updated Table Structure:</h3><pre>";
    $stmt = $pdo->query('DESCRIBE organization_members');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color:green'>All required columns exist!</p>";
}

echo "<h3>Sample Data:</h3>";
$count = $pdo->query('SELECT COUNT(*) FROM organization_members')->fetchColumn();
echo "<p>Total records: $count</p>";

if ($count > 0) {
    $sample = $pdo->query('SELECT * FROM organization_members LIMIT 5')->fetchAll();
    echo "<pre>";
    print_r($sample);
    echo "</pre>";
}

echo "<p><a href='lydo-system/org-president/members.php'>Go to Members Page</a></p>";
