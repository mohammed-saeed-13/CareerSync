<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('alumni');

$db   = getDB();
$user = currentUser();

$alumni = $db->prepare("SELECT * FROM alumni WHERE user_id=?");
$alumni->execute([$user['id']]);
$alumni = $alumni->fetch();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $company    = trim($_POST['current_company'] ?? '');
    $role       = trim($_POST['current_role'] ?? '');
    $branch     = trim($_POST['branch'] ?? '');
    $gradYear   = (int)($_POST['graduation_year'] ?? 0);
    $exp        = (int)($_POST['years_experience'] ?? 0);
    $linkedin   = trim($_POST['linkedin_url'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $bio        = trim($_POST['bio'] ?? '');
    $isMentor   = isset($_POST['is_mentor']) ? 1 : 0;

    $db->prepare("
        UPDATE alumni SET current_company=?, current_role=?, branch=?, graduation_year=?,
               years_experience=?, linkedin_url=?, phone=?, bio=?, is_mentor=?
        WHERE user_id=?
    ")->execute([$company, $role, $branch, $gradYear ?: null, $exp, $linkedin, $phone, $bio, $isMentor, $user['id']]);

    // Update name
    if (trim($_POST['name'] ?? '')) {
        $db->prepare("UPDATE users SET name=? WHERE id=?")->execute([trim($_POST['name']), $user['id']]);
        $_SESSION['user']['name'] = trim($_POST['name']);
    }

    $success = 'Profile updated!';
    $alumni = $db->prepare("SELECT * FROM alumni WHERE user_id=?");
    $alumni->execute([$user['id']]);
    $alumni = $alumni->fetch();
}

$branches = ['Computer Science','Information Technology','Electronics','Electrical','Mechanical','Civil','Chemical','Biotechnology'];

$pageTitle = 'Alumni Profile';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>My Profile</h1>
      <p>Keep your profile updated to inspire and guide current students</p>
    </div>

    <?php if ($success): ?><div class="alert alert-success" data-autohide><?= e($success) ?></div><?php endif; ?>

    <div style="max-width:600px">
      <div class="card">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Current Company</label>
            <input type="text" name="current_company" class="form-control" placeholder="Google, Microsoft..." value="<?= e($alumni['current_company'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Current Role</label>
            <input type="text" name="current_role" class="form-control" placeholder="Software Engineer" value="<?= e($alumni['current_role'] ?? '') ?>">
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Branch</label>
              <select name="branch" class="form-control">
                <option value="">Select Branch</option>
                <?php foreach ($branches as $b): ?>
                <option value="<?= e($b) ?>" <?= ($alumni['branch'] ?? '') === $b ? 'selected' : '' ?>><?= e($b) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Graduation Year</label>
              <input type="number" name="graduation_year" class="form-control" min="2000" max="<?= date('Y') ?>" value="<?= e($alumni['graduation_year'] ?? '') ?>">
            </div>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Years of Experience</label>
              <input type="number" name="years_experience" class="form-control" min="0" max="50" value="<?= e($alumni['years_experience'] ?? 0) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input type="tel" name="phone" class="form-control" value="<?= e($alumni['phone'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">LinkedIn URL</label>
            <input type="url" name="linkedin_url" class="form-control" placeholder="https://linkedin.com/in/..." value="<?= e($alumni['linkedin_url'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Bio</label>
            <textarea name="bio" class="form-control" rows="4" placeholder="Tell students about yourself, your journey, and what you can help with..."><?= e($alumni['bio'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:0.6rem;cursor:pointer;font-size:0.9rem">
              <input type="checkbox" name="is_mentor" value="1" <?= ($alumni['is_mentor'] ?? 0) ? 'checked' : '' ?>>
              <span>I want to be a <strong>Mentor</strong> (students can book sessions with me)</span>
            </label>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Save Profile</button>
        </form>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
