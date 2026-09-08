<?php
/**
 * Email Test Script
 * Use this to test if PHPMailer is working correctly
 */

require_once 'lydo-system/shared/email_config.php';

// ============================================
// CHANGE THIS TO YOUR EMAIL ADDRESS
// ============================================
$testEmail = 'your-email@gmail.com';  // ← PUT YOUR EMAIL HERE
$testName  = 'Test User';
// ============================================

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Email Test - LYDO</title>
<style>
body{font-family:Arial,sans-serif;max-width:600px;margin:50px auto;padding:20px;background:#f8fafc}
.card{background:#fff;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.1)}
h1{color:#1565c0;margin-bottom:10px}
.status{padding:15px;border-radius:8px;margin:20px 0;font-weight:600}
.success{background:#e8f5e9;color:#2e7d32;border:2px solid #a5d6a7}
.error{background:#ffebee;color:#c62828;border:2px solid #ef9a9a}
.info{background:#e3f2fd;color:#1565c0;border:2px solid #90caf9}
code{background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:14px}
.btn{display:inline-block;background:#1565c0;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;margin:10px 0}
.btn:hover{background:#0d47a1}
</style>
</head>
<body>
<div class="card">
<h1>📧 LYDO Email Test</h1>
<p>This script tests if PHPMailer is configured correctly.</p>

<?php
if ($testEmail === 'your-email@gmail.com') {
    echo '<div class="status error">
    ❌ <strong>Please update the email address!</strong><br/>
    Edit this file and change <code>$testEmail</code> to your actual email address.
    </div>';
    exit;
}

echo '<div class="status info">
📤 Sending test email to: <strong>' . htmlspecialchars($testEmail) . '</strong><br/>
Please wait...
</div>';

$subject = '✅ LYDO Email Test - Success!';
$htmlBody = '
<html>
<head>
<style>
body{font-family:Arial,sans-serif;line-height:1.6}
.container{max-width:600px;margin:0 auto;padding:20px;background:#f8fafc}
.header{background:linear-gradient(135deg,#0d3b6e,#1565c0);color:white;padding:30px;text-align:center;border-radius:10px 10px 0 0}
.content{background:#fff;padding:30px;border:1px solid #e2e8f0}
.success{background:#e8f5e9;color:#2e7d32;padding:15px;border-radius:8px;margin:20px 0}
</style>
</head>
<body>
<div class="container">
<div class="header">
<h1>✅ Email Test Successful!</h1>
<p>LYDO Sta. Cruz, Laguna</p>
</div>
<div class="content">
<p>Hello <strong>' . htmlspecialchars($testName) . '</strong>,</p>

<div class="success">
🎉 <strong>Congratulations!</strong><br/>
Your PHPMailer configuration is working correctly.
</div>

<p>This confirms that:</p>
<ul>
<li>✅ PHPMailer is installed properly</li>
<li>✅ SMTP credentials are correct</li>
<li>✅ Gmail connection is successful</li>
<li>✅ HTML emails can be sent</li>
</ul>

<p>The LYDO email notification system is now ready to send approval emails to youth members!</p>

<p style="margin-top:30px;color:#64748b;font-size:14px">
This is an automated test message from LYDO Youth Portal.<br/>
Sent on: ' . date('F j, Y g:i A') . '
</p>
</div>
</div>
</body>
</html>
';

$result = sendEmail($testEmail, $testName, $subject, $htmlBody);

if ($result) {
    echo '<div class="status success">
    ✅ <strong>Email sent successfully!</strong><br/>
    <br/>
    Check your inbox: <code>' . htmlspecialchars($testEmail) . '</code><br/>
    <br/>
    <small>Note: If you don\'t see it, check your spam/junk folder.</small>
    </div>';
    
    echo '<p><strong>Next Steps:</strong></p>
    <ol>
    <li>Check your email inbox</li>
    <li>If successful, delete this test file: <code>test_email.php</code></li>
    <li>Go to Approvals page and approve a youth registration</li>
    </ol>';
    
} else {
    echo '<div class="status error">
    ❌ <strong>Email failed to send</strong><br/>
    <br/>
    Please check:
    <ul>
    <li>Gmail credentials in <code>email_config.php</code></li>
    <li>App Password is correct (no spaces)</li>
    <li>2-Step Verification is enabled</li>
    <li>Internet connection is active</li>
    <li>XAMPP Apache is running</li>
    </ul>
    <br/>
    Check error logs: <code>c:\xampp\apache\logs\error.log</code>
    </div>';
}
?>

<p style="margin-top:30px">
<a href="lydo-system/admin2/approvals.php" class="btn">Go to Approvals Page</a>
<a href="PHPMAILER_SETUP_GUIDE.md" class="btn" style="background:#64748b">Setup Guide</a>
</p>

</div>
</body>
</html>
