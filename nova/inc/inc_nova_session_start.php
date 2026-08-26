<?php

// Start the isolated Nova session with one shared cookie policy.
if (session_status() === PHP_SESSION_NONE) {
  $nova_script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/nova/index.php');
  $nova_path_marker = '/nova/';
  $nova_path_position = strpos($nova_script_name, $nova_path_marker);
  $nova_cookie_path = $nova_path_position === false
    ? '/nova/'
    : substr($nova_script_name, 0, $nova_path_position + strlen($nova_path_marker));

  ini_set('session.use_only_cookies', '1');
  ini_set('session.use_strict_mode', '1');
  session_name('NOVASESSID');
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => $nova_cookie_path,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Strict'
  ]);
  session_start();

  unset($nova_script_name, $nova_path_marker, $nova_path_position, $nova_cookie_path);
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
