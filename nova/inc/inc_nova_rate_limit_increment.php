<?php
// Nova Rate Limiting - INCREMENT failed login attempts
// Simple procedural PHP with English comments - NO FUNCTIONS!
// Database-based tracking for Star51 Team version
//
// Usage: Include this file AFTER a failed login attempt
//
// Optional input variables:
// - $rate_limit_username (string) → Username for logging (optional)
// - $rate_limit_context (string) → Action context for logging (optional, default: login)
//
// Returns these variables:
// - $rate_limit_locked (bool) → Was lockout triggered?
// - $rate_limit_attempts (int) → New attempt count
// - $rate_limit_minutes_remaining (int) → Minutes until unlock (if locked)
// - $rate_limit_message (string) → Formatted lockout message (if locked)

// ============================================================================
// Configuration
// ============================================================================
if (!defined('NOVA_MAX_LOGIN_ATTEMPTS') || !defined('NOVA_LOCKOUT_TIME')) {
  require_once __DIR__ . '/inc_nova_constants.php';
}

// ============================================================================
// Get client IP address, username and context (if provided)
// ============================================================================
$rate_limit_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rate_limit_username = $rate_limit_username ?? '';
$rate_limit_context = $rate_limit_context ?? 'login';
$rate_limit_action = $rate_limit_context === 'password_reset' ? 'Password reset request' : 'Failed login attempt';
$rate_limit_lock_action = $rate_limit_context === 'password_reset' ? 'Password reset locked' : 'IP locked';

// Validate IP address
$rate_limit_ip = filter_var($rate_limit_ip, FILTER_VALIDATE_IP);
if (!$rate_limit_ip) {
    $rate_limit_ip = '0.0.0.0';
    error_log('Nova Rate Limit Increment: Invalid IP address detected');
}

// ============================================================================
// Initialize variables
// ============================================================================
$rate_limit_locked = false;
$rate_limit_attempts = 0;
$rate_limit_minutes_remaining = 0;
$rate_limit_message = '';
$current_time = time();

// ============================================================================
// Check if record exists for this IP
// ============================================================================
$rl_inc_check = "SELECT login_attempts, lockout_until FROM ns_login_security WHERE ip_address = ? LIMIT 1";
$rl_inc_stmt = mysqli_prepare($conn, $rl_inc_check);
mysqli_stmt_bind_param($rl_inc_stmt, 's', $rate_limit_ip);
mysqli_stmt_execute($rl_inc_stmt);
$rl_inc_result = mysqli_stmt_get_result($rl_inc_stmt);

if ($rl_inc_result && mysqli_num_rows($rl_inc_result) > 0) {
    // ========================================================================
    // Record exists - increment attempts
    // ========================================================================
    $rl_inc_record = mysqli_fetch_assoc($rl_inc_result);
    $new_attempts = $rl_inc_record['login_attempts'] + 1;
    $existing_lockout = $rl_inc_record['lockout_until'];

    // Check if we need to trigger lockout
    if ($new_attempts >= NOVA_MAX_LOGIN_ATTEMPTS && $existing_lockout <= $current_time) {
        // ====================================================================
        // TRIGGER LOCKOUT - Max attempts reached
        // ====================================================================
        $lockout_until = $current_time + NOVA_LOCKOUT_TIME;
        $rl_update = "UPDATE ns_login_security SET login_attempts = ?, lockout_until = ?, last_attempt = NOW() WHERE ip_address = ?";
        $rl_update_stmt = mysqli_prepare($conn, $rl_update);
        mysqli_stmt_bind_param($rl_update_stmt, 'iis', $new_attempts, $lockout_until, $rate_limit_ip);
        mysqli_stmt_execute($rl_update_stmt);

        $rate_limit_locked = true;
        $rate_limit_attempts = $new_attempts;
        $rate_limit_minutes_remaining = ceil(NOVA_LOCKOUT_TIME / 60);

        // Format lockout message (i18n)
        if ($rate_limit_minutes_remaining == 1) {
            $rate_limit_message = __admin('rate_limit.lockout_singular');
        } else {
            $rate_limit_message = str_replace('{minutes}', $rate_limit_minutes_remaining, __admin('rate_limit.lockout_plural'));
        }

        // Log lockout event
        $username_log = $rate_limit_username ? " for user '$rate_limit_username'" : '';
        error_log("Nova Rate Limit: $rate_limit_lock_action IP $rate_limit_ip{$username_log} (attempts: $new_attempts)");

        unset($rl_update, $rl_update_stmt);
    } else {
        // ====================================================================
        // Just increment - Not locked yet
        // ====================================================================
        $rl_update = "UPDATE ns_login_security SET login_attempts = ?, last_attempt = NOW() WHERE ip_address = ?";
        $rl_update_stmt = mysqli_prepare($conn, $rl_update);
        mysqli_stmt_bind_param($rl_update_stmt, 'is', $new_attempts, $rate_limit_ip);
        mysqli_stmt_execute($rl_update_stmt);

        $rate_limit_attempts = $new_attempts;
        $remaining = NOVA_MAX_LOGIN_ATTEMPTS - $new_attempts;

        // Log attempt
        $username_log = $rate_limit_username ? " for user '$rate_limit_username'" : '';
        error_log("Nova Rate Limit: $rate_limit_action IP $rate_limit_ip{$username_log} ($new_attempts/" . NOVA_MAX_LOGIN_ATTEMPTS . ", $remaining remaining)");

        unset($rl_update, $rl_update_stmt, $remaining);
    }

    unset($new_attempts, $existing_lockout, $lockout_until);
} else {
    // ========================================================================
    // First failed attempt - Create new record
    // ========================================================================
    $rl_insert = "INSERT INTO ns_login_security (ip_address, login_attempts, lockout_until, last_attempt) VALUES (?, 1, 0, NOW())";
    $rl_insert_stmt = mysqli_prepare($conn, $rl_insert);
    mysqli_stmt_bind_param($rl_insert_stmt, 's', $rate_limit_ip);
    mysqli_stmt_execute($rl_insert_stmt);

    $rate_limit_attempts = 1;

    // Log first attempt
    $username_log = $rate_limit_username ? " for user '$rate_limit_username'" : '';
    error_log("Nova Rate Limit: First $rate_limit_action IP $rate_limit_ip{$username_log}");

    unset($rl_insert, $rl_insert_stmt);
}

// ============================================================================
// Cleanup temporary variables
// ============================================================================
unset($rl_inc_check, $rl_inc_stmt, $rl_inc_result, $rl_inc_record, $username_log, $current_time, $rate_limit_action, $rate_limit_lock_action);
