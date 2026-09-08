<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/admin_auth.php';
require_once __DIR__ . '/../../config/roles.php';

$admin  = requireAdminAuth();
requirePermission($admin, 'manage_admins');   // super_admin only

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $stmt = $pdo->query(
    'SELECT id, full_name, email, role, barangay, is_active, last_login, created_at
     FROM admin_users ORDER BY created_at DESC'
  );
  echo json_encode(['success' => true, 'admins' => $stmt->fetchAll()]);
  exit;
}

if ($method === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);
  $required = ['full_name','email','password','role'];
  foreach ($required as $f) {
    if (empty($data[$f])) {
      http_response_code(422);
      echo json_encode(['success'=>false,'message'=>"Field '$f' is required."]);
      exit;
    }
  }
  $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
  $check = $pdo->prepare('SELECT id FROM admin_users WHERE email = ?');
  $check->execute([$email]);
  if ($check->fetch()) {
    http_response_code(409);
    echo json_encode(['success'=>false,'message'=>'Email already exists.']);
    exit;
  }
  $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost'=>12]);
  $pdo->prepare(
    'INSERT INTO admin_users (full_name, email, password, role, barangay) VALUES (?,?,?,?,?)'
  )->execute([
    trim($data['full_name']), $email, $hash,
    $data['role'], $data['barangay'] ?? null,
  ]);
  logActivity($admin['id'], 'CREATE_ADMIN', "Created admin: $email role: {$data['role']}");
  echo json_encode(['success'=>true,'message'=>'Admin account created.']);
  exit;
}

if ($method === 'PUT') {
  $data = json_decode(file_get_contents('php://input'), true);
  $id   = (int)($data['id'] ?? 0);
  if (!$id) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'ID required.']); exit; }
  $fields = []; $params = [];
  if (!empty($data['full_name'])) { $fields[] = 'full_name = ?'; $params[] = $data['full_name']; }
  if (!empty($data['role']))      { $fields[] = 'role = ?';      $params[] = $data['role']; }
  if (isset($data['is_active']))  { $fields[] = 'is_active = ?'; $params[] = (int)$data['is_active']; }
  if (!empty($data['barangay']))  { $fields[] = 'barangay = ?';  $params[] = $data['barangay']; }
  if (!empty($data['password']))  { $fields[] = 'password = ?';  $params[] = password_hash($data['password'], PASSWORD_BCRYPT); }
  if (!$fields) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Nothing to update.']); exit; }
  $params[] = $id;
  $pdo->prepare('UPDATE admin_users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
  logActivity($admin['id'], 'UPDATE_ADMIN', "Updated admin ID $id");
  echo json_encode(['success'=>true,'message'=>'Admin updated.']);
  exit;
}

if ($method === 'DELETE') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id === $admin['id']) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Cannot delete yourself.']); exit; }
  $pdo->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);
  logActivity($admin['id'], 'DELETE_ADMIN', "Deleted admin ID $id");
  echo json_encode(['success'=>true,'message'=>'Admin deleted.']);
  exit;
}

http_response_code(405);
echo json_encode(['success'=>false,'message'=>'Method not allowed.']);
