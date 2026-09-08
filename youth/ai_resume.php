<?php
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../shared/ai_config.php';
if (empty($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$pdo    = db();
$userId = (int)$_SESSION['user_id'];
$uStmt  = $pdo->prepare('SELECT * FROM youth_users WHERE id=? LIMIT 1');
$uStmt->execute([$userId]);
$user = $uStmt->fetch();
$nc   = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
$nc->execute([$userId]);
$notifCount = (int)$nc->fetchColumn();

$cls  = $user['youth_classification'] ? json_decode($user['youth_classification'], true) : [];
$prgs = $user['programs_interested']  ? json_decode($user['programs_interested'],  true) : [];

// Get volunteer history
$volStmt = $pdo->prepare('SELECT p.name, p.type, r.total_hours, r.status FROM volunteer_registrations r JOIN volunteer_programs p ON p.id=r.program_id WHERE r.user_id=? AND r.status IN ("approved","completed")');
$volStmt->execute([$userId]);
$volunteerHistory = $volStmt->fetchAll();

// Get org memberships
$orgStmt = $pdo->prepare('SELECT o.name, o.category, om.position, om.joined_at FROM organizations o JOIN organization_members om ON om.organization_id=o.id WHERE om.user_id=? AND om.is_active=1');
$orgStmt->execute([$userId]);
$orgMemberships = $orgStmt->fetchAll();

$generatedResume = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $objective  = trim($_POST['objective'] ?? '');
    $extraSkills= trim($_POST['extra_skills'] ?? '');
    $extraExp   = trim($_POST['extra_experience'] ?? '');
    $tone       = $_POST['tone'] ?? 'professional';

    // Build profile summary for AI
    $profileData = "Name: " . $user['first_name'] . ' ' . $user['last_name'] . "\n";
    $profileData .= "Age: " . $user['age'] . ", Gender: " . $user['gender'] . "\n";
    $profileData .= "Address: " . ($user['barangay'] ?? '') . ", Sta. Cruz, Laguna\n";
    $profileData .= "Contact: " . $user['contact_number'] . " | Email: " . $user['email'] . "\n";
    $profileData .= "Civil Status: " . $user['civil_status'] . "\n\n";
    $profileData .= "EDUCATION:\n";
    $profileData .= "Status: " . ($user['educational_status'] ?? 'Not specified') . "\n";
    if ($user['school_name']) $profileData .= "School: " . $user['school_name'] . "\n";
    if ($user['course_or_grade']) $profileData .= "Course/Grade: " . $user['course_or_grade'] . "\n\n";
    $profileData .= "EMPLOYMENT:\n";
    $profileData .= "Status: " . ($user['employment_status'] ?? 'Not specified') . "\n\n";
    if (!empty($orgMemberships)) {
        $profileData .= "ORGANIZATION MEMBERSHIPS:\n";
        foreach ($orgMemberships as $o) {
            $profileData .= "- " . $o['name'] . " (" . $o['category'] . ") — " . $o['position'];
            if ($o['joined_at']) $profileData .= ", since " . date('Y', strtotime($o['joined_at']));
            $profileData .= "\n";
        }
        $profileData .= "\n";
    }
    if (!empty($volunteerHistory)) {
        $profileData .= "VOLUNTEER EXPERIENCE:\n";
        foreach ($volunteerHistory as $v) {
            $profileData .= "- " . $v['name'] . " (" . $v['total_hours'] . " hours)\n";
        }
        $profileData .= "\n";
    }
    if ($user['skills']) $profileData .= "SKILLS: " . $user['skills'] . "\n";
    if ($user['interests']) $profileData .= "INTERESTS: " . $user['interests'] . "\n";
    if (!empty($cls)) $profileData .= "YOUTH CLASSIFICATION: " . implode(', ', $cls) . "\n";
    if ($extraSkills) $profileData .= "ADDITIONAL SKILLS: " . $extraSkills . "\n";
    if ($extraExp) $profileData .= "ADDITIONAL EXPERIENCE: " . $extraExp . "\n";

    $toneDesc = $tone === 'professional' ? 'formal and professional' : ($tone === 'creative' ? 'creative and dynamic' : 'simple and straightforward');

    $messages = [
        ['role' => 'system', 'content' =>
            "You are an expert resume writer specializing in Philippine youth resumes. " .
            "Create a well-structured, $toneDesc resume in plain text format. " .
            "Use clear section headers (OBJECTIVE, PERSONAL INFORMATION, EDUCATION, WORK EXPERIENCE, SKILLS, ORGANIZATIONS, VOLUNTEER WORK, REFERENCES). " .
            "Make it ATS-friendly and appropriate for a Filipino youth applying for jobs or scholarships. " .
            "If information is missing, write appropriate placeholder text. " .
            "Format it cleanly with proper spacing."
        ],
        ['role' => 'user', 'content' =>
            "Create a resume for this youth from Sta. Cruz, Laguna:\n\n" . $profileData .
            ($objective ? "\nCAREER OBJECTIVE: " . $objective : "") .
            "\n\nTone: $toneDesc"
        ]
    ];

    $generatedResume = openai_chat($messages, 0.6, 1500);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>AI Resume Builder – LYDO</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="youth.css"/>
<style>
.resume-output{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:28px;font-family:'Courier New',monospace;font-size:.88rem;line-height:1.8;white-space:pre-wrap;color:#1e293b;min-height:400px}
.tone-btn{padding:8px 18px;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer;color:#475569;background:#fff;transition:.2s}
.tone-btn.active{background:#1565c0;border-color:#1565c0;color:#fff}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="y-main">
<?php include 'topbar.php'; ?>
<main class="y-content">

<div class="y-page-header">
  <h2><i class="fas fa-file-alt" style="color:#1565c0;margin-right:8px"></i>AI Resume Builder</h2>
  <p>Generate a professional resume using your LYDO profile data powered by AI.</p>
</div>

<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:20px;align-items:start">

  <!-- LEFT: Form -->
  <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden">
    <div style="padding:14px 20px;border-bottom:1px solid #e2e8f0;background:linear-gradient(135deg,#0d3b6e,#1565c0);color:#fff;display:flex;align-items:center;gap:8px">
      <i class="fas fa-sliders-h"></i><span style="font-weight:700">Resume Settings</span>
    </div>
    <form method="POST" style="padding:20px">
      <input type="hidden" name="generate" value="1"/>

      <!-- Profile preview -->
      <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-bottom:18px;font-size:.83rem">
        <div style="font-weight:700;color:#0d3b6e;margin-bottom:8px"><i class="fas fa-user"></i> Profile Data (auto-filled)</div>
        <div style="color:#475569;line-height:1.8">
          <div><strong>Name:</strong> <?=htmlspecialchars($user['first_name'].' '.$user['last_name'])?></div>
          <div><strong>Education:</strong> <?=htmlspecialchars($user['educational_status'] ?: 'Not set')?></div>
          <div><strong>Skills:</strong> <?=htmlspecialchars($user['skills'] ?: 'Not set')?></div>
          <div><strong>Orgs:</strong> <?=count($orgMemberships)?> membership(s)</div>
          <div><strong>Volunteer:</strong> <?=count($volunteerHistory)?> program(s)</div>
        </div>
      </div>

      <div style="margin-bottom:14px">
        <label style="font-size:.83rem;font-weight:600;color:#1e293b;display:block;margin-bottom:6px">Resume Tone</label>
        <div style="display:flex;gap:8px" id="toneBtns">
          <button type="button" class="tone-btn active" onclick="setTone('professional',this)">Professional</button>
          <button type="button" class="tone-btn" onclick="setTone('creative',this)">Creative</button>
          <button type="button" class="tone-btn" onclick="setTone('simple',this)">Simple</button>
        </div>
        <input type="hidden" name="tone" id="toneInput" value="professional"/>
      </div>

      <div style="margin-bottom:14px">
        <label style="font-size:.83rem;font-weight:600;color:#1e293b;display:block;margin-bottom:6px">Career Objective <span style="color:#94a3b8;font-weight:400">(optional)</span></label>
        <textarea name="objective" rows="3" placeholder="e.g. To obtain a position where I can apply my leadership skills and contribute to community development..." style="width:100%;padding:9px 13px;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.88rem;outline:none;resize:vertical;background:#f8fafc"></textarea>
      </div>

      <div style="margin-bottom:14px">
        <label style="font-size:.83rem;font-weight:600;color:#1e293b;display:block;margin-bottom:6px">Additional Skills <span style="color:#94a3b8;font-weight:400">(optional)</span></label>
        <input type="text" name="extra_skills" placeholder="e.g. Microsoft Office, Canva, Video Editing" style="width:100%;padding:9px 13px;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.88rem;outline:none;background:#f8fafc"/>
      </div>

      <div style="margin-bottom:18px">
        <label style="font-size:.83rem;font-weight:600;color:#1e293b;display:block;margin-bottom:6px">Additional Experience <span style="color:#94a3b8;font-weight:400">(optional)</span></label>
        <textarea name="extra_experience" rows="2" placeholder="e.g. Part-time cashier at SM Sta. Cruz (2024-2025)..." style="width:100%;padding:9px 13px;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.88rem;outline:none;resize:vertical;background:#f8fafc"></textarea>
      </div>

      <button type="submit" id="genBtn" style="width:100%;padding:13px;background:linear-gradient(135deg,#0d3b6e,#1565c0);color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:.95rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;transition:.2s;box-shadow:0 4px 14px rgba(21,101,192,.3)">
        <i class="fas fa-magic"></i> Generate Resume with AI
      </button>
    </form>
  </div>

  <!-- RIGHT: Output -->
  <div>
    <?php if ($generatedResume): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
      <span style="font-weight:700;color:#0d3b6e"><i class="fas fa-check-circle" style="color:#2e7d32"></i> Resume Generated!</span>
      <div style="display:flex;gap:8px">
        <button onclick="copyResume()" style="padding:7px 14px;background:#e3f2fd;color:#1565c0;border:1.5px solid #90caf9;border-radius:8px;font-family:inherit;font-size:.82rem;font-weight:700;cursor:pointer"><i class="fas fa-copy"></i> Copy</button>
        <button onclick="printResume()" style="padding:7px 14px;background:#e8f5e9;color:#2e7d32;border:1.5px solid #a5d6a7;border-radius:8px;font-family:inherit;font-size:.82rem;font-weight:700;cursor:pointer"><i class="fas fa-print"></i> Print / Save PDF</button>
      </div>
    </div>
    <div class="resume-output" id="resumeOutput"><?=htmlspecialchars($generatedResume)?></div>
    <?php else: ?>
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:48px;text-align:center;color:#94a3b8">
      <i class="fas fa-file-alt" style="font-size:3rem;display:block;margin-bottom:16px;color:#e2e8f0"></i>
      <p style="font-size:.95rem;font-weight:600;margin-bottom:8px">Your AI-generated resume will appear here</p>
      <p style="font-size:.85rem">Fill in the settings and click Generate to create your resume.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

</main>
</div>

<div id="printArea" style="display:none"></div>

<script>
const yHam=document.getElementById('yHamburger'),ySb=document.getElementById('ySidebar'),yOv=document.getElementById('yOverlay'),yCl=document.getElementById('ySidebarClose');
if(yHam)yHam.onclick=()=>{ySb.classList.add('open');yOv.classList.add('open')};
if(yCl)yCl.onclick=()=>{ySb.classList.remove('open');yOv.classList.remove('open')};
if(yOv)yOv.onclick=()=>{ySb.classList.remove('open');yOv.classList.remove('open')};

function setTone(val, btn) {
  document.getElementById('toneInput').value = val;
  document.querySelectorAll('.tone-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

function copyResume() {
  const text = document.getElementById('resumeOutput').textContent;
  navigator.clipboard.writeText(text).then(() => alert('Resume copied to clipboard!'));
}

function printResume() {
  const content = document.getElementById('resumeOutput').textContent;
  const w = window.open('', '_blank');
  w.document.write('<html><head><title>Resume</title><style>body{font-family:Courier New,monospace;padding:40px;font-size:12px;line-height:1.8;white-space:pre-wrap}</style></head><body>' + content.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</body></html>');
  w.document.close();
  w.print();
}

document.querySelector('form').addEventListener('submit', function() {
  const btn = document.getElementById('genBtn');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
  btn.disabled = true;
});
</script>
</body>
</html>
