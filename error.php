<?php
/**
 * Star51 - Unified Error Page
 * Handles all HTTP error codes with full Star51 layout
 *
 * Usage: error.php?code=404
 * Called by .htaccess ErrorDocument directives
 *
 * Supported codes: 400, 401, 403, 404, 500, 503
 * Unknown codes show a generic error message
 */

// Include language loader
require_once 'inc/inc_star51_lang.php';

// Get and validate error code
$valid_codes = [400, 401, 403, 404, 500, 503];
$error_code = isset($_GET['code']) ? (int)$_GET['code'] : 0;

if (!in_array($error_code, $valid_codes, true)) {
  $error_code = 0; // Will use 'default' translations
}

// Set correct HTTP status code (default to 500 for unknown/missing codes)
http_response_code($error_code > 0 ? $error_code : 500);

// Translation key for this error code
$code_key = $error_code > 0 ? (string)$error_code : 'default';

// Page configuration
$page_title = __front("error_page.{$code_key}.title");
$page_description = __front("error_page.{$code_key}.description");
$current_page = 'error';
$meta_robots = 'noindex, nofollow';

// Icon mapping per error code
$error_icons = [
  400 => 'bi-x-circle',
  401 => 'bi-lock',
  403 => 'bi-shield-x',
  404 => 'bi-search',
  500 => 'bi-exclamation-triangle',
  503 => 'bi-tools',
];
$icon = $error_icons[$error_code] ?? 'bi-question-circle';

// Include common HEAD
include 'inc/inc_head.php';

// Include common NAVBAR
include 'inc/inc_navbar.php';
?>

  <!-- ========== ERROR PAGE ========== -->
  <!-- Unified error page content -->
  <main class="content-section py-5 star51-main-no-carousel" role="main" id="main-content">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6 text-center py-5">

          <!-- Error icon with background circle -->
          <div class="error-icon-circle d-inline-flex align-items-center justify-content-center rounded-circle mb-4">
            <i class="bi <?= $icon ?> text-star51-orange"
               aria-hidden="true"></i>
          </div>

          <!-- Error code badge -->
          <?php if ($error_code > 0): ?>
            <div class="mb-3">
              <span class="badge bg-star51-orange text-white fs-4 px-4 py-2 rounded-pill"><?= $error_code ?></span>
            </div>
          <?php endif; ?>

          <!-- Error heading -->
          <h1 class="display-6 fw-bold mb-3"><?= __front("error_page.{$code_key}.title") ?></h1>

          <!-- Error description (funny message) -->
          <p class="lead text-muted mb-5"><?= __front("error_page.{$code_key}.description") ?></p>

          <!-- Action buttons: primary orange + secondary outline -->
          <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="index.php" class="btn btn-star51 btn-lg btn-pill px-4">
              <i class="bi bi-house me-2" aria-hidden="true"></i><?= __front('error_page.back_home') ?>
            </a>
            <a href="search.php" class="btn btn-outline-star51 btn-lg btn-pill px-4">
              <i class="bi bi-search me-2" aria-hidden="true"></i><?= __front('error_page.search') ?>
            </a>
          </div>

        </div>
      </div>
    </div>
  </main>

<?php
// Include common FOOTER
include 'inc/inc_footer.php';

// Include common SCRIPTS
include 'inc/inc_scripts.php';
?>
