<?php
// Nova Session Management Include - Modular session system
// Simple procedural PHP with English comments
// Based on Star50 pattern adapted for Nova session structure

// Shared session startup for both pre-auth and protected Nova pages.
require_once __DIR__ . '/inc_nova_session_start.php';

// Database connection and dynamic Nova paths required by protected sessions.
require_once dirname(__DIR__) . '/legas/nova_config.php';

// Session Timeout - Auto logout after 1 hour of inactivity (only for logged users)
$timeout_duration = 3600; // 1 hour (3600 seconds)
if (isset($_SESSION['nova_logged']) && $_SESSION['nova_logged'] === true) {
  // Only apply timeout to authenticated sessions
  if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Session expired - cleanup and redirect to login
    session_unset();
    session_destroy();
    header('Location: ' . NOVA_BASE_PATH . '/index.php?timeout=1');
    exit();
  }
  // Update last activity timestamp
  $_SESSION['last_activity'] = time();
}

// Session validation - redirect to login if not authenticated
if (!isset($_SESSION['nova_logged']) || $_SESSION['nova_logged'] !== true) {
  header('Location: ' . NOVA_BASE_PATH . '/index.php');
  exit();
}

// Get admin data from session with defensive programming
$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0;
$admin_username = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : '';
$admin_first_name = isset($_SESSION['admin_first_name']) ? $_SESSION['admin_first_name'] : 'Guest';
$admin_last_name = isset($_SESSION['admin_last_name']) ? $_SESSION['admin_last_name'] : 'User';

// Solo Edition: admin_level must be 0 (single Super Admin)
if (!isset($_SESSION['admin_level']) || $_SESSION['admin_level'] !== 0) {
  // Invalid or missing level - force logout (corrupted/attacked session)
  session_unset();
  session_destroy();
  header('Location: ' . NOVA_BASE_PATH . '/index.php');
  exit();
}

// Confirm that the authenticated Solo administrator still exists and is active.
$active_admin_query = 'SELECT id_admin FROM ns_admins WHERE id_admin = ? AND is_active = 1 LIMIT 1';
$active_admin_stmt = mysqli_prepare($conn, $active_admin_query);
mysqli_stmt_bind_param($active_admin_stmt, 'i', $admin_id);
mysqli_stmt_execute($active_admin_stmt);
$active_admin_result = mysqli_stmt_get_result($active_admin_stmt);

if (!$active_admin_result || mysqli_num_rows($active_admin_result) !== 1) {
  session_unset();
  session_destroy();
  header('Location: ' . NOVA_BASE_PATH . '/index.php');
  exit();
}

$admin_full_name = $admin_first_name . ' ' . $admin_last_name;

// CSRF Token Generation - Protection against Cross-Site Request Forgery
// Generate a unique random token per session for form validation
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// System configuration constants (dimensions, limits, paths, etc.)
require_once __DIR__ . "/inc_nova_constants.php";

// Language loader (i18n system)
require_once __DIR__ . "/inc_nova_lang.php";

/**
 * Safe redirect using HTTP_REFERER with same-host validation
 * Returns referer only if it belongs to the current host, otherwise fallback
 *
 * @param string $fallback Default redirect path if referer is missing or external
 * @return string Safe redirect URL
 */
function nova_safe_redirect($fallback)
{
  $referer = $_SERVER['HTTP_REFERER'] ?? '';
  $referer_host = preg_replace('/:\d+$/', '', (string)(parse_url($referer, PHP_URL_HOST) ?? ''));
  $current_host = preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));

  if ($referer !== '' && $referer_host === $current_host) {
    return $referer;
  }
  return $fallback;
}
