<?php
require_once 'config.php';
require_once 'merit_engine.php';
requireLogin();

$pdo   = db();
$admin = currentAdmin();
$tab   = $_GET['tab'] ?? 'leaderboard';

// ── Handle POST ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'award') {
        $orgId    = (int)$_POST['org_id'];
        $ruleKeys = $_POST['rule_keys'] ?? [];   // multiple checkboxes
        $bonus    = (int)($_POST['bonus_points'] ?? 0);
        $bonusNote= trim($_POST['bonus_note'] ?? '');

        if (!$orgId) { flash('error','Please select an organization.'); header('Location: merit.php?tab=logs'); exit; }

        $applied = [];
        $allConsequences = [];

        // Apply each selected rule
        foreach ($ruleKeys as $ruleKey) {
            if (!isset(MERIT_RULES[$ruleKey])) continue;
            $result = awardOrgPoints($pdo, $orgId, $ruleKey, $admin['id']);
            $pts = ($result['points'] > 0 ? '+' : '') . $result['points'];
            $applied[] = MERIT_RULES[$ruleKey]['label'] . " ($pts)";
            $allConsequences = array_merge($allConsequences, $result['consequences'] ?? []);
        }

        // Apply bonus points from LYDO Head
        if ($bonus !== 0 && $bonusNote) {
            $type = $bonus > 0 ? 'merit' : 'demerit';
            $pdo->prepare(
                'INSERT INTO org_merit_logs (organization_id,points,type,reason,category,awarded_by) VALUES (?,?,?,?,?,?)'
            )->execute([$orgId, $bonus, $type, '[LYDO Head Bonus] ' . $bonusNote, 'lydo_head_bonus', $admin['id']]);
            $applied[] = 'LYDO Head Bonus: ' . $bonusNote . ' (' . ($bonus > 0 ? '+' : '') . $bonus . ')';
            if ($type === 'demerit') {
                $cons = checkOrgConsequences($pdo, $orgId, $admin['id']);
                $allConsequences = array_merge($allConsequences, $cons);
            }
        }

        if (empty($applied)) {
            flash('error', 'Please select at least one rule or enter bonus points.');
        } else {
            $msg = 'Applied: ' . implode(' | ', $applied);
            if (!empty($allConsequences)) {
                $msg .= ' ⚠️ Auto-sanctions: ' . implode(', ', array_unique($allConsequences));
            }
            flash('success', $msg);
        }
        header('Location: merit.php?tab=logs'); exit;
    }

    if ($action === 'warn') {
        $orgId  = (int)$_POST['org_id'];
        $reason = trim($_POST['reason'] ?? '');
        $level  = $_POST['level'] ?? 'warning';
        if ($orgId && $reason) {
            $pdo->prepare('INSERT INTO org_warning_letters (organization_id,reason,level,issued_by) VALUES (?,?,?,?)')
                ->execute([$orgId, $reason, $level, $admin['id']]);
            flash('success', 'Warning letter issued.');
        }
        header('Location: merit.php?tab=warnings'); exit;
    }

    if ($action === 'review_letter') {
        $id     = (int)$_POST['letter_id'];
        $status = $_POST['status'] ?? 'accepted';
        $pdo->prepare('UPDATE org_explanation_letters SET status=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?')
            ->execute([$status, $admin['id'], $id]);
        flash('success', 'Explanation letter reviewed.');
        header('Location: merit.php?tab=letters'); exit;
    }
}

// ── Stats ─────────────────────────────────────────────────
$totalMerit     = (int)$pdo->query('SELECT COALESCE(SUM(points),0) FROM org_merit_logs WHERE type="merit"')->fetchColumn();
$totalDemerit   = (int)$pdo->query('SELECT COALESCE(SUM(ABS(points)),0) FROM org_merit_logs WHERE type="demerit"')->fetchColumn();
$pendingLetters = (int)$pdo->query('SELECT COUNT(*) FROM org_explanation_letters WHERE status="pending"')->fetchColumn();
$warningCount   = (int)$pdo->query('SELECT COUNT(*) FROM org_warning_letters')->fetchColumn();

// ── Leaderboard ───────────────────────────────────────────
$leaderboard = $pdo->query('
    SELECT o.id, o.name, o.category, o.barangay, o.is_active,
           COALESCE(SUM(CASE WHEN m.type="merit"   THEN m.points  ELSE 0 END),0) as merit_pts,
           COALESCE(SUM(CASE WHEN m.type="demerit" THEN ABS(m.points) ELSE 0 END),0) as demerit_pts,
           COALESCE(SUM(m.points),0) as net_pts
    FROM organizations o
    LEFT JOIN org_merit_logs m ON m.organization_id = o.id
    GROUP BY o.id
    ORDER BY net_pts DESC, merit_pts DESC
')->fetchAll();

// ── Logs ──────────────────────────────────────────────────
$logs = $pdo->query('
    SELECT m.*, o.name as org_name, a.full_name as admin_name,
           e.title as event_title
    FROM org_merit_logs m
    JOIN organizations o ON o.id = m.organization_id
    LEFT JOIN admin_users a ON a.id = m.awarded_by
    LEFT JOIN events e ON e.id = m.event_id
    ORDER BY m.created_at DESC LIMIT 200
')->fetchAll();

// ── Warnings ─────────────────────────────────────────────
$warnings = $pdo->query('
    SELECT w.*, o.name as org_name, a.full_name as admin_name
    FROM org_warning_letters w
    JOIN organizations o ON o.id = w.organization_id
    LEFT JOIN admin_users a ON a.id = w.issued_by
    ORDER BY w.issued_at DESC
')->fetchAll();

// ── Explanation letters ───────────────────────────────────
$letters = $pdo->query('
    SELECT e.*, o.name as org_name
    FROM org_explanation_letters e
    JOIN organizations o ON o.id = e.organization_id
    ORDER BY e.created_at DESC
')->fetchAll();

// ── Org list for dropdowns ────────────────────────────────
$orgList = $pdo->query('SELECT id, name FROM organizations ORDER BY name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Merit & Demerit – LYDO Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="admin.css"/>
<style>
.tabs{display:flex;gap:4px;margin-bottom:20px;background:#f1f5f9;padding:4px;border-radius:10px;width:fit-content;flex-wrap:wrap}
.tab-btn{padding:8px 16px;border-radius:8px;border:none;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer;color:#475569;background:transparent;transition:.2s;text-decoration:none;display:flex;align-items:center;gap:6px}
.tab-btn.active{background:#fff;color:#1565c0;box-shadow:0 1px 4px rgba(0,0,0,.1)}
.cnt{background:#e53935;color:#fff;border-radius:50px;padding:1px 7px;font-size:.68rem;font-weight:700}
.rank-1{color:#f57f17;font-size:1.1rem}
.rank-2{color:#607d8b}
.rank-3{color:#795548}
.pts-merit{color:#2e7d32;font-weight:700}
.pts-demerit{color:#c62828;font-weight:700}
.pts-pos{color:#1565c0;font-weight:800}
.pts-neg{color:#c62828;font-weight:800}
.warn-warning{background:#fff8e1;color:#f57f17;padding:3px 9px;border-radius:50px;font-size:.72rem;font-weight:700}
.warn-show_cause{background:#ffebee;color:#c62828;padding:3px 9px;border-radius:50px;font-size:.72rem;font-weight:700}
.warn-revocation_flag{background:#c62828;color:#fff;padding:3px 9px;border-radius:50px;font-size:.72rem;font-weight:700}
.letter-pending{background:#fff8e1;color:#f57f17;padding:3px 9px;border-radius:50px;font-size:.72rem;font-weight:700}
.letter-accepted{background:#e8f5e9;color:#2e7d32;padding:3px 9px;border-radius:50px;font-size:.72rem;font-weight:700}
.letter-rejected{background:#ffebee;color:#c62828;padding:3px 9px;border-radius:50px;font-size:.72rem;font-weight:700}
.rule-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px}
.rule-pts{font-size:1.1rem;font-weight:800;min-width:40px;text-align:right}
.btn-approve{padding:5px 12px;background:#e8f5e9;color:#2e7d32;border:1.5px solid #a5d6a7;border-radius:7px;font-family:inherit;font-size:.78rem;font-weight:700;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:5px}
.btn-approve:hover{background:#2e7d32;color:#fff}
.btn-reject{padding:5px 12px;background:#ffebee;color:#c62828;border:1.5px solid #ef9a9a;border-radius:7px;font-family:inherit;font-size:.78rem;font-weight:700;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:5px}
.btn-reject:hover{background:#c62828;color:#fff}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-wrap">
<?php include 'topbar.php'; ?>
<main class="content">

<?php if ($msg=flash('success')): ?><div class="flash success"><i class="fas fa-check-circle"></i><?=htmlspecialchars($msg)?></div><?php endif; ?>
<?php if ($msg=flash('error')):   ?><div class="flash error"><i class="fas fa-exclamation-circle"></i><?=htmlspecialchars($msg)?></div><?php endif; ?>

<div class="page-header">
  <div>
    <h2>Merit & Demerit System</h2>
    <p>Track merit and demerit points per <strong>youth organization</strong> based on MYDC Resolution.</p>
  </div>
  <button class="btn-primary" onclick="document.getElementById('awardModal').style.display='flex'">
    <i class="fas fa-plus"></i> Award / Deduct Points
  </button>
</div>

<!-- STAT CARDS -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
  <div class="stat-card green">
    <div class="stat-icon"><i class="fas fa-star"></i></div>
    <div><span class="stat-val"><?=number_format($totalMerit)?></span><span class="stat-lbl">Total Merit Points</span></div>
  </div>
  <div class="stat-card" style="border:1px solid #e2e8f0">
    <div class="stat-icon" style="background:#ffebee;color:#c62828"><i class="fas fa-minus-circle"></i></div>
    <div><span class="stat-val" style="color:#c62828"><?=number_format($totalDemerit)?></span><span class="stat-lbl">Total Demerit Points</span></div>
  </div>
  <div class="stat-card orange">
    <div class="stat-icon"><i class="fas fa-envelope-open-text"></i></div>
    <div><span class="stat-val"><?=$pendingLetters?></span><span class="stat-lbl">Pending Explanation Letters</span></div>
  </div>
  <div class="stat-card" style="border:1px solid #e2e8f0">
    <div class="stat-icon" style="background:#fff8e1;color:#f57f17"><i class="fas fa-exclamation-triangle"></i></div>
    <div><span class="stat-val" style="color:#f57f17"><?=$warningCount?></span><span class="stat-lbl">Warning Letters Issued</span></div>
  </div>
</div>

<!-- TABS -->
<div class="tabs">
  <a href="?tab=leaderboard" class="tab-btn <?=$tab==='leaderboard'?'active':''?>"><i class="fas fa-trophy"></i> Leaderboard</a>
  <a href="?tab=logs"        class="tab-btn <?=$tab==='logs'?'active':''?>"><i class="fas fa-history"></i> Point Logs</a>
  <a href="?tab=warnings"    class="tab-btn <?=$tab==='warnings'?'active':''?>"><i class="fas fa-exclamation-triangle"></i> Warnings</a>
  <a href="?tab=letters"     class="tab-btn <?=$tab==='letters'?'active':''?>">
    <i class="fas fa-envelope"></i> Explanation Letters
    <?php if ($pendingLetters > 0): ?><span class="cnt"><?=$pendingLetters?></span><?php endif; ?>
  </a>
  <a href="?tab=rules"       class="tab-btn <?=$tab==='rules'?'active':''?>"><i class="fas fa-book"></i> Rules</a>
</div>

<!-- ── LEADERBOARD ── -->
<?php if ($tab === 'leaderboard'): ?>
<div class="card">
  <div class="card-header"><h3><i class="fas fa-trophy"></i> Organization Merit Leaderboard</h3></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr><th>Rank</th><th>Organization</th><th>Category</th><th>Barangay</th><th>Merit</th><th>Demerit</th><th>Net Score</th><th>Status</th><th>Action</th></tr>
      </thead>
      <tbody>
      <?php if (empty($leaderboard)): ?>
        <tr><td colspan="9" class="empty">No merit data yet. Record attendance or award points to get started.</td></tr>
      <?php else: foreach ($leaderboard as $i => $r):
        $rank = $i + 1;
        $medal = $rank===1?'🥇':($rank===2?'🥈':($rank===3?'🥉':$rank));
        $netClass = $r['net_pts'] >= 0 ? 'pts-pos' : 'pts-neg';
      ?>
        <tr>
          <td class="<?=$rank<=3?'rank-'.$rank:''?>"><?=$medal?></td>
          <td><strong><?=htmlspecialchars($r['name'])?></strong></td>
          <td><span class="badge blue"><?=htmlspecialchars($r['category']?:'—')?></span></td>
          <td><?=htmlspecialchars($r['barangay']?:'—')?></td>
          <td class="pts-merit">+<?=$r['merit_pts']?></td>
          <td class="pts-demerit">-<?=$r['demerit_pts']?></td>
          <td class="<?=$netClass?>"><?=($r['net_pts']>=0?'+':'').$r['net_pts']?></td>
          <td><span class="badge <?=$r['is_active']?'green':'gray'?>"><?=$r['is_active']?'Active':'Inactive'?></span></td>
          <td><a href="org_profile.php?id=<?=$r['id']?>" class="btn-icon teal" title="View"><i class="fas fa-eye"></i></a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── POINT LOGS ── -->
<?php elseif ($tab === 'logs'): ?>
<div class="card">
  <div class="card-header"><h3><i class="fas fa-history"></i> Merit & Demerit Logs</h3></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>#</th><th>Organization</th><th>Type</th><th>Points</th><th>Reason</th><th>Event</th><th>Awarded By</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($logs)): ?>
        <tr><td colspan="8" class="empty">No logs yet.</td></tr>
      <?php else: foreach ($logs as $i => $l): ?>
        <tr>
          <td><?=$i+1?></td>
          <td><strong><?=htmlspecialchars($l['org_name'])?></strong></td>
          <td><span class="badge <?=$l['type']==='merit'?'green':'red'?>"><?=ucfirst($l['type'])?></span></td>
          <td class="<?=$l['type']==='merit'?'pts-merit':'pts-demerit'?>"><?=($l['points']>0?'+':'').$l['points']?></td>
          <td>
            <?php if ($l['category'] === 'lydo_head_bonus'): ?>
              <span style="background:#e3f2fd;color:#1565c0;padding:2px 8px;border-radius:50px;font-size:.7rem;font-weight:700;margin-right:4px"><i class="fas fa-award"></i> LYDO Head</span>
            <?php endif; ?>
            <?=htmlspecialchars($l['reason'])?>
          </td>          <td><?=htmlspecialchars($l['event_title']?:'—')?></td>
          <td><?=htmlspecialchars($l['admin_name']?:'System')?></td>
          <td><?=date('M j, Y g:i A',strtotime($l['created_at']))?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── WARNINGS ── -->
<?php elseif ($tab === 'warnings'): ?>
<div class="page-header" style="margin-top:0;margin-bottom:16px">
  <div></div>
  <button class="btn-primary" onclick="document.getElementById('warnModal').style.display='flex'">
    <i class="fas fa-plus"></i> Issue Warning
  </button>
</div>
<div class="card">
  <div class="card-header"><h3><i class="fas fa-exclamation-triangle"></i> Warning Letters</h3></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>#</th><th>Organization</th><th>Level</th><th>Reason</th><th>Issued By</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($warnings)): ?>
        <tr><td colspan="6" class="empty">No warnings issued.</td></tr>
      <?php else: foreach ($warnings as $i => $w): ?>
        <tr>
          <td><?=$i+1?></td>
          <td><strong><?=htmlspecialchars($w['org_name'])?></strong></td>
          <td><span class="warn-<?=$w['level']?>"><?=ucwords(str_replace('_',' ',$w['level']))?></span></td>
          <td style="max-width:300px"><?=htmlspecialchars(substr($w['reason'],0,100))?>...</td>
          <td><?=htmlspecialchars($w['admin_name']?:'System')?></td>
          <td><?=date('M j, Y',strtotime($w['issued_at']))?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── EXPLANATION LETTERS ── -->
<?php elseif ($tab === 'letters'): ?>
<div class="card">
  <div class="card-header"><h3><i class="fas fa-envelope"></i> Explanation Letters from Organizations</h3></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>#</th><th>Organization</th><th>Subject</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (empty($letters)): ?>
        <tr><td colspan="6" class="empty">No explanation letters submitted.</td></tr>
      <?php else: foreach ($letters as $i => $l): ?>
        <tr>
          <td><?=$i+1?></td>
          <td><strong><?=htmlspecialchars($l['org_name'])?></strong></td>
          <td><?=htmlspecialchars($l['subject'])?></td>
          <td><span class="letter-<?=$l['status']?>"><?=ucfirst($l['status'])?></span></td>
          <td><?=date('M j, Y',strtotime($l['created_at']))?></td>
          <td>
            <?php if ($l['status']==='pending'): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="review_letter"/>
              <input type="hidden" name="letter_id" value="<?=$l['id']?>"/>
              <input type="hidden" name="status" value="accepted"/>
              <button type="submit" class="btn-approve"><i class="fas fa-check"></i> Accept</button>
            </form>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="review_letter"/>
              <input type="hidden" name="letter_id" value="<?=$l['id']?>"/>
              <input type="hidden" name="status" value="rejected"/>
              <button type="submit" class="btn-reject"><i class="fas fa-times"></i> Reject</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── RULES REFERENCE ── -->
<?php elseif ($tab === 'rules'): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
  <div class="card">
    <div class="card-header"><h3><i class="fas fa-star" style="color:#2e7d32"></i> Merit Rules</h3></div>
    <div style="padding:14px 16px">
      <?php foreach (MERIT_RULES as $key => $rule): if ($rule['type']==='merit'): ?>
      <div class="rule-card">
        <div><div style="font-weight:600;font-size:.88rem"><?=htmlspecialchars($rule['label'])?></div><div style="font-size:.72rem;color:#94a3b8;margin-top:2px"><?=$key?></div></div>
        <div class="rule-pts pts-merit">+<?=$rule['points']?></div>
      </div>
      <?php endif; endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3><i class="fas fa-minus-circle" style="color:#c62828"></i> Demerit Rules</h3></div>
    <div style="padding:14px 16px">
      <?php foreach (MERIT_RULES as $key => $rule): if ($rule['type']==='demerit'): ?>
      <div class="rule-card">
        <div><div style="font-weight:600;font-size:.88rem"><?=htmlspecialchars($rule['label'])?></div><div style="font-size:.72rem;color:#94a3b8;margin-top:2px"><?=$key?></div></div>
        <div class="rule-pts pts-demerit"><?=$rule['points']?></div>
      </div>
      <?php endif; endforeach; ?>
    </div>
  </div>
</div>
<div class="card mt-16">
  <div class="card-header"><h3><i class="fas fa-exclamation-triangle" style="color:#f57f17"></i> Automated Consequences</h3></div>
  <div style="padding:14px 16px">
    <?php foreach (DEMERIT_THRESHOLDS as $pts => $c): ?>
    <div class="rule-card">
      <div>
        <div style="font-weight:700;font-size:.9rem"><?=htmlspecialchars($c['label'])?></div>
        <div style="font-size:.78rem;color:#94a3b8;margin-top:2px">Auto-generated when organization's total demerit reaches <?=$pts?> points</div>
      </div>
      <div class="rule-pts pts-demerit"><?=$pts?>pts</div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

</main>
</div>

<!-- AWARD POINTS MODAL -->
<div id="awardModal" class="modal-overlay" style="display:none">
  <div class="modal-box" style="max-width:640px">
    <div class="modal-head"><h3><i class="fas fa-star"></i> Award / Deduct Points</h3>
      <button onclick="document.getElementById('awardModal').style.display='none'" class="modal-close"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" class="modal-body">
      <input type="hidden" name="action" value="award"/>

      <!-- Organization -->
      <div class="fg">
        <label>Organization <span class="req">*</span></label>
        <select name="org_id" required>
          <option value="">Select organization...</option>
          <?php foreach ($orgList as $o): ?>
            <option value="<?=$o['id']?>"><?=htmlspecialchars($o['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Multi-select rules -->
      <div class="fg">
        <label>Select Rules <small style="color:#94a3b8;font-weight:400">(check all that apply)</small></label>
        <div style="border:1.5px solid #e2e8f0;border-radius:10px;overflow:hidden">

          <div style="background:#e8f5e9;padding:8px 14px;font-size:.75rem;font-weight:700;color:#2e7d32;text-transform:uppercase;letter-spacing:.04em">
            <i class="fas fa-star"></i> Merit Rules
          </div>
          <?php foreach (MERIT_RULES as $key => $rule): if ($rule['type']==='merit'): ?>
          <label style="display:flex;align-items:center;gap:10px;padding:9px 14px;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:.15s" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
            <input type="checkbox" name="rule_keys[]" value="<?=$key?>" style="width:16px;height:16px;accent-color:#2e7d32;cursor:pointer"/>
            <span style="flex:1;font-size:.88rem;color:#1e293b"><?=htmlspecialchars($rule['label'])?></span>
            <span style="font-weight:800;color:#2e7d32;font-size:.9rem">+<?=$rule['points']?></span>
          </label>
          <?php endif; endforeach; ?>

          <div style="background:#ffebee;padding:8px 14px;font-size:.75rem;font-weight:700;color:#c62828;text-transform:uppercase;letter-spacing:.04em">
            <i class="fas fa-minus-circle"></i> Demerit Rules
          </div>
          <?php foreach (MERIT_RULES as $key => $rule): if ($rule['type']==='demerit'): ?>
          <label style="display:flex;align-items:center;gap:10px;padding:9px 14px;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:.15s" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
            <input type="checkbox" name="rule_keys[]" value="<?=$key?>" style="width:16px;height:16px;accent-color:#c62828;cursor:pointer"/>
            <span style="flex:1;font-size:.88rem;color:#1e293b"><?=htmlspecialchars($rule['label'])?></span>
            <span style="font-weight:800;color:#c62828;font-size:.9rem"><?=$rule['points']?></span>
          </label>
          <?php endif; endforeach; ?>
        </div>
      </div>

      <!-- LYDO Head Bonus -->
      <div style="background:#e3f2fd;border:1.5px solid #90caf9;border-radius:10px;padding:14px 16px;margin-top:4px">
        <div style="font-size:.82rem;font-weight:700;color:#1565c0;margin-bottom:10px;display:flex;align-items:center;gap:7px">
          <i class="fas fa-award"></i> LYDO Head Bonus Points
          <span style="font-size:.72rem;font-weight:400;color:#64748b">(optional — positive for bonus, negative for penalty)</span>
        </div>
        <div style="display:grid;grid-template-columns:120px 1fr;gap:10px">
          <div class="fg" style="margin-bottom:0">
            <label style="font-size:.78rem">Points</label>
            <input type="number" name="bonus_points" placeholder="e.g. 3 or -2" style="padding:8px 11px;border:1.5px solid #90caf9;border-radius:8px;font-family:inherit;font-size:.88rem;outline:none;width:100%;background:#fff"/>
          </div>
          <div class="fg" style="margin-bottom:0">
            <label style="font-size:.78rem">Reason / Note <span style="color:#94a3b8;font-weight:400">(required if points entered)</span></label>
            <input type="text" name="bonus_note" placeholder="e.g. Outstanding performance at Regional Summit" style="padding:8px 11px;border:1.5px solid #90caf9;border-radius:8px;font-family:inherit;font-size:.88rem;outline:none;width:100%;background:#fff"/>
          </div>
        </div>
      </div>

      <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:16px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;border-radius:0 0 16px 16px;margin:0 -20px -20px">
        <button type="button"
          onclick="document.getElementById('awardModal').style.display='none'"
          style="padding:10px 22px;background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.9rem;font-weight:600;cursor:pointer;transition:.2s"
          onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
          Cancel
        </button>
        <button type="submit"
          style="padding:10px 24px;background:linear-gradient(135deg,#0d3b6e,#1565c0);color:#fff;border:none;border-radius:9px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;transition:.2s;box-shadow:0 4px 12px rgba(21,101,192,.3)"
          onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform=''">
          <i class="fas fa-bolt"></i> Apply Points
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ISSUE WARNING MODAL -->
<div id="warnModal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="modal-head" style="background:linear-gradient(135deg,#b71c1c,#c62828)">
      <h3><i class="fas fa-exclamation-triangle"></i> Issue Warning Letter</h3>
      <button onclick="document.getElementById('warnModal').style.display='none'" class="modal-close"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" class="modal-body">
      <input type="hidden" name="action" value="warn"/>
      <div class="fg"><label>Organization <span class="req">*</span></label>
        <select name="org_id" required>
          <option value="">Select organization...</option>
          <?php foreach ($orgList as $o): ?>
            <option value="<?=$o['id']?>"><?=htmlspecialchars($o['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg"><label>Warning Level</label>
        <select name="level">
          <option value="warning">Warning Letter</option>
          <option value="show_cause">Show Cause Order</option>
          <option value="revocation_flag">Membership Revocation Flag</option>
        </select>
      </div>
      <div class="fg"><label>Reason <span class="req">*</span></label>
        <textarea name="reason" rows="3" required placeholder="Explain the reason for this warning..."></textarea>
      </div>
      <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:16px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;border-radius:0 0 16px 16px;margin:0 -20px -20px">
        <button type="button"
          onclick="document.getElementById('warnModal').style.display='none'"
          style="padding:10px 22px;background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.9rem;font-weight:600;cursor:pointer;transition:.2s"
          onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
          Cancel
        </button>
        <button type="submit"
          style="padding:10px 24px;background:linear-gradient(135deg,#b71c1c,#c62828);color:#fff;border:none;border-radius:9px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;transition:.2s;box-shadow:0 4px 12px rgba(198,40,40,.3)"
          onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform=''">
          <i class="fas fa-paper-plane"></i> Issue Warning
        </button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
