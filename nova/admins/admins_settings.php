<?php
// Nova Super Admin Settings - Global system configuration
// Session management and database connection
include '../inc/inc_nova_session.php';

// Page configuration
$page_title = __admin('settings.page.title') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('settings.page.subtitle');

// Success/Error messages
$success_message = $_SESSION['success'] ?? null;
$error_message = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="<?= $nova_lang_code ?>">
<head>
  <?php include '../inc/inc_nova_head.php'; ?>
</head>

<body class="nova-layout">
  <!-- Navigation -->
  <?php include '../inc/inc_nova_navigation.php'; ?>

  <!-- Main Content -->
  <main class="nova-main-content" role="main" id="main-content">
    <div class="container-nova py-4">

      <!-- Page Header -->
      <header class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="page-title">
              <i class="bi bi-gear-fill me-2"></i><?= __admin('settings.page.title') ?>
          </h1>
          <p class="page-subtitle mb-1">
              <i class="bi bi-shield-lock-fill me-1"></i>
              <?= __admin('settings.page.access') ?>
          </p>
          <p class="page-subtitle mb-0">
              <i class="bi bi-file-earmark-code me-1"></i>
              <?= __admin('settings.page.config_file') ?> <code>nova/conf/nova_config_values.php</code>
          </p>
        </div>
        <div>
            <a href="admins_list.php" class="btn btn-primary nova-btn-action">
                <i class="bi bi-arrow-left"></i><?= __admin('settings.page.back_to_list') ?>
            </a>
        </div>
        </div>
      </header>

      <!-- Success Message -->
      <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle-fill me-2"></i>
          <?= $success_message ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Error Message -->
      <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <?= $error_message ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Utilities Section -->
      <section class="content-section mb-5">
                  <!-- Install Cleanup & Log Management -->
                  <div class="row g-4">

                    <!-- Card 7: Install Cleanup (1/3 Width) -->
                    <div class="col-md-4">
                      <div class="card h-100">
                        <div class="card-header bg-light border-bottom">
                          <h6 class="mb-0 fw-bold">
                            <i class="bi bi-trash3 me-2"></i><?= __admin('settings.cleanup.title') ?>
                          </h6>
                        </div>
                        <div class="card-body d-flex flex-column">
                          <?php
                          // Check installation state
                          $install_dir = '../../install';
                          $install_dir_exists = is_dir($install_dir);
                          $lock_file_exists = file_exists('../../.installed');
                          ?>

                          <?php if ($install_dir_exists): ?>
                            <!-- CASE 1: /install/ present — cleanup needed -->

                            <!-- Warning -->
                            <div class="alert alert-warning mb-3">
                              <i class="bi bi-exclamation-triangle-fill me-2"></i>
                              <strong><?= __admin('settings.cleanup.files_detected') ?></strong>
                            </div>

                            <!-- Box 1: File to remove + security note -->
                            <div class="alert alert-warning border small mb-3">
                              <small class="text-muted d-block mb-2"><?= __admin('settings.cleanup.files_to_remove') ?></small>
                              <div class="text-danger mb-2">
                                <i class="bi bi-folder-fill me-1"></i>
                                <code>/install/</code>
                              </div>
                              <i class="bi bi-shield-exclamation me-1"></i>
                              <?= __admin('settings.cleanup.security_note') ?>
                            </div>

                            <?php if ($lock_file_exists): ?>
                            <!-- Box 2: Safety lock info -->
                            <div class="alert alert-light border small mb-3">
                              <i class="bi bi-lock-fill me-1"></i>
                              <?= __admin('settings.cleanup.safety_lock_note') ?>
                            </div>
                            <?php endif; ?>

                            <!-- Cleanup button -->
                            <a href="admins_cleanup_install.php?csrf_token=<?= $_SESSION['csrf_token'] ?>"
                               class="btn btn-danger btn-sm w-100 mt-auto"
                               onclick="return confirm('<?= __admin('settings.cleanup.remove_confirm') ?>');">
                              <i class="bi bi-trash3-fill me-1"></i>
                              <?= __admin('settings.cleanup.remove_button') ?>
                            </a>

                          <?php else: ?>
                            <!-- CASE 2 & 3: /install/ removed — system clean -->

                            <div class="alert alert-success mb-3">
                              <i class="bi bi-check-circle-fill me-2"></i>
                              <strong><?= __admin('settings.cleanup.system_clean') ?></strong>
                              <p class="mb-0 mt-2 small"><?= __admin('settings.cleanup.system_clean_desc') ?></p>
                            </div>

                            <?php if ($lock_file_exists): ?>
                            <!-- Safety lock info -->
                            <div class="alert alert-light border small mb-0">
                              <i class="bi bi-lock-fill me-1"></i>
                              <?= __admin('settings.cleanup.safety_lock_note') ?>
                            </div>
                            <?php endif; ?>

                          <?php endif; ?>

                        </div>
                      </div>
                    </div>

                    <!-- Card 8: Log Management (2/3 Width) -->
                    <div class="col-md-8">
                      <div class="card h-100">
                        <div class="card-header bg-light border-bottom">
                          <h6 class="mb-0 fw-bold">
                            <i class="bi bi-file-earmark-text me-2"></i><?= __admin('settings.log.title') ?>
                          </h6>
                        </div>
                        <div class="card-body py-2">
                          <?php
                          // Log file path (from centralized constant in nova_config.php)
                          $log_file = NOVA_LOG_PATH;

                          // Auto-create log file if it doesn't exist
                          if (!file_exists($log_file)) {
                            touch($log_file);
                            chmod($log_file, 0644);
                          }

                          $log_exists = file_exists($log_file);

                          if ($log_exists) {
                            $log_size_bytes = filesize($log_file);
                            $log_size_kb = round($log_size_bytes / 1024, 2);
                            $log_size_mb = round($log_size_bytes / (1024 * 1024), 2);
                            $log_modified = filemtime($log_file);

                            // Read last 100 lines
                            $log_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                            $total_lines = count($log_lines);
                            $last_100_lines = array_slice($log_lines, -100);
                          }
                          ?>

                          <?php if ($log_exists): ?>
                            <!-- Log Info + Actions - Single Line -->
                            <div class="d-flex justify-content-between align-items-center">
                              <!-- Left: Log Info -->
                              <div class="text-muted nova-log-info-text">
                                <i class="bi bi-file-earmark-text me-1"></i>
                                error.log
                                <span class="mx-2">•</span>
                                <i class="bi bi-hdd me-1"></i>
                                <?= $log_size_mb > 1 ? $log_size_mb . ' MB' : $log_size_kb . ' KB' ?>
                                <span class="mx-2">•</span>
                                <i class="bi bi-list-ol me-1"></i>
                                <?= number_format($total_lines, 0, ',', '.') ?> <?= __admin('settings.log.lines') ?>
                              </div>

                              <!-- Right: Action Buttons -->
                              <div class="d-flex gap-3">
                                <!-- Archive Log Button -->
                                <a href="admins_log_archive.php?csrf_token=<?= $_SESSION['csrf_token'] ?>"
                                   class="btn btn-outline-primary btn-sm"
                                   onclick="return confirm('<?= __admin('settings.log.archive_confirm') ?>');">
                                  <i class="bi bi-archive me-1"></i><?= __admin('settings.log.archive_button') ?>
                                </a>

                                <!-- Toggle Accordion Button -->
                                <a href="#logViewerAccordion"
                                   class="btn btn-primary btn-sm nova-log-toggle collapsed"
                                   data-bs-toggle="collapse"
                                   aria-expanded="false"
                                   aria-controls="logViewerAccordion">
                                  <i class="bi bi-chevron-down me-1"></i><?= __admin('settings.log.view_last') ?>
                                </a>
                              </div>
                            </div>

                            <!-- Accordion: Log Viewer -->
                            <div class="collapse mt-3" id="logViewerAccordion">
                              <div class="bg-light border rounded p-3">
                                <h6 class="text-muted mb-3">
                                  <i class="bi bi-eye me-2"></i><?= __admin('settings.log.last_records') ?>
                                  <small class="text-muted">(<?= number_format($total_lines, 0, ',', '.') ?> <?= __admin('settings.log.total') ?>)</small>
                                </h6>
                                <div class="bg-white border rounded p-3 nova-log-viewer">
                                  <?php if (!empty($last_100_lines)): ?>
                                    <?php foreach ($last_100_lines as $line): ?>
                                      <?php
                                      // Syntax highlighting - Standard black text, color only for errors/warnings
                                      $line_class = '';

                                      // ERROR patterns - Red
                                      if (preg_match('/\b(ERROR|Fatal|Failed|Exception)\b/i', $line)) {
                                        $line_class = 'text-danger fw-semibold';
                                      }
                                      // WARNING patterns - Yellow/Orange
                                      elseif (preg_match('/\b(WARNING|Notice|Deprecated)\b/i', $line)) {
                                        $line_class = 'text-warning';
                                      }
                                      ?>
                                      <div class="<?= $line_class ?> mb-1">
                                        <?= htmlspecialchars($line) ?>
                                      </div>
                                    <?php endforeach; ?>
                                  <?php else: ?>
                                    <p class="text-muted fst-italic mb-0"><?= __admin('settings.log.no_records') ?></p>
                                  <?php endif; ?>
                                </div>
                              </div>
                            </div>

                          <?php else: ?>
                            <!-- Log file doesn't exist -->
                            <div class="alert alert-warning mb-0">
                              <i class="bi bi-exclamation-triangle me-2"></i>
                              <strong><?= __admin('settings.log.log_not_found') ?></strong><br>
                              <?= __admin('settings.log.log_auto_create') ?>
                            </div>
                          <?php endif; ?>

                        </div>
                      </div>
                    </div>

                  </div>
      </section>

    </div>
  </main>

  <!-- Footer -->
  <?php include '../inc/inc_nova_footer.php'; ?>


</body>
</html>
