<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) redirectToDashboard(currentUser()['role']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? '';

    // Validation
    if (!$name || !$email || !$password || !$role) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['admin', 'student', 'alumni'], true)) {
        $error = 'Invalid role selected.';
    } else {
        $db = getDB();

        // Check existing email
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->beginTransaction();
            try {
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hash, $role]);
                $userId = (int)$db->lastInsertId();

                // Create role-specific profile
                if ($role === 'student') {
                    $db->prepare("INSERT INTO students (user_id) VALUES (?)")->execute([$userId]);
                } elseif ($role === 'alumni') {
                    $db->prepare("INSERT INTO alumni (user_id) VALUES (?)")->execute([$userId]);
                }

                $db->commit();

                // Auto-login
                $user = ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => $role];
                loginUser($user);
                redirectToDashboard($role);
            } catch (Exception $ex) {
                $db->rollBack();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-box fade-in">
    <div class="auth-logo">
      <h1>Career<span>Sync</span></h1>
      <p>Create your account and start your placement journey</p>
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
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" placeholder="John Doe" value="<?= e($_POST['name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@college.edu" value="<?= e($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Role</label>
        <select name="role" class="form-control" required>
          <option value="">-- Select Role --</option>
          <option value="student" <?= (($_POST['role'] ?? '') === 'student') ? 'selected' : '' ?>>Student</option>
          <option value="alumni" <?= (($_POST['role'] ?? '') === 'alumni') ? 'selected' : '' ?>>Alumni</option>
          <!-- <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin (TPO)</option> -->
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" required>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
    </form>

    <p class="text-center mt-2" style="font-size:0.88rem;color:var(--text-muted)">
      Already have an account? <a href="<?= APP_URL ?>/login.php">Login here</a>
    </p>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
