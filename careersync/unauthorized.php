<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Access Denied';
include __DIR__ . '/includes/header.php';
?>
<div style="min-height:calc(100vh - 62px);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:1rem;text-align:center;padding:2rem">
  <div style="font-size:4rem">🚫</div>
  <h1>Access Denied</h1>
  <p class="text-muted">You don't have permission to view this page.</p>
  <a href="<?= APP_URL ?>/index.php" class="btn btn-primary">Go to Home</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
