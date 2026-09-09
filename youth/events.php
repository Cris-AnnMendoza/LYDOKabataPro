<?php
require_once __DIR__ . '/../shared/config.php';
if (empty($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$pdo    = db();
$userId = (int)$_SESSION['user_id'];

$uStmt = $pdo->prepare('SELECT * FROM youth_users WHERE id=? LIMIT 1');
$uStmt->execute([$userId]);
$user = $uStmt->fetch();
if (!$user) { session_destroy(); header('Location: ../login.php'); exit; }

// Unread notifications
$nc = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
$nc->execute([$userId]);
$notifCount = (int)$nc->fetchColumn();

// Upcoming & recent events
$events = $pdo->query(
    'SELECT e.*, o.name as org_name,
     (SELECT COUNT(*) FROM event_checkins c WHERE c.event_id=e.id AND c.user_id=' . $userId . ') as already_checked
     FROM events e
     LEFT JOIN organizations o ON o.id=e.organization_id
     WHERE e.checkin_open=1 OR e.event_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY e.event_date DESC
     LIMIT 50'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Events & Check-in – LYDO Youth Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="youth.css"/>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="y-main">
<?php include 'topbar.php'; ?>
<main class="y-content">

  <div class="y-page-header">
    <div>
      <h2><i class="fas fa-calendar-check" style="color:var(--blue);margin-right:8px"></i>Events & Check-in</h2>
      <p style="color:var(--gray-600);font-size:.88rem;margin-top:4px">Scan the QR code at the event venue to check in and earn merit points.</p>
    </div>
  </div>

  <?php if (empty($events)): ?>
  <div style="background:#fff;border-radius:12px;border:1px solid var(--gray-200);padding:48px;text-align:center;color:var(--gray-400)">
    <i class="fas fa-calendar-times" style="font-size:2.5rem;margin-bottom:14px;display:block"></i>
    <p style="font-size:1rem;font-weight:600">No upcoming events at the moment.</p>
    <p style="font-size:.85rem;margin-top:6px">Check back soon for new events from LYDO.</p>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:12px">
    <?php foreach ($events as $ev): ?>
    <div style="background:#fff;border-radius:12px;border:1px solid var(--gray-200);padding:18px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;box-shadow:0 1px 4px rgba(0,0,0,.05)">
      <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0">
        <div style="width:48px;height:48px;border-radius:12px;background:var(--blue-pale);color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0">
          <i class="fas fa-calendar-alt"></i>
        </div>
        <div style="min-width:0">
          <div style="font-weight:700;font-size:.95rem;color:var(--gray-800);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($ev['title']) ?></div>
          <div style="font-size:.78rem;color:var(--gray-500);margin-top:3px">
            <i class="fas fa-clock" style="margin-right:4px"></i><?= date('M j, Y', strtotime($ev['event_date'])) ?>
            <?php if ($ev['location']): ?>
            &nbsp;·&nbsp;<i class="fas fa-map-marker-alt" style="margin-right:4px"></i><?= htmlspecialchars($ev['location']) ?>
            <?php endif; ?>
          </div>
          <div style="margin-top:5px;display:flex;gap:6px;flex-wrap:wrap">
            <span style="background:var(--blue-pale);color:var(--blue);font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:50px">+<?= $ev['merit_points'] ?? 2 ?> Merit</span>
            <?php if ($ev['checkin_open']): ?>
              <span style="background:#e8f5e9;color:#2e7d32;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:50px"><i class="fas fa-circle" style="font-size:.45rem;vertical-align:middle;margin-right:3px"></i>Check-in Open</span>
            <?php else: ?>
              <span style="background:var(--gray-100);color:var(--gray-500);font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:50px">Closed</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div style="flex-shrink:0">
        <?php if ($ev['already_checked']): ?>
          <span style="display:inline-flex;align-items:center;gap:6px;background:#e8f5e9;color:#2e7d32;padding:8px 16px;border-radius:9px;font-size:.82rem;font-weight:700">
            <i class="fas fa-check-circle"></i> Checked In
          </span>
        <?php elseif ($ev['checkin_open']): ?>
          <a href="../event_checkin.php?event_id=<?= $ev['id'] ?>" style="display:inline-flex;align-items:center;gap:6px;background:var(--blue);color:#fff;padding:9px 18px;border-radius:9px;font-size:.82rem;font-weight:700;text-decoration:none;transition:.2s">
            <i class="fas fa-qrcode"></i> Check In
          </a>
        <?php else: ?>
          <span style="color:var(--gray-400);font-size:.82rem">Unavailable</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>
</div>
<script>
const hamburger = document.getElementById('yHamburger');
const sidebar   = document.getElementById('ySidebar');
const closeBtn  = document.getElementById('ySidebarClose');
const overlay   = document.getElementById('yOverlay');
function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('open'); }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }
if (hamburger) hamburger.addEventListener('click', openSidebar);
if (closeBtn)  closeBtn.addEventListener('click',  closeSidebar);
if (overlay)   overlay.addEventListener('click',   closeSidebar);
</script>
</body>
</html>
