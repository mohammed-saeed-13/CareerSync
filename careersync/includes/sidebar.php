<?php
// includes/sidebar.php – Role-based sidebar
$user = currentUser();
$role = $user['role'] ?? '';
$base = APP_URL;

$initials = '';
if ($user) {
    $parts = explode(' ', $user['name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}

function sideLink(string $href, string $icon, string $label, string $current): string {
    $active = (basename($_SERVER['PHP_SELF']) === basename($href)) ? 'active' : '';
    return "<a href=\"$href\" class=\"sidebar-link $active\">$icon <span>$label</span></a>";
}

$icons = [
  'dash'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
  'user'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
  'file'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
  'drive'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
  'app'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
  'chart'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
  'people'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  'mail'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
  'calendar'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
  'settings'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
  'ai'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 0 2h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1 0-2h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/></svg>',
];
?>
<div class="sidebar">
  <div class="sidebar-header">
    <div class="avatar-circle"><?= e($initials) ?></div>
    <div class="user-name"><?= e($user['name'] ?? '') ?></div>
    <div class="user-role"><?= e(strtoupper($role)) ?></div>
  </div>
  <nav class="sidebar-menu">
    <?php if ($role === 'admin'): ?>
      <div class="sidebar-section-title">OVERVIEW</div>
      <?= sideLink("$base/admin/dashboard.php", $icons['dash'], 'Dashboard', '') ?>
      <?= sideLink("$base/admin/drives.php", $icons['drive'], 'Manage Drives', '') ?>
      <?= sideLink("$base/admin/criteria.php", $icons['settings'], 'Criteria Engine', '') ?>
      <?= sideLink("$base/admin/interviews.php", $icons['calendar'], 'Interview Scheduler', '') ?>
      <div class="sidebar-section-title">STUDENTS</div>
      <?= sideLink("$base/admin/students.php", $icons['people'], 'All Students', '') ?>
      <?= sideLink("$base/admin/applications.php", $icons['app'], 'Applications', '') ?>
      <div class="sidebar-section-title">ANALYTICS</div>
      <?= sideLink("$base/admin/analytics.php", $icons['chart'], 'Analytics', '') ?>
    <?php elseif ($role === 'student'): ?>
      <div class="sidebar-section-title">MY SPACE</div>
      <?= sideLink("$base/student/dashboard.php", $icons['dash'], 'Dashboard', '') ?>
      <?= sideLink("$base/student/profile.php", $icons['user'], 'My Profile', '') ?>
      <?= sideLink("$base/student/resume.php", $icons['file'], 'Resume & AI', '') ?>
      <div class="sidebar-section-title">PLACEMENT</div>
      <?= sideLink("$base/student/drives.php", $icons['drive'], 'Live Drives', '') ?>
      <?= sideLink("$base/student/applications.php", $icons['app'], 'My Applications', '') ?>
      <?= sideLink("$base/student/skill-gap.php", $icons['ai'], 'Skill Gap AI', '') ?>
      <div class="sidebar-section-title">CONNECT</div>
      <?= sideLink("$base/student/alumni.php", $icons['people'], 'Alumni Mentors', '') ?>
    <?php elseif ($role === 'alumni'): ?>
      <div class="sidebar-section-title">OVERVIEW</div>
      <?= sideLink("$base/alumni/dashboard.php", $icons['dash'], 'Dashboard', '') ?>
      <?= sideLink("$base/alumni/profile.php", $icons['user'], 'My Profile', '') ?>
      <div class="sidebar-section-title">GIVE BACK</div>
      <?= sideLink("$base/alumni/referrals.php", $icons['mail'], 'Job Referrals', '') ?>
      <?= sideLink("$base/alumni/mentorship.php", $icons['calendar'], 'Mentorship Slots', '') ?>
    <?php endif; ?>
  </nav>
</div>
