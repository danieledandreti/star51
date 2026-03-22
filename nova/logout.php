<?php
// Nova Logout System - Simple procedural PHP with English comments
// Based on Star50 pattern but modernized for Nova session structure

// Start output buffering to prevent header issues
ob_start();

// Start session to access session variables
session_start();

// Regenerate session ID for security (prevent session fixation attacks)
session_regenerate_id(true);

// Check if Nova session exists and destroy it
if (isset($_SESSION['nova_logged']) && $_SESSION['nova_logged'] === true) {
  // Clear all session variables
  session_unset();

  // Destroy the session completely
  session_destroy();

  // Redirect to login page
  header('Location: index.php');
  exit();
} else {
  // If no valid session exists, still redirect to login
  header('Location: index.php');
  exit();
}

// End output buffering and send output
ob_end_flush();
