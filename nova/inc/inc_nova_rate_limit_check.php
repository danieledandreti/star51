<?php
// Nova Rate Limiting - CHECK if IP is locked
// Simple procedural PHP with English comments - NO FUNCTIONS!
// Database-based tracking for Star51 Solo
//
// Usage: Include this file to check if current IP is locked out
//
// Returns these variables:
// - $rate_limit_locked (bool) → Is IP currently locked?
// - $rate_limit_attempts (int) → Current attempt count
// - $rate_limit_minutes_remaining (int) → Minutes until unlock
// - $rate_limit_message (string) → Formatted lockout message

// ============================================================================
// Configuration
// ============================================================================
// ============================================================================
// Get client IP address
// ============================================================================
$rate_limit_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Validate IP address
$rate_limit_ip = filter_var($rate_limit_ip, FILTER_VALIDATE_IP);
if (!$rate_limit_ip) {
    $rate_limit_ip = '0.0.0.0';
    error_log('Nova Rate Limit Check: Invalid IP address detected');
}

// ============================================================================
// Initialize variables
// ============================================================================
$rate_limit_locked = false;
$rate_limit_attempts = 0;
$rate_limit_unlock_time = 0;
$rate_limit_minutes_remaining = 0;
$rate_limit_message = '';
$current_time = time();

// ============================================================================
// Query database for lockout status
// ============================================================================
$rl_check_query = "SELECT login_attempts, lockout_until FROM ns_login_security WHERE ip_address = ? LIMIT 1";
$rl_check_stmt = mysqli_prepare($conn, $rl_check_query);
mysqli_stmt_bind_param($rl_check_stmt, 's', $rate_limit_ip);
mysqli_stmt_execute($rl_check_stmt);
$rl_check_result = mysqli_stmt_get_result($rl_check_stmt);

if ($rl_check_result && mysqli_num_rows($rl_check_result) > 0) {
    // Record found - check lockout status
    $rl_record = mysqli_fetch_assoc($rl_check_result);
    $rate_limit_attempts = $rl_record['login_attempts'];
    $rate_limit_unlock_time = $rl_record['lockout_until'];

    // Check if lockout is currently active
    if ($rate_limit_unlock_time > $current_time) {
        // IP is locked out
        $rate_limit_locked = true;
        $seconds_remaining = $rate_limit_unlock_time - $current_time;
        $rate_limit_minutes_remaining = ceil($seconds_remaining / 60);

        // Format message with proper pluralization
        if ($rate_limit_minutes_remaining == 1) {
            $rate_limit_message = __admin('rate_limit.lockout_singular');
        } else {
            $rate_limit_message = str_replace('{minutes}', $rate_limit_minutes_remaining, __admin('rate_limit.lockout_plural'));
        }
    }
}

// ============================================================================
// Cleanup temporary variables
// ============================================================================
unset($rl_check_query, $rl_check_stmt, $rl_check_result, $rl_record, $current_time, $seconds_remaining);
?>
