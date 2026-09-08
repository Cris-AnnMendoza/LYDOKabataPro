<?php
require_once __DIR__ . '/../shared/config.php';
if (empty($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$pdo    = db();
$userId = (int)$_SESSION['user_id'];

$uStmt = $pdo->prepare('SELECT * FROM youth_users WHERE id=? LIMIT 1');
$uStmt->execute([$userId]);
$user = $uStmt->fetch();

$nc = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
$nc->execute([$userId]);
$notifCount = (int)$nc->fetchColumn();

$uploadDir = __DIR__ . '/../uploads/accreditation/';
$tempDir   = __DIR__ . '/../uploads/accreditation_temp/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
if (!is_dir($tempDir))   mkdir($tempDir,   0755, true);

$checkResult  = null;
$checkPanel   = '';
$formData     = [];

// Required docs for accreditation
$requiredDocs = [
    'letter_of_intent' => 'Letter of Intent',
    'nyc_form'         => 'NYC Accreditation Form',
    'officers_list'    => 'Officers and Members List',
    'constitution'     => 'Constitution and By-Laws',
    'lydo_form'        => 'LYDO Accreditation Form',
];

// Field rules
$fieldRules = [
    'organization_name' => ['label' => 'Organization Name', 'required' => true, 'min_len' => 3],
    'contact_person'    => ['label' => 'Contact Person',    'required' => true, 'min_len' => 3],
    'contact_email'     => ['label' => 'Contact Email',     'required' => true, 'type' => 'email'],
    'contact_phone'     => ['label' => 'Contact Phone',     'required' => false],
    'barangay'          => ['label' => 'Barangay',          'required' => false],
];

// Temp file helpers
function saveAccredTempFiles(array $files, string $tempDir, int $userId): array {
    $saved = [];
    foreach ($files as $type => $file) {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) continue;
        $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fname = 'tmp_accred_' . $userId . '_' . $type . '_' . time() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $tempDir . $fname)) {
            $saved[$type] = ['tmp_path' => $tempDir . $fname, 'name' => $file['name'], 'size' => $file['size'], 'error' => UPLOAD_ERR_OK];
        }
    }
    return $saved;
}

function buildAccredFakeFiles(array $sessionFiles): array {
    $fake = [];
    foreach ($sessionFiles as $type => $info) {
        if (file_exists($info['tmp_path'] ?? '')) {
            $fake[$type] = ['tmp_name' => $info['tmp_path'], 'name' => $info['name'], 'size' => $info['size'], 'error' => UPLOAD_ERR_OK];
        }
    }
    return $fake;
}

// ── Handle submission ─────────────────────────────────────
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;

    if (isset($_POST['check_only'])) {
        // Save temp files
        $newTemp = saveAccredTempFiles($_FILES, $tempDir, $userId);
        $sessionFiles = $_SESSION['accred_temp_' . $userId] ?? [];
        foreach ($newTemp as $type => $info) {
            if (isset($sessionFiles[$type]) && file_exists($sessionFiles[$type]['tmp_path'] ?? '')) @unlink($sessionFiles[$type]['tmp_path']);
            $sessionFiles[$type] = $info;
        }
        $_SESSION['accred_temp_' . $userId] = $sessionFiles;

        $fakeFiles   = buildAccredFakeFiles($sessionFiles);
        $fieldResult = checkFields($_POST, $fieldRules);
        $fileResult  = checkFiles($fakeFiles, $requiredDocs, $sessionFiles);
        $scoreResult = calcScore($fieldResult, $fileResult, count($fieldRules), count($requiredDocs));
        $checkPanel  = renderCheckPanel($fieldResult, $fileResult, $scoreResult, $requiredDocs);
    } else {
    $orgName  = trim($_POST['organization_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $contact  = trim($_POST['contact_person'] ?? '');
    $email    = trim($_POST['contact_email'] ?? '');
    $phone    = trim($_POST['contact_phone'] ?? '');
    $desc     = trim($_POST['description'] ?? '');

    if (!$orgName || !$contact || !$email) {
        $error = 'Organization name, contact person, and email are required.';
    } else {
        $existing = $pdo->prepare('SELECT id FROM accreditation_applications WHERE submitted_by=? AND status NOT IN ("rejected") LIMIT 1');
        $existing->execute([$userId]);
        if ($existing->fetch()) {
            $error = 'You already have an active accreditation application.';
        } else {
            // Use session temp files + new uploads
            $sessionFiles = $_SESSION['accred_temp_' . $userId] ?? [];
            $newTemp = saveAccredTempFiles($_FILES, $tempDir, $userId);
            foreach ($newTemp as $type => $info) {
                if (isset($sessionFiles[$type]) && file_exists($sessionFiles[$type]['tmp_path'] ?? '')) @unlink($sessionFiles[$type]['tmp_path']);
                $sessionFiles[$type] = $info;
            }

            $pdo->prepare(
                'INSERT INTO accreditation_applications
                 (organization_name,category,barangay,contact_person,contact_email,contact_phone,description,status,submitted_by)
                 VALUES (?,?,?,?,?,?,?,"submitted",?)'
            )->execute([$orgName,$category,$barangay,$contact,$email,$phone,$desc,$userId]);

            $appId = (int)$pdo->lastInsertId();

            // Record on hash chain
            require_once __DIR__ . '/../shared/blockchain.php';
            blockchain_record($pdo, 'accreditation', $appId, $userId, 'submitted', [
                'org_name' => $orgName, 'category' => $category, 'barangay' => $barangay
            ]);

            $allowed = ['pdf','doc','docx','jpg','jpeg','png'];

            // Move temp files to permanent
            foreach ($sessionFiles as $type => $info) {
                if (!file_exists($info['tmp_path'] ?? '')) continue;
                $ext   = strtolower(pathinfo($info['name'], PATHINFO_EXTENSION));
                $fname = uniqid($type . '_', true) . '.' . $ext;
                rename($info['tmp_path'], $uploadDir . $fname);
                $fileHash = blockchain_hash_file($pdo, $uploadDir . $fname, $type, 'accreditation', $appId, $userId);
                $pdo->prepare('INSERT INTO accreditation_documents (application_id,doc_type,filename,original_name,file_size,file_hash) VALUES (?,?,?,?,?,?)')
                    ->execute([$appId, $type, $fname, $info['name'], $info['size'], $fileHash]);
            }
            unset($_SESSION['accred_temp_' . $userId]);
                move_uploaded_file($_FILES[$type]['tmp_name'], $uploadDir . $fname);

                $pdo->prepare(
                    'INSERT INTO accreditation_documents (application_id,doc_type,filename,original_name,file_size) VALUES (?,?,?,?,?)'
                )->execute([$appId, $type, $fname, $_FILES[$type]['name'], $_FILES[$type]['size']]);
            }

            // Initialize workflow steps
            $steps = ['Submission of Requirements','Document Verification','Evaluation Process','Approval / Rejection','Certificate Generation','Accreditation Release'];
            foreach ($steps as $step) {
                $pdo->prepare('INSERT INTO accreditation_workflow (application_id,step,status) VALUES (?,?,"pending")')
                    ->execute([$appId, $step]);
            }
            // Mark first step done
            $pdo->prepare('UPDATE accreditation_workflow SET status="completed",done_at=NOW() WHERE application_id=? AND step=?')
                ->execute([$appId, 'Submission of Requirements']);

            $success = 'Application submitted successfully! Your application ID is #' . $appId . '. You can track the status below.';
        }
    }
}

// ── Load user's applications ──────────────────────────────
$apps = $pdo->prepare(
    'SELECT a.*,
     (SELECT COUNT(*) FROM accreditation_documents d WHERE d.application_id=a.id) as doc_count,
     (SELECT COUNT(*) FROM accreditation_documents d WHERE d.application_id=a.id AND d.status="verified") as verified_count
     FROM accreditation_applications a WHERE a.submitted_by=? ORDER BY a.created_at DESC'
);
$apps->execute([$userId]);
$applications = $apps->fetchAll();

$docLabels = [
    'letter_of_intent' => 'Letter of Intent',
    'nyc_form'         => 'NYC Accreditation Form',
    'officers_list'    => 'Officers and Members List',
    'constitution'     => 'Constitution and By-Laws',
    'lydo_form'        => 'LYDO Accreditation Form',
];

$statusColors = [
    'submitted'    => ['#1565c0','#e3f2fd'],
    'under_review' => ['#f57f17','#fff8e1'],
    'approved'     => ['#2e7d32','#e8f5e9'],
    'rejected'     => ['#c62828','#ffebee'],
    'draft'        => ['#475569','#f1f5f9'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Organization Accreditation – LYDO</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--blue:#1565c0;--blue-dark:#0d3b6e;--blue-pale:#e3f2fd;--green:#2e7d32;--green-pale:#e8f5e9;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;--gray-600:#475569;--gray-800:#1e293b}
body{font-family:'Inter',sans-serif;background:var(--gray-50);color:var(--gray-800)}
.topbar{background:linear-gradient(135deg,var(--blue-dark),var(--blue));padding:0 28px;height:62px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.15)}
.t-logo{display:flex;align-items:center;gap:10px;color:#fff;text-decoration:none}
.t-logo-icon{width:36px;height:36px;border-radius:9px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1rem}
.t-logo-text{font-size:.95rem;font-weight:800}
.t-right{display:flex;align-items:center;gap:10px}
.btn-back{padding:7px 14px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:8px;color:#fff;font-family:inherit;font-size:.82rem;font-weight:600;cursor:pointer;text-decoration:none;transition:.2s;display:flex;align-items:center;gap:6px}
.btn-back:hover{background:rgba(255,255,255,.22)}
.content{max-width:900px;margin:0 auto;padding:28px 20px}
.page-title{font-size:1.5rem;font-weight:800;margin-bottom:6px}
.page-sub{font-size:.9rem;color:var(--gray-600);margin-bottom:28px}
.card{background:#fff;border-radius:14px;border:1px solid var(--gray-200);box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;margin-bottom:20px}
.card-header{padding:16px 22px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;gap:8px}
.card-header h3{font-size:.95rem;font-weight:700;color:var(--gray-800)}
.card-header i{color:var(--blue)}
.card-body{padding:22px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:16px}
.fg label{font-size:.83rem;font-weight:600;color:var(--gray-800)}
.fg input,.fg select,.fg textarea{padding:9px 13px;border:1.5px solid var(--gray-200);border-radius:9px;font-family:inherit;font-size:.9rem;color:var(--gray-800);background:var(--gray-50);outline:none;transition:.2s;width:100%}
.fg input:focus,.fg select:focus,.fg textarea:focus{border-color:#1e88e5;box-shadow:0 0 0 3px rgba(30,136,229,.1);background:#fff}
.form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.req{color:#e53935}
.doc-upload-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.doc-item{border:1.5px dashed var(--gray-200);border-radius:10px;padding:14px;transition:.2s;position:relative;background:var(--gray-50)}
.doc-item:hover{border-color:var(--blue);background:var(--blue-pale)}
.doc-item label{display:flex;flex-direction:column;gap:6px;cursor:pointer}
.doc-item .doc-icon{font-size:1.4rem;color:var(--gray-400)}
.doc-item .doc-name{font-size:.85rem;font-weight:600;color:var(--gray-800)}
.doc-item .doc-hint{font-size:.75rem;color:var(--gray-600)}
.doc-item input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.doc-item.has-file{border-color:var(--green);background:var(--green-pale)}
.doc-item.has-file .doc-icon{color:var(--green)}
.btn-submit{width:100%;padding:13px;background:linear-gradient(135deg,var(--blue-dark),var(--blue));color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:.95rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;transition:.2s;box-shadow:0 4px 14px rgba(21,101,192,.3);margin-top:8px}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(21,101,192,.4)}
.alert{padding:13px 16px;border-radius:10px;font-size:.88rem;font-weight:500;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px}
.alert.error{background:#ffebee;color:#c62828;border:1px solid rgba(198,40,40,.2)}
.alert.success{background:var(--green-pale);color:var(--green);border:1px solid rgba(46,125,50,.2)}
/* Status badge */
.status-badge{display:inline-block;padding:4px 12px;border-radius:50px;font-size:.75rem;font-weight:700}
/* Workflow steps */
.workflow{display:flex;flex-direction:column;gap:0}
.wf-step{display:flex;align-items:flex-start;gap:14px;padding:12px 0;position:relative}
.wf-step:not(:last-child)::after{content:'';position:absolute;left:15px;top:36px;bottom:0;width:2px;background:var(--gray-200)}
.wf-dot{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;z-index:1}
.wf-dot.done{background:var(--green);color:#fff}
.wf-dot.pending{background:var(--gray-200);color:var(--gray-600)}
.wf-dot.active{background:var(--blue);color:#fff}
.wf-label{font-size:.88rem;font-weight:600;color:var(--gray-800);margin-top:6px}
.wf-sub{font-size:.78rem;color:var(--gray-600);margin-top:2px}
/* QR section */
.qr-box{text-align:center;padding:20px;background:var(--gray-50);border-radius:12px;border:1px solid var(--gray-200)}
.qr-box img{width:160px;height:160px;border-radius:8px}
@media(max-width:600px){.form-row-2,.doc-upload-grid{grid-template-columns:1fr}.y-content{padding:16px}}
</style>
<link rel="stylesheet" href="youth.css"/>
</head>
<body>

<?php include 'sidebar.php'; ?>
<div class="y-main">
<?php include 'topbar.php'; ?>
<main class="y-content">

  <div class="y-page-header">
    <h2><i class="fas fa-award" style="color:var(--blue);margin-right:8px"></i>Organization Accreditation</h2>
    <p>Apply for LYDO accreditation for your youth organization. Upload all required documents to complete your application.</p>
  </div>

  <?php if ($error): ?>
    <div class="alert error"><i class="fas fa-exclamation-circle"></i><?=htmlspecialchars($error)?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert success"><i class="fas fa-check-circle"></i><?=htmlspecialchars($success)?></div>
  <?php endif; ?>

  <!-- EXISTING APPLICATIONS -->
  <?php if (!empty($applications)): ?>
  <?php foreach ($applications as $app):
    [$sc,$bg] = $statusColors[$app['status']] ?? ['#475569','#f1f5f9'];

    // Load workflow
    $wfStmt = $pdo->prepare('SELECT * FROM accreditation_workflow WHERE application_id=? ORDER BY id');
    $wfStmt->execute([$app['id']]);
    $workflow = $wfStmt->fetchAll();

    // Load documents
    $docStmt = $pdo->prepare('SELECT * FROM accreditation_documents WHERE application_id=?');
    $docStmt->execute([$app['id']]);
    $docs = $docStmt->fetchAll();
  ?>
  <div class="card">
    <div class="card-header" style="justify-content:space-between">
      <div style="display:flex;align-items:center;gap:8px">
        <i class="fas fa-building" style="color:var(--blue)"></i>
        <h3><?=htmlspecialchars($app['organization_name'])?></h3>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <span class="status-badge" style="background:<?=$bg?>;color:<?=$sc?>"><?=ucwords(str_replace('_',' ',$app['status']))?></span>
        <span style="font-size:.78rem;color:var(--gray-600)">App #<?=$app['id']?> · <?=date('M j, Y',strtotime($app['created_at']))?></span>
      </div>
    </div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        <!-- Workflow -->
        <div>
          <p style="font-size:.8rem;font-weight:700;color:var(--gray-600);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px">Application Progress</p>
          <div class="workflow">
            <?php
            $doneCount = 0;
            foreach ($workflow as $wf) { if ($wf['status']==='completed') $doneCount++; }
            $activeIdx = $doneCount;
            foreach ($workflow as $idx => $wf):
              $isDone   = $wf['status'] === 'completed';
              $isActive = $idx === $activeIdx && !$isDone;
              $dotClass = $isDone ? 'done' : ($isActive ? 'active' : 'pending');
              $icon     = $isDone ? 'fa-check' : ($isActive ? 'fa-circle-notch fa-spin' : 'fa-circle');
            ?>
            <div class="wf-step">
              <div class="wf-dot <?=$dotClass?>"><i class="fas <?=$icon?>"></i></div>
              <div>
                <div class="wf-label"><?=htmlspecialchars($wf['step'])?></div>
                <?php if ($isDone && $wf['done_at']): ?>
                  <div class="wf-sub">Completed <?=date('M j, Y',strtotime($wf['done_at']))?></div>
                <?php elseif ($isActive): ?>
                  <div class="wf-sub" style="color:var(--blue)">In progress...</div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Documents -->
        <div>
          <p style="font-size:.8rem;font-weight:700;color:var(--gray-600);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px">
            Documents (<?=$app['verified_count']?>/<?=$app['doc_count']?> verified)
          </p>
          <?php foreach ($docs as $doc):
            $dColors = ['verified'=>['#2e7d32','#e8f5e9','fa-check-circle'],'rejected'=>['#c62828','#ffebee','fa-times-circle'],'pending'=>['#f57f17','#fff8e1','fa-clock']];
            [$dc,$db,$di] = $dColors[$doc['status']] ?? ['#475569','#f1f5f9','fa-file'];
          ?>
          <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--gray-100)">
            <i class="fas <?=$di?>" style="color:<?=$dc?>;font-size:.9rem;width:16px"></i>
            <div style="flex:1">
              <div style="font-size:.83rem;font-weight:600"><?=htmlspecialchars($docLabels[$doc['doc_type']]??$doc['doc_type'])?></div>
              <div style="font-size:.75rem;color:var(--gray-600)"><?=htmlspecialchars($doc['original_name'])?></div>
            </div>
            <span style="background:<?=$db?>;color:<?=$dc?>;padding:2px 8px;border-radius:50px;font-size:.7rem;font-weight:700"><?=ucfirst($doc['status'])?></span>
          </div>
          <?php endforeach; ?>

          <?php if ($app['status'] === 'approved' && $app['certificate_no']): ?>
          <div class="qr-box" style="margin-top:14px">
            <i class="fas fa-certificate" style="font-size:2rem;color:var(--green);margin-bottom:8px;display:block"></i>
            <div style="font-weight:700;color:var(--green);margin-bottom:4px">Accredited!</div>
            <div style="font-size:.8rem;color:var(--gray-600)">Certificate No: <strong><?=htmlspecialchars($app['certificate_no'])?></strong></div>
            <?php if ($app['valid_until']): ?>
            <div style="font-size:.78rem;color:var(--gray-600);margin-top:4px">Valid until: <?=date('F j, Y',strtotime($app['valid_until']))?></div>
            <?php endif; ?>
            <?php if (!empty($app['qr_code'])): ?>
            <img src="<?=htmlspecialchars($app['qr_code'])?>" alt="QR Code" style="margin-top:10px"/>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <?php if ($app['status'] === 'rejected' && $app['rejection_reason']): ?>
          <div style="background:#ffebee;border-radius:8px;padding:10px 12px;margin-top:12px;font-size:.83rem;color:#c62828">
            <strong>Rejection Reason:</strong> <?=htmlspecialchars($app['rejection_reason'])?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <!-- APPLICATION FORM -->
  <?php
  $hasActive = !empty(array_filter($applications, fn($a) => !in_array($a['status'],['rejected'])));
  if (!$hasActive):
  ?>
  <div class="card">
    <div class="card-header"><i class="fas fa-file-alt"></i><h3>New Accreditation Application</h3></div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">

        <p style="font-size:.85rem;color:var(--gray-600);margin-bottom:20px;padding:12px 14px;background:var(--blue-pale);border-radius:8px;border-left:3px solid var(--blue)">
          <i class="fas fa-info-circle" style="margin-right:6px;color:var(--blue)"></i>
          Fill in your organization details and upload all required documents. Accepted formats: PDF, DOC, DOCX, JPG, PNG (max 10MB each).
        </p>

        <div style="font-size:.88rem;font-weight:700;color:var(--blue-dark);margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid var(--blue-pale)">
          <i class="fas fa-building" style="margin-right:6px"></i> Organization Information
        </div>

        <div class="form-row-2">
          <div class="fg">
            <label>Organization Name <span class="req">*</span></label>
            <?php
            $existingOrgs = $pdo->query('SELECT id, name, category FROM organizations WHERE is_active=1 ORDER BY name')->fetchAll();
            ?>
            <select id="organization_name_sel" onchange="handleAccredOrgSelect(this)" style="margin-bottom:6px">
              <option value="">— Select existing organization —</option>
              <?php foreach ($existingOrgs as $eo): ?>
              <option value="<?=htmlspecialchars($eo['name'])?>" data-category="<?=htmlspecialchars($eo['category']??'')?>"><?=htmlspecialchars($eo['name'])?></option>
              <?php endforeach; ?>
              <option value="__new__">+ Register a new organization</option>
            </select>
            <input type="text" id="organization_name" name="organization_name" required placeholder="Enter organization name" style="display:none"/>
          </div>
          <div class="fg"><label>Category</label>
            <select name="category">
              <option value="">Select category</option>
              <?php foreach (['Sangguniang Kabataan','Youth NGO','Religious Organization','Sports Club','Academic Organization','Community Group','Cultural Group','Other'] as $c): ?>
                <option value="<?=$c?>"><?=$c?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row-2">
          <div class="fg"><label>Contact Person <span class="req">*</span></label><input type="text" id="contact_person" name="contact_person" required placeholder="Full name"/></div>
          <div class="fg"><label>Contact Email <span class="req">*</span></label><input type="email" id="contact_email" name="contact_email" required placeholder="email@example.com"/></div>
        </div>
        <div class="form-row-2">
          <div class="fg"><label>Contact Phone</label><input type="text" name="contact_phone" placeholder="09XX-XXX-XXXX"/></div>
          <div class="fg"><label>Barangay</label><input type="text" name="barangay" placeholder="e.g. Barangay 1 - Poblacion"/></div>
        </div>
        <div class="fg"><label>Organization Description</label><textarea name="description" rows="3" placeholder="Brief description of your organization's purpose and activities..."></textarea></div>

        <div style="font-size:.88rem;font-weight:700;color:var(--blue-dark);margin:20px 0 14px;padding-bottom:8px;border-bottom:2px solid var(--blue-pale)">
          <i class="fas fa-paperclip" style="margin-right:6px"></i> Required Documents
        </div>

        <div class="doc-upload-grid">
          <?php foreach ($docLabels as $type => $label): ?>
          <div class="doc-item" id="doc_wrap_<?=$type?>">
            <label>
              <i class="fas fa-file-upload doc-icon" id="doc_icon_<?=$type?>"></i>
              <span class="doc-name"><?=htmlspecialchars($label)?></span>
              <span class="doc-hint" id="doc_hint_<?=$type?>">Click to upload or drag & drop</span>
              <input type="file" name="<?=$type?>" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                onchange="markFile('<?=$type?>',this)"/>
            </label>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Check panel -->
        <div id="accredCheckPanel" style="display:none"></div>

        <button type="button" id="accredCheckBtn" style="width:100%;padding:11px;background:#f1f5f9;color:#1565c0;border:1.5px solid #90caf9;border-radius:10px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:10px;transition:.2s" onmouseover="this.style.background='#e3f2fd'" onmouseout="this.style.background='#f1f5f9'">
          <i class="fas fa-search"></i> Check My Submission First
        </button>

        <button type="submit" class="btn-submit" id="accredSubmitBtn">
          <i class="fas fa-paper-plane"></i> Submit Accreditation Application
        </button>
      </form>
    </div>
  </div>
  <?php endif; ?>

</main>
</div>

<script src="form_checker.js"></script>
<script>
function handleAccredOrgSelect(sel) {
  const nameInput = document.getElementById('organization_name');
  const catSelect = document.querySelector('select[name="category"]');
  if (sel.value === '__new__') {
    nameInput.style.display = 'block';
    nameInput.value = '';
    nameInput.focus();
  } else if (sel.value) {
    nameInput.style.display = 'none';
    nameInput.value = sel.value;
    // Auto-fill category
    const opt = sel.options[sel.selectedIndex];
    if (catSelect && opt.dataset.category) catSelect.value = opt.dataset.category;
  } else {
    nameInput.style.display = 'none';
    nameInput.value = '';
  }
}

function markFile(type, input) {
  const wrap = document.getElementById('doc_wrap_' + type);
  const icon = document.getElementById('doc_icon_' + type);
  const hint = document.getElementById('doc_hint_' + type);
  if (input.files[0]) {
    wrap.classList.add('has-file');
    icon.className = 'fas fa-check-circle doc-icon';
    hint.textContent = input.files[0].name;
  }
}
// Sidebar toggle
const hamburger = document.getElementById('yHamburger');
const sidebar   = document.getElementById('ySidebar');
const overlay   = document.getElementById('yOverlay');
const closeBtn  = document.getElementById('ySidebarClose');
function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('open'); }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }
hamburger.addEventListener('click', openSidebar);
closeBtn.addEventListener('click',  closeSidebar);
overlay.addEventListener('click',   closeSidebar);

// Check My Submission First
FormChecker.init({
  formId:      'accredForm',
  checkBtnId:  'accredCheckBtn',
  submitBtnId: 'accredSubmitBtn',
  panelId:     'accredCheckPanel',
  requiredFields: [
    { id: 'organization_name', label: 'Organization Name' },
    { id: 'contact_person',    label: 'Contact Person' },
    { id: 'contact_email',     label: 'Contact Email' },
  ],
  requiredFiles: [
    { id: 'letter_of_intent', label: 'Letter of Intent' },
    { id: 'nyc_form',         label: 'NYC Accreditation Form' },
    { id: 'officers_list',    label: 'Officers and Members List' },
    { id: 'constitution',     label: 'Constitution and By-Laws' },
    { id: 'lydo_form',        label: 'LYDO Accreditation Form' },
  ],
});
</script>
</body>
</html>
