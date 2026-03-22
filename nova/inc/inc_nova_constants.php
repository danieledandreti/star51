<?php
// ============================================
// NOVA SYSTEM CONFIGURATION
// ============================================
// Central configuration file for Star51 Nova Admin
// All dimensions, sizes, limits, and system settings
//
// Created: October 2025
// Philosophy: Single Source of Truth - Change once, works everywhere
// ============================================

// Prevent multiple inclusions
if (defined('NOVA_CONSTANTS_LOADED')) {
    return;
}
define('NOVA_CONSTANTS_LOADED', true);

// ============================================
// LOAD CONFIGURATION VALUES
// ============================================
// Super Admin editable values loaded from separate file
require_once __DIR__ . '/../conf/nova_config_values.php';

// ========================================
// IMAGE DIMENSIONS - ARTICLES
// ========================================

// Gallery Images (image_1, image_2) - Horizontal OR Vertical
define('NOVA_GALLERY_H_WIDTH', $nova_settings['gallery_h_width']);      // Super Admin editable
define('NOVA_GALLERY_H_HEIGHT', $nova_settings['gallery_h_height']);    // Auto-calculated (ratio 4:3)
define('NOVA_GALLERY_V_WIDTH', $nova_settings['gallery_v_width']);      // Super Admin editable
define('NOVA_GALLERY_V_HEIGHT', $nova_settings['gallery_v_height']);    // Auto-calculated (ratio 3:4)

// ========================================
// IMAGE PROCESSING
// ========================================
define('NOVA_IMAGE_QUALITY', $nova_settings['image_quality']);  // Always 100 (best quality)
define('NOVA_IMAGE_FORMAT', 'jpg');                             // Output format (jpg only)

// Resize dimensions for GALLERY (image_1, image_2) - calculated from width + percentages
define('NOVA_GALLERY_RESIZE_MED', (int)round(NOVA_GALLERY_H_WIDTH * $nova_settings['resize_med_percent'] / 100));
define('NOVA_GALLERY_RESIZE_MIN', (int)round(NOVA_GALLERY_H_WIDTH * $nova_settings['resize_min_percent'] / 100));

// Allowed image MIME types (JPG only)
define('NOVA_ALLOWED_IMAGE_MIMES', [
    'image/jpeg',
    'image/jpg'
]);

// ========================================
// FILE PERMISSIONS
// ========================================
define('NOVA_FILE_CHMOD', 0777);       // Permissions after upload (rwxrwxrwx)
define('NOVA_DIR_CHMOD', 0755);        // Directory permissions (rwxr-xr-x)

// ========================================
// FILE STORAGE PATHS
// ========================================
// Dynamic paths using dirname(__DIR__) - robust and portable
// __DIR__ = /nova/inc, dirname(__DIR__) = /nova, dirname(dirname(__DIR__)) = /project_root
define('NOVA_PATH_FILE_MAX', dirname(dirname(__DIR__)) . '/file_db_max/');  // Original/large files
define('NOVA_PATH_FILE_MED', dirname(dirname(__DIR__)) . '/file_db_med/');  // Medium size images
define('NOVA_PATH_FILE_MIN', dirname(dirname(__DIR__)) . '/file_db_min/');  // Thumbnails/small images

// ========================================
// DATABASE CONFIGURATION
// ========================================
define('NOVA_YEAR_MIN', 1901);         // MySQL YEAR type minimum
define('NOVA_YEAR_MAX', 2155);         // MySQL YEAR type maximum

// ========================================
// VALIDATION LIMITS
// ========================================
define('NOVA_TITLE_MAX_LENGTH', 255);          // Article/Category title max length
define('NOVA_COLLECTION_MAX_LENGTH', 255);     // item_collection max length
define('NOVA_PASSWORD_MIN_LENGTH', 8);         // Password minimum length

// ========================================
// PAGINATION
// ========================================
define('NOVA_RECORDS_PER_PAGE', $nova_settings['records_per_page']);   // Super Admin editable (10-100)

// ========================================
// SYSTEM INFO
// ========================================
define('NOVA_SITE_NAME', $nova_settings['site_name']);         // Super Admin editable (for <title>, footer, etc)

// ========================================
// RATE LIMITING
// ========================================
define('NOVA_MAX_LOGIN_ATTEMPTS', 5);    // Max attempts before lockout
define('NOVA_LOCKOUT_TIME', 900);        // 15 minutes (900 seconds)

// ========================================
// DATE FORMAT
// ========================================
define('NOVA_DATE_FORMAT', 'd-m-Y');       // Base date format (use . ', H:i:s' for full timestamp)

// ========================================
// RESERVED IDs (System Categories/Subcategories)
// ========================================
// Guards prevent redefinition if frontend inc_reserved_ids.php is loaded too
if (!defined('CATEGORY_EXTRA')) {
    define('CATEGORY_EXTRA', 1);         // Backup container for orphaned articles
}
if (!defined('CATEGORY_INFO')) {
    define('CATEGORY_INFO', 2);          // System category (News)
}
if (!defined('SUBCATEGORY_VARIE')) {
    define('SUBCATEGORY_VARIE', 1);      // Default orphan container
}
if (!defined('SUBCATEGORY_NEWS')) {
    define('SUBCATEGORY_NEWS', 2);       // News/Updates
}

// ========================================
// END OF CONFIGURATION
// ========================================
// Pure procedural PHP - constants only, no functions
// Keep it simple: define() values used directly in code
?>
