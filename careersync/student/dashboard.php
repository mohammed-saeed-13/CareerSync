<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('student');

$db   = getDB();
$user = currentUser();

// Get student profile
$student = $db->prepare("SELECT s.*, u.email FROM students s JOIN users u ON u.id=s.user_id WHERE s.user_id=?");
$student->execute([$user['id']]);
$student = $student->fetch();
$sId = $student ? $student['id'] : null;

// Skills
$skills = $sId ? $db->prepare("SELECT * FROM student_skills WHERE student_id=?") : null;
if ($skills) { $skills->execute([$sId]); $skills = $skills->fetchAll(); } else { $skills = []; }

// Recent applications
$myApps = $sId ? $db->prepare("
    SELECT a.*, d.company_name, d.job_role, d.drive_date, d.package_lpa
    FROM applications a JOIN drives d ON d.id=a.drive_id
    WHERE a.student_id=? ORDER BY a.applied_at DESC LIMIT 5
") : null;
if ($myApps) { $myApps->execute([$sId]); $myApps = $myApps->fetchAll(); } else { $myApps = []; }

// Eligible drives (not applied yet)
$eligibleDrives = [];
if ($student) {
    $allDrives = $db->query("SELECT * FROM drives WHERE status IN ('upcoming','active') AND drive_date >= CURDATE()")->fetchAll();
    foreach ($allDrives as $d) {
        $branches = json_decode($d['allowed_branches'], true) ?: [];
        if (
            $student['cgpa'] >= $d['min_cgpa'] &&
            $student['backlogs'] <= $d['max_backlogs'] &&
            in_array($student['branch'], $branches, true)
        ) {
            $eligibleDrives[] = $d;
        }
    }
}

// Latest resume analysis
$latestAnalysis = $sId ? $db->prepare("SELECT * FROM resume_analysis_logs WHERE student_id=? ORDER BY analyzed_at DESC LIMIT 1") : null;
if ($latestAnalysis) { $latestAnalysis->execute([$sId]); $latestAnalysis = $latestAnalysis->fetch(); }

// Stats
$totalApps   = $sId ? $db->prepare("SELECT COUNT(*) FROM applications WHERE student_id=?")->execute([$sId]) : 0;
$totalApps   = $sId ? $db->query("SELECT COUNT(*) FROM applications WHERE student_id=$sId")->fetchColumn() : 0;
$selectedApps= $sId ? $db->query("SELECT COUNT(*) FROM applications WHERE student_id=$sId AND status='selected'")->fetchColumn() : 0;

$pageTitle = 'Student Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Welcome, <?= e($user['name']) ?> 👋</h1>
      <p>Your placement journey at a glance</p>
    </div>

    <?php if (!$student || !$student['branch']): ?>
    <div class="alert alert-warning mb-3">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      Your profile is incomplete. <a href="<?= APP_URL ?>/student/profile.php"><strong>Update your profile</strong></a> to appear in drive eligibility checks.
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= $student['cgpa'] ?? 'N/A' ?></div>
          <div class="stat-label">CGPA</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= count($eligibleDrives) ?></div>
          <div class="stat-label">Eligible Drives</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= $totalApps ?></div>
          <div class="stat-label">Applied</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon teal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= $latestAnalysis ? $latestAnalysis['score'] : '—' ?></div>
          <div class="stat-label">Resume Score</div>
        </div>
      </div>
    </div>

    <div class="grid-2">
      <!-- Eligible Drives -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">🎯 Eligible Drives</h3>
          <a href="<?= APP_URL ?>/student/drives.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <?php if ($eligibleDrives): ?>
        <div style="display:flex;flex-direction:column;gap:0.75rem">
          <?php foreach (array_slice($eligibleDrives, 0, 4) as $d): ?>
          <div style="padding:0.9rem;border:1px solid var(--border);border-radius:var(--radius-sm);display:flex;justify-content:space-between;align-items:center">
            <div>
              <strong><?= e($d['company_name']) ?></strong>
              <div style="font-size:0.82rem;color:var(--text-muted)"><?= e($d['job_role']) ?> • <?= $d['package_lpa'] ?> LPA</div>
              <div style="font-size:0.8rem;color:var(--text-muted)">
                <?= date('d M Y', strtotime($d['drive_date'])) ?>
              </div>
            </div>
            <a href="<?= APP_URL ?>/student/drives.php?apply=<?= $d['id'] ?>" class="btn btn-primary btn-sm">Apply</a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted" style="padding:1.5rem 0">No eligible drives at the moment. Complete your profile to unlock more drives.</p>
        <?php endif; ?>
      </div>

      <!-- Application Tracker -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">📋 Application Tracker</h3>
          <a href="<?= APP_URL ?>/student/applications.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <?php if ($myApps): ?>
        <div class="timeline">
          <?php
          $statusIcons = [
            'applied'=>'🟡','aptitude'=>'🔵','aptitude_cleared'=>'🟢',
            'interview_scheduled'=>'📅','selected'=>'✅','rejected'=>'❌','on_hold'=>'⏸️'
          ];
          foreach ($myApps as $app): ?>
          <div class="timeline-item">
            <div class="timeline-time"><?= e($app['company_name']) ?> • <?= date('d M', strtotime($app['applied_at'])) ?></div>
            <div class="timeline-content">
              <?= $statusIcons[$app['status']] ?? '•' ?> <?= e($app['job_role']) ?> —
              <span class="badge badge-<?= ['applied'=>'info','selected'=>'success','rejected'=>'danger','interview_scheduled'=>'warning'][$app['status']] ?? 'secondary' ?>">
                <?= ucwords(str_replace('_', ' ', $app['status'])) ?>
              </span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted" style="padding:1.5rem 0">No applications yet. Start applying to eligible drives!</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Skills & Resume CTA -->
    <div class="grid-2 mt-3">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">My Skills</h3>
          <a href="<?= APP_URL ?>/student/profile.php" class="btn btn-sm btn-secondary">Edit</a>
        </div>
        <?php if ($skills): ?>
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem">
          <?php foreach ($skills as $sk): ?>
          <span class="badge badge-info"><?= e($sk['skill_name']) ?></span>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted">No skills added yet. <a href="<?= APP_URL ?>/student/profile.php">Add skills</a></p>
        <?php endif; ?>
      </div>
      <div class="card" style="background:linear-gradient(135deg,rgba(14,165,233,0.08),rgba(139,92,246,0.08))">
        <h3 class="card-title mb-2">🤖 AI Resume Analyzer</h3>
        <p style="font-size:0.88rem;color:var(--text-secondary);margin-bottom:1rem">
          Get your resume scored, check ATS compatibility, and receive personalized suggestions powered by Gemini AI.
        </p>
        <?php if ($latestAnalysis): ?>
        <div style="display:flex;gap:1.5rem;margin-bottom:1rem">
          <div class="text-center">
            <div style="font-size:1.5rem;font-weight:700;color:var(--accent)"><?= $latestAnalysis['score'] ?>/100</div>
            <div style="font-size:0.78rem;color:var(--text-muted)">Resume Score</div>
          </div>
          <div class="text-center">
            <div style="font-size:1.5rem;font-weight:700;color:var(--success)"><?= $latestAnalysis['ats_score'] ?>/100</div>
            <div style="font-size:0.78rem;color:var(--text-muted)">ATS Score</div>
          </div>
          <div class="text-center">
            <div style="font-size:1.5rem;font-weight:700;color:var(--purple)"><?= $latestAnalysis['placement_probability'] ?>%</div>
            <div style="font-size:0.78rem;color:var(--text-muted)">Placement %</div>
          </div>
        </div>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/student/resume.php" class="btn btn-primary">
          <?= $latestAnalysis ? 'Re-analyze Resume' : 'Analyze My Resume' ?>
        </a>
      </div>
    </div>
  </main>
</div>

<!-- Chatbot -->
<button class="chatbot-fab" id="chatbot-fab" title="Ask AI">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
</button>
<div class="chatbot-window" id="chatbot-window">
  <div class="chatbot-header">
    <h4>🤖 Career AI Assistant</h4>
    <button class="chatbot-close" id="chatbot-close">✕</button>
  </div>
  <div class="chatbot-messages" id="chatbot-messages"></div>
  <div class="chatbot-input">
    <input type="text" id="chatbot-input" placeholder="Am I eligible for TCS Digital?">
    <button class="btn btn-primary btn-sm" id="chatbot-send">Send</button>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
