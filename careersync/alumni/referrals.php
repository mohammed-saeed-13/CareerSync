<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('alumni');

$db   = getDB();
$user = currentUser();
$alumni = $db->prepare("SELECT * FROM alumni WHERE user_id=?")->execute([$user['id']]) ?
    $db->prepare("SELECT * FROM alumni WHERE user_id=?") : null;
$alumni = $db->prepare("SELECT * FROM alumni WHERE user_id=?");
$alumni->execute([$user['id']]);
$alumni = $alumni->fetch();
$aId = $alumni['id'];

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_referral') {
        $title    = trim($_POST['job_title'] ?? '');
        $company  = trim($_POST['company_name'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $skills   = trim($_POST['required_skills'] ?? '');
        $exp      = trim($_POST['experience_required'] ?? '');
        $link     = trim($_POST['apply_link'] ?? '');
        $expiry   = $_POST['expiry_date'] ?? null;

        if (!$title || !$company) {
            $error = 'Job title and company are required.';
        } else {
            $db->prepare("
                INSERT INTO referrals (alumni_id, job_title, company_name, description, required_skills, experience_required, apply_link, expiry_date)
                VALUES (?,?,?,?,?,?,?,?)
            ")->execute([$aId, $title, $company, $desc, $skills, $exp, $link, $expiry ?: null]);
            $success = 'Referral posted!';
        }
    }

    if ($action === 'toggle_referral') {
        $db->prepare("UPDATE referrals SET is_active = NOT is_active WHERE id=? AND alumni_id=?")
           ->execute([(int)$_POST['ref_id'], $aId]);
        $success = 'Referral status updated.';
    }
}

$referrals = $db->query("SELECT * FROM referrals WHERE alumni_id=$aId ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Job Referrals';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Job Referral Board</h1>
      <p>Post job openings from your company for campus students</p>
    </div>

    <?php if ($success): ?><div class="alert alert-success" data-autohide><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" data-autohide><?= e($error) ?></div><?php endif; ?>

    <div class="grid-2">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Post a Referral</h3></div>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="add_referral">
          <div class="form-group">
            <label class="form-label">Job Title *</label>
            <input type="text" name="job_title" class="form-control" placeholder="Software Engineer" required>
          </div>
          <div class="form-group">
            <label class="form-label">Company *</label>
            <input type="text" name="company_name" class="form-control" placeholder="Google, Microsoft..." required>
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Role details, responsibilities..."></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Required Skills</label>
            <input type="text" name="required_skills" class="form-control" placeholder="Python, React, AWS">
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Experience Required</label>
              <input type="text" name="experience_required" class="form-control" placeholder="0-2 years / Fresher">
            </div>
            <div class="form-group">
              <label class="form-label">Expiry Date</label>
              <input type="date" name="expiry_date" class="form-control" min="<?= date('Y-m-d') ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Application Link</label>
            <input type="url" name="apply_link" class="form-control" placeholder="https://careers.company.com/...">
          </div>
          <button type="submit" class="btn btn-primary btn-block">Post Referral</button>
        </form>
      </div>

      <div class="card">
        <div class="card-header"><h3 class="card-title">Your Referrals</h3></div>
        <?php if ($referrals): ?>
        <div style="max-height:550px;overflow-y:auto;display:flex;flex-direction:column;gap:0.75rem">
          <?php foreach ($referrals as $r): ?>
          <div style="padding:0.9rem;border:1px solid var(--border);border-radius:var(--radius-sm);opacity:<?= $r['is_active'] ? 1 : 0.55 ?>">
            <div style="display:flex;justify-content:space-between;align-items:start;gap:0.5rem">
              <div style="flex:1">
                <strong><?= e($r['job_title']) ?></strong>
                <div style="font-size:0.85rem;color:var(--accent)"><?= e($r['company_name']) ?></div>
                <?php if ($r['required_skills']): ?>
                <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.3rem">Skills: <?= e($r['required_skills']) ?></div>
                <?php endif; ?>
                <?php if ($r['experience_required']): ?>
                <div style="font-size:0.8rem;color:var(--text-muted)">Exp: <?= e($r['experience_required']) ?></div>
                <?php endif; ?>
                <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.3rem">
                  Posted: <?= date('d M Y', strtotime($r['created_at'])) ?>
                  <?php if ($r['expiry_date']): ?> · Expires: <?= date('d M Y', strtotime($r['expiry_date'])) ?><?php endif; ?>
                </div>
              </div>
              <div style="display:flex;flex-direction:column;gap:0.4rem;align-items:flex-end">
                <span class="badge badge-<?= $r['is_active'] ? 'success' : 'secondary' ?>">
                  <?= $r['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
                <form method="POST">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="action" value="toggle_referral">
                  <input type="hidden" name="ref_id" value="<?= $r['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-secondary">Toggle</button>
                </form>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted">No referrals posted yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
