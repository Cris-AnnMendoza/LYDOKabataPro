<?php
/**
 * Public Certificate Verification Page
 * URL: /LYDO/lydo-system/verify_cert.php?cert=LYDO-EVT-XXXX&h=HASH
 * No login required — anyone can verify a certificate's authenticity.
 */
require_once __DIR__ . '/shared/config.php';

$pdo      = db();
$certNo   = trim($_GET['cert'] ?? '');
$hash     = trim($_GET['h']    ?? '');
$valid    = false;
$cert     = null;
$event    = null;
$user     = null;
$checkin  = null;

if ($certNo && $hash) {
    // Load certificate
    $cStmt = $pdo->prepare('SELECT * FROM event_certificates WHERE cert_number = ? LIMIT 1');
    $cStmt->execute([$certNo]);
    $cert = $cStmt->fetch();

    if ($cert) {
        // Verify HMAC hash
        $verifySecret = 'LYDO_VERIFY_2026_' . DB_NAME;
        $expectedHash = substr(hash_hmac('sha256', $certNo . $cert['user_id'] . $cert['event_id'], $verifySecret), 0, 16);

        if (hash_equals($expectedHash, $hash)) {
            $valid = true;

            // Load event
            $eStmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
            $eStmt->execute([$cert['event_id']]);
            $event = $eStmt->fetch();

            // Load user
            $uStmt = $pdo->prepare('SELECT first_name, last_name, barangay, email FROM youth_users WHERE id = ? LIMIT 1');
            $uStmt->execute([$cert['user_id']]);
            $user = $uStmt->fetch();

            // Load check-in times
            $ciStmt = $pdo->prepare('SELECT checked_in_at, checked_out_at FROM event_checkins WHERE event_id = ? AND user_id = ? LIMIT 1');
            $ciStmt->execute([$cert['event_id'], $cert['user_id']]);
            $checkin = $ciStmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Certificate Verification – LYDO</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;min-height:100vh;background:linear-gradient(160deg,#0d3b6e 0%,#1565c0 55%,#1b5e20 100%);display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);width:100%;max-width:500px;overflow:hidden}
.card-top{padding:28px;text-align:center;color:#fff}
.card-top.valid{background:linear-gradient(135deg,#1b5e20,#2e7d32)}
.card-top.invalid{background:linear-gradient(135deg,#b71c1c,#c62828)}
.card-top.unknown{background:linear-gradient(135deg,#0d3b6e,#1565c0)}
.status-icon{font-size:3rem;margin-bottom:12px;display:block}
.card-top h1{font-size:1.3rem;font-weight:800;margin-bottom:4px}
.card-top p{font-size:.85rem;opacity:.8}
.card-body{padding:28px}
.info-row{display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:.88rem}
.info-row:last-child{border-bottom:none}
.info-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0}
.info-label{font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px}
.info-val{font-size:.9rem;font-weight:600;color:#1e293b}
.time-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:16px 0}
.time-cell{background:#f8fafc;border-radius:10px;padding:12px;text-align:center;border:1.5px solid #e2e8f0}
.time-cell.in{border-color:#a5d6a7;background:#e8f5e9}
.time-cell.out{border-color:#ef9a9a;background:#ffebee}
.time-cell-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px}
.time-cell.in .time-cell-label{color:#2e7d32}
.time-cell.out .time-cell-label{color:#c62828}
.time-cell-val{font-size:1.1rem;font-weight:800;font-family:monospace}
.time-cell.in .time-cell-val{color:#1b5e20}
.time-cell.out .time-cell-val{color:#b71c1c}
.cert-no{background:#e3f2fd;border-radius:8px;padding:10px 14px;font-family:monospace;font-size:.85rem;font-weight:700;color:#0d3b6e;text-align:center;margin:14px 0;word-break:break-all}
.btn-back{display:flex;align-items:center;justify-content:center;gap:7px;padding:12px;background:linear-gradient(135deg,#0d3b6e,#1565c0);color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;text-decoration:none;margin-top:16px;transition:.2s}
.btn-back:hover{transform:translateY(-1px)}
.lydo-badge{display:flex;align-items:center;gap:10px;padding:12px 14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;margin-bottom:16px}
.lydo-badge img{width:36px;height:36px;object-fit:contain}
.lydo-badge-text{font-size:.78rem;color:#475569;line-height:1.5}
.lydo-badge-text strong{color:#0d3b6e;display:block;font-size:.85rem}
</style>
</head>
<body>
<div class="card">

  <?php if (!$certNo || !$hash): ?>
  <!-- No parameters -->
  <div class="card-top unknown">
    <span class="status-icon">🔍</span>
    <h1>Certificate Verification</h1>
    <p>Scan a certificate QR code to verify its authenticity</p>
  </div>
  <div class="card-body">
    <p style="text-align:center;color:#475569;font-size:.9rem">No certificate specified. Please scan the QR code on a LYDO certificate.</p>
    <a href="/LYDO/index.html" class="btn-back"><i class="fas fa-home"></i> Go to LYDO Portal</a>
  </div>

  <?php elseif ($valid && $cert && $event && $user): ?>
  <!-- VALID certificate -->
  <div class="card-top valid">
    <span class="status-icon">✅</span>
    <h1>Certificate Verified!</h1>
    <p>This is an authentic LYDO certificate</p>
  </div>
  <div class="card-body">

    <div class="lydo-badge">
      <img src="/LYDO/lydo-logo.png" alt="LYDO"/>
      <div class="lydo-badge-text">
        <strong>Local Youth Development Office</strong>
        Municipal Government of Sta. Cruz, Laguna
      </div>
    </div>

    <div class="cert-no">
      <i class="fas fa-certificate" style="color:#1565c0;margin-right:6px"></i>
      <?= htmlspecialchars($certNo) ?>
    </div>

    <!-- Attendee info -->
    <div class="info-row">
      <div class="info-icon" style="background:#e3f2fd;color:#1565c0"><i class="fas fa-user"></i></div>
      <div>
        <div class="info-label">Attendee</div>
        <div class="info-val"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
        <?php if ($user['barangay']): ?>
        <div style="font-size:.78rem;color:#94a3b8"><?= htmlspecialchars($user['barangay']) ?>, Sta. Cruz, Laguna</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="info-row">
      <div class="info-icon" style="background:#e8f5e9;color:#2e7d32"><i class="fas fa-calendar-check"></i></div>
      <div>
        <div class="info-label">Event</div>
        <div class="info-val"><?= htmlspecialchars($event['title']) ?></div>
        <div style="font-size:.78rem;color:#94a3b8">
          <?= date('F j, Y', strtotime($event['event_date'])) ?>
          <?php if ($event['location']): ?> · <?= htmlspecialchars($event['location']) ?><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Time In / Time Out -->
    <?php if ($checkin): ?>
    <div class="time-grid">
      <div class="time-cell in">
        <div class="time-cell-label"><i class="fas fa-sign-in-alt"></i> Time In</div>
        <div class="time-cell-val">
          <?= $checkin['checked_in_at'] ? date('g:i A', strtotime($checkin['checked_in_at'])) : '—' ?>
        </div>
        <div style="font-size:.68rem;color:#2e7d32;margin-top:3px">
          <?= $checkin['checked_in_at'] ? date('M j, Y', strtotime($checkin['checked_in_at'])) : '' ?>
        </div>
      </div>
      <div class="time-cell out">
        <div class="time-cell-label"><i class="fas fa-sign-out-alt"></i> Time Out</div>
        <div class="time-cell-val">
          <?= $checkin['checked_out_at'] ? date('g:i A', strtotime($checkin['checked_out_at'])) : '—' ?>
        </div>
        <div style="font-size:.68rem;color:#c62828;margin-top:3px">
          <?= $checkin['checked_out_at'] ? date('M j, Y', strtotime($checkin['checked_out_at'])) : '' ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="info-row">
      <div class="info-icon" style="background:#f3e5f5;color:#7b1fa2"><i class="fas fa-star"></i></div>
      <div>
        <div class="info-label">Merit Points</div>
        <div class="info-val">+<?= (int)$cert['merit_points'] ?> points awarded</div>
        <div style="font-size:.78rem;color:#94a3b8">
          Issued: <?= date('F j, Y', strtotime($cert['generated_at'])) ?>
        </div>
      </div>
    </div>

    <div style="background:#e8f5e9;border-radius:10px;padding:12px 14px;font-size:.82rem;color:#2e7d32;display:flex;align-items:center;gap:8px;margin-top:8px">
      <i class="fas fa-shield-alt" style="font-size:1rem;flex-shrink:0"></i>
      <div>This certificate was <strong>digitally verified</strong> by the LYDO system. The attendance record is stored in the LYDO database and cannot be falsified.</div>
    </div>

    <a href="/LYDO/index.html" class="btn-back"><i class="fas fa-home"></i> Go to LYDO Portal</a>
  </div>

  <?php else: ?>
  <!-- INVALID certificate -->
  <div class="card-top invalid">
    <span class="status-icon">❌</span>
    <h1>Invalid Certificate</h1>
    <p>This certificate could not be verified</p>
  </div>
  <div class="card-body">
    <div style="background:#ffebee;border-radius:10px;padding:14px;font-size:.88rem;color:#c62828;margin-bottom:16px;display:flex;align-items:flex-start;gap:10px">
      <i class="fas fa-exclamation-triangle" style="font-size:1.1rem;margin-top:2px;flex-shrink:0"></i>
      <div>
        <strong>Certificate Not Found or Tampered</strong><br>
        This certificate number does not exist in the LYDO database, or the QR code has been modified. This may be a fake certificate.
      </div>
    </div>
    <?php if ($certNo): ?>
    <div class="cert-no" style="background:#ffebee;color:#c62828"><?= htmlspecialchars($certNo) ?></div>
    <?php endif; ?>
    <p style="font-size:.82rem;color:#475569;text-align:center;margin-top:12px">
      If you believe this is an error, contact the LYDO office at Municipal Hall, Sta. Cruz, Laguna.
    </p>
    <a href="/LYDO/index.html" class="btn-back" style="background:linear-gradient(135deg,#b71c1c,#c62828)"><i class="fas fa-home"></i> Go to LYDO Portal</a>
  </div>
  <?php endif; ?>

</div>
</body>
</html>
