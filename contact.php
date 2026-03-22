<?php
/**
 * Star51 - Contact Page
 * Contact page with form and location info
 */

// Start session with secure cookie parameters
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
  'httponly' => true,
  'samesite' => 'Lax'
]);
session_start();

// Generate CSRF token if not present
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load language file early (needed for $page_title)
require_once 'inc/inc_star51_lang.php';

// Page configuration
$page_title = __front('nav.contact');
$page_description = __front('contact.subtitle');
$current_page = 'contact';

// Include common HEAD
include 'inc/inc_head.php';
?>

  <!-- Page-specific: Contact Form Loading Overlay Styles -->
  <style>
    /* Contact Form Submit Loading Overlay - JavaScript-driven overlay for form submission */
    #contactLoadingOverlay {
      display: none; /* Hidden by default, shown by JS with display: flex */
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      z-index: 9999;
      align-items: center;
      justify-content: center;
    }

    #contactLoadingOverlay > div {
      position: relative;
      background-color: white;
      border-radius: 10px;
      padding: 2rem;
      max-width: 400px;
      width: 90%;
      text-align: center;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    #closeContactOverlay {
      position: absolute;
      top: 10px;
      right: 10px;
      background: transparent;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #6c757d;
      line-height: 1;
    }

    #closeContactOverlay:hover {
      color: #495057;
    }

    .progress-container {
      width: 100%;
      margin-bottom: 1.5rem;
    }

    #contactLoadingOverlay .progress {
      height: 1.5rem;
    }
  </style>

<?php
// Include common NAVBAR
include 'inc/inc_navbar.php';
?>

  <!-- ========== MAIN CONTENT SECTION ========== -->
  <!-- Contact page content -->
  <main class="content-section py-5 star51-main-no-carousel" role="main" id="main-content">
    <div class="container">

      <!-- Content section header -->
      <header class="section-title text-center mb-5 rounded-4 p-4 star51-section-header">
        <h1 class="display-6 mb-3"><?= __front('contact.title') ?></h1>
        <p class="lead"><?= __front('contact.subtitle') ?></p>
        <p class="lead"><?= __front('contact.subtitle_2') ?></p>
      </header>

      <?php if (isset($_SESSION['contact_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle me-2"></i><?= $_SESSION['contact_success'] ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['contact_success']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['contact_errors'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle me-2"></i><strong><?= __front('contact.error') ?></strong>
          <ul class="mb-0 mt-2">
            <?php foreach ($_SESSION['contact_errors'] as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['contact_errors']); ?>
      <?php endif; ?>

      <?php
      // Retrieve form data if there was an error
      $form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
      unset($_SESSION['form_data']);
      ?>

      <!-- Contact Form Section -->
      <section class="mb-5">
        <div class="row">
          <div class="col-12">
            <!-- Form Card Container -->
            <div class="card star51-card">
              <div class="card-body p-4">
                <form id="contact-form" class="needs-validation star51-form" action="contact_store.php" method="POST">
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                  <div class="row g-4">

                    <!-- First Row: Nome, Cognome, Email -->
                    <div class="col-md-4">
                      <label for="firstName" class="form-label star51-label"><?= __front('contact.first_name') ?> *</label>
                      <input type="text" class="form-control form-control-lg" id="firstName" name="first_name" value="<?= htmlspecialchars($form_data['first_name'] ?? '') ?>" autocomplete="given-name" required aria-required="true" aria-describedby="firstNameFeedback">
                      <div class="invalid-feedback" id="firstNameFeedback">
                        <?= __front('contact.validation.first_name') ?>
                      </div>
                    </div>

                    <div class="col-md-4">
                      <label for="lastName" class="form-label star51-label"><?= __front('contact.last_name') ?> *</label>
                      <input type="text" class="form-control form-control-lg" id="lastName" name="last_name" value="<?= htmlspecialchars($form_data['last_name'] ?? '') ?>" autocomplete="family-name" required aria-required="true" aria-describedby="lastNameFeedback">
                      <div class="invalid-feedback" id="lastNameFeedback">
                        <?= __front('contact.validation.last_name') ?>
                      </div>
                    </div>

                    <div class="col-md-4">
                      <label for="email" class="form-label star51-label"><?= __front('contact.email') ?> *</label>
                      <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" autocomplete="email" required aria-required="true" aria-describedby="emailFeedback">
                      <div class="invalid-feedback" id="emailFeedback">
                        <?= __front('contact.validation.email') ?>
                      </div>
                    </div>

                    <!-- Second Row: Phone and Captcha -->
                    <div class="col-md-6">
                      <label for="phone" class="form-label star51-label"><?= __front('contact.phone') ?></label>
                      <input type="tel" class="form-control form-control-lg" id="phone" name="phone" value="<?= htmlspecialchars($form_data['phone'] ?? '') ?>" autocomplete="tel" aria-describedby="phoneHint">
                      <small class="form-text text-muted" id="phoneHint"><?= __front('contact.phone_hint') ?></small>
                    </div>

                    <div class="col-md-6">
                      <?php // Include captcha generator
                      include 'inc/inc_captcha.php'; ?>
                      <label for="captcha" class="form-label star51-label"><?= __front('contact.captcha_label') ?> <strong><?= $captcha_question ?></strong> *</label>
                      <input type="text" class="form-control form-control-lg" id="captcha" name="captcha" value="<?= htmlspecialchars($form_data['captcha'] ?? '') ?>" required aria-required="true" aria-describedby="captchaHint captchaFeedback">
                      <small class="form-text text-muted" id="captchaHint"><?= __front('contact.captcha_hint') ?></small>
                      <div class="invalid-feedback" id="captchaFeedback">
                        <?= __front('contact.validation.captcha') ?>
                      </div>
                    </div>

                    <!-- Third Row: Message -->
                    <div class="col-12">
                      <label for="message" class="form-label star51-label"><?= __front('contact.message') ?> *</label>
                      <textarea class="form-control form-control-lg" id="message" name="message" rows="5" placeholder="<?= __front('contact.message_placeholder') ?>" required aria-required="true" aria-describedby="messageFeedback"><?= htmlspecialchars($form_data['message'] ?? '') ?></textarea>
                      <div class="invalid-feedback" id="messageFeedback"><?= __front('contact.validation.message') ?></div>
                    </div>

                    <!-- Fourth Row: Privacy Consent (Solo Edition - Full Width) -->
                    <div class="col-12">
                      <div class="p-3 rounded-3 bg-star51-cream border text-center">
                        <div class="form-check star51-form-check d-inline-block text-start">
                          <input class="form-check-input star51-checkbox" type="checkbox" id="privacy" name="privacy" <?= isset($form_data['privacy']) ? 'checked' : '' ?> required aria-required="true" aria-describedby="privacyFeedback">
                          <label class="form-check-label star51-checkbox-label" for="privacy">
                            <strong><?= __front('contact.privacy_label') ?>
                            <a href="policy.php" target="_blank" rel="noopener noreferrer" class="star51-link-base star51-link-base--content"><?= __front('contact.privacy_link') ?></a> *</strong>
                          </label>
                          <div class="invalid-feedback" id="privacyFeedback">
                            <?= __front('contact.validation.privacy') ?>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Submit Button (Solo Edition - No Newsletter) -->
                    <div class="col-12 text-center mt-4">
                      <button type="submit" class="btn btn-star51 btn-pill contact-form-btn">
                        <?= __front('contact.submit') ?>
                      </button>
                      <p class="small text-muted mt-3 mb-0">
                        <i class="bi bi-shield-check me-1"></i>
                        <?= __front('contact.data_protection') ?>
                      </p>
                    </div>

                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ========== CONTACT INFO & MAP SECTION ========== -->

      <section id="map-section" class="mb-5">
        <!-- Section Header -->
        <header class="text-center mb-4">
          <h2 class="display-6 mb-3">
            <i class="bi bi-geo-alt-fill me-2"></i><?= __front('contact.locations_title') ?>
          </h2>
          <p class="lead"><?= __front('contact.locations_subtitle') ?></p>
        </header>

        <!-- Offices Grid -->
        <div class="row g-4">
          <!-- Genoa Office -->
          <div class="col-lg-6">
            <div class="card star51-card-base star51-card-base--fixed">
              <div class="card-body">
                <div class="row g-3">
                  <!-- Address -->
                  <div class="col-6">
                    <div class="d-flex align-items-start">
                      <i class="bi bi-geo-alt-fill text-star51-orange me-3 star51-icon-sm"></i>
                      <div>
                        <h3 class="h5 card-title mb-2"><?= __front('contact.location_1.title') ?></h3>
                        <p class="card-text mb-0">
                          <strong>Corso Sardegna 156</strong><br>
                          16100 Genova (GE)<br>
                          Italia
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- Contact Info -->
                  <div class="col-6 text-center">
                    <small class="text-muted d-block"><?= __front('contact.location_labels.working_hours') ?></small>
                    <strong class="d-block mb-2"><?= __front('contact.location_labels.weekdays') ?> 9:00-18:00</strong>

                    <small class="text-muted d-block"><?= __front('contact.location_labels.phone') ?></small>
                    <strong class="d-block mb-2">+39 010 123 4567</strong>

                    <small class="text-muted d-block"><?= __front('contact.location_labels.email') ?></small>
                    <strong>info@yourdomain.com</strong>
                  </div>

                  <!-- Map Placeholder -->
                  <div class="col-12">
                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center star51-map-placeholder"
                         role="img"
                         aria-label="Map placeholder - Genova office location">
                      <div class="text-center text-muted">
                        <i class="bi bi-map star51-icon-md" aria-hidden="true"></i>
                        <p class="mt-2 mb-0">Map Genova</p>
                        <small>Google Maps Integration</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Frauenfeld Office -->
          <div class="col-lg-6">
            <div class="card star51-card-base star51-card-base--fixed">
              <div class="card-body">
                <div class="row g-3">
                  <!-- Address -->
                  <div class="col-6">
                    <div class="d-flex align-items-start">
                      <i class="bi bi-geo-alt-fill text-star51-orange me-3 star51-icon-sm"></i>
                      <div>
                        <h3 class="h5 card-title mb-2"><?= __front('contact.location_2.title') ?></h3>
                        <p class="card-text mb-0">
                          <strong>Industriestrasse 15</strong><br>
                          8500 Frauenfeld<br>
                          Svizzera
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- Contact Info -->
                  <div class="col-6 text-center">
                    <small class="text-muted d-block"><?= __front('contact.location_labels.working_hours') ?></small>
                    <strong class="d-block mb-2"><?= __front('contact.location_labels.weekdays') ?> 9:00-18:00</strong>

                    <small class="text-muted d-block"><?= __front('contact.location_labels.phone') ?></small>
                    <strong class="d-block mb-2">+41 52 123 4567</strong>

                    <small class="text-muted d-block"><?= __front('contact.location_labels.email') ?></small>
                    <strong>swiss@yourdomain.com</strong>
                  </div>

                  <!-- Map Placeholder -->
                  <div class="col-12">
                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center star51-map-placeholder"
                         role="img"
                         aria-label="Map placeholder - Frauenfeld office location">
                      <div class="text-center text-muted">
                        <i class="bi bi-map star51-icon-md" aria-hidden="true"></i>
                        <p class="mt-2 mb-0">Map Frauenfeld</p>
                        <small>Google Maps Integration</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
  </main>

  <!-- Contact Form Loading Overlay -->
  <div id="contactLoadingOverlay"
       role="dialog"
       aria-modal="true"
       aria-labelledby="contactOverlayTitle">
    <div>
      <!-- Close button -->
      <button type="button" id="closeContactOverlay" aria-label="<?= __front('contact.overlay.close') ?>">&times;</button>

      <!-- Progress Bar Container -->
      <div class="progress-container" aria-live="polite">
        <div class="progress">
          <div class="progress-bar progress-bar-striped progress-bar-animated"
               id="contactProgressBar"
               role="progressbar"
               aria-valuenow="0"
               aria-valuemin="0"
               aria-valuemax="100"
               style="width: 0%">
          </div>
        </div>
      </div>

      <!-- Message -->
      <h5 id="contactOverlayTitle" class="mb-2"><?= __front('contact.overlay.sending') ?></h5>
      <p class="text-muted mb-0">
        <?= __front('contact.overlay.wait') ?>
      </p>
    </div>
  </div>

<?php
// Include common FOOTER
include 'inc/inc_footer.php';

// Include common SCRIPTS
include 'inc/inc_scripts.php';
?>

  <!-- Contact Form Loading Overlay Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const contactForm = document.getElementById('contact-form');
      const overlay = document.getElementById('contactLoadingOverlay');
      const progressBar = document.getElementById('contactProgressBar');
      const closeBtn = document.getElementById('closeContactOverlay');
      const successAlert = document.querySelector('.alert-success');

      // ============================================================
      // SCENARIO 1: Page reload AFTER server finished (success message exists)
      // ============================================================
      if (successAlert && overlay && progressBar) {
        // Server finished! Complete the animation from 75% to 100%
        overlay.style.display = 'flex';
        overlay.style.opacity = '1';
        closeBtn.focus();
        progressBar.style.width = '75%';
        progressBar.setAttribute('aria-valuenow', '75');

        // Hide success alert initially (will show after animation)
        successAlert.style.display = 'none';

        // Complete to 100%
        setTimeout(function() {
          progressBar.style.width = '100%';
          progressBar.setAttribute('aria-valuenow', '100');
        }, 500);

        // Fade out overlay
        setTimeout(function() {
          overlay.style.opacity = '0';
          overlay.style.transition = 'opacity 0.5s';
        }, 1500);

        // Remove overlay and show success message
        setTimeout(function() {
          overlay.style.display = 'none';
          successAlert.style.display = 'block'; // Show success alert
        }, 2000);
      }

      // ============================================================
      // SCENARIO 2: Form submit (first request, go to 75% and STOP)
      // ============================================================
      if (contactForm && overlay && progressBar) {
        contactForm.addEventListener('submit', function(e) {
          // Check Bootstrap validation first
          if (!contactForm.checkValidity()) {
            // Validation failed, let Bootstrap show errors
            e.preventDefault();
            e.stopPropagation();
            contactForm.classList.add('was-validated');
            return;
          }

          // Validation passed, show overlay
          e.preventDefault();
          contactForm.querySelector('button[type="submit"]').disabled = true;

          // Show overlay and move focus
          overlay.style.display = 'flex';
          overlay.style.opacity = '1';
          closeBtn.focus();

          // Animate progress bar to 75% ONLY (fake timer)
          setTimeout(function() {
            progressBar.style.width = '25%';
            progressBar.setAttribute('aria-valuenow', '25');
          }, 1000);

          setTimeout(function() {
            progressBar.style.width = '50%';
            progressBar.setAttribute('aria-valuenow', '50');
          }, 2000);

          setTimeout(function() {
            progressBar.style.width = '75%';
            progressBar.setAttribute('aria-valuenow', '75');
            // STOP HERE - submit and let server do its thing
            contactForm.submit(); // Real form submission
          }, 3000);
        });
      }

      // ============================================================
      // Close button functionality
      // ============================================================
      if (closeBtn && overlay) {
        closeBtn.addEventListener('click', function() {
          overlay.style.display = 'none';
          overlay.style.opacity = '1';
        });
      }

      // ============================================================
      // ESC key closes overlay
      // ============================================================
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay && overlay.style.display === 'flex') {
          overlay.style.display = 'none';
          overlay.style.opacity = '1';
        }
      });
    });
  </script>

