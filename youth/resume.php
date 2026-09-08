<?php
require_once __DIR__ . "/../shared/config.php";
if (empty($_SESSION["user_id"])) { header("Location: ../login.php"); exit; }
$pdo    = db();
$userId = (int)$_SESSION["user_id"];

// Load user profile
$uStmt = $pdo->prepare("SELECT * FROM youth_users WHERE id=? LIMIT 1");
$uStmt->execute([$userId]);
$user = $uStmt->fetch();
$nc   = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
$nc->execute([$userId]);
$notifCount = (int)$nc->fetchColumn();

// Volunteer programs completed/approved
$volStmt = $pdo->prepare("SELECT r.*,p.name as prog_name,p.type as prog_type,p.location FROM volunteer_registrations r JOIN volunteer_programs p ON p.id=r.program_id WHERE r.user_id=? AND r.status IN (\"approved\",\"completed\") ORDER BY r.created_at DESC");
$volStmt->execute([$userId]);
$volunteerHistory = $volStmt->fetchAll();

// Events attended — via organizations the user belongs to
$eventsAttended = [];
try {
    $orgIds = $pdo->prepare("SELECT organization_id FROM organization_members WHERE user_id=? AND is_active=1");
    $orgIds->execute([$userId]);
    $orgIdList = $orgIds->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($orgIdList)) {
        $placeholders = implode(',', array_fill(0, count($orgIdList), '?'));
        $attStmt = $pdo->prepare(
            "SELECT ea.*, e.title as event_title, e.event_date, e.location, e.event_type, o.name as org_name
             FROM event_attendance ea
             JOIN events e ON e.id = ea.event_id
             JOIN organizations o ON o.id = ea.organization_id
             WHERE ea.organization_id IN ($placeholders) AND ea.status IN ('present','representative')
             ORDER BY e.event_date DESC"
        );
        $attStmt->execute($orgIdList);
        $eventsAttended = $attStmt->fetchAll();
    }
} catch (Exception $e) {
    $eventsAttended = [];
}

// Scholarship beneficiary
$scholarships = [];
try {
    $schStmt = $pdo->prepare("SELECT a.*,b.name as batch_name,b.school_year FROM scholarship_applications a JOIN scholarship_batches b ON b.id=a.batch_id WHERE a.user_id=? AND a.status IN (\"approved\",\"beneficiary\") ORDER BY a.created_at DESC");
    $schStmt->execute([$userId]);
    $scholarships = $schStmt->fetchAll();
} catch (Exception $e) {
    $scholarships = [];
}

// Parse JSON fields
$cls  = $user["youth_classification"] ? json_decode($user["youth_classification"], true) : [];
$prgs = $user["programs_interested"]  ? json_decode($user["programs_interested"],  true) : [];
$skills   = array_filter(array_map("trim", explode(",", $user["skills"] ?? "")));
$interests = array_filter(array_map("trim", explode(",", $user["interests"] ?? "")));

$fullName = trim(($user["first_name"]??"")." ".($user["middle_name"]??"")." ".($user["last_name"]??""));
$address  = trim(implode(", ", array_filter([$user["house_number"]??"", $user["street"]??"", $user["barangay"]??"", $user["municipality"]??"", $user["province"]??""])));

$typeLabels = ["youth_volunteer"=>"Youth Volunteer Program","linggo_kabataan"=>"Linggo ng Kabataan","junior_officials"=>"Junior Officials Program"];
$eventTypeLabels = ["official_event"=>"Official MYDC/LYDO Event","meeting_patawag"=>"Meeting/Patawag","invitation"=>"Organization Invitation","other"=>"Event"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>My Resume – <?= htmlspecialchars($fullName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="youth.css"/>
<style>
/* Print bar */
.print-bar{background:#1565c0;color:#fff;padding:12px 24px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.print-bar span{flex:1;font-size:.9rem;font-weight:500}
.btn-print{padding:8px 18px;background:#fff;color:#1565c0;border:none;border-radius:8px;font-family:inherit;font-size:.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.2s}
.btn-print:hover{background:#e3f2fd}
.btn-back-r{padding:8px 18px;background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px}

/* Resume wrapper */
.resume-wrap{max-width:860px;margin:24px auto;background:#fff;box-shadow:0 4px 24px rgba(0,0,0,.12);font-family:'Inter',sans-serif;color:#1e293b}

/* Header */
.r-header{background:linear-gradient(135deg,#0d3b6e 0%,#1565c0 60%,#1b5e20 100%);color:#fff;padding:36px 44px;display:flex;align-items:flex-start;gap:24px}
.r-avatar{width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.2);border:3px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;flex-shrink:0}
.r-name{font-family:'Playfair Display',serif;font-size:2rem;font-weight:700;margin-bottom:4px}
.r-tagline{font-size:.88rem;opacity:.85;margin-bottom:12px}
.r-contact{display:flex;flex-wrap:wrap;gap:14px;font-size:.8rem;opacity:.9}
.r-contact span{display:flex;align-items:center;gap:5px}

/* Body */
.r-body{display:grid;grid-template-columns:1fr 2fr;gap:0}
.r-left{background:#f8fafc;padding:28px 24px;border-right:1px solid #e2e8f0}
.r-right{padding:28px 32px}

/* Section */
.r-section{margin-bottom:24px}
.r-section:last-child{margin-bottom:0}
.r-section-title{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#1565c0;margin-bottom:12px;padding-bottom:6px;border-bottom:2px solid #e3f2fd;display:flex;align-items:center;gap:6px}
.r-section-title i{font-size:.8rem}

/* Skills */
.skill-tag{display:inline-block;background:#e3f2fd;color:#1565c0;padding:4px 10px;border-radius:50px;font-size:.75rem;font-weight:600;margin:3px 3px 3px 0}
.skill-tag.green{background:#e8f5e9;color:#2e7d32}
.skill-tag.purple{background:#f3e5f5;color:#7b1fa2}

/* Timeline items */
.r-item{margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #f1f5f9;position:relative}
.r-item:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.r-item-title{font-size:.9rem;font-weight:700;color:#1e293b;margin-bottom:2px}
.r-item-sub{font-size:.82rem;color:#1565c0;font-weight:600;margin-bottom:3px}
.r-item-meta{font-size:.75rem;color:#94a3b8;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.r-item-desc{font-size:.82rem;color:#475569;margin-top:5px;line-height:1.6}
.r-dot{width:8px;height:8px;border-radius:50%;background:#1565c0;flex-shrink:0}

/* Info rows */
.r-info-row{display:flex;gap:8px;padding:5px 0;font-size:.82rem;border-bottom:1px solid #f1f5f9}
.r-info-row:last-child{border-bottom:none}
.r-info-label{width:90px;flex-shrink:0;color:#94a3b8;font-weight:500}
.r-info-val{color:#1e293b;font-weight:500}

/* Cert badge */
.cert-badge{display:inline-flex;align-items:center;gap:6px;background:#e8f5e9;color:#2e7d32;padding:4px 10px;border-radius:6px;font-size:.75rem;font-weight:700;margin-top:4px}

@media print{
  body{background:#fff}
  .print-bar,.y-sidebar,.y-main>.y-topbar{display:none!important}
  .y-main{margin-left:0!important}
  .resume-wrap{box-shadow:none;margin:0;max-width:100%}
  @page{size:A4;margin:0}
}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>
<div class="y-main">
<?php include 'topbar.php'; ?>

<!-- Print bar -->
<div class="print-bar">
  <a href="dashboard.php" class="btn-back-r"><i class="fas fa-arrow-left"></i> Back</a>
  <span>Resume Builder — <?= htmlspecialchars($fullName) ?></span>
  <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print / Save PDF</button>
</div>

<!-- RESUME -->
<div class="resume-wrap" id="resume">

  <!-- HEADER -->
  <div class="r-header">
    <div class="r-avatar"><?= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) ?></div>
    <div style="flex:1">
      <div class="r-name"><?= htmlspecialchars(strtoupper($fullName)) ?></div>
      <div class="r-tagline">
        <?= htmlspecialchars($user['educational_status'] ?: 'Youth Member') ?>
        <?php if ($user['school_name']): ?> · <?= htmlspecialchars($user['school_name']) ?><?php endif; ?>
      </div>
      <div class="r-contact">
        <?php if ($user['contact_number']): ?><span><i class="fas fa-phone"></i><?= htmlspecialchars($user['contact_number']) ?></span><?php endif; ?>
        <?php if ($user['email']): ?><span><i class="fas fa-envelope"></i><?= htmlspecialchars($user['email']) ?></span><?php endif; ?>
        <?php if ($address): ?><span><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($address) ?></span><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="r-body">

    <!-- LEFT COLUMN -->
    <div class="r-left">

      <!-- Personal Info -->
      <div class="r-section">
        <div class="r-section-title"><i class="fas fa-user"></i> Personal Info</div>
        <?php
        $infos = [
          'Age'          => $user['age'] ? $user['age'].' years old' : null,
          'Birthdate'    => $user['birthdate'] ? date('F j, Y', strtotime($user['birthdate'])) : null,
          'Gender'       => $user['gender'],
          'Civil Status' => $user['civil_status'],
          'Barangay'     => $user['barangay'],
        ];
        foreach ($infos as $label => $val): if (!$val) continue; ?>
        <div class="r-info-row">
          <span class="r-info-label"><?= $label ?></span>
          <span class="r-info-val"><?= htmlspecialchars($val) ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Skills -->
      <?php if (!empty($skills)): ?>
      <div class="r-section">
        <div class="r-section-title"><i class="fas fa-star"></i> Skills & Talents</div>
        <?php foreach ($skills as $s): ?>
          <span class="skill-tag"><?= htmlspecialchars($s) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Interests -->
      <?php if (!empty($interests)): ?>
      <div class="r-section">
        <div class="r-section-title"><i class="fas fa-heart"></i> Interests</div>
        <?php foreach ($interests as $i): ?>
          <span class="skill-tag green"><?= htmlspecialchars($i) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Youth Classification -->
      <?php if (!empty($cls)): ?>
      <div class="r-section">
        <div class="r-section-title"><i class="fas fa-id-card"></i> Classification</div>
        <?php foreach ((array)$cls as $c): ?>
          <span class="skill-tag purple"><?= htmlspecialchars($c) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Volunteer hours summary -->
      <?php
      $totalHours = array_sum(array_column($volunteerHistory, 'total_hours'));
      if ($totalHours > 0):
      ?>
      <div class="r-section">
        <div class="r-section-title"><i class="fas fa-clock"></i> Volunteer Hours</div>
        <div style="font-size:1.6rem;font-weight:800;color:#1565c0"><?= number_format($totalHours, 1) ?></div>
        <div style="font-size:.75rem;color:#94a3b8">Total hours rendered</div>
      </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT COLUMN -->
    <div class="r-right">

      <!-- Education -->
      <div class="r-section">
        <div class="r-section-title"><i class="fas fa-graduation-cap"></i> Education</div>
        <?php if ($user['school_name'] || $user['educational_status']): ?>
        <div class="r-item">
          <div class="r-item-title"><?= htmlspecialchars($user['school_name'] ?: 'School not specified') ?></div>
          <?php if ($user['course_or_grade']): ?>
          <div class="r-item-sub"><?= htmlspecialchars($user['course_or_grade']) ?></div>
          <?php endif; ?>
          <div class="r-item-meta">
            <span class="r-dot"></span>
            <?= htmlspecialchars($user['educational_status'] ?: 'Student') ?>
          </div>
        </div>
        <?php else: ?>
        <p style="font-size:.82rem;color:#94a3b8;font-style:italic">No education information added yet.</p>
        <?php endif; ?>
      </div>

      <!-- Employment / Organization -->
      <?php if ($user['employment_status'] || $user['organization_name']): ?>
      <div class="r-section">
        <div class="r-section-title"><i class="fas fa-briefcase"></i> Work & Organization</div>
        <?php if ($user['organization_name']): ?>
        <div class="r-item">
          <div class="r-item-title"><?= htmlspecialchars($user['organization_name']) ?></div>
          <?php if ($user['organization_role']): ?>
          <div class="r-item-sub"><?= htmlspecialchars($user['organization_role']) ?></div>
          <?php endif; ?>
          <div class="r-item-meta">
            <span class="r-dot"></span>
            <?= htmlspecialchars($user['organization_type'] ?: 'Organization') ?>
            <?php if ($user['years_membership']): ?> · <?= $user['years_membership'] ?> year<?= $user['years_membership']!=1?'s':'' ?><?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($user['employment_status']): ?>
        <div class="r-item">
          <div class="r-item-title"><?= htmlspecialchars($user['employment_status']) ?></div>
          <div class="r-item-meta"><span class="r-dot"></span> Employment Status</div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Volunteer Programs -->
      <?php if (!empty($volunteerHistory)): ?>
      <div class="r-section">
        <div class="r-section-title"><i class="fas fa-hands-helping"></i> Volunteer Experience</div>
        <?php foreach ($volunteerHistory as $v): ?>
        <div class="r-item">
          <div class="r-item-title"><?= htmlspecialchars($v['prog_name']) ?></div>
          <div class="r-item-sub"><?= htmlspecialchars($typeLabels[$v['prog_type']] ?? $v['prog_type']) ?></div>
          <div class="r-item-meta">
            <span class="r-dot"></span>
            <?php if ($v['location']): ?><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($v['location']) ?><?php endif; ?>
            <?php if ($v['total_hours'] > 0): ?> · <i class="fas fa-clock"></i><?= $v['total_hours'] ?> hrs<?php endif; ?>
            <?php if ($v['orientation_date']): ?> · <?= date('M Y', strtotime($v['orientation_date'])) ?><?php endif; ?>
          </div>
          <?php if ($v['certificate_issued']): ?>
          <div class="cert-badge"><i class="fas fa-certificate"></i> Certificate Issued</div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Events / Seminars Attended -->
      <?php if (!empty($eventsAttended)): ?>
      <div class="r-section">
        <div class="r-section-title"><i class="fas fa-calendar-check"></i> Events & Seminars Attended</div>
        <?php foreach ($eventsAttended as $e): ?>
        <div class="r-item">
          <div class="r-item-title"><?= htmlspecialchars($e['event_title']) ?></div>
          <div class="r-item-sub"><?= htmlspecialchars($eventTypeLabels[$e['event_type']] ?? 'Event') ?></div>
          <div class="r-item-meta">
            <span class="r-dot"></span>
            <i class="fas fa-calendar"></i><?= date('F j, Y', strtotime($e['event_date'])) ?>
            <?php if ($e['location']): ?> · <i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($e['location']) ?><?php endif; ?>
            <?php if (!empty($e['org_name'])): ?> · <?= htmlspecialchars($e['org_name']) ?><?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Scholarships -->
      <?php if (!empty($scholarships)): ?>
      <div class="r-section">
        <div class="r-section-title"><i class="fas fa-award"></i> Scholarships & Awards</div>
        <?php foreach ($scholarships as $s): ?>
        <div class="r-item">
          <div class="r-item-title">Iskolar ng Bayan – <?= htmlspecialchars($s['batch_name']) ?></div>
          <div class="r-item-sub">LYDO Scholarship Beneficiary</div>
          <div class="r-item-meta">
            <span class="r-dot"></span>
            <i class="fas fa-calendar"></i><?= htmlspecialchars($s['school_year']) ?>
            <?php if ($s['gwa']): ?> · GWA: <?= $s['gwa'] ?><?php endif; ?>
          </div>
          <div class="cert-badge"><i class="fas fa-star"></i> Scholarship Recipient</div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Programs Interested -->
      <?php if (!empty($prgs)): ?>
      <div class="r-section">
        <div class="r-section-title"><i class="fas fa-list-check"></i> Areas of Interest</div>
        <div class="r-item-desc">
          <?php foreach ((array)$prgs as $p): ?>
            <span class="skill-tag"><?= htmlspecialchars($p) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Footer note -->
      <div style="margin-top:20px;padding-top:14px;border-top:1px solid #e2e8f0;font-size:.72rem;color:#94a3b8;text-align:center">
        Generated by LYDO Youth Portal · Sta. Cruz, Laguna · <?= date('F j, Y') ?>
      </div>

    </div>
  </div>
</div>

</div><!-- y-main -->

<script>
const yHam=document.getElementById('yHamburger'),ySb=document.getElementById('ySidebar'),yOv=document.getElementById('yOverlay'),yCl=document.getElementById('ySidebarClose');
if(yHam)yHam.onclick=()=>{ySb.classList.add('open');yOv.classList.add('open')};
if(yCl)yCl.onclick=()=>{ySb.classList.remove('open');yOv.classList.remove('open')};
if(yOv)yOv.onclick=()=>{ySb.classList.remove('open');yOv.classList.remove('open')};
</script>
</body>
</html>
