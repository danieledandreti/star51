<?php
// ============================================
// NOVA MAIN CONFIGURATION
// ============================================
// Central configuration for Star51/Nova system
// Configuration values loaded from conf/nova_config_values.php
// Session 49 (January 2026) - Settings-based configuration
// ============================================

// ============================================
// 1. LOAD CONFIGURATION VALUES
// ============================================
require_once __DIR__ . "/../conf/nova_config_values.php";

// ============================================
// 2. ENVIRONMENT DETECTION
// ============================================
$http_host = (string)($_SERVER['HTTP_HOST'] ?? 'unknown');
$normalized_host = preg_replace('/:\d+$/', '', $http_host);
$local_hosts = ['127.0.0.1', 'localhost', 'gomes.local', 'god.local'];
$is_local = in_array($normalized_host, $local_hosts, true);

// ============================================
// 3. ERROR REPORTING (environment-based)
// ============================================
if ($is_local) {
  error_reporting(E_ALL);
  ini_set("display_errors", 1);
  ini_set("log_errors", 1);
} else {
  error_reporting(0);
  ini_set("display_errors", 0);
  ini_set("log_errors", 1);
}

// ============================================
// 4. LOG FILE PATH (centralized constant)
// ============================================
define("NOVA_LOG_PATH", __DIR__ . "/../logs/error.log");

ini_set("error_log", NOVA_LOG_PATH);

if (!file_exists(NOVA_LOG_PATH)) {
  @touch(NOVA_LOG_PATH);
}

// ============================================
// 5. DATABASE CONFIGURATION (from settings)
// ============================================
if ($is_local) {
  // Local environment - read from config
  define("DB_HOST", $nova_settings["db_host_local"] ?? "");
  define("DB_USER", $nova_settings["db_user_local"] ?? "");
  define("DB_PASS", $nova_settings["db_pass_local"] ?? "");
  define("DB_NAME", $nova_settings["db_name_local"] ?? "");
} else {
  // Remote environment - read from config
  define("DB_HOST", $nova_settings["db_host_remote"] ?? "");
  define("DB_USER", $nova_settings["db_user_remote"] ?? "");
  define("DB_PASS", $nova_settings["db_pass_remote"] ?? "");
  define("DB_NAME", $nova_settings["db_name_remote"] ?? "");
}

// ============================================
// 6. WEB PATHS (auto-detect)
// ============================================
$script_path = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$nova_web_path = '/nova';

// Extract stable /.../nova base even when current page is inside nested Nova folders.
$nova_marker_pos = strpos($script_path, '/nova/');
if ($nova_marker_pos !== false) {
  $nova_web_path = substr($script_path, 0, $nova_marker_pos + 5);
} elseif (substr($script_path, -5) === '/nova') {
  $nova_web_path = $script_path;
}

if ($nova_web_path === '' || $nova_web_path[0] !== '/') {
  $nova_web_path = '/nova';
}

define('NOVA_WEB_PATH', $nova_web_path);
define('NOVA_BASE_PATH', $nova_web_path);

// Backward compatibility variables
$nova_web_path = NOVA_WEB_PATH;
$nova_base = NOVA_BASE_PATH;

// ============================================
// DATABASE CONNECTION
// ============================================

// Offer setup only when the installer is available and database settings are incomplete.
$setup_project_root = dirname(__DIR__, 2);
$setup_required = is_file($setup_project_root . '/install/index.php')
  && !file_exists($setup_project_root . '/.installed')
  && (DB_HOST === '' || DB_USER === '' || DB_NAME === '');
$conn = false;

if (!$setup_required) {
  try {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
  } catch (mysqli_sql_exception $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] DB Error: ' . $e->getMessage() . ' - Host: ' . DB_HOST . ' - User: ' . DB_USER);
  }
}

if (!$conn) {
  $setup_url = '/install/';
  if ($setup_required) {
    // Derive the project URL from the entry script, including nested Nova routes.
    $setup_script_file = str_replace('\\', '/', (string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $setup_script_name = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $setup_root_path = str_replace('\\', '/', $setup_project_root);
    if (str_starts_with($setup_script_file, $setup_root_path . '/')) {
      $setup_relative_script = substr($setup_script_file, strlen($setup_root_path));
      if (str_ends_with($setup_script_name, $setup_relative_script)) {
        $setup_web_base = substr($setup_script_name, 0, -strlen($setup_relative_script));
        $setup_url = '/' . ltrim($setup_web_base . '/install/', '/');
      }
    }
  }

  // Keep first-run guidance separate from failures on an already configured site.
  http_response_code($setup_required ? 200 : 503);
  ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $setup_required ? 'Star51 — Setup' : 'Database Error' ?></title>
  <style>
    body {
      font-family: system-ui, -apple-system, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
      background: #f8f9fa;
    }
    .msg {
      text-align: center;
      padding: 40px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,.1);
      max-width: 400px;
    }
    h1 {
      color: #dc3545;
      font-size: 1.3rem;
      margin: 0 0 16px;
    }
    p {
      color: #666;
      margin: 0;
      line-height: 1.8;
    }
    .setup-title { color: #333; }
    a { color: #0d6efd; }
  </style>
</head>
<body>
  <div class="msg">
    <?php if ($setup_required): ?>
      <h1 class="setup-title">Star51 — Setup Required</h1>
      <p>Welcome!<br>Get started by running the<br><a href="<?= htmlspecialchars($setup_url, ENT_QUOTES, 'UTF-8') ?>">installation wizard</a>.</p>
    <?php else: ?>
      <h1>Database Connection Error</h1>
      <p>Please check access credentials.</p>
    <?php endif; ?>
  </div>
</body>
</html>
<?php
  exit();
}

if ($conn) {
  mysqli_set_charset($conn, 'utf8mb4');
}

// ============================================
// END OF MAIN CONFIGURATION
// ============================================
