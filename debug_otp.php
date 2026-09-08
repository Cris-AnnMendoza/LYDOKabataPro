<?php
// Temporary debug page - DELETE after fixing
require_once __DIR__ . '/shared/config.php';
$pdo = db();

echo '<pre>';
echo 'PHP time:   ' . date('Y-m-d H:i:s') . "\n";
echo 'PHP timezone: ' . date_default_timezone_get() . "\n";

$r = $pdo->query("SELECT NOW() as mysql_now, @@global.time_zone as tz, @@session.time_zone as sess_tz");
$row = $r->fetch();
echo 'MySQL NOW(): ' . $row['mysql_now'] . "\n";
echo 'MySQL global TZ: ' . $row['tz'] . "\n";
echo 'MySQL session TZ: ' . $row['sess_tz'] . "\n";

echo "\n--- Recent OTPs ---\n";
$otps = $pdo->query("SELECT email, otp, expires_at, verified, created_at, (expires_at > NOW()) as still_valid FROM password_otps ORDER BY id DESC LIMIT 5");
foreach ($otps->fetchAll() as $o) {
    echo "Email: {$o['email']} | OTP: {$o['otp']} | Expires: {$o['expires_at']} | Verified: {$o['verified']} | Still valid: {$o['still_valid']}\n";
}
echo '</pre>';
