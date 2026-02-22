<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('admin');

$db = getDB();
$success = $error = '';

// Update application status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verifyCsrf();
    $appId  = (int)$_POST['app_id'];
    $status = $_POST['status'] ?? '';
    $notes  = trim($_POST['notes'] ?? '');
    $allowed = ['applied','aptitude','aptitude_cleared','interview_scheduled','selected','rejected','on_hold'];
    if (in_array($status, $allowed, true)) {
        $db->prepare("UPDATE applications SET status=?, notes=? WHERE id=?")->execute([$status, $notes ?: null, $appId]);

        // Notify student
        $app = $db->prepare("SELECT a.*, u.id as user_id, d.company_name FROM applications a JOIN students s ON s.id=a.student_id JOIN users u ON u.id=s.user_id JOIN drives d ON d.id=a.drive_id WHERE a.id=?");
        $app->execute([$appId]);
        $app = $app->fetch();
        if ($app) {
            $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)")
               ->execute([$app['user_id'],
                   'Application Update',
                   "Your application for {$app['company_name']} has been updated to: " . ucwords(str_replace('_',' ',$status)),
                   $status === 'selected' ? 'success' : ($status === 'rejected' ? 'danger' : 'info')
               ]);
        }
        $success = 'Application status updated and student notified.';
    }
}

// Filters
$filterDrive  = (int)($_GET['drive_id'] ?? 0);
$filterStatus = $_GET['status'] ?? '';

$where  = '1=1';
$params = [];
if ($filterDrive)  { $where .= ' AND a.drive_id=?';    $params[] = $filterDrive; }
if ($filterStatus) { $where .= ' AND a.status=?';       $params[] = $filterStatus; }

$stmt = $db->prepare("
    SELECT a.*, u.name AS student_name, u.email,
           s.cgpa, s.backlogs, s.branch,
           d.company_name, d.job_role, d.drive_date
    FROM applications a
    JOIN students s ON s.id = a.student_id
    JOIN users u ON u.id = s.user_id
    JOIN drives d ON d.id = a.drive_id
    WHERE $where
    ORDER BY a.applied_at DESC
");
$stmt->execute($params);
$applications = $stmt->fetchAll();

$drives = $db->query("SELECT id, company_name, job_role FROM drives ORDER BY drive_date DESC")->fetchAll();

$statusColors = [
    'applied'=>'info','aptitude'=>'warning','aptitude_cleared'=>'purple',
    'interview_scheduled'=>'warning','selected'=>'success','rejected'=>'danger','on_hold'=>'secondary'
];

$pageTitle = 'Applications';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Application Management</h1>
      <p><?= count($applications) ?> applications found</p>
    </div>

    <?php if ($success): ?><div class="alert alert-success" data-autohide><?= e($success) ?></div><?php endif; ?>

    <!-- Filters -->
    <div class="card mb-3" style="padding:1rem">
      <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0;flex:1;min-width:200px">
          <label class="form-label" style="font-size:0.8rem">Drive</label>
          <select name="drive_id" class="form-control">
            <option value="">All Drives</option>
            <?php foreach ($drives as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $filterDrive==$d['id']?'selected':'' ?>><?= e($d['company_name']) ?> – <?= e($d['job_role']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0;min-width:150px">
          <label class="form-label" style="font-size:0.8rem">Status</label>
          <select name="status" class="form-control">
            <option value="">All Status</option>
            <?php foreach (array_keys($statusColors) as $st): ?>
            <option value="<?= $st ?>" <?= $filterStatus===$st?'selected':'' ?>><?= ucwords(str_replace('_',' ',$st)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="align-self:flex-end">Filter</button>
        <a href="<?= APP_URL ?>/admin/applications.php" class="btn btn-secondary" style="align-self:flex-end">Reset</a>
      </form>
    </div>

    <div class="card">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>Student</th><th>Drive</th><th>Profile</th><th>Status</th><th>Applied</th><th>Update Status</th></tr>
          </thead>
          <tbody>
          <?php foreach ($applications as $a): ?>
          <tr>
            <td>
              <strong><?= e($a['student_name']) ?></strong><br>
              <small class="text-muted"><?= e($a['email']) ?></small>
            </td>
            <td>
              <strong><?= e($a['company_name']) ?></strong><br>
              <small class="text-muted"><?= e($a['job_role']) ?></small><br>
              <small class="text-muted"><?= date('d M Y', strtotime($a['drive_date'])) ?></small>
            </td>
            <td style="font-size:0.82rem">
              CGPA: <strong><?= $a['cgpa'] ?></strong><br>
              Backlogs: <?= $a['backlogs'] ?><br>
              <span class="text-muted"><?= e($a['branch']) ?></span>
            </td>
            <td>
              <span class="badge badge-<?= $statusColors[$a['status']] ?? 'secondary' ?>">
                <?= ucwords(str_replace('_',' ',$a['status'])) ?>
              </span>
              <?php if ($a['notes']): ?>
              <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.2rem"><?= e(mb_substr($a['notes'],0,50)) ?></div>
              <?php endif; ?>
            </td>
            <td class="text-muted" style="font-size:0.85rem"><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
            <td>
              <form method="POST" style="min-width:180px">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                <select name="status" class="form-control" style="margin-bottom:0.4rem;font-size:0.82rem">
                  <?php foreach (array_keys($statusColors) as $st): ?>
                  <option value="<?= $st ?>" <?= $a['status']===$st?'selected':'' ?>><?= ucwords(str_replace('_',' ',$st)) ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="text" name="notes" class="form-control" placeholder="Add note..." style="margin-bottom:0.4rem;font-size:0.82rem" value="<?= e($a['notes'] ?? '') ?>">
                <button type="submit" class="btn btn-sm btn-primary btn-block">Update</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$applications): ?>
          <tr><td colspan="6" class="text-center text-muted" style="padding:2rem">No applications found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
