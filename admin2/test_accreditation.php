<?php
require_once 'config.php';
requireLogin();

$pdo = db();
$errors = [];
$ok = [];

// Test each table
$tables = ['accreditation_applications','accreditation_documents','accreditation_workflow'];
foreach ($tables as $t) {
    try {
        $c = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        $ok[] = "✓ Table <strong>$t</strong> exists — $c rows";
    } catch (Exception $e) {
        $errors[] = "✗ Table <strong>$t</strong>: " . $e->getMessage();
    }
}

// Test the main query in accreditation.php
try {
    $apps = $pdo->query(
        "SELECT a.*, u.first_name, u.last_name,
         (SELECT COUNT(*) FROM accreditation_documents d WHERE d.application_id=a.id) as doc_count,
         (SELECT COUNT(*) FROM accreditation_documents d WHERE d.application_id=a.id AND d.status='verified') as verified_count
         FROM accreditation_applications a
         JOIN youth_users u ON u.id=a.submitted_by
         ORDER BY a.created_at DESC"
    )->fetchAll();
    $ok[] = "✓ Main query works — " . count($apps) . " applications";
} catch (Exception $e) {
    $errors[] = "✗ Main query failed: " . $e->getMessage();
}

// Test counts query
try {
    foreach (['submitted','under_review','approved','rejected'] as $s) {
        $c = $pdo->prepare("SELECT COUNT(*) FROM accreditation_applications WHERE status=?");
        $c->execute([$s]);
        $ok[] = "✓ Status count '$s': " . $c->fetchColumn();
    }
} catch (Exception $e) {
    $errors[] = "✗ Status count query: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head><title>Accreditation Test</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet"/>
<style>body{font-family:Inter,sans-serif;padding:40px;max-width:700px;margin:0 auto}h2{margin-bottom:20px}.ok{background:#e8f5e9;color:#2e7d32;padding:10px 14px;border-radius:8px;margin-bottom:8px;font-size:.9rem}.err{background:#ffebee;color:#c62828;padding:10px 14px;border-radius:8px;margin-bottom:8px;font-size:.9rem}.btn{display:inline-block;padding:10px 20px;background:#1565c0;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;margin-top:16px}</style>
</head>
<body>
<h2>Accreditation Diagnostic</h2>
<?php foreach ($ok as $m): ?><div class="ok"><?=$m?></div><?php endforeach; ?>
<?php foreach ($errors as $m): ?><div class="err"><?=$m?></div><?php endforeach; ?>
<?php if (empty($errors)): ?>
  <div class="ok" style="font-weight:700;margin-top:12px">✓ All checks passed! Accreditation page should work.</div>
  <a href="accreditation.php" class="btn">→ Go to Accreditation</a>
<?php else: ?>
  <div class="err" style="font-weight:700;margin-top:12px">Some issues found. Check errors above.</div>
<?php endif; ?>
</body>
</html>
