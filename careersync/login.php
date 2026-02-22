<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) redirectToDashboard(currentUser()['role']);

$error = getFlash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter email and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, name, email, password, role, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } elseif (!$user['is_active']) {
            $error = 'Your account has been deactivated. Contact admin.';
        } else {
            loginUser($user);
            redirectToDashboard($user['role']);
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-box fade-in">
    <div class="auth-logo">
      <h1>Career<span>Sync</span></h1>
      <p>Sign in to access your dashboard</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger" data-autohide>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@college.edu"
               value="<?= e($_POST['email'] ?? '') ?>" autofocus required>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Your password" required>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
    </form>

    <!-- <div style="margin-top:1.2rem; padding:0.9rem; background:var(--bg-hover); border-radius:var(--radius-sm); font-size:0.82rem; color:var(--text-muted)">
      <strong>Demo Accounts</strong> (password: <code>password</code>)<br>
      Admin: admin@careersync.edu | Student: rahul@student.edu | Alumni: amit@alumni.edu
    </div> -->

    <p class="text-center mt-2" style="font-size:0.88rem;color:var(--text-muted)">
      New here? <a href="<?= APP_URL ?>/register.php">Create an account</a>
    </p>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
