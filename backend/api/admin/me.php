<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/admin_auth.php';
require_once __DIR__ . '/../../config/roles.php';

$admin = requireAdminAuth();

echo json_encode([
  'success' => true,
  'admin'   => [
    'id'          => $admin['id'],
    'full_name'   => $admin['full_name'],
    'email'       => $admin['email'],
    'role'        => $admin['role'],
    'barangay'    => $admin['barangay'],
    'last_login'  => $admin['last_login'],
    'permissions' => ROLES[$admin['role']] ?? [],
  ],
]);
