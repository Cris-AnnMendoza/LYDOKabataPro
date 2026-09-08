<?php
/**
 * POST /api/youth/login
 * Login endpoint for youth users
 * Body: { "email": "user@example.com", "password": "password123" }
 * Returns: { "success": true, "token": "...", "user": {...} }
 */

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$body = getJsonBody();
$email = trim($body['email'] ?? '');
$password = trim($body['password'] ?? '');

if (!$email || !$password) {
    jsonResponse(['error' => 'Email and password are required'], 400);
}

$pdo = db();

// Find user by email
$stmt = $pdo->prepare('SELECT * FROM youth_users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    jsonResponse(['error' => 'Invalid email or password'], 401);
}

// Generate API token
$token = createToken((int)$user['id']);

// Remove sensitive data
unset($user['password']);

jsonResponse([
    'success' => true,
    'token' => $token,
    'user' => $user
]);
