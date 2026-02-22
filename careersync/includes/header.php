<?php
// includes/header.php – Shared HTML Head + Navbar
// Usage: include with $pageTitle and $currentUser already set

$pageTitle = $pageTitle ?? 'CareerSync';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> – CareerSync</title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
  <script>
    // Apply theme immediately to prevent flash
    (function(){var t=localStorage.getItem('careersync_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();
  </script>
</head>
<body>
<nav class="navbar">
  <a class="navbar-brand" href="<?= APP_URL ?>">
    <!-- <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.67 13.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.58 2.85h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
    </svg> -->
    Career<span>Sync</span>
  </a>
  <ul class="navbar-nav">
    <?php if ($user): ?>
      <li>
        <a href="<?= APP_URL ?>/<?= $user['role'] ?>/dashboard.php">
          <?= e($user['name']) ?>
        </a>
      </li>
      <li>
        <a href="<?= APP_URL ?>/notifications.php" style="position:relative">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span id="notif-badge" style="position:absolute;top:-4px;right:-4px;background:var(--danger);color:#fff;border-radius:50%;width:16px;height:16px;font-size:10px;display:none;align-items:center;justify-content:center;font-weight:700"></span>
        </a>
      </li>
      <li><a href="<?= APP_URL ?>/logout.php">Logout</a></li>
    <?php else: ?>
      <li><a href="<?= APP_URL ?>/login.php">Login</a></li>
      <li><a href="<?= APP_URL ?>/register.php">Register</a></li>
    <?php endif; ?>
    <li>
      <button class="theme-toggle" onclick="toggleTheme()">
        <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 7a5 5 0 1 0 0 10A5 5 0 0 0 12 7zm0-5a1 1 0 0 1 1 1v2a1 1 0 0 1-2 0V3a1 1 0 0 1 1-1zm0 16a1 1 0 0 1 1 1v2a1 1 0 0 1-2 0v-2a1 1 0 0 1 1-1zm9-9h2a1 1 0 0 1 0 2h-2a1 1 0 0 1 0-2zM1 12H3a1 1 0 0 1 0 2H1a1 1 0 0 1 0-2z"/></svg>
        Dark
      </button>
    </li>
  </ul>
</nav>
