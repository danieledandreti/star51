<?php
/**
 * Star51 - About Page
 * About Us page with company history, values and team
 * i18n: Header from JSON, content from include files
 *
 * CONFIGURAZIONE CONTENUTO:
 * Per modificare il contenuto, edita il file corrispondente alla lingua:
 *   - Italiano: inc/inc_about_content_it.php
 *   - English:  inc/inc_about_content_en.php
 */

// Include language loader
require_once 'inc/inc_star51_lang.php';

// Page configuration
$page_title = __front('about.page_title');
$page_description = __front('about.page_description');
$current_page = 'about';

// Include common HEAD
include 'inc/inc_head.php';

// Include common NAVBAR
include 'inc/inc_navbar.php';

// Include content based on language (fallback to Italian)
$lang = star51_get_current_lang();
$about_content_file = __DIR__ . "/inc/inc_about_content_{$lang}.php";

if (!file_exists($about_content_file)) {
 $about_content_file = __DIR__ . '/inc/inc_about_content_it.php';
}
?>

  <!-- ========== MAIN CONTENT SECTION ========== -->
  <!-- About page content -->
  <main class="content-section py-5 star51-main-no-carousel" role="main" id="main-content">
    <div class="container">

      <!-- Content section header -->
      <header class="section-title text-center mb-5 rounded-4 p-4 star51-section-header">
        <h1 class="display-6 mb-3"><?= __front('about.title') ?></h1>
        <p class="lead"><?= __front('about.subtitle') ?></p>
      </header>

<?php include $about_content_file; ?>

    </div>
  </main>

<?php
// Include FOOTER comune
include 'inc/inc_footer.php';

// Include SCRIPTS comuni
include 'inc/inc_scripts.php';
?>
