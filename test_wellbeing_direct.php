<?php
// Direct test of wellbeing assistant functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Wellbeing Assistant Direct Test</h1>";

// Set up session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';

echo "<h2>Environment Check:</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";

// Include config
try {
    require_once __DIR__ . '/lydo-system/shared/config.php';
    echo "<p style='color: green;'>✅ Config loaded successfully</p>";
    
    // Test database connection
    $pdo = db();
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Test if wellbeing tables exist
    $tables = $pdo->query("SHOW TABLES LIKE 'wellbeing_chats'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p style='color: green;'>✅ Wellbeing_chats table exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Wellbeing_chats table doesn't exist yet (will be created on first access)</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error in setup: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

echo "<h2>Access Test:</h2>";
echo "<p><a href='lydo-system/shared/youth/wellbeing.php' target='_blank'>🔗 Open Wellbeing Assistant</a></p>";
echo "<p><a href='lydo-system/shared/youth/wellbeing_ai.php' target='_blank'>🔗 Open Wellbeing Assistant (AI Link)</a></p>";

echo "<h2>API Test:</h2>";
echo "<p><a href='lydo-system/api/youth/wellbeing.php' target='_blank'>🔗 Test API Endpoint</a></p>";
?>