<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Create Test User for Wellbeing Assistant</h1>";

require_once __DIR__ . '/lydo-system/shared/config.php';

try {
    $pdo = db();
    
    // Check if youth_users table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'youth_users'")->fetchAll();
    if (count($tables) === 0) {
        echo "<p style='color: red;'>❌ youth_users table doesn't exist!</p>";
        exit;
    }
    
    // Check if test user exists
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM youth_users WHERE id = 1 LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p style='color: green;'>✅ Test user exists:</p>";
        echo "<ul>";
        echo "<li>ID: " . $user['id'] . "</li>";
        echo "<li>Name: " . $user['first_name'] . " " . $user['last_name'] . "</li>";
        echo "<li>Email: " . $user['email'] . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ No test user found. Creating one...</p>";
        
        // Create a test user
        $stmt = $pdo->prepare("
            INSERT INTO youth_users (first_name, last_name, email, password, is_verified, created_at) 
            VALUES (?, ?, ?, ?, TRUE, NOW())
        ");
        
        $hashedPassword = password_hash('testpass123', PASSWORD_DEFAULT);
        $stmt->execute(['Test', 'User', 'test@lydo.com', $hashedPassword]);
        
        $userId = $pdo->lastInsertId();
        echo "<p style='color: green;'>✅ Test user created with ID: $userId</p>";
    }
    
    // Set up session for testing
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test User';
    $_SESSION['user_email'] = 'test@lydo.com';
    
    echo "<p style='color: blue;'>📋 Session set up for testing:</p>";
    echo "<ul>";
    echo "<li>user_id: " . $_SESSION['user_id'] . "</li>";
    echo "<li>user_name: " . $_SESSION['user_name'] . "</li>";
    echo "</ul>";
    
    echo "<h2>Test Links:</h2>";
    echo "<p><a href='lydo-system/shared/youth/wellbeing.php' style='color: blue; font-weight: bold;'>🤖 Test Wellbeing Assistant</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>