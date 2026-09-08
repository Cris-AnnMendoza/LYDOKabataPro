<?php
/**
 * GET /api/youth/merit - Get merit logs
 * Headers: Authorization: Bearer {token}
 */

require_once __DIR__ . '/bootstrap.php';

$user = requireAuth();
$pdo = db();
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$limit = (int)($_GET['limit'] ?? 50);
$limit = min($limit, 100); // Max 100

// Get merit logs
$stmt = $pdo->prepare('
    SELECT id, points, type, reason, category, event_id, created_at
    FROM user_merit_logs
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT ?
');
$stmt->execute([$userId, $limit]);
$logs = $stmt->fetchAll();

// Calculate totals
$stmt = $pdo->prepare('SELECT COALESCE(SUM(points),0) as total_merit FROM user_merit_logs WHERE user_id=? AND type="merit"');
$stmt->execute([$userId]);
$totalMerit = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(ABS(points)),0) as total_demerit FROM user_merit_logs WHERE user_id=? AND type="demerit"');
$stmt->execute([$userId]);
$totalDemerit = (int)$stmt->fetchColumn();

jsonResponse([
    'success' => true,
    'totals' => [
        'merit' => $totalMerit,
        'demerit' => $totalDemerit,
        'net' => $totalMerit - $totalDemerit
    ],
    'logs' => $logs
]);
