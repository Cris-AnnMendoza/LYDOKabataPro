<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$email    = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password = $data['password'] ?? '';

if (!$email || !$password) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM youth_users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Account not found. Please register first.']);
    exit;
}

if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
    exit;
}

// ── Start session ──────────────────────────────────────────
session_regenerate_id(true);
$_SESSION['user_id']    = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];

echo json_encode([
    'success' => true,
    'message' => 'Login successful.',
    'user'    => [
        'id'         => $user['id'],
        'name'       => $_SESSION['user_name'],
        'email'      => $user['email'],
        'barangay'   => $user['barangay'],
        'profile_picture' => $user['profile_picture'],
    ],
]);
