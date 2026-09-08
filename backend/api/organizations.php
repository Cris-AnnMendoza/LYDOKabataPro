<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDB();
$stmt = $pdo->query('SELECT id, name, category FROM organizations WHERE is_active=1 ORDER BY name');
$orgs = $stmt->fetchAll();

echo json_encode(['success' => true, 'organizations' => $orgs]);
