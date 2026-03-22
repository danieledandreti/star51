<?php
/**
 * Star51 - Privacy & Cookie Policy Page
 * Privacy policy and cookie policy page
 * i18n: Header from JSON, card content from include files
 *
 * POLICY CONFIGURATION:
 * To insert your Privacy Policy, edit the file corresponding to the language:
 *   - Italian: inc/inc_policy_card_it.php
 *   - English: inc/inc_policy_card_en.php
 */

// Load language file
require_once 'inc/inc_star51_lang.php';

// Page configuration
$page_title = __front('policy.page_title');
$page_description = __front('policy.page_description');
$current_page = 'policy';

// Include common HEAD
include 'inc/inc_head.php';

// Include common NAVBAR
include 'inc/inc_navbar.php';

// Include policy card based on language (fallback to Italian)
$lang = star51_get_current_lang();
$policy_card_file = __DIR__ . "/inc/inc_policy_card_{$lang}.php";

if (!file_exists($policy_card_file)) {
 $policy_card_file = __DIR__ . '/inc/inc_policy_card_it.php';
}
?>

 <!-- ========== PRIVACY POLICY CONTENT ========== -->
 <main class="content-section py-5 star51-main-no-carousel" role="main" id="main-content">
  <div class="container">

   <!-- Section header -->
   <header class="section-title text-center mb-5 rounded-4 p-4 star51-section-header">
    <h1 class="display-6 mb-3"><?= __front('policy.title') ?></h1>
    <p class="lead"><?= __front('policy.subtitle') ?></p>
   </header>

   <!-- Privacy & Cookie Policy Content -->
   <section class="policy-content">
    <div class="row">
     <div class="col-lg-12">
      <?php include $policy_card_file; ?>
     </div>
    </div>
   </section>

  </div>
 </main>

<?php
// Include common FOOTER
include 'inc/inc_footer.php';

// Include common SCRIPTS
include 'inc/inc_scripts.php';
?>
