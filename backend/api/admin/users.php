<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/admin_auth.php';
require_once __DIR__ . '/../../config/roles.php';

$admin = requireAdminAuth();
$pdo   = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: list youth users ──────────────────────────────────
if ($method === 'GET') {
  $canViewAll = hasPermission($admin['role'], 'view_all_users');
  $barangayOnly = hasPermission($admin['role'], 'view_barangay_users') && !$canViewAll;

  $where  = [];
  $params = [];

  if ($barangayOnly && $admin['barangay']) {
    $where[]  = 'barangay = ?';
    $params[] = $admin['barangay'];
  }

  // Search
  if (!empty($_GET['search'])) {
    $s = '%' . $_GET['search'] . '%';
    $where[]  = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR barangay LIKE ?)';
    $params   = array_merge($params, [$s, $s, $s, $s]);
  }

  // Filter by barangay
  if (!empty($_GET['barangay']) && $canViewAll) {
    $where[]  = 'barangay = ?';
    $params[] = $_GET['barangay'];
  }

  $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
  $page     = max(1, (int)($_GET['page'] ?? 1));
  $limit    = 20;
  $offset   = ($page - 1) * $limit;

  $total = $pdo->prepare("SELECT COUNT(*) FROM youth_users $whereSQL");
  $total->execute($params);
  $totalCount = (int)$total->fetchColumn();

  $stmt = $pdo->prepare(
    "SELECT id, first_name, last_name, email, gender, barangay,
            youth_classification, educational_status, created_at
     FROM youth_users $whereSQL
     ORDER BY created_at DESC LIMIT $limit OFFSET $offset"
  );
  $stmt->execute($params);
  $users = $stmt->fetchAll();

  echo json_encode([
    'success' => true,
    'users'   => $users,
    'total'   => $totalCount,
    'page'    => $page,
    'pages'   => ceil($totalCount / $limit),
  ]);
  exit;
}

// ── DELETE: remove a youth user ────────────────────────────
if ($method === 'DELETE') {
  requirePermission($admin, 'delete_users');
  $id   = (int)($_GET['id'] ?? 0);
  if (!$id) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'ID required.']); exit; }
  $pdo->prepare('DELETE FROM youth_users WHERE id = ?')->execute([$id]);
  logActivity($admin['id'], 'DELETE_USER', "Deleted youth user ID $id");
  echo json_encode(['success' => true, 'message' => 'User deleted.']);
  exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
