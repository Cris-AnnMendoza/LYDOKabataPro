<?php
require_once __DIR__ . '/shared/config.php';

// Already logged in — redirect to correct dashboard
if (!empty($_SESSION['admin_id'])) { header('Location: admin2/dashboard.php'); exit; }
if (!empty($_SESSION['user_id']))  { header('Location: youth/dashboard.php');  exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $pdo = db();

        // ── Check admin table first ───────────────────────
        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Check status
            if ($admin['status'] === 'pending') {
                $error = 'Your admin account is pending approval. Please wait for the administrator to approve your account.';
            } elseif ($admin['status'] === 'rejected') {
                $error = 'Your account has been rejected. Please contact the LYDO office.';
            } else {
                // Admin login success
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin']    = [
                    'id'        => $admin['id'],
                    'full_name' => $admin['full_name'],
                    'email'     => $admin['email'],
                    'role'      => $admin['role'],
                    'barangay'  => $admin['barangay'],
                ];
                $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?')
                    ->execute([$admin['id']]);
                header('Location: admin2/dashboard.php');
                exit;
            }
        }

        // ── Check youth users table ───────────────────────
        $stmt2 = $pdo->prepare('SELECT * FROM youth_users WHERE email = ? LIMIT 1');
        $stmt2->execute([$email]);
        $user = $stmt2->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check status
            if ($user['status'] === 'pending') {
                $error = 'Your registration is pending approval. You will be notified once the admin reviews your account.';
            } elseif ($user['status'] === 'rejected') {
                $error = 'Your registration has been rejected. Please contact the LYDO office for assistance.';
            } else {
                // Youth login success
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                header('Location: youth/dashboard.php');
                exit;
            }
        }

        // ── Neither matched ───────────────────────────────
        if (!$admin && !$user) {
            $error = 'Account not found. Please register first.';
        } else {
            $error = 'Incorrect password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Login – LYDO Sta. Cruz, Laguna</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;min-height:100vh;display:grid;grid-template-columns:1fr 1fr;background:#f8fafc}

/* ── LEFT PANEL ── */
.left{background:linear-gradient(160deg,#0d3b6e 0%,#1565c0 55%,#1b5e20 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 40px;position:relative;overflow:hidden}
.left::before{content:'';position:absolute;width:420px;height:420px;border-radius:50%;background:rgba(255,255,255,.05);top:-120px;right:-100px}
.left::after{content:'';position:absolute;width:260px;height:260px;border-radius:50%;background:rgba(255,255,255,.05);bottom:-70px;left:-60px}
.left-content{position:relative;z-index:1;text-align:center}
.logo-wrap{width:110px;height:110px;border-radius:28px;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:3rem;color:#fff;margin:0 auto 22px;box-shadow:0 8px 32px rgba(0,0,0,.2)}
.left h1{font-size:2.6rem;font-weight:900;color:#fff;letter-spacing:.04em;margin-bottom:6px}
.left .org{font-size:.9rem;color:rgba(255,255,255,.7);margin-bottom:20px}
.divider{width:40px;height:3px;background:rgba(255,255,255,.4);border-radius:2px;margin:0 auto 14px}
.loc{color:rgba(255,255,255,.8);font-size:.88rem;margin-bottom:10px}
.tagline{font-size:.82rem;color:rgba(255,255,255,.55);font-style:italic;max-width:280px;margin:0 auto 28px;line-height:1.6}
.info-pills{display:flex;flex-wrap:wrap;gap:8px;justify-content:center}
.pill{padding:5px 14px;border-radius:50px;font-size:.75rem;font-weight:600;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.1);color:rgba(255,255,255,.85)}
.left-foot{position:absolute;bottom:18px;font-size:.72rem;color:rgba(255,255,255,.3);z-index:1}

/* ── RIGHT PANEL ── */
.right{display:flex;align-items:center;justify-content:center;background:#fff;padding:48px 40px;position:relative}
.back-link{position:absolute;top:24px;left:28px;display:flex;align-items:center;gap:7px;font-size:.85rem;color:#64748b;font-weight:500;text-decoration:none;transition:.2s}
.back-link:hover{color:#1565c0}
.form-box{width:100%;max-width:400px}
.form-icon{width:62px;height:62px;border-radius:16px;background:linear-gradient(135deg,#0d3b6e,#1565c0);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;margin:0 auto 18px}
.form-box h2{font-size:1.8rem;font-weight:800;text-align:center;margin-bottom:6px;color:#1e293b}
.form-sub{text-align:center;color:#64748b;font-size:.88rem;margin-bottom:28px;line-height:1.5}

/* Role hint */
.role-hint{display:flex;gap:10px;margin-bottom:24px}
.rh{flex:1;padding:10px 12px;border-radius:10px;border:1.5px solid #e2e8f0;text-align:center;cursor:pointer;transition:.2s;background:#f8fafc}
.rh:hover{border-color:#1565c0;background:#e3f2fd}
.rh i{display:block;font-size:1.2rem;margin-bottom:4px;color:#94a3b8}
.rh span{font-size:.75rem;font-weight:600;color:#475569}

.alert{padding:12px 16px;border-radius:10px;font-size:.88rem;font-weight:500;margin-bottom:18px;display:flex;align-items:center;gap:8px;background:#ffebee;color:#c62828;border:1px solid rgba(198,40,40,.2)}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:18px}
.fg label{font-size:.85rem;font-weight:600;color:#1e293b}
.iw{position:relative;display:flex;align-items:center}
.iw i.ico{position:absolute;left:13px;color:#94a3b8;font-size:.88rem;pointer-events:none}
.iw input{width:100%;padding:12px 42px 12px 38px;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:.92rem;color:#1e293b;background:#f8fafc;outline:none;transition:.2s}
.iw input:focus{border-color:#1e88e5;box-shadow:0 0 0 3px rgba(30,136,229,.12);background:#fff}
.toggle-pw{position:absolute;right:11px;background:none;border:none;cursor:pointer;color:#94a3b8;font-size:.88rem;padding:4px;transition:.2s}
.toggle-pw:hover{color:#1565c0}
.opts{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:8px}
.chk-label{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.85rem;color:#475569;user-select:none}
.chk-label input{width:16px;height:16px;accent-color:#1565c0;cursor:pointer}
.forgot{font-size:.85rem;color:#1565c0;font-weight:600;text-decoration:none}
.forgot:hover{text-decoration:underline}
.btn-login{width:100%;padding:13px;background:linear-gradient(135deg,#0d3b6e,#1565c0);color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:.95rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;transition:.2s;box-shadow:0 4px 14px rgba(21,101,192,.35)}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(21,101,192,.45)}
.divider-or{display:flex;align-items:center;gap:12px;margin:20px 0;color:#94a3b8;font-size:.85rem}
.divider-or::before,.divider-or::after{content:'';flex:1;height:1px;background:#e2e8f0}
.register-link{text-align:center;font-size:.88rem;color:#475569}
.register-link a{color:#1565c0;font-weight:700;text-decoration:none}
.register-link a:hover{text-decoration:underline}

@media(max-width:768px){body{grid-template-columns:1fr}.left{display:none}.right{padding:32px 24px}}
</style>
</head>
<body>

<!-- LEFT -->
<div class="left">
  <div class="left-content">
    <div class="logo-wrap"><i class="fas fa-seedling"></i></div>
    <h1>LYDO</h1>
    <p class="org">Local Youth Development Office</p>
    <div class="divider"></div>
    <p class="loc"><i class="fas fa-map-marker-alt" style="color:#a5d6a7;margin-right:5px"></i>Sta. Cruz, Laguna</p>
    <p class="tagline">"Empowering the Youth of Sta. Cruz Laguna"</p>
    <div class="info-pills">
      <span class="pill"><i class="fas fa-users"></i> Youth Members</span>
      <span class="pill"><i class="fas fa-graduation-cap"></i> Programs</span>
      <span class="pill"><i class="fas fa-calendar"></i> Events</span>
      <span class="pill"><i class="fas fa-shield-alt"></i> Admin Portal</span>
    </div>
  </div>
  <p class="left-foot">© 2026 Municipal Government of Sta. Cruz, Laguna</p>
</div>

<!-- RIGHT -->
<div class="right">
  <a href="http://localhost/lydo-system/index.html" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>

  <div class="form-box">
    <div class="form-icon"><i class="fas fa-sign-in-alt"></i></div>
    <h2>Welcome Back!</h2>
    <p class="form-sub">One login for everyone — youth members and administrators.</p>

    <?php if ($error): ?>
      <div class="alert"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="fg">
        <label for="email">Email Address</label>
        <div class="iw">
          <i class="fas fa-envelope ico"></i>
          <input type="email" id="email" name="email" placeholder="Enter your email" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email"/>
        </div>
      </div>

      <div class="fg">
        <label for="password">Password</label>
        <div class="iw">
          <i class="fas fa-lock ico"></i>
          <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password"/>
          <button type="button" class="toggle-pw" onclick="togglePw()">
            <i class="fas fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <div class="opts">
        <label class="chk-label">
          <input type="checkbox" name="remember"/> Remember Me
        </label>
        <a href="#" class="forgot">Forgot Password?</a>
      </div>

      <button type="submit" class="btn-login">
        <i class="fas fa-sign-in-alt"></i> Login
      </button>
    </form>

    <div class="divider-or">or</div>
    <p class="register-link">
      New youth member? <a href="http://localhost/lydo-system/index.html#get-started">Register here</a>
    </p>
  </div>
</div>

<script>
function togglePw() {
  const inp = document.getElementById('password');
  const ico = document.getElementById('eyeIcon');
  if (inp.type === 'password') { inp.type = 'text'; ico.className = 'fas fa-eye-slash'; }
  else { inp.type = 'password'; ico.className = 'fas fa-eye'; }
}
</script>
</body>
</html>
