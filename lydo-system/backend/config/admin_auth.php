<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/roles.php';

/**
 * Validate the admin session token from the Authorization header.
 * Returns the admin row or exits with 401.
 */
function requireAdminAuth(): array {
  $pdo = getDB();

  $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  $token  = '';
  if (preg_match('/Bearer\s+(.+)/i', $header, $m)) {
    $token = trim($m[1]);
  }

  if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. No token provided.']);
    exit;
  }

  $stmt = $pdo->prepare(
    'SELECT a.*, s.expires_at
     FROM admin_sessions s
     JOIN admin_users a ON a.id = s.admin_id
     WHERE s.token = ? AND a.is_active = TRUE
     LIMIT 1'
  );
  $stmt->execute([$token]);
  $admin = $stmt->fetch();

  if (!$admin || strtotime($admin['expires_at']) < time()) {
    // Clean up expired token
    if ($admin) {
      $pdo->prepare('DELETE FROM admin_sessions WHERE token = ?')->execute([$token]);
    }
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
  }

  return $admin;
}

function logActivity(int $adminId, string $action, string $details = ''): void {
  $pdo = getDB();
  $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
  $pdo->prepare(
    'INSERT INTO admin_activity_log (admin_id, action, details, ip_address) VALUES (?,?,?,?)'
  )->execute([$adminId, $action, $details, $ip]);
}
