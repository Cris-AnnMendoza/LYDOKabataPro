<?php
/**
 * API Bootstrap - Shared utilities for all youth API endpoints
 * Handles: CORS, JSON responses, token-based auth
 */

require_once __DIR__ . '/../../shared/config.php';

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Create api_tokens table if not exists
$pdo = db();
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS api_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        INDEX idx_token (token),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // Table exists
}

/**
 * Send JSON response and exit
 */
function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get JSON body from request
 */
function getJsonBody(): array {
    $body = file_get_contents('php://input');
    return $body ? json_decode($body, true) ?? [] : [];
}

/**
 * Require authentication - validate Bearer token
 * Returns user array if valid, sends 401 and exits if invalid
 */
function requireAuth(): array {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    
    if (!$authHeader || !preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
        jsonResponse(['error' => 'Missing or invalid Authorization header'], 401);
    }
    
    $token = trim($matches[1]);
    $pdo = db();
    
    // Find valid token
    $stmt = $pdo->prepare('
        SELECT u.* FROM api_tokens t 
        JOIN youth_users u ON u.id = t.user_id 
        WHERE t.token = ? AND t.expires_at > NOW() 
        LIMIT 1
    ');
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['error' => 'Invalid or expired token'], 401);
    }
    
    return $user;
}

/**
 * Generate random token
 */
function generateToken(): string {
    return bin2hex(random_bytes(32));
}

/**
 * Create new API token for user
 */
function createToken(int $userId): string {
    $pdo = db();
    $token = generateToken();
    $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days
    
    $pdo->prepare('INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, ?)')
        ->execute([$userId, $token, $expiresAt]);
    
    return $token;
}

/**
 * Delete token (logout)
 */
function deleteToken(string $token): bool {
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM api_tokens WHERE token = ?');
    return $stmt->execute([$token]);
}
