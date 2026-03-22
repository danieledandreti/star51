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
$local_hosts = ["127.0.0.1", "localhost", "gomes.local", "god.local"];
$is_local = in_array($_SERVER["HTTP_HOST"] ?? "unknown", $local_hosts);

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
if ($is_local) {
  // Local: auto-detect project folder from script path
  // Example: /Users/daniele/Sites/star51/nova/index.php → /star51/nova
  $script_path = $_SERVER["SCRIPT_NAME"] ?? "";
  $path_parts = explode("/", $script_path);
  $project_folder = $path_parts[1] ?? "star51"; // Fallback to 'star51'

  define("NOVA_WEB_PATH", "/{$project_folder}/nova");
  define("NOVA_BASE_PATH", "/{$project_folder}/nova");
} else {
  // Remote: always from web root
  define("NOVA_WEB_PATH", "/nova");
  define("NOVA_BASE_PATH", "/nova");
}

// Backward compatibility variables
$nova_web_path = NOVA_WEB_PATH;
$nova_base = NOVA_BASE_PATH;

// ============================================
// DATABASE CONNECTION
// ============================================

// Database connection with friendly error handling (PHP 8+)
try {
  $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
} catch (mysqli_sql_exception $e) {
  // Fresh install detection
  $not_installed = file_exists(__DIR__ . "/../../install/index.php") && !file_exists(__DIR__ . "/../../.installed");

  // Log error only if not a fresh install (avoid noise pre-install)
  if (!$not_installed) {
    error_log("[" . date("Y-m-d H:i:s") . "] DB Error: " . $e->getMessage() . " - Host: " . DB_HOST . " - User: " . DB_USER);
  }

  // User-friendly error page — no sensitive info
  http_response_code($not_installed ? 200 : 503);
  ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $not_installed ? "Star51 — Setup" : "Database Error" ?></title>
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
      font-size: 1.3rem;
      margin: 0 0 16px;
    }
    p {
      color: #666;
      margin: 0;
      line-height: 1.8;
    }
    a { color: #0d6efd; }
  </style>
</head>
<body>
  <div class="msg">
  <?php if ($not_installed): ?>
    <h1 style="color:#333">Star51 — Setup Required</h1>
    <p>Welcome!<br>Get started by running the<br><a href="install/">installation wizard</a>.</p>
  <?php else: ?>
    <h1 style="color:#dc3545">Database Connection Error</h1>
    <p>Please check access credentials.</p>
  <?php endif; ?>
  </div>
</body>
</html>
<?php exit();
}

if ($conn) {
  mysqli_set_charset($conn, "utf8mb4");
}

// ============================================
// END OF MAIN CONFIGURATION
// ============================================
