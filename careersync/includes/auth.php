<?php
// ============================================================
// includes/auth.php – Session & Role-based Middleware
// ============================================================

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => false, // set true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ---- CSRF Token ------------------------------------------------
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF validation failed.');
    }
}

// ---- Auth Helpers -----------------------------------------------
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function requireLogin(string $redirect = '/careersync/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function requireRole(string|array $roles, string $redirect = '/careersync/login.php'): void {
    requireLogin($redirect);
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['user']['role'] ?? '', $allowed, true)) {
        header('Location: ' . APP_URL . '/unauthorized.php');
        exit;
    }
}

// ---- Set Session After Login ------------------------------------
function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user']      = [
        'id'    => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ---- Redirect Based on Role ------------------------------------
function redirectToDashboard(string $role): void {
    $map = [
        'admin'   => APP_URL . '/admin/dashboard.php',
        'student' => APP_URL . '/student/dashboard.php',
        'alumni'  => APP_URL . '/alumni/dashboard.php',
    ];
    header('Location: ' . ($map[$role] ?? APP_URL . '/index.php'));
    exit;
}

// ---- Output Helpers --------------------------------------------
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function flash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function getFlash(string $key): string {
    $msg = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $msg;
}
