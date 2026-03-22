<?php
// Nova Head Include - Modular HTML head section
// Simple procedural PHP with English comments
// Based on Star50 pattern adapted for Nova design

// Default page title if not set
if (!isset($page_title)) {
  $page_title = ($nova_settings['admin_name'] ?? 'Nova Admin') . ' - ' . __admin('head.default_title_suffix');
}

// Default page description if not set
if (!isset($page_description)) {
  $page_description = __admin('head.default_description');
}

?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="author" content="Daniele D'Andreti">
<meta name="description" content="<?= htmlspecialchars($page_description) ?>">
<meta name="robots" content="noindex, nofollow">
<title><?= htmlspecialchars($page_title) ?></title>

<!-- Bootstrap 5.3.8 + Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">

<?php
// Nova web path for CSS/JS/images
// Defined centrally in legas/nova_config.php based on environment
// If not loaded (edge case), use fallback
if (!defined('NOVA_WEB_PATH')) {
    $nova_web_path = '/nova'; // Fallback default
} else {
    $nova_web_path = NOVA_WEB_PATH;
}
?>
<!-- Nova System CSS -->
<link href="<?= $nova_web_path ?>/css/nova-system.css" rel="stylesheet">