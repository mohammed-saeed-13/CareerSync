<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('admin');

$db = getDB();

// Handle placement status update
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_placement'])) {
    verifyCsrf();
    $studentId = (int)$_POST['student_id'];
    $status    = $_POST['placement_status'] ?? '';
    $company   = trim($_POST['placed_company'] ?? '');
    $package   = (float)($_POST['placed_package'] ?? 0);

    $allowed = ['not_placed','placed','in_process'];
    if (in_array($status, $allowed, true)) {
        $db->prepare("UPDATE students SET placement_status=?, placed_company=?, placed_package=? WHERE id=?")
           ->execute([$status, $company ?: null, $package ?: null, $studentId]);

        // Log skill if placed
        if ($status === 'placed') {
            $skills = $db->prepare("SELECT skill_name FROM student_skills WHERE student_id=?");
            $skills->execute([$studentId]);
            foreach ($skills->fetchAll() as $sk) {
                $db->prepare("INSERT IGNORE INTO skill_logs (student_id, skill_name, was_placed) VALUES (?,?,1)")
                   ->execute([$studentId, $sk['skill_name']]);
            }
        }
        $success = 'Placement status updated.';
    }
}

// Filters
$filterBranch = $_GET['branch'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');

$where = '1=1';
$params = [];
if ($filterBranch) { $where .= ' AND s.branch=?'; $params[] = $filterBranch; }
if ($filterStatus) { $where .= ' AND s.placement_status=?'; $params[] = $filterStatus; }
if ($search) { $where .= ' AND (u.name LIKE ? OR u.email LIKE ? OR s.roll_number LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $db->prepare("
    SELECT s.*, u.name, u.email,
           GROUP_CONCAT(ss.skill_name ORDER BY ss.skill_name SEPARATOR ', ') AS skills
    FROM students s
    JOIN users u ON u.id = s.user_id
    LEFT JOIN student_skills ss ON ss.student_id = s.id
    WHERE $where
    GROUP BY s.id
    ORDER BY s.cgpa DESC
");
$stmt->execute($params);
$students = $stmt->fetchAll();

$branches = $db->query("SELECT DISTINCT branch FROM students WHERE branch != '' ORDER BY branch")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Manage Students';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Student Management</h1>
      <p><?= count($students) ?> students found</p>
    </div>

    <?php if ($success): ?><div class="alert alert-success" data-autohide><?= e($success) ?></div><?php endif; ?>

    <!-- Filters -->
    <div class="card mb-3" style="padding:1rem">
      <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0;flex:1;min-width:160px">
          <label class="form-label" style="font-size:0.8rem">Search</label>
          <input type="text" name="q" class="form-control" placeholder="Name, email, roll..." value="<?= e($search) ?>">
        </div>
        <div class="form-group" style="margin:0;min-width:160px">
          <label class="form-label" style="font-size:0.8rem">Branch</label>
          <select name="branch" class="form-control">
            <option value="">All Branches</option>
            <?php foreach ($branches as $b): ?>
            <option value="<?= e($b) ?>" <?= $filterBranch===$b?'selected':'' ?>><?= e($b) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0;min-width:140px">
          <label class="form-label" style="font-size:0.8rem">Status</label>
          <select name="status" class="form-control">
            <option value="">All Status</option>
            <option value="not_placed" <?= $filterStatus==='not_placed'?'selected':'' ?>>Not Placed</option>
            <option value="in_process" <?= $filterStatus==='in_process'?'selected':'' ?>>In Process</option>
            <option value="placed" <?= $filterStatus==='placed'?'selected':'' ?>>Placed</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="align-self:flex-end">Filter</button>
        <a href="<?= APP_URL ?>/admin/students.php" class="btn btn-secondary" style="align-self:flex-end">Reset</a>
      </form>
    </div>

    <div class="card">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Student</th>
              <th>Branch</th>
              <th>CGPA</th>
              <th>Backlogs</th>
              <th>Skills</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($students as $s): ?>
          <tr>
            <td>
              <strong><?= e($s['name']) ?></strong><br>
              <small class="text-muted"><?= e($s['email']) ?></small><br>
              <?php if ($s['roll_number']): ?><small class="text-muted"><?= e($s['roll_number']) ?></small><?php endif; ?>
            </td>
            <td><?= e($s['branch'] ?: '—') ?></td>
            <td>
              <span class="badge badge-<?= $s['cgpa']>=8?'success':($s['cgpa']>=6?'warning':'danger') ?>">
                <?= $s['cgpa'] ?>
              </span>
            </td>
            <td><?= $s['backlogs'] ?></td>
            <td style="font-size:0.8rem;max-width:180px"><?= e($s['skills'] ?: '—') ?></td>
            <td>
              <?php
              $psBadge = ['not_placed'=>'secondary','in_process'=>'warning','placed'=>'success'];
              $psLabel = ['not_placed'=>'Not Placed','in_process'=>'In Process','placed'=>'Placed ✓'];
              ?>
              <span class="badge badge-<?= $psBadge[$s['placement_status']] ?? 'secondary' ?>">
                <?= $psLabel[$s['placement_status']] ?? ucfirst($s['placement_status']) ?>
              </span>
              <?php if ($s['placed_company']): ?>
              <div style="font-size:0.78rem;color:var(--success);margin-top:0.2rem">@ <?= e($s['placed_company']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <button class="btn btn-sm btn-secondary"
                      onclick="document.getElementById('update-<?= $s['id'] ?>').classList.toggle('hidden')">
                Update
              </button>
              <div id="update-<?= $s['id'] ?>" class="hidden" style="margin-top:0.5rem;padding:0.75rem;background:var(--bg-hover);border-radius:var(--radius-sm)">
                <form method="POST">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="update_placement" value="1">
                  <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                  <div class="form-group" style="margin-bottom:0.5rem">
                    <select name="placement_status" class="form-control">
                      <option value="not_placed" <?= $s['placement_status']==='not_placed'?'selected':'' ?>>Not Placed</option>
                      <option value="in_process" <?= $s['placement_status']==='in_process'?'selected':'' ?>>In Process</option>
                      <option value="placed" <?= $s['placement_status']==='placed'?'selected':'' ?>>Placed</option>
                    </select>
                  </div>
                  <div class="form-group" style="margin-bottom:0.5rem">
                    <input type="text" name="placed_company" class="form-control" placeholder="Company" value="<?= e($s['placed_company'] ?? '') ?>">
                  </div>
                  <div class="form-group" style="margin-bottom:0.5rem">
                    <input type="number" name="placed_package" class="form-control" placeholder="Package LPA" step="0.1" value="<?= e($s['placed_package'] ?? '') ?>">
                  </div>
                  <button type="submit" class="btn btn-success btn-sm btn-block">Save</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$students): ?>
          <tr><td colspan="7" class="text-center text-muted" style="padding:2rem">No students found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
