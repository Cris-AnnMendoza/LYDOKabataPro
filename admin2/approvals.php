<?php
require_once 'config.php';
requireLogin();
if (!hasPermission('view_users')) { header('Location: dashboard.php'); exit; }

$pdo  = db();
$tab  = $_GET['tab'] ?? 'youth';   // youth | staff

// ── Handle approve / reject ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'], $_POST['type'])) {
    $id     = (int)$_POST['id'];
    $action = $_POST['action'];   // approve | reject
    $type   = $_POST['type'];     // youth | staff
    $status = $action === 'approve' ? 'approved' : 'rejected';

    if ($type === 'youth') {
        $pdo->prepare('UPDATE youth_users SET status = ? WHERE id = ?')->execute([$status, $id]);
        flash('success', $action === 'approve' ? 'Youth registration approved.' : 'Youth registration rejected.');
    } elseif ($type === 'staff' && hasPermission('manage_admins')) {
        $pdo->prepare('UPDATE admin_users SET status = ? WHERE id = ?')->execute([$status, $id]);
        flash('success', $action === 'approve' ? 'Staff account approved.' : 'Staff account rejected.');
    }
    header('Location: approvals.php?tab=' . $type); exit;
}

// ── Counts for badges ─────────────────────────────────────
$pendingYouth = (int)$pdo->query("SELECT COUNT(*) FROM youth_users WHERE status='pending'")->fetchColumn();
$pendingStaff = hasPermission('manage_admins')
    ? (int)$pdo->query("SELECT COUNT(*) FROM admin_users WHERE status='pending'")->fetchColumn()
    : 0;

// ── Fetch list ────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'pending';   // pending | approved | rejected | all
$validFilters = ['pending','approved','rejected','all'];
if (!in_array($filter, $validFilters)) $filter = 'pending';

$whereStatus = $filter === 'all' ? '' : "WHERE status = '$filter'";

if ($tab === 'youth') {
    $rows = $pdo->query("SELECT id,first_name,last_name,email,gender,barangay,youth_classification,status,created_at
                         FROM youth_users $whereStatus ORDER BY created_at DESC")->fetchAll();
} else {
    $rows = $pdo->query("SELECT id,full_name,email,role,barangay,status,created_at
                         FROM admin_users $whereStatus ORDER BY created_at DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Approvals – LYDO Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="admin.css"/>
<style>
.tabs{display:flex;gap:4px;margin-bottom:20px;background:#f1f5f9;padding:4px;border-radius:10px;width:fit-content}
.tab-btn{padding:8px 20px;border-radius:8px;border:none;font-family:inherit;font-size:.88rem;font-weight:600;cursor:pointer;color:#475569;background:transparent;transition:.2s;display:flex;align-items:center;gap:7px}
.tab-btn.active{background:#fff;color:#1565c0;box-shadow:0 1px 4px rgba(0,0,0,.1)}
.tab-btn .cnt{background:#e53935;color:#fff;border-radius:50px;padding:1px 7px;font-size:.7rem;font-weight:700}
.filter-tabs{display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap}
.ftab{padding:6px 14px;border-radius:8px;border:1.5px solid #e2e8f0;font-family:inherit;font-size:.82rem;font-weight:600;cursor:pointer;color:#475569;background:#fff;text-decoration:none;transition:.2s}
.ftab:hover,.ftab.active{background:#1565c0;border-color:#1565c0;color:#fff}
.status-pending{background:#fff8e1;color:#f57f17;padding:3px 10px;border-radius:50px;font-size:.72rem;font-weight:700}
.status-approved{background:#e8f5e9;color:#2e7d32;padding:3px 10px;border-radius:50px;font-size:.72rem;font-weight:700}
.status-rejected{background:#ffebee;color:#c62828;padding:3px 10px;border-radius:50px;font-size:.72rem;font-weight:700}
.action-btns{display:flex;gap:6px;align-items:center}
.btn-approve{padding:5px 12px;background:#e8f5e9;color:#2e7d32;border:1.5px solid #a5d6a7;border-radius:7px;font-family:inherit;font-size:.78rem;font-weight:700;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:5px}
.btn-approve:hover{background:#2e7d32;color:#fff;border-color:#2e7d32}
.btn-reject{padding:5px 12px;background:#ffebee;color:#c62828;border:1.5px solid #ef9a9a;border-radius:7px;font-family:inherit;font-size:.78rem;font-weight:700;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:5px}
.btn-reject:hover{background:#c62828;color:#fff;border-color:#c62828}
.empty-state{text-align:center;padding:48px 20px;color:#94a3b8}
.empty-state i{font-size:2.5rem;margin-bottom:12px;display:block}
.empty-state p{font-size:.95rem;font-weight:500}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-wrap">
<?php include 'topbar.php'; ?>
<main class="content">

  <?php if ($msg = flash('success')): ?>
    <div class="flash success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="page-header">
    <div>
      <h2>Registration Approvals</h2>
      <p>Review and approve or reject pending registrations.</p>
    </div>
  </div>

  <!-- TABS: Youth / Staff -->
  <div class="tabs">
    <a href="?tab=youth&filter=<?= $filter ?>" class="tab-btn <?= $tab==='youth'?'active':'' ?>">
      <i class="fas fa-users"></i> Youth Registrations
      <?php if ($pendingYouth > 0): ?><span class="cnt"><?= $pendingYouth ?></span><?php endif; ?>
    </a>
    <?php if (hasPermission('manage_admins')): ?>
    <a href="?tab=staff&filter=<?= $filter ?>" class="tab-btn <?= $tab==='staff'?'active':'' ?>">
      <i class="fas fa-user-shield"></i> Staff Accounts
      <?php if ($pendingStaff > 0): ?><span class="cnt"><?= $pendingStaff ?></span><?php endif; ?>
    </a>
    <?php endif; ?>
  </div>

  <!-- FILTER TABS -->
  <div class="filter-tabs">
    <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','all'=>'All'] as $f => $label): ?>
      <a href="?tab=<?= $tab ?>&filter=<?= $f ?>" class="ftab <?= $filter===$f?'active':'' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="table-wrap">
      <?php if ($tab === 'youth'): ?>
      <table class="tbl">
        <thead>
          <tr><th>#</th><th>Name</th><th>Email</th><th>Gender</th><th>Barangay</th><th>Classification</th><th>Registered</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="9">
            <div class="empty-state">
              <i class="fas fa-check-double"></i>
              <p>No <?= $filter === 'all' ? '' : $filter ?> youth registrations.</p>
            </div>
          </td></tr>
        <?php else: foreach ($rows as $i => $r):
          $cls = $r['youth_classification'] ? json_decode($r['youth_classification'],true) : [];
          $cls = is_array($cls) ? ($cls[0] ?? '—') : ($r['youth_classification'] ?: '—');
        ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></strong></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><?= htmlspecialchars($r['gender'] ?: '—') ?></td>
            <td><?= htmlspecialchars($r['barangay'] ?: '—') ?></td>
            <td><span class="badge blue"><?= htmlspecialchars($cls) ?></span></td>
            <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
            <td><span class="status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
            <td>
              <div class="action-btns">
                <a href="view_user.php?id=<?= $r['id'] ?>" class="btn-icon teal" title="View"><i class="fas fa-eye"></i></a>
                <?php if ($r['status'] !== 'approved'): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
                  <input type="hidden" name="type" value="youth"/>
                  <input type="hidden" name="action" value="approve"/>
                  <button type="submit" class="btn-approve"><i class="fas fa-check"></i> Approve</button>
                </form>
                <?php endif; ?>
                <?php if ($r['status'] !== 'rejected'): ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Reject this registration?')">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
                  <input type="hidden" name="type" value="youth"/>
                  <input type="hidden" name="action" value="reject"/>
                  <button type="submit" class="btn-reject"><i class="fas fa-times"></i> Reject</button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>

      <?php else: /* STAFF TAB */ ?>
      <table class="tbl">
        <thead>
          <tr><th>#</th><th>Full Name</th><th>Email</th><th>Role</th><th>Barangay</th><th>Registered</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8">
            <div class="empty-state">
              <i class="fas fa-check-double"></i>
              <p>No <?= $filter === 'all' ? '' : $filter ?> staff accounts.</p>
            </div>
          </td></tr>
        <?php else: foreach ($rows as $i => $r): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><?= roleBadge($r['role']) ?></td>
            <td><?= htmlspecialchars($r['barangay'] ?: '—') ?></td>
            <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
            <td><span class="status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
            <td>
              <div class="action-btns">
                <?php if ($r['status'] !== 'approved'): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
                  <input type="hidden" name="type" value="staff"/>
                  <input type="hidden" name="action" value="approve"/>
                  <button type="submit" class="btn-approve"><i class="fas fa-check"></i> Approve</button>
                </form>
                <?php endif; ?>
                <?php if ($r['status'] !== 'rejected'): ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Reject this account?')">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
                  <input type="hidden" name="type" value="staff"/>
                  <input type="hidden" name="action" value="reject"/>
                  <button type="submit" class="btn-reject"><i class="fas fa-times"></i> Reject</button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

</main>
</div>
</body>
</html>
