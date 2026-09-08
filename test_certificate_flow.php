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

$message = null;
$error = null;
$certificate = null;
$flowStep = 0; // 0=select, 1=checked-in, 2=checked-out, 3=certificate

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $eventId = (int)($_POST['event_id'] ?? 0);
    $userId = $user['id'];
    
    // Get event
    $eventStmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch();
    
    if (!$event) {
        $error = 'Event not found';
    } else {
        // Check existing check-in
        $checkStmt = $pdo->prepare('SELECT * FROM event_checkins WHERE event_id = ? AND user_id = ? LIMIT 1');
        $checkStmt->execute([$eventId, $userId]);
        $existingCheckin = $checkStmt->fetch();
        
        if ($action === 'checkin') {
            if ($existingCheckin) {
                $error = 'Already checked in to this event';
                $flowStep = 1;
            } else {
                // Perform check-in
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
                
                $message = '✅ Successfully checked in! Merit points awarded: +' . $event['merit_points'];
                $flowStep = 1;
            }
        } elseif ($action === 'checkout') {
            if (!$existingCheckin) {
                $error = 'Must check in first before checking out';
            } elseif ($existingCheckin['checked_out_at']) {
                $error = 'Already checked out from this event';
                $flowStep = 2;
            } else {
                // Perform checkout
                $updateStmt = $pdo->prepare('UPDATE event_checkins SET checked_out_at = NOW() WHERE id = ?');
                $updateStmt->execute([$existingCheckin['id']]);
                
                $message = '✅ Successfully checked out!';
                $flowStep = 2;
            }
        } elseif ($action === 'generate_cert') {
            if (!$existingCheckin) {
                $error = 'Must check in first';
            } elseif (!$existingCheckin['checked_out_at']) {
                $error = 'Must check out before generating certificate';
            } else {
                // Check if certificate already exists
                $certStmt = $pdo->prepare('SELECT * FROM event_certificates WHERE event_id = ? AND user_id = ? LIMIT 1');
                $certStmt->execute([$eventId, $userId]);
                $existingCert = $certStmt->fetch();
                
                if ($existingCert) {
                    $certificate = $existingCert;
                    $message = '📜 Certificate already exists!';
                    $flowStep = 3;
                } else {
                    // Generate certificate number
                    $certNumber = 'LYDO-' . strtoupper(substr(md5($eventId . $userId . time()), 0, 10));
                    
                    // Insert certificate
                    $insertCertStmt = $pdo->prepare('INSERT INTO event_certificates (event_id, user_id, cert_number, generated_at, merit_awarded, merit_points) VALUES (?, ?, ?, NOW(), 1, ?)');
                    $insertCertStmt->execute([$eventId, $userId, $certNumber, $event['merit_points']]);
                    
                    // Get the newly created certificate
                    $certId = $pdo->lastInsertId();
                    $certStmt = $pdo->prepare('SELECT * FROM event_certificates WHERE id = ? LIMIT 1');
                    $certStmt->execute([$certId]);
                    $certificate = $certStmt->fetch();
                    
                    $message = '🎉 Certificate generated successfully!';
                    $flowStep = 3;
                }
            }
        }
    }
}

// Get available events
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

// Get user's certificates
$certsStmt = $pdo->prepare("
    SELECT 
        c.*,
        e.title as event_title,
        e.event_date,
        e.location,
        ci.checked_in_at,
        ci.checked_out_at
    FROM event_certificates c
    JOIN events e ON e.id = c.event_id
    LEFT JOIN event_checkins ci ON ci.event_id = c.event_id AND ci.user_id = c.user_id
    WHERE c.user_id = ?
    ORDER BY c.generated_at DESC
    LIMIT 5
");
$certsStmt->execute([$user['id']]);
$userCertificates = $certsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Certificate Flow Test – LYDO</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--blue:#1565c0;--blue-dark:#0d3b6e;--blue-light:#1e88e5;--blue-pale:#e3f2fd;--green:#2e7d32;--green-light:#43a047;--green-pale:#e8f5e9;--red:#c62828;--red-pale:#ffebee;--orange:#e65100;--orange-pale:#fff3e0;--purple:#7b1fa2;--purple-pale:#f3e5f5;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;--gray-400:#94a3b8;--gray-600:#475569;--gray-800:#1e293b}
body{font-family:Inter,sans-serif;color:var(--gray-800);background:var(--gray-50);padding:20px}
.container{max-width:1000px;margin:0 auto}
.header{background:linear-gradient(135deg,var(--purple),#9c27b0);border-radius:16px;padding:24px 28px;color:#fff;margin-bottom:20px;box-shadow:0 4px 16px rgba(123,31,162,.2)}
.header h1{font-size:1.6rem;font-weight:800;margin-bottom:6px}
.header p{font-size:.9rem;opacity:.85}
.card{background:#fff;border-radius:12px;border:1px solid var(--gray-200);box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:16px;overflow:hidden}
.card-header{padding:16px 20px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;gap:8px;font-size:1rem;font-weight:700;color:var(--gray-800)}
.card-header i{color:var(--purple)}
.card-body{padding:20px}
.btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border:none;border-radius:10px;font-family:inherit;font-size:.9rem;font-weight:600;cursor:pointer;transition:.2s;text-decoration:none}
.btn-primary{background:var(--blue);color:#fff}
.btn-primary:hover{background:var(--blue-dark);transform:translateY(-2px);box-shadow:0 4px 12px rgba(21,101,192,.3)}
.btn-success{background:var(--green);color:#fff}
.btn-success:hover{background:var(--green-light);transform:translateY(-2px)}
.btn-purple{background:var(--purple);color:#fff}
.btn-purple:hover{background:#6a1b9a;transform:translateY(-2px)}
.btn:disabled{opacity:.5;cursor:not-allowed;transform:none!important}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:.85rem;font-weight:600;color:var(--gray-700);margin-bottom:6px}
.form-group select{width:100%;padding:12px 14px;border:1.5px solid var(--gray-200);border-radius:10px;font-family:inherit;font-size:.9rem;color:var(--gray-800);background:var(--gray-50);outline:none;transition:.2s}
.form-group select:focus{border-color:var(--blue-light);box-shadow:0 0 0 3px rgba(30,136,229,.1);background:#fff}
.alert{padding:16px 20px;border-radius:10px;margin-bottom:16px;display:flex;align-items:center;gap:12px;font-size:.9rem}
.alert-success{background:var(--green-pale);color:var(--green);border:1px solid var(--green)}
.alert-error{background:var(--red-pale);color:var(--red);border:1px solid var(--red)}
.alert i{font-size:1.2rem}
.flow-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px}
.flow-step{background:#fff;border:2px solid var(--gray-200);border-radius:12px;padding:20px;text-align:center;position:relative}
.flow-step.active{border-color:var(--purple);background:var(--purple-pale)}
.flow-step.completed{border-color:var(--green);background:var(--green-pale)}
.flow-step .step-number{width:36px;height:36px;border-radius:50%;background:var(--gray-200);color:var(--gray-600);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;margin:0 auto 10px}
.flow-step.active .step-number{background:var(--purple);color:#fff}
.flow-step.completed .step-number{background:var(--green);color:#fff}
.flow-step .step-title{font-size:.9rem;font-weight:700;color:var(--gray-700);margin-bottom:4px}
.flow-step.active .step-title{color:var(--purple)}
.flow-step.completed .step-title{color:var(--green)}
.flow-step .step-desc{font-size:.75rem;color:var(--gray-500)}
.certificate-preview{background:linear-gradient(135deg,#f5f5f5,#fff);border:3px solid var(--purple);border-radius:16px;padding:32px;text-align:center;margin-bottom:20px;position:relative;overflow:hidden}
.certificate-preview::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(123,31,162,.05) 0%,transparent 70%);pointer-events:none}
.cert-badge{width:80px;height:80px;background:linear-gradient(135deg,var(--purple),#9c27b0);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 4px 16px rgba(123,31,162,.3)}
.cert-badge i{font-size:2.5rem;color:#fff}
.cert-title{font-size:1.8rem;font-weight:800;color:var(--purple);margin-bottom:12px}
.cert-subtitle{font-size:1rem;color:var(--gray-700);margin-bottom:20px}
.cert-number{display:inline-block;background:var(--purple);color:#fff;padding:8px 20px;border-radius:50px;font-size:.85rem;font-weight:700;letter-spacing:1px;margin-bottom:20px}
.cert-info{display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:500px;margin:20px auto}
.cert-info-item{background:#fff;border:1px solid var(--gray-200);border-radius:10px;padding:12px;text-align:center}
.cert-info-label{font-size:.7rem;color:var(--gray-600);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px}
.cert-info-value{font-size:.9rem;font-weight:700;color:var(--gray-800)}
table{width:100%;border-collapse:collapse;font-size:.85rem}
table th{padding:12px 14px;text-align:left;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--gray-600);background:var(--gray-50);border-bottom:1px solid var(--gray-200)}
table td{padding:12px 14px;border-bottom:1px solid var(--gray-100);color:var(--gray-800)}
table tr:last-child td{border-bottom:none}
table tr:hover td{background:var(--gray-50)}
.badge{display:inline-block;padding:4px 10px;border-radius:50px;font-size:.7rem;font-weight:700}
.badge.green{background:var(--green-pale);color:var(--green)}
.badge.blue{background:var(--blue-pale);color:var(--blue)}
.badge.purple{background:var(--purple-pale);color:var(--purple)}
.empty{text-align:center;padding:32px;color:var(--gray-400);font-style:italic}
.action-buttons{display:flex;gap:12px;flex-wrap:wrap}
@media(max-width:768px){
  .flow-steps{grid-template-columns:1fr}
  .cert-info{grid-template-columns:1fr}
}
</style>
</head>
<body>

<div class="container">
  <div class="header">
    <h1><i class="fas fa-certificate"></i> Certificate Flow Test</h1>
    <p>Test the complete flow: Check-in → Check-out → Certificate Generation</p>
  </div>

  <!-- User Info -->
  <div class="card">
    <div class="card-header">
      <i class="fas fa-user"></i>
      <span>Testing As: <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
    </div>
  </div>

  <!-- Flow Steps Progress -->
  <div class="flow-steps">
    <div class="flow-step <?= $flowStep >= 1 ? 'completed' : ($flowStep === 0 ? 'active' : '') ?>">
      <div class="step-number"><?= $flowStep >= 1 ? '✓' : '1' ?></div>
      <div class="step-title">Check-In</div>
      <div class="step-desc">Scan QR at event entrance</div>
    </div>
    <div class="flow-step <?= $flowStep >= 2 ? 'completed' : ($flowStep === 1 ? 'active' : '') ?>">
      <div class="step-number"><?= $flowStep >= 2 ? '✓' : '2' ?></div>
      <div class="step-title">Check-Out</div>
      <div class="step-desc">Scan QR at event exit</div>
    </div>
    <div class="flow-step <?= $flowStep >= 3 ? 'completed' : ($flowStep === 2 ? 'active' : '') ?>">
      <div class="step-number"><?= $flowStep >= 3 ? '✓' : '3' ?></div>
      <div class="step-title">Certificate</div>
      <div class="step-desc">Auto-generated & ready</div>
    </div>
  </div>

  <!-- Messages -->
  <?php if ($message): ?>
  <div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <div><?= htmlspecialchars($message) ?></div>
  </div>
  <?php endif; ?>

  <?php if ($error): ?>
  <div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    <div><?= htmlspecialchars($error) ?></div>
  </div>
  <?php endif; ?>

  <!-- Certificate Preview -->
  <?php if ($certificate): ?>
  <div class="certificate-preview">
    <div class="cert-badge">
      <i class="fas fa-award"></i>
    </div>
    <div class="cert-title">Certificate of Attendance</div>
    <div class="cert-subtitle">This is to certify that</div>
    <h2 style="font-size:1.6rem;font-weight:800;color:var(--gray-800);margin-bottom:16px">
      <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
    </h2>
    <p style="font-size:.95rem;color:var(--gray-700);margin-bottom:20px">
      has successfully attended and participated in
    </p>
    <h3 style="font-size:1.3rem;font-weight:700;color:var(--purple);margin-bottom:24px">
      <?= htmlspecialchars($event['title']) ?>
    </h3>
    <div class="cert-number"><?= htmlspecialchars($certificate['cert_number']) ?></div>
    <div class="cert-info">
      <div class="cert-info-item">
        <div class="cert-info-label">Merit Points</div>
        <div class="cert-info-value" style="color:var(--green)">+<?= $certificate['merit_points'] ?></div>
      </div>
      <div class="cert-info-item">
        <div class="cert-info-label">Issue Date</div>
        <div class="cert-info-value"><?= date('M j, Y', strtotime($certificate['generated_at'])) ?></div>
      </div>
    </div>
    <div style="margin-top:24px">
      <a href="event_cert_view.php?id=<?= $certificate['id'] ?>" target="_blank" class="btn btn-purple">
        <i class="fas fa-external-link-alt"></i>
        View Full Certificate
      </a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Test Actions -->
  <div class="card">
    <div class="card-header">
      <i class="fas fa-play-circle"></i>
      <span>Test Actions</span>
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
            <label>Select Event</label>
            <select name="event_id" id="eventSelect" required>
              <option value="">-- Select an event --</option>
              <?php foreach ($events as $ev): ?>
                <option value="<?= $ev['id'] ?>" <?= isset($_POST['event_id']) && $_POST['event_id'] == $ev['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($ev['title']) ?> 
                  <?php if ($ev['org_name']): ?>
                    (<?= htmlspecialchars($ev['org_name']) ?>)
                  <?php endif; ?>
                  - <?= date('M j, Y', strtotime($ev['event_date'])) ?>
                  - +<?= $ev['merit_points'] ?> pts
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="action-buttons">
            <button type="submit" name="action" value="checkin" class="btn btn-primary">
              <i class="fas fa-sign-in-alt"></i>
              Step 1: Check-In
            </button>
            <button type="submit" name="action" value="checkout" class="btn btn-success">
              <i class="fas fa-sign-out-alt"></i>
              Step 2: Check-Out
            </button>
            <button type="submit" name="action" value="generate_cert" class="btn btn-purple">
              <i class="fas fa-certificate"></i>
              Step 3: Generate Certificate
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- User's Certificates -->
  <div class="card">
    <div class="card-header">
      <i class="fas fa-award"></i>
      <span>My Certificates</span>
    </div>
    <?php if (empty($userCertificates)): ?>
      <div class="empty">No certificates yet</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Certificate #</th>
            <th>Event</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Merit Points</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($userCertificates as $cert): ?>
          <tr>
            <td>
              <code style="background:var(--purple-pale);color:var(--purple);padding:4px 8px;border-radius:6px;font-size:.75rem;font-weight:700">
                <?= htmlspecialchars($cert['cert_number']) ?>
              </code>
            </td>
            <td><strong><?= htmlspecialchars($cert['event_title']) ?></strong></td>
            <td><?= date('M j, g:i A', strtotime($cert['checked_in_at'])) ?></td>
            <td><?= date('M j, g:i A', strtotime($cert['checked_out_at'])) ?></td>
            <td><span class="badge green">+<?= $cert['merit_points'] ?></span></td>
            <td>
              <a href="event_cert_view.php?id=<?= $cert['id'] ?>" target="_blank" class="btn btn-purple" style="padding:6px 12px;font-size:.8rem">
                <i class="fas fa-eye"></i> View
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div style="text-align:center;margin-top:24px">
    <a href="shared/youth/dashboard.php" class="btn btn-primary">
      <i class="fas fa-arrow-left"></i>
      Back to Dashboard
    </a>
  </div>
</div>

<script>
// Keep selected event when performing actions
const eventSelect = document.getElementById('eventSelect');
if (eventSelect && eventSelect.value) {
  // Store selected event in session storage
  sessionStorage.setItem('selectedEvent', eventSelect.value);
}

// Restore selected event on page load
window.addEventListener('DOMContentLoaded', function() {
  const savedEvent = sessionStorage.getItem('selectedEvent');
  if (savedEvent && eventSelect) {
    eventSelect.value = savedEvent;
  }
});
</script>

</body>
</html>
