<?php
session_start();

// Load database connection
require_once 'legas/nova_config.php';

// Language System (i18n)
require_once 'inc/inc_nova_lang.php';

// Rate Limiting - Check if IP is locked (DB-based)
require_once 'inc/inc_nova_rate_limit_check.php';

// Set page variables for includes
$page_title = __admin('password_reset.title') . ' | ' . $nova_settings['admin_name'];
$page_description = 'Nova Administration - Password Recovery';
?>
<!DOCTYPE html>
<html lang="<?= $nova_lang_code ?>">
<head>
  <?php include 'inc/inc_nova_head.php'; ?>
</head>
<body class="nova-layout login-page nova-bg-mint-soft">

  <!-- Main Content -->
  <main class="nova-main-content" role="main" id="main-content">
    <div class="container-nova py-4">

      <!-- Password Reset Card Container -->
      <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-11 col-sm-8 col-md-6 col-lg-5 col-xl-4">

          <!-- Password Reset Card -->
          <div class="nova-card login-card">

            <!-- Card Header -->
            <div class="nova-card-header text-center bg-nova-mint-dark">
              <div class="login-logo mb-3">
                <i class="bi bi-star-fill"></i>
              </div>
              <h1 class="h4 mb-2"><?= __admin('login.title') ?></h1>
              <p class="text-muted mb-0"><?= __admin('password_reset.title') ?></p>
            </div>

            <!-- Card Body -->
            <div class="nova-card-body">

              <!-- Lockout Alert (Priority over other messages) -->
              <?php if ($rate_limit_locked): ?>
              <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-shield-lock me-3 fs-1"></i>
                <div><?= htmlspecialchars($rate_limit_message) ?></div>
              </div>
              <?php endif; ?>

              <!-- Success Message (hide if CAPTCHA required) -->
              <?php if (isset($_SESSION['reset_success']) && $rate_limit_attempts < 3): ?>
              <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <div><?= htmlspecialchars($_SESSION['reset_success']) ?></div>
              </div>
              <?php unset($_SESSION['reset_success']); ?>
              <?php endif; ?>

              <!-- Error Messages -->
              <?php if (isset($_SESSION['reset_errors'])): ?>
              <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <div>
                  <?php foreach ($_SESSION['reset_errors'] as $error): ?>
                    <?= htmlspecialchars($error) ?><br>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php unset($_SESSION['reset_errors']); ?>
              <?php endif; ?>

              <!-- Reset Form -->
              <form action="password-reset-send.php" method="post" novalidate <?= $rate_limit_locked ? 'class="nova-form-locked"' : '' ?>>

                <?php
                // Show CAPTCHA FIRST if >= 3 attempts from this IP
                if ($rate_limit_attempts >= 3 && !$rate_limit_locked):
                  // Generate CAPTCHA
                  include '../inc/inc_captcha.php';
                ?>
                <!-- CAPTCHA Section (after 3 attempts) -->
                <div class="alert alert-warning d-flex align-items-start mb-3" role="alert">
                  <i class="bi bi-shield-check me-2 mt-1"></i>
                  <div>
                    <strong><?= __admin('password_reset.security_check') ?></strong><br>
                    <?= __admin('password_reset.security_check_desc') ?>
                  </div>
                </div>

                <div class="form-floating mb-4">
                  <input type="text"
                         class="form-control"
                         id="captcha_answer"
                         name="captcha_answer"
                         placeholder="<?= __admin('labels.message') ?>"
                         required
                         autofocus
                         autocomplete="off">
                  <label for="captcha_answer">
                    <i class="bi bi-calculator me-2"></i><?= htmlspecialchars($captcha_question) ?>
                  </label>
                  <div class="form-text mt-2">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= __admin('password_reset.captcha_hint') ?>
                  </div>
                </div>
                <?php endif; ?>

                <!-- Email Field -->
                <div class="form-floating mb-4">
                  <input type="email"
                         class="form-control"
                         id="email"
                         name="email"
                         placeholder="Email"
                         required
                         <?= ($rate_limit_attempts < 3) ? 'autofocus' : '' ?>
                         value="<?= htmlspecialchars($_SESSION['form_data']['email'] ?? '') ?>">
                  <label for="email">
                    <i class="bi bi-envelope me-2"></i><?= __admin('password_reset.email_label') ?>
                  </label>
                </div>

                <button type="submit" class="btn btn-outline-primary fs-5 w-100 mb-3" <?= $rate_limit_locked ? 'disabled' : '' ?>>
                  <i class="bi bi-send me-2"></i>
                  <?= __admin('password_reset.send_link') ?>
                </button>
              </form>

              <!-- Back to Login -->
              <div class="text-center">
                <a href="index.php" class="btn btn-outline-secondary btn-lg w-100 btn-mint-secondary">
                  <i class="bi bi-arrow-left me-2"></i>
                  <?= __admin('buttons.back_to_login') ?>
                </a>
              </div>

            </div>

            <!-- Card Footer -->
            <div class="nova-card-footer login-footer-light d-flex justify-content-center align-items-center">
              <small class="text-muted text-center">
                <i class="bi bi-info-circle me-1"></i>
                <?= __admin('password_reset.footer_info') ?>
              </small>
            </div>

          </div>

        </div>
      </div>

    </div>
  </main>

  <?php unset($_SESSION['form_data']); ?>
</body>
</html>
