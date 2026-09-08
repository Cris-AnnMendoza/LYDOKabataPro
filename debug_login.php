<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/shared/config.php';

echo "<h1>Debug Login Test</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;} pre{background:white;padding:15px;border-radius:5px;overflow:auto;} .success{color:green;} .error{color:red;}</style>";

// Test 1: Database Connection
echo "<h2>1. Database Connection</h2>";
try {
    $pdo = db();
    echo "<p class='success'>✓ Connected to database</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 2: Check if admin exists
echo "<h2>2. Check Admin User</h2>";
try {
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ?');
    $stmt->execute(['admin@lydo.gov.ph']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p class='success'>✓ Admin user found</p>";
        echo "<pre>";
        echo "ID: " . $admin['id'] . "\n";
        echo "Name: " . htmlspecialchars($admin['full_name']) . "\n";
        echo "Email: " . htmlspecialchars($admin['email']) . "\n";
        echo "Role: " . htmlspecialchars($admin['role']) . "\n";
        echo "Active: " . ($admin['is_active'] ? 'Yes' : 'No') . "\n";
        echo "Password Hash: " . substr($admin['password'], 0, 20) . "...\n";
        echo "</pre>";
    } else {
        echo "<p class='error'>✗ Admin user NOT found</p>";
        echo "<p>Running INSERT to create admin...</p>";
        
        // Create admin
        $pdo->prepare("INSERT INTO admin_users (full_name, email, password, role) VALUES (?, ?, ?, ?)
                       ON CONFLICT (email) DO NOTHING")
            ->execute([
                'Super Administrator',
                'admin@lydo.gov.ph',
                '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'super_admin'
            ]);
        
        echo "<p class='success'>✓ Admin user created</p>";
        echo "<p><a href='debug_login.php'>Refresh to verify</a></p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 3: Test Password Verification
echo "<h2>3. Password Verification Test</h2>";
$testPassword = 'Admin@1234';
$storedHash = $admin['password'];

echo "<p>Testing password: <code>$testPassword</code></p>";
if (password_verify($testPassword, $storedHash)) {
    echo "<p class='success'>✓ Password verification successful</p>";
} else {
    echo "<p class='error'>✗ Password verification FAILED</p>";
    echo "<p>This means the password hash in the database doesn't match 'Admin@1234'</p>";
    echo "<p>Let me create a new hash...</p>";
    
    $newHash = password_hash('Admin@1234', PASSWORD_DEFAULT);
    echo "<p>New hash: <code>$newHash</code></p>";
    
    $pdo->prepare("UPDATE admin_users SET password = ? WHERE email = ?")
        ->execute([$newHash, 'admin@lydo.gov.ph']);
    
    echo "<p class='success'>✓ Password updated. Please try again.</p>";
    echo "<p><a href='debug_login.php'>Refresh to verify</a></p>";
    exit;
}

// Test 4: Test Login Query
echo "<h2>4. Test Login Query</h2>";
try {
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ? AND is_active = TRUE LIMIT 1');
    $stmt->execute(['admin@lydo.gov.ph']);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<p class='success'>✓ Login query works correctly</p>";
    } else {
        echo "<p class='error'>✗ Login query returned no results</p>";
        
        // Check is_active value
        $stmt2 = $pdo->prepare('SELECT is_active FROM admin_users WHERE email = ?');
        $stmt2->execute(['admin@lydo.gov.ph']);
        $row = $stmt2->fetch();
        echo "<p>is_active value: " . var_export($row['is_active'], true) . "</p>";
        
        // Fix is_active if needed
        if (!$row['is_active']) {
            $pdo->prepare("UPDATE admin_users SET is_active = TRUE WHERE email = ?")
                ->execute(['admin@lydo.gov.ph']);
            echo "<p class='success'>✓ Fixed is_active to TRUE</p>";
            echo "<p><a href='debug_login.php'>Refresh to verify</a></p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Query error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 5: Test Full Login Flow
echo "<h2>5. Full Login Flow Test</h2>";
session_start();

$email = 'admin@lydo.gov.ph';
$password = 'Admin@1234';

$stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ? AND is_active = TRUE LIMIT 1');
$stmt->execute([$email]);
$admin = $stmt->fetch();

if ($admin && password_verify($password, $admin['password'])) {
    echo "<p class='success'>✓ Login would succeed!</p>";
    echo "<p>Would redirect to: <a href='admin2/dashboard.php'>admin2/dashboard.php</a></p>";
    
    // Actually set session and redirect
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin'] = [
        'id'        => $admin['id'],
        'full_name' => $admin['full_name'],
        'email'     => $admin['email'],
        'role'      => $admin['role'],
    ];
    
    echo "<p><strong>Session set! Click below to go to dashboard:</strong></p>";
    echo "<p><a href='admin2/dashboard.php' style='display:inline-block;padding:15px 30px;background:#28a745;color:white;text-decoration:none;border-radius:5px;font-size:18px;'>Go to Admin Dashboard</a></p>";
} else {
    echo "<p class='error'>✗ Login would FAIL</p>";
    if (!$admin) {
        echo "<p>Reason: Admin not found with is_active = TRUE</p>";
    } else {
        echo "<p>Reason: Password verification failed</p>";
    }
}

echo "<hr>";
echo "<h2>Try Normal Login</h2>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>
