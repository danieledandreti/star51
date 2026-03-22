<?php
/**
 * System Detection - Auto-detect PHP, MySQL, Extensions
 *
 * Provides system capability information for the installer
 * Used to show upfront what's available and what's missing
 *
 * Created: 11 November 2025 - Session 29
 */

/**
 * Detect system capabilities
 *
 * @return array System information
 */
function detect_system()
{
  $config = require 'config_mapping.php';

  // PHP Version Check
  $php_ok = version_compare(
    PHP_VERSION,
    $config['requirements']['php_min'],
    '>='
  );

  // Extensions Check
  $extensions_list = [];
  foreach ($config['requirements']['extensions'] as $ext) {
    $extensions_list[$ext] = extension_loaded($ext);
  }
  $extensions_ok = count(array_filter($extensions_list));
  $extensions_total = count($extensions_list);

  // Writable Directories Check
  $permissions = check_writable_dirs($config['paths']['writable']);
  $writable_ok = 0;
  $writable_total = count($permissions);
  foreach ($permissions as $perm) {
    if ($perm['writable']) {
      $writable_ok++;
    }
  }

  return [
    'php' => [
      'version' => PHP_VERSION,
      'ok' => $php_ok,
      'required' => $config['requirements']['php_min'],
      'sapi' => php_sapi_name(),
    ],
    'extensions' => [
      'list' => $extensions_list,
      'all_ok' => $extensions_ok === $extensions_total,
      'ok_count' => $extensions_ok,
      'total' => $extensions_total,
    ],
    'writable' => [
      'list' => $permissions,
      'all_ok' => $writable_ok === $writable_total,
      'ok_count' => $writable_ok,
      'total' => $writable_total,
    ],
    'permissions' => $permissions, // Keep for backwards compatibility
    'mysql_available' => extension_loaded('mysqli'),
    'all_ok' => $php_ok && $extensions_list['mysqli'] && $writable_ok === $writable_total,
  ];
}

/**
 * Check if directories are writable
 *
 * @param array $dirs Directories to check
 * @return array Map of directory => writable status
 */
function check_writable_dirs($dirs)
{
  $results = [];

  foreach ($dirs as $dir) {
    // Clean path for display (remove ../)
    $display_path = str_replace('../', '', $dir);

    // Check if directory exists and is writable
    if (!file_exists($dir)) {
      $results[$display_path] = [
        'writable' => false,
        'exists' => false,
        'message' => 'Directory does not exist',
      ];
    } elseif (!is_dir($dir)) {
      $results[$display_path] = [
        'writable' => false,
        'exists' => true,
        'message' => 'Path exists but is not a directory',
      ];
    } else {
      $is_writable = is_writable($dir);
      $results[$display_path] = [
        'writable' => $is_writable,
        'exists' => true,
        'message' => $is_writable ? 'OK' : 'Not writable (chmod 755 or 777 needed)',
      ];
    }
  }

  return $results;
}

/**
 * Display system info as HTML alert box
 *
 * @param array $info System information from detect_system()
 * @return string HTML alert box
 */
function display_system_info($info)
{
  // Determine alert type
  $critical_errors = !$info['php']['ok'] || !$info['mysql_available'];
  $has_warnings = false;

  // Use new structure
  $extensions_ok = $info['extensions']['ok_count'];
  $extensions_total = $info['extensions']['total'];
  $permissions_ok = $info['writable']['ok_count'];
  $permissions_total = $info['writable']['total'];

  if ($extensions_ok < $extensions_total || $permissions_ok < $permissions_total) {
    $has_warnings = true;
  }

  $alert_type = $critical_errors ? 'danger' : ($has_warnings ? 'warning' : 'success');
  $icon = $critical_errors ? '❌' : ($has_warnings ? '⚠️' : '✅');

  ob_start();
  ?>
    <div class="alert alert-<?= $alert_type ?>">
        <h6><?= $icon ?> System Requirements</h6>
        <div style="font-size: 0.9rem;">

            <!-- PHP Version -->
            <div class="mb-2">
                <?php if ($info['php']['ok']): ?>
                    ✅ <strong>PHP <?= htmlspecialchars($info['php']['version']) ?></strong>
                    <small class="text-muted">(required: <?= htmlspecialchars($info['php']['required']) ?>+)</small>
                <?php else: ?>
                    ❌ <strong>PHP <?= htmlspecialchars($info['php']['version']) ?></strong>
                    <small class="text-danger">(required: <?= htmlspecialchars($info['php']['required']) ?>+)</small>
                <?php endif; ?>
            </div>

            <!-- MySQL Extension -->
            <div class="mb-2">
                <?php if ($info['mysql_available']): ?>
                    ✅ <strong>MySQL extension available</strong>
                <?php else: ?>
                    ❌ <strong>MySQL extension missing</strong> <small>(enable mysqli in php.ini)</small>
                <?php endif; ?>
            </div>

            <!-- Extensions -->
            <div class="mb-2">
                <?php if ($extensions_ok === $extensions_total): ?>
                    ✅ <strong>All extensions available</strong>
                    <small class="text-muted">(<?= $extensions_total ?>/<?= $extensions_total ?>)</small>
                <?php else: ?>
                    ⚠️ <strong>Extensions:</strong>
                    <?= $extensions_ok ?>/<?= $extensions_total ?> available
                    <small class="text-muted">
                        (Missing:
                        <?php
                        $missing = [];
                        foreach ($info['extensions']['list'] as $ext => $loaded) {
                          if (!$loaded) {
                            $missing[] = $ext;
                          }
                        }
                        echo htmlspecialchars(implode(', ', $missing));
                        ?>)
                    </small>
                <?php endif; ?>
            </div>

            <!-- Permissions -->
            <div>
                <?php if ($permissions_ok === $permissions_total): ?>
                    ✅ <strong>All directories writable</strong>
                    <small class="text-muted">(<?= $permissions_total ?>/<?= $permissions_total ?>)</small>
                <?php else: ?>
                    ⚠️ <strong>Directory permissions:</strong>
                    <?= $permissions_ok ?>/<?= $permissions_total ?> writable

                    <!-- Show details of non-writable dirs -->
                    <div class="mt-2" style="font-size: 0.85rem;">
                        <?php foreach ($info['writable']['list'] as $dir => $perm): ?>
                            <?php if (!$perm['writable']): ?>
                                <div class="text-danger">
                                    • <code><?= htmlspecialchars($dir) ?></code>:
                                    <?= htmlspecialchars($perm['message']) ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($critical_errors): ?>
                <hr>
                <div class="text-danger">
                    <strong>⚠️ Cannot continue:</strong> Fix critical errors above before installing.
                </div>
            <?php endif; ?>

        </div>
    </div>
    <?php return ob_get_clean();
}

/**
 * Set writable permissions (chmod 777) on required directories
 * Called after successful installation to ensure uploads and config work
 *
 * @param array $dirs Directories to chmod (from config['paths']['writable'])
 * @return array Results with success/failure for each directory
 */
function set_writable_permissions($dirs)
{
  $results = [];

  foreach ($dirs as $dir) {
    // Clean path for display (remove ../)
    $display_path = str_replace('../', '', $dir);

    if (!file_exists($dir)) {
      // Try to create directory if it doesn't exist
      if (@mkdir($dir, 0777, true)) {
        $results[$display_path] = [
          'success' => true,
          'message' => 'Created with 777',
        ];
      } else {
        $results[$display_path] = [
          'success' => false,
          'message' => 'Could not create directory',
        ];
      }
    } elseif (is_dir($dir)) {
      // Directory exists, try to chmod
      if (@chmod($dir, 0777)) {
        $results[$display_path] = [
          'success' => true,
          'message' => 'Set to 777',
        ];
      } else {
        $results[$display_path] = [
          'success' => false,
          'message' => 'chmod failed (may need manual: chmod 777 ' . $display_path . ')',
        ];
      }
    } else {
      $results[$display_path] = [
        'success' => false,
        'message' => 'Path exists but is not a directory',
      ];
    }
  }

  return $results;
}

/**
 * Get suggested database name based on folder
 *
 * @return string Suggested database name
 */
function suggest_db_name()
{
  $parent_dir = basename(dirname(__DIR__));
  $web_roots = ['public_html', 'www', 'htdocs', 'web', 'html'];

  // If in common web root folder, use generic name
  if (in_array($parent_dir, $web_roots)) {
    return 'novastar51';
  }

  // Otherwise, use folder name (sanitized)
  return 'nova' . preg_replace('/[^a-z0-9_]/', '_', strtolower($parent_dir));
}
