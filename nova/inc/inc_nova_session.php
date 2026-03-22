<?php
// Nova Session Management Include - Modular session system
// Simple procedural PHP with English comments
// Based on Star50 pattern adapted for Nova session structure

// Session Configuration & Start - Security hardened
if (session_status() === PHP_SESSION_NONE) {
  // Set secure cookie parameters before session start
  session_set_cookie_params([
    'lifetime' => 0,           // Session cookie (expires when browser closes)
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // HTTPS only (if available)
    'httponly' => true,        // No JavaScript access (XSS protection)
    'samesite' => 'Strict'     // CSRF protection
  ]);
  session_start();
}

// Session Timeout - Auto logout after 1 hour of inactivity (only for logged users)
$timeout_duration = 3600; // 1 hour (3600 seconds)
if (isset($_SESSION['nova_logged']) && $_SESSION['nova_logged'] === true) {
  // Only apply timeout to authenticated sessions
  if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Session expired - cleanup and redirect to login
    session_unset();
    session_destroy();
    header('Location: index.php?timeout=1');
    exit();
  }
  // Update last activity timestamp
  $_SESSION['last_activity'] = time();
}

// Session validation - redirect to login if not authenticated
if (!isset($_SESSION['nova_logged']) || $_SESSION['nova_logged'] !== true) {
  header('Location: index.php');
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
  header('Location: index.php');
  exit();
}

$admin_full_name = $admin_first_name . ' ' . $admin_last_name;

// CSRF Token Generation - Protection against Cross-Site Request Forgery
// Generate a unique random token per session for form validation
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection required for protected pages
require_once dirname(__DIR__) . "/legas/nova_config.php";

// System configuration constants (dimensions, limits, paths, etc.)
require_once __DIR__ . "/inc_nova_constants.php";

// Language loader (i18n system)
require_once __DIR__ . "/inc_nova_lang.php";

// Security headers for admin pages
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

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
  if ($referer !== '' && parse_url($referer, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')) {
    return $referer;
  }
  return $fallback;
}
?>
