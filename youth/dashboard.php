<?php
require_once __DIR__ . '/../shared/config.php';
if (empty($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$pdo  = db();
$stmt = $pdo->prepare('SELECT * FROM youth_users WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) { session_destroy(); header('Location: ../login.php'); exit; }

$cls  = $user['youth_classification'] ? json_decode($user['youth_classification'], true) : [];
$prgs = $user['programs_interested']  ? json_decode($user['programs_interested'],  true) : [];

// Pending assistance requests
$pendingReqs = (int)$pdo->prepare('SELECT COUNT(*) FROM assistance_requests WHERE submitted_by=? AND status NOT IN ("completed","declined")')
    ->execute([$user['id']]) ? 0 : 0;
$rStmt = $pdo->prepare('SELECT COUNT(*) FROM assistance_requests WHERE submitted_by=? AND status NOT IN ("completed","declined")');
$rStmt->execute([$user['id']]);
$pendingReqs = (int)$rStmt->fetchColumn();

// Unread notifications
$nc = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
$nc->execute([$user['id']]);
$notifCount = (int)$nc->fetchColumn();

// User's organizations with merit/demerit points
$orgStmt = $pdo->prepare(
    'SELECT o.id, o.name, o.category, o.barangay, om.position,
     COALESCE(SUM(CASE WHEN m.type="merit" THEN m.points ELSE 0 END),0) as merit_pts,
     COALESCE(SUM(CASE WHEN m.type="demerit" THEN ABS(m.points) ELSE 0 END),0) as demerit_pts,
     COALESCE(SUM(m.points),0) as net_pts
     FROM organizations o
     JOIN organization_members om ON om.organization_id=o.id
     LEFT JOIN org_merit_logs m ON m.organization_id=o.id
     WHERE om.user_id=? AND om.is_active=1
     GROUP BY o.id, om.position
     ORDER BY net_pts DESC'
);
$orgStmt->execute([$user['id']]);
$userOrgPoints = $orgStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Dashboard – LYDO Youth Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="youth.css"/>
<style>
@media(max-width:600px){
  .dash-stat-grid{grid-template-columns:1fr!important}
  .dash-main-grid{grid-template-columns:1fr!important}
  .dash-quick-grid{grid-template-columns:1fr!important}
  .dash-welcome{flex-direction:column!important;gap:10px!important}
  .dash-welcome-badge{width:100%!important;text-align:left!important;padding:10px 14px!important}
}
@media(max-width:400px){
  .dash-stat-grid{grid-template-columns:1fr!important}
}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="y-main">
<?php include 'topbar.php'; ?>
<main class="y-content">

  <!-- WELCOME BANNER -->
  <div class="dash-welcome" style="background:linear-gradient(135deg,var(--blue-dark),var(--blue));border-radius:16px;padding:24px 28px;color:#fff;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px">
    <div>
      <h2 style="font-size:1.4rem;font-weight:800;margin-bottom:5px">Welcome back, <?= htmlspecialchars($user['first_name']) ?>! 👋</h2>
      <p style="font-size:.88rem;opacity:.85">You're logged in to the LYDO Youth Portal of Sta. Cruz, Laguna.</p>
    </div>
    <div style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:12px 20px;text-align:center">
      <span style="display:block;font-size:.72rem;opacity:.75;margin-bottom:2px">Member since</span>
      <strong style="font-size:1rem"><?= date('M Y', strtotime($user['created_at'])) ?></strong>
    </div>
  </div>

  <!-- STAT CARDS -->
  <div class="dash-stat-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px">
    <div style="background:#fff;border-radius:12px;border:1px solid var(--gray-200);padding:18px;display:flex;align-items:center;gap:14px;box-shadow:var(--shadow-sm)">
      <div style="width:44px;height:44px;border-radius:11px;background:var(--blue-pale);color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0"><i class="fas fa-hands-helping"></i></div>
      <div><span style="display:block;font-size:1.6rem;font-weight:800;color:var(--gray-800);line-height:1"><?= $pendingReqs ?></span><span style="font-size:.75rem;color:var(--gray-600);font-weight:500">Active Requests</span></div>
    </div>
    <div style="background:#fff;border-radius:12px;border:1px solid var(--gray-200);padding:18px;display:flex;align-items:center;gap:14px;box-shadow:var(--shadow-sm)">
      <div style="width:44px;height:44px;border-radius:11px;background:var(--green-pale);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0"><i class="fas fa-map-marker-alt"></i></div>
      <div><span style="display:block;font-size:1.1rem;font-weight:800;color:var(--gray-800);line-height:1.2"><?= htmlspecialchars($user['barangay'] ?: '—') ?></span><span style="font-size:.75rem;color:var(--gray-600);font-weight:500">Barangay</span></div>
    </div>
    <div style="background:#fff;border-radius:12px;border:1px solid var(--gray-200);padding:18px;display:flex;align-items:center;gap:14px;box-shadow:var(--shadow-sm)">
      <div style="width:44px;height:44px;border-radius:11px;background:#fff8e1;color:#f57f17;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0"><i class="fas fa-bell"></i></div>
      <div><span style="display:block;font-size:1.6rem;font-weight:800;color:var(--gray-800);line-height:1"><?= $notifCount ?></span><span style="font-size:.75rem;color:var(--gray-600);font-weight:500">Notifications</span></div>
    </div>
  </div>

  <div class="dash-main-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

    <!-- PROFILE SUMMARY -->
    <div style="background:#fff;border-radius:12px;border:1px solid var(--gray-200);box-shadow:var(--shadow-sm);overflow:hidden">
      <div style="padding:14px 18px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;gap:7px">
        <i class="fas fa-user" style="color:var(--blue);font-size:.85rem"></i>
        <span style="font-size:.9rem;font-weight:700">My Profile</span>
      </div>
      <div style="padding:4px 0">
        <?php
        $fields = [
          'Full Name'    => $user['first_name'].' '.$user['last_name'],
          'Email'        => $user['email'],
          'Contact'      => $user['contact_number'] ?: '—',
          'Civil Status' => $user['civil_status'] ?: '—',
          'Education'    => $user['educational_status'] ?: '—',
          'Employment'   => $user['employment_status'] ?: '—',
        ];
        foreach ($fields as $label => $val): ?>
        <div style="display:flex;gap:10px;padding:8px 18px;border-bottom:1px solid var(--gray-100);font-size:.85rem">
          <span style="width:110px;flex-shrink:0;color:var(--gray-600);font-weight:500"><?= $label ?></span>
          <span style="color:var(--gray-800);font-weight:500"><?= htmlspecialchars($val) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- QUICK LINKS + CLASSIFICATION -->
    <div style="display:flex;flex-direction:column;gap:14px">
      <div style="background:#fff;border-radius:12px;border:1px solid var(--gray-200);box-shadow:var(--shadow-sm);overflow:hidden">
        <div style="padding:14px 18px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;gap:7px">
          <i class="fas fa-th" style="color:var(--blue);font-size:.85rem"></i>
          <span style="font-size:.9rem;font-weight:700">Quick Links</span>
        </div>
        <div class="dash-quick-grid" style="padding:14px 16px;display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <?php
          $links = [
            ['assistance.php',   'hands-helping', 'Assistance Request', '#e3f2fd','#1565c0'],
            ['accreditation.php','award',         'Accreditation',      '#e8f5e9','#2e7d32'],
            ['notifications.php','bell',          'Notifications',      '#fff8e1','#f57f17'],
            ['profile.php',      'user-edit',     'Edit Profile',       '#f3e5f5','#7b1fa2'],
          ];
          foreach ($links as [$href,$icon,$label,$bg,$color]): ?>
          <a href="<?= $href ?>" style="display:flex;align-items:center;gap:9px;padding:10px 12px;border-radius:9px;border:1.5px solid var(--gray-200);text-decoration:none;color:var(--gray-800);font-size:.83rem;font-weight:600;transition:.2s" onmouseover="this.style.background='<?= $bg ?>';this.style.borderColor='<?= $color ?>';this.style.color='<?= $color ?>'" onmouseout="this.style.background='';this.style.borderColor='var(--gray-200)';this.style.color='var(--gray-800)'">
            <i class="fas fa-<?= $icon ?>" style="font-size:.95rem;color:<?= $color ?>"></i><?= $label ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Classification -->
      <div style="background:#fff;border-radius:12px;border:1px solid var(--gray-200);box-shadow:var(--shadow-sm);overflow:hidden">
        <div style="padding:14px 18px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;gap:7px">
          <i class="fas fa-id-card" style="color:var(--blue);font-size:.85rem"></i>
          <span style="font-size:.9rem;font-weight:700">My Classification</span>
        </div>
        <div style="padding:14px 18px;display:flex;flex-wrap:wrap;gap:7px">
          <?php if ($cls): foreach ((array)$cls as $c): ?>
            <span style="background:var(--blue-pale);color:var(--blue);padding:4px 11px;border-radius:50px;font-size:.75rem;font-weight:700"><?= htmlspecialchars($c) ?></span>
          <?php endforeach; else: ?>
            <span style="color:var(--gray-400);font-size:.85rem">Not specified</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- ORG MERIT/DEMERIT POINTS -->
  <?php if (!empty($userOrgPoints)): ?>
  <div style="background:#fff;border-radius:12px;border:1px solid var(--gray-200);box-shadow:var(--shadow-sm);overflow:hidden;margin-top:16px">
    <div style="padding:14px 18px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;gap:7px">
      <i class="fas fa-star" style="color:#f57f17;font-size:.85rem"></i>
      <span style="font-size:.9rem;font-weight:700">My Organization Merit Standing</span>
    </div>
    <div style="padding:4px 0">
      <?php foreach ($userOrgPoints as $org):
        $netColor = $org['net_pts'] >= 0 ? '#2e7d32' : '#c62828';
        $netBg    = $org['net_pts'] >= 0 ? '#e8f5e9' : '#ffebee';
      ?>
      <div style="padding:12px 18px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div>
          <div style="font-size:.9rem;font-weight:700;color:var(--gray-800)"><?=htmlspecialchars($org['name'])?></div>
          <div style="font-size:.75rem;color:var(--gray-600);margin-top:2px;display:flex;align-items:center;gap:8px">
            <?php if ($org['position']): ?>
              <span style="background:#e3f2fd;color:#1565c0;padding:1px 7px;border-radius:50px;font-size:.7rem;font-weight:700"><?=htmlspecialchars($org['position'])?></span>
            <?php endif; ?>
            <?php if ($org['category']): ?><span><?=htmlspecialchars($org['category'])?></span><?php endif; ?>
            <?php if ($org['barangay']): ?><span><i class="fas fa-map-marker-alt"></i> <?=htmlspecialchars($org['barangay'])?></span><?php endif; ?>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
          <div style="text-align:center">
            <div style="font-size:1rem;font-weight:800;color:#2e7d32">+<?=$org['merit_pts']?></div>
            <div style="font-size:.68rem;color:var(--gray-600)">Merit</div>
          </div>
          <div style="text-align:center">
            <div style="font-size:1rem;font-weight:800;color:#c62828">-<?=$org['demerit_pts']?></div>
            <div style="font-size:.68rem;color:var(--gray-600)">Demerit</div>
          </div>
          <div style="text-align:center;background:<?=$netBg?>;padding:6px 12px;border-radius:8px">
            <div style="font-size:1.1rem;font-weight:800;color:<?=$netColor?>"><?=($org['net_pts']>=0?'+':'').$org['net_pts']?></div>
            <div style="font-size:.68rem;color:<?=$netColor?>;font-weight:600">Net Score</div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ANNOUNCEMENTS -->
  <div style="background:#fff;border-radius:12px;border:1px solid var(--gray-200);box-shadow:var(--shadow-sm);overflow:hidden;margin-top:16px">
    <div style="padding:14px 18px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;gap:7px">
      <i class="fas fa-bullhorn" style="color:var(--blue);font-size:.85rem"></i>
      <span style="font-size:.9rem;font-weight:700">Latest Announcements</span>
    </div>
    <div style="padding:4px 0">
      <?php
      $announcements = [
        ['Youth Leadership Summit 2026 — Registration Open', 'June 15, 2026', 'Sta. Cruz Municipal Hall', 'leadership'],
        ['Free Digital Skills Workshop — Limited Slots',     'June 22, 2026', 'Sta. Cruz Public Library',  'skills'],
        ['Inter-Barangay Sports Fest 2026',                  'July 5, 2026',  'Sta. Cruz Sports Complex',  'sports'],
      ];
      $tagColors = ['leadership'=>['#1565c0','#e3f2fd'],'skills'=>['#2e7d32','#e8f5e9'],'sports'=>['#00796b','#e0f2f1']];
      foreach ($announcements as [$title,$date,$venue,$tag]):
        [$tc,$tb] = $tagColors[$tag];
      ?>
      <div style="padding:12px 18px;border-bottom:1px solid var(--gray-100);display:flex;align-items:flex-start;gap:12px">
        <div style="flex:1">
          <div style="font-size:.88rem;font-weight:600;color:var(--gray-800);margin-bottom:4px"><?= $title ?></div>
          <div style="font-size:.78rem;color:var(--gray-600);display:flex;align-items:center;gap:12px">
            <span><i class="fas fa-calendar" style="margin-right:4px"></i><?= $date ?></span>
            <span><i class="fas fa-map-marker-alt" style="margin-right:4px"></i><?= $venue ?></span>
          </div>
        </div>
        <span style="background:<?= $tb ?>;color:<?= $tc ?>;padding:3px 9px;border-radius:50px;font-size:.7rem;font-weight:700;flex-shrink:0"><?= ucfirst($tag) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</main>
</div>

<script>
const hamburger = document.getElementById('yHamburger');
const sidebar   = document.getElementById('ySidebar');
const overlay   = document.getElementById('yOverlay');
const closeBtn  = document.getElementById('ySidebarClose');

function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('open'); }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }

hamburger.addEventListener('click', openSidebar);
closeBtn.addEventListener('click',  closeSidebar);
overlay.addEventListener('click',   closeSidebar);
</script>
</body>
</html>
