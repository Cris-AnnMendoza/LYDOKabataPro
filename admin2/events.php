<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';
require_once 'merit_engine.php';
requireLogin();

$pdo   = db();
$admin = currentAdmin();
$tab   = $_GET['tab'] ?? 'list';

// ── Handle POST ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_event') {
        $pdo->prepare(
            'INSERT INTO events (title, event_type, requires_representative, description, event_date, event_time, location, organization_id, merit_points, quarter, year, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            trim($_POST['title']),
            $_POST['event_type'] ?? 'official_event',
            isset($_POST['requires_representative']) ? 1 : 0,
            trim($_POST['description'] ?? ''),
            $_POST['event_date'],
            $_POST['event_time'] ?: null,
            trim($_POST['location'] ?? ''),
            $_POST['organization_id'] ?: null,
            (int)($_POST['merit_points'] ?? 0),
            $_POST['quarter'] ?: null,
            $_POST['year'] ?: date('Y'),
            $admin['id'],
        ]);
        flash('success', 'Event created successfully.');
        header('Location: events.php'); exit;
    }

    if ($action === 'record_attendance') {
        $eventId    = (int)$_POST['event_id'];
        $attendance = $_POST['attendance'] ?? [];  // [user_id => status]

        // Handle "no representative" for orgs that didn't send anyone
        $noRepOrgs = $_POST['no_rep_orgs'] ?? [];
        foreach ($noRepOrgs as $userId) {
            $attendance[(int)$userId] = 'absent';
            // Also apply no-representative demerit
            awardOrgPoints($pdo, (int)$userId, 'no_representative', $admin['id'], $eventId);
        }

        $result = processEventAttendance($pdo, $eventId, $attendance, $admin['id']);

        $triggered = [];
        foreach ($result['results'] ?? [] as $r) {
            $triggered = array_merge($triggered, $r['consequences'] ?? []);
        }

        $msg = 'Attendance recorded. Points applied automatically.';
        if ($triggered) {
            $msg .= ' Auto-sanctions triggered: ' . implode(', ', array_unique($triggered)) . '.';
        }
        flash('success', $msg);
        header('Location: events.php?tab=attendance&event_id=' . $eventId); exit;
    }

    if ($action === 'award_manual') {
        $orgId   = (int)$_POST['org_id'];
        $ruleKey = $_POST['rule_key'] ?? '';
        if ($orgId && $ruleKey && isset(MERIT_RULES[$ruleKey])) {
            $result = awardOrgPoints($pdo, $orgId, $ruleKey, $admin['id']);
            $msg = 'Points applied: ' . MERIT_RULES[$ruleKey]['label']
                 . ' (' . ($result['points'] > 0 ? '+' : '') . $result['points'] . ')';
            if (!empty($result['consequences'])) {
                $msg .= ' | Auto-sanctions: ' . implode(', ', $result['consequences']);
            }
            flash('success', $msg);
        }
        header('Location: events.php?tab=manual'); exit;
    }
}

// ── Data ──────────────────────────────────────────────────
$events = $pdo->query(
    'SELECT e.*, o.name as org_name,
     (SELECT COUNT(*) FROM event_attendance a WHERE a.event_id = e.id) as recorded
     FROM events e LEFT JOIN organizations o ON o.id = e.organization_id
     ORDER BY e.event_date DESC'
)->fetchAll();

$orgs      = $pdo->query('SELECT id, name FROM organizations WHERE is_active=1 ORDER BY name')->fetchAll();
$youthList = $pdo->query('SELECT id, first_name, last_name FROM youth_users WHERE status="approved" ORDER BY first_name')->fetchAll();

// Attendance tab
$selectedEvent = null;
$attendanceList = [];
if ($tab === 'attendance' && isset($_GET['event_id'])) {
    $eid = (int)$_GET['event_id'];
    $selectedEvent = $pdo->prepare('SELECT * FROM events WHERE id=?');
    $selectedEvent->execute([$eid]);
    $selectedEvent = $selectedEvent->fetch();

    $attendanceList = $pdo->prepare(
        'SELECT o.id, o.name, o.category, o.barangay,
                COALESCE(MAX(a.status),"not_recorded") as att_status,
                COALESCE(SUM(CASE WHEN m.type="merit" THEN m.points ELSE 0 END),0) as merit,
                COALESCE(SUM(CASE WHEN m.type="demerit" THEN ABS(m.points) ELSE 0 END),0) as demerit
         FROM organizations o
         LEFT JOIN event_attendance a ON a.event_id=? AND a.organization_id=o.id
         LEFT JOIN org_merit_logs m ON m.organization_id=o.id
         WHERE o.is_active=1
         GROUP BY o.id, o.name, o.category, o.barangay ORDER BY o.name'
    );
    $attendanceList->execute([$eid]);
    $attendanceList = $attendanceList->fetchAll();
}

$eventTypeLabels = [
    'official_event'  => 'Official MYDC/LYDO Event',
    'meeting_patawag' => 'Meeting / Patawag',
    'invitation'      => 'Fellow Org Invitation',
    'other'           => 'Other',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Events & Attendance – LYDO Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="admin.css"/>
<style>
.tabs{display:flex;gap:4px;margin-bottom:20px;background:#f1f5f9;padding:4px;border-radius:10px;width:fit-content}
.tab-btn{padding:8px 16px;border-radius:8px;border:none;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer;color:#475569;background:transparent;transition:.2s;text-decoration:none}
.tab-btn.active{background:#fff;color:#1565c0;box-shadow:0 1px 4px rgba(0,0,0,.1)}
.att-row{display:grid;grid-template-columns:2fr 1fr 1fr 1.5fr;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:.85rem}
.att-row:last-child{border-bottom:none}
.att-row:hover{background:#f8fafc}
.att-header{background:#f1f5f9;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.04em;color:#475569;border-radius:8px 8px 0 0}
.att-radio{display:flex;gap:8px;flex-wrap:wrap}
.att-radio label{display:flex;align-items:center;gap:4px;cursor:pointer;font-size:.8rem;padding:4px 8px;border-radius:6px;border:1.5px solid #e2e8f0;transition:.2s}
.att-radio input{display:none}
.att-radio input:checked+span{font-weight:700}
.att-radio label:has(input[value="present"]:checked){background:#e8f5e9;border-color:#a5d6a7;color:#2e7d32}
.att-radio label:has(input[value="absent"]:checked){background:#ffebee;border-color:#ef9a9a;color:#c62828}
.att-radio label:has(input[value="excused"]:checked){background:#fff8e1;border-color:#ffe082;color:#f57f17}
.att-radio label:has(input[value="late"]:checked){background:#e3f2fd;border-color:#90caf9;color:#1565c0}
.att-radio label:has(input[value="representative"]:checked){background:#f3e5f5;border-color:#ce93d8;color:#7b1fa2}
.rule-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px}
.rule-pts{font-size:1.1rem;font-weight:800;min-width:40px;text-align:right}
.pts-pos{color:#2e7d32}
.pts-neg{color:#c62828}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-wrap">
<?php include 'topbar.php'; ?>
<main class="content">

<?php if ($msg=flash('success')): ?><div class="flash success"><i class="fas fa-check-circle"></i><?=htmlspecialchars($msg)?></div><?php endif; ?>

<div class="page-header">
  <div><h2>Events & Attendance</h2><p>Manage events and record attendance to auto-apply merit/demerit points.</p></div>
  <?php if ($tab==='list'): ?>
  <button class="btn-primary" onclick="document.getElementById('addEventModal').style.display='flex'">
    <i class="fas fa-plus"></i> Add Event
  </button>
  <?php endif; ?>
</div>

<div class="tabs">
  <a href="?tab=list"       class="tab-btn <?=$tab==='list'?'active':''?>"><i class="fas fa-calendar-alt"></i> Events</a>
  <a href="?tab=attendance" class="tab-btn <?=$tab==='attendance'?'active':''?>"><i class="fas fa-clipboard-check"></i> Record Attendance</a>
  <a href="?tab=manual"     class="tab-btn <?=$tab==='manual'?'active':''?>"><i class="fas fa-hand-pointer"></i> Manual Award</a>
  <a href="?tab=rules"      class="tab-btn <?=$tab==='rules'?'active':''?>"><i class="fas fa-book"></i> Point Rules</a>
</div>

<?php if ($tab === 'list'): ?>
<!-- EVENTS LIST -->
<div class="card">
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>#</th><th>Event</th><th>Type</th><th>Date</th><th>Location</th><th>Organization</th><th>Merit Pts</th><th>Recorded</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (empty($events)): ?>
        <tr><td colspan="9" class="empty">No events yet. Add one to start tracking attendance.</td></tr>
      <?php else: foreach ($events as $i => $ev): ?>
        <tr>
          <td><?=$i+1?></td>
          <td><strong><?=htmlspecialchars($ev['title'])?></strong></td>
          <td><span class="badge blue"><?=htmlspecialchars($eventTypeLabels[$ev['event_type']]??$ev['event_type'])?></span></td>
          <td><?=date('M j, Y',strtotime($ev['event_date']))?></td>
          <td><?=htmlspecialchars($ev['location']?:'—')?></td>
          <td><?=htmlspecialchars($ev['org_name']?:'—')?></td>
          <td><strong>+<?=$ev['merit_points']?></strong></td>
          <td><?=$ev['recorded']?> attendees</td>
          <td style="display:flex;gap:6px">
            <a href="?tab=attendance&event_id=<?=$ev['id']?>" class="btn-primary" style="padding:6px 12px;font-size:.8rem"><i class="fas fa-clipboard-check"></i> Record</a>
            <a href="event_qr.php?id=<?=$ev['id']?>" class="btn-secondary" style="padding:6px 12px;font-size:.8rem"><i class="fas fa-qrcode"></i> QR</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif ($tab === 'attendance'): ?>
<!-- ATTENDANCE RECORDING -->
<?php if (!$selectedEvent): ?>
  <div class="card">
    <div class="card-header"><h3><i class="fas fa-calendar-alt"></i> Select an Event</h3></div>
    <div style="padding:16px 18px;display:flex;flex-direction:column;gap:8px">
      <?php foreach ($events as $ev): ?>
        <a href="?tab=attendance&event_id=<?=$ev['id']?>" style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:10px;text-decoration:none;color:#1e293b;transition:.2s" onmouseover="this.style.borderColor='#1565c0'" onmouseout="this.style.borderColor='#e2e8f0'">
          <div><strong><?=htmlspecialchars($ev['title'])?></strong> <span class="badge blue" style="margin-left:8px"><?=htmlspecialchars($eventTypeLabels[$ev['event_type']]??'')?></span></div>
          <span style="font-size:.82rem;color:#94a3b8"><?=date('M j, Y',strtotime($ev['event_date']))?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php else: ?>
  <div class="card" style="margin-bottom:16px">
    <div class="card-header">
      <h3><i class="fas fa-calendar-check"></i> <?=htmlspecialchars($selectedEvent['title'])?></h3>
      <span class="badge blue"><?=htmlspecialchars($eventTypeLabels[$selectedEvent['event_type']]??'')?></span>
    </div>
    <div style="padding:12px 18px;display:flex;gap:24px;flex-wrap:wrap;font-size:.85rem;color:#475569">
      <span><i class="fas fa-calendar" style="margin-right:5px"></i><?=date('F j, Y',strtotime($selectedEvent['event_date']))?></span>
      <span><i class="fas fa-map-marker-alt" style="margin-right:5px"></i><?=htmlspecialchars($selectedEvent['location']?:'—')?></span>
      <span><i class="fas fa-star" style="margin-right:5px;color:#2e7d32"></i>+<?=$selectedEvent['merit_points']?> merit pts for attendance</span>
    </div>
  </div>

  <form method="POST">
    <input type="hidden" name="action" value="record_attendance"/>
    <input type="hidden" name="event_id" value="<?=$selectedEvent['id']?>"/>

    <div class="card">
      <div class="att-row att-header">
        <div>Organization</div><div>Barangay</div><div>Current Score</div><div>Attendance Status</div>
      </div>
      <?php foreach ($attendanceList as $u): ?>
      <div class="att-row">
        <div>
          <strong><?=htmlspecialchars($u['name'])?></strong>
          <span class="badge blue" style="margin-left:6px;font-size:.68rem"><?=htmlspecialchars($u['category']?:'—')?></span>
        </div>
        <div><?=htmlspecialchars($u['barangay']?:'—')?></div>
        <div>
          <span style="color:#2e7d32;font-weight:600">+<?=$u['merit']?></span>
          <span style="color:#c62828;font-weight:600;margin-left:6px">-<?=$u['demerit']?></span>
        </div>
        <div class="att-radio">
          <?php
          $cur = $u['att_status'];
          foreach (['present'=>'Present','absent'=>'Absent','excused'=>'Excused','late'=>'Late','representative'=>'Rep.'] as $val => $lbl):
          ?>
          <label>
            <input type="radio" name="attendance[<?=$u['id']?>]" value="<?=$val?>" <?=$cur===$val?'checked':''?>>
            <span><?=$lbl?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="display:flex;gap:10px;margin-top:16px">
      <button type="submit" class="btn-primary" style="width:auto;padding:11px 28px">
        <i class="fas fa-save"></i> Save Attendance & Apply Points
      </button>
      <a href="?tab=list" class="btn-secondary">Cancel</a>
    </div>
  </form>
<?php endif; ?>

<?php elseif ($tab === 'manual'): ?>
<!-- MANUAL AWARD -->
<div class="card">
  <div class="card-header"><h3><i class="fas fa-hand-pointer"></i> Manual Point Award</h3></div>
  <form method="POST" class="modal-body" style="padding:20px">
    <input type="hidden" name="action" value="award_manual"/>
    <div class="form-row-2">
      <div class="fg"><label>Organization <span class="req">*</span></label>
        <select name="org_id" required>
          <option value="">Select organization...</option>
          <?php foreach ($orgs as $o): ?>
            <option value="<?=$o['id']?>"><?=htmlspecialchars($o['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg"><label>Rule / Reason <span class="req">*</span></label>
        <select name="rule_key" required>
          <option value="">Select rule...</option>
          <optgroup label="Merit (+)">
            <?php foreach (MERIT_RULES as $key => $rule): if ($rule['type']==='merit'): ?>
              <option value="<?=$key?>"><?=$rule['label']?> (+<?=$rule['points']?>)</option>
            <?php endif; endforeach; ?>
          </optgroup>
          <optgroup label="Demerit (-)">
            <?php foreach (MERIT_RULES as $key => $rule): if ($rule['type']==='demerit'): ?>
              <option value="<?=$key?>"><?=$rule['label']?> (<?=$rule['points']?>)</option>
            <?php endif; endforeach; ?>
          </optgroup>
        </select>
      </div>
    </div>
    <button type="submit" class="btn-primary" style="width:auto;padding:11px 28px">
      <i class="fas fa-bolt"></i> Apply Points
    </button>
  </form>
</div>

<?php elseif ($tab === 'rules'): ?>
<!-- POINT RULES REFERENCE -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
  <div class="card">
    <div class="card-header"><h3><i class="fas fa-star" style="color:#2e7d32"></i> Merit Rules</h3></div>
    <div style="padding:14px 16px">
      <?php foreach (MERIT_RULES as $key => $rule): if ($rule['type']==='merit'): ?>
      <div class="rule-card">
        <div>
          <div style="font-weight:600;font-size:.88rem"><?=htmlspecialchars($rule['label'])?></div>
          <div style="font-size:.75rem;color:#94a3b8;margin-top:2px">Rule: <?=$key?></div>
        </div>
        <div class="rule-pts pts-pos">+<?=$rule['points']?></div>
      </div>
      <?php endif; endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3><i class="fas fa-minus-circle" style="color:#c62828"></i> Demerit Rules</h3></div>
    <div style="padding:14px 16px">
      <?php foreach (MERIT_RULES as $key => $rule): if ($rule['type']==='demerit'): ?>
      <div class="rule-card">
        <div>
          <div style="font-weight:600;font-size:.88rem"><?=htmlspecialchars($rule['label'])?></div>
          <div style="font-size:.75rem;color:#94a3b8;margin-top:2px">Rule: <?=$key?></div>
        </div>
        <div class="rule-pts pts-neg"><?=$rule['points']?></div>
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
        <div style="font-size:.78rem;color:#94a3b8;margin-top:2px">Auto-generated when total demerit reaches <?=$pts?> points</div>
      </div>
      <div class="rule-pts pts-neg"><?=$pts?>pts</div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

</main>
</div>

<!-- ADD EVENT MODAL -->
<div id="addEventModal" class="modal-overlay" style="display:none">
  <div class="modal-box" style="max-width:620px">
    <div class="modal-head"><h3><i class="fas fa-calendar-plus"></i> Add Event</h3><button onclick="document.getElementById('addEventModal').style.display='none'" class="modal-close"><i class="fas fa-times"></i></button></div>
    <form method="POST" class="modal-body">
      <input type="hidden" name="action" value="add_event"/>
      <div class="form-row-2">
        <div class="fg"><label>Event Title <span class="req">*</span></label><input type="text" name="title" required placeholder="e.g. Youth Leadership Summit 2026"/></div>
        <div class="fg"><label>Event Type <span class="req">*</span></label>
          <select name="event_type" required>
            <?php foreach ($eventTypeLabels as $val => $lbl): ?>
              <option value="<?=$val?>"><?=$lbl?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Date <span class="req">*</span></label><input type="date" name="event_date" required value="<?=date('Y-m-d')?>"/></div>
        <div class="fg"><label>Time</label><input type="time" name="event_time"/></div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Location</label><input type="text" name="location" placeholder="e.g. Municipal Hall"/></div>
        <div class="fg"><label>Organization</label>
          <select name="organization_id">
            <option value="">All / General</option>
            <?php foreach ($orgs as $o): ?><option value="<?=$o['id']?>"><?=htmlspecialchars($o['name'])?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Quarter</label>
          <select name="quarter"><option value="">—</option><?php for($q=1;$q<=4;$q++): ?><option value="<?=$q?>">Q<?=$q?></option><?php endfor; ?></select>
        </div>
        <div class="fg"><label>Year</label><input type="number" name="year" value="<?=date('Y')?>" min="2020" max="2030"/></div>
      </div>
      <div class="fg"><label>Description</label><textarea name="description" rows="2" placeholder="Brief description..."></textarea></div>
      <label class="chk-label" style="margin-bottom:16px"><input type="checkbox" name="requires_representative"/><span class="chk"></span> Requires official representative</label>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="document.getElementById('addEventModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Create Event</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
