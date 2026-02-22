<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('admin');

$db  = getDB();
$user = currentUser();

// Handle new drive creation
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_drive'])) {
    verifyCsrf();

    $company     = trim($_POST['company_name'] ?? '');
    $jobRole     = trim($_POST['job_role'] ?? '');
    $desc        = trim($_POST['description'] ?? '');
    $minCgpa     = (float)($_POST['min_cgpa'] ?? 0);
    $maxBl       = (int)($_POST['max_backlogs'] ?? 0);
    $branches    = $_POST['allowed_branches'] ?? [];
    $skills      = array_filter(array_map('trim', explode(',', $_POST['required_skills'] ?? '')));
    $package     = (float)($_POST['package_lpa'] ?? 0);
    $driveDate   = $_POST['drive_date'] ?? '';
    $regDeadline = $_POST['registration_deadline'] ?? '';
    $venue       = trim($_POST['venue'] ?? '');

    if (!$company || !$jobRole || !$driveDate || empty($branches)) {
        $error = 'Company name, role, date, and at least one branch are required.';
    } else {
        $stmt = $db->prepare("
            INSERT INTO drives (company_name, job_role, description, min_cgpa, max_backlogs, allowed_branches, required_skills, package_lpa, drive_date, registration_deadline, venue, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $company, $jobRole, $desc, $minCgpa, $maxBl,
            json_encode($branches), json_encode(array_values($skills)),
            $package ?: null, $driveDate,
            $regDeadline ?: null, $venue,
            $user['id']
        ]);
        $newDriveId = (int)$db->lastInsertId();
        $success = "Drive for $company created successfully!";
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verifyCsrf();
    $appId  = (int)$_POST['app_id'];
    $status = $_POST['status'] ?? '';
    $allowed = ['applied','aptitude','aptitude_cleared','interview_scheduled','selected','rejected','on_hold'];
    if (in_array($status, $allowed, true)) {
        $db->prepare("UPDATE applications SET status=? WHERE id=?")->execute([$status, $appId]);
        $success = 'Application status updated.';
    }
}

// Criteria query: fetch eligible students for a drive
$eligibleStudents = [];
$selectedDrive    = null;
$eligibleCount    = 0;

if (isset($_GET['drive_id'])) {
    $driveId = (int)$_GET['drive_id'];
    $selectedDrive = $db->prepare("SELECT * FROM drives WHERE id=?");
    $selectedDrive->execute([$driveId]);
    $selectedDrive = $selectedDrive->fetch();

    if ($selectedDrive) {
        $branches = json_decode($selectedDrive['allowed_branches'], true) ?: [];
        $placeholders = rtrim(str_repeat('?,', count($branches)), ',');

        $stmt = $db->prepare("
            SELECT s.*, u.name, u.email,
                   GROUP_CONCAT(ss.skill_name ORDER BY ss.skill_name SEPARATOR ', ') AS skills
            FROM students s
            JOIN users u ON u.id = s.user_id
            LEFT JOIN student_skills ss ON ss.student_id = s.id
            WHERE s.cgpa >= ?
              AND s.backlogs <= ?
              AND s.branch IN ($placeholders)
            GROUP BY s.id
            ORDER BY s.cgpa DESC
        ");
        $params = array_merge([$selectedDrive['min_cgpa'], $selectedDrive['max_backlogs']], $branches);
        $stmt->execute($params);
        $eligibleStudents = $stmt->fetchAll();
        $eligibleCount    = count($eligibleStudents);
    }
}

// All drives list
$allDrives = $db->query("SELECT * FROM drives ORDER BY drive_date DESC")->fetchAll();

$pageTitle = 'Criteria Engine';
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Criteria Engine</h1>
      <p>Create placement drives and dynamically query eligible students</p>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success" data-autohide>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="20 6 9 17 4 12"/></svg>
      <?= e($success) ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger" data-autohide>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
      <?= e($error) ?>
    </div>
    <?php endif; ?>

    <div class="grid-2">
      <!-- Create Drive Form -->
      <div class="card">
        <div class="card-header"><h3 class="card-title">Create Placement Drive</h3></div>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="create_drive" value="1">

          <div class="form-group">
            <label class="form-label">Company Name *</label>
            <input type="text" name="company_name" class="form-control" placeholder="e.g. TCS Digital" required>
          </div>
          <div class="form-group">
            <label class="form-label">Job Role *</label>
            <input type="text" name="job_role" class="form-control" placeholder="e.g. Software Engineer" required>
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Brief job description..."></textarea>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Minimum CGPA</label>
              <input type="number" name="min_cgpa" class="form-control" min="0" max="10" step="0.1" value="6.5">
            </div>
            <div class="form-group">
              <label class="form-label">Max Backlogs</label>
              <input type="number" name="max_backlogs" class="form-control" min="0" max="20" value="2">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Allowed Branches *</label>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;padding:0.6rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--bg-secondary)">
              <?php
              $allBranches = ['Computer Science','Information Technology','Electronics','Electrical','Mechanical','Civil','Chemical','Biotechnology'];
              foreach ($allBranches as $br):
              ?>
              <label style="display:flex;align-items:center;gap:0.3rem;font-size:0.85rem;cursor:pointer">
                <input type="checkbox" name="allowed_branches[]" value="<?= e($br) ?>"> <?= e($br) ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Required Skills (comma-separated)</label>
            <input type="text" name="required_skills" class="form-control" placeholder="Python, SQL, Java">
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Package (LPA)</label>
              <input type="number" name="package_lpa" class="form-control" min="0" step="0.1" value="6">
            </div>
            <div class="form-group">
              <label class="form-label">Drive Date *</label>
              <input type="date" name="drive_date" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Registration Deadline</label>
              <input type="date" name="registration_deadline" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label">Venue</label>
              <input type="text" name="venue" class="form-control" placeholder="Seminar Hall A">
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Create Drive</button>
        </form>
      </div>

      <!-- Check Eligibility -->
      <div class="card">
        <div class="card-header"><h3 class="card-title">Check Eligible Students</h3></div>
        <form method="GET" style="margin-bottom:1.25rem">
          <div class="form-group">
            <label class="form-label">Select Drive</label>
            <select name="drive_id" class="form-control" onchange="this.form.submit()">
              <option value="">-- Select a Drive --</option>
              <?php foreach ($allDrives as $d): ?>
              <option value="<?= $d['id'] ?>" <?= (isset($_GET['drive_id']) && $_GET['drive_id'] == $d['id']) ? 'selected' : '' ?>>
                <?= e($d['company_name']) ?> – <?= e($d['job_role']) ?> (<?= $d['drive_date'] ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>

        <?php if ($selectedDrive): ?>
        <div class="alert alert-info" style="margin-bottom:1rem">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <strong><?= $eligibleCount ?> students eligible</strong> for <?= e($selectedDrive['company_name']) ?>
          (Min CGPA: <?= $selectedDrive['min_cgpa'] ?>, Max Backlogs: <?= $selectedDrive['max_backlogs'] ?>)
        </div>

        <?php if ($eligibleStudents): ?>
        <form method="POST" action="<?= APP_URL ?>/api/notify.php">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="drive_id" value="<?= $selectedDrive['id'] ?>">
          <button type="submit" class="btn btn-success btn-block mb-2">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            Notify All <?= $eligibleCount ?> Eligible Students
          </button>
        </form>

        <div class="table-wrapper" style="max-height:350px;overflow-y:auto">
          <table>
            <thead><tr><th>Name</th><th>Branch</th><th>CGPA</th><th>Backlogs</th><th>Skills</th></tr></thead>
            <tbody>
            <?php foreach ($eligibleStudents as $s): ?>
            <tr>
              <td>
                <strong><?= e($s['name']) ?></strong><br>
                <small class="text-muted"><?= e($s['email']) ?></small>
              </td>
              <td><?= e($s['branch']) ?></td>
              <td><span class="badge badge-success"><?= $s['cgpa'] ?></span></td>
              <td><?= $s['backlogs'] ?></td>
              <td style="font-size:0.8rem"><?= e($s['skills'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
        <?php elseif (isset($_GET['drive_id'])): ?>
        <p class="text-muted">Select a drive to see eligible students.</p>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
