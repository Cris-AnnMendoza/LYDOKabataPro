<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/admin_auth.php';

$admin = requireAdminAuth();
logActivity($admin['id'], 'LOGOUT', 'Admin logged out.');

$header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/Bearer\s+(.+)/i', $header, $m)) {
  getDB()->prepare('DELETE FROM admin_sessions WHERE token = ?')->execute([trim($m[1])]);
}

echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
