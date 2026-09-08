<?php
/**
 * Boolean Query Fixer for PostgreSQL Migration
 * 
 * This script fixes all queries that use MySQL boolean syntax (1/0)
 * and converts them to PostgreSQL syntax (TRUE/FALSE)
 * 
 * Run this ONCE after migration to fix all boolean comparisons
 */

$files = [
    'backend/config/admin_auth.php' => [
        "is_active = 1" => "is_active = TRUE"
    ],
    'api/get_organizations.php' => [
        "is_active = 1" => "is_active = TRUE"
    ],
    'backend/api/admin/login.php' => [
        "is_active = 1" => "is_active = TRUE"
    ],
    'forgot_password.php' => [
        "is_active=1" => "is_active = TRUE"
    ],
    'admin2/dashboard.php' => [
        "is_active=1" => "is_active = TRUE",
        "is_active=0" => "is_active = FALSE"
    ],
    'shared/youth/assistance.php' => [
        "is_active=1" => "is_active = TRUE"
    ],
    'admin2/events.php' => [
        "is_active=1" => "is_active = TRUE"
    ],
    'admin2/organizations.php' => [
        "is_active=1" => "is_active = TRUE"
    ],
    'shared/youth/volunteer.php' => [
        "is_active=1" => "is_active = TRUE"
    ],
    'shared/youth/wellbeing.php' => [
        "is_active=1" => "is_active = TRUE"
    ],
];

$basePath = __DIR__;
$fixed = 0;
$errors = 0;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Boolean Query Fixer</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: white; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
<h1>PostgreSQL Boolean Query Fixer</h1>
<p>Fixing MySQL boolean syntax (1/0) to PostgreSQL syntax (TRUE/FALSE)...</p>
<hr>
";

foreach ($files as $file => $replacements) {
    $fullPath = $basePath . '/' . $file;
    
    if (!file_exists($fullPath)) {
        echo "<p class='error'>✗ File not found: $file</p>";
        $errors++;
        continue;
    }
    
    $content = file_get_contents($fullPath);
    $originalContent = $content;
    $changesMade = 0;
    
    foreach ($replacements as $search => $replace) {
        $count = 0;
        $content = str_replace($search, $replace, $content, $count);
        $changesMade += $count;
    }
    
    if ($changesMade > 0) {
        // Backup original
        $backupPath = $fullPath . '.backup';
        if (!file_exists($backupPath)) {
            file_put_contents($backupPath, $originalContent);
        }
        
        // Write fixed content
        file_put_contents($fullPath, $content);
        echo "<p class='success'>✓ Fixed $file ($changesMade changes)</p>";
        $fixed++;
    } else {
        echo "<p class='info'>- $file (no changes needed)</p>";
    }
}

echo "<hr>
<h2>Summary</h2>
<p class='success'>✓ Fixed: $fixed files</p>
<p class='error'>✗ Errors: $errors files</p>

<h3>Next Steps:</h3>
<ol>
    <li>Refresh your browser</li>
    <li>Try logging in again</li>
    <li>If you need to rollback, rename .backup files back to original</li>
</ol>

<p><strong>Note:</strong> Original files have been backed up with .backup extension</p>

<p><a href='login.php' style='display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Go to Login</a></p>
</body>
</html>";
