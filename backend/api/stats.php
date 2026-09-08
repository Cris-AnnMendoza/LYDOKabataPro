<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

$pdo = getDB();
$uid = (int)$_SESSION['user_id'];

// Pending assistance requests
$r1 = $pdo->prepare("SELECT COUNT(*) FROM assistance_requests WHERE submitted_by=? AND status NOT IN ('completed','declined')");
$r1->execute([$uid]);
$requests = (int)$r1->fetchColumn();

// Unread notifications
$r2 = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
$r2->execute([$uid]);
$notifications = (int)$r2->fetchColumn();

echo json_encode(['success' => true, 'requests' => $requests, 'notifications' => $notifications]);
