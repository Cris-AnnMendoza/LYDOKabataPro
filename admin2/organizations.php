<?php
require_once 'config.php';
requireLogin();

$pdo = db();
$admin = currentAdmin();

// ── Handle POST actions ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'name'               => trim($_POST['name'] ?? ''),
            'category'           => trim($_POST['category'] ?? ''),
            'description'        => trim($_POST['description'] ?? ''),
            'island'             => trim($_POST['island'] ?? 'Luzon'),
            'region'             => trim($_POST['region'] ?? 'Region IVA - CALABARZON'),
            'province'           => trim($_POST['province'] ?? 'Laguna'),
            'municipality'       => trim($_POST['municipality'] ?? 'Santa Cruz'),
            'barangay'           => trim($_POST['barangay'] ?? ''),
            'mobile_number'      => trim($_POST['mobile_number'] ?? ''),
            'org_email'          => trim($_POST['org_email'] ?? ''),
            'no_of_members'      => (int)($_POST['no_of_members'] ?? 0),
            'established_date'   => $_POST['established_date'] ?: null,
            'registration_date'  => $_POST['registration_date'] ?: null,
            'date_approved'      => $_POST['date_approved'] ?: null,
            'suggested_trainings'=> trim($_POST['suggested_trainings'] ?? ''),
            'is_active'          => isset($_POST['is_active']) ? 1 : 0,
        ];
        if (!$data['name']) { flash('error','Organization name is required.'); header('Location: organizations.php'); exit; }

        if ($action === 'add') {
            $cols = implode(',', array_keys($data));
            $phs  = implode(',', array_fill(0, count($data), '?'));
            $pdo->prepare("INSERT INTO organizations ($cols) VALUES ($phs)")->execute(array_values($data));
            flash('success','Organization added successfully.');
        } else {
            $sets = implode(', ', array_map(fn($k) => "$k=?", array_keys($data)));
            $vals = array_values($data);
            $vals[] = $id;
            $pdo->prepare("UPDATE organizations SET $sets WHERE id=?")->execute($vals);
            flash('success','Organization updated.');
        }
        header('Location: organizations.php'); exit;
    }

    if ($action === 'toggle') {
        $id  = (int)$_POST['id'];
        $cur = $pdo->prepare('SELECT is_active FROM organizations WHERE id=?');
        $cur->execute([$id]);
        $row = $cur->fetch();
        if ($row) {
            $pdo->prepare('UPDATE organizations SET is_active=? WHERE id=?')->execute([$row['is_active']?0:1, $id]);
            flash('success','Organization status updated.');
        }
        header('Location: organizations.php'); exit;
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM organizations WHERE id=?')->execute([(int)$_POST['id']]);
        flash('success','Organization deleted.');
        header('Location: organizations.php'); exit;
    }

    if ($action === 'approve_join') {
        $reqId = (int)$_POST['req_id'];
        $req = $pdo->prepare('SELECT * FROM org_join_requests WHERE id=?');
        $req->execute([$reqId]);
        $r = $req->fetch();
        if ($r) {
            $pdo->prepare('UPDATE org_join_requests SET status="approved",reviewed_by=?,reviewed_at=NOW() WHERE id=?')
                ->execute([$admin['id'], $reqId]);
            $pdo->prepare('INSERT INTO organization_members (organization_id,user_id,role,position,is_active,joined_at)
                VALUES (?,?,"Member","Member",1,CURDATE())
                ON DUPLICATE KEY UPDATE is_active=1,role="Member"')
                ->execute([$r['organization_id'], $r['user_id']]);
            $pdo->prepare('INSERT INTO notifications (user_id,type,title,category,message,is_read) VALUES (?,?,?,?,?,0)')
                ->execute([$r['user_id'],'approval','Join Request Approved','approval',
                    'Your request to join the organization has been approved!']);
            flash('success','Join request approved.');
        }
        header('Location: organizations.php'); exit;
    }

    if ($action === 'reject_join') {
        $reqId = (int)$_POST['req_id'];
        $pdo->prepare('UPDATE org_join_requests SET status="rejected",reviewed_by=?,reviewed_at=NOW() WHERE id=?')
            ->execute([$admin['id'], $reqId]);
        flash('success','Join request rejected.');
        header('Location: organizations.php'); exit;
    }
}

$orgs = $pdo->query('SELECT o.*, (SELECT COUNT(*) FROM organization_members m WHERE m.organization_id=o.id AND m.is_active=1) as member_count FROM organizations o ORDER BY o.name')->fetchAll();
$totalActive   = count(array_filter($orgs, fn($o) => $o['is_active']));
$totalInactive = count($orgs) - $totalActive;

// Pending join requests
$joinReqs = $pdo->query(
    'SELECT r.*, o.name as org_name, u.first_name, u.last_name, u.email, u.barangay
     FROM org_join_requests r
     JOIN organizations o ON o.id=r.organization_id
     JOIN youth_users u ON u.id=r.user_id
     WHERE r.status="pending"
     ORDER BY r.created_at DESC'
)->fetchAll();

$categories = ['Sangguniang Kabataan','Youth NGO','Religious Organization','Sports Club','Academic Organization','Community Group','Cultural Group','Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Organizations – LYDO Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="admin.css"/>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-wrap">
<?php include 'topbar.php'; ?>
<main class="content">

<?php if ($msg=flash('success')): ?><div class="flash success"><i class="fas fa-check-circle"></i><?=htmlspecialchars($msg)?></div><?php endif; ?>
<?php if ($msg=flash('error')):   ?><div class="flash error"><i class="fas fa-exclamation-circle"></i><?=htmlspecialchars($msg)?></div><?php endif; ?>

<div class="page-header">
  <div><h2>Organizations</h2><p>Manage youth organizations and their members.</p></div>
  <button class="btn-primary" onclick="document.getElementById('addModal').style.display='flex'">
    <i class="fas fa-plus"></i> Add Organization
  </button>
</div>

<!-- Summary -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px">
  <div class="stat-card blue"><div class="stat-icon"><i class="fas fa-sitemap"></i></div><div><span class="stat-val"><?=count($orgs)?></span><span class="stat-lbl">Total Organizations</span></div></div>
  <div class="stat-card green"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div><span class="stat-val"><?=$totalActive?></span><span class="stat-lbl">Active</span></div></div>
  <div class="stat-card" style="border:1px solid #e2e8f0"><div class="stat-icon" style="background:#f1f5f9;color:#475569"><i class="fas fa-pause-circle"></i></div><div><span class="stat-val"><?=$totalInactive?></span><span class="stat-lbl">Inactive</span></div></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>#</th><th>Organization</th><th>Category</th><th>Adviser</th><th>Barangay</th><th>Members</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (empty($orgs)): ?>
        <tr><td colspan="8" class="empty">No organizations yet. Add one to get started.</td></tr>
      <?php else: foreach ($orgs as $i => $o): ?>
        <tr>
          <td><?=$i+1?></td>
          <td><strong><?=htmlspecialchars($o['name'])?></strong><?php if($o['description']): ?><br><small style="color:#94a3b8"><?=htmlspecialchars(substr($o['description'],0,60))?>...</small><?php endif; ?></td>
          <td><span class="badge blue"><?=htmlspecialchars($o['category']?:'—')?></span></td>
          <td><?=htmlspecialchars($o['adviser_name']?:'—')?></td>
          <td><?=htmlspecialchars($o['barangay']?:'—')?></td>
          <td><strong><?=$o['member_count']?></strong></td>
          <td><span class="badge <?=$o['is_active']?'green':'gray'?>"><?=$o['is_active']?'Active':'Inactive'?></span></td>
          <td>
            <a href="org_profile.php?id=<?=$o['id']?>" class="btn-icon teal" title="View Profile"><i class="fas fa-eye"></i></a>
            <button class="btn-icon edit" title="Edit" onclick="openEdit(<?=htmlspecialchars(json_encode($o),ENT_QUOTES)?>)"><i class="fas fa-edit"></i></button>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="toggle"/>
              <input type="hidden" name="id" value="<?=$o['id']?>"/>
              <button type="submit" class="btn-icon <?=$o['is_active']?'orange':'green'?>" title="<?=$o['is_active']?'Deactivate':'Activate'?>"><i class="fas fa-<?=$o['is_active']?'ban':'check'?>"></i></button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this organization?')">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="id" value="<?=$o['id']?>"/>
              <button type="submit" class="btn-icon red" title="Delete"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

</main>
</div>

<!-- JOIN REQUESTS SECTION -->
<?php if (!empty($joinReqs)): ?>
<div style="max-width:100%;padding:0 20px 20px">
<div class="card" style="margin-top:0">
  <div class="card-header" style="justify-content:space-between">
    <h3><i class="fas fa-user-plus"></i> Pending Join Requests</h3>
    <span style="background:#e53935;color:#fff;border-radius:50px;padding:2px 10px;font-size:.75rem;font-weight:700"><?=count($joinReqs)?></span>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>#</th><th>Youth User</th><th>Organization</th><th>Message</th><th>Requested</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($joinReqs as $i => $r): ?>
        <tr>
          <td><?=$i+1?></td>
          <td>
            <strong><?=htmlspecialchars($r['first_name'].' '.$r['last_name'])?></strong><br>
            <small style="color:#94a3b8"><?=htmlspecialchars($r['email'])?></small>
          </td>
          <td><?=htmlspecialchars($r['org_name'])?></td>
          <td style="font-size:.82rem;max-width:200px"><?=htmlspecialchars($r['message'] ?: '—')?></td>
          <td style="font-size:.8rem"><?=date('M j, Y', strtotime($r['created_at']))?></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="approve_join"/>
              <input type="hidden" name="req_id" value="<?=$r['id']?>"/>
              <button type="submit" class="btn-icon green" title="Approve"><i class="fas fa-check"></i></button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Reject this request?')">
              <input type="hidden" name="action" value="reject_join"/>
              <input type="hidden" name="req_id" value="<?=$r['id']?>"/>
              <button type="submit" class="btn-icon red" title="Reject"><i class="fas fa-times"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>
<?php endif; ?>

<!-- ADD MODAL -->
<div id="addModal" class="modal-overlay" style="display:none">
  <div class="modal-box" style="max-width:680px;max-height:90vh;overflow-y:auto">
    <div class="modal-head"><h3><i class="fas fa-plus"></i> Add Organization</h3><button onclick="document.getElementById('addModal').style.display='none'" class="modal-close"><i class="fas fa-times"></i></button></div>
    <form method="POST" class="modal-body">
      <input type="hidden" name="action" value="add"/>

      <div style="font-size:.75rem;font-weight:700;color:#1565c0;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #e2e8f0">Organization Info</div>
      <div class="form-row-2">
        <div class="fg"><label>Organization Name <span class="req">*</span></label><input type="text" name="name" required placeholder="e.g. New Gen Volleyball Club"/></div>
        <div class="fg"><label>Category</label>
          <select name="category"><?php foreach($categories as $c): ?><option value="<?=$c?>"><?=$c?></option><?php endforeach; ?></select>
        </div>
      </div>
      <div class="fg"><label>Description</label><textarea name="description" rows="2" placeholder="Brief description..."></textarea></div>

      <div style="font-size:.75rem;font-weight:700;color:#1565c0;text-transform:uppercase;letter-spacing:.05em;margin:14px 0 8px;padding-bottom:6px;border-bottom:1px solid #e2e8f0">Location</div>
      <div class="form-row-2">
        <div class="fg"><label>Island</label><input type="text" name="island" value="Luzon"/></div>
        <div class="fg"><label>Region</label><input type="text" name="region" value="Region IVA - CALABARZON"/></div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Province</label><input type="text" name="province" value="Laguna"/></div>
        <div class="fg"><label>Municipality</label><input type="text" name="municipality" value="Santa Cruz"/></div>
      </div>
      <div class="fg"><label>Barangay</label><input type="text" name="barangay" placeholder="e.g. Patimbao"/></div>

      <div style="font-size:.75rem;font-weight:700;color:#1565c0;text-transform:uppercase;letter-spacing:.05em;margin:14px 0 8px;padding-bottom:6px;border-bottom:1px solid #e2e8f0">Contact & Details</div>
      <div class="form-row-2">
        <div class="fg"><label>Mobile / Telephone</label><input type="text" name="mobile_number" placeholder="09XX-XXX-XXXX"/></div>
        <div class="fg"><label>Organization Email</label><input type="email" name="org_email" placeholder="org@email.com"/></div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>No. of Members</label><input type="number" name="no_of_members" value="0" min="0"/></div>
        <div class="fg"><label>Established Date</label><input type="date" name="established_date"/></div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Registration Date</label><input type="datetime-local" name="registration_date"/></div>
        <div class="fg"><label>Date Approved</label><input type="datetime-local" name="date_approved"/></div>
      </div>
      <div class="fg"><label>Suggested Trainings</label><input type="text" name="suggested_trainings" placeholder="e.g. Health, Education, Environment, Active Citizenship"/></div>

      <label class="chk-label" style="margin:12px 0 16px"><input type="checkbox" name="is_active" checked/><span class="chk"></span> Active</label>
      <div class="modal-footer"><button type="button" class="btn-cancel" onclick="document.getElementById('addModal').style.display='none'">Cancel</button><button type="submit" class="btn-save"><i class="fas fa-save"></i> Save</button></div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal-overlay" style="display:none">
  <div class="modal-box" style="max-width:680px;max-height:90vh;overflow-y:auto">
    <div class="modal-head"><h3><i class="fas fa-edit"></i> Edit Organization</h3><button onclick="document.getElementById('editModal').style.display='none'" class="modal-close"><i class="fas fa-times"></i></button></div>
    <form method="POST" class="modal-body" id="editForm">
      <input type="hidden" name="action" value="edit"/>
      <input type="hidden" name="id" id="edit_id"/>

      <div style="font-size:.75rem;font-weight:700;color:#1565c0;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #e2e8f0">Organization Info</div>
      <div class="form-row-2">
        <div class="fg"><label>Organization Name <span class="req">*</span></label><input type="text" name="name" id="edit_name" required/></div>
        <div class="fg"><label>Category</label>
          <select name="category" id="edit_category"><?php foreach($categories as $c): ?><option value="<?=$c?>"><?=$c?></option><?php endforeach; ?></select>
        </div>
      </div>
      <div class="fg"><label>Description</label><textarea name="description" id="edit_description" rows="2"></textarea></div>

      <div style="font-size:.75rem;font-weight:700;color:#1565c0;text-transform:uppercase;letter-spacing:.05em;margin:14px 0 8px;padding-bottom:6px;border-bottom:1px solid #e2e8f0">Location</div>
      <div class="form-row-2">
        <div class="fg"><label>Island</label><input type="text" name="island" id="edit_island"/></div>
        <div class="fg"><label>Region</label><input type="text" name="region" id="edit_region"/></div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Province</label><input type="text" name="province" id="edit_province"/></div>
        <div class="fg"><label>Municipality</label><input type="text" name="municipality" id="edit_municipality"/></div>
      </div>
      <div class="fg"><label>Barangay</label><input type="text" name="barangay" id="edit_barangay"/></div>

      <div style="font-size:.75rem;font-weight:700;color:#1565c0;text-transform:uppercase;letter-spacing:.05em;margin:14px 0 8px;padding-bottom:6px;border-bottom:1px solid #e2e8f0">Contact & Details</div>
      <div class="form-row-2">
        <div class="fg"><label>Mobile / Telephone</label><input type="text" name="mobile_number" id="edit_mobile_number"/></div>
        <div class="fg"><label>Organization Email</label><input type="email" name="org_email" id="edit_org_email"/></div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>No. of Members</label><input type="number" name="no_of_members" id="edit_no_of_members" min="0"/></div>
        <div class="fg"><label>Established Date</label><input type="date" name="established_date" id="edit_established_date"/></div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Registration Date</label><input type="datetime-local" name="registration_date" id="edit_registration_date"/></div>
        <div class="fg"><label>Date Approved</label><input type="datetime-local" name="date_approved" id="edit_date_approved"/></div>
      </div>
      <div class="fg"><label>Suggested Trainings</label><input type="text" name="suggested_trainings" id="edit_suggested_trainings"/></div>

      <label class="chk-label" style="margin:12px 0 16px"><input type="checkbox" name="is_active" id="edit_is_active"/><span class="chk"></span> Active</label>
      <div class="modal-footer"><button type="button" class="btn-cancel" onclick="document.getElementById('editModal').style.display='none'">Cancel</button><button type="submit" class="btn-save"><i class="fas fa-save"></i> Update</button></div>
    </form>
  </div>
</div>

<script>
function openEdit(o) {
  document.getElementById('edit_id').value                = o.id;
  document.getElementById('edit_name').value              = o.name;
  document.getElementById('edit_category').value          = o.category || '';
  document.getElementById('edit_island').value            = o.island || 'Luzon';
  document.getElementById('edit_region').value            = o.region || 'Region IVA - CALABARZON';
  document.getElementById('edit_province').value          = o.province || 'Laguna';
  document.getElementById('edit_municipality').value      = o.municipality || 'Santa Cruz';
  document.getElementById('edit_barangay').value          = o.barangay || '';
  document.getElementById('edit_mobile_number').value     = o.mobile_number || '';
  document.getElementById('edit_org_email').value         = o.org_email || '';
  document.getElementById('edit_no_of_members').value     = o.no_of_members || 0;
  document.getElementById('edit_established_date').value  = o.established_date || '';
  document.getElementById('edit_registration_date').value = o.registration_date ? o.registration_date.replace(' ','T').slice(0,16) : '';
  document.getElementById('edit_date_approved').value     = o.date_approved ? o.date_approved.replace(' ','T').slice(0,16) : '';
  document.getElementById('edit_suggested_trainings').value = o.suggested_trainings || '';
  document.getElementById('edit_description').value       = o.description || '';
  document.getElementById('edit_is_active').checked       = o.is_active == 1;
  document.getElementById('editModal').style.display      = 'flex';
}
</script>
</body>
</html>
