<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('student');

$db   = getDB();
$user = currentUser();

$student = $db->prepare("SELECT * FROM students WHERE user_id=?");
$student->execute([$user['id']]);
$student = $student->fetch();
$sId = $student ? $student['id'] : null;

$success = $error = '';

// Apply to drive
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_drive']) && $sId) {
    verifyCsrf();
    $driveId = (int)$_POST['drive_id'];
    try {
        $db->prepare("INSERT INTO applications (student_id, drive_id) VALUES (?,?)")->execute([$sId, $driveId]);

        // Send notification
        $drive = $db->prepare("SELECT company_name, job_role FROM drives WHERE id=?")->execute([$driveId]);
        $drive = $db->prepare("SELECT company_name, job_role FROM drives WHERE id=?");
        $drive->execute([$driveId]); $drive = $drive->fetch();

        $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)")
           ->execute([$user['id'],
               'Application Submitted',
               "You applied to {$drive['company_name']} for {$drive['job_role']}.",
               'success'
           ]);
        $success = "Successfully applied to {$drive['company_name']}!";
    } catch (PDOException $e) {
        $error = 'You have already applied to this drive.';
    }
}

// Fetch all active drives
$allDrives = $db->query("
    SELECT d.*,
           (SELECT COUNT(*) FROM applications a WHERE a.drive_id = d.id) AS app_count
    FROM drives d
    WHERE d.status IN ('upcoming','active') AND d.drive_date >= CURDATE()
    ORDER BY d.drive_date ASC
")->fetchAll();

// Get applied drive IDs
$appliedIds = [];
if ($sId) {
    $stmt = $db->prepare("SELECT drive_id FROM applications WHERE student_id=?");
    $stmt->execute([$sId]);
    $appliedIds = array_column($stmt->fetchAll(), 'drive_id');
}

// Check eligibility for each drive
function isEligible(array $student, array $drive): bool {
    $branches = json_decode($drive['allowed_branches'], true) ?: [];
    return $student['cgpa'] >= $drive['min_cgpa']
        && $student['backlogs'] <= $drive['max_backlogs']
        && in_array($student['branch'], $branches, true);
}

$pageTitle = 'Live Drives';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Live Placement Drives</h1>
      <p>Browse all active drives. Eligible ones are highlighted.</p>
    </div>

    <?php if ($success): ?><div class="alert alert-success" data-autohide><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" data-autohide><?= e($error) ?></div><?php endif; ?>

    <?php if (!$student || !$student['branch']): ?>
    <div class="alert alert-warning mb-3">
      <a href="<?= APP_URL ?>/student/profile.php"><strong>Complete your profile</strong></a> to see eligibility for each drive.
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card mb-3" style="padding:1rem">
      <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center">
        <span style="font-size:0.88rem;font-weight:500;color:var(--text-muted)">Filter:</span>
        <button class="btn btn-sm btn-secondary filter-btn active" data-filter="all">All Drives (<?= count($allDrives) ?>)</button>
        <button class="btn btn-sm btn-secondary filter-btn" data-filter="eligible">Eligible Only</button>
        <button class="btn btn-sm btn-secondary filter-btn" data-filter="applied">Applied</button>
      </div>
    </div>

    <div id="drives-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.25rem">
      <?php foreach ($allDrives as $d):
        $eligible = $student && $student['branch'] ? isEligible($student, $d) : null;
        $applied  = in_array($d['id'], $appliedIds, true);
        $requiredSkills = json_decode($d['required_skills'], true) ?: [];
        $allowedBranches = json_decode($d['allowed_branches'], true) ?: [];
      ?>
      <div class="drive-card card"
           data-eligible="<?= $eligible ? 'yes' : 'no' ?>"
           data-applied="<?= $applied ? 'yes' : 'no' ?>"
           style="border-left:4px solid <?= $eligible ? 'var(--success)' : ($eligible === null ? 'var(--border)' : 'var(--border)') ?>">

        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:0.75rem">
          <div>
            <h3 style="font-size:1rem;margin-bottom:0.2rem"><?= e($d['company_name']) ?></h3>
            <div style="font-size:0.85rem;color:var(--text-muted)"><?= e($d['job_role']) ?></div>
          </div>
          <div style="text-align:right">
            <?php if ($eligible === true): ?>
            <span class="badge badge-success">✓ Eligible</span>
            <?php elseif ($eligible === false): ?>
            <span class="badge badge-danger">Not Eligible</span>
            <?php endif; ?>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;font-size:0.83rem;margin-bottom:0.9rem">
          <div><span class="text-muted">Package:</span> <strong><?= $d['package_lpa'] ?> LPA</strong></div>
          <div><span class="text-muted">CGPA ≥:</span> <strong><?= $d['min_cgpa'] ?></strong></div>
          <div><span class="text-muted">Max Backlogs:</span> <strong><?= $d['max_backlogs'] ?></strong></div>
          <div><span class="text-muted">Applications:</span> <strong><?= $d['app_count'] ?></strong></div>
        </div>

        <div style="font-size:0.8rem;margin-bottom:0.7rem">
          <span class="text-muted">Drive Date:</span>
          <strong><?= date('d M Y', strtotime($d['drive_date'])) ?></strong>
          <?php if ($d['registration_deadline']): ?>
          | <span class="text-muted">Deadline:</span>
          <strong style="color:var(--warning)"><?= date('d M Y', strtotime($d['registration_deadline'])) ?></strong>
          <?php endif; ?>
        </div>

        <?php if ($requiredSkills): ?>
        <div style="margin-bottom:0.8rem;display:flex;flex-wrap:wrap;gap:0.3rem">
          <?php foreach ($requiredSkills as $sk): ?>
          <span class="badge <?= in_array($sk, $mySkillList ?? [], true) ? 'badge-success' : 'badge-secondary' ?>"><?= e($sk) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($d['venue']): ?>
        <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.8rem">📍 <?= e($d['venue']) ?></div>
        <?php endif; ?>

        <?php if ($applied): ?>
        <button class="btn btn-secondary btn-block" disabled>✓ Applied</button>
        <?php elseif ($eligible): ?>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="apply_drive" value="1">
          <input type="hidden" name="drive_id" value="<?= $d['id'] ?>">
          <button type="submit" class="btn btn-primary btn-block">Apply Now</button>
        </form>
        <?php else: ?>
        <button class="btn btn-secondary btn-block" disabled>Apply</button>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </main>
</div>

<?php
$mySkillList = $mySkillList ?? [];
$extraJs = <<<JS
<script>
document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active','btn-primary'));
    document.querySelectorAll('.filter-btn').forEach(b=>b.classList.add('btn-secondary'));
    this.classList.add('active','btn-primary');
    this.classList.remove('btn-secondary');
    const f = this.dataset.filter;
    document.querySelectorAll('.drive-card').forEach(card => {
      if (f === 'all') { card.style.display=''; }
      else if (f === 'eligible') { card.style.display = card.dataset.eligible === 'yes' ? '' : 'none'; }
      else if (f === 'applied')  { card.style.display = card.dataset.applied === 'yes' ? '' : 'none'; }
    });
  });
});
</script>
JS;
include __DIR__ . '/../includes/footer.php';
?>
