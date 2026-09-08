<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Diagnosis for Wellbeing Assistant</h1>";

require_once __DIR__ . '/lydo-system/shared/config.php';

try {
    $pdo = db();
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    echo "<h2>Available Tables:</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Check specific tables
    $requiredTables = [
        'youth_users' => 'User authentication',
        'notifications' => 'Notifications system',
        'org_president_notifications' => 'President notifications',
        'wellbeing_chats' => 'Chat history (optional)',
        'admin_users' => 'Admin system'
    ];
    
    echo "<h2>Required Tables Check:</h2>";
    foreach ($requiredTables as $table => $purpose) {
        if (in_array($table, $tables)) {
            echo "<p style='color: green;'>✅ $table - $purpose</p>";
            
            // Show count of records
            try {
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "<span style='margin-left: 20px; color: gray;'>($count records)</span><br>";
            } catch (Exception $e) {
                echo "<span style='margin-left: 20px; color: orange;'>Error counting: " . $e->getMessage() . "</span><br>";
            }
        } else {
            echo "<p style='color: red;'>❌ $table - $purpose (Missing!)</p>";
        }
    }
    
    // Test authentication
    echo "<h2>Authentication Test:</h2>";
    session_start();
    
    if (in_array('youth_users', $tables)) {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM youth_users LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            
            echo "<p style='color: green;'>✅ Test session created for: " . $_SESSION['user_name'] . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No users found in youth_users table</p>";
        }
    }
    
    echo "<h2>Test Wellbeing Assistant:</h2>";
    echo "<p><a href='lydo-system/shared/youth/wellbeing.php' target='_blank' style='background: #1565c0; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🤖 Launch Wellbeing Assistant</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>