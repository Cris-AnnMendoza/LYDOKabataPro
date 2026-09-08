<?php
session_start();
require_once __DIR__ . '/shared/config.php';

// Check if logged in
if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM youth_users WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Get available events for testing
$eventsStmt = $pdo->prepare("
    SELECT e.*, o.name as org_name 
    FROM events e 
    LEFT JOIN organizations o ON o.id = e.organization_id 
    WHERE e.checkin_open = TRUE 
    ORDER BY e.event_date DESC 
    LIMIT 10
");
$eventsStmt->execute();
$events = $eventsStmt->fetchAll();

// Handle QR scan simulation
$scanResult = null;
$scanError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simulate_scan'])) {
    $eventId = (int)$_POST['event_id'];
    $userId = $user['id'];
    
    // Get event details
    $eventStmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch();
    
    if (!$event) {
        $scanError = 'Event not found';
    } elseif (!$event['checkin_open']) {
        $scanError = 'Check-in is not open for this event';
    } else {
        // Check if already checked in
        $checkStmt = $pdo->prepare('SELECT * FROM event_checkins WHERE event_id = ? AND user_id = ? LIMIT 1');
        $checkStmt->execute([$eventId, $userId]);
        $existingCheckin = $checkStmt->fetch();
        
        if ($existingCheckin) {
            // Already checked in - this is a check-out
            if ($existingCheckin['checked_out_at']) {
                $scanError = 'Already checked out from this event';
            } else {
                // Perform checkout
                $updateStmt = $pdo->prepare('UPDATE event_checkins SET checked_out_at = NOW() WHERE id = ?');
                $updateStmt->execute([$existingCheckin['id']]);
                
                $scanResult = [
                    'type' => 'checkout',
                    'event' => $event['title'],
                    'time' => date('h:i A'),
                    'message' => 'Successfully checked out!'
                ];
            }
        } else {
            // New check-in
            $insertStmt = $pdo->prepare('INSERT INTO event_checkins (event_id, user_id, checked_in_at, ip_address) VALUES (?, ?, NOW(), ?)');
            $insertStmt->execute([$eventId, $userId, $_SERVER['REMOTE_ADDR'] ?? null]);
            
            // Award merit points on check-in
            if ($event['merit_points'] > 0) {
                $meritStmt = $pdo->prepare('INSERT INTO user_merit_logs (user_id, event_id, points, type, reason, awarded_by) VALUES (?, ?, ?, ?, ?, ?)');
                $meritStmt->execute([
                    $userId,
                    $eventId,
                    $event['merit_points'],
                    'merit',
                    'Event attendance: ' . $event['title'],
                    1 // System/Admin
                ]);
            }
            
            $scanResult = [
                'type' => 'checkin',
                'event' => $event['title'],
                'time' => date('h:i A'),
                'merit' => $event['merit_points'],
                'message' => 'Successfully checked in!'
            ];
        }
    }
}

// Get user's recent check-ins
$recentStmt = $pdo->prepare("
    SELECT 
        ec.*,
        e.title as event_title,
        e.event_date,
        e.merit_points
    FROM event_checkins ec
    JOIN events e ON e.id = ec.event_id
    WHERE ec.user_id = ?
    ORDER BY ec.checked_in_at DESC
    LIMIT 5
");
$recentStmt->execute([$user['id']]);
$recentCheckins = $recentStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>QR Scanner Test – LYDO</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--blue:#1565c0;--blue-dark:#0d3b6e;--blue-light:#1e88e5;--blue-pale:#e3f2fd;--green:#2e7d32;--green-light:#43a047;--green-pale:#e8f5e9;--red:#c62828;--red-pale:#ffebee;--orange:#e65100;--orange-pale:#fff3e0;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;--gray-400:#94a3b8;--gray-600:#475569;--gray-800:#1e293b}
body{font-family:Inter,sans-serif;color:var(--gray-800);background:var(--gray-50);padding:20px}
.container{max-width:900px;margin:0 auto}
.header{background:linear-gradient(135deg,var(--blue-dark),var(--blue));border-radius:16px;padding:24px 28px;color:#fff;margin-bottom:20px;box-shadow:0 4px 16px rgba(21,101,192,.2)}
.header h1{font-size:1.6rem;font-weight:800;margin-bottom:6px}
.header p{font-size:.9rem;opacity:.85}
.card{background:#fff;border-radius:12px;border:1px solid var(--gray-200);box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:16px;overflow:hidden}
.card-header{padding:16px 20px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;gap:8px;font-size:1rem;font-weight:700;color:var(--gray-800)}
.card-header i{color:var(--blue)}
.card-body{padding:20px}
.btn-primary{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:.9rem;font-weight:600;cursor:pointer;transition:.2s;text-decoration:none}
.btn-primary:hover{background:var(--blue-dark);transform:translateY(-2px);box-shadow:0 4px 12px rgba(21,101,192,.3)}
.btn-success{background:var(--green)}
.btn-success:hover{background:var(--green-light)}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:.85rem;font-weight:600;color:var(--gray-700);margin-bottom:6px}
.form-group select{width:100%;padding:12px 14px;border:1.5px solid var(--gray-200);border-radius:10px;font-family:inherit;font-size:.9rem;color:var(--gray-800);background:var(--gray-50);outline:none;transition:.2s}
.form-group select:focus{border-color:var(--blue-light);box-shadow:0 0 0 3px rgba(30,136,229,.1);background:#fff}
.alert{padding:16px 20px;border-radius:10px;margin-bottom:16px;display:flex;align-items:center;gap:12px;font-size:.9rem}
.alert-success{background:var(--green-pale);color:var(--green);border:1px solid var(--green)}
.alert-error{background:var(--red-pale);color:var(--red);border:1px solid var(--red)}
.alert i{font-size:1.2rem}
.result-card{padding:20px;border-radius:12px;text-align:center;margin-bottom:20px}
.result-card.checkin{background:var(--green-pale);border:2px solid var(--green)}
.result-card.checkout{background:var(--blue-pale);border:2px solid var(--blue)}
.result-card .icon{font-size:3rem;margin-bottom:12px}
.result-card.checkin .icon{color:var(--green)}
.result-card.checkout .icon{color:var(--blue)}
.result-card h2{font-size:1.4rem;font-weight:800;margin-bottom:8px}
.result-card.checkin h2{color:var(--green)}
.result-card.checkout h2{color:var(--blue)}
.result-card p{font-size:.95rem;color:var(--gray-700);margin-bottom:4px}
.result-card .merit{display:inline-flex;align-items:center;gap:6px;background:var(--green);color:#fff;padding:8px 16px;border-radius:50px;font-size:.85rem;font-weight:700;margin-top:12px}
table{width:100%;border-collapse:collapse;font-size:.85rem}
table th{padding:12px 14px;text-align:left;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--gray-600);background:var(--gray-50);border-bottom:1px solid var(--gray-200)}
table td{padding:12px 14px;border-bottom:1px solid var(--gray-100);color:var(--gray-800)}
table tr:last-child td{border-bottom:none}
table tr:hover td{background:var(--gray-50)}
.badge{display:inline-block;padding:4px 10px;border-radius:50px;font-size:.7rem;font-weight:700}
.badge.green{background:var(--green-pale);color:var(--green)}
.badge.blue{background:var(--blue-pale);color:var(--blue)}
.badge.gray{background:var(--gray-100);color:var(--gray-600)}
.empty{text-align:center;padding:32px;color:var(--gray-400);font-style:italic}
.qr-display{text-align:center;padding:20px;background:var(--gray-50);border-radius:10px;margin-bottom:20px}
.qr-display img{max-width:200px;border:3px solid var(--blue);border-radius:10px;padding:10px;background:#fff}
.user-info{display:flex;align-items:center;gap:12px;padding:16px;background:var(--blue-pale);border-radius:10px;margin-bottom:20px}
.user-info i{font-size:2rem;color:var(--blue)}
.user-info div{flex:1}
.user-info .name{font-size:1rem;font-weight:700;color:var(--gray-800)}
.user-info .email{font-size:.8rem;color:var(--gray-600)}
</style>
</head>
<body>

<div class="container">
  <div class="header">
    <h1><i class="fas fa-qrcode"></i> QR Scanner Test</h1>
    <p>Test event check-in and check-out functionality</p>
  </div>

  <!-- User Info -->
  <div class="card">
    <div class="card-header">
      <i class="fas fa-user"></i>
      <span>Logged In As</span>
    </div>
    <div class="card-body">
      <div class="user-info">
        <i class="fas fa-user-circle"></i>
        <div>
          <div class="name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
          <div class="email"><?= htmlspecialchars($user['email']) ?></div>
        </div>
      </div>
      
      <!-- Display User QR Code -->
      <div class="qr-display">
        <h3 style="font-size:.9rem;font-weight:700;color:var(--gray-700);margin-bottom:12px">Your QR Code</h3>
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($user['qr_token']) ?>" alt="User QR Code">
        <p style="font-size:.75rem;color:var(--gray-600);margin-top:8px">QR Token: <code style="background:var(--gray-100);padding:4px 8px;border-radius:6px;font-family:monospace"><?= htmlspecialchars($user['qr_token']) ?></code></p>
      </div>
    </div>
  </div>

  <!-- Scan Result -->
  <?php if ($scanResult): ?>
  <div class="result-card <?= $scanResult['type'] ?>">
    <div class="icon">
      <?php if ($scanResult['type'] === 'checkin'): ?>
        <i class="fas fa-check-circle"></i>
      <?php else: ?>
        <i class="fas fa-sign-out-alt"></i>
      <?php endif; ?>
    </div>
    <h2><?= htmlspecialchars($scanResult['message']) ?></h2>
    <p><strong><?= htmlspecialchars($scanResult['event']) ?></strong></p>
    <p><?= $scanResult['type'] === 'checkin' ? 'Checked in' : 'Checked out' ?> at <?= $scanResult['time'] ?></p>
    <?php if ($scanResult['type'] === 'checkin' && $scanResult['merit'] > 0): ?>
      <div class="merit">
        <i class="fas fa-star"></i>
        <span>+<?= $scanResult['merit'] ?> Merit Points Earned!</span>
      </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($scanError): ?>
  <div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    <div><strong>Error:</strong> <?= htmlspecialchars($scanError) ?></div>
  </div>
  <?php endif; ?>

  <!-- Simulate QR Scan -->
  <div class="card">
    <div class="card-header">
      <i class="fas fa-camera"></i>
      <span>Simulate QR Scan</span>
    </div>
    <div class="card-body">
      <?php if (empty($events)): ?>
        <div class="empty">
          <i class="fas fa-inbox" style="font-size:2rem;margin-bottom:10px;opacity:.5"></i>
          <p>No events available with open check-in</p>
        </div>
      <?php else: ?>
        <form method="POST">
          <div class="form-group">
            <label>Select Event to Check In/Out</label>
            <select name="event_id" required>
              <option value="">-- Select an event --</option>
              <?php foreach ($events as $event): ?>
                <option value="<?= $event['id'] ?>">
                  <?= htmlspecialchars($event['title']) ?> 
                  <?php if ($event['org_name']): ?>
                    (<?= htmlspecialchars($event['org_name']) ?>)
                  <?php endif; ?>
                  - <?= date('M j, Y', strtotime($event['event_date'])) ?>
                  - +<?= $event['merit_points'] ?> pts
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" name="simulate_scan" class="btn-primary">
            <i class="fas fa-qrcode"></i>
            Scan QR Code
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Check-ins -->
  <div class="card">
    <div class="card-header">
      <i class="fas fa-history"></i>
      <span>Recent Check-ins</span>
    </div>
    <?php if (empty($recentCheckins)): ?>
      <div class="empty">No check-in history yet</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Event</th>
            <th>Check-in Time</th>
            <th>Check-out Time</th>
            <th>Merit Points</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentCheckins as $checkin): ?>
          <tr>
            <td><strong><?= htmlspecialchars($checkin['event_title']) ?></strong></td>
            <td><?= date('M j, Y g:i A', strtotime($checkin['checked_in_at'])) ?></td>
            <td>
              <?php if ($checkin['checked_out_at']): ?>
                <?= date('M j, Y g:i A', strtotime($checkin['checked_out_at'])) ?>
              <?php else: ?>
                <span style="color:var(--gray-400)">Not yet</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge green">+<?= $checkin['merit_points'] ?></span>
            </td>
            <td>
              <?php if ($checkin['checked_out_at']): ?>
                <span class="badge blue">Completed</span>
              <?php else: ?>
                <span class="badge gray">Checked In</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div style="text-align:center;margin-top:24px">
    <a href="shared/youth/dashboard.php" class="btn-primary">
      <i class="fas fa-arrow-left"></i>
      Back to Dashboard
    </a>
  </div>
</div>

</body>
</html>
