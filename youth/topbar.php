<?php
$current = basename($_SERVER['PHP_SELF']);
$titles  = [
    'dashboard.php'    => 'Dashboard',
    'assistance.php'   => 'Assistance Request',
    'accreditation.php'=> 'Accreditation',
    'profile.php'      => 'My Profile',
    'notifications.php'=> 'Notifications',
];
$pageTitle = $titles[$current] ?? 'Youth Portal';
?>
<header class="y-topbar">
  <button class="y-hamburger" id="yHamburger" aria-label="Open menu">
    <i class="fas fa-bars"></i>
  </button>
  <span class="y-topbar-title"><?= $pageTitle ?></span>
  <div class="y-topbar-right">
    <?php if ($notifCount > 0): ?>
    <a href="notifications.php" class="y-notif-btn" title="<?= $notifCount ?> unread notifications">
      <i class="fas fa-bell"></i>
      <span class="y-badge"><?= $notifCount ?></span>
    </a>
    <?php endif; ?>
    <div class="y-topbar-user">
      <div class="y-avatar sm"><?= $initials ?></div>
      <span><?= htmlspecialchars($user['first_name'] ?? '') ?></span>
    </div>
  </div>
</header>
