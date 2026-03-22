<?php
// Install Cleanup Handler
// Removes /install/ directory only — .installed is kept as safety lock
// Super Admin (level 0) only

require_once '../inc/inc_nova_session.php';

// Security: CSRF Protection
if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
  $_SESSION['error'] = __admin('settings.msg.invalid_csrf');
  header('Location: admins_settings.php');
  exit();
}

// Cleanup logic
$install_dir = __DIR__ . '/../../install';
$installed_file = __DIR__ . '/../../.installed';

// Debug logging
error_log("Nova Cleanup: Script started by Admin ID: " . $_SESSION['admin_id']);
error_log("Nova Cleanup: Install dir path: $install_dir (exists: " . (is_dir($install_dir) ? 'YES' : 'NO') . ')');
error_log("Nova Cleanup: Installed file path: $installed_file (exists: " . (file_exists($installed_file) ? 'YES' : 'NO') . ')');

$cleanup_success = [];
$cleanup_errors = [];

// Helper function: Delete directory recursively
function deleteDirectory($dir) {
  if (!is_dir($dir)) {
    error_log("Nova Cleanup: Directory not found: $dir");
    return false;
  }

  $files = array_diff(scandir($dir), ['.', '..']);

  foreach ($files as $file) {
    $path = $dir . '/' . $file;

    if (is_dir($path)) {
      if (!deleteDirectory($path)) {
        error_log("Nova Cleanup: Failed to delete subdirectory: $path");
        return false;
      }
    } else {
      if (!unlink($path)) {
        error_log("Nova Cleanup: Failed to delete file: $path (permissions: " . substr(sprintf('%o', fileperms($path)), -4) . ')');
        return false;
      }
    }
  }

  if (!rmdir($dir)) {
    error_log("Nova Cleanup: Failed to remove directory: $dir (permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . ')');
    return false;
  }

  return true;
}

// 1. Remove /install/ directory
if (is_dir($install_dir)) {
  if (deleteDirectory($install_dir)) {
    $cleanup_success[] = __admin('settings.cleanup.install_removed');
    error_log("Nova: Install directory removed by Admin ID: " . $_SESSION['admin_id']);
  } else {
    $cleanup_errors[] = __admin('settings.cleanup.install_error');
    error_log('Nova: Install cleanup failed - Unable to remove /install/ directory');
  }
} else {
  $cleanup_success[] = __admin('settings.cleanup.install_absent');
}

// Note: .installed lock file is intentionally kept as safety guard
// To reinstall, user must manually delete .installed via FTP/terminal

// Result feedback
if (empty($cleanup_errors)) {
  // Full success
  $_SESSION['success'] = __admin('settings.cleanup.complete') . ' ' . implode('. ', $cleanup_success);
  error_log("Nova: Install cleanup completed successfully by Admin ID: " . $_SESSION['admin_id']);
} else {
  // Partial or full failure
  $message = __admin('settings.cleanup.partial') . ' ';
  if (!empty($cleanup_success)) {
    $message .= 'OK: ' . implode(', ', $cleanup_success) . '. ';
  }
  $message .= 'ERRORS: ' . implode(', ', $cleanup_errors);
  $_SESSION['error'] = $message;
}

// Redirect back to settings
header('Location: admins_settings.php');
exit();
