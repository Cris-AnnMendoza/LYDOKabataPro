<?php
require_once 'config.php';
requireLogin();
require_once __DIR__ . '/../shared/blockchain.php';

$pdo   = db();
$admin = currentAdmin();

// Verify chain integrity
$verification = blockchain_verify($pdo);

// File integrity check
$fileResults = [];
if (isset($_GET['verify_files'])) {
    $uploadBase = __DIR__ . '/../uploads/';
    $docTables = [
        'accreditation' => ['table'=>'accreditation_documents','path'=>'accreditation/'],
        'assistance'    => ['table'=>'assistance_documents',   'path'=>'assistance/'],
        'scholarship'   => ['table'=>'scholarship_documents',  'path'=>'scholarship/'],
    ];
    foreach ($docTables as $type => $cfg) {
        $docs = $pdo->query("SELECT id, filename, original_name, file_hash FROM {$cfg['table']} WHERE file_hash IS NOT NULL")->fetchAll();
        foreach ($docs as $doc) {
            $path = $uploadBase . $cfg['path'] . $doc['filename'];
            $result = blockchain_verify_file($path, $doc['file_hash']);
            $fileResults[] = [
                'type'     => $type,
                'filename' => $doc['original_name'],
                'valid'    => $result['valid'],
                'reason'   => $result['reason'],
            ];
        }
    }
}

// Recent chain records
$records = $pdo->query(
    'SELECT sc.*, u.first_name, u.last_name
     FROM submission_chain sc
     JOIN youth_users u ON u.id=sc.user_id
     ORDER BY sc.id DESC LIMIT 50'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Submission Chain – LYDO Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="admin.css"/>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-wrap">
<?php include 'topbar.php'; ?>
<main class="content">

<div class="page-header">
  <div>
    <h2><i class="fas fa-link" style="color:#1565c0;margin-right:8px"></i>Submission Hash Chain</h2>
    <p>Tamper-evident audit trail for all youth submissions. Each record is cryptographically linked.</p>
  </div>
</div>

<!-- Chain Status -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px">
  <div class="stat-card <?=$verification['valid']?'green':'red'?>">
    <div class="stat-icon"><i class="fas fa-<?=$verification['valid']?'shield-alt':'exclamation-triangle'?>"></i></div>
    <div>
      <span class="stat-val"><?=$verification['valid']?'VALID':'BROKEN'?></span>
      <span class="stat-lbl">Chain Integrity</span>
    </div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon"><i class="fas fa-link"></i></div>
    <div><span class="stat-val"><?=$verification['total']?></span><span class="stat-lbl">Total Records</span></div>
  </div>
  <div class="stat-card <?=empty($verification['broken_ids'])?'green':'red'?>">
    <div class="stat-icon"><i class="fas fa-<?=empty($verification['broken_ids'])?'check-circle':'times-circle'?>"></i></div>
    <div><span class="stat-val"><?=count($verification['broken_ids'])?></span><span class="stat-lbl">Broken Links</span></div>
  </div>
</div>

<?php if (!$verification['valid']): ?>
<div class="flash error"><i class="fas fa-exclamation-triangle"></i> Chain integrity compromised! Records <?=implode(', #', $verification['broken_ids'])?> have been tampered with.</div>
<?php else: ?>
<div class="flash success"><i class="fas fa-check-circle"></i> All <?=$verification['total']?> records verified. Chain is intact and untampered.</div>
<?php endif; ?>

<!-- File Integrity Section -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header" style="justify-content:space-between">
    <h3><i class="fas fa-file-shield"></i> Document File Integrity</h3>
    <a href="?verify_files=1" class="btn-primary" style="padding:7px 16px;font-size:.85rem"><i class="fas fa-search"></i> Verify All Files</a>
  </div>
  <?php if (!empty($fileResults)): ?>
  <?php
    $totalFiles  = count($fileResults);
    $validFiles  = count(array_filter($fileResults, fn($r) => $r['valid']));
    $invalidFiles = $totalFiles - $validFiles;
  ?>
  <div style="padding:14px 18px;border-bottom:1px solid #e2e8f0;display:flex;gap:20px">
    <span style="color:#2e7d32;font-weight:700"><i class="fas fa-check-circle"></i> <?=$validFiles?> intact</span>
    <?php if ($invalidFiles > 0): ?>
    <span style="color:#c62828;font-weight:700"><i class="fas fa-exclamation-triangle"></i> <?=$invalidFiles?> tampered/missing</span>
    <?php endif; ?>
    <span style="color:#475569">of <?=$totalFiles?> hashed files</span>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Type</th><th>File</th><th>Status</th><th>Details</th></tr></thead>
      <tbody>
      <?php foreach ($fileResults as $r): ?>
        <tr style="<?=!$r['valid']?'background:#fff5f5':''?>">
          <td><span class="badge blue"><?=ucfirst($r['type'])?></span></td>
          <td><?=htmlspecialchars($r['filename'])?></td>
          <td>
            <?php if ($r['valid']): ?>
              <span class="badge green"><i class="fas fa-check"></i> Intact</span>
            <?php else: ?>
              <span class="badge red"><i class="fas fa-times"></i> Failed</span>
            <?php endif; ?>
          </td>
          <td style="font-size:.82rem;color:#475569"><?=htmlspecialchars($r['reason'])?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div style="padding:24px;text-align:center;color:#94a3b8;font-size:.88rem">
    Click "Verify All Files" to check the integrity of all uploaded documents against their stored hashes.
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header"><h3><i class="fas fa-history"></i> Recent Chain Records</h3></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr><th>#</th><th>Type</th><th>Record ID</th><th>Youth User</th><th>Action</th><th>Chain Hash</th><th>Timestamp</th></tr>
      </thead>
      <tbody>
      <?php if (empty($records)): ?>
        <tr><td colspan="7" class="empty">No chain records yet. Records are created when youth submit applications.</td></tr>
      <?php else: foreach ($records as $r):
        $isBroken = in_array($r['id'], $verification['broken_ids']);
      ?>
        <tr style="<?=$isBroken?'background:#ffebee':''?>">
          <td><?=$r['id']?> <?php if($isBroken): ?><i class="fas fa-exclamation-triangle" style="color:#c62828"></i><?php endif; ?></td>
          <td><span class="badge blue"><?=ucfirst($r['record_type'])?></span></td>
          <td>#<?=$r['record_id']?></td>
          <td><?=htmlspecialchars($r['first_name'].' '.$r['last_name'])?></td>
          <td><?=htmlspecialchars($r['action'])?></td>
          <td style="font-family:monospace;font-size:.72rem;color:#475569"><?=substr($r['chain_hash'],0,16)?>...</td>
          <td style="font-size:.8rem"><?=date('M j, Y g:i A',strtotime($r['created_at']))?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

</main>
</div>
</body>
</html>
