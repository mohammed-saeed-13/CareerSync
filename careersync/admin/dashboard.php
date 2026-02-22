<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('admin');

$db  = getDB();
$user = currentUser();

// ---- Stats ---------------------------------------------------
$totalStudents   = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$placedStudents  = $db->query("SELECT COUNT(*) FROM students WHERE placement_status='placed'")->fetchColumn();
$activeDrives    = $db->query("SELECT COUNT(*) FROM drives WHERE status IN ('upcoming','active')")->fetchColumn();
$totalApplications = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$placementPct    = $totalStudents > 0 ? round(($placedStudents / $totalStudents) * 100, 1) : 0;

// ---- Recent Drives -------------------------------------------
$drives = $db->query("
    SELECT d.*, 
           COUNT(DISTINCT a.id) AS app_count
    FROM drives d
    LEFT JOIN applications a ON a.drive_id = d.id
    WHERE d.status IN ('upcoming','active')
    ORDER BY d.drive_date ASC
    LIMIT 5
")->fetchAll();

// ---- Recent Applications ------------------------------------
$recentApps = $db->query("
    SELECT a.*, u.name AS student_name, d.company_name, d.job_role
    FROM applications a
    JOIN students s ON s.id = a.student_id
    JOIN users u ON u.id = s.user_id
    JOIN drives d ON d.id = a.drive_id
    ORDER BY a.applied_at DESC
    LIMIT 8
")->fetchAll();

// ---- Branch placement data for chart -------------------------
$branchStats = $db->query("
    SELECT branch,
           COUNT(*) AS total,
           SUM(placement_status='placed') AS placed
    FROM students
    GROUP BY branch
")->fetchAll();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>TPO Control Center</h1>
      <p>Welcome back, <?= e($user['name']) ?>. Here's today's placement overview.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
          <div class="stat-value" data-val="<?= $totalStudents ?>"><?= $totalStudents ?></div>
          <div class="stat-label">Total Students</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        </div>
        <div>
          <div class="stat-value" data-val="<?= $placedStudents ?>"><?= $placedStudents ?></div>
          <div class="stat-label">Placed Students</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div>
          <div class="stat-value" data-val="<?= $activeDrives ?>"><?= $activeDrives ?></div>
          <div class="stat-label">Active Drives</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= $placementPct ?>%</div>
          <div class="stat-label">Placement Rate</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon teal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div>
          <div class="stat-value" data-val="<?= $totalApplications ?>"><?= $totalApplications ?></div>
          <div class="stat-label">Applications</div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-3">
      <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
      </div>
      <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
        <a href="<?= APP_URL ?>/admin/drives.php?action=create" class="btn btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          New Drive
        </a>
        <a href="<?= APP_URL ?>/admin/criteria.php" class="btn btn-secondary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          Criteria Engine
        </a>
        <a href="<?= APP_URL ?>/admin/students.php" class="btn btn-secondary">View All Students</a>
        <a href="<?= APP_URL ?>/admin/analytics.php" class="btn btn-secondary">Analytics</a>
      </div>
    </div>

    <div class="grid-2">
      <!-- Upcoming Drives -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Upcoming Drives</h3>
          <a href="<?= APP_URL ?>/admin/drives.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <?php if ($drives): ?>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Company</th><th>Date</th><th>Apps</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($drives as $d): ?>
            <tr>
              <td>
                <strong><?= e($d['company_name']) ?></strong><br>
                <small class="text-muted"><?= e($d['job_role']) ?></small>
              </td>
              <td><?= date('d M Y', strtotime($d['drive_date'])) ?></td>
              <td><span class="badge badge-info"><?= $d['app_count'] ?></span></td>
              <td>
                <span class="badge badge-<?= $d['status'] === 'active' ? 'success' : 'warning' ?>">
                  <?= ucfirst($d['status']) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <p class="text-muted text-center" style="padding:2rem">No upcoming drives. <a href="<?= APP_URL ?>/admin/drives.php?action=create">Create one</a></p>
        <?php endif; ?>
      </div>

      <!-- Branch Chart -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Placement by Branch</h3>
        </div>
        <canvas id="branchChart" height="220"></canvas>
      </div>
    </div>

    <!-- Recent Applications -->
    <div class="card mt-3">
      <div class="card-header">
        <h3 class="card-title">Recent Applications</h3>
        <a href="<?= APP_URL ?>/admin/applications.php" class="btn btn-sm btn-secondary">View All</a>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Student</th><th>Company</th><th>Role</th><th>Status</th><th>Applied</th></tr></thead>
          <tbody>
          <?php foreach ($recentApps as $app): ?>
          <tr>
            <td><?= e($app['student_name']) ?></td>
            <td><?= e($app['company_name']) ?></td>
            <td><?= e($app['job_role']) ?></td>
            <td>
              <?php
              $statusColors = [
                'applied'=>'info','aptitude'=>'warning','aptitude_cleared'=>'purple',
                'interview_scheduled'=>'warning','selected'=>'success','rejected'=>'danger','on_hold'=>'secondary'
              ];
              $sc = $statusColors[$app['status']] ?? 'secondary';
              ?>
              <span class="badge badge-<?= $sc ?>"><?= e(ucwords(str_replace('_',' ',$app['status']))) ?></span>
            </td>
            <td class="text-muted"><?= date('d M', strtotime($app['applied_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Chatbot FAB -->
<button class="chatbot-fab" id="chatbot-fab" title="AI Assistant">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
</button>
<div class="chatbot-window" id="chatbot-window">
  <div class="chatbot-header">
    <h4>🤖 CareerSync AI</h4>
    <button class="chatbot-close" id="chatbot-close">✕</button>
  </div>
  <div class="chatbot-messages" id="chatbot-messages"></div>
  <div class="chatbot-input">
    <input type="text" id="chatbot-input" placeholder="Ask me anything...">
    <button class="btn btn-primary btn-sm" id="chatbot-send">Send</button>
  </div>
</div>

<?php
$branchLabels = json_encode(array_column($branchStats, 'branch'));
$branchTotal  = json_encode(array_map('intval', array_column($branchStats, 'total')));
$branchPlaced = json_encode(array_map('intval', array_column($branchStats, 'placed')));

$extraJs = <<<JS
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('branchChart');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: {$branchLabels},
    datasets: [
      { label: 'Total', data: {$branchTotal}, backgroundColor: 'rgba(14,165,233,0.2)', borderColor: '#0ea5e9', borderWidth: 2, borderRadius: 4 },
      { label: 'Placed', data: {$branchPlaced}, backgroundColor: 'rgba(16,185,129,0.7)', borderColor: '#10b981', borderWidth: 2, borderRadius: 4 }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'top' } },
    scales: {
      y: { beginAtZero: true, grid: { color: 'rgba(128,128,128,0.1)' } },
      x: { grid: { display: false } }
    }
  }
});
</script>
JS;
include __DIR__ . '/../includes/footer.php';
?>
