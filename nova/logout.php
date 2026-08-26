<?php
// Nova Logout System - Destroy the isolated administration session.
require_once 'inc/inc_nova_session_start.php';

$nova_cookie_params = session_get_cookie_params();
$nova_cookie_name = session_name();

session_unset();
session_destroy();

if (ini_get('session.use_cookies')) {
  setcookie($nova_cookie_name, '', [
    'expires' => time() - 42000,
    'path' => $nova_cookie_params['path'],
    'secure' => $nova_cookie_params['secure'],
    'httponly' => $nova_cookie_params['httponly'],
    'samesite' => $nova_cookie_params['samesite']
  ]);
}

header('Location: index.php');
exit();
