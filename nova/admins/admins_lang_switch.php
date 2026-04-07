<?php
/**
 * Quick Language Switch
 * Changes system language via GET parameter and saves to config
 *
 * Usage: admins_lang_switch.php?lang=en&redirect=/nova/home.php
 */

require_once '../inc/inc_nova_session.php';
require_once '../inc/inc_nova_settings_writer.php';

// Get and validate language parameter
$new_lang = $_GET['lang'] ?? 'it';
if (!in_array($new_lang, ['it', 'en'], true)) {
  $new_lang = 'it';
}

// Load current settings
include '../conf/nova_config_values.php';

// Update only the language setting
$nova_settings['nova_lang'] = $new_lang;

// Save config file (no backup needed for simple lang switch)
$result = nova_save_config_values($nova_settings, $_SESSION['admin_username'] ?? 'system', false);

// Redirect back — only allow safe internal paths
$redirect = $_GET['redirect'] ?? '../home.php';

// Security: strip newlines (prevent header injection), reject external URLs
$redirect = str_replace(["\r", "\n"], '', $redirect);

// Block protocol URLs (http://, https://, javascript:, //) and data: URIs
if (preg_match('#^(https?:|javascript:|data:|//)#i', $redirect)) {
  $redirect = '../home.php';
}

header('Location: ' . $redirect);
exit();
