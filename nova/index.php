<?php
// ============================================
// PRE-INSTALLATION CHECK
// ============================================
if (!file_exists(__DIR__ . '/../.installed')) {
  if (file_exists(__DIR__ . '/../install/index.php')) {
    header('Location: ../install/');
    exit();
  }
  // No lock file AND no installer — broken state
  http_response_code(503);
  ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Star51 — Not Installed</title>
  <style>
    body { font-family: system-ui, -apple-system, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f8f9fa; }
    .msg { text-align: center; padding: 40px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.1); max-width: 440px; }
    h1 { font-size: 1.3rem; color: #dc3545; margin: 0 0 16px; }
    p { color: #666; margin: 0; line-height: 1.8; }
    code { background: #f1f1f1; padding: 2px 6px; border-radius: 3px; font-size: 0.9em; }
  </style>
</head>
<body>
  <div class="msg">
    <h1>Star51 — Not Installed</h1>
    <p>The installation lock file is missing and no installer was found.<br>
    Please restore the <code>/install/</code> folder and run the setup wizard.</p>
  </div>
</body>
</html>
<?php exit();
}

session_start();
require_once 'legas/nova_config.php';
require_once 'inc/inc_nova_constants.php';

// ============================================================================
// Language System (i18n)
// ============================================================================
require_once 'inc/inc_nova_lang.php';

// ============================================================================
// Rate Limiting - Check if IP is locked (DB-based)
// ============================================================================
require_once 'inc/inc_nova_rate_limit_check.php';

// ============================================================================
// SOLO EDITION: Simple Login (Username + Password only, NO 2FA)
// ============================================================================
if (!empty($_POST['username']) && !empty($_POST['password']) && !$rate_limit_locked) {
  $username = $_POST['username'];
  $password = $_POST['password'];

  // Query to verify user credentials
  $login_query = "
    SELECT id_admin, username, password, email, first_name, last_name, is_active
    FROM ns_admins
    WHERE username = ?
      AND is_active = '1'
  ";

  $stmt = mysqli_prepare($conn, $login_query);
  mysqli_stmt_bind_param($stmt, 's', $username);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if ($result && mysqli_num_rows($result) > 0) {
    $admin_data = mysqli_fetch_array($result, MYSQLI_ASSOC);

    // Verify password hash
    if (password_verify($password, $admin_data['password'])) {
      // ======================================================================
      // PASSWORD CORRECT - Create session directly (Solo edition: no 2FA)
      // ======================================================================

      // Reset rate limiting on successful login
      $rate_limit_username = $admin_data['username'];
      require_once 'inc/inc_nova_rate_limit_reset.php';

      // Regenerate session ID to prevent session fixation attacks
      session_regenerate_id(true);

      // Create Nova session
      $_SESSION['nova_logged'] = true;
      $_SESSION['admin_id'] = $admin_data['id_admin'];
      $_SESSION['admin_username'] = $admin_data['username'];
      $_SESSION['admin_first_name'] = $admin_data['first_name'];
      $_SESSION['admin_last_name'] = $admin_data['last_name'];
      $_SESSION['admin_level'] = 0; // Solo Edition: single Super Admin

      // Redirect to dashboard
      header('Location: home.php');
      exit();
    } else {
      // ======================================================================
      // LOGIN FAILED - Increment attempt counter
      // ======================================================================
      $rate_limit_username = $username;
      require_once 'inc/inc_nova_rate_limit_increment.php';
      $login_error = true;
    }
  } else {
    // ========================================================================
    // USER NOT FOUND - Increment attempt counter (prevent username enumeration)
    // ========================================================================
    $rate_limit_username = $username ?? '';
    require_once 'inc/inc_nova_rate_limit_increment.php';
    $login_error = true;
  }
}

// Set page variables for includes
$page_title = __admin('login.title') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('pages.dashboard_overview') . ' ' . $nova_settings['admin_name'];
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

      <!-- Login Card Container -->
      <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-11 col-sm-8 col-md-6 col-lg-5 col-xl-4">

          <!-- Login Card -->
          <div class="nova-card login-card">

            <!-- Card Header -->
            <div class="nova-card-header text-center bg-nova-mint-dark">
              <div class="login-logo mb-3">
                <i class="bi bi-star-fill"></i>
              </div>
              <h1 class="h4 mb-2"><?= __admin('login.title') ?></h1>
              <p class="text-muted mb-0"><?= __admin('login.subtitle') ?></p>
            </div>

            <!-- Card Body -->
            <div class="nova-card-body">

              <!-- Lockout Alert (Priority over all messages) -->
              <?php if ($rate_limit_locked): ?>
              <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-shield-lock me-3 fs-1"></i>
                <div><?= htmlspecialchars($rate_limit_message) ?></div>
              </div>
              <?php endif; ?>

              <!-- Login Error -->
              <?php if (isset($login_error) && !$rate_limit_locked): ?>
              <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <div>
                  <?= __admin('messages.credentials_invalid') ?>
                  <?php
                  $remaining_attempts = defined('NOVA_MAX_LOGIN_ATTEMPTS') ? NOVA_MAX_LOGIN_ATTEMPTS - $rate_limit_attempts : 0;
                  if ($remaining_attempts > 0 && $rate_limit_attempts > 0): ?>
                    <br><small class="text-muted"><?= __admin('messages.attempts_remaining') ?>: <?= $remaining_attempts ?></small>
                  <?php endif; ?>
                </div>
              </div>
              <?php endif; ?>

              <!-- Success Message -->
              <?php if (isset($_SESSION['login_success'])): ?>
              <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <div><?= htmlspecialchars($_SESSION['login_success']) ?></div>
              </div>
              <?php unset($_SESSION['login_success']); ?>
              <?php endif; ?>

              <!-- ============================================================ -->
              <!-- LOGIN FORM (Solo Edition - username + password only)         -->
              <!-- ============================================================ -->
              <form id="loginForm"
                    action="index.php"
                    method="post"
                    novalidate
                    <?= $rate_limit_locked ? 'class="nova-form-locked"' : '' ?>>
                <div class="form-floating mb-3">
                  <input type="text"
                         class="form-control"
                         id="username"
                         name="username"
                         placeholder="Username"
                         required
                         autofocus
                         value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                  <label for="username">
                    <i class="bi bi-person me-2"></i><?= __admin('labels.username') ?>
                  </label>
                </div>

                <div class="form-floating mb-4">
                  <input type="password"
                         class="form-control"
                         id="password"
                         name="password"
                         placeholder="Password"
                         required>
                  <label for="password">
                    <i class="bi bi-lock me-2"></i><?= __admin('labels.password') ?>
                  </label>
                </div>

                <button type="submit"
                        class="btn btn-outline-primary fs-5 w-100 mb-3"
                        <?= $rate_limit_locked ? 'disabled' : '' ?>>
                  <i class="bi bi-box-arrow-in-right me-2"></i>
                  <?= __admin('buttons.login') ?>
                </button>
              </form>

              <!-- Forgot Password -->
              <div class="text-center">
                <?php if ($rate_limit_locked): ?>
                <button class="btn btn-outline-secondary btn-lg w-100 btn-mint-secondary" disabled>
                  <i class="bi bi-question-circle me-2"></i>
                  <?= __admin('login.forgot_password') ?>
                </button>
                <?php else: ?>
                <a href="password-reset.php" class="btn btn-outline-secondary btn-lg w-100 btn-mint-secondary">
                  <i class="bi bi-question-circle me-2"></i>
                  <?= __admin('login.forgot_password') ?>
                </a>
                <?php endif; ?>
              </div>

            </div>

            <!-- Card Footer -->
            <div class="nova-card-footer login-footer-neutral d-flex justify-content-between align-items-center">
              <small class="text-muted">
                NovaStar51 Solo
              </small>
              <small class="text-muted">
                <a href="https://medium.com/@daniele.dandreti" target="_blank" rel="noopener noreferrer" title="Passione + Tecnologia + Sogni = Daniele D'Andreti" class="footer-icons-link">
                  <i class="bi bi-person-raised-hand" aria-hidden="true"></i> +
                  <i class="bi bi-laptop" aria-hidden="true"></i> +
                  <i class="bi bi-balloon-heart" aria-hidden="true"></i> =
                  <i class="bi bi-star-fill" aria-hidden="true"></i>
                </a>
              </small>
            </div>

          </div>

        </div>
      </div>

    </div>
  </main>

  <!-- Bootstrap JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
