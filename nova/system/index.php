<?php
// Nova Directory Protection System - Session Aware
// Prevents unauthorized directory scanning by intelligently routing requests.

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

// Check if the session is active
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Smart redirect based on authentication status
if (!isset($_SESSION['nova_logged']) || $_SESSION['nova_logged'] !== true) {
  // Not logged in - redirect to login page
  header('Location: ../index.php');
} else {
  // Logged in but accessing protected directory - redirect to dashboard
  header('Location: ../home.php');
}

exit();
