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

$applications = [];
if ($sId) {
    $stmt = $db->prepare("
        SELECT a.*, d.company_name, d.job_role, d.drive_date, d.package_lpa, d.venue,
               i.interview_date, i.start_time, i.interview_type, i.result AS interview_result
        FROM applications a
        JOIN drives d ON d.id = a.drive_id
        LEFT JOIN interviews i ON i.application_id = a.id
        WHERE a.student_id = ?
        ORDER BY a.applied_at DESC
    ");
    $stmt->execute([$sId]);
    $applications = $stmt->fetchAll();
}

$statusColors = [
    'applied'              => ['badge' => 'info',      'label' => 'Applied'],
    'aptitude'             => ['badge' => 'warning',   'label' => 'Aptitude Test'],
    'aptitude_cleared'     => ['badge' => 'purple',    'label' => 'Aptitude Cleared'],
    'interview_scheduled'  => ['badge' => 'warning',   'label' => 'Interview Scheduled'],
    'selected'             => ['badge' => 'success',   'label' => 'Selected 🎉'],
    'rejected'             => ['badge' => 'danger',    'label' => 'Rejected'],
    'on_hold'              => ['badge' => 'secondary', 'label' => 'On Hold'],
];

$statusSteps = ['applied','aptitude','aptitude_cleared','interview_scheduled','selected'];

$pageTitle = 'My Applications';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Application Tracker</h1>
      <p>Track the status of all your placement applications</p>
    </div>

    <!-- Summary counts -->
    <div class="stats-grid mb-3">
      <?php
      $counts = array_fill_keys(array_keys($statusColors), 0);
      foreach ($applications as $a) {
          if (isset($counts[$a['status']])) $counts[$a['status']]++;
      }
      $iconMap = ['applied'=>'blue','aptitude'=>'amber','aptitude_cleared'=>'purple','interview_scheduled'=>'amber','selected'=>'green','rejected'=>'red','on_hold'=>'secondary'];
      foreach (['applied','aptitude_cleared','interview_scheduled','selected','rejected'] as $st):
      ?>
      <div class="stat-card">
        <div class="stat-icon <?= $iconMap[$st] ?? 'blue' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= $counts[$st] ?></div>
          <div class="stat-label"><?= $statusColors[$st]['label'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($applications): ?>
    <div style="display:flex;flex-direction:column;gap:1.25rem">
      <?php foreach ($applications as $app):
        $sc = $statusColors[$app['status']] ?? ['badge'=>'secondary','label'=>ucwords($app['status'])];
        $stepIdx = array_search($app['status'], $statusSteps);
        $isRejected = $app['status'] === 'rejected';
      ?>
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:0.75rem;margin-bottom:1rem">
          <div>
            <h3 style="font-size:1.05rem;margin-bottom:0.2rem"><?= e($app['company_name']) ?></h3>
            <div style="font-size:0.88rem;color:var(--text-muted)"><?= e($app['job_role']) ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.2rem">
              Drive: <?= date('d M Y', strtotime($app['drive_date'])) ?>
              <?php if ($app['package_lpa']): ?> &nbsp;·&nbsp; <?= $app['package_lpa'] ?> LPA<?php endif; ?>
              <?php if ($app['venue']): ?> &nbsp;·&nbsp; 📍 <?= e($app['venue']) ?><?php endif; ?>
            </div>
          </div>
          <div style="text-align:right">
            <span class="badge badge-<?= $sc['badge'] ?>" style="font-size:0.88rem;padding:0.35rem 0.85rem">
              <?= $sc['label'] ?>
            </span>
            <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.3rem">
              Applied: <?= date('d M Y', strtotime($app['applied_at'])) ?>
            </div>
          </div>
        </div>

        <!-- Progress Steps -->
        <?php if (!$isRejected): ?>
        <div style="display:flex;align-items:center;gap:0;margin-bottom:1rem;overflow-x:auto">
          <?php foreach ($statusSteps as $i => $step):
            $done    = ($stepIdx !== false) && $i <= $stepIdx;
            $current = ($stepIdx !== false) && $i === $stepIdx;
            $labels  = ['Applied','Aptitude','Cleared','Interview','Selected'];
          ?>
          <div style="display:flex;align-items:center;flex:1;min-width:60px">
            <div style="text-align:center;flex:0 0 auto">
              <div style="
                width:28px;height:28px;border-radius:50%;
                background:<?= $done ? 'var(--success)' : 'var(--border)' ?>;
                color:<?= $done ? '#fff' : 'var(--text-muted)' ?>;
                display:flex;align-items:center;justify-content:center;
                font-size:0.75rem;font-weight:700;
                border:<?= $current ? '2px solid var(--accent)' : 'none' ?>;
                margin:0 auto;
              ">
                <?= $done ? '✓' : ($i+1) ?>
              </div>
              <div style="font-size:0.7rem;color:<?= $done ? 'var(--success)' : 'var(--text-muted)' ?>;margin-top:0.2rem;white-space:nowrap">
                <?= $labels[$i] ?>
              </div>
            </div>
            <?php if ($i < count($statusSteps)-1): ?>
            <div style="flex:1;height:2px;background:<?= ($stepIdx !== false && $i < $stepIdx) ? 'var(--success)' : 'var(--border)' ?>;margin:0 4px;margin-bottom:1.2rem"></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-danger" style="font-size:0.85rem;margin-bottom:0.75rem">
          ❌ Unfortunately, your application was not selected. Keep applying to other drives!
        </div>
        <?php endif; ?>

        <!-- Interview details if scheduled -->
        <?php if ($app['interview_date']): ?>
        <div style="background:var(--accent-light);border-radius:var(--radius-sm);padding:0.75rem;font-size:0.85rem">
          📅 <strong>Interview Scheduled:</strong>
          <?= date('D, d M Y', strtotime($app['interview_date'])) ?>
          at <?= substr($app['start_time'],0,5) ?>
          | Type: <?= ucwords(str_replace('_',' ',$app['interview_type'])) ?>
          <?php if ($app['interview_result'] && $app['interview_result'] !== 'pending'): ?>
          | Result: <strong class="<?= $app['interview_result'] === 'cleared' ? 'text-success' : 'text-danger' ?>">
            <?= ucfirst($app['interview_result']) ?>
          </strong>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($app['notes']): ?>
        <div style="font-size:0.83rem;color:var(--text-muted);margin-top:0.6rem">
          📝 Note: <?= e($app['notes']) ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="card" style="text-align:center;padding:3rem">
      <div style="font-size:3rem;margin-bottom:1rem">📋</div>
      <h3 style="margin-bottom:0.5rem">No Applications Yet</h3>
      <p class="text-muted">You haven't applied to any drives yet.</p>
      <a href="<?= APP_URL ?>/student/drives.php" class="btn btn-primary" style="margin-top:1rem">Browse Drives</a>
    </div>
    <?php endif; ?>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
