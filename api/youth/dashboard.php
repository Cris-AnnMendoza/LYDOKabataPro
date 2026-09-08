<?php
/**
 * GET /api/youth/dashboard
 * Get dashboard stats: merit points, org info, requests, notifications
 * Headers: Authorization: Bearer {token}
 */

require_once __DIR__ . '/bootstrap.php';

$user = requireAuth();
$pdo = db();
$userId = (int)$user['id'];

// Personal merit points
$stmt = $pdo->prepare('SELECT COALESCE(SUM(points),0) as total_merit FROM user_merit_logs WHERE user_id=? AND type="merit"');
$stmt->execute([$userId]);
$myMerit = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(ABS(points)),0) as total_demerit FROM user_merit_logs WHERE user_id=? AND type="demerit"');
$stmt->execute([$userId]);
$myDemerit = (int)$stmt->fetchColumn();

// Pending assistance requests
$stmt = $pdo->prepare('SELECT COUNT(*) FROM assistance_requests WHERE submitted_by=? AND status NOT IN ("completed","declined")');
$stmt->execute([$userId]);
$pendingReqs = (int)$stmt->fetchColumn();

// Unread notifications
$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=FALSE');
$stmt->execute([$userId]);
$notifCount = (int)$stmt->fetchColumn();

// Event check-ins
$stmt = $pdo->prepare('SELECT COUNT(*) FROM event_checkins WHERE user_id=?');
$stmt->execute([$userId]);
$eventsAttended = (int)$stmt->fetchColumn();

// Organization data (if user belongs to one)
$orgData = null;
$orgName = $user['organization_name'] ?? '';
if ($orgName) {
    $stmt = $pdo->prepare('
        SELECT o.*, 
            COALESCE(SUM(CASE WHEN m.type="merit" THEN m.points ELSE 0 END),0) as total_merit,
            COALESCE(SUM(CASE WHEN m.type="demerit" THEN ABS(m.points) ELSE 0 END),0) as total_demerit
        FROM organizations o
        LEFT JOIN org_merit_logs m ON m.organization_id=o.id
        WHERE o.name=?
        GROUP BY o.id
        LIMIT 1
    ');
    $stmt->execute([$orgName]);
    $orgData = $stmt->fetch();
    if ($orgData) {
        $orgData['total_merit'] = (int)$orgData['total_merit'];
        $orgData['total_demerit'] = (int)$orgData['total_demerit'];
        $orgData['net_score'] = $orgData['total_merit'] - $orgData['total_demerit'];
    }
}

jsonResponse([
    'success' => true,
    'stats' => [
        'my_merit' => $myMerit,
        'my_demerit' => $myDemerit,
        'net_score' => $myMerit - $myDemerit,
        'pending_requests' => $pendingReqs,
        'unread_notifications' => $notifCount,
        'events_attended' => $eventsAttended
    ],
    'organization' => $orgData
]);
