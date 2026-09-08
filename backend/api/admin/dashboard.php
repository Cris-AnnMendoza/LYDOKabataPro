<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/admin_auth.php';
require_once __DIR__ . '/../../config/roles.php';

$admin = requireAdminAuth();
$pdo   = getDB();

$barangayFilter = '';
$params         = [];

// Barangay admins only see their own barangay
if ($admin['role'] === 'barangay_admin' && $admin['barangay']) {
  $barangayFilter = 'WHERE barangay = ?';
  $params[]       = $admin['barangay'];
}

$total = $pdo->prepare("SELECT COUNT(*) FROM youth_users $barangayFilter");
$total->execute($params);

$thisMonth = $pdo->prepare(
  "SELECT COUNT(*) FROM youth_users
   WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())
   " . ($barangayFilter ? "AND barangay = ?" : "")
);
$thisMonth->execute($params);

$byGender = $pdo->prepare(
  "SELECT gender, COUNT(*) as count FROM youth_users $barangayFilter GROUP BY gender"
);
$byGender->execute($params);

$byBarangay = $pdo->prepare(
  "SELECT barangay, COUNT(*) as count FROM youth_users
   " . ($barangayFilter ?: '') . "
   GROUP BY barangay ORDER BY count DESC LIMIT 10"
);
$byBarangay->execute($params);

$byClassification = $pdo->prepare(
  "SELECT youth_classification, COUNT(*) as count FROM youth_users
   $barangayFilter GROUP BY youth_classification"
);
$byClassification->execute($params);

$recent = $pdo->prepare(
  "SELECT id, first_name, last_name, email, barangay, created_at
   FROM youth_users $barangayFilter ORDER BY created_at DESC LIMIT 5"
);
$recent->execute($params);

echo json_encode([
  'success'           => true,
  'total_users'       => (int)$total->fetchColumn(),
  'new_this_month'    => (int)$thisMonth->fetchColumn(),
  'by_gender'         => $byGender->fetchAll(),
  'by_barangay'       => $byBarangay->fetchAll(),
  'by_classification' => $byClassification->fetchAll(),
  'recent_users'      => $recent->fetchAll(),
]);
