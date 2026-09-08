<?php
/**
 * GET /api/youth/notifications - Get notifications list
 * POST /api/youth/notifications/mark-read - Mark notification(s) as read
 * Headers: Authorization: Bearer {token}
 */

require_once __DIR__ . '/bootstrap.php';

$user = requireAuth();
$pdo = db();
$userId = (int)$user['id'];

// GET - List notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = (int)($_GET['limit'] ?? 50);
    $limit = min($limit, 100); // Max 100
    
    $stmt = $pdo->prepare('
        SELECT id, title, message, type, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ');
    $stmt->execute([$userId, $limit]);
    $notifications = $stmt->fetchAll();
    
    jsonResponse(['success' => true, 'notifications' => $notifications]);
}

// POST - Mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = getJsonBody();
    $notifId = (int)($body['notification_id'] ?? 0);
    $markAll = $body['mark_all'] ?? false;
    
    if ($markAll) {
        // Mark all as read
        $stmt = $pdo->prepare('UPDATE notifications SET is_read=TRUE WHERE user_id = ? AND is_read=FALSE');
        $stmt->execute([$userId]);
        
        jsonResponse(['success' => true, 'message' => 'All notifications marked as read']);
        
    } elseif ($notifId > 0) {
        // Mark specific notification as read
        $stmt = $pdo->prepare('UPDATE notifications SET is_read=TRUE WHERE id = ? AND user_id = ?');
        $stmt->execute([$notifId, $userId]);
        
        jsonResponse(['success' => true, 'message' => 'Notification marked as read']);
        
    } else {
        jsonResponse(['error' => 'Invalid request'], 400);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
