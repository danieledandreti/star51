<?php
// Nova Rate Limiting - RESET attempts after successful login
// Simple procedural PHP with English comments - NO FUNCTIONS!
// Database-based tracking for Star51 Team version
//
// Usage: Include this file AFTER a successful login
//
// Optional input variable:
// - $rate_limit_username (string) → Username for logging (optional)
//
// Returns these variables:
// - $rate_limit_reset_success (bool) → Was reset successful?

// ============================================================================
// Get client IP address and username (if provided)
// ============================================================================
$rate_limit_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rate_limit_username = $rate_limit_username ?? '';

// Validate IP address
$rate_limit_ip = filter_var($rate_limit_ip, FILTER_VALIDATE_IP);
if (!$rate_limit_ip) {
    $rate_limit_ip = '0.0.0.0';
    error_log('Nova Rate Limit Reset: Invalid IP address detected');
}

// ============================================================================
// Initialize variables
// ============================================================================
$rate_limit_reset_success = false;

// ============================================================================
// Delete security record for this IP (clean slate)
// ============================================================================
$rl_delete = "DELETE FROM ns_login_security WHERE ip_address = ?";
$rl_delete_stmt = mysqli_prepare($conn, $rl_delete);
mysqli_stmt_bind_param($rl_delete_stmt, 's', $rate_limit_ip);
$rate_limit_reset_success = mysqli_stmt_execute($rl_delete_stmt);

if ($rate_limit_reset_success) {
    // Log successful reset
    $username_log = $rate_limit_username ? " for user '$rate_limit_username'" : '';
    error_log("Nova Rate Limit: Login success IP $rate_limit_ip{$username_log} - attempts reset");
}

// ============================================================================
// Cleanup temporary variables
// ============================================================================
unset($rl_delete, $rl_delete_stmt, $username_log);
?>
