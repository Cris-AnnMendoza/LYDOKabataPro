<?php
/**
 * Supabase Migration Test Script
 * 
 * This script tests the connection and basic functionality
 * of the LYDO system with Supabase PostgreSQL.
 * 
 * Usage: Open in browser: /LYDO/lydo-system/test_supabase.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/shared/config.php';

$tests = [];
$passed = 0;
$failed = 0;

function test($name, $callback) {
    global $tests, $passed, $failed;
    try {
        $result = $callback();
        if ($result) {
            $tests[] = ['name' => $name, 'status' => 'pass', 'message' => 'Success'];
            $passed++;
        } else {
            $tests[] = ['name' => $name, 'status' => 'fail', 'message' => 'Test returned false'];
            $failed++;
        }
    } catch (Exception $e) {
        $tests[] = ['name' => $name, 'status' => 'error', 'message' => $e->getMessage()];
        $failed++;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supabase Migration Test - LYDO System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .info-box {
            padding: 20px 30px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            margin: 20px 30px;
        }
        .info-box strong {
            color: #007bff;
        }
        .content {
            padding: 30px;
        }
        .test-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        .test-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .test-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .test-pass .test-icon {
            background: #d4edda;
            color: #28a745;
        }
        .test-fail .test-icon, .test-error .test-icon {
            background: #f8d7da;
            color: #dc3545;
        }
        .test-info {
            flex: 1;
        }
        .test-name {
            font-weight: 600;
            font-size: 16px;
            color: #333;
            margin-bottom: 4px;
        }
        .test-message {
            font-size: 13px;
            color: #666;
        }
        .summary {
            display: flex;
            gap: 20px;
            margin: 30px;
            justify-content: center;
        }
        .summary-card {
            flex: 1;
            max-width: 200px;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            color: white;
        }
        .summary-card.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .summary-card.passed {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        }
        .summary-card.failed {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        }
        .summary-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .summary-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 13px;
        }
        .config-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 30px;
        }
        .config-info h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .config-info code {
            background: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Supabase Migration Test</h1>
            <p>LYDO System - Database Connection & Functionality Verification</p>
        </div>

        <?php if (defined('USE_SUPABASE') && USE_SUPABASE): ?>
            <div class="info-box">
                <strong>✓ Supabase Mode:</strong> Currently using Supabase PostgreSQL
            </div>
        <?php else: ?>
            <div class="config-info">
                <h3>⚠️ MySQL Mode Active</h3>
                <p>The system is currently configured to use MySQL.</p>
                <p>To test Supabase, edit <code>lydo-system/shared/config.php</code> and set:</p>
                <p><code>define('USE_SUPABASE', true);</code></p>
            </div>
        <?php endif; ?>

        <?php
        // ================================
        // RUN TESTS
        // ================================

        // Test 1: Database Connection
        test('Database Connection', function() {
            $pdo = db();
            return $pdo instanceof PDO;
        });

        // Test 2: Check Database Driver
        test('PostgreSQL Driver', function() {
            if (!defined('USE_SUPABASE') || !USE_SUPABASE) {
                throw new Exception('Supabase mode not enabled');
            }
            $pdo = db();
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver !== 'pgsql') {
                throw new Exception("Expected 'pgsql', got '$driver'");
            }
            return true;
        });

        // Test 3: Query Helper Functions
        test('Query Helper Functions Loaded', function() {
            return function_exists('insertAndGetId') && 
                   function_exists('dbBool') && 
                   function_exists('limitQuery');
        });

        // Test 4: Check youth_users Table
        test('youth_users Table Exists', function() {
            $pdo = db();
            $stmt = $pdo->query("SELECT COUNT(*) FROM youth_users");
            return $stmt !== false;
        });

        // Test 5: Check admin_users Table
        test('admin_users Table Exists', function() {
            $pdo = db();
            $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
            $count = $stmt->fetchColumn();
            return $count >= 1; // Should have at least default admin
        });

        // Test 6: Check Default Admin
        test('Default Admin Account Exists', function() {
            $pdo = db();
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ?");
            $stmt->execute(['admin@lydo.gov.ph']);
            $admin = $stmt->fetch();
            return $admin !== false;
        });

        // Test 7: Check organizations Table
        test('organizations Table Exists', function() {
            $pdo = db();
            $stmt = $pdo->query("SELECT COUNT(*) FROM organizations");
            return $stmt !== false;
        });

        // Test 8: Check events Table
        test('events Table Exists', function() {
            $pdo = db();
            $stmt = $pdo->query("SELECT COUNT(*) FROM events");
            return $stmt !== false;
        });

        // Test 9: Check merit_logs Table
        test('merit_logs Table Exists', function() {
            $pdo = db();
            $stmt = $pdo->query("SELECT COUNT(*) FROM merit_logs");
            return $stmt !== false;
        });

        // Test 10: Check notifications Table
        test('notifications Table Exists', function() {
            $pdo = db();
            $stmt = $pdo->query("SELECT COUNT(*) FROM notifications");
            return $stmt !== false;
        });

        // Test 11: Test INSERT with RETURNING (PostgreSQL specific)
        test('INSERT with RETURNING Support', function() {
            if (!defined('USE_SUPABASE') || !USE_SUPABASE) {
                return true; // Skip for MySQL
            }
            
            $pdo = db();
            
            // Create test notification
            $userId = 1; // Assuming admin user exists
            $testId = insertAndGetId($pdo, 
                "INSERT INTO notifications (user_id, title, message, type) 
                 VALUES (?, ?, ?, ?)",
                [$userId, 'Test Notification', 'Migration test', 'info']
            );
            
            if ($testId > 0) {
                // Clean up
                $pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([$testId]);
                return true;
            }
            return false;
        });

        // Test 12: Test Boolean Handling
        test('Boolean Value Handling', function() {
            $pdo = db();
            $stmt = $pdo->query("SELECT is_active FROM admin_users LIMIT 1");
            $result = $stmt->fetch();
            return isset($result['is_active']);
        });

        // Test 13: Test Date/Time Functions
        test('Timestamp Functions', function() {
            $pdo = db();
            $stmt = $pdo->query("SELECT CURRENT_TIMESTAMP as now");
            $result = $stmt->fetch();
            return isset($result['now']);
        });

        // Test 14: Test JOIN Query
        test('JOIN Query Support', function() {
            $pdo = db();
            $stmt = $pdo->query("
                SELECT e.*, o.name as org_name 
                FROM events e 
                LEFT JOIN organizations o ON e.organization_id = o.id 
                LIMIT 1
            ");
            return $stmt !== false;
        });

        // Test 15: Database Adapter Class
        test('Database Adapter Available', function() {
            return function_exists('dbAdapter') && class_exists('DatabaseAdapter');
        });

        ?>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-card total">
                <div class="summary-number"><?php echo count($tests); ?></div>
                <div class="summary-label">Total Tests</div>
            </div>
            <div class="summary-card passed">
                <div class="summary-number"><?php echo $passed; ?></div>
                <div class="summary-label">Passed</div>
            </div>
            <div class="summary-card failed">
                <div class="summary-number"><?php echo $failed; ?></div>
                <div class="summary-label">Failed</div>
            </div>
        </div>

        <!-- Test Results -->
        <div class="content">
            <h2 style="margin-bottom: 20px; color: #333;">Test Results</h2>
            <?php foreach ($tests as $test): ?>
                <div class="test-item test-<?php echo $test['status']; ?>">
                    <div class="test-icon">
                        <?php echo $test['status'] === 'pass' ? '✓' : '✗'; ?>
                    </div>
                    <div class="test-info">
                        <div class="test-name"><?php echo htmlspecialchars($test['name']); ?></div>
                        <div class="test-message"><?php echo htmlspecialchars($test['message']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Connection Info -->
        <?php if (defined('USE_SUPABASE') && USE_SUPABASE): ?>
        <div class="info-box" style="margin: 30px;">
            <h3 style="margin-bottom: 10px; color: #007bff;">Connection Information</h3>
            <p><strong>Database:</strong> <?php echo SUPABASE_DB_NAME; ?></p>
            <p><strong>Host:</strong> <?php echo SUPABASE_DB_HOST; ?></p>
            <p><strong>Port:</strong> <?php echo SUPABASE_DB_PORT; ?></p>
            <p><strong>User:</strong> <?php echo SUPABASE_DB_USER; ?></p>
            <?php
            $pdo = db();
            $version = $pdo->query("SELECT version()")->fetchColumn();
            ?>
            <p><strong>Version:</strong> <?php echo htmlspecialchars($version); ?></p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <?php if ($failed === 0): ?>
                <p style="color: #28a745; font-weight: bold; font-size: 16px;">
                    ✓ All tests passed! Your Supabase migration is ready.
                </p>
                <p style="margin-top: 10px;">
                    You can now use the LYDO system with Supabase PostgreSQL.
                </p>
            <?php else: ?>
                <p style="color: #dc3545; font-weight: bold; font-size: 16px;">
                    ✗ Some tests failed. Please review the configuration.
                </p>
                <p style="margin-top: 10px;">
                    Check <code>lydo-system/shared/supabase_config.php</code> and ensure your Supabase credentials are correct.
                </p>
            <?php endif; ?>
            
            <p style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #dee2e6;">
                LYDO System © 2026 | <a href="SUPABASE_MIGRATION_GUIDE.md" style="color: #007bff;">Migration Guide</a> | 
                <a href="SUPABASE_QUICK_SETUP.md" style="color: #007bff;">Quick Setup</a>
            </p>
        </div>
    </div>

    <script>
        // Auto-refresh every 10 seconds if tests are failing
        <?php if ($failed > 0): ?>
        setTimeout(() => {
            if (confirm('Some tests failed. Refresh to re-test?')) {
                location.reload();
            }
        }, 10000);
        <?php endif; ?>
    </script>
</body>
</html>
