<?php
require_once "config.php";
requireLogin();
$pdo   = db();
$admin = currentAdmin();
$tab   = $_GET["tab"] ?? "registrations";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $regId  = (int)($_POST["reg_id"] ?? 0);

    if ($action === "update_status" && $regId) {
        $status  = $_POST["status"] ?? "pending";
        $oriDate = $_POST["orientation_date"] ?: null;
        $notes   = trim($_POST["notes"] ?? "");
        $pdo->prepare("UPDATE volunteer_registrations SET status=?,orientation_date=?,notes=? WHERE id=?")
            ->execute([$status, $oriDate, $notes, $regId]);

        // Notify the youth user
        $reg = $pdo->prepare("SELECT r.user_id, p.name as prog_name FROM volunteer_registrations r JOIN volunteer_programs p ON p.id=r.program_id WHERE r.id=?");
        $reg->execute([$regId]);
        $regRow = $reg->fetch();
        if ($regRow) {
            $statusLabels = ['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','completed'=>'Completed'];
            $statusLabel  = $statusLabels[$status] ?? ucfirst($status);
            $msg = "Your registration for \"{$regRow['prog_name']}\" has been updated to: $statusLabel.";
            if ($oriDate && $status === 'approved') {
                $msg .= " Orientation date: " . date('F j, Y', strtotime($oriDate)) . ".";
            }
            $pdo->prepare("INSERT INTO notifications (user_id,type,title,category,message,is_read) VALUES (?,?,?,?,?,0)")
                ->execute([$regRow['user_id'], 'volunteer', 'Volunteer Registration Update', 'approval', $msg]);
        }

        flash("success", "Registration updated.");
        header("Location: volunteer_admin.php?tab=registrations"); exit;
    }
    if ($action === "record_attendance" && $regId) {
        $eventName = trim($_POST["event_name"] ?? "");
        $eventDate = $_POST["event_date"] ?? date("Y-m-d");
        $hours     = (float)($_POST["hours"] ?? 0);
        $status    = $_POST["att_status"] ?? "present";
        if ($eventName) {
            $pdo->prepare("INSERT INTO volunteer_attendance (registration_id,event_name,event_date,hours,status,recorded_by) VALUES (?,?,?,?,?,?)")
                ->execute([$regId, $eventName, $eventDate, $hours, $status, $admin["id"]]);
            // Update total hours
            $pdo->prepare("UPDATE volunteer_registrations SET total_hours=total_hours+? WHERE id=?")
                ->execute([$hours, $regId]);
            flash("success", "Attendance recorded.");
        }
        header("Location: volunteer_admin.php?tab=attendance"); exit;
    }
    if ($action === "issue_certificate" && $regId) {
        $pdo->prepare("UPDATE volunteer_registrations SET certificate_issued=1 WHERE id=?")->execute([$regId]);
        flash("success", "Certificate issued.");
        header("Location: volunteer_admin.php?tab=registrations"); exit;
    }
    if ($action === "add_program") {
        $pdo->prepare("INSERT INTO volunteer_programs (name,type,description,min_age,max_age,slots,start_date,end_date,location,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([trim($_POST["name"]),trim($_POST["type"]),trim($_POST["description"]??""),(int)$_POST["min_age"],(int)$_POST["max_age"],(int)$_POST["slots"],$_POST["start_date"]?:null,$_POST["end_date"]?:null,trim($_POST["location"]??""),$admin["id"]]);
        flash("success", "Program added.");
        header("Location: volunteer_admin.php?tab=programs"); exit;
    }

    if ($action === "add_event") {
        $pdo->prepare("INSERT INTO volunteer_events (program_id,title,event_date,hours,location,created_by) VALUES (?,?,?,?,?,?)")
            ->execute([(int)$_POST["program_id"],trim($_POST["title"]),$_POST["event_date"],(float)$_POST["hours"],trim($_POST["location"]??""),$admin["id"]]);
        flash("success", "Event added.");
        header("Location: volunteer_admin.php?tab=attendance"); exit;
    }

    if ($action === "confirm_attendance") {
        $attId = (int)$_POST["att_id"];
        $pdo->prepare("UPDATE volunteer_attendance SET confirmed=1,confirmed_by=? WHERE id=?")->execute([$admin["id"],$attId]);
        // Add hours to total if self-submitted (not yet counted)
        $att = $pdo->prepare("SELECT * FROM volunteer_attendance WHERE id=?");
        $att->execute([$attId]);
        $attRow = $att->fetch();
        if ($attRow && $attRow['submitted_by'] !== 'admin' && $attRow['status'] === 'present') {
            // Hours already added on self check-in, just confirm
        }
        flash("success", "Attendance confirmed.");
        header("Location: volunteer_admin.php?tab=attendance"); exit;
    }

    if ($action === "reject_attendance") {
        $attId = (int)$_POST["att_id"];
        $att = $pdo->prepare("SELECT * FROM volunteer_attendance WHERE id=?");
        $att->execute([$attId]);
        $attRow = $att->fetch();
        if ($attRow && $attRow['submitted_by'] !== 'admin' && $attRow['status'] === 'present') {
            // Deduct hours since they were added on check-in
            $pdo->prepare("UPDATE volunteer_registrations SET total_hours=GREATEST(0,total_hours-?) WHERE id=?")
                ->execute([$attRow['hours'], $attRow['registration_id']]);
        }
        $pdo->prepare("DELETE FROM volunteer_attendance WHERE id=?")->execute([$attId]);
        flash("success", "Attendance rejected and removed.");
        header("Location: volunteer_admin.php?tab=attendance"); exit;
    }
}

$programs = $pdo->query("SELECT p.*,(SELECT COUNT(*) FROM volunteer_registrations r WHERE r.program_id=p.id) as reg_count FROM volunteer_programs p ORDER BY p.created_at DESC")->fetchAll();
$regs = $pdo->prepare("SELECT r.*,u.first_name,u.last_name,u.email,p.name as prog_name,p.type as prog_type FROM volunteer_registrations r JOIN youth_users u ON u.id=r.user_id JOIN volunteer_programs p ON p.id=r.program_id ORDER BY r.created_at DESC");
$regs->execute();
$registrations = $regs->fetchAll();
$events = $pdo->query("SELECT ve.*, p.name as prog_name FROM volunteer_events ve JOIN volunteer_programs p ON p.id=ve.program_id ORDER BY ve.event_date DESC")->fetchAll();
$pendingAtt = $pdo->query("SELECT a.*,u.first_name,u.last_name,p.name as prog_name FROM volunteer_attendance a JOIN volunteer_registrations r ON r.id=a.registration_id JOIN youth_users u ON u.id=r.user_id JOIN volunteer_programs p ON p.id=r.program_id WHERE a.confirmed=0 ORDER BY a.event_date DESC")->fetchAll();
$attendance = $pdo->query("SELECT a.*,u.first_name,u.last_name,p.name as prog_name FROM volunteer_attendance a JOIN volunteer_registrations r ON r.id=a.registration_id JOIN youth_users u ON u.id=r.user_id JOIN volunteer_programs p ON p.id=r.program_id ORDER BY a.event_date DESC LIMIT 100")->fetchAll();
$leaderboard = $pdo->query("SELECT r.user_id,u.first_name,u.last_name,u.barangay,SUM(r.total_hours) as total_hours,COUNT(r.id) as programs FROM volunteer_registrations r JOIN youth_users u ON u.id=r.user_id WHERE r.status IN (\"approved\",\"completed\") GROUP BY r.user_id ORDER BY total_hours DESC LIMIT 20")->fetchAll();
$statusColors = ["pending"=>["#f57f17","#fff8e1"],"approved"=>["#2e7d32","#e8f5e9"],"rejected"=>["#c62828","#ffebee"],"completed"=>["#00796b","#e0f2f1"]];
$typeLabels = ["youth_volunteer"=>"Youth Volunteer","linggo_kabataan"=>"Linggo ng Kabataan","junior_officials"=>"Junior Officials"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Volunteer Program  LYDO Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="admin.css"/>
<style>
.tabs{display:flex;gap:4px;margin-bottom:20px;background:#f1f5f9;padding:4px;border-radius:10px;width:fit-content;flex-wrap:wrap}
.tab-btn{padding:8px 16px;border-radius:8px;border:none;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer;color:#475569;background:transparent;transition:.2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.tab-btn.active{background:#fff;color:#1565c0;box-shadow:0 1px 4px rgba(0,0,0,.1)}
.tab-btn:hover:not(.active){background:rgba(255,255,255,.6)}
/* Action button group in table */
.action-group{display:flex;align-items:center;gap:6px;flex-wrap:nowrap}
.act-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:7px;border:1.5px solid;font-family:inherit;font-size:.78rem;font-weight:700;cursor:pointer;transition:.2s;white-space:nowrap}
.act-btn-edit{background:#e3f2fd;color:#1565c0;border-color:#90caf9}
.act-btn-edit:hover{background:#1565c0;color:#fff;border-color:#1565c0}
.act-btn-cert{background:#e8f5e9;color:#2e7d32;border-color:#a5d6a7}
.act-btn-cert:hover{background:#2e7d32;color:#fff;border-color:#2e7d32}
/* Modal footer */
.vol-modal-footer{display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:14px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;margin:16px -22px -22px;border-radius:0 0 16px 16px}
.vol-btn-cancel{padding:9px 20px;background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.88rem;font-weight:600;cursor:pointer;transition:.2s}
.vol-btn-cancel:hover{background:#e2e8f0}
.vol-btn-save{padding:9px 20px;background:linear-gradient(135deg,#0d3b6e,#1565c0);color:#fff;border:none;border-radius:9px;font-family:inherit;font-size:.88rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:.2s;box-shadow:0 3px 10px rgba(21,101,192,.3)}
.vol-btn-save:hover{transform:translateY(-1px);box-shadow:0 5px 16px rgba(21,101,192,.4)}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-wrap">
<?php include 'topbar.php'; ?>
<main class="content">
<?php if ($msg=flash('success')): ?><div class="flash success"><i class="fas fa-check-circle"></i><?=htmlspecialchars($msg)?></div><?php endif; ?>

<div class="page-header">
  <div><h2>Volunteer Program Management</h2><p>Manage programs, registrations, attendance, and certificates.</p></div>
  <?php if ($tab==='programs'): ?>
  <button class="btn-primary" onclick="document.getElementById('addProgModal').style.display='flex'"><i class="fas fa-plus"></i> Add Program</button>
  <?php endif; ?>
</div>

<div class="tabs">
  <a href="?tab=registrations" class="tab-btn <?=$tab==='registrations'?'active':''?>"><i class="fas fa-user-check"></i> Registrations</a>
  <a href="?tab=programs"      class="tab-btn <?=$tab==='programs'?'active':''?>"><i class="fas fa-list"></i> Programs</a>
  <a href="?tab=attendance"    class="tab-btn <?=$tab==='attendance'?'active':''?>"><i class="fas fa-calendar-check"></i> Attendance</a>
  <a href="?tab=leaderboard"   class="tab-btn <?=$tab==='leaderboard'?'active':''?>"><i class="fas fa-trophy"></i> Leaderboard</a>
</div>

<?php if ($tab === 'registrations'): ?>
<div class="card">
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>#</th><th>Name</th><th>Program</th><th>Age</th><th>Contact</th><th>Hours</th><th>Status</th><th>Certificate</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (empty($registrations)): ?>
        <tr><td colspan="9" class="empty">No registrations yet.</td></tr>
      <?php else: foreach ($registrations as $i => $r):
        [$sc,$sb] = $statusColors[$r['status']] ?? ['#475569','#f1f5f9'];
      ?>
        <tr>
          <td><?=$i+1?></td>
          <td><strong><?=htmlspecialchars($r['first_name'].' '.$r['last_name'])?></strong><br><small style="color:#94a3b8"><?=htmlspecialchars($r['email'])?></small></td>
          <td><span class="badge blue"><?=htmlspecialchars($typeLabels[$r['prog_type']]??$r['prog_type'])?></span><br><small><?=htmlspecialchars($r['prog_name'])?></small></td>
          <td><?=$r['age']?></td>
          <td><?=htmlspecialchars($r['contact_number'])?></td>
          <td><strong><?=$r['total_hours']?></strong> hrs</td>
          <td><span class="badge" style="background:<?=$sb?>;color:<?=$sc?>"><?=ucfirst($r['status'])?></span></td>
          <td><?=$r['certificate_issued']?'<span class="badge green">Issued</span>':'<span class="badge gray">No</span>'?></td>
          <td>
            <div class="action-group">
              <button class="act-btn act-btn-edit" onclick="openUpdateModal(<?=$r['id']?>,<?=htmlspecialchars(json_encode($r),ENT_QUOTES)?>)">
                <i class="fas fa-edit"></i> Update
              </button>
              <?php if ($r['status']==='approved' && !$r['certificate_issued']): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="issue_certificate"/>
                <input type="hidden" name="reg_id" value="<?=$r['id']?>"/>
                <button type="submit" class="act-btn act-btn-cert">
                  <i class="fas fa-certificate"></i> Issue Cert
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif ($tab === 'programs'): ?>
<div class="card">
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>#</th><th>Program</th><th>Type</th><th>Age Range</th><th>Slots</th><th>Registered</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($programs as $i => $p): ?>
        <tr>
          <td><?=$i+1?></td>
          <td><strong><?=htmlspecialchars($p['name'])?></strong><br><small style="color:#94a3b8"><?=htmlspecialchars($p['location']?:'')?></small></td>
          <td><span class="badge blue"><?=htmlspecialchars($typeLabels[$p['type']]??$p['type'])?></span></td>
          <td><?=$p['min_age']?>–<?=$p['max_age']?> yrs</td>
          <td><?=$p['slots']?></td>
          <td><strong><?=$p['reg_count']?></strong>/<?=$p['slots']?></td>
          <td><span class="badge <?=$p['is_active']?'green':'gray'?>"><?=$p['is_active']?'Active':'Inactive'?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Volunteer Event under Programs -->
<div class="card" style="margin-top:16px">
  <div class="card-header"><h3><i class="fas fa-calendar-plus"></i> Add Volunteer Event</h3></div>
  <form method="POST" style="padding:18px;display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:12px;align-items:end">
    <input type="hidden" name="action" value="add_event"/>
    <div class="fg" style="margin:0"><label style="font-size:.78rem;font-weight:600">Event Title</label><input type="text" name="title" required placeholder="e.g. Linggo ng Kabataan Day 1"/></div>
    <div class="fg" style="margin:0"><label style="font-size:.78rem;font-weight:600">Program</label>
      <select name="program_id" required>
        <option value="">Select...</option>
        <?php foreach ($programs as $p): ?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['name'])?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="fg" style="margin:0"><label style="font-size:.78rem;font-weight:600">Date</label><input type="date" name="event_date" required value="<?=date('Y-m-d')?>"/></div>
    <div class="fg" style="margin:0"><label style="font-size:.78rem;font-weight:600">Hours</label><input type="number" name="hours" required value="1" min="0.5" max="24" step="0.5"/></div>
    <button type="submit" class="btn-primary" style="white-space:nowrap"><i class="fas fa-plus"></i> Add</button>
  </form>
</div>

<!-- Events list -->
<?php if (!empty($events)): ?>
<div class="card" style="margin-top:16px">
  <div class="card-header"><h3><i class="fas fa-list-alt"></i> Volunteer Events</h3></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>#</th><th>Event</th><th>Program</th><th>Date</th><th>Hours</th></tr></thead>
      <tbody>
      <?php foreach ($events as $i => $ev): ?>
        <tr>
          <td><?=$i+1?></td>
          <td><strong><?=htmlspecialchars($ev['title'])?></strong></td>
          <td><?=htmlspecialchars($ev['prog_name'])?></td>
          <td><?=date('M j, Y',strtotime($ev['event_date']))?></td>
          <td><?=$ev['hours']?> hrs</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php elseif ($tab === 'attendance'): ?>

<!-- Pending confirmations -->
<?php if (!empty($pendingAtt)): ?>
<div class="card" style="border:2px solid #f57f17;margin-bottom:16px">
  <div class="card-header" style="background:linear-gradient(135deg,#e65100,#f57f17)">
    <h3><i class="fas fa-clock"></i> Pending Attendance Confirmations (<?=count($pendingAtt)?>)</h3>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>#</th><th>Volunteer</th><th>Program</th><th>Event</th><th>Date</th><th>Hours</th><th>Submitted By</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($pendingAtt as $i => $a):
        $aColors = ['present'=>['#2e7d32','#e8f5e9'],'absent'=>['#c62828','#ffebee'],'excused'=>['#f57f17','#fff8e1']];
        [$ac,$ab] = $aColors[$a['status']] ?? ['#475569','#f1f5f9'];
      ?>
        <tr>
          <td><?=$i+1?></td>
          <td><strong><?=htmlspecialchars($a['first_name'].' '.$a['last_name'])?></strong></td>
          <td><?=htmlspecialchars($a['prog_name'])?></td>
          <td><?=htmlspecialchars($a['event_name'])?></td>
          <td><?=date('M j, Y',strtotime($a['event_date']))?></td>
          <td><strong><?=$a['hours']?></strong> hrs</td>
          <td><span style="background:#e3f2fd;color:#1565c0;padding:2px 8px;border-radius:50px;font-size:.72rem;font-weight:700"><?=ucfirst($a['submitted_by']??'self')?></span></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="confirm_attendance"/>
              <input type="hidden" name="att_id" value="<?=$a['id']?>"/>
              <button type="submit" class="btn-icon green" title="Confirm"><i class="fas fa-check"></i></button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Reject this attendance?')">
              <input type="hidden" name="action" value="reject_attendance"/>
              <input type="hidden" name="att_id" value="<?=$a['id']?>"/>
              <button type="submit" class="btn-icon red" title="Reject"><i class="fas fa-times"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Add Event -->
<div class="card">
  <div class="card-header"><h3><i class="fas fa-calendar-check"></i> All Attendance Records</h3></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>#</th><th>Volunteer</th><th>Program</th><th>Event</th><th>Date</th><th>Hours</th><th>Status</th><th>Verified</th></tr></thead>
      <tbody>
      <?php if (empty($attendance)): ?>
        <tr><td colspan="8" class="empty">No attendance records yet.</td></tr>
      <?php else: foreach ($attendance as $i => $a):
        $aColors = ['present'=>['#2e7d32','#e8f5e9'],'absent'=>['#c62828','#ffebee'],'excused'=>['#f57f17','#fff8e1']];
        [$ac,$ab] = $aColors[$a['status']] ?? ['#475569','#f1f5f9'];
      ?>
        <tr>
          <td><?=$i+1?></td>
          <td><?=htmlspecialchars($a['first_name'].' '.$a['last_name'])?></td>
          <td><?=htmlspecialchars($a['prog_name'])?></td>
          <td><?=htmlspecialchars($a['event_name'])?></td>
          <td><?=date('M j, Y',strtotime($a['event_date']))?></td>
          <td><strong><?=$a['hours']?></strong> hrs</td>
          <td><span class="badge" style="background:<?=$ab?>;color:<?=$ac?>"><?=ucfirst($a['status'])?></span></td>
          <td><?=$a['confirmed']?'<span class="badge green">✓ Confirmed</span>':'<span class="badge" style="background:#fff8e1;color:#f57f17">Pending</span>'?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif ($tab === 'leaderboard'): ?>
<div class="card">
  <div class="card-header"><h3><i class="fas fa-trophy"></i> Volunteer Leaderboard</h3></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Rank</th><th>Volunteer</th><th>Barangay</th><th>Programs</th><th>Total Hours</th></tr></thead>
      <tbody>
      <?php if (empty($leaderboard)): ?>
        <tr><td colspan="5" class="empty">No data yet.</td></tr>
      <?php else: foreach ($leaderboard as $i => $v): $rank=$i+1; ?>
        <tr>
          <td><?=$rank<=3?['','',''][$rank-1]:$rank?></td>
          <td><strong><?=htmlspecialchars($v['first_name'].' '.$v['last_name'])?></strong></td>
          <td><?=htmlspecialchars($v['barangay']?:'')?></td>
          <td><?=$v['programs']?></td>
          <td><strong><?=$v['total_hours']?></strong> hrs</td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

</main>
</div>

<!-- UPDATE REGISTRATION MODAL -->
<div id="updateModal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="modal-head"><h3><i class="fas fa-edit"></i> Update Registration</h3><button onclick="document.getElementById('updateModal').style.display='none'" class="modal-close"><i class="fas fa-times"></i></button></div>
    <form method="POST" class="modal-body">
      <input type="hidden" name="action" value="update_status"/>
      <input type="hidden" name="reg_id" id="upd_reg_id"/>
      <div class="fg"><label>Status</label>
        <select name="status" id="upd_status">
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="completed">Completed</option>
        </select>
      </div>
      <div class="fg"><label>Orientation Date</label><input type="date" name="orientation_date" id="upd_ori"/></div>
      <div class="fg"><label>Notes</label><textarea name="notes" id="upd_notes" rows="2"></textarea></div>
      <div class="vol-modal-footer">
        <button type="button" class="vol-btn-cancel" onclick="document.getElementById('updateModal').style.display='none'">Cancel</button>
        <button type="submit" class="vol-btn-save"><i class="fas fa-save"></i> Update</button>
      </div>
    </form>
  </div>
</div>

<!-- ADD PROGRAM MODAL -->
<div id="addProgModal" class="modal-overlay" style="display:none">
  <div class="modal-box" style="max-width:580px">
    <div class="modal-head"><h3><i class="fas fa-plus"></i> Add Program</h3><button onclick="document.getElementById('addProgModal').style.display='none'" class="modal-close"><i class="fas fa-times"></i></button></div>
    <form method="POST" class="modal-body">
      <input type="hidden" name="action" value="add_program"/>
      <div class="form-row-2">
        <div class="fg"><label>Program Name <span class="req">*</span></label><input type="text" name="name" required/></div>
        <div class="fg"><label>Type <span class="req">*</span></label>
          <select name="type" required>
            <option value="youth_volunteer">Youth Volunteer Program</option>
            <option value="linggo_kabataan">Linggo ng Kabataan</option>
            <option value="junior_officials">Junior Officials Program</option>
          </select>
        </div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Min Age</label><input type="number" name="min_age" value="15" min="1" max="100"/></div>
        <div class="fg"><label>Max Age</label><input type="number" name="max_age" value="30" min="1" max="100"/></div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Slots</label><input type="number" name="slots" value="50" min="1"/></div>
        <div class="fg"><label>Location</label><input type="text" name="location" placeholder="e.g. Municipal Hall"/></div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Start Date</label><input type="date" name="start_date"/></div>
        <div class="fg"><label>End Date</label><input type="date" name="end_date"/></div>
      </div>
      <div class="fg"><label>Description</label><textarea name="description" rows="2"></textarea></div>
      <div class="vol-modal-footer">
        <button type="button" class="vol-btn-cancel" onclick="document.getElementById('addProgModal').style.display='none'">Cancel</button>
        <button type="submit" class="vol-btn-save"><i class="fas fa-save"></i> Add Program</button>
      </div>
    </form>
  </div>
</div>

<!-- ATTENDANCE MODAL -->
<div id="attModal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="modal-head"><h3><i class="fas fa-calendar-check"></i> Record Attendance</h3><button onclick="document.getElementById('attModal').style.display='none'" class="modal-close"><i class="fas fa-times"></i></button></div>
    <form method="POST" class="modal-body">
      <input type="hidden" name="action" value="record_attendance"/>
      <div class="fg"><label>Volunteer <span class="req">*</span></label>
        <select name="reg_id" required>
          <option value="">Select volunteer...</option>
          <?php foreach ($registrations as $r): if ($r['status']==='approved'): ?>
          <option value="<?=$r['id']?>"><?=htmlspecialchars($r['first_name'].' '.$r['last_name'])?>  <?=htmlspecialchars($r['prog_name'])?></option>
          <?php endif; endforeach; ?>
        </select>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Event Name <span class="req">*</span></label>
          <select name="event_name" required>
            <option value="">Select event</option>
            <?php foreach ($events as $ev): ?>
            <option value="<?=htmlspecialchars($ev['title'])?>"><?=htmlspecialchars($ev['title'])?> — <?=date('M j, Y',strtotime($ev['event_date']))?> (<?=htmlspecialchars($ev['prog_name'])?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label>Event Date</label><input type="date" name="event_date" value="<?=date('Y-m-d')?>"/></div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Hours</label><input type="number" name="hours" value="2" min="0" step="0.5"/></div>
        <div class="fg"><label>Status</label>
          <select name="att_status">
            <option value="present">Present</option>
            <option value="absent">Absent</option>
            <option value="excused">Excused</option>
          </select>
        </div>
      </div>
      <div class="vol-modal-footer">
        <button type="button" class="vol-btn-cancel" onclick="document.getElementById('attModal').style.display='none'">Cancel</button>
        <button type="submit" class="vol-btn-save"><i class="fas fa-save"></i> Record</button>
      </div>
      </div>
    </form>
  </div>
</div>

<script>
function openUpdateModal(id, r) {
  document.getElementById('upd_reg_id').value = id;
  document.getElementById('upd_status').value = r.status;
  document.getElementById('upd_ori').value    = r.orientation_date || '';
  document.getElementById('upd_notes').value  = r.notes || '';
  document.getElementById('updateModal').style.display = 'flex';
}
</script>
</body></html>
