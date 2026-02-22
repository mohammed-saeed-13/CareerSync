<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('alumni');

$db   = getDB();
$user = currentUser();

$alumni = $db->prepare("SELECT * FROM alumni WHERE user_id=?");
$alumni->execute([$user['id']]);
$alumni = $alumni->fetch();
$aId = $alumni ? $alumni['id'] : null;

$referralCount  = $aId ? $db->query("SELECT COUNT(*) FROM referrals WHERE alumni_id=$aId AND is_active=1")->fetchColumn() : 0;
$mentorCount    = $aId ? $db->query("SELECT COUNT(*) FROM mentorship_slots WHERE alumni_id=$aId")->fetchColumn() : 0;
$bookedSlots    = $aId ? $db->query("SELECT COUNT(*) FROM mentorship_slots WHERE alumni_id=$aId AND status='booked'")->fetchColumn() : 0;

$recentReferrals = $aId ? $db->query("SELECT * FROM referrals WHERE alumni_id=$aId ORDER BY created_at DESC LIMIT 5")->fetchAll() : [];
$slots = $aId ? $db->query("SELECT ms.*, u.name as student_name FROM mentorship_slots ms LEFT JOIN students s ON s.id=ms.booked_by LEFT JOIN users u ON u.id=s.user_id WHERE ms.alumni_id=$aId ORDER BY ms.slot_date DESC LIMIT 5")->fetchAll() : [];

$pageTitle = 'Alumni Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Welcome back, <?= e($user['name']) ?> 👋</h1>
      <p>Give back to your college community</p>
    </div>

    <?php if (!$alumni || !$alumni['current_company']): ?>
    <div class="alert alert-warning mb-3">
      <a href="<?= APP_URL ?>/alumni/profile.php"><strong>Complete your profile</strong></a> to start mentoring and posting referrals.
    </div>
    <?php endif; ?>

    <div class="stats-grid mb-3">
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= $referralCount ?></div>
          <div class="stat-label">Active Referrals</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= $mentorCount ?></div>
          <div class="stat-label">Mentorship Slots</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= $bookedSlots ?></div>
          <div class="stat-label">Students Mentored</div>
        </div>
      </div>
    </div>

    <div class="grid-2">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Recent Referrals</h3>
          <a href="<?= APP_URL ?>/alumni/referrals.php" class="btn btn-sm btn-secondary">Manage</a>
        </div>
        <?php if ($recentReferrals): ?>
        <?php foreach ($recentReferrals as $r): ?>
        <div style="padding:0.75rem 0;border-bottom:1px solid var(--border)">
          <strong><?= e($r['job_title']) ?></strong> at <strong><?= e($r['company_name']) ?></strong>
          <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.2rem"><?= date('d M Y',strtotime($r['created_at'])) ?>
            <?php if ($r['expiry_date']): ?> · Expires <?= date('d M', strtotime($r['expiry_date'])) ?><?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p class="text-muted">No referrals posted yet. <a href="<?= APP_URL ?>/alumni/referrals.php">Post a referral</a></p>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Mentorship Slots</h3>
          <a href="<?= APP_URL ?>/alumni/mentorship.php" class="btn btn-sm btn-secondary">Manage</a>
        </div>
        <?php if ($slots): ?>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Date</th><th>Time</th><th>Status</th><th>Student</th></tr></thead>
            <tbody>
            <?php foreach ($slots as $sl): ?>
            <tr>
              <td><?= date('d M', strtotime($sl['slot_date'])) ?></td>
              <td style="font-size:0.82rem"><?= substr($sl['start_time'],0,5) ?>–<?= substr($sl['end_time'],0,5) ?></td>
              <td>
                <span class="badge badge-<?= ['available'=>'success','booked'=>'warning','completed'=>'info','cancelled'=>'danger'][$sl['status']] ?>">
                  <?= ucfirst($sl['status']) ?>
                </span>
              </td>
              <td style="font-size:0.85rem"><?= $sl['student_name'] ? e($sl['student_name']) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <p class="text-muted">No slots created yet. <a href="<?= APP_URL ?>/alumni/mentorship.php">Create a slot</a></p>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
