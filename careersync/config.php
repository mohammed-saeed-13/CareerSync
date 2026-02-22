<?php
// ============================================================
// config.php – CareerSync Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'careersync');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Gemini API Key
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');
define('GEMINI_MODEL', 'gemini-1.5-flash');

// App Settings
define('APP_NAME', 'CareerSync');
define('APP_URL', 'http://localhost/careersync');
define('SESSION_LIFETIME', 3600);

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
