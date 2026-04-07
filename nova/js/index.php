<?php
// Nova Directory Protection System - Session Aware
// This system prevents unauthorized directory scanning by intelligently routing requests.

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

// Check if the session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Smart redirect based on authentication status
if (!isset($_SESSION['nova_logged']) || $_SESSION['nova_logged'] !== true) {
    // If not logged in, redirect to the login page
    header('Location: ../index.php');
} else {
    // If logged in, but accessing a protected directory, redirect to the dashboard
    header('Location: ../home.php');
}

// Exit the script
exit();
