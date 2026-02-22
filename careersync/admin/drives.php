<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('admin');

$db   = getDB();
$user = currentUser();
$success = $error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_drive_status'])) {
    verifyCsrf();
    $driveId = (int)$_POST['drive_id'];
    $status  = $_POST['status'] ?? '';
    $allowed = ['upcoming','active','completed','cancelled'];
    if (in_array($status, $allowed, true)) {
        $db->prepare("UPDATE drives SET status=? WHERE id=?")->execute([$status, $driveId]);
        $success = 'Drive status updated.';
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_drive'])) {
    verifyCsrf();
    $driveId = (int)$_POST['drive_id'];
    $db->prepare("DELETE FROM drives WHERE id=?")->execute([$driveId]);
    $success = 'Drive deleted.';
}

$drives = $db->query("
    SELECT d.*,
           COUNT(DISTINCT a.id) AS app_count,
           SUM(a.status='selected') AS selected_count
    FROM drives d
    LEFT JOIN applications a ON a.drive_id = d.id
    GROUP BY d.id
    ORDER BY d.drive_date DESC
")->fetchAll();

$pageTitle = 'Manage Drives';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Placement Drives</h1>
      <p>Manage all placement drives and their status</p>
    </div>

    <?php if ($success): ?><div class="alert alert-success" data-autohide><?= e($success) ?></div><?php endif; ?>

    <div style="display:flex;justify-content:flex-end;margin-bottom:1.25rem">
      <a href="<?= APP_URL ?>/admin/criteria.php" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Create New Drive
      </a>
    </div>

    <div class="card">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>Company</th><th>Role</th><th>Package</th><th>Criteria</th><th>Drive Date</th><th>Applications</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php foreach ($drives as $d):
            $branches = json_decode($d['allowed_branches'], true) ?: [];
          ?>
          <tr>
            <td><strong><?= e($d['company_name']) ?></strong></td>
            <td><?= e($d['job_role']) ?></td>
            <td><?= $d['package_lpa'] ? $d['package_lpa'].' LPA' : '—' ?></td>
            <td style="font-size:0.82rem">
              CGPA ≥ <?= $d['min_cgpa'] ?> &nbsp;·&nbsp; Backlogs ≤ <?= $d['max_backlogs'] ?><br>
              <span class="text-muted"><?= e(implode(', ', array_slice($branches,0,2))) ?><?= count($branches)>2?'...':'' ?></span>
            </td>
            <td>
              <?= date('d M Y', strtotime($d['drive_date'])) ?>
              <?php if ($d['venue']): ?><br><small class="text-muted">📍 <?= e($d['venue']) ?></small><?php endif; ?>
            </td>
            <td>
              <span class="badge badge-info"><?= $d['app_count'] ?> applied</span><br>
              <?php if ($d['selected_count']): ?><span class="badge badge-success"><?= $d['selected_count'] ?> selected</span><?php endif; ?>
            </td>
            <td>
              <span class="badge badge-<?= ['upcoming'=>'warning','active'=>'success','completed'=>'info','cancelled'=>'danger'][$d['status']] ?? 'secondary' ?>">
                <?= ucfirst($d['status']) ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:0.35rem;flex-wrap:wrap">
                <a href="<?= APP_URL ?>/admin/criteria.php?drive_id=<?= $d['id'] ?>" class="btn btn-sm btn-secondary">Eligible</a>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="update_drive_status" value="1">
                  <input type="hidden" name="drive_id" value="<?= $d['id'] ?>">
                  <select name="status" onchange="this.form.submit()" class="form-control" style="padding:0.3rem;font-size:0.8rem;height:auto">
                    <option value="">Change Status</option>
                    <?php foreach (['upcoming','active','completed','cancelled'] as $st): ?>
                    <option value="<?= $st ?>" <?= $d['status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this drive?')">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="delete_drive" value="1">
                  <input type="hidden" name="drive_id" value="<?= $d['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$drives): ?>
          <tr><td colspan="8" class="text-center text-muted" style="padding:2rem">No drives yet. <a href="<?= APP_URL ?>/admin/criteria.php">Create one</a></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
