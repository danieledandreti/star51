<?php
// Log Archive Handler
// Archives error.log with timestamp and creates new empty log file
// Super Admin (level 0) only

require_once '../inc/inc_nova_session.php';

// Security: CSRF Protection
if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
  $_SESSION['error'] = __admin('settings.msg.invalid_csrf');
  header('Location: admins_settings.php');
  exit();
}

// Log archive logic
$log_file = NOVA_LOG_PATH;
$log_dir = dirname($log_file);

// Check if log file exists
if (!file_exists($log_file)) {
  $_SESSION['error'] = __admin('settings.log.not_found');
  header('Location: admins_settings.php');
  exit();
}

// Check if file is empty (no need to archive)
if (filesize($log_file) === 0) {
  $_SESSION['error'] = __admin('settings.log.empty');
  header('Location: admins_settings.php');
  exit();
}

// Generate archive filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$archive_filename = 'error_' . $timestamp . '.log';
$archive_path = $log_dir . '/' . $archive_filename;

// Rename current log to archived version
if (!rename($log_file, $archive_path)) {
  $_SESSION['error'] = __admin('settings.log.archive_error');
  error_log("Nova: Log archive failed - Unable to rename error.log to $archive_filename");
  header('Location: admins_settings.php');
  exit();
}

// Create new empty error.log file
if (!touch($log_file)) {
  $_SESSION['error'] = __admin('settings.log.archive_partial');
  error_log('Nova: Log archive partial success - Archive created but new error.log creation failed');
  header('Location: admins_settings.php');
  exit();
}

// Set proper permissions for new log file (writable)
chmod($log_file, 0644);

// Log the action
error_log("Nova: Log archived successfully - File: $archive_filename by Admin ID: " . $_SESSION['admin_id']);

// Success message
$_SESSION['success'] = __admin('settings.log.archive_success') . " <strong>$archive_filename</strong>";

header('Location: admins_settings.php');
exit();
