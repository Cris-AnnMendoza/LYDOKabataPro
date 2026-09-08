<?php
/**
 * GET /api/youth/events?filter=upcoming|history|all
 * POST /api/youth/events/checkin - Check in to event
 * POST /api/youth/events/checkout - Check out of event
 * Headers: Authorization: Bearer {token}
 */

require_once __DIR__ . '/bootstrap.php';

$user = requireAuth();
$pdo = db();
$userId = (int)$user['id'];

// GET - List events
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filter = $_GET['filter'] ?? 'upcoming';
    
    if ($filter === 'upcoming') {
        // Upcoming events (checkin_open=TRUE, event_date >= today)
        $stmt = $pdo->prepare('
            SELECT e.*, o.name as org_name
            FROM events e
            LEFT JOIN organizations o ON o.id = e.organization_id
            WHERE e.checkin_open=TRUE AND e.event_date >= CURRENT_DATE
            ORDER BY e.event_date ASC, e.event_start_time ASC NULLS LAST
            LIMIT 50
        ');
        $stmt->execute();
        $events = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'events' => $events]);
        
    } elseif ($filter === 'history') {
        // User's check-in history
        $stmt = $pdo->prepare('
            SELECT c.*, e.title, e.event_date, e.location, e.merit_points,
                cert.id as cert_id, cert.cert_number, cert.merit_awarded, cert.merit_points as cert_pts,
                c.checkin_photo, c.checkout_photo
            FROM event_checkins c
            JOIN events e ON e.id = c.event_id
            LEFT JOIN event_certificates cert ON cert.event_id = c.event_id AND cert.user_id = c.user_id
            WHERE c.user_id = ?
            ORDER BY c.checked_in_at DESC
            LIMIT 100
        ');
        $stmt->execute([$userId]);
        $history = $stmt->fetchAll();
        
        // Add photo URLs
        foreach ($history as &$item) {
            if ($item['checkin_photo']) {
                $item['checkin_photo_url'] = '/LYDO/lydo-system/shared/uploads/event_photos/' . $item['checkin_photo'];
            }
            if ($item['checkout_photo']) {
                $item['checkout_photo_url'] = '/LYDO/lydo-system/shared/uploads/event_photos/' . $item['checkout_photo'];
            }
        }
        
        jsonResponse(['success' => true, 'history' => $history]);
        
    } else {
        // All events
        $stmt = $pdo->query('SELECT e.*, o.name as org_name FROM events e LEFT JOIN organizations o ON o.id = e.organization_id ORDER BY e.event_date DESC LIMIT 100');
        $events = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'events' => $events]);
    }
}

// POST - Check in or check out
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = getJsonBody();
    $action = $body['action'] ?? '';
    $code = strtoupper(trim($body['code'] ?? ''));
    
    if (!in_array($action, ['checkin', 'checkout'])) {
        jsonResponse(['error' => 'Invalid action. Use "checkin" or "checkout"'], 400);
    }
    
    if (!$code) {
        jsonResponse(['error' => 'Event code is required'], 400);
    }
    
    // Check-in logic
    if ($action === 'checkin') {
        $evAll = $pdo->query('SELECT * FROM events WHERE checkin_open=TRUE')->fetchAll();
        $ev = null;
        
        foreach ($evAll as $c) {
            $eid = (int)$c['id'];
            $tok = $c['qr_token'] ?? '';
            $sc = strtoupper(trim($c['checkin_code'] ?? ''));
            
            // Static code match
            if ($sc && $code === $sc) { $ev = $c; break; }
            
            // Rotating code match (30-second window)
            if ($tok) {
                $w = floor(time() / 30);
                if ($code === strtoupper(substr(md5($eid . $tok . $w . 'CHECKIN'), 0, 6)) ||
                    $code === strtoupper(substr(md5($eid . $tok . ($w - 1) . 'CHECKIN'), 0, 6))) {
                    $ev = $c;
                    break;
                }
            }
        }
        
        if (!$ev) {
            jsonResponse(['error' => 'Invalid code. Use the code shown on screen.'], 400);
        }
        
        // Check 15-minute window
        $st = $ev['event_start_time'] ?? null;
        if ($st && strlen($st) > 0) {
            if (strlen($st) === 5) $st .= ':00';
            $startTs = strtotime(date('Y-m-d') . ' ' . $st);
            $deadlineTs = $startTs + 900; // +15 minutes
            $nowTs = time();
            if ($nowTs > $deadlineTs) {
                $deadlineStr = date('g:i A', $deadlineTs);
                jsonResponse(['error' => 'Check-in closed. The 15-minute window ended at ' . $deadlineStr . '. Hindi na pwede mag check-in kapag late.'], 400);
            }
        }
        
        $eid = (int)$ev['id'];
        
        // Check if already checked in
        $chk = $pdo->prepare('SELECT id FROM event_checkins WHERE event_id=? AND user_id=? LIMIT 1');
        $chk->execute([$eid, $userId]);
        if ($chk->fetch()) {
            jsonResponse(['error' => 'Already checked in to "' . $ev['title'] . '".'], 400);
        }
        
        // Insert check-in with timestamp
        $pdo->prepare('INSERT INTO event_checkins(event_id, user_id, checked_in_at, ip_address) VALUES(?,?,CURRENT_TIMESTAMP,?)')->execute([$eid, $userId, $_SERVER['REMOTE_ADDR'] ?? null]);
        
        // Handle photo upload (base64)
        if (!empty($body['photo'])) {
            $photoDir = __DIR__ . '/../../shared/uploads/event_photos/';
            if (!is_dir($photoDir)) mkdir($photoDir, 0755, true);
            
            $photoData = $body['photo'];
            if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $type)) {
                $photoData = substr($photoData, strpos($photoData, ',') + 1);
                $type = strtolower($type[1]);
                if (in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $photoData = base64_decode($photoData);
                    $fname = 'ci_' . $eid . '_' . $userId . '_' . time() . '.' . $type;
                    if (file_put_contents($photoDir . $fname, $photoData)) {
                        $pdo->prepare('UPDATE event_checkins SET checkin_photo=? WHERE event_id=? AND user_id=?')->execute([$fname, $eid, $userId]);
                    }
                }
            }
        }
        
        // Create certificate entry
        $cn = 'LYDO-EVT-' . date('Y') . '-' . str_pad($eid, 4, '0', STR_PAD_LEFT) . '-' . str_pad($userId, 4, '0', STR_PAD_LEFT);
        try {
            $pdo->prepare('INSERT INTO event_certificates(event_id, user_id, cert_number, merit_points) VALUES(?,?,?,?)')->execute([$eid, $userId, $cn, $ev['merit_points'] ?: 2]);
        } catch (PDOException $e) {}
        
        // Send notification
        try {
            $pdo->prepare('INSERT INTO notifications(user_id, title, message, type) VALUES(?,?,?,?)')->execute([$userId, 'Checked In: ' . $ev['title'], 'Checked in to "' . $ev['title'] . '". Check out after the event to earn merit points.', 'success']);
        } catch (PDOException $e) {}
        
        jsonResponse([
            'success' => true,
            'message' => 'Checked in successfully!',
            'event' => $ev['title'],
            'cert_no' => $cn,
            'pts' => (int)($ev['merit_points'] ?: 2),
            'event_id' => $eid
        ]);
    }
    
    // Check-out logic
    if ($action === 'checkout') {
        $evAll = $pdo->query('SELECT * FROM events WHERE checkin_open=TRUE')->fetchAll();
        $ev = null;
        
        foreach ($evAll as $c) {
            $eid = (int)$c['id'];
            $tok = $c['qr_token'] ?? '';
            if (!$tok) continue;
            
            // Rotating checkout code (30-second window)
            $w = floor(time() / 30);
            if ($code === strtoupper(substr(md5($eid . $tok . $w . 'CHECKOUT'), 0, 6)) ||
                $code === strtoupper(substr(md5($eid . $tok . ($w - 1) . 'CHECKOUT'), 0, 6))) {
                
                // Check end time if set
                $et = $c['event_end_time'] ?? null;
                if ($et && strlen($et) > 0) {
                    if (strlen($et) === 5) $et .= ':00';
                    if (date('H:i:s') < $et && empty($c['checkout_open'])) {
                        jsonResponse(['error' => 'Check-out not yet available. Opens at ' . date('g:i A', strtotime($et)) . '.'], 400);
                    }
                }
                
                $ev = $c;
                break;
            }
        }
        
        if (!$ev) {
            jsonResponse(['error' => 'Invalid checkout code. Use the red code shown on screen.'], 400);
        }
        
        $eid = (int)$ev['id'];
        
        // Check if user checked in
        $ci = $pdo->prepare('SELECT * FROM event_checkins WHERE event_id=? AND user_id=? LIMIT 1');
        $ci->execute([$eid, $userId]);
        $checkin = $ci->fetch();
        
        if (!$checkin) {
            jsonResponse(['error' => 'You have not checked in to this event yet.'], 400);
        }
        
        if ($checkin['checked_out_at']) {
            jsonResponse(['error' => 'Already checked out of "' . $ev['title'] . '".'], 400);
        }
        
        // Update checkout time
        $pdo->prepare('UPDATE event_checkins SET checked_out_at=CURRENT_TIMESTAMP WHERE event_id=? AND user_id=?')->execute([$eid, $userId]);
        
        // Handle photo upload (base64)
        if (!empty($body['photo'])) {
            $photoDir = __DIR__ . '/../../shared/uploads/event_photos/';
            if (!is_dir($photoDir)) mkdir($photoDir, 0755, true);
            
            $photoData = $body['photo'];
            if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $type)) {
                $photoData = substr($photoData, strpos($photoData, ',') + 1);
                $type = strtolower($type[1]);
                if (in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $photoData = base64_decode($photoData);
                    $fname = 'co_' . $eid . '_' . $userId . '_' . time() . '.' . $type;
                    if (file_put_contents($photoDir . $fname, $photoData)) {
                        $pdo->prepare('UPDATE event_checkins SET checkout_photo=? WHERE event_id=? AND user_id=?')->execute([$fname, $eid, $userId]);
                    }
                }
            }
        }
        
        $pts = (int)($ev['merit_points'] ?: 2);
        
        // Get or create certificate
        $cs = $pdo->prepare('SELECT * FROM event_certificates WHERE event_id=? AND user_id=? LIMIT 1');
        $cs->execute([$eid, $userId]);
        $cert = $cs->fetch();
        
        if (!$cert) {
            $cn = 'LYDO-EVT-' . date('Y') . '-' . str_pad($eid, 4, '0', STR_PAD_LEFT) . '-' . str_pad($userId, 4, '0', STR_PAD_LEFT);
            try {
                $pdo->prepare('INSERT INTO event_certificates(event_id, user_id, cert_number, merit_points, merit_awarded) VALUES(?,?,?,?,TRUE)')->execute([$eid, $userId, $cn, $pts]);
                $certId = (int)$pdo->lastInsertId();
            } catch (PDOException $e) {
                $cn = '';
                $certId = 0;
            }
        } else {
            $cn = $cert['cert_number'];
            $certId = (int)$cert['id'];
            if (!$cert['merit_awarded']) {
                $pdo->prepare('UPDATE event_certificates SET merit_awarded=TRUE WHERE id=?')->execute([$certId]);
            }
        }
        
        // Award personal merit points (if not already awarded)
        $al = $pdo->prepare('SELECT id FROM user_merit_logs WHERE user_id=? AND event_id=? AND category=? LIMIT 1');
        $al->execute([$userId, $eid, 'event_attendance']);
        if (!$al->fetch()) {
            $pdo->prepare('INSERT INTO user_merit_logs(user_id, points, type, reason, category, event_id, cert_id) VALUES(?,?,?,?,?,?,?)')->execute([$userId, $pts, 'merit', 'Event checkout: ' . $cn, 'event_attendance', $eid, $certId ?? 0]);
        }
        
        // Award org merit points (only ONCE per event)
        $userOrg = $user['organization_name'] ?? '';
        if ($userOrg) {
            $orgRow = $pdo->prepare('SELECT id FROM organizations WHERE name=? LIMIT 1');
            $orgRow->execute([$userOrg]);
            $orgRow = $orgRow->fetch();
            if ($orgRow) {
                $orgId = (int)$orgRow['id'];
                $orgCheck = $pdo->prepare('SELECT id FROM org_merit_logs WHERE organization_id=? AND event_id=? AND category=? LIMIT 1');
                $orgCheck->execute([$orgId, $eid, 'event_attendance']);
                if (!$orgCheck->fetch()) {
                    $pdo->prepare('INSERT INTO org_merit_logs(organization_id, points, type, reason, category, event_id, created_at) VALUES(?,?,?,?,?,?,CURRENT_TIMESTAMP)')->execute([$orgId, $pts, 'merit', 'Event attendance: ' . $ev['title'], 'event_attendance', $eid]);
                }
            }
        }
        
        // Send notification
        try {
            $pdo->prepare('INSERT INTO notifications(user_id, title, message, type) VALUES(?,?,?,?)')->execute([$userId, 'Merit Points Awarded!', '+' . $pts . ' points for "' . $ev['title'] . '". Certificate ready.', 'success']);
        } catch (PDOException $e) {}
        
        jsonResponse([
            'success' => true,
            'message' => 'Checked out successfully! Merit points awarded.',
            'event' => $ev['title'],
            'cert_no' => $cn,
            'pts' => $pts,
            'event_id' => $eid,
            'cert_id' => $certId ?? 0
        ]);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
