<?php
/**
 * POST /api/youth/logout
 * Logout endpoint - invalidates token
 * Headers: Authorization: Bearer {token}
 * Returns: { "success": true }
 */

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

if ($authHeader && preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
    $token = trim($matches[1]);
    deleteToken($token);
}

jsonResponse(['success' => true]);
