<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
  exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$email    = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password = $data['password'] ?? '';

if (!$email || !$password) {
  http_response_code(422);
  echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
  exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$email]);
$admin = $stmt->fetch();

if (!$admin) {
  http_response_code(404);
  echo json_encode(['success' => false, 'message' => 'Admin account not found.']);
  exit;
}

if (!password_verify($password, $admin['password'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
  exit;
}

// Generate secure token
$token     = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+8 hours'));

// Remove old sessions for this admin
$pdo->prepare('DELETE FROM admin_sessions WHERE admin_id = ?')->execute([$admin['id']]);

// Store new session
$pdo->prepare(
  'INSERT INTO admin_sessions (admin_id, token, expires_at) VALUES (?,?,?)'
)->execute([$admin['id'], $token, $expiresAt]);

// Update last login
$pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);

logActivity($admin['id'], 'LOGIN', 'Admin logged in from ' . ($_SERVER['REMOTE_ADDR'] ?? ''));

echo json_encode([
  'success' => true,
  'message' => 'Login successful.',
  'token'   => $token,
  'admin'   => [
    'id'        => $admin['id'],
    'full_name' => $admin['full_name'],
    'email'     => $admin['email'],
    'role'      => $admin['role'],
    'barangay'  => $admin['barangay'],
  ],
]);
