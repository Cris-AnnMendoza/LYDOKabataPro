<?php
if (session_status() === PHP_SESSION_NONE) session_start();

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3307');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'local_youth_development_db');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8080');

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        try {
            $dsn = 'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:40px;color:#c62828;background:#ffebee;border-radius:10px;max-width:600px;margin:40px auto">
                <h2>Database Connection Failed</h2>
                <p>'.$e->getMessage().'</p>
                <p>Make sure <strong>MySQL is running</strong> in XAMPP Control Panel.</p>
            </div>');
        }
    }
    return $pdo;
}

function flash(string $key, string $msg = ''): string {
    if ($msg) { $_SESSION['flash'][$key] = $msg; return ''; }
    $val = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $val;
}
