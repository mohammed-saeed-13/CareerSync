<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$db = getDB();
$user = currentUser();

// Mark all as read
$db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$user['id']]);

$notifications = $db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50");
$notifications->execute([$user['id']]);
$notifications = $notifications->fetchAll();

$pageTitle = 'Notifications';
include __DIR__ . '/includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Notifications</h1>
      <p>Stay updated with placement activities</p>
    </div>
    <?php if ($notifications): ?>
    <div style="display:flex;flex-direction:column;gap:0.75rem">
      <?php foreach ($notifications as $n):
        $typeMap = ['info'=>'info','success'=>'success','warning'=>'warning','danger'=>'danger'];
        $tc = $typeMap[$n['type']] ?? 'info';
      ?>
      <div class="card" style="border-left:4px solid var(--<?= $tc === 'info' ? 'accent' : $tc ?>)">
        <div style="display:flex;justify-content:space-between;align-items:start">
          <div>
            <strong><?= e($n['title']) ?></strong>
            <p style="font-size:0.88rem;color:var(--text-secondary);margin-top:0.25rem"><?= e($n['message']) ?></p>
          </div>
          <span style="font-size:0.78rem;color:var(--text-muted);white-space:nowrap;margin-left:1rem">
            <?= date('d M, h:i A', strtotime($n['created_at'])) ?>
          </span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card flex-center" style="flex-direction:column;min-height:200px;gap:1rem">
      <div style="font-size:2.5rem">🔔</div>
      <p class="text-muted">No notifications yet.</p>
    </div>
    <?php endif; ?>
  </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
