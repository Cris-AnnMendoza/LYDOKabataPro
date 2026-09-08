<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/shared/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supabase Verification - LYDO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
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
        .content {
            padding: 30px;
        }
        .status-box {
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid;
        }
        .success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .check-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin: 8px 0;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .check-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 16px;
            font-weight: bold;
        }
        .check-icon.pass { background: #28a745; color: white; }
        .check-icon.fail { background: #dc3545; color: white; }
        h2 { margin: 20px 0 10px; color: #333; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
        }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Supabase Verification</h1>
            <p>LYDO System - Database Connection Check</p>
        </div>

        <div class="content">
            <?php
            $allPassed = true;
            
            // Test 1: Database Connection
            try {
                $pdo = db();
                echo '<div class="status-box success">';
                echo '<strong>✓ Database Connection:</strong> Successfully connected to Supabase PostgreSQL';
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="status-box error">';
                echo '<strong>✗ Database Connection Failed:</strong> ' . htmlspecialchars($e->getMessage());
                echo '</div>';
                $allPassed = false;
            }

            if ($pdo) {
                // Test 2: Check Driver
                $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
                echo '<div class="check-item">';
                echo '<div class="check-icon ' . ($driver === 'pgsql' ? 'pass' : 'fail') . '">';
                echo $driver === 'pgsql' ? '✓' : '✗';
                echo '</div>';
                echo '<div>Database Driver: ' . htmlspecialchars($driver) . ($driver === 'pgsql' ? ' (PostgreSQL)' : '') . '</div>';
                echo '</div>';

                // Test 3: Get Version
                try {
                    $version = $pdo->query("SELECT version()")->fetchColumn();
                    echo '<div class="status-box info">';
                    echo '<strong>PostgreSQL Version:</strong><br>' . htmlspecialchars(substr($version, 0, 100));
                    echo '</div>';
                } catch (Exception $e) {
                    // Ignore
                }

                // Test 4: Count Tables
                echo '<h2>Database Tables</h2>';
                try {
                    $stmt = $pdo->query("
                        SELECT table_name 
                        FROM information_schema.tables 
                        WHERE table_schema = 'public' 
                        ORDER BY table_name
                    ");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (count($tables) > 0) {
                        echo '<div class="status-box success">';
                        echo '<strong>✓ Found ' . count($tables) . ' tables</strong>';
                        echo '</div>';
                        
                        echo '<table>';
                        echo '<tr><th>#</th><th>Table Name</th><th>Row Count</th></tr>';
                        foreach ($tables as $i => $table) {
                            try {
                                $count = $pdo->query("SELECT COUNT(*) FROM \"$table\"")->fetchColumn();
                                echo '<tr>';
                                echo '<td>' . ($i + 1) . '</td>';
                                echo '<td>' . htmlspecialchars($table) . '</td>';
                                echo '<td>' . number_format($count) . '</td>';
                                echo '</tr>';
                            } catch (Exception $e) {
                                echo '<tr>';
                                echo '<td>' . ($i + 1) . '</td>';
                                echo '<td>' . htmlspecialchars($table) . '</td>';
                                echo '<td>Error</td>';
                                echo '</tr>';
                            }
                        }
                        echo '</table>';
                    } else {
                        echo '<div class="status-box error">';
                        echo '<strong>✗ No tables found!</strong> You need to run the schema SQL.';
                        echo '</div>';
                        $allPassed = false;
                    }
                } catch (Exception $e) {
                    echo '<div class="status-box error">';
                    echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
                    echo '</div>';
                }

                // Test 5: Check Admin User
                echo '<h2>Default Admin Account</h2>';
                try {
                    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ?");
                    $stmt->execute(['admin@lydo.gov.ph']);
                    $admin = $stmt->fetch();
                    
                    if ($admin) {
                        echo '<div class="status-box success">';
                        echo '<strong>✓ Default admin account exists</strong><br>';
                        echo 'Email: ' . htmlspecialchars($admin['email']) . '<br>';
                        echo 'Name: ' . htmlspecialchars($admin['full_name']) . '<br>';
                        echo 'Role: ' . htmlspecialchars($admin['role']);
                        echo '</div>';
                    } else {
                        echo '<div class="status-box error">';
                        echo '<strong>✗ Default admin not found</strong>';
                        echo '</div>';
                        $allPassed = false;
                    }
                } catch (Exception $e) {
                    echo '<div class="status-box error">';
                    echo '<strong>Error checking admin:</strong> ' . htmlspecialchars($e->getMessage());
                    echo '</div>';
                }
            }

            // Final Status
            echo '<h2>Final Status</h2>';
            if ($allPassed && isset($tables) && count($tables) >= 15) {
                echo '<div class="status-box success" style="font-size: 18px;">';
                echo '<strong>🎉 SUCCESS! Supabase is fully configured and working!</strong><br>';
                echo 'You can now use the LYDO system with Supabase PostgreSQL.';
                echo '</div>';
                
                echo '<div style="text-align: center; margin-top: 30px;">';
                echo '<a href="../admin2/index.php" class="btn">Go to Admin Login</a>';
                echo '<a href="../register.php" class="btn">Go to Youth Registration</a>';
                echo '</div>';
            } else {
                echo '<div class="status-box error">';
                echo '<strong>⚠️ Setup Incomplete</strong><br>';
                echo 'Please run the database schema in Supabase SQL Editor.';
                echo '</div>';
            }
            ?>

            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 6px;">
                <h3>Connection Details</h3>
                <p><strong>Host:</strong> <?php echo SUPABASE_DB_HOST; ?></p>
                <p><strong>Database:</strong> <?php echo SUPABASE_DB_NAME; ?></p>
                <p><strong>Port:</strong> <?php echo SUPABASE_DB_PORT; ?></p>
                <p><strong>User:</strong> <?php echo SUPABASE_DB_USER; ?></p>
            </div>
        </div>
    </div>
</body>
</html>
