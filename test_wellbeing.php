<?php
// Simple test to check if wellbeing system works
session_start();

// Mock a session for testing
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';

echo "<!DOCTYPE html>";
echo "<html><head><title>Wellbeing Test</title></head><body>";
echo "<h1>Wellbeing Assistant Test</h1>";

try {
    // Test if we can include the wellbeing file
    include_once __DIR__ . '/lydo-system/shared/youth/wellbeing.php';
    echo "<p style='color: green;'>✅ Wellbeing assistant loaded successfully!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading wellbeing assistant: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='lydo-system/shared/youth/wellbeing.php'>Go to Wellbeing Assistant</a></p>";
echo "</body></html>";
?>