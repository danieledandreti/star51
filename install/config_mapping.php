<?php
/**
 * Setup Wizard Configuration Mapping
 *
 * Centralized variables - NO hardcoding in main wizard
 * All paths, constants, and settings in one place
 *
 * Created: 11 November 2025 - Session 29
 *
 * Philosophy: "Zero hardcoding, maximum flexibility"
 */

return [
  // ============================================
  // DATABASE CONFIGURATION
  // ============================================
  'db' => [
    'prefix' => 'ns_', // Table prefix (always ns_)
    'min_version' => '8.0.0', // Minimum MySQL version
    'port' => 3306, // Default MySQL port
    'charset' => 'utf8mb4', // Database charset
    'required_tables' => [
      // Tables that will be created
      'ns_admins',
      'ns_articles',
      'ns_categories',
      'ns_subcategories',
      'ns_requests',
      'ns_login_security',
    ],
  ],

  // ============================================
  // FILE PATHS (relative to /install/)
  // ============================================
  'paths' => [
    'sql' => 'star51_solo.sql', // SQL schema file
    'config_values' => '../nova/conf/nova_config_values.php', // Central config file (Session 52, moved to conf/ Session 57)
    'lock' => '../.installed', // Installation lock file
    'nova_login' => '../nova/index.php', // Nova login page
    'htaccess_root' => '_htaccess_root', // .htaccess template for project root
    'robots_txt' => '../robots.txt', // robots.txt output path
    'sitemap_xml' => '../sitemap.xml', // sitemap.xml output path
    'htaccess_dest' => '../.htaccess', // .htaccess output path
    'writable' => [
      // Directories that must be writable
      '../nova/conf', // For config_values (Session 57 - moved from inc/)
      '../nova/logs',
      '../file_db_max',
      '../file_db_med',
      '../file_db_min',
    ],
  ],

  // ============================================
  // SUPER ADMIN DEFAULTS
  // ============================================
  'admin' => [
    'level' => 0, // 0=Super Admin, 1=Admin, 2=Editor, 3=Operator
    'active' => 1, // Active by default
    'created_by' => 0, // Self-created (0 = system/installer)
  ],

  // ============================================
  // PASSWORD POLICY
  // ============================================
  'password' => [
    'min_length' => 8, // Minimum password length
    'temp_length' => 12, // Auto-generated temp password length
    'temp_charset' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789', // No ambiguous chars (no 0/O, 1/I)
  ],

  // ============================================
  // EMAIL CONFIGURATION (Gmail SMTP)
  // ============================================
  'email' => [
    'host' => 'smtp.gmail.com', // SMTP host
    'port' => 587, // SMTP port (TLS)
    'encryption' => 'tls', // Encryption type
    'from_name' => 'Star51 Nova', // Email sender name
  ],

  // ============================================
  // SYSTEM REQUIREMENTS
  // ============================================
  'requirements' => [
    'php_min' => '8.0.0', // Minimum PHP version
    'extensions' => [
      // Required/recommended PHP extensions
      'mysqli', // Database (required)
      'gd', // Image processing
      'json', // JSON support
      'fileinfo', // File type detection
      'mbstring', // Multibyte string support
    ],
  ],

  // ============================================
  // SEO / SITE CONFIGURATION
  // ============================================
  'seo' => [
    // Static pages included in sitemap.xml (Solo edition)
    'sitemap_pages' => [
      ['page' => 'index.php', 'priority' => '1.0', 'changefreq' => 'weekly'],
      ['page' => 'articles.php', 'priority' => '0.8', 'changefreq' => 'weekly'],
      ['page' => 'news.php', 'priority' => '0.7', 'changefreq' => 'weekly'],
      ['page' => 'about.php', 'priority' => '0.5', 'changefreq' => 'monthly'],
      ['page' => 'contact.php', 'priority' => '0.5', 'changefreq' => 'monthly'],
      ['page' => 'search.php', 'priority' => '0.4', 'changefreq' => 'monthly'],
      ['page' => 'policy.php', 'priority' => '0.3', 'changefreq' => 'yearly'],
    ],
    // Directories blocked in robots.txt
    'robots_disallow' => [
      '/nova/',
      '/inc/',
      '/install/',
      '/file_db_max/',
      '/file_db_med/',
      '/file_db_min/',
    ],
  ],

  // ============================================
  // UI/UX SETTINGS
  // ============================================
  'ui' => [
    'wizard_title' => 'Star51 Express Install',
    'wizard_subtitle' => 'Fast setup for developers and testing labs',
    'bootstrap_version' => '5.3.8',
    'bootstrap_icons_version' => '1.13.1',
  ],
];
