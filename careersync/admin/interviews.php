<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('admin');

$db   = getDB();
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_interview'])) {
    verifyCsrf();
    $appId       = (int)$_POST['application_id'];
    $date        = $_POST['interview_date'] ?? '';
    $startTime   = $_POST['start_time'] ?? '';
    $endTime     = $_POST['end_time'] ?? '';
    $type        = $_POST['interview_type'] ?? 'technical';
    $venue       = trim($_POST['venue'] ?? '');
    $interviewer = trim($_POST['interviewer_name'] ?? '');

    if (!$appId || !$date || !$startTime || !$endTime) {
        $error = 'Application, date, and time are required.';
    } else {
        // Fetch application details
        $app = $db->prepare("SELECT a.*, s.id as sid, d.id as did FROM applications a JOIN students s ON s.id=a.student_id JOIN drives d ON d.id=a.drive_id WHERE a.id=?");
        $app->execute([$appId]);
        $app = $app->fetch();

        if (!$app) {
            $error = 'Application not found.';
        } else {
            // Check time overlap for this student
            $overlap = $db->prepare("
                SELECT id FROM interviews
                WHERE student_id=? AND interview_date=? AND (
                    (start_time < ? AND end_time > ?) OR (start_time >= ? AND start_time < ?)
                )
            ");
            $overlap->execute([$app['sid'], $date, $endTime, $startTime, $startTime, $endTime]);
            if ($overlap->fetch()) {
                $error = 'This student already has an interview scheduled that overlaps with this time slot.';
            } else {
                try {
                    $db->prepare("
                        INSERT INTO interviews (application_id, student_id, drive_id, interview_date, start_time, end_time, venue, interview_type, interviewer_name)
                        VALUES (?,?,?,?,?,?,?,?,?)
                    ")->execute([$appId, $app['sid'], $app['did'], $date, $startTime, $endTime, $venue, $type, $interviewer]);

                    // Update application status
                    $db->prepare("UPDATE applications SET status='interview_scheduled' WHERE id=?")->execute([$appId]);

                    // Notify student
                    $studentUser = $db->prepare("SELECT u.id FROM users u JOIN students s ON s.user_id=u.id WHERE s.id=?");
                    $studentUser->execute([$app['sid']]);
                    $uid = $studentUser->fetchColumn();
                    if ($uid) {
                        $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)")
                           ->execute([$uid,
                               'Interview Scheduled',
                               "Your interview has been scheduled on $date from " . substr($startTime,0,5) . " to " . substr($endTime,0,5) . ($venue ? " at $venue" : '') . ".",
                               'info'
                           ]);
                    }
                    $success = 'Interview scheduled and student notified!';
                } catch (Exception $e) {
                    $error = 'Failed to schedule. Check for duplicates.';
                }
            }
        }
    }
}

// Upcoming interviews
$interviews = $db->query("
    SELECT i.*, u.name AS student_name, d.company_name, d.job_role
    FROM interviews i
    JOIN students s ON s.id = i.student_id
    JOIN users u ON u.id = s.user_id
    JOIN drives d ON d.id = i.drive_id
    ORDER BY i.interview_date ASC, i.start_time ASC
    LIMIT 50
")->fetchAll();

// Applications ready for interview (aptitude_cleared)
$readyApps = $db->query("
    SELECT a.id, u.name AS student_name, d.company_name, d.job_role
    FROM applications a
    JOIN students s ON s.id = a.student_id
    JOIN users u ON u.id = s.user_id
    JOIN drives d ON d.id = a.drive_id
    WHERE a.status IN ('aptitude_cleared', 'applied', 'aptitude')
    ORDER BY d.drive_date ASC
    LIMIT 100
")->fetchAll();

$pageTitle = 'Interview Scheduler';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Interview Scheduler</h1>
      <p>Schedule interviews with automatic overlap prevention</p>
    </div>

    <?php if ($success): ?><div class="alert alert-success" data-autohide><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" data-autohide><?= e($error) ?></div><?php endif; ?>

    <div class="grid-2">
      <!-- Schedule Form -->
      <div class="card">
        <div class="card-header"><h3 class="card-title">Schedule New Interview</h3></div>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="schedule_interview" value="1">

          <div class="form-group">
            <label class="form-label">Application *</label>
            <select name="application_id" class="form-control" required>
              <option value="">-- Select Student + Drive --</option>
              <?php foreach ($readyApps as $ra): ?>
              <option value="<?= $ra['id'] ?>">
                <?= e($ra['student_name']) ?> → <?= e($ra['company_name']) ?> (<?= e($ra['job_role']) ?>)
              </option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">Shows students with applied/aptitude cleared status</small>
          </div>

          <div class="form-group">
            <label class="form-label">Interview Date *</label>
            <input type="date" name="interview_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Start Time *</label>
              <input type="time" name="start_time" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">End Time *</label>
              <input type="time" name="end_time" class="form-control" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Interview Type</label>
            <select name="interview_type" class="form-control">
              <option value="technical">Technical</option>
              <option value="hr">HR</option>
              <option value="managerial">Managerial</option>
              <option value="group_discussion">Group Discussion</option>
              <option value="aptitude">Aptitude</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Venue</label>
            <input type="text" name="venue" class="form-control" placeholder="Seminar Hall A / Online">
          </div>

          <div class="form-group">
            <label class="form-label">Interviewer Name</label>
            <input type="text" name="interviewer_name" class="form-control" placeholder="Mr. Sharma">
          </div>

          <button type="submit" class="btn btn-primary btn-block">Schedule Interview</button>
        </form>
      </div>

      <!-- Upcoming Interviews -->
      <div class="card">
        <div class="card-header"><h3 class="card-title">Scheduled Interviews</h3></div>
        <?php if ($interviews): ?>
        <div style="max-height:520px;overflow-y:auto;display:flex;flex-direction:column;gap:0.6rem">
          <?php foreach ($interviews as $iv): ?>
          <div style="padding:0.8rem;border:1px solid var(--border);border-radius:var(--radius-sm)">
            <div style="display:flex;justify-content:space-between;align-items:start">
              <div>
                <strong style="font-size:0.92rem"><?= e($iv['student_name']) ?></strong>
                <div style="font-size:0.82rem;color:var(--text-muted)"><?= e($iv['company_name']) ?> – <?= e($iv['job_role']) ?></div>
                <div style="font-size:0.82rem;color:var(--accent);margin-top:0.25rem">
                  📅 <?= date('D, d M Y', strtotime($iv['interview_date'])) ?>
                  &nbsp;<?= substr($iv['start_time'],0,5) ?>–<?= substr($iv['end_time'],0,5) ?>
                </div>
                <?php if ($iv['venue']): ?>
                <div style="font-size:0.78rem;color:var(--text-muted)">📍 <?= e($iv['venue']) ?></div>
                <?php endif; ?>
              </div>
              <div>
                <span class="badge badge-<?= ['technical'=>'info','hr'=>'purple','managerial'=>'warning','group_discussion'=>'secondary','aptitude'=>'amber'][$iv['interview_type']] ?? 'info' ?>">
                  <?= ucwords(str_replace('_',' ',$iv['interview_type'])) ?>
                </span>
                <div style="margin-top:0.3rem">
                  <span class="badge badge-<?= ['pending'=>'secondary','cleared'=>'success','rejected'=>'danger'][$iv['result']] ?? 'secondary' ?>">
                    <?= ucfirst($iv['result']) ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted text-center" style="padding:2rem">No interviews scheduled yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
