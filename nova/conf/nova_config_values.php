<?php
// ============================================
// STAR51 SOLO — SYSTEM CONFIGURATION
// ============================================
// Edit this file manually to change settings
// ============================================

// Prevent multiple inclusions
if (defined('NOVA_CONFIG_VALUES_LOADED')) {
    return;
}
define('NOVA_CONFIG_VALUES_LOADED', true);

// Configuration array
$nova_settings = array (
  'nova_lang' => 'en',
  'gallery_h_width' => 800,
  'gallery_h_height' => 600,
  'gallery_v_width' => 600,
  'gallery_v_height' => 800,
  'resize_med_percent' => 50,
  'resize_min_percent' => 25,
  'site_name' => 'Star51',
  'admin_name' => 'Nova01',
  'records_per_page' => 20,
  'db_host_local' => '',
  'db_user_local' => '',
  'db_pass_local' => '',
  'db_name_local' => '',
  'db_host_remote' => '',
  'db_user_remote' => '',
  'db_pass_remote' => '',
  'db_name_remote' => '',
  'smtp_host' => 'smtp.gmail.com',
  'smtp_port' => 587,
  'smtp_user' => '',
  'smtp_pass' => '',
  'site_url' => '',
  'image_quality' => 100,
  'max_file_size_mb' => 3,
);

// ============================================
// END OF CONFIGURATION VALUES
// ============================================
?>